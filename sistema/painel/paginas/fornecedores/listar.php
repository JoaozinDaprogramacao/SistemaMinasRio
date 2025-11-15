<?php
require_once("../../verificar.php");
@session_start();
$mostrar_registros = @$_SESSION['registros'];
$id_usuario = @$_SESSION['id'];

$tabela = 'fornecedores';
require_once("../../../conexao.php");


if ($mostrar_registros == 'Não') {
	$query = $pdo->query("SELECT * from $tabela where usuario = '$id_usuario' order by id desc");
} else {
	$query = $pdo->query("SELECT * from $tabela order by id desc");
}

$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);
if ($linhas > 0) {
	echo <<<HTML

    <table class="table table-bordered text-nowrap border-bottom dt-responsive" id="tabela">
    <thead> 
    <tr> 
    <th align="center" width="5%" class="text-center">Selecionar</th>
    <th>Atacadista</th> 
    <th class="esc">Plano PGTO</th> 
    <th class="esc">Prazo PGTO</th>
    <th class="esc">Forma de Recebimento</th>
    <th>E-mail</th>
    <th>Ações</th>
    </tr> 
    </thead> 
    <tbody> 
HTML;

	for ($i = 0; $i < $linhas; $i++) {
		$id = $res[$i]['id'];
		$nome = addslashes($res[$i]['nome_atacadista']);
		$razao_social = addslashes($res[$i]['razao_social']);
		$cnpj = $res[$i]['cnpj'];
		$ie = $res[$i]['ie'];
		$cpf = $res[$i]['cpf'];
		$rg = $res[$i]['rg'];
		$rua = addslashes($res[$i]['rua']);
		$numero = $res[$i]['numero'];
		$bairro = addslashes($res[$i]['bairro']);
		$cidade = addslashes($res[$i]['cidade']);
		$cep = $res[$i]['cep'];
		$uf = $res[$i]['uf'];
		$complemento = addslashes($res[$i]['complemento']);
		$contato = addslashes($res[$i]['contato']);
		$contato2 = addslashes($res[$i]['contato2']); // <-- ALTERAÇÃO 1: Buscar contato2
		$site = addslashes($res[$i]['site']);

		$plano_pgto = $res[$i]['plano_pagamento'];
		$plano_pgto_value = $plano_pgto;

		$prazo_pgto = $res[$i]['prazo_pagamento'];
		$prazo_pgto_value = $prazo_pgto;

		$forma_pagamento = $res[$i]['forma_pagamento'];
		$forma_pagamento_value = $forma_pagamento;

		$email = addslashes($res[$i]['email']);

		$tipo_pessoa = addslashes($res[$i]['tipo_pessoa']);
		$tipo_fornecedor = addslashes($res[$i]['tipo_fornecedor']);
		$data_cad    = $res[$i]['data_cadastro'];  // do banco

		// Formata as datas para “DD/MM/AAAA”
		$data_cadF  = $data_cad  ? implode('/', array_reverse(explode('-', $data_cad)))  : '';

		// Fazendo a consulta para obter o nome do plano de pagamento com base no ID
		$query_plano_recebimento = $pdo->query("SELECT nome FROM planos_pgto WHERE id = '$plano_pgto'");

		// Recuperando o nome do plano de pagamento
		$plano_pgto = $query_plano_recebimento->fetch(PDO::FETCH_ASSOC);

		// Verificando se o resultado foi encontrado e atribuindo o nome do plano
		if ($plano_pgto) {
			$plano_pgto = $plano_pgto['nome'];
		} else {
			$plano_pgto = 'Plano não encontrado';
		}

		// Fazendo a consulta para obter o nome do plano de pagamento com base no ID
		$query_forma_pagamento = $pdo->query("SELECT nome FROM formas_pgto WHERE id = '$forma_pagamento'");

		// Recuperando o nome do plano de pagamento
		$forma_pagamento = $query_forma_pagamento->fetch(PDO::FETCH_ASSOC);

		// Verificando se o resultado foi encontrado e atribuindo o nome do plano
		if ($forma_pagamento) {
			$forma_pagamento = $forma_pagamento['nome'];
		} else {
			$forma_pagamento = 'Plano não encontrado';
		}



		echo <<<HTML
<tr>
<td align="center">
<div class="custom-checkbox custom-control">
<input type="checkbox" class="custom-control-input" id="seletor-{$id}" onchange="selecionar('{$id}')">
<label for="seletor-{$id}" class="custom-control-label mt-1 text-dark"></label>
</div>
</td>
<td>{$nome}</td>
<td class="esc">{$plano_pgto}</td>
<td class="esc">{$prazo_pgto} dias</td>
<td class="esc">{$forma_pagamento}</td>
<td class="esc">{$email}</td>
<td>
    <a class="btn btn-info btn-sm" href="#"  title="Editar Dados" onclick="editar(
    '{$id}', '{$nome}', '{$razao_social}', '{$cnpj}', '{$ie}', '{$cpf}', 
    '{$rg}', '{$rua}', '{$numero}', '{$bairro}', '{$cidade}', '{$cep}', '{$uf}', '{$complemento}', 
    '{$contato}', '{$contato2}', '{$site}', '{$plano_pgto_value}', '{$prazo_pgto_value}', '{$forma_pagamento_value}', '{$email}',
    '{$tipo_fornecedor}'
)"><i class="fa fa-edit "></i></a>

    <div class="dropdown" style="display: inline-block;"> 
        <a class="btn btn-danger btn-sm" href="#" aria-expanded="false" aria-haspopup="true" data-bs-toggle="dropdown" class="dropdown"><i class="fa fa-trash "></i> </a>
        <div  class="dropdown-menu tx-13">
            <div class="dropdown-item-text botao_excluir">
            <p>Confirmar Exclusão? <a href="#" onclick="excluir('{$id}')"><span class="text-danger">Sim</span></a></p>
            </div>
        </div>
    </div>
    <a class="btn btn-primary btn-sm" href="#"
    onclick="mostrar(
       '{$tipo_pessoa}',
       '{$tipo_fornecedor}',
       '{$nome}',
       '{$razao_social}',
       '{$cnpj}',
       '{$ie}',
       '{$cpf}',
       '{$rg}',
       '{$rua}',
       '{$numero}',
       '{$complemento}',
       '{$bairro}',
       '{$cidade}',
       '{$uf}',
       '{$cep}',
       '{$contato}',
       '{$contato2}', // <-- ALTERAÇÃO 3: Passar contato2 para 'mostrar'
       '{$email}',
       '{$site}',
       '{$plano_pgto}',
       '{$forma_pagamento}',
       '{$prazo_pgto}',
       '{$data_cadF}'
    )"
    title="Mostrar Dados">
   <i class="fa fa-info-circle"></i>
  </a>


