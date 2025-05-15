<?php
require_once("verificar.php");
$pag = 'fornecedores';

//verificar se ele tem a permissão de estar nessa página
if (@$fornecedores == 'ocultar') {
	echo "<script>window.location='index'</script>";
	exit();
}
?>

<div class="justify-content-between">
	<div class="left-content mt-2 mb-3">
		<a class="btn ripple btn-primary text-white" onclick="inserir()" type="button"><i class="fe fe-plus me-2"></i> Adicionar <?php echo ucfirst($pag); ?></a>



		<div class="dropdown" style="display: inline-block;">
			<a href="#" aria-expanded="false" aria-haspopup="true" data-bs-toggle="dropdown" class="btn btn-danger dropdown" id="btn-deletar" style="display:none"><i class="fe fe-trash-2"></i> Deletar</a>
			<div class="dropdown-menu tx-13">
				<div style="width: 240px; padding:15px 5px 0 10px;" class="dropdown-item-text">
					<p>Excluir Selecionados? <a href="#" onclick="deletarSel()"><span class="text-danger">Sim</span></a></p>
				</div>
			</div>
		</div>

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
	function mascara_ie(valor) {
		var valorAlterado = $('#' + valor).val();
		valorAlterado = valorAlterado.replace(/\D/g, ""); // Remove todos os não dígitos
		valorAlterado = valorAlterado.replace(/(\d{3})(\d)/, "$1.$2"); // Adiciona o primeiro ponto
		valorAlterado = valorAlterado.replace(/(\d{3})(\d)/, "$1.$2"); // Adiciona o segundo ponto
		valorAlterado = valorAlterado.replace(/(\d{3})(\d{1,2})$/, "$1.$2"); // Adiciona o terceiro ponto
		$('#' + valor).val(valorAlterado);
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

<div class="modal fade" id="modalForm" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header bg-primary text-white">
				<h4 class="modal-title" id="exampleModalLabel"><span id="titulo_inserir"></span></h4>
				<button id="btn-fechar" aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"><span class="text-white" aria-hidden="true">&times;</span></button>
			</div>
			<form id="form">
				<div class="modal-body">

					<!-- Título: Tipo de Pessoa -->
					<h5 class="mb-3">Tipo de Pessoa</h5>
					<div class="row mb-3">
						<div class="col-md-12">
							<label>
								<input type="radio" name="tipo_pessoa" id="radio_pessoa_fisica" value="fisica" checked>
								Pessoa Física
							</label>
							<label class="ms-3">
								<input type="radio" name="tipo_pessoa" id="radio_cnpj" value="cnpj">
								CNPJ
							</label>
						</div>
					</div>

					<hr>

					<!-- Título: Dados Gerais -->
					<h5 class="mb-3">Dados Gerais</h5>
					<div class="row">
						<div class="col-md-6 mb-2">
							<label>Nome do Atacadista</label>
							<input type="text" class="form-control" id="nome_atacadista" name="nome_atacadista" placeholder="Nome do Atacadista" required>
						</div>
					</div>

					<hr>

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
							<input type="text" class="form-control" id="ie" name="ie" placeholder="IE do Fornecedor" onkeyup="mascara_ie('ie')" maxlength="14">
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

<!-- Modal Dados - Fornecedores Atualizado -->
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
            <tr><td class="bg-warning">Tipo de Pessoa</td><td><span id="tipo_pessoa_dados"></span></td></tr>
            <tr><td class="bg-warning">Nome do Atacadista</td><td><span id="nome_atacadista_dados"></span></td></tr>
            <tr><td class="bg-warning">Razão Social</td><td><span id="razao_social_dados"></span></td></tr>
            <tr><td class="bg-warning">CNPJ</td><td><span id="cnpj_dados"></span></td></tr>
            <tr><td class="bg-warning">Inscrição Estadual</td><td><span id="ie_dados"></span></td></tr>
            <tr><td class="bg-warning">CPF</td><td><span id="cpf_dados"></span></td></tr>
            <tr><td class="bg-warning">RG</td><td><span id="rg_dados"></span></td></tr>
            <tr><td class="bg-warning">Rua</td><td><span id="rua_dados"></span></td></tr>
            <tr><td class="bg-warning">Número</td><td><span id="numero_dados"></span></td></tr>
            <tr><td class="bg-warning">Complemento</td><td><span id="complemento_dados"></span></td></tr>
            <tr><td class="bg-warning">Bairro</td><td><span id="bairro_dados"></span></td></tr>
            <tr><td class="bg-warning">Cidade</td><td><span id="cidade_dados"></span></td></tr>
            <tr><td class="bg-warning">UF</td><td><span id="uf_dados"></span></td></tr>
            <tr><td class="bg-warning">CEP</td><td><span id="cep_dados"></span></td></tr>
            <tr><td class="bg-warning">Contato</td><td><span id="contato_dados"></span></td></tr>
            <tr><td class="bg-warning">E-mail</td><td><span id="email_dados"></span></td></tr>
            <tr><td class="bg-warning">Site</td><td><span id="site_dados"></span></td></tr>
            <tr><td class="bg-warning">Plano de Pagamento</td><td><span id="plano_pagamento_dados"></span></td></tr>
            <tr><td class="bg-warning">Forma de Pagamento</td><td><span id="forma_pagamento_dados"></span></td></tr>
            <tr><td class="bg-warning">Prazo de Pagamento (dias)</td><td><span id="prazo_pagamento_dados"></span></td></tr>
            <tr><td class="bg-warning">Data de Cadastro</td><td><span id="data_cadastro_dados"></span></td></tr>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>





<script type="text/javascript">
	var pag = "<?= $pag ?>"
</script>
<script src="js/ajax.js"></script>