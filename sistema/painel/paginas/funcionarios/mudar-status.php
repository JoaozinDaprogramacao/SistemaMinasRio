<?php 
$tabela = 'funcionarios';
require_once("../../../conexao.php");

if (empty($_POST['id']) || empty($_POST['status'])) {
    echo 'Dados insuficientes para realizar a alteração.';
    exit();
}

$id = $_POST['id'];
$novo_status = $_POST['status'];

// --- ETAPA ESSENCIAL: BUSCAR O STATUS ATUAL ANTES DE MODIFICAR ---
$query_antigo = $pdo->prepare("SELECT status FROM $tabela WHERE id = :id");
$query_antigo->bindValue(":id", $id);
$query_antigo->execute();
$dados_antigos = $query_antigo->fetch(PDO::FETCH_ASSOC);
$status_antigo = $dados_antigos ? $dados_antigos['status'] : '';


// --- LÓGICA DE HISTÓRICO ---
// Registra o evento ANTES de alterar o status no banco

// CASO 1: Se o novo status for 'Demitido'
if ($novo_status == 'Demitido' && $status_antigo != 'Demitido') {
    $query_hist = $pdo->prepare("INSERT INTO historico_funcionarios SET id_funcionario = :id_func, tipo_evento = 'DEMISSAO', descricao = 'Funcionário(a) desligado(a) da empresa.', data_evento = NOW()");
    $query_hist->bindValue(":id_func", $id);
    $query_hist->execute();
}

// CASO 2: Se o novo status for 'Ativo' E o status antigo era 'Demitido'
if ($novo_status == 'Ativo' && $status_antigo == 'Demitido') {
    $query_hist = $pdo->prepare("INSERT INTO historico_funcionarios SET id_funcionario = :id_func, tipo_evento = 'REATIVACAO', descricao = 'Funcionário(a) reativado(a) no sistema.', data_evento = NOW()");
    $query_hist->bindValue(":id_func", $id);
    $query_hist->execute();
}


// --- ATUALIZA O STATUS E DATA DE DEMISSÃO DO FUNCIONÁRIO ---
$sql_update = "UPDATE $tabela SET status = :novo_status, data_demissao = :data_demissao WHERE id = :id";
$query = $pdo->prepare($sql_update);

// Define a data de demissão baseada no novo status (se demitido, põe data; se reativado, limpa a data)
$data_demissao = ($novo_status == 'Demitido') ? date('Y-m-d') : null;

$query->bindValue(":novo_status", $novo_status);
$query->bindValue(":data_demissao", $data_demissao);
$query->bindValue(":id", $id);
$query->execute();

echo 'Alterado com Sucesso';
?>