<?php
@session_start();
$mostrar_registros = @$_SESSION['registros'];
$id_usuario = @$_SESSION['id'];
$tabela = 'receber';
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
$data_inicio_ano = $ano_atual . "-01-01";

$data_ontem = date('Y-m-d', @strtotime("-1 days", @strtotime($data_atual)));
$data_amanha = date('Y-m-d', @strtotime("+1 days", @strtotime($data_atual)));


if ($mes_atual == '04' || $mes_atual == '06' || $mes_atual == '09' || $mes_atual == '11') {
	$data_final_mes = $ano_atual . '-' . $mes_atual . '-30';
} else if ($mes_atual == '02') {
	$bissexto = date('L', @mktime(0, 0, 0, 1, 1, $ano_atual));
	if ($bissexto == 1) {
		$data_final_mes = $ano_atual . '-' . $mes_atual . '-29';
	} else {
		$data_final_mes = $ano_atual . '-' . $mes_atual . '-28';
	}
} else {
	$data_final_mes = $ano_atual . '-' . $mes_atual . '-31';
}

$total_pago = 0;
$total_pendentes = 0;

$total_pagoF = 0;
$total_pendentesF = 0;

$dataInicial = @$_POST['p2'];
$dataFinal = @$_POST['p3'];
$filtro = @$_POST['p1'];
$tipo_data = @$_POST['p4'];
$atacadista = @$_POST['p5'];
$forma_pgto = @$_POST['p6'];

if ($tipo_data == "") {
	$tipo_data = 'vencimento';
}

if ($dataInicial == "") {
	$dataInicial = $data_inicio_mes;
}

if ($dataFinal == "") {
	$dataFinal = $data_final_mes;
}

$sql_atacadista = "";
if (!empty($atacadista)) {
	$sql_atacadista = " AND cliente = '$atacadista'";
}

$sql_pgto = "";
if (!empty($forma_pgto)) {
	$sql_pgto = " AND forma_pgto = '$forma_pgto'";
}

// Inicialização de variáveis
$total_valor = 0;
$total_valorF = "0,00";
$total_total = 0;
$total_totalF = "0,00";
$total_vencidas = 0;
$total_vencidasF = "0,00";
$total_hoje = 0;
$total_hojeF = "0,00";
$total_amanha = 0;
$total_amanhaF = "0,00";
$total_recebidas = 0;
$total_rece_pendentes = 0; // Para o rodapé da tabela
$total_recebidasF = "0,00";

// 1. TOTAL PENDENTES GERAL (respeitando atacadista)
$query = $pdo->query("SELECT SUM(valor) as total from $tabela where pago = 'Não' $sql_usuario_lanc $sql_atacadista $sql_pgto");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_valor = $res[0]['total'] ?? 0;
$total_valorF = @number_format($total_valor, 2, ',', '.');

// 2. TOTAL GERAL (Tudo: Pago e Não Pago)
$query = $pdo->query("SELECT SUM(valor) as total from $tabela where 1=1 $sql_usuario_lanc $sql_atacadista $sql_pgto");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_total = $res[0]['total'] ?? 0;
$total_totalF = @number_format($total_total, 2, ',', '.');

// 3. TOTAL VENCIDAS
$query = $pdo->query("SELECT SUM(valor) as total from $tabela where vencimento < curDate() and pago = 'Não' $sql_usuario_lanc $sql_atacadista $sql_pgto");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_vencidas = $res[0]['total'] ?? 0;
$total_vencidasF = @number_format($total_vencidas, 2, ',', '.');

// 4. TOTAL HOJE
$query = $pdo->query("SELECT SUM(valor) as total from $tabela where vencimento = curDate() and pago = 'Não' $sql_usuario_lanc $sql_atacadista $sql_pgto");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_hoje = $res[0]['total'] ?? 0;
$total_hojeF = @number_format($total_hoje, 2, ',', '.');

