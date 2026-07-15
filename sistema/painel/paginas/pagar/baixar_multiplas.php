<?php
$tabela = 'pagar';
require_once("../../../conexao.php");
@session_start();
$id_usuario = $_SESSION['id'];

$ids = $_POST['ids'] ?? '';
$ids = rtrim($ids, '-');
$lista_ids = array_filter(explode('-', $ids), function ($v) {
    return $v !== '' && (int) $v > 0;
});

$data_pgto       = $_POST['data_baixar'] ?? date('Y-m-d');
$forma_pgto      = $_POST['forma_baixar'] ?? '';
$banco           = $_POST['banco_baixar'] ?? '';
$numero_operacao = $_POST['numero_operacao_baixar'] ?? '';
$obs_baixar      = trim($_POST['obs_baixar'] ?? '');

if (count($lista_ids) === 0) {
    echo 'Nenhum título selecionado!';
    exit();
}
if (empty($forma_pgto)) {
    echo 'Selecione a forma de pagamento!';
    exit();
}
if (empty($banco)) {
    echo 'Selecione o banco para realizar a baixa em massa!';
    exit();
}

// Caixa do operador
$query1 = $pdo->query("SELECT * FROM caixas WHERE operador = '$id_usuario' AND data_fechamento IS NULL ORDER BY id DESC LIMIT 1");
$res1 = $query1->fetchAll(PDO::FETCH_ASSOC);
$id_caixa = (@count($res1) > 0) ? $res1[0]['id'] : 0;

$titulos_processados = 0;

try {
    $pdo->beginTransaction();

    foreach ($lista_ids as $id) {
        $id = (int) $id;

        $conta = $pdo->query("SELECT * FROM $tabela WHERE id = '$id'")->fetch(PDO::FETCH_ASSOC);
        if (!$conta || $conta['pago'] === 'Sim') continue;

        $subtotal_titulo = ($conta['pago'] === 'Parcial')
            ? (float) $conta['valor_restante']
            : (float) ($conta['subtotal'] ?: $conta['valor']);

        if ($subtotal_titulo <= 0) continue;

        // Subtotal final gravado na conta: mantém o já existente (Parcial já tem multa/juros/desconto
        // aplicados de uma baixa anterior); preenche se estiver vazio (títulos antigos sem subtotal).
        $subtotal_final = ($conta['pago'] === 'Parcial')
            ? $conta['subtotal']
            : ($conta['subtotal'] ?: $conta['valor']);

        $descricao = addslashes($conta['descricao']);
        $obs_final = addslashes($obs_baixar !== '' ? $obs_baixar : $conta['obs']);

        $pdo->query("INSERT INTO pagar_pagamentos SET
            id_pagar        = '$id',
            valor           = '$subtotal_titulo',
            data_pgto       = '$data_pgto',
            forma_pgto      = '$forma_pgto',
            banco           = '$banco',
            numero_operacao = '" . addslashes($numero_operacao) . "'");

        $pdo->query("INSERT INTO linha_bancos SET
            descricao     = '$descricao',
            id_banco      = '$banco',
            data          = '$data_pgto',
            remetente     = '$id_usuario',
            n_fiscal      = '',
            classificacao = 2,
            mes_ref       = MONTH('$data_pgto'),
            credito       = '0',
            debito        = '$subtotal_titulo',
            saldo         = (SELECT saldo FROM bancos WHERE id = '$banco') - '$subtotal_titulo',
            status        = 'Confirmado'
        ");
        $pdo->query("UPDATE bancos SET saldo = saldo - $subtotal_titulo WHERE id = '$banco'");

        $pdo->query("UPDATE $tabela SET
            usuario_pgto   = '$id_usuario',
            pago           = 'Sim',
            subtotal       = '$subtotal_final',
            valor_restante = 0,
            data_pgto      = '$data_pgto',
            forma_pgto     = '$forma_pgto',
            caixa          = '$id_caixa',
            hora           = curTime(),
            obs            = '$obs_final'
            WHERE id = '$id'");

        // Gera próxima conta recorrente, igual à baixa individual
        $frequencia = $conta['frequencia'];
        if ($frequencia > 0) {
            $data_venc = $conta['vencimento'];
            if (in_array($frequencia, [30, 31])) {
                $nova_data_vencimento = date('Y-m-d', strtotime("+1 month", strtotime($data_venc)));
            } elseif ($frequencia == 90) {
                $nova_data_vencimento = date('Y-m-d', strtotime("+3 months", strtotime($data_venc)));
            } elseif ($frequencia == 180) {
                $nova_data_vencimento = date('Y-m-d', strtotime("+6 months", strtotime($data_venc)));
            } elseif (in_array($frequencia, [360, 365])) {
                $nova_data_vencimento = date('Y-m-d', strtotime("+1 year", strtotime($data_venc)));
            } else {
                $nova_data_vencimento = date('Y-m-d', strtotime("+$frequencia days", strtotime($data_venc)));
            }

            $pdo->query("INSERT INTO $tabela SET
                descricao    = '$descricao',
                fornecedor   = '{$conta['fornecedor']}',
                funcionario  = '{$conta['funcionario']}',
                valor        = '{$conta['valor']}',
                data_lanc    = curDate(),
                vencimento   = '$nova_data_vencimento',
                frequencia   = '$frequencia',
                forma_pgto   = '$forma_pgto',
                arquivo      = '" . addslashes($conta['arquivo']) . "',
                pago         = 'Não',
                referencia   = '" . addslashes($conta['referencia']) . "',
                usuario_lanc = '$id_usuario',
                caixa        = '$id_caixa',
                hora         = curTime()
            ");
        }

        $titulos_processados++;
    }

    $pdo->commit();

    echo ($titulos_processados > 0) ? 'Baixado com Sucesso' : 'Nenhum título pendente encontrado para baixa!';
} catch (Exception $e) {
    $pdo->rollBack();
    echo 'Erro ao processar baixa em massa: ' . $e->getMessage();
}
?>
