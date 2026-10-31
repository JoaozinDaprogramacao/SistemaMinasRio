<?php
$tabela = 'vendas';
require_once("../../../conexao.php");

// Define o cabeçalho da resposta como JSON para o AJAX interpretar corretamente
header('Content-Type: application/json');

// Recebe o ID da venda via POST
$id_venda = @$_POST['id'];

// Validação de segurança: verifica se o ID foi enviado
if (empty($id_venda)) {
    echo json_encode(['erro' => 'ID da venda não foi fornecido.']);
    exit();
}

// 1. BUSCAR DADOS PRINCIPAIS DA VENDA E NOMES RELACIONADOS
$query_venda = $pdo->prepare("SELECT v.*, c.nome as nome_cliente, u.nome as nome_vendedor 
                             FROM vendas v
                             LEFT JOIN clientes c ON v.cliente_id = c.id
                             LEFT JOIN usuarios u ON v.vendedor_id = u.id
                             WHERE v.id = :id_venda");
$query_venda->execute([':id_venda' => $id_venda]);
$res_venda = $query_venda->fetchAll(PDO::FETCH_ASSOC);

// Validação de segurança: verifica se a venda existe
if (count($res_venda) == 0) {
    echo json_encode(['erro' => 'Venda não encontrada no banco de dados.']);
    exit();
}
$venda = $res_venda[0];


// 2. FORMATAR OS DADOS DA VENDA PARA EXIBIÇÃO
$status = $venda['status_pagamento'];
$classe_status = 'text-warning'; // Padrão
if($status == 'Pago'){ $classe_status = 'text-success'; }
elseif ($status == 'Parcialmente Pago'){ $classe_status = 'text-primary'; }
elseif ($status == 'Cancelada'){ $classe_status = 'text-muted'; }

// Formata o desconto para mostrar o tipo (R$ ou %)
$desconto_val = $venda['desconto'];
if($venda['tipo_desconto'] == '%'){
    $desconto_formatado = number_format($desconto_val, 2, ',', '.') . '%';
} else {
    $desconto_formatado = 'R$ ' . number_format($desconto_val, 2, ',', '.');
}


// 3. BUSCAR OS ITENS VENDIDOS
$query_itens = $pdo->prepare("SELECT i.*, m.nome as nome_material FROM itens_venda i 
                              JOIN materiais m ON i.material = m.id 
                              WHERE i.id_venda_real = :id_venda ORDER BY m.nome ASC");
$query_itens->execute([':id_venda' => $id_venda]);
$res_itens = $query_itens->fetchAll(PDO::FETCH_ASSOC);

// 4. MONTAR A TABELA HTML DOS ITENS
$tabela_itens = '<table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>Material</th>
                            <th class="text-center">Quantidade</th>
                            <th class="text-end">Valor Unit.</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>';
foreach ($res_itens as $item) {
    $tabela_itens .= '<tr>';
    $tabela_itens .= '<td>' . htmlspecialchars($item['nome_material']) . '</td>';
    $tabela_itens .= '<td class="text-center">' . $item['quantidade'] . '</td>';
    $tabela_itens .= '<td class="text-end">R$ ' . number_format($item['valor'], 2, ',', '.') . '</td>';
    $tabela_itens .= '<td class="text-end">R$ ' . number_format($item['total'], 2, ',', '.') . '</td>';
    $tabela_itens .= '</tr>';
}
$tabela_itens .= '</tbody></table>';


// 5. LANÇAMENTOS EM CONTAS A RECEBER DA VENDA (entrada, restante, parcelas, resíduos)
$query_rec = $pdo->prepare("SELECT r.*, f.nome as nome_forma FROM receber r
                            LEFT JOIN formas_pgto f ON f.id = r.forma_pgto
                            WHERE r.id_venda_real = :id_venda ORDER BY r.vencimento, r.id");
$query_rec->execute([':id_venda' => $id_venda]);
$res_rec = $query_rec->fetchAll(PDO::FETCH_ASSOC);

$tabela_itens .= '<h6 class="px-2 mt-3">Contas a Receber</h6>
                  <table class="table table-sm mb-0">
                    <thead><tr><th>Descrição</th><th>Forma</th><th>Vencimento</th><th class="text-end">Valor</th><th class="text-center">Pago</th></tr></thead>
                    <tbody>';
foreach ($res_rec as $rec) {
    $situacao = ($rec['cancelada'] == 'Sim') ? 'Cancelada' : $rec['pago'];
    $tabela_itens .= '<tr>';
    $tabela_itens .= '<td>' . htmlspecialchars($rec['descricao']) . '</td>';
    $tabela_itens .= '<td>' . htmlspecialchars($rec['nome_forma'] ?? '-') . '</td>';
    $tabela_itens .= '<td>' . date('d/m/Y', strtotime($rec['vencimento'])) . '</td>';
    $tabela_itens .= '<td class="text-end">R$ ' . number_format($rec['valor'], 2, ',', '.') . '</td>';
    $tabela_itens .= '<td class="text-center">' . $situacao . '</td>';
    $tabela_itens .= '</tr>';
}
if (count($res_rec) == 0) {
    $tabela_itens .= '<tr><td colspan="5" class="text-center text-muted">Nenhum lançamento.</td></tr>';
}
$tabela_itens .= '</tbody></table>';


// 6. MONTAR O ARRAY DE RESPOSTA JSON
$output = [
    'id' => $venda['id'],
    'cliente' => htmlspecialchars($venda['nome_cliente'] ?? 'Cliente excluído'),
    'vendedor' => htmlspecialchars($venda['nome_vendedor'] ?? 'Usuário excluído'),
    'data' => date('d/m/Y H:i', strtotime($venda['data_venda'])),
    'status' => $status,
    'classe_status' => $classe_status,
    'subtotal' => 'R$ ' . number_format($venda['subtotal'], 2, ',', '.'),
    'desconto' => $desconto_formatado,
    'frete' => 'R$ ' . number_format($venda['frete'], 2, ',', '.'),
    'total' => 'R$ ' . number_format($venda['valor_total'], 2, ',', '.'),
    'pago' => 'R$ ' . number_format($venda['valor_pago'], 2, ',', '.'),
    'restante' => 'R$ ' . number_format($venda['valor_restante'], 2, ',', '.'),
    'tabela_itens' => $tabela_itens
];

// Envia a resposta final em formato JSON
echo json_encode($output);
?>