<?php 
$tabela = 'pagar';
require_once("../../../conexao.php");

@session_start();
$id_usuario = @$_SESSION['id'];

$descricao = $_POST['descricao'];
$valor = $_POST['valor'];
$fornecedor = $_POST['fornecedor'];
$funcionario = $_POST['funcionario'];
$vencimento = $_POST['vencimento'];
$data_pgto = $_POST['data_pgto'];
$forma_pgto = $_POST['forma_pgto'];
$frequencia = $_POST['frequencia'];
$obs = $_POST['obs'];
$id = $_POST['id'];
$banco = $_POST['banco'] ?? 0;

$valor = str_replace(',', '.', $valor);
$valorF = @number_format($valor, 2, ',', '.');

if($fornecedor == ""){
	$fornecedor = 0;
}
if($funcionario == ""){
	$funcionario = 0;
}
if($forma_pgto == ""){
	$forma_pgto = 0;
}
if($frequencia == ""){
	$frequencia = 0;
}

if($data_pgto == ""){
	$pgto = '';
	$usu_pgto = '';
	$pago = 'Não';
}else{
	$pgto = " ,data_pgto = '$data_pgto'";
	$usu_pgto = " ,usuario_pgto = '$id_usuario'";
	$pago = 'Sim';
}

// Validação básica
if($descricao == "" and $fornecedor == "0" and $funcionario == "0"){
	echo 'Selecione um Fornecedor ou um Funcionário ou uma Descrição!';
	exit();
}
if($fornecedor != "0" and $funcionario != "0"){
	echo 'Selecione um Fornecedor ou um Funcionário!';
	exit();
}

// Pega arquivo antigo se houver
$query = $pdo->query("SELECT * FROM $tabela WHERE id = '$id'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$foto = (@count($res) > 0) ? $res[0]['arquivo'] : 'sem-foto.png';

// Upload de arquivo
$nome_img = date('d-m-Y H:i:s') .'-'.@$_FILES['foto']['name'];
$nome_img = preg_replace('/[ :]+/' , '-' , $nome_img);
$caminho = '../../images/contas/' .$nome_img;
$imagem_temp = @$_FILES['foto']['tmp_name'];

if(@$_FILES['foto']['name'] != ""){
	$ext = pathinfo($nome_img, PATHINFO_EXTENSION);   
	$ext_permitidas = ['png','jpg','jpeg','gif','pdf','rar','zip','doc','docx','webp','xlsx','xlsm','xls','xml','PNG','JPG','JPEG','GIF','PDF','RAR','ZIP','DOC','DOCX','WEBP'];
	if(in_array($ext, $ext_permitidas)){ 
		if($foto != "sem-foto.png"){
			@unlink('../../images/contas/'.$foto);
		}
		$foto = $nome_img;
		list($largura, $altura) = getimagesize($imagem_temp);
		if($largura > 1400 && in_array($ext, ['png','jpg','jpeg','gif','webp','PNG','JPG','JPEG','GIF','WEBP'])){
			$image = imagecreatefromjpeg($imagem_temp);
			imagejpeg($image, $caminho, 20);
			imagedestroy($image);
		}else{
			move_uploaded_file($imagem_temp, $caminho);
		}
	}else{
		echo 'Extensão de Imagem não permitida!';
		exit();
	}
}

// Se necessário, preenche a descrição com o nome do fornecedor ou funcionário
if($fornecedor != 0 || $funcionario != 0){
	$tab = $fornecedor != 0 ? 'fornecedores' : 'usuarios';
	$id_pessoa = $fornecedor != 0 ? $fornecedor : $funcionario;
	$query = $pdo->query("SELECT * FROM $tab WHERE id = '$id_pessoa'");
	$res = $query->fetchAll(PDO::FETCH_ASSOC);
	$nome_pessoa = (@count($res) > 0) ? $res[0]['nome'] : '';
	if($descricao == ""){
		$descricao = $nome_pessoa;
	}
}

// Verifica se o caixa está aberto para o usuário atual
$query1 = $pdo->query("SELECT * FROM caixas WHERE operador = '$id_usuario' AND data_fechamento IS NULL ORDER BY id DESC LIMIT 1");
$res1 = $query1->fetchAll(PDO::FETCH_ASSOC);
$id_caixa = (@count($res1) > 0) ? $res1[0]['id'] : 0;

// INSERT ou UPDATE
if($id == ""){
	$query = $pdo->prepare("INSERT INTO $tabela SET 
		descricao = :descricao, 
		fornecedor = :fornecedor, 
		funcionario = :funcionario, 
		valor = :valor, 
		vencimento = '$vencimento' $pgto, 
		data_lanc = curDate(), 
		forma_pgto = '$forma_pgto', 
		frequencia = '$frequencia', 
		obs = :obs, 
		arquivo = '$foto', 
		subtotal = :valor, 
		usuario_lanc = '$id_usuario' $usu_pgto, 
		pago = '$pago', 
		referencia = 'Conta', 
		caixa = '$id_caixa', 
		hora = curTime()");
} else {
	$query = $pdo->prepare("UPDATE $tabela SET 
		descricao = :descricao, 
		fornecedor = :fornecedor, 
		funcionario = :funcionario, 
		valor = :valor, 
		vencimento = '$vencimento' $pgto, 
		forma_pgto = '$forma_pgto', 
		frequencia = '$frequencia', 
		obs = :obs, 
		arquivo = '$foto', 
		subtotal = :valor 
		WHERE id = '$id'");
}

$query->bindValue(":descricao", $descricao);
$query->bindValue(":fornecedor", $fornecedor);
$query->bindValue(":funcionario", $funcionario);
$query->bindValue(":valor", $valor);
$query->bindValue(":obs", $obs);
$query->execute();


if($vencimento == $data_pgto) {


	$valor_para_db = preg_replace('/\.(?=.*\.)/', '', $valor);

	// Inserir na tabela linha_bancos
	$pdo->query("INSERT INTO linha_bancos SET 
	id_banco = '$banco',
	data = '$data_pgto',
	remetente = '$id_usuario',
	n_fiscal = '',
	classificacao = 1,
	mes_ref = MONTH('$data_pgto'),
	credito = '0',
	debito = '$valor_para_db',
	saldo = (SELECT saldo FROM bancos WHERE id = '$banco') - '$valor_para_db',
	status = 'Confirmado'
	");
    $pdo->query("UPDATE bancos SET 
        saldo = saldo + $valor_para_db 
        WHERE id = '$banco'
    ");
}


echo 'Salvo com Sucesso';
?>
