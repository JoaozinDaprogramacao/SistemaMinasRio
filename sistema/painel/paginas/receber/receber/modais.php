<!-- Modal Dados -->
<div class="modal fade" id="modalDados" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title" id="exampleModalLabel"><span id="titulo_dados"></span></h4>
                <button id="btn-fechar-dados" aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"><span class="text-white" aria-hidden="true">&times;</span></button>
            </div>

            <div class="modal-body">


                <div class="row">


                    <div class="col-md-6">
                        <div class="tile">
                            <div class="table-responsive">
                                <table id="" class="text-left table table-bordered">
                                    <tr>
                                        <td class="bg-warning alert-warning">Cliente</td>
                                        <td><span id="cliente_dados"></span></td>
                                    </tr>

                                    <tr>
                                        <td class="bg-warning alert-warning">Vencimento</td>
                                        <td><span id="vencimento_dados"></span></td>
                                    </tr>

                                    <tr>
                                        <td class="bg-warning alert-warning w_150">Pagamento</td>
                                        <td><span id="data_pgto_dados"></span></td>
                                    </tr>


                                    <tr>
                                        <td class="bg-warning alert-warning w_150">Frequência</td>
                                        <td><span id="frequencia_dados"></span></td>
                                    </tr>
                                    <tr>
                                        <td class="bg-warning alert-warning w_150">Multa</td>
                                        <td><span id="multa_dados"></span></td>
                                    </tr>

                                    <tr>
                                        <td class="bg-warning alert-warning w_150">Júros</td>
                                        <td><span id="juros_dados"></span></td>
                                    </tr>

                                    <tr>
                                        <td class="bg-warning alert-warning w_150">Desconto</td>
                                        <td><span id="desconto_dados"></span></td>
                                    </tr>

                                    <tr>
                                        <td class="bg-warning alert-warning w_150">Taxa</td>
                                        <td><span id="taxa_dados"></span></td>
                                    </tr>


                                    <tr>
                                        <td class="bg-warning alert-warning w_150">Subtotal</td>
                                        <td><span id="total_dados"></span></td>
                                    </tr>





                                </table>
                            </div>
                        </div>
                    </div>



                    <div class="col-md-6">
                        <div class="tile">
                            <div class="table-responsive">
                                <table id="" class="text-left table table-bordered">

                                    <tr>
                                        <td class="bg-warning alert-warning w_150">Pago</td>
                                        <td><span id="pago_dados"></span></td>
                                    </tr>

                                    <tr>
                                        <td class="bg-warning alert-warning w_150">Lançado Por</td>
                                        <td><span id="usu_lanc_dados"></span></td>
                                    </tr>


                                    <tr>
                                        <td class="bg-warning alert-warning w_150">Baixa Usuário</td>
                                        <td><span id="usu_pgto_dados"></span></td>
                                    </tr>


                                    <tr>
                                        <td class="bg-warning alert-warning w_150">OBS</td>
                                        <td><span id="obs_dados"></span></td>
                                    </tr>


                                    <tr>
                                        <td align="center"><img src="" id="target_dados" width="200px"></td>
                                    </tr>

                                </table>
                            </div>
                        </div>
                    </div>

                </div>





            </div>

        </div>
    </div>
</div>




