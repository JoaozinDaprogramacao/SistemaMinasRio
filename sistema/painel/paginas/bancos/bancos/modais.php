<div class="modal fade" id="modalForm" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title" id="exampleModalLabel"><span id="titulo_inserir"></span></h4>
                <button id="btn-fechar" aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"><span class="text-white" aria-hidden="true">&times;</span></button>
            </div>
            <form id="form" onsubmit="return validarForm()">
                <div class="modal-body">


                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label>Correntista *</label>
                            <input type="text" class="form-control" id="correntista" name="correntista"
                                placeholder="Nome do Correntista" maxlength="100">
                        </div>

                        <div class="col-md-6 mb-2">
                            <label>Banco *</label>
                            <input type="text" class="form-control" id="banco" name="banco"
                                placeholder="Nome do Banco" maxlength="50">
                        </div>

                        <div class="col-md-4 mb-2">
                            <label>Agência *</label>
                            <input type="text" class="form-control" id="agencia" name="agencia"
                                placeholder="0000-0" maxlength="6">
                        </div>

                        <div class="col-md-4 mb-2">
                            <label>Conta *</label>
                            <input type="text" class="form-control" id="conta" name="conta"
                                placeholder="00000000-0" maxlength="10">
                        </div>

                        <div class="col-md-4 mb-2">
                            <label>Saldo R$ *</label>
                            <input type="text" class="form-control" id="saldo" name="saldo"
                                placeholder="R$ 0,00">
                        </div>

                    </div>



                    <input type="hidden" class="form-control" id="id" name="id">

                    <br>
                    <small>
                        <div id="mensagem" align="center"></div>
                    </small>
                </div>
                <div class="modal-footer">
                    <button id="btn_salvar" type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

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