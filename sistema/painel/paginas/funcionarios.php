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

            <form id="form" enctype="multipart/form-data">
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

                    <div class="row align-items-center">
                        <div class="col-md-8 mb-3">
                            <label>Foto (3x4)</label>
                            <input
                                type="file"
                                class="form-control"
                                id="foto"
                                name="foto"
                                accept="image/*"
                            >
                            <small class="text-muted">Formatos: JPG, PNG, WEBP. A prévia corta em 3×4 (cover).</small>
                        </div>
                        <div class="col-md-4 mb-3 d-flex justify-content-center">
                            <div class="preview-3x4-box border rounded position-relative overflow-hidden">
                                <img id="preview-foto" src="images/funcionarios/sem-foto.jpg" alt="Preview 3x4" class="preview-3x4-img">
                                <div class="preview-3x4-watermark">3x4</div>
                            </div>
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
    <div class="modal-dialog modal-xl">
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

<div class="modal fade" id="modalGratificacao" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title" id="exampleModalLabel"><span id="titulo_gratificacao"></span></h4>
                <button aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"><span class="text-white" aria-hidden="true">&times;</span></button>
            </div>
            <form id="form-gratificacao">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Valor (R$)</label>
                            <input type="text" class="form-control" id="valor_grat" name="valor" placeholder="Ex: 150,00" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Data</label>
                            <input type="date" class="form-control" id="data_grat" name="data" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <label>Descrição</label>
                            <textarea class="form-control" id="descricao_grat" name="descricao" placeholder="Ex: Produção da colheita de café..." maxlength="255"></textarea>
                        </div>
                    </div>
                    
                    <input type="hidden" id="id_funcionario_grat" name="id_funcionario">
                    <br>
                    <small><div id="mensagem-gratificacao" align="center"></div></small>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Salvar Lançamento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAdiantamento" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title" id="exampleModalLabel"><span id="titulo_adiantamento"></span></h4>
                <button aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"><span class="text-white" aria-hidden="true">&times;</span></button>
            </div>
            <form id="form-adiantamento">
                <div class="modal-body">
                    <div class="alert alert-info" role="alert">
                        <small>Limite recomendado (30%): <strong id="limite_adiantamento">R$ 0,00</strong></small>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Valor (R$)</label>
                            <input type="text" class="form-control" id="valor_adiant" name="valor" placeholder="Ex: 500,00" required>
                        </div>
                        <div class="col-md-6 mb-3">
                             <label>Data</label>
                            <input type="date" class="form-control" id="data_adiant" name="data" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                     <div class="row">
                        <div class="col-md-12">
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
                    </div>

                    <input type="hidden" id="id_funcionario_adiant" name="id_funcionario">
                    <br>
                    <small><div id="mensagem-adiantamento" align="center"></div></small>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Salvar Adiantamento</button>
                </div>
            </form>
        </div>
    </div>
</div>


