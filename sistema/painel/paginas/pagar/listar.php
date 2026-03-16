<?php
@session_start();
$mostrar_registros = @$_SESSION['registros'];
$id_usuario = @$_SESSION['id'];
$tabela = 'pagar';
require_once("../../../conexao.php");
require_once("../../verificar.php");

// Filtro de usuário (removido espaço extra que poderia bugar)
$sql_usuario_lanc = ($mostrar_registros == 'Não') ? " and usuario_lanc = '$id_usuario'" : "";

$data_hoje = date('Y-m-d');
$mes_atual = date('m');
$ano_atual = date('Y');
$data_inicio_mes = $ano_atual . "-" . $mes_atual . "-01";

// Cálculo de datas do mês
if (in_array($mes_atual, ['04', '06', '09', '11'])) {
	$data_final_mes = $ano_atual . '-' . $mes_atual . '-30';
} else if ($mes_atual == '02') {
	$bissexto = date('L', mktime(0, 0, 0, 1, 1, $ano_atual));
	$data_final_mes = ($bissexto == 1) ? $ano_atual . '-02-29' : $ano_atual . '-02-28';
} else {
	$data_final_mes = $ano_atual . '-' . $mes_atual . '-31';
}

$dataInicial = @$_POST['p2'] ?: $data_inicio_mes;
$dataFinal = @$_POST['p3'] ?: $data_final_mes;
$filtro = @$_POST['p1'];
$tipo_data = @$_POST['p4'] ?: 'vencimento';
$atacadista = @$_POST['p5'];
$forma_pgto = @$_POST['p6'];
$funcionario = @$_POST['p7'];

// Filtros específicos de busca
$sql_atacadista = (!empty($atacadista)) ? " AND fornecedor = '$atacadista'" : "";
$sql_pgto = (!empty($forma_pgto)) ? " AND forma_pgto = '$forma_pgto'" : "";
$sql_funcionario = (!empty($funcionario)) ? " AND funcionario = '$funcionario'" : "";

// Query Base para os Cards (respeitando os filtros de pessoa selecionados)
$query_base = "WHERE 1=1 $sql_usuario_lanc $sql_atacadista $sql_pgto $sql_funcionario";

// --- CÁLCULO DOS CARDS (REGRA DE INTERVALO SEVERA) ---

// 1. VENCIDAS: Somente as que estão no intervalo de data E que já venceram
$query = $pdo->query("SELECT SUM(valor) as total FROM $tabela $query_base AND (pago != 'Sim' OR pago IS NULL) AND vencimento < '$data_hoje' AND $tipo_data >= '$dataInicial' AND $tipo_data <= '$dataFinal'");
$res = $query->fetch(PDO::FETCH_ASSOC);
$total_vencidas_val = $res['total'] ?? 0;
$total_vencidasF = number_format($total_vencidas_val, 2, ',', '.');

// 2. A VENCER: Somente as que estão no intervalo de data E que ainda vão vencer
$query = $pdo->query("SELECT SUM(valor) as total FROM $tabela $query_base AND (pago != 'Sim' OR pago IS NULL) AND vencimento >= '$data_hoje' AND $tipo_data >= '$dataInicial' AND $tipo_data <= '$dataFinal'");
$res = $query->fetch(PDO::FETCH_ASSOC);
$total_a_vencer_val = $res['total'] ?? 0;
$total_hojeF = number_format($total_a_vencer_val, 2, ',', '.');

// 3. PAGAS: Somente as que foram pagas no intervalo (usando subtotal)
$query = $pdo->query("SELECT SUM(subtotal) as total FROM $tabela $query_base AND pago = 'Sim' AND data_pgto >= '$dataInicial' AND data_pgto <= '$dataFinal'");
$res = $query->fetch(PDO::FETCH_ASSOC);
$total_pagas_val = $res['total'] ?? 0;
$total_recebidasF = number_format($total_pagas_val, 2, ',', '.');

// 4. TOTAL: Soma aritmética do que aparece nos cards acima para o período
$total_total_val = $total_vencidas_val + $total_a_vencer_val + $total_pagas_val;
$total_totalF = number_format($total_total_val, 2, ',', '.');

// --- LÓGICA DA LISTAGEM (TABELA) ---
$data_amanha = date('Y-m-d', strtotime("+1 days", strtotime($data_hoje)));

