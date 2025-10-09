<?php 
$tabela = 'pagar';
require_once("../../../conexao.php");
@session_start();
$id_usuario = $_SESSION['id'];

$id = $_POST['id-baixar'];
$data_atual = date('Y-m-d');

$valor = str_replace(',', '.', $_POST['valor-baixar']);
$taxa = str_replace(',', '.', $_POST['valor-taxa']);
$multa = str_replace(',', '.', $_POST['valor-multa']);
$desconto = str_replace(',', '.', $_POST['valor-desconto']);
$juros = str_replace(',', '.', $_POST['valor-juros']);
$valor_padrao = $valor;
$subtotal = str_replace(',', '.', $_POST['subtotal']);
$saida = $_POST['saida-baixar'];
$data_baixar = $_POST['data-baixar'];
$banco = $_POST['banco'];
$descricao_banco = $_POST['descricao_banco'] ?? 0;

if (empty($banco)) {
    echo 'Por favor selecione um banco para o pagamento!';
    exit();
}

$juros = $juros ?: 0;
$multa = $multa ?: 0;
$taxa = $taxa ?: 0;
$desconto = $desconto ?: 0;

// dados da conta a pagar
$query = $pdo->query("SELECT * FROM $tabela WHERE id = '$id'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$conta = $res[0];

$descricao = $conta['descricao'];
$fornecedor = $conta['fornecedor'] ?: 0;
$funcionario = $conta['funcionario'] ?: 0;
$valor_antigo = $conta['valor'];
$data_venc = $conta['vencimento'];
$usuario_pgto = $conta['usuario_pgto'] ?: 0;
$frequencia = $conta['frequencia'];
$saida_antiga = $conta['forma_pgto'];
$arquivo = $conta['arquivo'];
$referencia = $conta['referencia'];

if ($valor > $valor_antigo) {
    echo 'O valor a ser pago não pode ser superior ao valor da conta! O valor da conta é de R$ ' . $valor_antigo;
    exit();
}

if ($valor <= 0) {
    echo 'O valor precisa ser maior que 0';
    exit();
}

// verificar caixa aberto
$query1 = $pdo->query("SELECT * FROM caixas WHERE operador = '$id_usuario' AND data_fechamento IS NULL ORDER BY id DESC LIMIT 1");
$res1 = $query1->fetchAll(PDO::FETCH_ASSOC);
$id_caixa = (@count($res1) > 0) ? $res1[0]['id'] : 0;

if ($valor == $valor_antigo) {
    // pagamento total

    // lançar movimentação
    $pdo->query("INSERT INTO linha_bancos SET 
	    descricao = '$descricao_banco',
        id_banco = '$banco',
        data = '$data_baixar',
        remetente = '$id_usuario',
        n_fiscal = '',
        classificacao = 2,
        mes_ref = MONTH('$data_baixar'),
        credito = '0',
        debito = '$subtotal',
        saldo = (SELECT saldo FROM bancos WHERE id = '$banco') - '$subtotal',
        status = 'Confirmado'
    ");

    $pdo->query("UPDATE bancos SET saldo = saldo - $subtotal WHERE id = '$banco'");

    $pdo->query("UPDATE $tabela SET 
        forma_pgto = '$saida',
        usuario_pgto = '$id_usuario',
        pago = 'Sim',
        subtotal = '$subtotal',
        taxa = '$taxa',
        juros = '$juros',
        multa = '$multa',
        desconto = '$desconto',
        data_pgto = '$data_baixar',
        caixa = '$id_caixa',
        hora = curTime()
        WHERE id = '$id'");

    // gerar próxima conta se houver frequência
    if ($frequencia > 0) {
        if (in_array($frequencia, [30, 31])) {
            $nova_data_vencimento = date('Y/m/d', strtotime("+1 month", strtotime($data_venc)));
        } elseif ($frequencia == 90) {
            $nova_data_vencimento = date('Y/m/d', strtotime("+3 month", strtotime($data_venc)));
        } elseif ($frequencia == 180) {
            $nova_data_vencimento = date('Y/m/d', strtotime("+6 month", strtotime($data_venc)));
        } elseif (in_array($frequencia, [360, 365])) {
            $nova_data_vencimento = date('Y/m/d', strtotime("+1 year", strtotime($data_venc)));
        } else {
            $nova_data_vencimento = date('Y/m/d', strtotime("+$frequencia days", strtotime($data_venc)));
        }

        $pdo->query("INSERT INTO $tabela SET 
            descricao = '$descricao',
            fornecedor = '$fornecedor',
            funcionario = '$funcionario',
            valor = '$valor_antigo',
            data_lanc = curDate(),
            vencimento = '$nova_data_vencimento',
            frequencia = '$frequencia',
            forma_pgto = '$saida_antiga',
            arquivo = '$arquivo',
            pago = 'Não',
            referencia = '$referencia',
            usuario_lanc = '$id_usuario',
            caixa = '$id_caixa',
            hora = curTime()
        ");
    }

} else {
    // pagamento parcial / resíduo

    $descricao = '(Resíduo) ' . $descricao;

    $total_resid = 0;
    $res = $pdo->query("SELECT * FROM $tabela WHERE id_ref = '$id' AND residuo = 'Sim'")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($res as $r) {
        $total_resid += $r['valor'];
    }

    $valor_antigo = $valor_antigo - ($subtotal - $taxa - $multa - $juros);

    $pdo->query("INSERT INTO $tabela SET 
        id_ref = '$id',
        referencia = '$referencia',
        valor = '$valor_padrao',
        data_pgto = curDate(),
        vencimento = curDate(),
        data_lanc = curDate(),
        descricao = '$descricao',
        usuario_lanc = '$id_usuario',
        usuario_pgto = '$id_usuario',
        fornecedor = '$fornecedor',
        funcionario = '$funcionario',
        forma_pgto = '$saida',
        frequencia = '$frequencia',
        arquivo = '$arquivo',
        subtotal = '$subtotal',
        pago = 'Sim',
        taxa = '$taxa',
        multa = '$multa',
        juros = '$juros',
        desconto = '$desconto',
        residuo = 'Sim',
        caixa = '$id_caixa',
        hora = curTime()
    ");

    $pdo->query("UPDATE $tabela SET 
        forma_pgto = '$saida',
        usuario_pgto = '$id_usuario',
        valor = '$valor_antigo',
        data_pgto = curDate()
        WHERE id = '$id'");
    
    // movimentação
    $pdo->query("INSERT INTO linha_bancos SET 
	    descricao = '$descricao_banco',
        id_banco = '$banco',
        data = '$data_baixar',
        remetente = '$id_usuario',
        n_fiscal = '',
        classificacao = 2,
        mes_ref = MONTH('$data_baixar'),
        credito = '0',
        debito = '$subtotal',
        saldo = (SELECT saldo FROM bancos WHERE id = '$banco') - '$subtotal',
        status = 'Confirmado'
    ");

    $pdo->query("UPDATE bancos SET saldo = saldo - $subtotal WHERE id = '$banco'");
}

echo 'Baixado com Sucesso' ;
?>