<div class="modal fade" id="modalParcelar" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title" id="tituloModal">Parcelar Conta: <span id="nome-parcelar"> </span></h4>
                <button id="btn-fechar-parcelar" aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"><span class="text-white" aria-hidden="true">&times;</span></button>
            </div>
            <form method="post" id="form-parcelar">
                <div class="modal-body">


                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label>Valor</label>
                                <input type="text" class="form-control" name="valor-parcelar" id="valor-parcelar" readonly>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="mb-3">
                                <label>Parcelas</label>
                                <input type="number" class="form-control" name="qtd-parcelar" id="qtd-parcelar" required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Frequência Parcelas</label>
                                <select class="form-select" name="frequencia" id="frequencia-parcelar" required style="width:100%;">

                                    <?php
                                    $query = $pdo->query("SELECT * FROM frequencias order by id asc");
                                    $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                    for ($i = 0; $i < @count($res); $i++) {
                                        foreach ($res[$i] as $key => $value) {
                                        }
                                        $id_item = $res[$i]['id'];
                                        $nome_item = $res[$i]['frequencia'];
                                        $dias = $res[$i]['dias'];

                                        if ($nome_item != 'Uma Vez' and $nome_item != 'Única' and $nome_item != 'Nenhuma') {

                                    ?>
                                            <option <?php if ($nome_item == 'Mensal') { ?> selected <?php } ?> value="<?php echo $dias ?>"><?php echo $nome_item ?></option>

                                    <?php }
                                    } ?>


                                </select>
                            </div>
                        </div>

                        <div class="col-md-3" style="margin-top:25px">
                            <button type="submit" class="btn btn-primary">Parcelar</button>
                        </div>

                    </div>



                    <br>
                    <input type="hidden" name="id-parcelar" id="id-parcelar">
                    <input type="hidden" name="nome-parcelar" id="nome-input-parcelar">
                    <small>
                        <div id="mensagem-parcelar" align="center" class="mt-3"></div>
                    </small>

                </div>

                <div class="modal-footer">

                </div>

            </form>

        </div>
    </div>
</div>

