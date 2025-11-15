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
                             JOIN clientes c ON v.cliente_id = c.id 
                             JOIN usuarios u ON v.vendedor_id = u.id 
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


// 5. BUSCAR O ID DO COMPROVANTE NA TABELA 'receber' PARA MANTER COMPATIBILIDADE
$query_rec = $pdo->prepare("SELECT id_venda FROM itens_venda WHERE id_venda_real = :id_venda LIMIT 1");
$query_rec->execute([':id_venda' => $id_venda]);
$res_rec = $query_rec->fetch(PDO::FETCH_ASSOC);
// O id do comprovante é o id da tabela 'receber', que está na coluna 'id_venda' da tabela 'itens_venda'
$id_comprovante = $res_rec['id_venda'] ?? $id_venda; 


// 6. MONTAR O ARRAY DE RESPOSTA JSON
$output = [
    'id' => $venda['id'],
    'cliente' => htmlspecialchars($venda['nome_cliente']),
    'vendedor' => htmlspecialchars($venda['nome_vendedor']),
    'data' => date('d/m/Y H:i', strtotime($venda['data_venda'])),
    'status' => $status,
    'classe_status' => $classe_status,
    'subtotal' => 'R$ ' . number_format($venda['subtotal'], 2, ',', '.'),
    'desconto' => $desconto_formatado,
    'frete' => 'R$ ' . number_format($venda['frete'], 2, ',', '.'),
    'total' => 'R$ ' . number_format($venda['valor_total'], 2, ',', '.'),
    'pago' => 'R$ ' . number_format($venda['valor_pago'], 2, ',', '.'),
    'restante' => 'R$ ' . number_format($venda['valor_restante'], 2, ',', '.'),
    'tabela_itens' => $tabela_itens,
    'id_comprovante' => $id_comprovante
];

// Envia a resposta final em formato JSON
echo json_encode($output);
?>