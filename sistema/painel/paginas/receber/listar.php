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

$sql_usuario_lanc = ($mostrar_registros == 'Não') ? " and usuario_lanc = '$id_usuario'" : " ";

$data_hoje = date('Y-m-d');
$mes_atual = date('m');
$ano_atual = date('Y');
$data_inicio_mes = $ano_atual . "-" . $mes_atual . "-01";

if ($mes_atual == '04' || $mes_atual == '06' || $mes_atual == '09' || $mes_atual == '11') {
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

$sql_atacadista = !empty($atacadista) ? " AND cliente = '$atacadista'" : "";
$sql_pgto = !empty($forma_pgto) ? " AND forma_pgto = '$forma_pgto'" : "";
$sql_tipo_conta = !empty($tipo_conta) ? " AND tipo_conta = '$tipo_conta'" : "";

// Inicialização Única de Totais
$total_pago = 0;
$total_pendentes = 0;
$total_vencidas = 0;
$total_recebidas = 0;
$total_a_vencer = 0;
$total_total = 0;

// Query Totais para os Cards
$res = $pdo->query("SELECT SUM(valor) as total FROM $tabela WHERE vencimento < curDate() AND pago = 'Não' AND $tipo_data >= '$dataInicial' AND $tipo_data <= '$dataFinal' $sql_usuario_lanc $sql_atacadista $sql_pgto $sql_tipo_conta")->fetch(PDO::FETCH_ASSOC);
$total_vencidas = $res['total'] ?? 0;

$res = $pdo->query("SELECT SUM(subtotal) as total FROM $tabela WHERE pago = 'Sim' AND data_pgto >= '$dataInicial' AND data_pgto <= '$dataFinal' $sql_usuario_lanc $sql_atacadista $sql_pgto $sql_tipo_conta")->fetch(PDO::FETCH_ASSOC);
$total_recebidas = $res['total'] ?? 0;

$res = $pdo->query("SELECT SUM(valor) as total FROM $tabela WHERE vencimento >= curDate() AND pago = 'Não' AND $tipo_data >= '$dataInicial' AND $tipo_data <= '$dataFinal' $sql_usuario_lanc $sql_atacadista $sql_pgto $sql_tipo_conta")->fetch(PDO::FETCH_ASSOC);
$total_a_vencer = $res['total'] ?? 0;

$res = $pdo->query("SELECT SUM(valor) as total FROM $tabela WHERE $tipo_data >= '$dataInicial' AND $tipo_data <= '$dataFinal' $sql_usuario_lanc $sql_atacadista $sql_pgto $sql_tipo_conta")->fetch(PDO::FETCH_ASSOC);
$total_total = $res['total'] ?? 0;

$total_vencidasF = number_format($total_vencidas, 2, ',', '.');
$total_recebidasF = number_format($total_recebidas, 2, ',', '.');
$total_a_vencerF = number_format($total_a_vencer, 2, ',', '.');
$total_totalF = number_format($total_total, 2, ',', '.');
$total_valorF = $total_totalF;

// Query Principal da Listagem com Ordenação Inteligente
if ($filtro == 'Vencidas') {
	$query = $pdo->query("SELECT * from $tabela where vencimento < curDate() and pago = 'Não' and $tipo_data >= '$dataInicial' and $tipo_data <= '$dataFinal' $sql_usuario_lanc $sql_atacadista $sql_pgto $sql_tipo_conta order by vencimento asc ");
} else if ($filtro == 'Recebidas') {
	$query = $pdo->query("SELECT * from $tabela where pago = 'Sim' and $tipo_data >= '$dataInicial' and $tipo_data <= '$dataFinal' $sql_usuario_lanc $sql_atacadista $sql_pgto $sql_tipo_conta order by data_pgto desc ");
} else if ($filtro == 'AVencer') {
	$query = $pdo->query("SELECT * from $tabela where vencimento >= curDate() and pago = 'Não' $sql_usuario_lanc $sql_atacadista $sql_pgto $sql_tipo_conta order by vencimento asc ");
} else {
	// ESTA É A QUERY QUE VOCÊ QUERIA: Vencidos -> Hoje/Futuro -> Pagos por último
	$query = $pdo->query("SELECT *, 
        CASE 
            WHEN pago = 'Não' AND vencimento < curDate() THEN 1 
            WHEN pago = 'Não' AND vencimento >= curDate() THEN 2 
            ELSE 3 
        END AS ordem_status 
        FROM $tabela 
        WHERE $tipo_data >= '$dataInicial' AND $tipo_data <= '$dataFinal' 
        $sql_usuario_lanc $sql_atacadista $sql_pgto $sql_tipo_conta 
        ORDER BY ordem_status ASC, vencimento ASC");
}

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
		$data_lanc = $res[$i]['data_lanc']; // Pega a data do banco
		$data_lancF = implode('/', array_reverse(explode('-', $data_lanc))); // Formata para DD/MM/AAAA
		$id = $res[$i]['id'];
		$descricao = $res[$i]['descricao'];
		$cliente = $res[$i]['cliente'];
		$valor = $res[$i]['valor'];
		$vencimento = $res[$i]['vencimento'];
		$data_pgto = $res[$i]['data_pgto'];
		$arquivo = $res[$i]['arquivo'];
		$id_ref = $res[$i]['id_ref'];
		$pago = $res[$i]['pago'];
		$subtotal = $res[$i]['subtotal'];
		$multa = $res[$i]['multa'];
		$juros = $res[$i]['juros'];
		$desconto = $res[$i]['desconto'];
		$taxa = $res[$i]['taxa'];
		$forma_pgto_row = $res[$i]['forma_pgto'];
		$frequencia = $res[$i]['frequencia'];
		$obs = $res[$i]['obs'];
		$usuario_lanc = $res[$i]['usuario_lanc'];
		$usuario_pgto = $res[$i]['usuario_pgto'];

		$vencimentoF = implode('/', array_reverse(explode('-', $vencimento)));
		$data_pgtoF = ($data_pgto && $data_pgto != '0000-00-00') ? implode('/', array_reverse(explode('-', $data_pgto))) : "";

		if ($pago == 'Sim') {
			$classe_pago = 'verde';
			$ocultar = 'ocultar';
			$ocultar_pendentes = '';
			$total_pago += $subtotal;
			$valor_finalF = number_format($subtotal, 2, ',', '.');
		} else {
			$classe_pago = 'text-danger';
			$ocultar_pendentes = 'ocultar';
			$ocultar = '';
			$total_pendentes += $valor;
			$valor_finalF = number_format($valor, 2, ',', '.');
		}

		// Dados auxiliares (Nomes)
		$sql_usu = $pdo->query("SELECT nome FROM usuarios where id = '$usuario_lanc'")->fetch(PDO::FETCH_ASSOC);
		$nome_usu_lanc = $sql_usu['nome'] ?? 'Sem Usuário';

		$sql_usu_p = $pdo->query("SELECT nome FROM usuarios where id = '$usuario_pgto'")->fetch(PDO::FETCH_ASSOC);
		$nome_usu_pgto = $sql_usu_p['nome'] ?? 'Sem Usuário';

		$sql_cli = $pdo->query("SELECT nome FROM clientes where id = '$cliente'")->fetch(PDO::FETCH_ASSOC);
		$nome_cliente = $sql_cli['nome'] ?? 'Sem Registro';

		$sql_f = $pdo->query("SELECT nome, taxa FROM formas_pgto where id = '$forma_pgto_row'")->fetch(PDO::FETCH_ASSOC);
		$nome_pgto = $sql_f['nome'] ?? 'Sem Registro';
		$taxa_pgto = $sql_f['taxa'] ?? 0;

		$ext = pathinfo($arquivo, PATHINFO_EXTENSION);
		$tumb_arquivo = (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif'])) ? $arquivo : ($ext ? $ext . '.png' : 'sem-foto.png');
		if (!file_exists('images/contas/' . $tumb_arquivo)) $tumb_arquivo = 'pdf.png'; // Fallback simples

		$valor_multa = 0;
		$valor_juros = 0;
		$classe_venc = '';
		if (strtotime($vencimento) < strtotime($data_hoje) && $pago != 'Sim') {
			$classe_venc = 'text-danger';
			$dias_vencidos = floor((strtotime($data_hoje) - strtotime($vencimento)) / (60 * 60 * 24));
			$valor_multa = $multa_atraso;
			$valor_juros = ($valor * $juros_atraso / 100) * $dias_vencidos;
		}

		$taxa_conta = $taxa_pgto * $valor / 100;

		echo <<<HTML
<tr>
<td align="center">
        <div class="custom-checkbox custom-control">
            <input type="checkbox" class="custom-control-input" id="seletor-{$id}" onchange="selecionar('{$id}')">
            <label for="seletor-{$id}" class="custom-control-label mt-1 text-dark"></label>
        </div>
    </td>
    <td class="esc">{$data_lancF}</td> <td><i class="fa fa-square {$classe_pago} mr-1"></i> {$descricao}</td>
    <td>R$ {$valor_finalF}</td>
<td class="esc">{$nome_cliente}</td>
<td class="esc {$classe_venc}">{$vencimentoF}</td>
<td class="esc">{$data_pgtoF}</td>
<td class="esc"><a href="images/contas/{$arquivo}" target="_blank"><img src="images/contas/{$tumb_arquivo}" width="25px"></a></td>
<td>
    <big><a href="#" onclick="editar('{$id}','{$descricao}','{$valor}','{$cliente}','{$vencimento}','{$data_pgto}','{$forma_pgto_row}','{$frequencia}','{$obs}','{$arquivo}')"><i class="fa fa-edit text-primary"></i></a></big>
    <div style="display: inline-block;" class="dropdown">
        <a href="#" data-bs-toggle="dropdown"><i class="fa fa-trash text-danger"></i></a>
        <div class="dropdown-menu">
            <div class="dropdown-item-text">Confirmar? <a href="#" onclick="excluir('{$id}')"><span class="text-danger">Sim</span></a></div>
        </div>
    </div>
    <big><a href="#" onclick="baixar('{$id}', '{$valor}', '{$descricao}', '{$forma_pgto_row}', '{$taxa_conta}', '{$valor_multa}', '{$valor_juros}')"><i class="fa fa-check-square text-success"></i></a></big>
HTML;
		if (!empty($id_ref)) {
			echo " <big><a href='#' onclick=\"imprimir('{$id_ref}')\"><i class='fa fa-file-pdf-o text-info'></i></a></big>";
		}
		echo "</td></tr>";
	}

	$total_pagoF = number_format($total_pago, 2, ',', '.');
	$total_pendentesF = number_format($total_pendentes, 2, ',', '.');

	echo <<<HTML
    </tbody>
    </table>
    <div class="d-flex justify-content-between mt-3">
        <span class="ocultar_mobile">Filtrar: <a href="#" onclick="tipoData('vencimento')">Venc</a> / <a href="#" onclick="tipoData('data_pgto')">Pgto</a></span>
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
</script>
<input type="hidden" id="val_vencidas" value="R$ <?= $total_vencidasF ?>">
<input type="hidden" id="val_a_vencer" value="R$ <?= $total_a_vencerF ?>">
<input type="hidden" id="val_recebidas" value="R$ <?= $total_recebidasF ?>">
<input type="hidden" id="val_total" value="R$ <?= $total_totalF ?>">

<script>
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

		// Puxa os valores dos inputs hidden e joga nos cards da index
		$('#total_vencidas').text($('#val_vencidas').val());
		$('#total_a_vencer').text($('#val_a_vencer').val());
		$('#total_recebidas').text($('#val_recebidas').val());
		$('#total_total').text($('#val_total').val());
	});
</script>
</script>