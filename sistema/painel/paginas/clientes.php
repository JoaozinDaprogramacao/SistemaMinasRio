<?php 
require_once("verificar.php");
$pag = 'clientes';

//verificar se ele tem a permissão de estar nessa página
if(@$clientes == 'ocultar'){
	echo "<script>window.location='index'</script>";
	exit();
}
?>



<div class="justify-content-between">
	<div class="left-content mt-2 mb-3">
		<a class="btn ripple btn-primary text-white" onclick="inserir()" type="button"><i class="fe fe-plus me-2"></i> Adicionar <?php echo ucfirst($pag); ?></a>

		<select class="form-select" name="inad" id="inad" onchange="$('#ina').val($('#inad').val()); buscar()" style="width:150px; display:inline-block; margin-left: 20px">
			<option value="">Todos</option>
			<option value="ina">Inadimplentes</option>
		</select>
		<input type="hidden" name="ina" id="ina">



		<div class="dropdown" style="display: inline-block;">                      
			<a href="#" aria-expanded="false" aria-haspopup="true" data-bs-toggle="dropdown" class="btn btn-danger dropdown" id="btn-deletar" style="display:none"><i class="fe fe-trash-2"></i> Deletar</a>
			<div  class="dropdown-menu tx-13">
				<div style="width: 240px; padding:15px 5px 0 10px;" class="dropdown-item-text">
					<p>Excluir Selecionados? <a href="#" onclick="deletarSel()"><span class="text-danger">Sim</span></a></p>
				</div>
			</div>
		</div>
		<a style="position:absolute; right:40px;" href="rel/excel_clientes.php" type="button" class="btn btn-success ocultar_mobile_app" target="_blank"><span class="fa fa-file-excel-o"></span> Exportar</a>

	</div>

</div>


<div class="row row-sm">
	<div class="col-lg-12">
		<div class="card custom-card">
			<div class="card-body" id="listar">

			</div>
		</div>
	</div>
</div>


<input type="hidden" id="ids">

