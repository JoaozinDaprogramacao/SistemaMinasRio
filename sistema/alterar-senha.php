<?php
require_once("conexao.php");

$senha = (string) @$_POST['senha'];
$re_senha = (string) @$_POST['re_senha'];
$token = (string) @$_POST['token'];
$email = (string) @$_POST['email'];

if($senha == ''){
     echo 'Informe a nova senha!';
     exit();
}

if($senha != $re_senha){
     echo 'As senhas são diferentes!!';
     exit();
}

// Só troca a senha com o token do link de recuperação enviado para o email
if($token == '' or $email == ''){
     echo 'Link de recuperação inválido ou expirado!';
     exit();
}

        $senha = password_hash($senha, PASSWORD_DEFAULT);

          // O token é apagado: o link só serve uma vez
          $query = $pdo->prepare("UPDATE usuarios SET senha_crip = :senha, token = '' WHERE email = :email AND token = :token AND token <> ''");

               $query->bindValue(":senha", $senha);
               $query->bindValue(":token", $token);
               $query->bindValue(":email", $email);
               $query->execute();

        if($query->rowCount() == 0){
             echo 'Link de recuperação inválido ou expirado!';
             exit();
        }

        echo 'Senha alterada com Sucesso';


?>