</td>
</tr>
HTML;
	}
} else {
	echo 'Não possui nenhum cadastro!';
}


echo <<<HTML
</tbody>
<small><div align="center" id="mensagem-excluir"></div></small>
</table>
HTML;
?>



<script type="text/javascript">
	$(document).ready(function() {
		$('#tabela').DataTable({
			"language": {
				//"url" : '//cdn.datatables.net/plug-ins/1.13.2/i18n/pt-BR.json'
			},
			"ordering": false,
			"stateSave": true
		});
	});
</script>
<script type="text/javascript">
	// <-- ALTERAÇÃO 4.1: Adicionar 'contato2' na assinatura da função
	function editar(id, nome, razao_social, cnpj, ie, cpf, rg, rua, numero, bairro, cidade, cep, uf, complemento, contato, contato2, site, plano_pgto, prazo_pgto, forma_pgto, email, tipo_fornecedor) {

		$('#mensagem').text('');
		$('#titulo_inserir').text('Editar Registro');

		$('#id').val(id);
		$('#nome_atacadista').val(nome);
		$('#razao_social').val(razao_social);
		$('#cnpj').val(cnpj);
		$('#ie').val(ie);
		$('#cpf').val(cpf);
		$('#rg').val(rg);
		$('#rua').val(rua);
		$('#numero').val(numero);
		$('#bairro').val(bairro);
		$('#cidade').val(cidade);
		$('#cep').val(cep);
		$('#uf').val(uf);
		$('#complemento').val(complemento);
		$('#contato').val(contato);
		$('#contato2').val(contato2); // <-- ALTERAÇÃO 4.2: Preencher o campo contato2
		$('#site').val(site);
		$('#plano_pagamento').val(plano_pgto);
		$('#prazo_pagamento').val(prazo_pgto);
		$('#forma_pagamento').val(forma_pgto);
		$('#email').val(email);

		// Lógica para marcar o Tipo de Fornecedor
		if (tipo_fornecedor == 'diversos') {
			$('#radio_diversos').prop('checked', true);
			$('#radio_produtor').prop('checked', false);
		} else {
			$('#radio_produtor').prop('checked', true);
			$('#radio_diversos').prop('checked', false);
		}

		// Lógica para marcar o Tipo de Pessoa (já existente)
		if (cpf != "") {
			// Caso CPF esteja preenchido, seleciona "Pessoa Física" e dispara o evento
			$('#radio_pessoa_fisica').prop('checked', true).trigger('change');
			$('#radio_cnpj').prop('checked', false); // Desmarca o outro rádio
		} else {
			// Caso CPF esteja vazio, seleciona "CNPJ" e dispara o evento
			$('#radio_pessoa_fisica').prop('checked', false); // Desmarca o outro rádio
			$('#radio_cnpj').prop('checked', true).trigger('change');
		}
		atualizarVisibilidade();


		$('#modalForm').modal('show');
	}


	function atualizarVisibilidade() {

		const pessoaFisicaRadio = document.getElementById("radio_pessoa_fisica");
		const cnpjRadio = document.getElementById("radio_cnpj");
		const fisicaFields = document.getElementById("fisica_fields");
		const cnpjFields = document.getElementById("cnpj_fields");
		const cnpjTitle = document.getElementById("cnpj_title");


		if (cnpjRadio.checked) {
			fisicaFields.classList.add("d-none");
			cnpjFields.classList.remove("d-none");
			cnpjTitle.classList.remove("d-none");
			limparCamposFisica();
		} else {
			console.log("Pessoa Física selecionada");
			fisicaFields.classList.remove("d-none");
			cnpjFields.classList.add("d-none");
			cnpjTitle.classList.add("d-none");
			limparCamposJuridica();
		}
	}



	function limparCamposFisica() {
		document.getElementById("cpf").value = "";
		document.getElementById("rg").value = "";
	}

	function limparCamposJuridica() {
		document.getElementById("cnpj").value = "";
		document.getElementById("ie").value = "";
		document.getElementById("razao_social").value = "";
	}


	// <-- ALTERAÇÃO 4.3: Adicionar 'contato2' na assinatura da função
	function mostrar(
		tipoPessoa,
		tipoFornecedor,
		nomeAtacadista,
		razaoSocial,
		cnpj,
		ie,
		cpf,
		rg,
		rua,
		numero,
		complemento,
		bairro,
		cidade,
		uf,
		cep,
		contato,
		contato2, // <-- Adicionado
		email,
		site,
		planoPagamento,
		formaPagamento,
		prazoPagamento,
		dataCadastro
	) {

		// Formata os valores para exibição
		var tipoPessoaFormatado = (tipoPessoa == 'cnpj') ? 'CNPJ' : 'Pessoa Física';
		var tipoFornFormatado = (tipoFornecedor == 'diversos') ? 'Diversos' : 'Produtor';


		$('#titulo_dados').text(nomeAtacadista);
		$('#tipo_pessoa_dados').text(tipoPessoaFormatado);
		$('#tipo_fornecedor_dados').text(tipoFornFormatado); // <-- ADICIONADO
		$('#nome_atacadista_dados').text(nomeAtacadista);
		$('#razao_social_dados').text(razaoSocial);
		$('#cnpj_dados').text(cnpj);
		$('#ie_dados').text(ie);
		$('#cpf_dados').text(cpf);
		$('#rg_dados').text(rg);
		$('#rua_dados').text(rua);
		$('#numero_dados').text(numero);
		$('#complemento_dados').text(complemento);
		$('#bairro_dados').text(bairro);
		$('#cidade_dados').text(cidade);
		$('#uf_dados').text(uf);
		$('#cep_dados').text(cep);
		$('#contato_dados').text(contato);
		$('#contato2_dados').text(contato2); // <-- ALTERAÇÃO 4.4: Preencher o campo no modal 'mostrar'
		$('#email_dados').text(email);
		$('#site_dados').text(site);
		$('#plano_pagamento_dados').text(planoPagamento);
		$('#forma_pagamento_dados').text(formaPagamento);
		$('#prazo_pagamento_dados').text(prazoPagamento + ' dias');
		$('#data_cadastro_dados').text(dataCadastro);

		$('#modalDados').modal('show');
	}

	function limparCampos() {

		console.log('entrou limparCampos');
		// Campos ocultos e básicos
		$('#id').val('');
		$('#ids').val('');
		$('#mensagem').text('');
		$('#btn-deletar').hide();

		// Radios de tipo de pessoa — volta para Pessoa Física
		$('#radio_pessoa_fisica').prop('checked', true);
		$('#radio_cnpj').prop('checked', false);

		$('#radio_produtor').prop('checked', true);
		$('#radio_diversos').prop('checked', false);

		// Dados Gerais / Jurídicos / Pessoais
		$('#nome_atacadista').val('');
		$('#razao_social').val('');
		$('#cnpj').val('');
		$('#ie').val('');
		$('#cpf').val('');
		$('#rg').val('');

		// Endereço
		$('#cep').val('');
		$('#rua').val('');
		$('#numero').val('');
		$('#bairro').val('');
		$('#cidade').val('');
		$('#uf').val('');
		$('#complemento').val('');

		// Contato e site
		$('#contato').val('');
		$('#contato2').val(''); // <-- ALTERAÇÃO 4.5: Limpar o campo contato2
		$('#email').val('');
		$('#site').val('');

		// Planos e prazos
		$('#plano_pagamento').val('0').trigger('change');
		$('#forma_pagamento').val('0').trigger('change');
		$('#prazo_pagamento').val('');

		// Reajusta visibilidade de campos conforme tipo de pessoa
		atualizarVisibilidade();
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
		} else {
			$('#btn-deletar').show();
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
</script>