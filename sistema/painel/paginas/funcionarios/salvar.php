<?php 
$tabela = 'funcionarios'; 
require_once("../../../conexao.php");

// captura dos campos do form
$nome         = $_POST['nome'];
$email        = $_POST['email'];
$telefone     = $_POST['telefone'];
$endereco     = $_POST['endereco'];
$chave_pix    = $_POST['chave_pix'];
$comissao     = $_POST['comissao'];
$cargo        = $_POST['nivel'];    // <<< novo campo
$id           = $_POST['id'];

// normaliza vírgula em ponto no decimal
$comissao = str_replace(',', '.', $comissao);
if ($comissao === "") {
    $comissao = 0;
}

// validação de e-mail duplicado
$query = $pdo->query("SELECT * FROM $tabela WHERE email = '$email'");
$res   = $query->fetchAll(PDO::FETCH_ASSOC);
$id_reg = @$res[0]['id'];
if (count($res) > 0 && $id != $id_reg) {
    echo 'Email já cadastrado!' . $id_reg . $id;
    exit();
}

// validação de telefone duplicado
$query = $pdo->query("SELECT * FROM $tabela WHERE telefone = '$telefone'");
$res   = $query->fetchAll(PDO::FETCH_ASSOC);
$id_reg = @$res[0]['id'];
if (count($res) > 0 && $id != $id_reg) {
    echo 'Telefone já cadastrado!';
    exit();
}

if ($id == "") {
    // INSERT inclui o cargo
    $sql = "INSERT INTO $tabela SET 
                nome       = :nome, 
                email      = :email, 
                telefone   = :telefone, 
                endereco   = :endereco, 
                chave_pix  = :chave_pix, 
                comissao   = :comissao,
                cargo      = :cargo,
                data_cad   = curDate(), 
                ativo      = 'Sim',
                foto       = 'sem-foto.jpg'";
    $query = $pdo->prepare($sql);
} else {
    // UPDATE também atualiza o cargo
    $sql = "UPDATE $tabela SET 
                nome       = :nome, 
                email      = :email, 
                telefone   = :telefone, 
                endereco   = :endereco, 
                chave_pix  = :chave_pix, 
                comissao   = :comissao,
                cargo      = :cargo
            WHERE id = :id";
    $query = $pdo->prepare($sql);
    $query->bindValue(":id", $id);
}

// binding de todos os parâmetros
$query->bindValue(":nome",      $nome);
$query->bindValue(":email",     $email);
$query->bindValue(":telefone",  $telefone);
$query->bindValue(":endereco",  $endereco);
$query->bindValue(":chave_pix", $chave_pix);
$query->bindValue(":comissao",  $comissao);
$query->bindValue(":cargo",     $cargo);   // <<< bind do cargo

$query->execute();

echo 'Salvo com Sucesso';