<div class="modal fade" id="modalBaixar" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white py-2">
                <h5 class="modal-title" id="tituloModal">Baixar Conta: <span id="descricao-baixar" class="fw-light"></span></h5>
                <button id="btn-fechar-baixar" aria-label="Close" class="btn-close btn-close-white" data-bs-dismiss="modal" type="button"></button>
            </div>
            <form id="form-baixar" method="post">
                <div class="modal-body bg-white">

                    <div class="row g-2 mb-3 pb-3 border-bottom">
                        <div class="col-md-5">
                            <label class="text-uppercase fw-bold text-muted small">Cliente</label>
                            <input type="text" class="form-control form-control-sm bg-light border-0" id="cliente-baixar" readonly>
                        </div>
                        <div class="col-md-2">
                            <label class="text-uppercase fw-bold text-muted small">ID Romaneio</label>
                            <input type="text" class="form-control form-control-sm bg-light border-0" id="romaneio-baixar" readonly>
                        </div>
                        <div class="col-md-2">
                            <label class="text-uppercase fw-bold text-muted small">Valor Título</label>
                            <input type="text" class="form-control form-control-sm bg-light border-0 fw-bold" id="valor-original-baixar" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="text-uppercase fw-bold text-muted small">Vencimento</label>
                            <input type="text" class="form-control form-control-sm bg-light border-0" id="vencimento-baixar" readonly>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="fw-bold mb-1">Valor Recebido</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-muted">R$</span>
                                <input onkeyup="totalizar()" type="text" class="form-control form-control-lg fw-bold" name="valor-baixar" id="valor-baixar" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-bold mb-1">Data Baixa</label>
                            <input type="date" class="form-control form-control-lg" name="data-baixar" id="data-baixar" value="<?php echo date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="fw-bold mb-1 text-primary">Subtotal Líquido</label>
                            <input type="text" class="form-control form-control-lg fw-bold bg-light border-primary text-primary" name="subtotal" id="subtotal" readonly>
                        </div>
                    </div>

                    <div class="row g-2 mb-4 p-3 rounded border bg-light">
                        <div class="col-md-3">
                            <label class="small text-muted fw-bold">Multa (+)</label>
                            <input onkeyup="totalizar()" type="text" class="form-control form-control-sm" name="valor-multa" id="valor-multa" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="small text-muted fw-bold">Juros (+)</label>
                            <input onkeyup="totalizar()" type="text" class="form-control form-control-sm" name="valor-juros" id="valor-juros" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="small text-muted fw-bold">Desconto (-)</label>
                            <input onkeyup="totalizar()" type="text" class="form-control form-control-sm" name="valor-desconto" id="valor-desconto" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="small text-muted fw-bold">Taxas (-)</label>
                            <input onkeyup="totalizar()" type="text" class="form-control form-control-sm" name="valor-taxa" id="valor-taxa" value="0">
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="small fw-bold text-secondary">Forma Pagamento</label>
                            <select class="form-select shadow-sm" name="saida-baixar" id="saida-baixar" required onchange="calcularTaxa()">
                                <?php
                                $query = $pdo->query("SELECT * FROM formas_pgto order by id asc");
                                $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                for ($i = 0; $i < @count($res); $i++) { ?>
                                    <option value="<?php echo $res[$i]['id'] ?>"><?php echo $res[$i]['nome'] ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold text-secondary">Banco Destino</label>
                            <select class="form-select shadow-sm" name="banco" id="banco" required onchange="calcularTaxa()">
                                <option value="">Selecione...</option>
                                <?php
                                $query = $pdo->query("SELECT * FROM bancos order by id asc");
                                $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                for ($i = 0; $i < @count($res); $i++) { ?>
                                    <option value="<?php echo $res[$i]['id'] ?>"><?php echo $res[$i]['banco'] ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold text-secondary">Classificação</label>
                            <select class="form-select shadow-sm" name="descricao_banco" id="descricao_banco">
                                <option value="">Nenhuma</option>
                                <?php
                                $query_class = $pdo->query("SELECT * FROM descricao_banco order by descricao asc");
                                $res_class = $query_class->fetchAll(PDO::FETCH_ASSOC);
                                for ($i = 0; $i < @count($res_class); $i++) { ?>
                                    <option value="<?php echo $res_class[$i]['id'] ?>"><?php echo $res_class[$i]['descricao'] ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <input type="hidden" name="id-baixar" id="id-baixar">

                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="submit" class="btn btn-success px-5 fw-bold shadow-sm">Confirmar Baixa</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalResiduos" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title" id="tituloModal">Residuos da Conta</h4>
                <button id="btn-fechar-residuos" aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"><span class="text-white" aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">

                <small>
                    <div id="listar-residuos"></div>
                </small>

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
                                <img src="images/arquivos/sem-foto.png" width="60px" id="target-arquivos">
                            </div>
                        </div>




                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <input type="text" class="form-control" name="nome-arq" id="nome-arq" placeholder="Nome do Arquivo * " required>
                        </div>

                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary">Inserir</button>
                        </div>
                    </div>

                    <hr>

                    <small>
                        <div id="listar-arquivos"></div>
                    </small>

                    <br>
                    <small>
                        <div align="center" id="mensagem-arquivo"></div>
                    </small>

                    <input type="hidden" class="form-control" name="id-arquivo" id="id-arquivo">


                </div>
            </form>
        </div>
    </div>
</div>

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
                        <div class="col-md-5 mb-2 col-8">
                            <label>Descrição</label>
                            <input type="text" class="form-control" id="descricao" name="descricao" placeholder="Descrição">
                        </div>

                        <div class="col-md-2 col-4">
                            <label>Valor</label>
                            <input type="text" class="form-control" id="valor" name="valor" placeholder="" required>
                        </div>

                        <div class="col-md-5">
                            <label>Cliente</label>
                            <select name="cliente" id="cliente" class="sel2" style="width:100%; height:35px; ">
                                <option value="0">Selecione um Cliente</option>
                                <?php
                                if ($mostrar_registros == 'Não') {
                                    $query = $pdo->query("SELECT * from clientes where usuario = '$id_usuario' order by id asc");
                                } else {
                                    $query = $pdo->query("SELECT * from clientes order by id asc");
                                }
                                $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                $linhas = @count($res);
                                if ($linhas > 0) {
                                    for ($i = 0; $i < $linhas; $i++) {
                                        echo '<option value="' . $res[$i]['id'] . '">' . $res[$i]['nome'] . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>


                    </div>


                    <div class="row">




                        <div class="col-md-3 mb-2 col-6">
                            <label>Vencimento</label>
                            <input type="date" name="vencimento" id="vencimento" value="<?php echo $data_atual ?>" class="form-control">
                        </div>


                        <div class="col-md-3 col-6">
                            <label>Pago Em</label>
                            <input type="date" name="data_pgto" id="data_pgto" value="" class="form-control">
                        </div>


                        <div class="col-md-3 col-6">
                            <label>Forma Pgto</label>
                            <select name="forma_pgto" id="forma_pgto" class="form-select">
                                <?php
                                $query = $pdo->query("SELECT * from formas_pgto order by id asc");
                                $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                $linhas = @count($res);
                                if ($linhas > 0) {
                                    for ($i = 0; $i < $linhas; $i++) {
                                        echo '<option value="' . $res[$i]['id'] . '">' . $res[$i]['nome'] . '</option>';
                                    }
                                } else {
                                    echo '<option value="0">Cadastre uma Forma de Pagamento</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-md-3 col-6 mb-2">
                            <label>Frequência</label>
                            <select name="frequencia" id="frequencia" class="form-select">
                                <?php
                                $query = $pdo->query("SELECT * from frequencias order by id asc");
                                $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                $linhas = @count($res);
                                if ($linhas > 0) {
                                    for ($i = 0; $i < $linhas; $i++) {
                                        echo '<option value="' . $res[$i]['dias'] . '">' . $res[$i]['frequencia'] . '</option>';
                                    }
                                } else {
                                    echo '<option value="0">Cadastre uma Forma de Pagamento</option>';
                                }
                                ?>
                            </select>
                        </div>



                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label>Observações</label>
                            <input type="text" class="form-control" id="obs" name="obs" placeholder="Observações">
                        </div>

                        <div class="col-md-4 mb-2">
                            <label>Arquivo</label>
                            <input type="file" class="form-control" id="arquivo" name="foto" onchange="carregarImg()">
                        </div>

                        <div class="col-md-2">
                            <img width="80px" id="target">

                        </div>

                    </div>
                    <div class="row d-none" id="div-banco">
                        <div class="col-md-6 mb-2">
                            <label>Banco</label>
                            <select class="form-select" name="banco" id="banco" onchange="calcularTaxa()">
                                <option value="">Selecione</option>
                                <?php
                                $query = $pdo->query("SELECT * FROM bancos order by id asc");
                                $res = $query->fetchAll(PDO::FETCH_ASSOC);
                                for ($i = 0; $i < @count($res); $i++) {
                                    foreach ($res[$i] as $key => $value) {
                                    }

                                ?>
                                    <option value="<?php echo $res[$i]['id'] ?>"><?php echo $res[$i]['banco'] ?></option>

                                <?php } ?>

                            </select>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Descrição (Banco)</label>
                            <select class="form-select" name="descricao_banco" id="descricao_banco">
                                <option value="">Selecione a Classificação</option>
                                <?php
                                // Correção: Removido o ponto e vírgula antes do "order by"
                                $query_class = $pdo->query("SELECT * FROM descricao_banco order by descricao asc");
                                $res_class = $query_class->fetchAll(PDO::FETCH_ASSOC);
                                for ($i = 0; $i < @count($res_class); $i++) {
                                ?>
                                    <option value="<?php echo $res_class[$i]['id'] ?>">
                                        <?php echo $res_class[$i]['descricao'] ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>







                    <input type="hidden" class="form-control" id="id" name="id">

                    <br>
                    <small>
                        <div id="mensagem" align="center"></div>
                    </small>
                </div>
                <div class="modal-footer">
                    <button id="btn-baixar-modal" type="button" class="btn btn-outline-success" style="display:none;" title="Baixar Conta">
                        <i class="fa fa-check-square"></i> Baixar
                    </button>

                    <button type="submit" class="btn btn-success">Salvar</button>
                </div>
        </div>
    </div>
</div>