// 5. TOTAL RECEBIDAS
$query = $pdo->query("SELECT SUM(subtotal) as total from $tabela where pago = 'Sim' $sql_usuario_lanc $sql_atacadista $sql_pgto");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_recebidas = $res[0]['total'] ?? 0;
$total_recebidasF = @number_format($total_recebidas, 2, ',', '.');

// 6. TOTAL AMANHÃ
$data_amanha = date('Y-m-d', strtotime("+1 days"));
$query = $pdo->query("SELECT SUM(valor) as total from $tabela where vencimento = '$data_amanha' and pago = 'Não' $sql_usuario_lanc $sql_atacadista $sql_pgto");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_amanha = $res[0]['total'] ?? 0;
$total_amanhaF = @number_format($total_amanha, 2, ',', '.');

if ($filtro == 'Vencidas') {
	$query = $pdo->query("SELECT * from $tabela where $tipo_data < curDate() and pago = 'Não' $sql_usuario_lanc $sql_atacadista $sql_pgto order by id desc ");
} else if ($filtro == 'Recebidas') {
	$query = $pdo->query("SELECT * from $tabela where pago = 'Sim' $sql_usuario_lanc $sql_atacadista $sql_pgto order by id desc ");
} else if ($filtro == 'Hoje') {
	$query = $pdo->query("SELECT * from $tabela where $tipo_data = curDate() and pago = 'Não' $sql_usuario_lanc $sql_atacadista $sql_pgto order by id desc ");
} else if ($filtro == 'Amanha') {
	$query = $pdo->query("SELECT * from $tabela where $tipo_data = '$data_amanha' and pago = 'Não' $sql_usuario_lanc $sql_atacadista $sql_pgto order by id desc ");
} else if ($filtro == 'Todas') {
	if ($mostrar_registros == 'Não') {
		$query = $pdo->query("SELECT * from $tabela where usuario_lanc = '$id_usuario' $sql_atacadista $sql_pgto order by id desc ");
	} else {
		$query = $pdo->query("SELECT * from $tabela $sql_atacadista $sql_pgto order by id desc ");
	}
} else {
	$query = $pdo->query("SELECT * from $tabela WHERE $tipo_data >= '$dataInicial' and vencimento <= '$dataFinal' $sql_usuario_lanc $sql_atacadista $sql_pgto order by id desc ");
}


