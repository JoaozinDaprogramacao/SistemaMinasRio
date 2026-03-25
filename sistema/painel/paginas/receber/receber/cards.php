<div class="card-group" style="margin-bottom: 10px">
    <div class="card text-center mb-3" style="border-radius: 10px; height:110px; margin-right: 10px;">
        <a class="text-white" href="#" onclick="$('#tipo_data_filtro').val('Vencidas'); $('#pago').val('Vencidas'); buscar(); ">
            <div class="card-header bg-red border-light">
                Vencidas <i class="fa fa-external-link pull-right"></i>
            </div>
            <div class="card-body">
                <h4 class="mt-n2"><span class="text-danger" id="total_vencidas">R$ 0,0</span></h4>
            </div>
        </a>
    </div>

    <div class="card text-center mb-3" style="border-radius: 10px; height:110px; margin-right: 10px;">
        <a href="#" onclick="$('#tipo_data_filtro').val('AVencer'); $('#pago').val('Não'); buscar(); ">
            <div class="card-header border-light text-white" style="background: #de5b1a ">
                A Vencer <i class="fa fa-external-link pull-right"></i>
            </div>
            <div class="card-body">
                <h4 class="mt-n2"><span style="color: #f05800" id="total_a_vencer">R$ 0,00</span></h4>
            </div>
        </a>
    </div>

    <div class="card text-center mb-3" style="border-radius: 10px; height:110px; margin-right: 10px;">
        <a href="#" onclick=" $('#tipo_data_filtro').val('Recebidas'); $('#pago').val('Sim'); buscar();">
            <div class="card-header border-light text-white" style="background: #2b7a00">
                Recebidas <i class="fa fa-external-link pull-right"></i>
            </div>
            <div class="card-body">
                <h4 class="mt-n2"><span style="color: #2b7a00" id="total_recebidas">R$ 0,0</span></h4>
            </div>
        </a>
    </div>

    <div class="card text-center mb-3" style="border-radius: 10px; height:110px;">
        <a href="#" onclick="$('#tipo_data_filtro').val('Todas'); $('#pago').val(''); buscar();">
            <div class="card-header border-light text-white" style="background: #08688c;">
                Faturamento Total <i class="fa fa-external-link pull-right"></i>
            </div>
            <div class="card-body">
                <h4 class="mt-n2"><span id="total_total">R$ 0,0</span></h4>
            </div>
        </a>
    </div>
</div>

<div class="row px-2">
    <div class="col-md-3 px-1">
        <div class="card text-center mb-3" style="border-radius: 8px; border-left: 5px solid #e74a3b; height: 60px;">
            <div class="card-body p-2">
                <small class="text-muted d-block text-uppercase font-weight-bold">Descontos (-)</small>
                <span class="text-danger" id="total_desconto" style="font-size: 1.1rem; font-weight: bold;">R$ 0,00</span>
            </div>
        </div>
    </div>

    <div class="col-md-3 px-1">
        <div class="card text-center mb-3" style="border-radius: 8px; border-left: 5px solid #f6c23e; height: 60px;">
            <div class="card-body p-2">
                <small class="text-muted d-block text-uppercase font-weight-bold">Acréscimos (+)</small>
                <span style="color: #f6c23e; font-size: 1.1rem; font-weight: bold;" id="total_acrescimo">R$ 0,00</span>
            </div>
        </div>
    </div>

    <div class="col-md-6 px-1">
        <div class="card text-center mb-3" style="border-radius: 8px; background-color: #f8f9fc; border: 1px dashed #1cc88a; height: 60px;">
            <div class="card-body p-2">
                <small class="text-success d-block text-uppercase font-weight-bold" style="font-size: 0.7rem;">Total Líquido</small>
                <span class="text-success" id="total_liquido" style="font-size: 1.3rem; font-weight: 800;">R$ 0,00</span>
            </div>
        </div>
    </div>
</div>