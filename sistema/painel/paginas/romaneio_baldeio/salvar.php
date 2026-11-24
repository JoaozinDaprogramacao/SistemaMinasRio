<?php
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
require_once(__DIR__ . "/../../funcoes/financeiro.php");
seguranca_exigir_acesso(['bancos']);

@session_start();
$id_usuario = @$_SESSION['id'];

$id = $_POST['id'] ?? '';
$id_romaneio = $_POST['id_romaneio'] ?? '';
$id_produtor = $_POST['id_produtor'] ?? '';
$total = $_POST['total'] ?? '0.0';
$local = $_POST['local'] ?? null;

// Debug dos valores recebidos
error_log("ID Romaneio: " . $id_romaneio);
error_log("ID Produtor: " . $id_produtor);
error_log("Total: " . $total);

// Validações básicas
if (empty($id_romaneio)) {
    echo 'Erro: ID do romaneio é obrigatório';
    exit();
}

if (empty($id_produtor)) {
    echo 'Erro: Produtor é obrigatório';
    exit();
}

try {
    $pdo->beginTransaction();

    // Verifica se o produtor existe
    $query = $pdo->prepare("SELECT id FROM fornecedores WHERE id = ?");
    $query->execute([$id_produtor]);
    if ($query->rowCount() == 0) {
        throw new Exception("Produtor não encontrado na tabela de fornecedores");
    }

    // Verifica se o romaneio existe
    $query = $pdo->prepare("SELECT id FROM romaneio_venda WHERE id = ?");
    $query->execute([$id_romaneio]);
    if ($query->rowCount() == 0) {
        throw new Exception("Romaneio não encontrado");
    }

    // Formatação do valor total
    $total = str_replace('.', '', $total);
    $total = str_replace(',', '.', $total);
    
    if ($total === '' || !is_numeric($total)) {
        $total = '0.0';
    }

    // Preparação da query
    if ($id == "") {
        $query = $pdo->prepare("INSERT INTO baldeio (id_romaneio, id_produtor, total, local) VALUES (?, ?, ?, ?)");
        $params = [$id_romaneio, $id_produtor, $total, $local];
    } else {
        $query = $pdo->prepare("UPDATE baldeio SET id_romaneio = ?, id_produtor = ?, total = ?, local = ? WHERE id = ?");
        $params = [$id_romaneio, $id_produtor, $total, $local, $id];
    }

    $query->execute($params);
    
    $id_baldeio = ($id == "") ? $pdo->lastInsertId() : $id;
    
    // Conta a pagar do baldeio: uma só por baldeio (editar atualiza em vez de duplicar)
    fin_sincronizar_titulo($pdo, 'pagar', 'id_baldeio = ?', [$id_baldeio], [
        'descricao' => 'Baldeio',
        'fornecedor' => $id_produtor,
        'funcionario' => 0,
        'valor' => (float) $total,
        'vencimento' => date('Y-m-d'),
        'data_lanc' => date('Y-m-d'),
        'forma_pgto' => 0,
        'arquivo' => 'sem-foto.png',
        'referencia' => 'Baldeio',
        'usuario_lanc' => $id_usuario,
        'id_baldeio' => $id_baldeio,
    ]);
    
    $pdo->commit();
    echo 'Salvo com Sucesso';
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Erro ao salvar baldeio: " . $e->getMessage());
    echo 'Erro: ' . $e->getMessage();
}
