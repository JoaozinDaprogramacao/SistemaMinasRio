<?php 
$tabela = 'usuarios';
require_once("../conexao.php");
require_once(__DIR__ . "/seguranca.php");
seguranca_exigir_login();

$nome = $_POST['nome'];
$email = $_POST['email'];
$telefone = $_POST['telefone'];
$conf_senha = $_POST['conf_senha'];
$endereco = $_POST['endereco'];
$senha = $_POST['senha'];
$senha_crip = password_hash($senha, PASSWORD_DEFAULT);
// Sempre o próprio usuário logado: o id que vem do formulário não é confiável
$id = $_SESSION['id'];

if($conf_senha != $senha){
	echo 'As senhas não se coincidem';
	exit();
}

//validacao email
$query = $pdo->prepare("SELECT id from $tabela where email = :email and id <> :id");
$query->execute([':email' => $email, ':id' => $id]);
if($query->fetchColumn()){
	echo 'Email já Cadastrado!';
	exit();
}

//validacao telefone
$query = $pdo->prepare("SELECT id from $tabela where telefone = :telefone and id <> :id");
$query->execute([':telefone' => $telefone, ':id' => $id]);
if($query->fetchColumn()){
	echo 'Telefone já Cadastrado!';
	exit();
}




//validar troca da foto
$query = $pdo->prepare("SELECT * FROM $tabela where id = :id");
$query->execute([':id' => $id]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
	$foto = $res[0]['foto'];
}else{
	$foto = 'sem-foto.jpg';
}



//SCRIPT PARA SUBIR FOTO NO SERVIDOR
$nome_img = date('d-m-Y H:i:s') .'-'.@$_FILES['foto']['name'];
$nome_img = preg_replace('/[ :]+/' , '-' , $nome_img);

$caminho = 'images/perfil/' .$nome_img;

$imagem_temp = @$_FILES['foto']['tmp_name']; 

if(@$_FILES['foto']['name'] != ""){
	$ext = pathinfo($nome_img, PATHINFO_EXTENSION);   
	if($ext == 'png' or $ext == 'jpg' or $ext == 'jpeg' or $ext == 'gif' or $ext == 'webp' or $ext == 'PNG' or $ext == 'JPG' or $ext == 'JPEG' or $ext == 'GIF' or $ext == 'WEBP'){ 
	
			//EXCLUO A FOTO ANTERIOR
			if($foto != "sem-foto.jpg"){
				@unlink('images/perfil/'.$foto);
			}

			$foto = $nome_img;
		
			//pegar o tamanho da imagem
			list($largura, $altura) = getimagesize($imagem_temp);
		 	if($largura > 1400){
		 		$image = imagecreatefromjpeg($imagem_temp);
		        // Reduza a qualidade para 20% ajuste conforme necessário
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



$query = $pdo->prepare("UPDATE $tabela SET nome = :nome, email = :email, telefone = :telefone, senha_crip = :senha_crip, endereco = :endereco, foto = :foto where id = :id");

$query->bindValue(":senha_crip", $senha_crip);
$query->bindValue(":foto", $foto);
$query->bindValue(":id", $id);
$query->bindValue(":nome", "$nome");
$query->bindValue(":email", "$email");
$query->bindValue(":telefone", "$telefone");
$query->bindValue(":endereco", "$endereco");

$query->execute();

echo 'Editado com Sucesso';
 ?>