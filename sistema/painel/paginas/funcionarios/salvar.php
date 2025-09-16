<?php
$tabela = 'funcionarios';
require_once("../../../conexao.php");

// === 1. CAPTURA DOS CAMPOS DO NOVO FORMULÁRIO ===
$nome              = $_POST['nome'];
$telefone          = $_POST['telefone'];
$chave_pix         = $_POST['chave_pix'];
$endereco          = $_POST['endereco'];
$cargo             = $_POST['cargo'];           // Campo 'cargo' do novo modal
$data_admissao     = $_POST['data_admissao'];
$status            = $_POST['status'];          // 'Ativo' ou 'Demitido'
$data_demissao     = $_POST['data_demissao'];   // Pode vir vazio
$descricao_salario = $_POST['descricao_salario'];
$salario_folha     = $_POST['salario_folha'];
$obs               = $_POST['obs'];
$id                = $_POST['id'];

// === 2. TRATAMENTO DOS DADOS ===
// Se o status não for 'Demitido' ou a data de demissão estiver vazia, define como NULL
if ($status !== 'Demitido' || $data_demissao === '') {
    $data_demissao = null;
}

// Garante que os valores numéricos sejam 0 se estiverem vazios
if ($descricao_salario === "") {
    $descricao_salario = 0;
}
if ($salario_folha === "") {
    $salario_folha = 0;
}


// === 3. VALIDAÇÃO DE DUPLICIDADE ===
// Validação de telefone duplicado (mantida)
$query = $pdo->prepare("SELECT * FROM $tabela WHERE telefone = :telefone");
$query->bindValue(":telefone", $telefone);
$query->execute();
$res    = $query->fetchAll(PDO::FETCH_ASSOC);
$id_reg = @$res[0]['id'];
if (count($res) > 0 && $id != $id_reg) {
    echo 'Telefone já cadastrado!';
    exit();
}


// === 4. OPERAÇÃO NO BANCO DE DADOS (INSERT OU UPDATE) ===
if ($id == "") {
    // INSERT para um novo funcionário
    $sql = "INSERT INTO $tabela SET 
                nome = :nome, 
                telefone = :telefone, 
                chave_pix = :chave_pix, 
                endereco = :endereco, 
                cargo = :cargo, 
                data_admissao = :data_admissao, 
                status = :status, 
                data_demissao = :data_demissao, 
                descricao_salario = :descricao_salario, 
                salario_folha = :salario_folha, 
                obs = :obs, 
                data_cad = curDate(), 
                foto = 'sem-foto.jpg'";
    $query = $pdo->prepare($sql);

} else {
    // UPDATE para um funcionário existente
    $sql = "UPDATE $tabela SET 
                nome = :nome, 
                telefone = :telefone, 
                chave_pix = :chave_pix, 
                endereco = :endereco, 
                cargo = :cargo, 
                data_admissao = :data_admissao, 
                status = :status, 
                data_demissao = :data_demissao, 
                descricao_salario = :descricao_salario, 
                salario_folha = :salario_folha, 
                obs = :obs 
            WHERE id = :id";
    $query = $pdo->prepare($sql);
    $query->bindValue(":id", $id);
}

// === 5. BINDING DOS PARÂMETROS ===
$query->bindValue(":nome", $nome);
$query->bindValue(":telefone", $telefone);
$query->bindValue(":chave_pix", $chave_pix);
$query->bindValue(":endereco", $endereco);
$query->bindValue(":cargo", $cargo);
$query->bindValue(":data_admissao", $data_admissao);
$query->bindValue(":status", $status);
$query->bindValue(":data_demissao", $data_demissao);
$query->bindValue(":descricao_salario", $descricao_salario);
$query->bindValue(":salario_folha", $salario_folha);
$query->bindValue(":obs", $obs);

$query->execute();

echo 'Salvo com Sucesso';
?>