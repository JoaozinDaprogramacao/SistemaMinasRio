<?php
@session_start();
$mostrar_registros = @$_SESSION['registros'];
$id_usuario = @$_SESSION['id'];
$tabela = 'pagar';
require_once("../../../conexao.php");
require_once("../../verificar.php");

if ($mostrar_registros == 'Não') {
	$sql_usuario_lanc = " and usuario_lanc = '$id_usuario '";
} else {
	$sql_usuario_lanc = " ";
}

$data_hoje = date('Y-m-d');
$data_atual = date('Y-m-d');
$mes_atual = Date('m');
$ano_atual = Date('Y');
$data_inicio_mes = $ano_atual . "-" . $mes_atual . "-01";

if ($mes_atual == '04' || $mes_atual == '06' || $mes_atual == '09' || $mes_atual == '11') {
	$data_final_mes = $ano_atual . '-' . $mes_atual . '-30';
} else if ($mes_atual == '02') {
	$bissexto = date('L', @mktime(0, 0, 0, 1, 1, $ano_atual));
	$data_final_mes = ($bissexto == 1) ? $ano_atual . '-' . $mes_atual . '-29' : $ano_atual . '-' . $mes_atual . '-28';
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
$cargo = @$_POST['p8'];

$sql_atacadista = "";
if (!empty($atacadista)) {
	$sql_atacadista = " AND fornecedor = '$atacadista'";
}

$sql_pgto = "";
if (!empty($forma_pgto)) {
	$sql_pgto = " AND forma_pgto = '$forma_pgto'";
}

$sql_funcionario = "";
if (!empty($funcionario)) {
	$sql_funcionario = " AND funcionario = '$funcionario'";
}

$query_base = "WHERE 1=1 $sql_usuario_lanc $sql_atacadista $sql_pgto $sql_funcionario";
$periodo_filtro = " AND $tipo_data >= '$dataInicial' AND $tipo_data <= '$dataFinal'";

$query = $pdo->query("SELECT SUM(valor) as total FROM $tabela $query_base AND pago = 'Não' AND vencimento < curDate() $periodo_filtro");
$res = $query->fetch(PDO::FETCH_ASSOC);
$total_vencidasF = number_format(($res['total'] ?? 0), 2, ',', '.');

$query = $pdo->query("SELECT SUM(valor) as total FROM $tabela $query_base AND pago = 'Não' AND vencimento >= curDate() $periodo_filtro");
$res = $query->fetch(PDO::FETCH_ASSOC);
$total_hojeF = number_format(($res['total'] ?? 0), 2, ',', '.');

$query = $pdo->query("SELECT SUM(subtotal) as total FROM $tabela $query_base AND pago = 'Sim' $periodo_filtro");
$res = $query->fetch(PDO::FETCH_ASSOC);
$total_recebidasF = number_format(($res['total'] ?? 0), 2, ',', '.');

$query = $pdo->query("SELECT SUM(valor) as total FROM $tabela $query_base $periodo_filtro");
$res = $query->fetch(PDO::FETCH_ASSOC);
$total_totalF = number_format(($res['total'] ?? 0), 2, ',', '.');

$data_amanha = date('Y-m-d', strtotime("+1 days", strtotime($data_hoje)));

if ($filtro == 'Vencidas') {
	$query = $pdo->query("SELECT * from $tabela where $tipo_data < curDate() and pago = 'Não' $sql_usuario_lanc $sql_atacadista $sql_pgto $sql_funcionario order by id desc ");
} else if ($filtro == 'Recebidas' || $filtro == 'Pagas') {
	$query = $pdo->query("SELECT * from $tabela where pago = 'Sim' $sql_usuario_lanc $sql_atacadista $sql_pgto $sql_funcionario order by id desc ");
} else if ($filtro == 'Hoje') {
	$query = $pdo->query("SELECT * from $tabela where $tipo_data = curDate() and pago = 'Não' $sql_usuario_lanc $sql_atacadista $sql_pgto $sql_funcionario order by id desc ");
} else if ($filtro == 'Amanha') {
	$query = $pdo->query("SELECT * from $tabela where $tipo_data = '$data_amanha' and pago = 'Não' $sql_usuario_lanc $sql_atacadista $sql_pgto $sql_funcionario order by id desc ");
} else if ($filtro == 'Todas') {
	$query = $pdo->query("SELECT * from $tabela " . ($mostrar_registros == 'Não' ? "where usuario_lanc = '$id_usuario' " : "where 1=1 ") . "$sql_atacadista $sql_pgto $sql_funcionario order by id desc ");
} else {
	$query = $pdo->query("SELECT * from $tabela WHERE $tipo_data >= '$dataInicial' and vencimento <= '$dataFinal' $sql_usuario_lanc $sql_atacadista $sql_pgto $sql_funcionario order by id desc ");
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
    <th class="">Valor</th> 
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
		$funcionario = $res[$i]['funcionario'];
		$valor = $res[$i]['valor'];
		$vencimento = $res[$i]['vencimento'];
		$data_pgto = $res[$i]['data_pgto'];
		$forma_pgto = $res[$i]['forma_pgto'];
		$frequencia = $res[$i]['frequencia'];
		$obs = $res[$i]['obs'];
		$arquivo = $res[$i]['arquivo'];
		$id_ref = $res[$i]['id_ref'];
		$subtotal = $res[$i]['subtotal'];
		$usuario_lanc = $res[$i]['usuario_lanc'];
		$usuario_pgto = $res[$i]['usuario_pgto'];
		$pago = $res[$i]['pago'];

		$vencimentoF = implode('/', array_reverse(explode('-', $vencimento)));
		$data_pgtoF = ($data_pgto && $data_pgto != '0000-00-00') ? implode('/', array_reverse(explode('-', $data_pgto))) : '---';
		$valor_finalF = number_format(($pago == "Sim" ? $subtotal : $valor), 2, ',', '.');

		$ext = pathinfo($arquivo, PATHINFO_EXTENSION);
		$tumb_arquivo = (in_array(strtolower($ext), ['pdf', 'rar', 'zip', 'doc', 'docx', 'xlsx', 'xlsm', 'xls', 'xml'])) ? strtolower($ext) . '.png' : $arquivo;
		if (in_array(strtolower($ext), ['doc', 'docx'])) $tumb_arquivo = 'word.png';
		if (in_array(strtolower($ext), ['xlsx', 'xlsm', 'xls'])) $tumb_arquivo = 'excel.png';

		$query2 = $pdo->query("SELECT nome FROM usuarios where id = '$usuario_lanc'");
		$nome_usu_lanc = $query2->fetchColumn() ?: 'Sem Usuário';

		$query2 = $pdo->query("SELECT nome FROM usuarios where id = '$usuario_pgto'");
		$nome_usu_pgto = $query2->fetchColumn() ?: 'Sem Usuário';

		$query2 = $pdo->query("SELECT frequencia FROM frequencias where dias = '$frequencia'");
		$nome_frequencia = $query2->fetchColumn() ?: 'Sem Registro';

		$query2 = $pdo->query("SELECT nome, taxa FROM formas_pgto where id = '$forma_pgto'");
		$dados_pgto = $query2->fetch(PDO::FETCH_ASSOC);
		$nome_pgto = $dados_pgto['nome'] ?? 'Sem Registro';
		$taxa_pgto = $dados_pgto['taxa'] ?? 0;

		$classe_pago = ($pago == 'Sim') ? 'verde' : 'text-danger';
		$ocultar = ($pago == 'Sim') ? 'ocultar' : '';
		$classe_venc = (strtotime($vencimento) < strtotime($data_hoje) && $pago != 'Sim') ? 'text-danger' : '';

		$nome_pessoa = 'Sem Registro';
		$tipo_pessoa = 'Pessoa';
		if ($fornecedor != 0) {
			$nome_pessoa = $pdo->query("SELECT nome_atacadista FROM fornecedores where id = '$fornecedor'")->fetchColumn();
			$tipo_pessoa = 'Fornecedor';
		} elseif ($funcionario != 0) {
			$nome_pessoa = $pdo->query("SELECT nome FROM funcionarios where id = '$funcionario'")->fetchColumn();
			$tipo_pessoa = 'Funcionário';
		}

		echo <<<HTML
<tr>
<td align="center">
<div class="custom-checkbox custom-control">
<input type="checkbox" class="custom-control-input" id="seletor-{$id}" onchange="selecionar('{$id}')">
<label for="seletor-{$id}" class="custom-control-label mt-1 text-dark"></label>
</div>
</td>
<td><i class="fa fa-square {$classe_pago} mr-1"></i> {$descricao}</td>
<td>R$ {$valor_finalF}</td>   
<td class="esc">{$nome_pessoa}</td>
<td class="esc {$classe_venc}">{$vencimentoF}</td>
<td class="esc">{$data_pgtoF}</td>
<td class="esc"><a href="images/contas/{$arquivo}" target="_blank"><img src="images/contas/{$tumb_arquivo}" width="25px"></a></td>
<td>
    <big><a href="#" onclick="editar('{$id}','{$descricao}','{$valor}','{$fornecedor}','{$funcionario}','{$vencimento}','{$data_pgto}','{$forma_pgto}','{$frequencia}','{$obs}','{$tumb_arquivo}')" title="Editar Dados"><i class="fa fa-edit text-primary"></i></a></big>
    <div class="dropdown" style="display: inline-block;">                      
        <a href="#" data-bs-toggle="dropdown"><i class="fa fa-trash text-danger"></i> </a>
        <div class="dropdown-menu tx-13"><div class="dropdown-item-text"><p>Excluir? <a href="#" onclick="excluir('{$id}')"><span class="text-danger">Sim</span></a></p></div></div>
    </div>
    <big><a href="#" onclick="mostrar('{$descricao}','{$valor_finalF}','{$nome_pessoa}','{$vencimentoF}','{$data_pgtoF}','{$nome_pgto}','{$nome_frequencia}','{$obs}','{$tumb_arquivo}','','','','','','{$nome_usu_lanc}','{$nome_usu_pgto}', '{$pago}', '{$arquivo}', '', '', '{$tipo_pessoa}')" title="Mostrar Dados"><i class="fa fa-info-circle text-primary"></i></a></big>
    <big><a class="{$ocultar}" href="#" onclick="baixar('{$id}', '{$valor}', '{$descricao}', '{$forma_pgto}', '', '', '')" title="Baixar Conta"><i class="fa fa-check-square " style="color:#079934"></i></a></big>
    <big><a class="{$ocultar}" href="#" onclick="parcelar('{$id}', '{$valor}', '{$descricao}')" title="Parcelar Conta"><i class="fa fa-calendar-o " style="color:#7f7f7f"></i></a></big>
HTML;
		if (!empty($id_ref)) {
			echo "<big><a href='#' onclick='imprimir(\"{$id_ref}\")' title='Visualizar Romaneio'><i class='fa fa-file-pdf-o text-info'></i></a></big>";
		}
		echo "</td></tr>";
	}
	echo "</tbody></table></small>";
} else {
	echo 'Nenhum Registro Encontrado!';
}
?>