$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);
if ($linhas > 0) {
	echo <<<HTML
<small>
	<table class="table table-bordered text-nowrap border-bottom dt-responsive " id="tabela">
	<thead> 
	<tr> 
	<th align="center" width="5%" class="text-center">Selecionar</th>
	<th>Descrição</th>	
	<th class="">Valor</th>	
	<th class="esc">Cliente</th>	
	<th class="esc">Vencimento</th>	
	<th class="esc">Pagamento</th>		
	<th class="esc">Arquivo</th>	
	<th>Ações</th>
	</tr> 
	</thead> 
	<tbody>	
	<small>
HTML;


	for ($i = 0; $i < $linhas; $i++) {
		$id = $res[$i]['id'];
		$descricao = $res[$i]['descricao'];
		$cliente = $res[$i]['cliente'];
		$valor = $res[$i]['valor'];
		$vencimento = $res[$i]['vencimento'];
		$data_pgto = $res[$i]['data_pgto'];
		$data_lanc = $res[$i]['data_lanc'];
		$forma_pgto = $res[$i]['forma_pgto'];
		$frequencia = $res[$i]['frequencia'];
		$obs = $res[$i]['obs'];
		$arquivo = $res[$i]['arquivo'];
		$referencia = $res[$i]['referencia'];
		$id_ref = $res[$i]['id_ref'];
		$multa = $res[$i]['multa'];
		$juros = $res[$i]['juros'];
		$desconto = $res[$i]['desconto'];
		$taxa = $res[$i]['taxa'];
		$subtotal = $res[$i]['subtotal'];
		$usuario_lanc = $res[$i]['usuario_lanc'];
		$usuario_pgto = $res[$i]['usuario_pgto'];
		$pago = $res[$i]['pago'];

		$vencimentoF = implode('/', array_reverse(@explode('-', $vencimento)));
		$data_pgtoF = implode('/', array_reverse(@explode('-', $data_pgto)));
		$data_lancF = implode('/', array_reverse(@explode('-', $data_lanc)));



		$valorF = @number_format($valor, 2, ',', '.');
		$multaF = @number_format($multa, 2, ',', '.');
		$jurosF = @number_format($juros, 2, ',', '.');
		$descontoF = @number_format($desconto, 2, ',', '.');
		$taxaF = @number_format($taxa, 2, ',', '.');
		$subtotalF = @number_format($subtotal, 2, ',', '.');

		if ($pago == "Sim") {
			$valor_finalF = @number_format($subtotal, 2, ',', '.');
		} else {
			$valor_finalF = @number_format($valor, 2, ',', '.');
		}



		//extensão do arquivo
		$ext = pathinfo($arquivo, PATHINFO_EXTENSION);
		if ($ext == 'pdf' || $ext == 'PDF') {
			$tumb_arquivo = 'pdf.png';
		} else if ($ext == 'rar' || $ext == 'zip' || $ext == 'RAR' || $ext == 'ZIP') {
			$tumb_arquivo = 'rar.png';
		} else if ($ext == 'doc' || $ext == 'docx' || $ext == 'DOC' || $ext == 'DOCX') {
			$tumb_arquivo = 'word.png';
		} else if ($ext == 'xlsx' || $ext == 'xlsm' || $ext == 'xls') {
			$tumb_arquivo = 'excel.png';
		} else if ($ext == 'xml') {
			$tumb_arquivo = 'xml.png';
		} else {
			$tumb_arquivo = $arquivo;
		}



		$query2 = $pdo->query("SELECT * FROM usuarios where id = '$usuario_lanc'");
		$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
		if (@count($res2) > 0) {
			$nome_usu_lanc = $res2[0]['nome'];
		} else {
			$nome_usu_lanc = 'Sem Usuário';
		}


		$query2 = $pdo->query("SELECT * FROM usuarios where id = '$usuario_pgto'");
		$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
		if (@count($res2) > 0) {
			$nome_usu_pgto = $res2[0]['nome'];
		} else {
			$nome_usu_pgto = 'Sem Usuário';
		}


		$query2 = $pdo->query("SELECT * FROM frequencias where dias = '$frequencia'");
		$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
		if (@count($res2) > 0) {
			$nome_frequencia = $res2[0]['frequencia'];
		} else {
			$nome_frequencia = 'Sem Registro';
		}

		$query2 = $pdo->query("SELECT * FROM formas_pgto where id = '$forma_pgto'");
		$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
		if (@count($res2) > 0) {
			$nome_pgto = $res2[0]['nome'];
			$taxa_pgto = $res2[0]['taxa'];
		} else {
			$nome_pgto = 'Sem Registro';
			$taxa_pgto = 0;
		}


		$query2 = $pdo->query("SELECT * FROM clientes where id = '$cliente'");
		$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
		if (@count($res2) > 0) {
			$nome_cliente = $res2[0]['nome'];
		} else {
			$nome_cliente = 'Sem Registro';
		}


		if ($pago == 'Sim') {
			$classe_pago = 'verde';
			$ocultar = 'ocultar';
			$ocultar_pendentes = '';
			$total_pago += $subtotal;
		} else {
			$classe_pago = 'text-danger';
			$ocultar_pendentes = 'ocultar';
			$ocultar = '';
			$total_pendentes += $valor;
		}

		$valor_multa = 0;
		$valor_juros = 0;
		$classe_venc = '';
		if (@strtotime($vencimento) < @strtotime($data_hoje)) {
			$classe_venc = 'text-danger';
			$valor_multa = $multa_atraso;

			//pegar a quantidade de dias que o pagamento está atrasado
			$dif = @strtotime($data_hoje) - @strtotime($vencimento);
			$dias_vencidos = floor($dif / (60 * 60 * 24));

			$valor_juros = ($valor * $juros_atraso / 100) * $dias_vencidos;
		}

		$total_pendentesF = @number_format($total_pendentes, 2, ',', '.');
		$total_pagoF = @number_format($total_pago, 2, ',', '.');

		$taxa_conta = $taxa_pgto * $valor / 100;




		//PEGAR RESIDUOS DA CONTA
		$total_resid = 0;
		$valor_com_residuos = 0;
		$query2 = $pdo->query("SELECT * FROM receber WHERE id_ref = '$id' and residuo = 'Sim'");
		$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
		if (@count($res2) > 0) {

			$descricao = '(Resíduo) - ' . $descricao;

			for ($i2 = 0; $i2 < @count($res2); $i2++) {
				foreach ($res2[$i2] as $key => $value) {
				}
				$id_res = $res2[$i2]['id'];
				$valor_resid = $res2[$i2]['valor'];
				$total_resid += $valor_resid - $res2[$i2]['desconto'];
			}


			$valor_com_residuos = $valor + $total_resid;
		}
		if ($valor_com_residuos > 0) {
			$vlr_antigo_conta = '(' . $valor_com_residuos . ')';
			$descricao_link = '';
			$descricao_texto = 'd-none';
		} else {
			$vlr_antigo_conta = '';
			$descricao_link = 'd-none';
			$descricao_texto = '';
		}


		if ($api_whatsapp != 'Não' and $cliente != "" and $cliente != "0") {
			$ocultar_cobranca = '';
		} else {
			$ocultar_cobranca = 'ocultar';
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
<td class="">R$ {$valor_finalF} <small><a href="#" onclick="mostrarResiduos('{$id}')" class="text-danger" title="Ver Resíduos">{$vlr_antigo_conta}</a></small></td>	
<td class="esc">{$nome_cliente}</td>
<td class="esc {$classe_venc}">{$vencimentoF}</td>
<td class="esc">{$data_pgtoF}</td>

<td class="esc"><a href="images/contas/{$arquivo}" target="_blank"><img src="images/contas/{$tumb_arquivo}" width="25px"></a></td>
<td>
	<big><a class="icones_mobile" href="#" onclick="editar('{$id}','{$descricao}','{$valor}','{$cliente}','{$vencimento}','{$data_pgto}','{$forma_pgto}','{$frequencia}','{$obs}','{$tumb_arquivo}')" title="Editar Dados"><i class="fa fa-edit text-primary"></i></a></big>

	<div class="icones_mobile" class="dropdown" style="display: inline-block;">                      
                        <a href="#" aria-expanded="false" aria-haspopup="true" data-bs-toggle="dropdown" class="dropdown"><i class="fa fa-trash text-danger"></i> </a>
                        <div  class="dropdown-menu tx-13">
                        <div class="dropdown-item-text botao_excluir">
                        <p>Confirmar Exclusão? <a href="#" onclick="excluir('{$id}')"><span class="text-danger">Sim</span></a></p>
                        </div>
                        </div>
                        </div>

<big><a class="icones_mobile" href="#" onclick="mostrar('{$descricao}','{$valorF}','{$nome_cliente}','{$vencimentoF}','{$data_pgtoF}','{$nome_pgto}','{$nome_frequencia}','{$obs}','{$tumb_arquivo}','{$multaF}','{$jurosF}','{$descontoF}','{$taxaF}','{$subtotalF}','{$nome_usu_lanc}','{$nome_usu_pgto}', '{$pago}', '{$arquivo}')" title="Mostrar Dados"><i class="fa fa-info-circle text-primary"></i></a></big>

<big><a class="{$ocultar} icones_mobile" href="#" onclick="baixar('{$id}', '{$valor}', '{$descricao}', '{$forma_pgto}', '{$taxa_conta}', '{$valor_multa}', '{$valor_juros}')" title="Baixar Conta"><i class="fa fa-check-square " style="color:#079934"></i></a></big>

	<big><a  class="{$ocultar} icones_mobile" href="#" onclick="parcelar('{$id}', '{$valor}', '{$descricao}')" title="Parcelar Conta"><i class="fa fa-calendar-o " style="color:#7f7f7f"></i></a></big>

		<big><a class="icones_mobile" href="#" onclick="arquivo('{$id}', '{$descricao}')" title="Inserir / Ver Arquivos"><i class="fa fa-file-o text-primary" ></i></a></big>

		<big><a class="{$ocultar} {$ocultar_cobranca} icones_mobile" href="#" onclick="cobrar('{$id}')" title="Gerar Cobrança"><i class="fa fa-whatsapp " style="color:green"></i></a></big>

			<form   method="POST" action="rel/recibo_conta_class.php" target="_blank" style="display:inline-block">
					<input type="hidden" name="id" value="{$id}">
					<big><button class="{$ocultar_pendentes} icones_mobile" title="PDF do Recibo Conta" style="background:transparent; border:none; margin:0; padding:0"><i class="fa fa-file-pdf-o " style="color:green"></i></button></big>
					</form>


					<form   method="POST" action="rel/imp_recibo.php" target="_blank" style="display:inline-block">
					<input type="hidden" name="id" value="{$id}">
					<big><button class="{$ocultar_pendentes} icones_mobile" title="Imprimir Recibo 80mmm" style="background:transparent; border:none; margin:0; padding:0"><i class="fa fa-print " style="color:#666464"></i></button></big>
					</form>
HTML;
		// Bloco 2: Lógica condicional para o novo botão de impressão
		// Este botão só aparecerá se $id_ref não for vazio
		if (!empty($id_ref)) {
			echo <<<HTML
        <big><a class="icones_mobile" href="#" onclick="imprimir('{$id_ref}')" title="Visualizar Romaneio"><i class="fa fa-file-pdf-o text-info"></i></a></big>
    HTML;
		}

		// Bloco 3: Fechamento da coluna e da linha
		echo <<<HTML
    </td>
</tr>
HTML;
	}


	echo <<<HTML
</small>
</tbody>
<small><div align="center" id="mensagem-excluir"></div></small>

</table>
</small>
<br>

			<span class="ocultar_mobile" style="font-size: 13px; border:1px solid #6092a8; padding:5px; ">
				Filtrar Por:  
				<a href="#" onclick="tipoData('vencimento')">Vencimento</a> / 
				<a href="#" onclick="tipoData('data_pgto')">Pagamento</a> /
				<a href="#" onclick="tipoData('data_lanc')">Lançamento</a> 
			</span>

			<p align="right" style="margin-top: -10px">
				<span style="margin-right: 10px">Total Pendentes  <span style="color:red">R$ {$total_pendentesF} </span></span>
				<span>Total Pago  <span style="color:green">R$ {$total_pagoF} </span></span>
			</p>

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

<script type="text/javascript">
	$(document).ready(function() {
		$('#tabela').DataTable({
			"language": {
				//"url" : '//cdn.datatables.net/plug-ins/1.13.2/i18n/pt-BR.json'
			},
			"ordering": false,
			"stateSave": true
		});


		$('#total_itens').text('R$ <?= $total_valorF ?>');
		$('#total_total').text('R$ <?= $total_totalF ?>');
		$('#total_vencidas').text('R$ <?= $total_vencidasF ?>');
		$('#total_hoje').text('R$ <?= $total_hojeF ?>');
		$('#total_amanha').text('R$ <?= $total_amanhaF ?>');
		$('#total_recebidas').text('R$ <?= $total_recebidasF ?>');
	});
</script>


<script type="text/javascript">
	function editar(id, descricao, valor, cliente, vencimento, data_pgto, forma_pgto, frequencia, obs, arquivo) {
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

		$('#arquivo').val('');
		$('#target').attr('src', 'images/contas/' + arquivo);

		$('#modalForm').modal('show');
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