<div class="modal fade" id="modalHistorico" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title" id="exampleModalLabel">Histórico - <span id="nome-historico"></span></h4>
                <button aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"><span class="text-white" aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                
                <form id="form-filtros-historico" class="mb-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="data_inicio_hist" class="form-label">De:</label>
                            <input type="date" class="form-control" id="data_inicio_hist" name="data_inicio">
                        </div>
                        <div class="col-md-3">
                            <label for="data_fim_hist" class="form-label">Até:</label>
                            <input type="date" class="form-control" id="data_fim_hist" name="data_fim">
                        </div>
                        <div class="col-md-3">
                            <label for="tipo_hist" class="form-label">Tipo de Evento:</label>
                            <select class="form-select" id="tipo_hist" name="tipo">
                                <option value="Todos" selected>Todos os Tipos</option>
                                <optgroup label="Financeiro">
                                    <option value="Gratificação">Gratificação</option>
                                    <option value="Adiantamento">Adiantamento</option>
                                </optgroup>
                                <optgroup label="Administrativo">
                                    <option value="CONTRATACAO">Contratação</option>
                                    <option value="PROMOCAO">Promoção</option>
                                    <option value="ALTERACAO_SALARIAL">Alteração Salarial</option>
                                    <option value="REATIVACAO">Reativação</option>
                                    <option value="DEMISSAO">Demissão</option>
                                </optgroup>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="valor_min_hist" class="form-label">Valor Mínimo (R$):</label>
                            <input type="text" class="form-control" id="valor_min_hist" name="valor_min" placeholder="Ex: 100,00">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12 text-end">
                            <button type="button" class="btn btn-light border" onclick="limparFiltrosHistorico()">Limpar</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-filter me-1"></i>Filtrar
                            </button>
                        </div>
                    </div>
                </form>

                <hr>
                
                <ul class="nav nav-tabs" id="historicoTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="timeline-tab" data-bs-toggle="tab" data-bs-target="#historico-timeline" type="button" role="tab" aria-controls="timeline" aria-selected="true">
                            <i class="fa fa-history me-1"></i>Linha do Tempo
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="financeiro-tab" data-bs-toggle="tab" data-bs-target="#historico-financeiro" type="button" role="tab" aria-controls="financeiro" aria-selected="false">
                            <i class="fa fa-dollar me-1"></i>Financeiro
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="admin-tab" data-bs-toggle="tab" data-bs-target="#historico-admin" type="button" role="tab" aria-controls="admin" aria-selected="false">
                            <i class="fa fa-briefcase me-1"></i>Administrativo
                        </button>
                    </li>
                </ul>

                <div class="tab-content pt-3" id="historicoTabContent">
                    <div class="tab-pane fade show active" id="historico-timeline" role="tabpanel" aria-labelledby="timeline-tab"></div>
                    <div class="tab-pane fade" id="historico-financeiro" role="tabpanel" aria-labelledby="financeiro-tab"></div>
                    <div class="tab-pane fade" id="historico-admin" role="tabpanel" aria-labelledby="admin-tab"></div>
                </div>
                <input type="hidden" id="id_funcionario_hist">
            </div>
        </div>
    </div>
</div>

<style>

    /* CSS para a Timeline do Histórico */
.timeline {
    margin-left: 15px; /* ADICIONADO: Adiciona um respiro à esquerda para a bolinha não cortar */
    position: relative;
    padding: 0;
    list-style: none;
}

.timeline-item {
    position: relative;
    padding-left: 30px; 
    padding-bottom: 25px; 
    border-left: 2px solid #e9ecef;
}

.timeline-item:last-child {
    border-left: 2px solid transparent;
    padding-bottom: 0;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: -9px;
    top: 4px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background-color: #adb5bd; /* Cor padrão do ponto */
    border: 2px solid #fff;
}

