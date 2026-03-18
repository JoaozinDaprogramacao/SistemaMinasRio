<?php
@session_start();
$mostrar_registros = @$_SESSION['registros'];
$id_usuario = @$_SESSION['id'];
$tabela = 'receber';
require_once("../../../conexao.php");
require_once("../../verificar.php");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$sql_usuario_lanc = ($mostrar_registros == 'Não') ? " AND t.usuario_lanc = '$id_usuario'" : " ";

$data_hoje = date('Y-m-d');
$mes_atual = date('m');
$ano_atual = date('Y');
$data_inicio_mes = $ano_atual . "-" . $mes_atual . "-01";

if (in_array($mes_atual, ['04', '06', '09', '11'])) {
	$data_final_mes = $ano_atual . '-' . $mes_atual . '-30';
} else if ($mes_atual == '02') {
	$data_final_mes = (date('L', mktime(0, 0, 0, 1, 1, $ano_atual)) == 1) ? $ano_atual . '-02-29' : $ano_atual . '-02-28';
} else {
	$data_final_mes = $ano_atual . '-' . $mes_atual . '-31';
}

$filtro = @$_POST['p1'];
$dataInicial = @$_POST['p2'] ?: $data_inicio_mes;
$dataFinal = @$_POST['p3'] ?: $data_final_mes;
$tipo_data = @$_POST['p4'] ?: 'vencimento';
$atacadista = @$_POST['p5'];
$forma_pgto = @$_POST['p6'];
$tipo_conta = @$_POST['p7'];

$sql_atacadista = !empty($atacadista) ? " AND t.cliente = '$atacadista'" : "";
$sql_pgto = !empty($forma_pgto) ? " AND t.forma_pgto = '$forma_pgto'" : "";
$sql_tipo_conta = !empty($tipo_conta) ? " AND pl.nome = '$tipo_conta'" : "";

// Base do JOIN para Totais e Listagem
$base_from = " FROM $tabela t 
               LEFT JOIN romaneio_venda r ON t.id_romaneio = r.id 
               LEFT JOIN planos_pgto pl ON r.plano_pgto = pl.id ";

$base_where = " WHERE t.$tipo_data >= '$dataInicial' AND t.$tipo_data <= '$dataFinal' 
                $sql_usuario_lanc $sql_atacadista $sql_pgto $sql_tipo_conta ";

// Inicialização de Totais
$total_pago = 0;
$total_pendentes = 0;

$res = $pdo->query("SELECT SUM(t.valor) as total $base_from $base_where AND t.vencimento < curDate() AND t.pago = 'Não'")->fetch(PDO::FETCH_ASSOC);
$total_vencidas = $res['total'] ?? 0;

$res = $pdo->query("SELECT SUM(t.subtotal) as total $base_from $base_where AND t.pago = 'Sim'")->fetch(PDO::FETCH_ASSOC);
$total_recebidas = $res['total'] ?? 0;

$res = $pdo->query("SELECT SUM(t.valor) as total $base_from $base_where AND t.vencimento >= curDate() AND t.pago = 'Não'")->fetch(PDO::FETCH_ASSOC);
$total_a_vencer = $res['total'] ?? 0;

$res = $pdo->query("SELECT SUM(t.valor) as total $base_from $base_where")->fetch(PDO::FETCH_ASSOC);
$total_total = $res['total'] ?? 0;

$total_vencidasF = number_format($total_vencidas, 2, ',', '.');
$total_recebidasF = number_format($total_recebidas, 2, ',', '.');
$total_a_vencerF = number_format($total_a_vencer, 2, ',', '.');
$total_totalF = number_format($total_total, 2, ',', '.');

// Query de Listagem
$ordem = "ORDER BY t.vencimento ASC";
$where_filtro = "";

if ($filtro == 'Vencidas') {
	$where_filtro = " AND t.vencimento < curDate() AND t.pago = 'Não'";
} else if ($filtro == 'Recebidas') {
	$where_filtro = " AND t.pago = 'Sim'";
	$ordem = "ORDER BY t.data_pgto DESC";
} else if ($filtro == 'AVencer') {
	$where_filtro = " AND t.vencimento >= curDate() AND t.pago = 'Não'";
} else {
	$ordem = "ORDER BY CASE 
                WHEN t.pago = 'Não' AND t.vencimento < curDate() THEN 1 
                WHEN t.pago = 'Não' AND t.vencimento >= curDate() THEN 2 
                ELSE 3 END ASC, t.vencimento ASC";
}

$query = $pdo->query("SELECT t.*, pl.nome as nome_plano $base_from $base_where $where_filtro $ordem");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);

