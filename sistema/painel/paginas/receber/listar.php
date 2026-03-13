<?php
@session_start();
$mostrar_registros = @$_SESSION['registros'];
$id_usuario = @$_SESSION['id'];
$tabela = 'receber';
require_once("../../../conexao.php");
require_once("../../verificar.php");

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

// Query Principal da Listagem
if ($filtro == 'Vencidas') {
	$query = $pdo->query("SELECT * from $tabela where vencimento < curDate() and pago = 'Não' and $tipo_data >= '$dataInicial' and $tipo_data <= '$dataFinal' $sql_usuario_lanc $sql_atacadista $sql_pgto $sql_tipo_conta order by id desc ");
} else if ($filtro == 'Recebidas') {
	$query = $pdo->query("SELECT * from $tabela where pago = 'Sim' and $tipo_data >= '$dataInicial' and $tipo_data <= '$dataFinal' $sql_usuario_lanc $sql_atacadista $sql_pgto $sql_tipo_conta order by id desc ");
} else if ($filtro == 'AVencer') {
	$query = $pdo->query("SELECT * from $tabela where vencimento >= curDate() and pago = 'Não' $sql_usuario_lanc $sql_atacadista $sql_pgto $sql_tipo_conta order by vencimento asc ");
} else {
	$query = $pdo->query("SELECT * from $tabela WHERE $tipo_data >= '$dataInicial' and $tipo_data <= '$dataFinal' $sql_usuario_lanc $sql_atacadista $sql_pgto $sql_tipo_conta order by id desc ");
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
<td><i class="fa fa-square {$classe_pago} mr-1"></i> {$descricao}</td>
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
	$(document).ready(function() {
		$('#tabela').DataTable({
			"ordering": false,
			"stateSave": true
		});
		$('#total_itens').text('R$ <?= $total_totalF ?>');
		$('#total_vencidas').text('R$ <?= $total_vencidasF ?>');
		$('#total_a_vencer').text('R$ <?= $total_a_vencerF ?>');
		$('#total_recebidas').text('R$ <?= $total_recebidasF ?>');
	});
</script>
<script>
	function imprimir(id) {
		window.open('rel/gerar_pdf_romaneio.php?id=' + id, '_blank');
	}
</script>

<script type="text/javascript">
	$(document).ready(function() {
		// O parâmetro destroy permite que a tabela seja recriada a cada busca/filtro
		$('#tabela').DataTable({
			"destroy": true,
			"ordering": false,
			"stateSave": true,
			"language": {
				// "url" : '//cdn.datatables.net/plug-ins/1.13.2/i18n/pt-BR.json'
			}
		});

		// Atualização dos cards de totais no painel principal
		$('#total_itens').text('R$ <?= $total_totalF ?>');
		$('#total_vencidas').text('R$ <?= $total_vencidasF ?>');
		$('#total_a_vencer').text('R$ <?= $total_a_vencerF ?>');
		$('#total_recebidas').text('R$ <?= $total_recebidasF ?>');
	});
</script>


<script type="text/javascript">
	function editar(id, descricao, valor, cliente, vencimento, data_pgto, forma_pgto, frequencia, obs, arquivo) {
		console.log("Log1: entrou");
		$('#mensagem').text('');
		$('#titulo_inserir').text('Editar Registro');

		$('#id').val(id);
		$('#descricao').val(descricao);
		$('#valor').val(valor);
		$('#cliente').val(cliente).change();
		$('#vencimento').val(vencimento);
		$('#data_pgto').val(data_pgto);
		$('#forma_pgto').val(forma_pgto).change();
		$('#frequencia').val(frequencia).change();
		$('#obs').val(obs);
		console.log("Log2: continuou");

		$('#arquivo').val('');
		$('#target').attr('src', 'images/contas/' + arquivo);

		$('#modalForm').modal('show');
		console.log("Log3: finalizou");
	}


	function mostrar(descricao, valor, cliente, vencimento, data_pgto, nome_pgto, frequencia, obs, arquivo, multa, juros, desconto, taxa, total, usu_lanc, usu_pgto, pago, arq) {

		if (data_pgto == "") {
			data_pgto = 'Pendente';
		}

		$('#titulo_dados').text(descricao);
		$('#valor_dados').text(valor);
		$('#cliente_dados').text(cliente);
		$('#vencimento_dados').text(vencimento);
		$('#data_pgto_dados').text(data_pgto);
		$('#nome_pgto_dados').text(nome_pgto);
		$('#frequencia_dados').text(frequencia);
		$('#obs_dados').text(obs);

		$('#multa_dados').text(multa);
		$('#juros_dados').text(juros);
		$('#desconto_dados').text(desconto);
		$('#taxa_dados').text(taxa);
		$('#total_dados').text(total);
		$('#usu_lanc_dados').text(usu_lanc);
		$('#usu_pgto_dados').text(usu_pgto);

		$('#pago_dados').text(pago);
		$('#target_dados').attr("src", "images/contas/" + arquivo);
		$('#target_link_dados').attr("href", "images/contas/" + arq);

		$('#modalDados').modal('show');
	}

	function limparCampos() {
		$('#id').val('');
		$('#descricao').val('');
		$('#valor').val('');
		$('#vencimento').val("<?= $data_atual ?>");
		$('#data_pgto').val('');
		$('#obs').val('');
		$('#arquivo').val('');

		$('#target').attr("src", "images/contas/sem-foto.png");

		$('#ids').val('');
		$('#btn-deletar').hide();
		$('#btn-baixar').hide();
	}

	function selecionar(id) {

		var ids = $('#ids').val();

		if ($('#seletor-' + id).is(":checked") == true) {
			var novo_id = ids + id + '-';
			$('#ids').val(novo_id);
		} else {
			var retirar = ids.replace(id + '-', '');
			$('#ids').val(retirar);
		}

		var ids_final = $('#ids').val();
		if (ids_final == "") {
			$('#btn-deletar').hide();
			$('#btn-baixar').hide();
		} else {
			$('#btn-deletar').show();
			$('#btn-baixar').show();
		}
	}

	function deletarSel() {
		var ids = $('#ids').val();
		var id = ids.split("-");

		for (i = 0; i < id.length - 1; i++) {
			excluirMultiplos(id[i]);
		}

		setTimeout(() => {
			listar();
		}, 1000);

		limparCampos();
	}


	function deletarSelBaixar() {
		var ids = $('#ids').val();
		var id = ids.split("-");

		for (i = 0; i < id.length - 1; i++) {
			var novo_id = id[i];
			$.ajax({
				url: 'paginas/' + pag + "/baixar_multiplas.php",
				method: 'POST',
				data: {
					novo_id
				},
				dataType: "html",

				success: function(result) {
					//alert(result)

				}
			});
		}

		setTimeout(() => {
			buscar();
			limparCampos();
		}, 1000);


	}


	function permissoes(id, nome) {

		$('#id_permissoes').val(id);
		$('#nome_permissoes').text(nome);

		$('#modalPermissoes').modal('show');
		listarPermissoes(id);
	}


	function parcelar(id, valor, nome) {
		$('#id-parcelar').val(id);
		$('#valor-parcelar').val(valor);
		$('#qtd-parcelar').val('');
		$('#nome-parcelar').text(nome);
		$('#nome-input-parcelar').val(nome);
		$('#modalParcelar').modal('show');
		$('#mensagem-parcelar').text('');
	}


	function baixar(id, valor, descricao, pgto, taxa, multa, juros) {
		$('#id-baixar').val(id);
		$('#descricao-baixar').text(descricao);
		$('#valor-baixar').val(valor);
		$('#saida-baixar').val(pgto).change();
		$('#subtotal').val(valor);


		$('#valor-juros').val(juros);
		$('#valor-desconto').val('');
		$('#valor-multa').val(multa);
		$('#valor-taxa').val(taxa);

		totalizar()

		$('#modalBaixar').modal('show');
		$('#mensagem-baixar').text('');
	}


	function mostrarResiduos(id) {

		$.ajax({
			url: 'paginas/' + pag + "/listar-residuos.php",
			method: 'POST',
			data: {
				id
			},
			dataType: "html",

			success: function(result) {
				$("#listar-residuos").html(result);
			}
		});
		$('#modalResiduos').modal('show');


	}

	function arquivo(id, nome) {
		$('#id-arquivo').val(id);
		$('#nome-arquivo').text(nome);
		$('#modalArquivos').modal('show');
		$('#mensagem-arquivo').text('');
		$('#arquivo_conta').val('');
		listarArquivos();
	}


	function cobrar(id) {
		$.ajax({
			url: 'paginas/' + pag + "/cobrar.php",
			method: 'POST',
			data: {
				id
			},
			dataType: "html",

			success: function(result) {
				alert(result);
			}
		});
	}
</script>