if ($filtro == 'Vencidas') {
	$query = $pdo->query("SELECT * from $tabela $query_base AND (pago != 'Sim' OR pago IS NULL) AND vencimento < '$data_hoje' AND $tipo_data >= '$dataInicial' AND $tipo_data <= '$dataFinal' ORDER BY vencimento ASC");
} else if ($filtro == 'Recebidas' || $filtro == 'Pagas') {
	$query = $pdo->query("SELECT * from $tabela $query_base AND pago = 'Sim' AND data_pgto >= '$dataInicial' AND data_pgto <= '$dataFinal' ORDER BY data_pgto DESC");
} else if ($filtro == 'Hoje') {
	$query = $pdo->query("SELECT * from $tabela $query_base AND vencimento = '$data_hoje' AND (pago != 'Sim' OR pago IS NULL) ORDER BY id DESC");
} else {
	// Filtro padrão de período
	$query = $pdo->query("SELECT * from $tabela $query_base AND $tipo_data >= '$dataInicial' AND $tipo_data <= '$dataFinal' ORDER BY $tipo_data DESC");
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
    <th>Descrição</th>  
    <th>Valor</th> 
    <th class="esc">Pessoa</th> 
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
		$fornecedor = $res[$i]['fornecedor'];
		$funcionario_row = $res[$i]['funcionario'];
		$valor = $res[$i]['valor'];
		$vencimento = $res[$i]['vencimento'];
		$data_pgto = $res[$i]['data_pgto'];
		$forma_pgto = $res[$i]['forma_pgto'];
		$frequencia = $res[$i]['frequencia'];
		$obs = $res[$i]['obs'];
		$arquivo = $res[$i]['arquivo'];
		$id_ref = $res[$i]['id_ref'];
		$subtotal = $res[$i]['subtotal'];
		$pago = $res[$i]['pago'] ?: "Não";

		$vencimentoF = implode('/', array_reverse(explode('-', $vencimento)));
		$data_pgtoF = ($data_pgto && $data_pgto != '0000-00-00') ? implode('/', array_reverse(explode('-', $data_pgto))) : '---';
		$valor_finalF = number_format(($pago == "Sim" ? $subtotal : $valor), 2, ',', '.');

		$classe_pago = ($pago == 'Sim') ? 'verde' : 'text-danger';
		$ocultar = ($pago == 'Sim') ? 'ocultar' : '';
		$classe_venc = (strtotime($vencimento) < strtotime($data_hoje) && $pago != 'Sim') ? 'text-danger' : '';

		// Nomes de Pessoa
		$nome_pessoa = 'Sem Registro';
		if ($fornecedor != 0) {
			$nome_pessoa = $pdo->query("SELECT nome_atacadista FROM fornecedores where id = '$fornecedor'")->fetchColumn() ?: 'Fornecedor Excluído';
		} elseif ($funcionario_row != 0) {
			$nome_pessoa = $pdo->query("SELECT nome FROM funcionarios where id = '$funcionario_row'")->fetchColumn() ?: 'Funcionário Excluído';
		}

		$ext = pathinfo($arquivo, PATHINFO_EXTENSION);
		$tumb_arquivo = (in_array(strtolower($ext), ['pdf', 'rar', 'zip', 'doc', 'docx', 'xlsx', 'xlsm', 'xls', 'xml'])) ? strtolower($ext) . '.png' : $arquivo;

		echo <<<HTML
<tr>
<td align="center">
    <input type="checkbox" id="seletor-{$id}" onchange="selecionar('{$id}')">
</td>
<td><i class="fa fa-square {$classe_pago} mr-1"></i> {$descricao}</td>
<td>R$ {$valor_finalF}</td>   
<td class="esc">{$nome_pessoa}</td>
<td class="esc {$classe_venc}">{$vencimentoF}</td>
<td class="esc">{$data_pgtoF}</td>
<td class="esc"><a href="images/contas/{$arquivo}" target="_blank"><img src="images/contas/{$tumb_arquivo}" width="25px"></a></td>
<td>
    <big><a href="#" onclick="editar('{$id}','{$descricao}','{$valor}','{$fornecedor}','{$funcionario_row}','{$vencimento}','{$data_pgto}','{$forma_pgto}','{$frequencia}','{$obs}','{$arquivo}')"><i class="fa fa-edit text-primary"></i></a></big>
    <big><a href="#" onclick="excluir('{$id}')"><i class="fa fa-trash text-danger"></i></a></big>
    <big><a class="{$ocultar}" href="#" onclick="baixar('{$id}', '{$valor}', '{$descricao}', '{$forma_pgto}')"><i class="fa fa-check-square text-success"></i></a></big>
</td>
</tr>
HTML;
	}
	echo "</tbody></table></small>";
} else {
	echo 'Nenhum Registro Encontrado para este período!';
}

// Injeção de valores nos cards
echo <<<HTML
<script>
    $('#total_vencidas').text('R$ {$total_vencidasF}');
    $('#total_a_vencer').text('R$ {$total_hojeF}');
    $('#total_pagas').text('R$ {$total_recebidasF}');
    $('#total_total').text('R$ {$total_totalF}');
</script>
HTML;
