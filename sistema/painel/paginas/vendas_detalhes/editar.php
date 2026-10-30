<?php
require_once("../../../conexao.php");
@session_start();

$id_venda = intval($_POST['id'] ?? 0);

if ($id_venda <= 0) {
    echo 'ID da venda não foi recebido.';
    exit();
}

try {
    $query = $pdo->prepare("SELECT * FROM vendas WHERE id = :id_venda");
    $query->execute([':id_venda' => $id_venda]);
    $venda = $query->fetch(PDO::FETCH_ASSOC);

    if (!$venda) {
        echo 'Venda não encontrada!';
        exit();
    }
    if ($venda['status_pagamento'] == 'Cancelada') {
        echo 'Venda cancelada não pode ser editada!';
        exit();
    }

    // A aba Vendas lê estes dados e copia os itens da venda para o carrinho (ETAPA 2)
    $_SESSION['modo_edicao_venda'] = true;
    $_SESSION['dados_edicao_venda'] = [
        'id'                 => $venda['id'],
        'cliente_id'         => $venda['cliente_id'],
        'desconto'           => $venda['desconto'] ?? '0',
        'tipo_desconto'      => $venda['tipo_desconto'],
        'frete'              => $venda['frete'] ?? '0',
        'valor_pago'         => $venda['valor_pago'],
        'forma_pagamento_id' => $venda['forma_pagamento_id'],
        'data_venda'         => $venda['data_venda']
    ];

    echo 'sucesso';
} catch (Exception $e) {
    echo 'Ocorreu um erro: ' . $e->getMessage();
}
