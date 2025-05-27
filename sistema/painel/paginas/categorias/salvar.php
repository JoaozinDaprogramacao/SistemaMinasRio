<?php
$tabela = 'categorias';
require_once("../../../conexao.php");

$nome = $_POST['nome'];
$id = $_POST['id'];
// A variável $vendas não é mais necessária aqui, pois seu valor será definido diretamente nas queries
// ou não será alterado.

//validacao
$query = $pdo->query("SELECT * from $tabela where nome = '$nome'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$id_reg = @$res[0]['id'];
if(@count($res) > 0 and $id != $id_reg){
    echo 'Nome já Cadastrado!';
    exit();
}


//validar troca da foto
$query = $pdo->query("SELECT * FROM $tabela where id = '$id'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
    $foto = $res[0]['foto'];
}else{
    $foto = 'sem-foto.png';
}


//SCRIPT PARA SUBIR FOTO NO SERVIDOR
$nome_img = date('d-m-Y H:i:s') .'-'.@$_FILES['foto']['name'];
$nome_img = preg_replace('/[ :]+/' , '-' , $nome_img);

$caminho = '../../images/categorias/' .$nome_img;

$imagem_temp = @$_FILES['foto']['tmp_name'];

if(@$_FILES['foto']['name'] != ""){
    $ext = pathinfo($nome_img, PATHINFO_EXTENSION);
    if($ext == 'png' or $ext == 'jpg' or $ext == 'jpeg' or $ext == 'gif' or $ext == 'PNG' or $ext == 'JPG' or $ext == 'JPEG' or $ext == 'GIF' or $ext == 'webp' or $ext == 'WEBP'){

            //EXCLUO A FOTO ANTERIOR
            if($foto != "sem-foto.png"){
                @unlink('../../images/categorias/'.$foto);
            }

            $foto = $nome_img;

        move_uploaded_file($imagem_temp, $caminho);
    }else{
        echo 'Extensão de Imagem não permitida!';
        exit();
    }
}


if($id == ""){
    // No INSERT, 'vendas' é definido diretamente como 0
    $query = $pdo->prepare("INSERT INTO $tabela SET nome = :nome, foto = :foto, vendas = 0, ativo = 'Sim'");

}else{
    // No UPDATE, o campo 'vendas' é removido da query para não ser alterado
    $query = $pdo->prepare("UPDATE $tabela SET nome = :nome, foto = :foto where id = :id");
    $query->bindValue(":id", $id);
}

$query->bindValue(":nome", $nome);
$query->bindValue(":foto", $foto);
// O bindValue para :vendas não é mais necessário aqui, pois:
// - No INSERT, o valor 0 está diretamente na query.
// - No UPDATE, o campo 'vendas' não é modificado.

$query->execute();

echo 'Salvo com Sucesso';

?>