<script>
	// Função para aplicar a máscara do CNPJ
	function mascara_cnpj(valor) {
		var valorAlterado = $('#' + valor).val();
		valorAlterado = valorAlterado.replace(/\D/g, ""); // Remove todos os não dígitos
		valorAlterado = valorAlterado.replace(/^(\d{2})(\d)/, "$1.$2"); // Adiciona o primeiro ponto
		valorAlterado = valorAlterado.replace(/^(\d{2})\.(\d{3})(\d)/, "$1.$2.$3"); // Adiciona o segundo ponto
		valorAlterado = valorAlterado.replace(/(\d{3})(\d)/, "$1/$2"); // Adiciona a barra
		valorAlterado = valorAlterado.replace(/(\d{4})(\d{1,2})$/, "$1-$2"); // Adiciona o traço antes dos dois últimos dígitos
		$('#' + valor).val(valorAlterado);
	}

	// Função para aplicar a máscara do CPF
	function mascara_cpf(valor) {
		var valorAlterado = $('#' + valor).val();
		valorAlterado = valorAlterado.replace(/\D/g, ""); // Remove todos os não dígitos
		valorAlterado = valorAlterado.replace(/(\d{3})(\d)/, "$1.$2"); // Adiciona o primeiro ponto
		valorAlterado = valorAlterado.replace(/(\d{3})(\d)/, "$1.$2"); // Adiciona o segundo ponto
		valorAlterado = valorAlterado.replace(/(\d{3})(\d{1,2})$/, "$1-$2"); // Adiciona o traço antes dos dois últimos dígitos
		$('#' + valor).val(valorAlterado);
	}

	// Função para aplicar a máscara do CNPJ
	function mascara_cnpj(valor) {
		var valorAlterado = $('#' + valor).val();
		valorAlterado = valorAlterado.replace(/\D/g, ""); // Remove todos os não dígitos
		valorAlterado = valorAlterado.replace(/^(\d{2})(\d)/, "$1.$2"); // Adiciona o primeiro ponto
		valorAlterado = valorAlterado.replace(/^(\d{2})\.(\d{3})(\d)/, "$1.$2.$3"); // Adiciona o segundo ponto
		valorAlterado = valorAlterado.replace(/(\d{3})(\d)/, "$1/$2"); // Adiciona a barra
		valorAlterado = valorAlterado.replace(/(\d{4})(\d{1,2})$/, "$1-$2"); // Adiciona o traço antes dos dois últimos dígitos
		$('#' + valor).val(valorAlterado);
	}

	// Função para aplicar a máscara da IE
	function mascara_ie(id) {
		// pega só dígitos e limita a 9
		let v = $('#' + id).val().replace(/\D/g, '').substring(0, 9);

		// 1) insere ponto depois dos 2 primeiros
		v = v.replace(/^(\d{2})(\d+)/, '$1.$2');
		// 2) insere traço antes do dígito verificador (último)
		v = v.replace(/^(\d{2}\.\d{6})(\d)$/, '$1-$2');

		$('#' + id).val(v);
	}

	// Função para aplicar a máscara do RG
	function mascara_rg(valor) {
		var valorAlterado = $('#' + valor).val();
		valorAlterado = valorAlterado.replace(/\D/g, ""); // Remove todos os não dígitos
		valorAlterado = valorAlterado.replace(/(\d{2})(\d)/, "$1.$2"); // Adiciona o primeiro ponto
		valorAlterado = valorAlterado.replace(/(\d{3})(\d)/, "$1.$2"); // Adiciona o segundo ponto
		valorAlterado = valorAlterado.replace(/(\d{3})(\d{1})$/, "$1-$2"); // Adiciona o traço antes do último dígito
		$('#' + valor).val(valorAlterado);
	}

	function mascara_cep(valor) {
		var valorAlterado = $('#' + valor).val();
		valorAlterado = valorAlterado.replace(/\D/g, ""); // Remove todos os não dígitos
		valorAlterado = valorAlterado.substring(0, 8); // Limita a entrada a 8 dígitos
		valorAlterado = valorAlterado.replace(/(\d{5})(\d)/, "$1-$2"); // Formata como XXXXX-XXX
		$('#' + valor).val(valorAlterado);
	}

	function mascara_celular(valor) {
		var valorAlterado = $('#' + valor).val();
		valorAlterado = valorAlterado.replace(/\D/g, ""); // Remove todos os não dígitos
		valorAlterado = valorAlterado.substring(0, 11); // Limita a entrada a 11 dígitos
		valorAlterado = valorAlterado.replace(/(\d{2})(\d)/, "($1) $2"); // Adiciona parênteses no DDD
		valorAlterado = valorAlterado.replace(/(\d{5})(\d)/, "$1-$2"); // Adiciona o traço no número
		$('#' + valor).val(valorAlterado);
	}

	function mascara_moeda(valor) {
		var valorAlterado = $('#' + valor).val();
		valorAlterado = valorAlterado.replace(/\D/g, ""); // Remove todos os não dígitos
		valorAlterado = valorAlterado.replace(/(\d+)(\d{2})$/, "$1,$2"); // Adiciona a parte de centavos
		valorAlterado = valorAlterado.replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1."); // Adiciona pontos a cada três dígitos
		valorAlterado = valorAlterado;
		$('#' + valor).val(valorAlterado);
	}



	function buscarCep() {
		const cep = $('#cep').val().replace(/\D/g, ''); // Remove caracteres não numéricos do CEP

		if (cep.length === 8) { // Verifica se o CEP tem 8 dígitos
			const url = `https://viacep.com.br/ws/${cep}/json/`;

			fetch(url)
				.then(response => {
					if (!response.ok) throw new Error('CEP não encontrado');
					return response.json();
				})
				.then(data => {
					if (data.erro) {
						alert('CEP inválido ou não encontrado!');
						return;
					}

					// Preenche os campos automaticamente
					$('#rua').val(data.logradouro);
					$('#bairro').val(data.bairro);
					$('#cidade').val(data.localidade);
					$('#uf').val(data.uf);
				})
				.catch(error => {
					alert('Erro ao buscar o CEP: ' + error.message);
				});
		} else {
			alert('Por favor, insira um CEP válido!');
		}
	}
</script>

