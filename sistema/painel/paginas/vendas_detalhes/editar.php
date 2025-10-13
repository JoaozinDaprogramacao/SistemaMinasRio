<?php
require_once("../../../conexao.php");
@session_start();

$id_venda = @$_POST['id'];

if (empty($id_venda)) {
    echo 'ID da venda não foi recebido.';
    exit();
}

try {
    // 1. Busca os dados principais da venda na tabela 'vendas'
    $query = $pdo->prepare("SELECT * FROM vendas WHERE id = :id_venda");
    $query->execute([':id_venda' => $id_venda]);
    $venda = $query->fetch(PDO::FETCH_ASSOC);

    if (!$venda) {
        echo 'Venda não encontrada!';
        exit();
    }

    // 2. Monta o array para a sessão, tratando valores que podem ser NULL
    $dados_para_sessao = [
        'id'                 => $venda['id'],
        'cliente_id'         => $venda['cliente_id'],
        
        // ** CORREÇÃO FINAL AQUI **
        // Se 'valor_desconto' não existir no array (for NULL no banco), usa '0' como padrão.
        'desconto'           => $venda['valor_desconto'] ?? '0', 
        
        'tipo_desconto'      => $venda['tipo_desconto'],
        'frete'              => $venda['frete'] ?? '0', // Boa prática aplicar aqui também
        'valor_pago'         => $venda['valor_pago'],
        'forma_pagamento_id' => $venda['forma_pagamento_id'],
        'data_venda'         => $venda['data_venda']
    ];

    // 3. Salva os dados na sessão PHP
    $_SESSION['modo_edicao_venda'] = true;
    $_SESSION['dados_edicao_venda'] = $dados_para_sessao;

    echo 'sucesso';

} catch (Exception $e) {
    echo 'Ocorreu um erro: ' . $e->getMessage();
}
?>