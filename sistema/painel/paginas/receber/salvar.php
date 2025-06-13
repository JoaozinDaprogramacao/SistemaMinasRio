<?php 
$tabela = 'receber';
require_once("../../../conexao.php");

@session_start();
$id_usuario = @$_SESSION['id'];

$descricao = $_POST['descricao'];
$valor = $_POST['valor'];
$cliente = $_POST['cliente'];
$vencimento = $_POST['vencimento'];
$data_pgto = $_POST['data_pgto'];
$forma_pgto = $_POST['forma_pgto'];
$frequencia = $_POST['frequencia'];
$obs = $_POST['obs'];
$id = $_POST['id'];
$banco = $_POST['banco'] ?? 0;

$valor = str_replace(',', '.', $valor);

$valorF = @number_format($valor, 2, ',', '.');

if($cliente == ""){
	$cliente = 0;
}

if($forma_pgto == ""){
	$forma_pgto = 0;
}

if($frequencia == ""){
	$frequencia = 0;
}

if($data_pgto == ""){
	$pgto = '';
	$usu_pgto = ' ,usuario_pgto = 0';
	$pago = 'Não';
}else{
	$pgto = " ,data_pgto = '$data_pgto'";
	$usu_pgto = " ,usuario_pgto = '$id_usuario'";
	$pago = 'Sim';
}

//validacao
if($descricao == "" and $cliente == "0"){
	echo 'Selecione um Cliente ou uma Descrição!';
	exit();
}

$query2 = $pdo->query("SELECT * FROM clientes where id = '$cliente'");
$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
if(@count($res2) > 0){
	$nome_cliente = $res2[0]['nome'];
	$telefone_cliente = $res2[0]['contato'];
}else{
	$nome_cliente = 'Sem Registro';
	$telefone_cliente = "";
}

if($descricao == ""){
	$descricao = $nome_cliente;
}

//validar troca da foto
$query = $pdo->query("SELECT * FROM $tabela where id = '$id'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
	$foto = $res[0]['arquivo'];
}else{
	$foto = 'sem-foto.png';
}



//SCRIPT PARA SUBIR FOTO NO SERVIDOR
$nome_img = date('d-m-Y H:i:s') .'-'.@$_FILES['foto']['name'];
$nome_img = preg_replace('/[ :]+/' , '-' , $nome_img);

$caminho = '../../images/contas/' .$nome_img;

$imagem_temp = @$_FILES['foto']['tmp_name']; 

if(@$_FILES['foto']['name'] != ""){
	$ext = pathinfo($nome_img, PATHINFO_EXTENSION);   
	if($ext == 'png' or $ext == 'jpg' or $ext == 'jpeg' or $ext == 'gif' or $ext == 'pdf' or $ext == 'rar' or $ext == 'zip' or $ext == 'doc' or $ext == 'docx' or $ext == 'webp' or $ext == 'PNG' or $ext == 'JPG' or $ext == 'JPEG' or $ext == 'GIF' or $ext == 'PDF' or $ext == 'RAR' or $ext == 'ZIP' or $ext == 'DOC' or $ext == 'DOCX' or $ext == 'WEBP' or $ext == 'xlsx' or $ext == 'xlsm' or $ext == 'xls' or $ext == 'xml'){ 
	
			//EXCLUO A FOTO ANTERIOR
			if($foto != "sem-foto.png"){
				@unlink('../../images/contas/'.$foto);
			}

			$foto = $nome_img;
		
		//pegar o tamanho da imagem
			list($largura, $altura) = getimagesize($imagem_temp);
		 	if($largura > 1400){
		 		if($ext == 'png' or $ext == 'jpg' or $ext == 'jpeg' or $ext == 'gif' or $ext == 'webp' or $ext == 'PNG' or $ext == 'JPG' or $ext == 'JPEG' or $ext == 'GIF' or $ext == 'PDF' or $ext == 'RAR' or $ext == 'WEBP'){
		 			$image = imagecreatefromjpeg($imagem_temp);
			        // Reduza a qualidade para 20% ajuste conforme necessário
			        imagejpeg($image, $caminho, 20);
			        imagedestroy($image);
		 		}else{
		 			move_uploaded_file($imagem_temp, $caminho);
		 		}
			 		
		 	}else{
		 		move_uploaded_file($imagem_temp, $caminho);
		 	}
		 		


	}else{
		echo 'Extensão de Imagem não permitida!';
		exit();
	}
}


//verificar caixa aberto
$query1 = $pdo->query("SELECT * from caixas where operador = '$id_usuario' and data_fechamento is null order by id desc limit 1");
$res1 = $query1->fetchAll(PDO::FETCH_ASSOC);
if(@count($res1) > 0){
	$id_caixa = @$res1[0]['id'];
}else{
	$id_caixa = 0;
}
//  , caixa = '$id_caixa'


if($id == ""){
$query = $pdo->prepare("INSERT INTO $tabela SET descricao = :descricao, cliente = :cliente, valor = :valor, vencimento = '$vencimento' $pgto, data_lanc = curDate(), forma_pgto = '$forma_pgto', frequencia = '$frequencia', obs = :obs, arquivo = '$foto', subtotal = :valor, usuario_lanc = '$id_usuario' $usu_pgto, pago = '$pago', referencia = 'Conta', caixa = '$id_caixa', hora = curTime() ");
	
}else{
$query = $pdo->prepare("UPDATE $tabela SET descricao = :descricao, cliente = :cliente, valor = :valor, vencimento = '$vencimento' $pgto, forma_pgto = '$forma_pgto', frequencia = '$frequencia', obs = :obs, arquivo = '$foto', subtotal = :valor where id = '$id'");
}


$query->bindValue(":descricao", "$descricao");
$query->bindValue(":cliente", "$cliente");
$query->bindValue(":valor", "$valor");
$query->bindValue(":obs", "$obs");
$query->execute();
$ultimo_id = $pdo->lastInsertId();

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
        credito = '$valor_para_db',
        debito = '0',
        saldo = (SELECT saldo FROM bancos WHERE id = '$banco') + '$valor_para_db',
        status = 'Confirmado'
    ");

    $pdo->query("UPDATE bancos SET 
        saldo = saldo + $valor_para_db 
        WHERE id = '$banco'
    ");
}



echo 'Salvo com Sucesso';
 ?>