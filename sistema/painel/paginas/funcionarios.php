<?php
require_once("verificar.php");
$pag = 'funcionarios';

//verificar se ele tem a permissão de estar nessa página
if (@$funcionarios == 'ocultar') {
    echo "<script>window.location='index'</script>";
    exit();
}
?>

<div class="justify-content-between">
 	<div class="left-content mt-2 mb-3">
 		<a class="btn ripple btn-primary text-white" onclick="inserir()" type="button"><i class="fe fe-plus me-2"></i> Adicionar <?php echo ucfirst($pag); ?></a>

		<div class="dropdown" style="display: inline-block;">
            <a href="#" aria-expanded="false" aria-haspopup="true" data-bs-toggle="dropdown" class="btn btn-danger dropdown" id="btn-deletar" style="display:none"><i class="fe fe-trash-2"></i> Deletar</a>
            <div  class="dropdown-menu tx-13">
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


<div class="modal fade" id="modalForm" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title" id="exampleModalLabel"><span id="titulo_inserir"></span></h4>
                <button id="btn-fechar" aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"><span class="text-white" aria-hidden="true">&times;</span></button>
            </div>
            <form id="form">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label>Nome</label>
                            <input type="text" class="form-control" id="nome" name="nome" placeholder="Nome do Funcionário" required>
                        </div>
                        <div class="col-md-3 mb-3 col-6">
                            <label>Telefone</label>
                            <input type="text" class="form-control" id="telefone" name="telefone" placeholder="Telefone" required>
                        </div>
                        <div class="col-md-3 mb-3 col-6">
                            <label>Chave Pix</label>
                            <input type="text" class="form-control" id="chave_pix" name="chave_pix" placeholder="Chave Pix">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <label>Endereço</label>
                            <input type="text" class="form-control" id="endereco" name="endereco" placeholder="Endereço Completo">
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-3 mb-2 col-6">
                            <label>Cargo</label>
                            <select class="form-select" name="cargo" id="cargo" required>
                                <?php
                                $query = $pdo->query("SELECT * from cargos order by nome asc");
                                $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                if (@count($res) > 0) {
                                    foreach ($res as $item) { ?>
                                        <option value="<?php echo $item['id'] ?>"><?php echo $item['nome'] ?></option>
                                <?php }
                                } ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3 col-6">
                            <label>Data de Admissão</label>
                            <input type="date" class="form-control" id="data_admissao" name="data_admissao" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-3 mb-2 col-6">
                            <label>Situação</label>
                            <select class="form-select" name="status" id="status" onchange="toggleDemissao()">
                                <option value="Ativo">Ativo</option>
                                <option value="Demitido">Demitido</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3 col-6" id="demissao-container" style="display: none;">
                            <label>Data de Demissão</label>
                            <input type="date" class="form-control" id="data_demissao" name="data_demissao">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>Descrição Salarial (x Sal. Mínimo)</label>
                            <input type="text" class="form-control" id="descricao_salario" name="descricao_salario" placeholder="Ex: 2.51" onkeyup="mascara_decimal_ponto(this); calcularSalarioFolha();">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Salário Folha (R$)</label>
                            <input type="text" class="form-control" id="salario_folha" name="salario_folha" placeholder="Cálculo Automático" readonly>
                        </div>
                    </div>
                    <div class="row">
                         <div class="col-md-12">
                            <label>Observações</label>
                            <textarea class="form-control" id="obs" name="obs" maxlength="500"></textarea>
                        </div>
                    </div>
                    <input type="hidden" class="form-control" id="id" name="id">
                    <br>
                    <small><div id="mensagem" align="center"></div></small>
                </div>
                <div class="modal-footer">
                    <button type="submit" id="btn_salvar" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>


<div class="modal fade" id="modalDados" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title" id="exampleModalLabel"><span id="titulo_dados"></span></h4>
                <button id="btn-fechar-dados" aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"><span class="text-white" aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-7">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tr><td class="bg-light w-25">Telefone</td><td><span id="telefone_dados"></span></td></tr>
                                <tr><td class="bg-light">Endereço</td><td><span id="endereco_dados"></span></td></tr>
                                <tr><td class="bg-light">Chave Pix</td><td><span id="pix_dados"></span></td></tr>
                                <tr><td class="bg-light">Cargo</td><td><span id="cargo_dados"></span></td></tr>
                                <tr><td class="bg-light">Salário Folha</td><td><span id="salario_dados"></span></td></tr>
                                <tr><td class="bg-light">Status</td><td><span id="status_dados"></span></td></tr>
                                <tr><td class="bg-light">Admissão</td><td><span id="admissao_dados"></span></td></tr>
                                <tr><td class="bg-light">Demissão</td><td><span id="demissao_dados"></span></td></tr>
                                <tr><td class="bg-light">Data Cadastro</td><td><span id="data_cad_dados"></span></td></tr>
                                <tr><td class="bg-light">Observações</td><td><span id="obs_dados"></span></td></tr>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-5 d-flex align-items-center justify-content-center">
                        <img src="" id="foto_dados" width="200px" class="img-fluid rounded">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


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


<script type="text/javascript">var pag = "<?=$pag?>"</script>
<script src="js/ajax.js"></script>


<script type="text/javascript">
    // Variável global para armazenar o valor do salário mínimo
    let salarioMinimoAtual = 0;

    /**
     * Busca o salário mínimo da nossa API interna assim que a página carrega.
     */
    function carregarSalarioMinimo() {
        fetch('buscar_salario.php')
            .then(response => response.json())
            .then(data => {
                if (data && data.valor > 0) {
                    salarioMinimoAtual = data.valor;
                    console.log('Salário mínimo carregado: R$', salarioMinimoAtual);
                } else {
                    salarioMinimoAtual = 1518.00; // Fallback de segurança
                }
            })
            .catch(error => {
                console.error('Erro ao buscar salário mínimo:', error);
                salarioMinimoAtual = 1518.00; // Fallback de segurança
            });
    }

    /**
     * Calcula o salário folha usando a variável global do salário mínimo.
     */
    function calcularSalarioFolha() {
        if (salarioMinimoAtual === 0) return; // Não calcula se o salário não foi carregado
        const inputDescricao = document.getElementById('descricao_salario');
        const inputSalarioFolha = document.getElementById('salario_folha');
        const multiplicador = parseFloat(inputDescricao.value) || 0;
        const salarioCalculado = multiplicador * salarioMinimoAtual;
        inputSalarioFolha.value = salarioCalculado.toFixed(2);
    }
    
    /**
     * Máscara para formatar números como decimais com ponto (ex: 251 -> 2.51).
     */
    function mascara_decimal_ponto(el) {
        let valor = el.value.replace(/\D/g, '');
        if (valor === '') {
            el.value = '';
            return;
        }
        valor = String(Number(valor));
        while (valor.length < 3) {
            valor = '0' + valor;
        }
        let parteInteira = valor.slice(0, -2);
        let centavos = valor.slice(-2);
        if (parteInteira === '') {
            parteInteira = '0';
        }
        el.value = parteInteira + '.' + centavos;
    }

    /**
     * Mostra ou esconde o campo de data de demissão com base na situação.
     */
    function toggleDemissao() {
        var status = document.getElementById('status').value;
        var container = document.getElementById('demissao-container');
        container.style.display = (status === 'Demitido') ? 'block' : 'none';
    }

    // Chama a função para carregar o salário mínimo assim que o documento estiver pronto.
    document.addEventListener('DOMContentLoaded', carregarSalarioMinimo);
</script>


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