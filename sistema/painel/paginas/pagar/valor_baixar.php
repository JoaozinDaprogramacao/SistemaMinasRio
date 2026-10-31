<?php
$tabela = 'pagar';
require_once("../../../conexao.php");

$ids = $_POST['ids'] ?? '';

// Remove ids repetidos: a lista pode conter o mesmo título mais de uma vez quando
// a listagem é recarregada com a seleção antiga ainda no campo oculto.
$lista_ids = array_unique(array_filter(explode('-', $ids), function ($v) {
    return $v !== '' && (int) $v > 0;
}));

$total_contas = 0;
$qtd_contas = 0;

// Mesma normalização usada na listagem (registros antigos ficaram com pago nulo/vazio)
$pago_norm = "COALESCE(NULLIF(pago, ''), 'Não')";

foreach ($lista_ids as $id) {
    $id = (int) $id;
    $conta = $pdo->query("SELECT * FROM $tabela WHERE id = '$id' AND $pago_norm != 'Sim'")->fetch(PDO::FETCH_ASSOC);
    if (!$conta) continue;

    $valor = ($conta['pago'] === 'Parcial') ? $conta['valor_restante'] : ($conta['subtotal'] ?: $conta['valor']);
    if ($valor <= 0) continue;

    $total_contas += $valor;
    $qtd_contas++;
}

// Devolve quantidade e total juntos para o modal exibir os dois a partir da mesma contagem
echo json_encode([
    'qtd'   => $qtd_contas,
    'total' => number_format($total_contas, 2, ',', '.')
]);
?>
