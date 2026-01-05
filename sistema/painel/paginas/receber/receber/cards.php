<div class="card-group" style="margin-bottom: -30px">

    <div class="card text-center mb-5" style="width: 100%; margin-right: 10px; border-radius: 10px; height:110px">
        <a class="text-white" href="#" onclick="$('#tipo_data_filtro').val('Vencidas'); $('#pago').val('Vencidas'); buscar(); ">
            <div class="card-header bg-red border-light">
                Vencidas
                <i class="fa fa-external-link pull-right"></i>
            </div>
            <div class="card-body">
                <p class="card-text" style="margin-top:-15px;">
                <h4><span class="text-danger" id="total_vencidas">R$ 0,0</span></h4>
                </p>
            </div>
        </a>
    </div>



    <div class="card text-center mb-5" style="width: 100%; margin-right: 10px; border-radius: 10px; height:110px">
        <a href="#" onclick="$('#tipo_data_filtro').val('AVencer'); $('#pago').val('Não'); buscar(); ">
            <div class="card-header border-light text-white" style="background: #de5b1a ">
                A Vencer
                <i class="fa fa-external-link pull-right"></i>
            </div>
            <div class="card-body">
                <p class="card-text" style="margin-top:-15px;">
                <h4><span style="color: #f05800" id="total_a_vencer">R$ 0,00</span></h4>
                </p>
            </div>
        </a>
    </div>



    <div class="card text-center mb-5" style="width: 100%; margin-right: 10px; border-radius: 10px; height:110px">
        <a href="#" onclick=" $('#tipo_data_filtro').val('Recebidas'); $('#pago').val('Sim'); buscar();">
            <div class="card-header border-light text-white" style="background: #2b7a00">
                Recebidas
                <i class="fa fa-external-link pull-right"></i>
            </div>
            <div class="card-body">
                <p class="card-text" style="margin-top:-15px;">
                <h4><span style="color: #2b7a00" id="total_recebidas">R$ 0,0</span></h4>
                </p>
            </div>
        </a>
    </div>


    <div class="card text-center mb-5" style="width: 100%; margin-right: 10px; border-radius: 10px; height:110px">
        <a href="#" onclick="$('#tipo_data_filtro').val('Todas'); $('#pago').val(''); buscar();">
            <div class="card-header border-light text-white" style="background: #08688c;">
                Total
                <i class="fa fa-external-link pull-right"></i>
            </div>
            <div class="card-body">
                <p class="card-text" style="margin-top:-15px;">
                <h4><span id="total_total">R$ 0,0</span></h4>
                </p>
            </div>
        </a>
    </div>



</div>