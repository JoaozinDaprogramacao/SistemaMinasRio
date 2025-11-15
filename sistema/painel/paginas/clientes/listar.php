<?php
require_once("../../verificar.php");
@session_start();
$mostrar_registros = @$_SESSION['registros'];
$id_usuario = @$_SESSION['id'];

$tabela = 'clientes';
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
    <th>Cliente</th> <th class="esc">Plano PGTO</th> 
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
		$nome = addslashes($res[$i]['nome']);
		$razao_social = addslashes($res[$i]['razao_social']);
		$cnpj = $res[$i]['cnpj'];
		$ie = $res[$i]['ie'];
		$cpf = $res[$i]['cpf'];
		$rg = $res[$i]['rg'];
		$rua = addslashes($res[$i]['endereco']);
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


		$tipo_pessoa = addslashes($res[$i]['tipo_pessoa']);
		$data_cad    = $res[$i]['data_cad'];    // do banco
		$data_nasc   = $res[$i]['data_nasc'];   // do banco

		// Formata as datas para “DD/MM/AAAA”
		$data_cadF  = $data_cad  ? implode('/', array_reverse(explode('-', $data_cad)))  : '';
		$data_nascF = $data_nasc ? implode('/', array_reverse(explode('-', $data_nasc))) : '';

		$email = addslashes($res[$i]['email']);

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
    '{$id}', '{$nome}', '{$data_nasc}', '{$razao_social}', '{$cnpj}', '{$ie}', '{$cpf}', 
    '{$rg}', '{$rua}', '{$numero}', '{$bairro}', '{$cidade}', '{$cep}', '{$uf}', '{$complemento}', 
    '{$contato}', '{$contato2}', '{$site}', '{$plano_pgto_value}', '{$prazo_pgto_value}', '{$forma_pagamento_value}', '{$email}'
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
       '{$nome}',
       '{$tipo_pessoa}',
       '{$cpf}',
       '{$razao_social}',
       '{$ie}',
       '{$rg}',
       '{$data_nascF}',
       '{$data_cadF}',
       '{$contato}',
       '{$contato2}',
       '{$email}',
       '{$site}',
       '{$rua}',
       '{$numero}',
       '{$complemento}',
       '{$bairro}',
       '{$cidade}',
       '{$uf}',
       '{$cep}',
       '{$plano_pgto}',
       '{$forma_pagamento}',
       '{$prazo_pgto}'
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
	// <-- ALTERAÇÃO 4.1: Adicionado 'contato2' nos parâmetros
	function editar(id, nome, data_nascF, razao_social, cnpj, ie, cpf, rg, rua, numero, bairro, cidade, cep, uf, complemento, contato, contato2, site, plano_pgto, prazo_pgto, forma_pgto, email) {

		$('#mensagem').text('');
		$('#titulo_inserir').text('Editar Registro');

		// console.log("Nome: " + nome);
		// console.log("data: " + data_nascF);

		$('#id').val(id);
		$('#nome_cliente').val(nome);
		$('#data_nasc').val(data_nascF);
		$('#razao_social').val(razao_social); // <-- CORREÇÃO: Estava data_nascF
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
		$('#contato2').val(contato2); // <-- ALTERAÇÃO 4.2: Preencher campo
		$('#site').val(site);
		$('#plano_pagamento').val(plano_pgto);
		$('#prazo_pagamento').val(prazo_pgto);
		$('#forma_pagamento').val(forma_pgto);
		$('#email').val(email);
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
		} else {
			console.log("Pessoa Física selecionada");
			fisicaFields.classList.remove("d-none");
			cnpjFields.classList.add("d-none");
			cnpjTitle.classList.add("d-none");
		}
	}



	// <-- ALTERAÇÃO 4.3: Adicionado 'contato2' nos parâmetros
	function mostrar(
		nome,
		pessoa,
		cpf,
		razao_social,
		ie,
		rg,
		data_nasc,
		data_cad,
		contato,
		contato2,
		email,
		site,
		endereco,
		numero,
		complemento,
		bairro,
		cidade,
		uf,
		cep,
		plano,
		forma,
		prazo
	) {
		$('#titulo_dados').text(nome);
		$('#nome_dados').text(nome);
		$('#pessoa_dados').text(pessoa);
		$('#cpf_dados').text(cpf);
		$('#razao_social_dados').text(razao_social);
		$('#ie_dados').text(ie);
		$('#rg_dados').text(rg);
		$('#data_nasc_dados').text(data_nasc);
		$('#data_cad_dados').text(data_cad);
		$('#contato_dados').text(contato);
		$('#contato2_dados').text(contato2); // <-- ALTERAÇÃO 4.4: Preencher
		$('#email_dados').text(email);
		$('#site_dados').text(site);
		$('#endereco_dados').text(endereco);
		$('#numero_dados').text(numero);
		$('#complemento_dados').text(complemento);
		$('#bairro_dados').text(bairro);
		$('#cidade_dados').text(cidade);
		$('#uf_dados').text(uf);
		$('#cep_dados').text(cep);
		$('#plano_pagamento_dados').text(plano);
		$('#forma_pagamento_dados').text(forma);
		$('#prazo_pagamento_dados').text(prazo);

		$('#modalDados').modal('show');
	}

	function limparCampos() {
		// Campos ocultos e básicos
		$('#id').val('');
		$('#ids').val('');
		$('#btn-deletar').hide();
		$('#mensagem').text('');

		// Radios de tipo de pessoa — volta para Pessoa Física
		$('#radio_pessoa_fisica').prop('checked', true);
		$('#radio_cnpj').prop('checked', false); // <-- CORREÇÃO: Estava 'radio_juridica'

		// Dados Gerais
		$('#nome_cliente').val('');
		$('#email').val('');
		$('#contato').val('');
		$('#contato2').val(''); // <-- ALTERAÇÃO 4.5: Limpar campo

		// Endereço
		$('#cep').val('');
		$('#rua').val('');
		$('#numero').val('');
		$('#bairro').val('');
		$('#cidade').val('');
		$('#uf').val('');
		$('#complemento').val('');

		// Pessoa Física
		$('#data_nasc').val('');
		$('#cpf').val('');
		$('#rg').val('');

		// Pessoa Jurídica
		$('#razao_social').val('');
		$('#cnpj').val('');
		$('#ie').val('');

		// Planos e prazos
		$('#plano_pagamento').val('0').trigger('change');
		$('#forma_pagamento').val('0').trigger('change');
		$('#prazo_pagamento').val('');

		// Site (não obrigatório, mas limpamos)
		$('#site').val('');

		// Reajusta visibilidade de campos conforme tipo de pessoa
		// <-- CORREÇÃO: Estava 'atualizarVisibilidadeClientes'
		if (typeof atualizarVisibilidade === 'function') {
			atualizarVisibilidade();
		}
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