/* Cores dos pontos da timeline baseadas no tipo de evento */
.timeline-item-success::before { background-color: #198754; } /* Verde */
.timeline-item-warning::before { background-color: #ffc107; } /* Amarelo */
.timeline-item-danger::before  { background-color: #dc3545; } /* Vermelho */
.timeline-item-info::before    { background-color: #0dcaf0; } /* Azul Claro */
.timeline-item-primary::before { background-color: #0d6efd; } /* Azul */
.timeline-item-secondary::before { background-color: #6c757d; } /* Cinza */

.timeline-content .badge {
    font-size: 0.9em;
}

.timeline-content small {
    display: block;
    color: #6c757d;
}
/* --- ESTILOS PARA AUMENTAR FONTE DO MODAL DE HISTÓRICO (VERSÃO AJUSTADA) --- */

/* Alvo: O item da lista dentro do modal específico */
#modalHistorico .list-group-item {
    /* Um aumento quase imperceptível, só para dar mais clareza */
    font-size: 1.05rem; 
    /* Reduzimos o espaçamento para não ficar muito "esticado" */
    padding-top: 0.8rem; 
    padding-bottom: 0.8rem;
}

/* Alvo: O ícone (fe) dentro do item da lista */
#modalHistorico .list-group-item .fe {
    /* Apenas um pouco maior que o texto, para se destacar na medida certa */
    font-size: 1.15rem; 
    vertical-align: middle; 
}

/* Alvo: O texto em negrito (data) - Removido o aumento para não sobrecarregar */
#modalHistorico .list-group-item strong {
    /* Não precisa de um tamanho maior, o negrito já faz o trabalho */
}

/* Alvo: O 'badge' com o valor */
#modalHistorico .list-group-item .badge {
    /* Um tamanho legível, mas que não compete com o texto principal */
    font-size: 0.9rem; 
    /* Padding menor para um badge mais compacto */
    padding: 0.45em 0.7em; 
}
</style>



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

<style>
  .preview-3x4-box {
    width: 180px;
    aspect-ratio: 3 / 4;
    background: #f8f9fa;
    display: inline-block;
  }
  .preview-3x4-img {
    width: 100%;
    height: 100%;
    object-fit: cover; /* corta para preencher mantendo 3x4 */
    display: block;
  }
  .preview-3x4-watermark{
    position:absolute;
    bottom:6px;
    right:8px;
    font-size:12px;
    color:#6c757d;
    background: rgba(255,255,255,.7);
    padding: 2px 6px;
    border-radius: 4px;
  }
</style>

<script type="text/javascript">var pag = "<?=$pag?>"</script>
<script src="js/ajax.js"></script>

<script type="text/javascript">
    // ======= SALÁRIO MÍNIMO / CÁLCULOS =======
    let salarioMinimoAtual = 0;

    function carregarSalarioMinimo() {
        fetch('apis/buscar_salario.php')
            .then(response => response.json())
            .then(data => {
                if (data && data.valor > 0) {
                    salarioMinimoAtual = data.valor;
                    console.log('[FUNCIONARIOS] salário mínimo carregado:', salarioMinimoAtual);
                } else {
                    salarioMinimoAtual = 1518.00;
                    console.warn('[FUNCIONARIOS] usando fallback do salário mínimo:', salarioMinimoAtual);
                }
            })
            .catch(error => {
                console.error('[FUNCIONARIOS] Erro ao buscar salário mínimo:', error);
                salarioMinimoAtual = 1518.00;
            });
    }

    function calcularSalarioFolha() {
        if (salarioMinimoAtual === 0) return;
        const inputDescricao = document.getElementById('descricao_salario');
        const inputSalarioFolha = document.getElementById('salario_folha');
        const multiplicador = parseFloat(inputDescricao.value) || 0;
        const salarioCalculado = multiplicador * salarioMinimoAtual;
        inputSalarioFolha.value = salarioCalculado.toFixed(2);
    }
    
    function mascara_decimal_ponto(el) {
        let valor = el.value.replace(/\D/g, '');
        if (valor === '') {
            el.value = '';
            return;
        }
        valor = String(Number(valor));
        while (valor.length < 3) valor = '0' + valor;
        let parteInteira = valor.slice(0, -2);
        let centavos = valor.slice(-2);
        if (parteInteira === '') parteInteira = '0';
        el.value = parteInteira + '.' + centavos;
    }

    function toggleDemissao() {
        var status = document.getElementById('status').value;
        var container = document.getElementById('demissao-container');
        container.style.display = (status === 'Demitido') ? 'block' : 'none';
    }

    // ======= PREVIEW 3x4 (SEM INLINE) =======
    (function(){
        let lastObjectURL = null;

        function resetPreviewFoto(){
            const img = document.getElementById('preview-foto');
            if (!img) return;
            if (lastObjectURL) {
                URL.revokeObjectURL(lastObjectURL);
                lastObjectURL = null;
            }
            img.src = 'images/arquivos/sem-foto.png';
            const input = document.getElementById('foto');
            if (input) input.value = ''; // Limpa o valor do input de arquivo
            console.log('[FUNCIONARIOS] reset preview/input');
        }

        function aplicarPreview(ev){
            // ev.target é o input que disparou o evento
            const input = ev.target;
            
            // Garantias de que o código só roda para o input certo
            if (!(input instanceof HTMLInputElement) || input.id !== 'foto' || input.type !== 'file') {
                return;
            }

            const file = input.files?.[0];
            const img = document.getElementById('preview-foto');
            if (!img) return;

            // Se não houver arquivo ou não for imagem, reseta
            if (!file || !file.type.startsWith('image/')) {
                resetPreviewFoto();
                return;
            }
            
            // Limpa a URL de objeto antiga para evitar vazamento de memória
            if (lastObjectURL) {
                URL.revokeObjectURL(lastObjectURL);
            }

            // Cria uma nova URL de objeto para o arquivo selecionado
            lastObjectURL = URL.createObjectURL(file);
            console.log("Nova Object URL criada: " + lastObjectURL);

            // Define o src da imagem de preview
            img.src = lastObjectURL;
            console.log('[FUNCIONARIOS] Preview atualizado:', file.name);
        }

        // Executa quando o HTML da página estiver pronto
        document.addEventListener('DOMContentLoaded', function(){
            carregarSalarioMinimo();

            // Adiciona o listener para resetar o preview quando o modal for aberto
            const modal = document.getElementById('modalForm');


            // *** CORREÇÃO APLICADA AQUI ***
            // Pega o input de foto e anexa o evento 'change' diretamente a ele.
            const inputFoto = document.getElementById('foto');
            if (inputFoto) {
                inputFoto.addEventListener('change', aplicarPreview);
            } else {
                console.error('[ERRO] Input de foto com id="foto" não foi encontrado.');
            }
        });

        // Expor a função de reset globalmente (opcional, mas pode ser útil)
        window.resetPreviewFoto = resetPreviewFoto;
    })();
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
        if (!file) { target.src = "images/arquivos/sem-foto.png"; return; }

        var arquivo = file['name'];
        var resultado = arquivo.split(".", 2);

        if(resultado[1] === 'pdf'){ $('#target-arquivos').attr('src', "images/pdf.png"); return; }
        if(resultado[1] === 'rar' || resultado[1] === 'zip'){ $('#target-arquivos').attr('src', "images/rar.png"); return; }
        if(resultado[1] === 'doc' || resultado[1] === 'docx' || resultado[1] === 'txt'){ $('#target-arquivos').attr('src', "images/word.png"); return; }
        if(resultado[1] === 'xlsx' || resultado[1] === 'xlsm' || resultado[1] === 'xls'){ $('#target-arquivos').attr('src', "images/excel.png"); return; }
        if(resultado[1] === 'xml'){ $('#target-arquivos').attr('src', "images/xml.png"); return; }

        var reader = new FileReader();
        reader.onloadend = function () { target.src = reader.result; };
        reader.readAsDataURL(file);
    }
</script>

<script type="text/javascript">
    // MÁSCARAS PARA OS NOVOS CAMPOS DE VALOR
    $(document).ready(function() {
        $('#valor_grat').mask('000.000.000,00', {reverse: true});
        $('#valor_adiant').mask('000.000.000,00', {reverse: true});
    });


    // ======= FUNÇÕES PARA ABRIR OS MODAIS =======
    
    function abrirModalGratificacao(id, nome) {
        // Limpa campos
        $('#form-gratificacao')[0].reset();
        $('#id_funcionario_grat').val(id);
        $('#titulo_gratificacao').text('Lançar Gratificação para ' + nome);
        
        var modal = new bootstrap.Modal(document.getElementById('modalGratificacao'));
        modal.show();
    }
    
	function abrirModalAdiantamento(id, nome, salarioFolha) {
    // Limpa campos
    $('#form-adiantamento')[0].reset();
    $('#valor_adiant').removeClass('is-invalid'); // Garante que o campo comece sem o alerta
    $('#id_funcionario_adiant').val(id);
    $('#titulo_adiantamento').text('Lançar Adiantamento para ' + nome);

    // Calcula e exibe o limite de 30%
    let limite = parseFloat(salarioFolha) * 0.30;
    $('#limite_adiantamento').text(limite.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }));

    // --- NOVA LINHA ADICIONADA AQUI ---
    // Armazena o valor numérico do limite diretamente no campo para fácil acesso
    $('#valor_adiant').data('limite', limite); 

    var modal = new bootstrap.Modal(document.getElementById('modalAdiantamento'));
    modal.show();
}

