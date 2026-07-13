<?php
require_once("../../../conexao.php");

$id = $_POST['id'];

$query = $pdo->query("SELECT * FROM receber WHERE id = '$id'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);

if (@count($res) > 0) {
    $dados = [
        'multa' => $res[0]['multa'],
        'juros' => $res[0]['juros'],
        'acrescimo' => $res[0]['taxa'],
        'desconto' => $res[0]['desconto'],
        'obs' => $res[0]['obs'],
        'pagamentos' => []
    ];

    $query_pgtos = $pdo->query("SELECT * FROM receber_pagamentos WHERE id_receber = '$id' ORDER BY id ASC");
    $res_pgtos = $query_pgtos->fetchAll(PDO::FETCH_ASSOC);

    foreach($res_pgtos as $pg) {
        $dados['pagamentos'][] = [
            'valor' => $pg['valor'],
            'data' => $pg['data_pgto'],
            'forma' => $pg['forma_pgto'],
            'banco' => $pg['banco'],
            'operacao' => $pg['numero_operacao']
        ];
    }

    echo json_encode($dados);
} else {
    echo json_encode(['erro' => 'Registro não encontrado']);
}
?>