<style>
	.input-wrapper {
		position: relative;
		display: inline-block;
		width: 200px;
		/* Defina a largura desejada para o input */
	}

	.input-wrapper input {
		width: 100%;
		padding-right: 50px;
		/* Espaço para o texto "7 dias" */
		box-sizing: border-box;
		/* Garante que padding não aumente o tamanho total */
		height: 40px;
		/* Altura do input */
		font-size: 16px;
	}

	.input-wrapper .input-suffix {
		position: absolute;
		right: 30px;
		top: 50%;
		height: fit-content;
		font-size: 16px;
		color: #999;
		/* Cor do texto para diferenciar do input */
		pointer-events: none;
		/* Garante que o texto não interfira no clique do input */
	}
</style>

<!-- Modal Perfil -->

<div class="modal fade" id="modalForm" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header bg-primary text-white">
				<h4 class="modal-title" id="exampleModalLabel"><span id="titulo_inserir"></span></h4>
				<button id="btn-fechar" aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"><span class="text-white" aria-hidden="true">&times;</span></button>
			</div>
			<form id="form">
				<div class="modal-body">

					<!-- Tipo de Pessoa -->
					<h5 class="mb-3">Tipo de Pessoa</h5>
					<div class="row mb-3">
						<div class="col-md-12">
							<label><input type="radio" name="tipo_pessoa" id="radio_pessoa_fisica" value="fisica" checked> Pessoa Física</label>
							<label class="ms-3"><input type="radio" name="tipo_pessoa" id="radio_cnpj" value="cnpj"> Pessoa Jurídica</label>
						</div>
					</div>
					<hr>

					<!-- Dados Gerais do Cliente -->
					<h5 class="mb-3">Dados Gerais</h5>
					<div class="row">
						<div class="col-md-6 mb-2">
							<label>Nome Completo</label>
							<input type="text" class="form-control" id="nome_cliente" name="nome_cliente" placeholder="Nome do Cliente" required>
						</div>
						<div class="col-md-6 mb-2">
							<label>Data de Nascimento</label>
							<input type="date" class="form-control" id="data_nasc" name="data_nasc">
						</div>
					</div>

					<!-- O restante do modal pode seguir a estrutura de fornecedores: endereço, contato, documentos, genitor/genitora -->
					<!-- Ajuste IDs e names de input conforme o banco de dados e backend -->

					<!-- Campos de contato, endereço, documentos, etc. -->

						<!-- Título: Endereço -->
						<h5 class="mb-3">Endereço</h5>
					<div class="row">
						<div class="col-md-4 mb-2">
							<label>CEP</label>
							<input type="text" class="form-control" id="cep" name="cep" placeholder="CEP" onkeyup="mascara_cep('cep')" onblur="buscarCep()">
						</div>
						<div class="col-md-6 mb-2">
							<label>Rua</label>
							<input type="text" class="form-control" id="rua" name="rua" placeholder="Rua">
						</div>
						<div class="col-md-2 mb-2">
							<label>Número</label>
							<input type="text" class="form-control" id="numero" name="numero" placeholder="Número">
						</div>
					</div>
					<div class="row">
						<div class="col-md-4 mb-2">
							<label>Bairro</label>
							<input type="text" class="form-control" id="bairro" name="bairro" placeholder="Bairro">
						</div>
						<div class="col-md-6 mb-2">
							<label>Cidade</label>
							<input type="text" class="form-control" id="cidade" name="cidade" placeholder="Cidade">
						</div>
						<div class="col-md-2 mb-2">
							<label>UF</label>
							<input type="text" class="form-control" id="uf" name="uf" placeholder="UF" maxlength="2">
						</div>
					</div>
					<div class="row">
						<div class="col-md-12 mb-2">
							<label>Complemento</label>
							<input type="text" class="form-control" id="complemento" name="complemento" placeholder="Complemento">
						</div>
					</div>

					<hr>

					<!-- Título: Dados Jurídicos -->
					<h5 class="mb-3 d-none" id="cnpj_title">Dados Jurídicos</h5>
					<div class="row d-none" id="cnpj_fields">
						<div class="col-md-6 mb-2">
							<label>Razão Social</label>
							<input type="text" class="form-control" id="razao_social" name="razao_social" placeholder="Razão Social">
						</div>
						<div class="col-md-6 mb-2">
							<label>CNPJ</label>
							<input type="text" class="form-control" id="cnpj" name="cnpj" placeholder="CNPJ do Fornecedor" onkeyup="mascara_cnpj('cnpj')" maxlength="14">
						</div>
						<div class="col-md-6 mb-2">
							<label>IE</label>
							<input type="text" class="form-control" id="ie" name="ie" placeholder="IE do Fornecedor" onkeyup="mascara_ie('ie')" maxlength="11">
						</div>
					</div>

					<!-- Título: Dados Pessoais -->

					<div class="row" id="fisica_fields">
						<h5 class="mb-3 w-100">Dados Pessoais</h5>
						<div class="col-md-6 mb-2">
							<label>CPF</label>
							<input type="text" class="form-control" id="cpf" name="cpf" placeholder="CPF do Fornecedor" onkeyup="mascara_cpf('cpf')" maxlength="11">
						</div>
						<div class="col-md-6 mb-2">
							<label>RG</label>
							<input type="text" class="form-control" id="rg" name="rg" placeholder="RG do Fornecedor" onkeyup="mascara_rg('rg')" maxlength="10">
						</div>
					</div>

					<div class="row" id="fisica_fields">
						<h5 class="mb-3 w-100">Dados de Contato</h5>
						<div class="col-md-6 mb-2">
							<label>Telefone/Celular</label>
							<input type="text" class="form-control" id="contato" name="contato" placeholder="xx xxxxxxxxx" onkeyup="mascara_celular('contato')" maxlength="15">
						</div>
						<div class="col-md-6 mb-2">
							<label>E-mail</label>
							<input type="email" class="form-control" id="email" name="email" placeholder="E-mail do fornecedor" maxlength="64">
						</div>
						<div class="col-md-6 mb-2">
							<label>Site</label>
							<input type="text" class="form-control" id="site" name="site" placeholder="Site do fornecedor">
						</div>
						<div class="col-md-4 mb-2 col-6">
							<label>Plano de Pagamento</label>
							<select class="sel2 form-control" name="plano_pagamento" id="plano_pagamento" style="width:100%">
								<option value="0">Escolher Plano</option>
								<?php
								$query = $pdo->query("SELECT * from planos_pgto order by id asc");
								$res = $query->fetchAll(PDO::FETCH_ASSOC);
								$linhas = @count($res);
								if ($linhas > 0) {
									for ($i = 0; $i < $linhas; $i++) { ?>
										<option value="<?php echo $res[$i]['id'] ?>"><?php echo $res[$i]['nome'] ?></option>
								<?php }
								} ?>
							</select>
						</div>
						<div class="col-md-4 mb-2 col-6">
							<label>Forma de Pagamento</label>
							<select class="sel2 form-control" name="forma_pagamento" id="forma_pagamento" style="width:100%">
								<option value="0">Escolher Forma</option>
								<?php
								$query = $pdo->query("SELECT * from formas_pgto order by id asc");
								$res = $query->fetchAll(PDO::FETCH_ASSOC);
								$linhas = @count($res);
								if ($linhas > 0) {
									for ($i = 0; $i < $linhas; $i++) { ?>
										<option value="<?php echo $res[$i]['id'] ?>"><?php echo $res[$i]['nome'] ?></option>
								<?php }
								} ?>
							</select>
						</div>
						<div class="col-md-4 mb-2 input-wrapper">
							<label>Prazo Pagamento</label>
							<input type="number" class="form-control" id="prazo_pagamento" name="prazo_pagamento" placeholder="Prazo PGTO">
							<span class="input-suffix">dias</span>
						</div>
					</div>

					<input type="hidden" class="form-control" id="id" name="id">
					<br>
					<small>
						<div id="mensagem" align="center"></div>
					</small>
				</div>
				<div class="modal-footer">
					<button type="submit" id="btn_salvar" class="btn btn-primary">Salvar</button>
				</div>

				</div>
			</form>

			<script>
				document.addEventListener("DOMContentLoaded", function() {
					const pessoaFisicaRadio = document.getElementById("radio_pessoa_fisica");
					const cnpjRadio = document.getElementById("radio_cnpj");
					const fisicaFields = document.getElementById("fisica_fields");
					const cnpjFields = document.getElementById("cnpj_fields");
					const cnpjTitle = document.getElementById("cnpj_title");

					function atualizarVisibilidade() {
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


					// Atualiza ao carregar a página
					atualizarVisibilidade();

					// Adiciona os eventos de clique nos rádios
					pessoaFisicaRadio.addEventListener("change", atualizarVisibilidade);
					cnpjRadio.addEventListener("change", atualizarVisibilidade);
				});
			</script>
		</div>
	</div>
</div>

</div>







<!-- Modal Dados -->
<!-- Modal Dados Atualizado -->
<div class="modal fade" id="modalDados" tabindex="-1" aria-labelledby="modalDadosLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h4 class="modal-title" id="modalDadosLabel"><span id="titulo_dados"></span></h4>
        <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table class="table table-bordered text-left">
            <tr>
              <td class="bg-warning">Nome</td>
              <td><span id="nome_dados"></span></td>
            </tr>
            <tr>
              <td class="bg-warning">Pessoa</td>
              <td><span id="pessoa_dados"></span></td>
            </tr>
            <tr>
              <td class="bg-warning">CPF / CNPJ</td>
              <td><span id="cpf_dados"></span></td>
            </tr>
            <tr>
              <td class="bg-warning">Razão Social</td>
              <td><span id="razao_social_dados"></span></td>
            </tr>
            <tr>
              <td class="bg-warning">Inscrição Estadual (IE)</td>
              <td><span id="ie_dados"></span></td>
            </tr>
            <tr>
              <td class="bg-warning">RG</td>
              <td><span id="rg_dados"></span></td>
            </tr>
            <tr>
              <td class="bg-warning">Data de Nascimento</td>
              <td><span id="data_nasc_dados"></span></td>
            </tr>
            <tr>
              <td class="bg-warning">Data de Cadastro</td>
              <td><span id="data_cad_dados"></span></td>
            </tr>
            <tr>
              <td class="bg-warning">Contato</td>
              <td><span id="contato_dados"></span></td>
            </tr>
            <tr>
              <td class="bg-warning">E-mail</td>
              <td><span id="email_dados"></span></td>
            </tr>
            <tr>
              <td class="bg-warning">Site</td>
              <td><span id="site_dados"></span></td>
            </tr>
            <tr>
              <td class="bg-warning">Endereço</td>
              <td><span id="endereco_dados"></span>, nº <span id="numero_dados"></span></td>
            </tr>
            <tr>
              <td class="bg-warning">Complemento</td>
              <td><span id="complemento_dados"></span></td>
            </tr>
            <tr>
              <td class="bg-warning">Bairro</td>
              <td><span id="bairro_dados"></span></td>
            </tr>
            <tr>
              <td class="bg-warning">Cidade</td>
              <td><span id="cidade_dados"></span></td>
            </tr>
            <tr>
              <td class="bg-warning">UF</td>
              <td><span id="uf_dados"></span></td>
            </tr>
            <tr>
              <td class="bg-warning">CEP</td>
              <td><span id="cep_dados"></span></td>
            </tr>
            <tr>
              <td class="bg-warning">Plano de Pagamento</td>
              <td><span id="plano_pagamento_dados"></span></td>
            </tr>
            <tr>
              <td class="bg-warning">Forma de Pagamento</td>
              <td><span id="forma_pagamento_dados"></span></td>
            </tr>
            <tr>
              <td class="bg-warning">Prazo de Pagamento (dias)</td>
              <td><span id="prazo_pagamento_dados"></span></td>
            </tr>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>





<!-- Modal Arquivos -->
<div class="modal fade" id="modalArquivos" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header bg-primary text-white">
				<h4 class="modal-title" id="tituloModal">Gestão de Arquivos - <span id="nome-arquivo"> </span></h4>
				<button id="btn-fechar-arquivos" aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"><span class="text-white" aria-hidden="true">&times;</span></button>
			</div>
			<form id="form-arquivos" method="post">
				<div class="modal-body">

					<div class="row">
						<div class="col-md-8">						
							<div class="form-group"> 
								<label>Arquivo</label> 
								<input class="form-control" type="file" name="arquivo_conta" onChange="carregarImgArquivos();" id="arquivo_conta">
							</div>	
						</div>
						<div class="col-md-4">	
							<div id="divImgArquivos">
								<img src="images/arquivos/sem-foto.png"  width="60px" id="target-arquivos">									
							</div>					
						</div>




					</div>

					<div class="row" >
						<div class="col-md-8">
							<input type="text" class="form-control" name="nome-arq"  id="nome-arq" placeholder="Nome do Arquivo * " required>
						</div>

						<div class="col-md-4">										 
							<button type="submit" class="btn btn-primary">Inserir</button>
						</div>
					</div>

					<hr>

					<small><div id="listar-arquivos"></div></small>

					<br>
					<small><div align="center" id="mensagem-arquivo"></div></small>

					<input type="hidden" class="form-control" name="id-arquivo"  id="id-arquivo">


				</div>
			</form>
		</div>
	</div>
</div>







<!-- Modal Contas -->
<div class="modal fade" id="modalContas" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header bg-primary text-white">
				<h4 class="modal-title" id="exampleModalLabel"><span id="titulo_contas"></span></h4>
				<button id="btn-fechar-contas" aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"><span class="text-white" aria-hidden="true">&times;</span></button>
			</div>
			
			<div class="modal-body">				
				<div id="listar_debitos" style="margin-top: 15px">

				</div>
				<input type="hidden" id="id_contas">
			</div>

		</div>
	</div>
</div>






<!-- Modal -->
<div class="modal fade" id="modalBaixar" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog ">
		<div class="modal-content">
			<div class="modal-header bg-primary text-white">
				<h4 class="modal-title" id="tituloModal">Baixar Conta: <span id="descricao-baixar"> </span></h4>
				<button id="btn-fechar-baixar" aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"><span class="text-white" aria-hidden="true">&times;</span></button>
			</div>
			<form id="form-baixar" method="post">
				<div class="modal-body">

					<div class="row">
						<div class="col-md-6">
							<div class="mb-3">
								<label>Valor <small class="text-muted">(Total ou Parcial)</small></label>
								<input onkeyup="totalizar()" type="text" class="form-control" name="valor-baixar"  id="valor-baixar" required>
							</div>
						</div>


						<div class="col-md-6">
							<div class="form-group"> 
								<label>Forma PGTO</label> 
								<select class="form-select" name="saida-baixar" id="saida-baixar" required onchange="calcularTaxa()">	
									<?php 
									$query = $pdo->query("SELECT * FROM formas_pgto order by id asc");
									$res = $query->fetchAll(PDO::FETCH_ASSOC);
									for($i=0; $i < @count($res); $i++){
										foreach ($res[$i] as $key => $value){}

											?>	
										<option value="<?php echo $res[$i]['id'] ?>"><?php echo $res[$i]['nome'] ?></option>

									<?php } ?>

								</select>
							</div>
						</div>

					</div>	


					<div class="row">


						<div class="col-md-3">
							<div class="mb-3">
								<label>Multa em R$</label>
								<input onkeyup="totalizar()" type="text" class="form-control" name="valor-multa"  id="valor-multa" placeholder="Ex 15.00" value="0">
							</div>
						</div>

						<div class="col-md-3">
							<div class="mb-3">
								<label>Júros em R$</label>
								<input onkeyup="totalizar()" type="text" class="form-control" name="valor-juros"  id="valor-juros" placeholder="Ex 0.15" value="0">
							</div>
						</div>

						<div class="col-md-3">
							<div class="mb-3">
								<label >Desconto R$</label>
								<input onkeyup="totalizar()" type="text" class="form-control" name="valor-desconto"  id="valor-desconto" placeholder="Ex 15.00" value="0" >
							</div>
						</div>



						<div class="col-md-3">
							<div class="mb-3">
								<label >Taxa PGTO</label>
								<input onkeyup="totalizar()" type="text" class="form-control" name="valor-taxa"  id="valor-taxa" placeholder="" value="" >
							</div>
						</div>

					</div>


					<div class="row">

						<div class="col-md-6">
							<div class="mb-3">
								<label >Data da Baixa</label>
								<input type="date" class="form-control" name="data-baixar"  id="data-baixar" value="<?php echo date('Y-m-d') ?>" >
							</div>
						</div>


						<div class="col-md-6">
							<div class="mb-3">
								<label >SubTotal</label>
								<input type="text" class="form-control" name="subtotal"  id="subtotal" readonly>
							</div>	
						</div>
					</div>




					<small><div id="mensagem-baixar" align="center"></div></small>

					<input type="hidden" class="form-control" name="id-baixar"  id="id-baixar">


				</div>
				<div class="modal-footer">

					<button type="submit" class="btn btn-success">Baixar</button>
				</div>
			</form>
		</div>
	</div>
</div>



<script type="text/javascript">var pag = "<?=$pag?>"</script>
<script src="js/ajax.js"></script>



<script type="text/javascript">
	function mudarPessoa(){
		var pessoa = $('#tipo_pessoa').val();
		if(pessoa == 'Física'){
			$('#cpf').mask('000.000.000-00');
			$('#cpf').attr("placeholder", "Insira CPF");
		}else{
			$('#cpf').mask('00.000.000/0000-00');
			$('#cpf').attr("placeholder", "Insira CNPJ");
		}
	}
</script>
>



<script type="text/javascript">
	$("#form-arquivos").submit(function () {
		event.preventDefault();
		var formData = new FormData(this);

		$.ajax({
			url: 'paginas/' + pag + "/arquivos.php",
			type: 'POST',
			data: formData,

			success: function (mensagem) {
				$('#mensagem-arquivo').text('');
				$('#mensagem-arquivo').removeClass()
				if (mensagem.trim() == "Inserido com Sucesso") {                    
						//$('#btn-fechar-arquivos').click();
						$('#nome-arq').val('');
						$('#arquivo_conta').val('');
						$('#target-arquivos').attr('src','images/arquivos/sem-foto.png');
						listarArquivos();
					} else {
						$('#mensagem-arquivo').addClass('text-danger')
						$('#mensagem-arquivo').text(mensagem)
					}

				},

				cache: false,
				contentType: false,
				processData: false,

			});

	});
</script>

<script type="text/javascript">
	function listarArquivos(){
		var id = $('#id-arquivo').val();	
		$.ajax({
			url: 'paginas/' + pag + "/listar-arquivos.php",
			method: 'POST',
			data: {id},
			dataType: "text",

			success:function(result){
				$("#listar-arquivos").html(result);
			}
		});
	}

</script>




<script type="text/javascript">
	function carregarImgArquivos() {
		var target = document.getElementById('target-arquivos');
		var file = document.querySelector("#arquivo_conta").files[0];

		var arquivo = file['name'];
		resultado = arquivo.split(".", 2);

		if(resultado[1] === 'pdf'){
			$('#target-arquivos').attr('src', "images/pdf.png");
			return;
		}

		if(resultado[1] === 'rar' || resultado[1] === 'zip'){
			$('#target-arquivos').attr('src', "images/rar.png");
			return;
		}

		if(resultado[1] === 'doc' || resultado[1] === 'docx' || resultado[1] === 'txt'){
			$('#target-arquivos').attr('src', "images/word.png");
			return;
		}


		if(resultado[1] === 'xlsx' || resultado[1] === 'xlsm' || resultado[1] === 'xls'){
			$('#target-arquivos').attr('src', "images/excel.png");
			return;
		}


		if(resultado[1] === 'xml'){
			$('#target-arquivos').attr('src', "images/xml.png");
			return;
		}



		var reader = new FileReader();

		reader.onloadend = function () {
			target.src = reader.result;
		};

		if (file) {
			reader.readAsDataURL(file);

		} else {
			target.src = "";
		}
	}
</script>


<script type="text/javascript">
	function totalizar(){
		valor = $('#valor-baixar').val();
		desconto = $('#valor-desconto').val();
		juros = $('#valor-juros').val();
		multa = $('#valor-multa').val();
		taxa = $('#valor-taxa').val();

		valor = valor.replace(",", ".");
		desconto = desconto.replace(",", ".");
		juros = juros.replace(",", ".");
		multa = multa.replace(",", ".");
		taxa = taxa.replace(",", ".");

		if(valor == ""){
			valor = 0;
		}

		if(desconto == ""){
			desconto = 0;
		}

		if(juros == ""){
			juros = 0;
		}

		if(multa == ""){
			multa = 0;
		}

		if(taxa == ""){
			taxa = 0;
		}

		subtotal = parseFloat(valor) + parseFloat(juros) + parseFloat(taxa) + parseFloat(multa) - parseFloat(desconto);


		console.log(subtotal)

		$('#subtotal').val(subtotal);

	}

	function calcularTaxa(){
		pgto = $('#saida-baixar').val();
		valor = $('#valor-baixar').val();
		$.ajax({
			url: 'paginas/receber/calcular_taxa.php',
			method: 'POST',
			data: {valor, pgto},
			dataType: "html",

			success:function(result){		           
				$('#valor-taxa').val(result);
				totalizar();
			}
		});


	}
</script>




<script type="text/javascript">
	$("#form-baixar").submit(function () {
		event.preventDefault();
		var formData = new FormData(this);

		var id_conta = $('#id_contas').val(); 	

		$.ajax({
			url: 'paginas/receber/baixar.php',
			type: 'POST',
			data: formData,

			success: function (mensagem) {						

				$('#mensagem-baixar').text('');
				$('#mensagem-baixar').removeClass()
				if (mensagem.trim() == "Baixado com Sucesso") {                    
					$('#btn-fechar-baixar').click();
					listarDebitos(id_conta);

				} else {
					$('#mensagem-baixar').addClass('text-danger')
					$('#mensagem-baixar').text(mensagem)
				}

			},

			cache: false,
			contentType: false,
			processData: false,

		});

	});
</script>





<script>
    
    function limpa_formulário_cep() {
            //Limpa valores do formulário de cep.
            document.getElementById('endereco').value=("");
            document.getElementById('bairro').value=("");
            document.getElementById('cidade').value=("");
            document.getElementById('estado').value=("");
            //document.getElementById('ibge').value=("");
    }

    function meu_callback(conteudo) {
        if (!("erro" in conteudo)) {
            //Atualiza os campos com os valores.
            document.getElementById('endereco').value=(conteudo.logradouro);
            document.getElementById('bairro').value=(conteudo.bairro);
            document.getElementById('cidade').value=(conteudo.localidade);
            document.getElementById('estado').value=(conteudo.uf);
            //document.getElementById('ibge').value=(conteudo.ibge);
        } //end if.
        else {
            //CEP não Encontrado.
            limpa_formulário_cep();
            alert("CEP não encontrado.");
        }
    }
        
    function pesquisacep(valor) {

        //Nova variável "cep" somente com dígitos.
        var cep = valor.replace(/\D/g, '');

        //Verifica se campo cep possui valor informado.
        if (cep != "") {

            //Expressão regular para validar o CEP.
            var validacep = /^[0-9]{8}$/;

            //Valida o formato do CEP.
            if(validacep.test(cep)) {

                //Preenche os campos com "..." enquanto consulta webservice.
                document.getElementById('endereco').value="...";
                document.getElementById('bairro').value="...";
                document.getElementById('cidade').value="...";
                document.getElementById('estado').value="...";
                //document.getElementById('ibge').value="...";

                //Cria um elemento javascript.
                var script = document.createElement('script');

                //Sincroniza com o callback.
                script.src = 'https://viacep.com.br/ws/'+ cep + '/json/?callback=meu_callback';

                //Insere script no documento e carrega o conteúdo.
                document.body.appendChild(script);

            } //end if.
            else {
                //cep é inválido.
                limpa_formulário_cep();
                alert("Formato de CEP inválido.");
            }
        } //end if.
        else {
            //cep sem valor, limpa formulário.
            limpa_formulário_cep();
        }
    };

    </script>



    <script type="text/javascript">
    	function buscar(){
    		var ina = $("#ina").val();
    		listar(ina);
    	}
    </script>