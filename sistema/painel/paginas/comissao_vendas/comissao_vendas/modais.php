<div class="modal fade" id="modalDados" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content" style="border-top: 5px solid #2b7a00;">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">
                    Romaneio: <span id="id_dados"></span>
                    <span class="ml-3" style="font-size: 0.9rem; color: #666;">Data: <span id="data_dados"></span></span>
                </h5>
                <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body" style="background-color: #fdfdfd;">
                <div class="row mb-4">
                    <div class="col-md-4">
                        <small class="text-muted">Cliente/Atacadista:</small><br>
                        <span id="cliente_dados" class="font-weight-bold" style="color: #1e5600;"></span>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted">Nota Fiscal:</small><br>
                        <span id="nota_fiscal_dados" class="font-weight-bold"></span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Plano de Pagamento:</small><br>
                        <span id="plano_pgto_dados" class="font-weight-bold"></span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Vencimento:</small><br>
                        <span id="vencimento_dados" class="font-weight-bold"></span>
                    </div>
                </div>

                <hr>

                <h6 class="font-weight-bold mb-2" style="color: #2b7a00;">
                    <i class="fas fa-box-open mr-2"></i>Comissões do Romaneio
                </h6>
                <div id="itens_dados" class="mb-4"></div>

                <h6 class="font-weight-bold mb-2" style="color: #2b7a00;">
                    <i class="fas fa-dolly mr-2"></i>Materiais Detalhados
                </h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm" style="font-size: 0.85rem;">
                        <thead>
                            <tr class="text-center" style="background-color: #2b7a00; color: #ffffff;">
                                <th class="text-left" style="background-color: #1e5600; min-width: 200px;">MATERIAL</th>
                                <th class="text-left">OBSERVAÇÕES</th>
                                <th width="10%">QTD</th>
                                <th width="15%">PREÇO UNIT</th>
                                <th width="15%" style="background-color:#1e5600;">VALOR</th>
                            </tr>
                        </thead>
                        <tbody id="corpo_materiais_detalhado"></tbody>
                    </table>
                </div>

                <h6 class="font-weight-bold mb-2" style="color: #2b7a00;">
                    <i class="fas fa-calculator mr-2"></i>Resumo Financeiro Consolidado
                </h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr class="text-center" style="background-color: #444; color: #fff; font-size: 0.85rem;">
                                <th width="25%">TOTAL MATERIAL</th>
                                <th width="25%">COMISSÃO + MATERIAL</th>
                                <th width="25%">TOTAL BANANA LÍQUIDO</th>
                                <th width="25%" style="background-color: #1e5600;">TOTAL LÍQUIDO A RECEBER</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr id="resumo_consolidado_dados" class="font-weight-bold">
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="row justify-content-end">
                    <div class="col-md-6">
                        <table class="table table-bordered table-sm">
                            <tr style="background-color: #f8f9fa;">
                                <td>Acréscimos (<span id="descricao_a_dados"></span>)</td>
                                <td class="text-right font-weight-bold text-primary" id="adicional_dados"></td>
                            </tr>
                            <tr style="background-color: #f8f9fa;">
                                <td>Descontos (<span id="descricao_d_dados"></span>)</td>
                                <td class="text-right font-weight-bold text-danger" id="desconto_dados"></td>
                            </tr>
                            <tr style="background-color: #e2efda; font-size: 1.2rem;">
                                <td class="font-weight-bold" style="color: #1e5600;">VALOR FINAL DO ROMANEIO</td>
                                <td class="text-right font-weight-bold" id="total_liquido_dados_footer" style="color: #1e5600;"></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>