if ($linhas > 0) {
	echo <<<HTML
<small>
    <table class="table table-bordered text-nowrap border-bottom dt-responsive" id="tabela">
    <thead> 
        <tr> 
            <th align="center" width="5%" class="text-center">Selecionar</th>
            <th class="esc">Data Lançamento</th> <th>Descrição</th>  
            <th>Valor</th> 
            <th class="esc">Cliente</th>    
            <th class="esc">Vencimento</th> 
            <th class="esc">Pagamento</th>      
            <th class="esc">Arquivo</th>    
            <th>Ações</th>
        </tr> 
    </thead>
    <tbody> 
HTML;

	for ($i = 0; $i < $linhas; $i++) {
		$id = $res[$i]['id'];
		$descricao = $res[$i]['descricao'];
		$cliente = $res[$i]['cliente'];
		$valor = $res[$i]['valor'];
		$vencimento = $res[$i]['vencimento'];
		$data_pgto = $res[$i]['data_pgto'];
		$data_lanc = $res[$i]['data_lanc'];
		$arquivo = $res[$i]['arquivo'] ?? '';
		$id_ref = $res[$i]['id_ref'];
		$pago = $res[$i]['pago'];
		$subtotal = $res[$i]['subtotal'];
		$forma_pgto_row = $res[$i]['forma_pgto'];
		$frequencia = $res[$i]['frequencia'];
		$obs = $res[$i]['obs'];
		$usuario_lanc = $res[$i]['usuario_lanc'];
		$usuario_pgto = $res[$i]['usuario_pgto'];

		$data_lancF = date('d/m/Y', strtotime($data_lanc));
		$vencimentoF = date('d/m/Y', strtotime($vencimento));
		$data_pgtoF = ($data_pgto && $data_pgto != '0000-00-00') ? date('d/m/Y', strtotime($data_pgto)) : "";

		if ($pago == 'Sim') {
			$classe_pago = 'verde';
			$total_pago += $subtotal;
			$valor_finalF = number_format($subtotal, 2, ',', '.');
		} else {
			$classe_pago = 'text-danger';
			$total_pendentes += $valor;
			$valor_finalF = number_format($valor, 2, ',', '.');
		}

		$sql_cli = $pdo->query("SELECT nome FROM clientes where id = '$cliente'")->fetch(PDO::FETCH_ASSOC);
		$nome_cliente = $sql_cli['nome'] ?? 'Sem Registro';

		$sql_f = $pdo->query("SELECT nome, taxa FROM formas_pgto where id = '$forma_pgto_row'")->fetch(PDO::FETCH_ASSOC);
		$taxa_pgto = $sql_f['taxa'] ?? 0;

		$ext = pathinfo($arquivo, PATHINFO_EXTENSION);
		$tumb_arquivo = (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif'])) ? $arquivo : ($ext ? $ext . '.png' : 'sem-foto.png');

		$classe_venc = (strtotime($vencimento) < strtotime($data_hoje) && $pago != 'Sim') ? 'text-danger' : '';
		$taxa_conta = $taxa_pgto * $valor / 100;

		echo <<<HTML
<tr>
    <td align="center">
        <div class="custom-checkbox custom-control">
            <input type="checkbox" class="custom-control-input" id="seletor-{$id}" onchange="selecionar('{$id}')">
            <label for="seletor-{$id}" class="custom-control-label mt-1 text-dark"></label>
        </div>
    </td>
    <td class="esc">{$data_lancF}</td> 
    <td><i class="fa fa-square {$classe_pago} mr-1"></i> {$descricao}</td>
    <td>R$ {$valor_finalF}</td>
    <td class="esc">{$nome_cliente}</td>
    <td class="esc {$classe_venc}">{$vencimentoF}</td>
    <td class="esc">{$data_pgtoF}</td>
    <td class="esc"><a href="images/contas/{$arquivo}" target="_blank"><img src="images/contas/{$tumb_arquivo}" width="25px"></a></td>
    <td>
        <big><a href="#" onclick="editar('{$id}','{$descricao}','{$valor}','{$cliente}','{$vencimento}','{$data_pgto}','{$forma_pgto_row}','{$frequencia}','{$obs}','{$arquivo}')" title="Editar Dados"><i class="fa fa-edit text-primary"></i></a></big>
        <div style="display: inline-block;" class="dropdown">
            <a href="#" data-bs-toggle="dropdown"><i class="fa fa-trash text-danger"></i></a>
            <div class="dropdown-menu">
                <div class="dropdown-item-text">Confirmar? <a href="#" onclick="excluir('{$id}')"><span class="text-danger">Sim</span></a></div>
            </div>
        </div>
        <big><a href="#" onclick="baixar('{$id}', '{$valor}', '{$descricao}', '{$forma_pgto_row}', '{$taxa_conta}', '0', '0')" title="Baixar Conta"><i class="fa fa-check-square text-success"></i></a></big>
HTML;
		if (!empty($id_ref)) {
			echo " <big><a href='#' onclick=\"imprimir('{$id_ref}')\" title='Imprimir Romaneio'><i class='fa fa-file-pdf-o text-info'></i></a></big>";
		}
		echo "</td></tr>";
	}

	$total_pagoF = number_format($total_pago, 2, ',', '.');
	$total_pendentesF = number_format($total_pendentes, 2, ',', '.');

	echo <<<HTML
    </tbody>
    </table>
    <div class="d-flex justify-content-between mt-3">
        <span class="ocultar_mobile">Filtro: <b>{$tipo_data}</b></span>
        <p>Pendentes: <span class="text-danger">R$ {$total_pendentesF}</span> | Pago: <span class="text-success">R$ {$total_pagoF}</span></p>
    </div>
</small>
HTML;
} else {
	echo 'Nenhum Registro Encontrado!';
}
?>

<script>
	function imprimir(id) {
		window.open('rel/gerar_pdf_romaneio.php?id=' + id, '_blank');
	}

	$(document).ready(function() {
		if ($.fn.DataTable.isDataTable('#tabela')) {
			$('#tabela').DataTable().destroy();
		}
		$('#tabela').DataTable({
			"ordering": false,
			"stateSave": true,
			"language": {
				"url": "//cdn.datatables.net/plug-ins/1.13.2/i18n/pt-BR.json"
			}
		});

		$('#total_vencidas').text('R$ <?= $total_vencidasF ?>');
		$('#total_a_vencer').text('R$ <?= $total_a_vencerF ?>');
		$('#total_recebidas').text('R$ <?= $total_recebidasF ?>');
		$('#total_total').text('R$ <?= $total_totalF ?>');
	});
</script>