function renderizarHistorico(historico) {
    // Aponta para os containers das abas
    const timelineContainer = $("#historico-timeline");
    const financeiroContainer = $("#historico-financeiro");
    const adminContainer = $("#historico-admin");

    // Limpa o conteúdo anterior de todas as abas
    timelineContainer.empty();
    financeiroContainer.empty();
    adminContainer.empty();

    if (!historico || historico.length === 0) {
        const msgVazio = '<div class="alert alert-light" role="alert">Nenhum registro encontrado para os filtros selecionados.</div>';
        timelineContainer.html(msgVazio);
        financeiroContainer.html(msgVazio);
        adminContainer.html(msgVazio);
        return;
    }

    // Cria as listas para cada aba
    const timelineList = $('<ul class="timeline"></ul>');
    const financeiroList = $('<ul class="timeline"></ul>');
    const adminList = $('<ul class="timeline"></ul>');

    historico.forEach(item => {
        const dataFormatada = new Date(item.data).toLocaleDateString('pt-BR', { day: '2-digit', month: 'long', year: 'numeric' });
        
        let timelineClass = '', iconClass = '', textoPrincipal = '', valorHtml = '';
        let categoria = ''; // Para saber em qual aba adicional inserir

        switch (item.tipo) {
            case 'Gratificação':
                const valorGrat = parseFloat(item.valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
                timelineClass = 'timeline-item-success';
                iconClass = 'fa fa-arrow-up';
                textoPrincipal = item.descricao || 'Gratificação Recebida';
                valorHtml = `<span class="badge bg-success rounded-pill">+ ${valorGrat}</span>`;
                categoria = 'financeiro';
                break;

            case 'Adiantamento':
                const valorAdiant = parseFloat(item.valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
                timelineClass = 'timeline-item-warning';
                iconClass = 'fa fa-arrow-down';
                textoPrincipal = `Adiantamento via ${item.forma_pgto || 'Não informado'}`;
                valorHtml = `<span class="badge bg-warning text-dark rounded-pill">- ${valorAdiant}</span>`;
                categoria = 'financeiro';
                break;

            case 'PROMOCAO':
                timelineClass = 'timeline-item-info';
                iconClass = 'fa fa-trophy';
                textoPrincipal = item.descricao;
                categoria = 'admin';
                break;

            case 'ALTERACAO_SALARIAL':
                timelineClass = 'timeline-item-secondary';
                iconClass = 'fa fa-dollar';
                textoPrincipal = item.descricao;
                categoria = 'admin';
                break;
            
            case 'CONTRATACAO':
                timelineClass = 'timeline-item-primary';
                iconClass = 'fa fa-user-plus';
                textoPrincipal = item.descricao;
                categoria = 'admin';
                break;
            
            case 'DEMISSAO':
                timelineClass = 'timeline-item-danger';
                iconClass = 'fa fa-user-times';
                textoPrincipal = item.descricao;
                categoria = 'admin';
                break;

            case 'REATIVACAO':
                timelineClass = 'timeline-item-info';
                iconClass = 'fa fa-undo';
                textoPrincipal = item.descricao;
                categoria = 'admin';
                break;

            default:
                timelineClass = 'timeline-item-secondary';
                iconClass = 'fa fa-info-circle';
                textoPrincipal = item.descricao || 'Evento não reconhecido';
        }

        const itemHtml = `
            <li class="timeline-item ${timelineClass}">
                <div class="d-flex justify-content-between align-items-center timeline-content">
                    <div>
                        <strong><i class="${iconClass} me-2"></i>${textoPrincipal}</strong>
                        <small>${dataFormatada}</small>
                    </div>
                    ${valorHtml}
                </div>
            </li>`;
        
        // Adiciona o item à timeline principal
        timelineList.append(itemHtml);

        // Adiciona o item à sua respectiva aba de categoria
        if (categoria === 'financeiro') {
            financeiroList.append(itemHtml);
        } else if (categoria === 'admin') {
            adminList.append(itemHtml);
        }
    });

    // Insere as listas preenchidas nas suas respectivas abas
    timelineContainer.append(timelineList);
    
    if (financeiroList.children().length > 0) {
        financeiroContainer.append(financeiroList);
    } else {
        financeiroContainer.html('<div class="alert alert-light" role="alert">Nenhum evento financeiro encontrado.</div>');
    }

    if (adminList.children().length > 0) {
        adminContainer.append(adminList);
    } else {
        adminContainer.html('<div class="alert alert-light" role="alert">Nenhum evento administrativo encontrado.</div>');
    }
}

function aplicarFiltroHistorico() {
    const container = $("#listar-historico");
    container.html('<p>Buscando histórico...</p>'); // Mensagem de carregamento

    let valorMin = $('#valor_min_hist').val().replace(/\./g, '').replace(',', '.');

    const filtroData = {
        id: $('#id_funcionario_hist').val(),
        data_inicio: $('#data_inicio_hist').val(),
        data_fim: $('#data_fim_hist').val(),
        tipo: $('#tipo_hist').val(),
        valor_min: valorMin
    };

    $.ajax({
        url: 'paginas/' + pag + "/listar-historico.php",
        method: 'POST',
        data: filtroData,
        dataType: "json",
        success: function(historico) {
            renderizarHistorico(historico);
        },
        error: function() {
            container.html('<div class="alert alert-danger">Ocorreu um erro ao carregar o histórico. Tente novamente.</div>');
        }
    });
}

function limparFiltrosHistorico() {
    $('#form-filtros-historico')[0].reset();
    aplicarFiltroHistorico();
}

// Evento de SUBMIT do formulário de filtros
$("#form-filtros-historico").submit(function (event) {
    event.preventDefault(); // Impede o recarregamento da página
    aplicarFiltroHistorico();
});

$('#valor_adiant').on('keyup', function() {
    // Pega o limite que armazenamos no campo usando .data()
    const limite = $(this).data('limite');
    if (limite === undefined) return; // Se não houver limite definido, não faz nada

    // Pega o valor digitado e o converte para um número
    // (remove pontos de milhar e substitui a vírgula por ponto decimal)
    let valorDigitadoStr = $(this).val().replace(/\./g, '').replace(',', '.');
    const valorDigitado = parseFloat(valorDigitadoStr);

    // Se o valor não for um número válido, não faz nada
    if (isNaN(valorDigitado)) {
        $(this).removeClass('is-invalid');
        return;
    }

    // Compara o valor digitado com o limite
    if (valorDigitado > limite) {
        // Se ultrapassou, adiciona a classe do Bootstrap que deixa o campo vermelho
        $(this).addClass('is-invalid');
    } else {
        // Se estiver dentro do limite, remove a classe
        $(this).removeClass('is-invalid');
    }
});

function abrirModalHistorico(id, nome) {
    // 1. Prepara o modal
    $('#nome-historico').text(nome);
    $('#id_funcionario_hist').val(id);
    
    // 2. Limpa os filtros de uma sessão anterior
    $('#form-filtros-historico')[0].reset();

    // 3. Mostra o modal
    var modal = new bootstrap.Modal(document.getElementById('modalHistorico'));
    modal.show();

    // 4. Busca os dados iniciais (sem filtros)
    aplicarFiltroHistorico();
}

    // ======= SUBMISSÃO DOS FORMS VIA AJAX =======

    $("#form-gratificacao").submit(function (event) {
        event.preventDefault();
        var formData = new FormData(this);
        const msgDiv = $('#mensagem-gratificacao');

        $.ajax({
            url: 'paginas/' + pag + "/gratificacao.php",
            type: 'POST',
            data: formData,
            success: function (mensagem) {
                msgDiv.text('');
                msgDiv.removeClass();
                if (mensagem.trim() == "Salvo com Sucesso") {
                    // Fechar o modal e atualizar a lista principal
                    var modal = bootstrap.Modal.getInstance(document.getElementById('modalGratificacao'));
                    modal.hide();
                    listar(); // Função do ajax.js para atualizar a tabela
                } else {
                    msgDiv.addClass('text-danger');
                    msgDiv.text(mensagem);
                }
            },
            cache: false,
            contentType: false,
            processData: false
        });
    });

    $("#form-adiantamento").submit(function (event) {
        event.preventDefault();
        var formData = new FormData(this);
        const msgDiv = $('#mensagem-adiantamento');

        $.ajax({
            url: 'paginas/' + pag + "/adiantamento.php",
            type: 'POST',
            data: formData,
            success: function (mensagem) {
                msgDiv.text('');
                msgDiv.removeClass();
                if (mensagem.trim() == "Salvo com Sucesso") {
                    var modal = bootstrap.Modal.getInstance(document.getElementById('modalAdiantamento'));
                    modal.hide();
                    listar();
                } else {
                    msgDiv.addClass('text-danger');
                    msgDiv.text(mensagem);
                }
            },
            cache: false,
            contentType: false,
            processData: false
        });
    });

</script>