<script type="text/javascript">
	$(document).ready(function() {
		$('#tabela').DataTable({
			"ordering": false,
			"stateSave": true
		});

		$('#total_vencidas').text('R$ <?= $total_vencidasF ?>');
		$('#total_a_vencer').text('R$ <?= $total_hojeF ?>');
		$('#total_pagas').text('R$ <?= $total_recebidasF ?>');
		$('#total_total').text('R$ <?= $total_totalF ?>');
	});

	function listar(p1, p2, p3, p4, p5, p6, p7, p8) {
		$.ajax({
			url: 'paginas/' + pag + "/listar.php",
			method: 'POST',
			data: {
				p1,
				p2,
				p3,
				p4,
				p5,
				p6,
				p7,
				p8
			},
			dataType: "html",
			success: function(result) {
				$("#listar").html(result);
			}
		});
	}

	function editar(id, descricao, valor, fornecedor, funcionario, vencimento, data_pgto, forma_pgto, frequencia, obs, arquivo) {
		$('#mensagem').text('');
		$('#titulo_inserir').text('Editar Registro');
		$('#id').val(id);
		$('#descricao').val(descricao);
		$('#valor').val(valor);
		$('#fornecedor').val(fornecedor).change();
		$('#funcionario').val(funcionario).change();
		$('#vencimento').val(vencimento);
		$('#data_pgto').val(data_pgto);
		$('#forma_pgto').val(forma_pgto).change();
		$('#frequencia').val(frequencia).change();
		$('#obs').val(obs);
		$('#target').attr('src', 'images/contas/' + arquivo);
		$('#modalForm').modal('show');
	}

	function mostrar(descricao, valor, pessoa, vencimento, data_pgto, nome_pgto, frequencia, obs, arquivo, multa, juros, desconto, taxa, total, usu_lanc, usu_pgto, pago, arq, telefone, pix, tipo_pessoa) {
		$('#titulo_dados').text(descricao);
		$('#valor_dados').text(valor);
		$('#cliente_dados').text(pessoa);
		$('#vencimento_dados').text(vencimento);
		$('#data_pgto_dados').text(data_pgto || 'Pendente');
		$('#nome_pgto_dados').text(nome_pgto);
		$('#frequencia_dados').text(frequencia);
		$('#obs_dados').text(obs);
		$('#usu_lanc_dados').text(usu_lanc);
		$('#usu_pgto_dados').text(usu_pgto);
		$('#pago_dados').text(pago);
		$('#target_dados').attr("src", "images/contas/" + arquivo);
		$('#modalDados').modal('show');
	}

	function baixar(id, valor, descricao, pgto, taxa, multa, juros) {
		$('#id-baixar').val(id);
		$('#descricao-baixar').text(descricao);
		$('#valor-baixar').val(valor);
		$('#saida-baixar').val(pgto).change();
		$('#subtotal').val(valor);
		$('#valor-juros').val(juros);
		$('#valor-multa').val(multa);
		$('#valor-taxa').val(taxa);
		$('#modalBaixar').modal('show');
	}

	function parcelar(id, valor, nome) {
		$('#id-parcelar').val(id);
		$('#valor-parcelar').val(valor);
		$('#qtd-parcelar').val('');
		$('#nome-parcelar').text(nome);
		$('#nome-input-parcelar').val(nome);
		$('#modalParcelar').modal('show');
	}

	function excluir(id) {
		$.ajax({
			url: 'paginas/' + pag + "/excluir.php",
			method: 'POST',
			data: {
				id
			},
			success: function(mensagem) {
				if (mensagem.trim() == "Excluído com Sucesso") {
					buscar();
				} else {
					$('#mensagem-excluir').addClass('text-danger').text(mensagem);
				}
			}
		});
	}

	function selecionar(id) {
		var ids = $('#ids').val();
		if ($('#seletor-' + id).is(":checked")) {
			$('#ids').val(ids + id + '-');
		} else {
			$('#ids').val(ids.replace(id + '-', ''));
		}
		var ids_final = $('#ids').val();
		if (ids_final == "") {
			$('#btn-deletar, #btn-baixar').hide();
		} else {
			$('#btn-deletar, #btn-baixar').show();
		}
	}

	function imprimir(id) {
		window.open('rel/gerar_pdf_romaneio_compra.php?id=' + id, '_blank');
	}

	function buscar() {
		var filtro = $('#tipo_data_filtro').val();
		var dataInicial = $('#dataInicial').val();
		var dataFinal = $('#dataFinal').val();
		var tipo_data = $('#tipo_data').val();
		var atacadista = $('#atacadista').val();
		var formaPGTO = $('#formaPGTO').val();
		var funcionario = $('#funcionario_filtro').val();
		listar(filtro, dataInicial, dataFinal, tipo_data, atacadista, formaPGTO, funcionario);
	}
</script>