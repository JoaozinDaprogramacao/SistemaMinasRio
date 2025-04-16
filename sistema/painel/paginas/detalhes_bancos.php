<?php
$pag = 'detalhes_bancos';

//verificar se ele tem a permissão de estar nessa página
if (@$produtos == 'ocultar') {
	echo "<script>window.location='../index.php'</script>";
	exit();
}

// Buscar os totais do banco de dados
$id_banco = @$_GET['id']; // Pega o ID do banco da URL

try {
    if(!empty($id_banco)) {
        $query = $pdo->query("SELECT 
            SUM(credito) as total_creditos,
            SUM(debito) as total_debitos,
            (SELECT saldo FROM linha_bancos WHERE id_banco = '$id_banco' ORDER BY id DESC LIMIT 1) as saldo_total
        FROM linha_bancos 
        WHERE id_banco = '$id_banco'");
    } else {
        $query = $pdo->query("SELECT 
            SUM(credito) as total_creditos,
            SUM(debito) as total_debitos,
            (SELECT saldo FROM linha_bancos ORDER BY id DESC LIMIT 1) as saldo_total
        FROM linha_bancos");
    }
    
    $res = $query->fetch(PDO::FETCH_ASSOC);

    $total_creditos = $res['total_creditos'] ?? 0;	
    $total_debitos = $res['total_debitos'] ?? 0; 
    $saldo_total = $res['saldo_total'] ?? 0;

    // Formatando os valores
    $total_creditosF = number_format($total_creditos, 2, ',', '.');
    $total_debitosF = number_format($total_debitos, 2, ',', '.');
    $saldo_totalF = number_format($saldo_total, 2, ',', '.');
} catch(PDOException $e) {
    // Em caso de erro, define valores zerados
    $total_creditos = 0;
    $total_debitos = 0;
    $saldo_total = 0;
    $total_creditosF = '0,00';
    $total_debitosF = '0,00';
    $saldo_totalF = '0,00';
}
?>

<div class="justify-content-between">
	<div class="left-content mt-2 mb-3">
		<div class="dropdown" style="display: inline-block;">
			<a href="#" aria-expanded="false" aria-haspopup="true" data-bs-toggle="dropdown" class="btn btn-danger dropdown" id="btn-deletar" style="display:none"><i class="fe fe-trash-2"></i> Deletar</a>
			<div class="dropdown-menu tx-13">
				<div style="width: 240px; padding:15px 5px 0 10px;" class="dropdown-item-text">
					<p>Excluir Selecionados? <a href="#" onclick="deletarSel()"><span class="text-danger">Sim</span></a></p>
				</div>
			</div>
		</div>



	</div>


	<form action="rel/produtos_class.php" target="_blank" method="POST">
		<input type="hidden" name="cat" id="cat">
		<div style=" position:absolute; right:10px; margin-bottom: 10px; top:70px">
			<button style="width:40px" type="submit" class="btn btn-danger ocultar_mobile_app" title="Gerar Relatório"><i class="fa fa-file-pdf-o"></i></button>
		</div>
	</form

</div>

<div class="card-group mb-4" style="margin-top: 50px">
    <div class="card text-center" style="width: 100%; margin-right: 10px; border-radius: 10px; height:80px">
        <div class="card-header bg-success border-light text-white" style="padding: 8px">
            Créditos
            <i class="fa fa-arrow-up pull-right"></i>
        </div>
        <div class="card-body" style="padding: 8px">
            <p class="card-text" style="margin-top:-5px;">
                <h5><span class="text-success" id="total_creditos">R$ <?php echo $total_creditosF; ?></span></h5>
            </p>
        </div>
    </div>

    <div class="card text-center" style="width: 100%; margin-right: 10px; border-radius: 10px; height:80px">
        <div class="card-header bg-danger border-light text-white" style="padding: 8px">
            Débitos
            <i class="fa fa-arrow-down pull-right"></i>
        </div>
        <div class="card-body" style="padding: 8px">
            <p class="card-text" style="margin-top:-5px;">
                <h5><span class="text-danger" id="total_debitos">R$ <?php echo $total_debitosF; ?></span></h5>
            </p>
        </div>
    </div>

    <div class="card text-center" style="width: 100%; margin-right: 10px; border-radius: 10px; height:80px">
        <div class="card-header <?php echo ($saldo_total >= 0) ? 'bg-success' : 'bg-danger'; ?> border-light text-white" style="padding: 8px">
            Saldo Total
            <i class="fa fa-balance-scale pull-right"></i>
        </div>
        <div class="card-body" style="padding: 8px">
            <p class="card-text" style="margin-top:-5px;">
                <h5><span class="<?php echo ($saldo_total >= 0) ? 'text-success' : 'text-danger'; ?>" id="saldo_total">R$ <?php echo $saldo_totalF; ?></span></h5>
            </p>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <div>
            <button type="button" class="btn btn-primary btn-sm" onclick="toggleFiltros()">
                <i class="fa fa-filter"></i> Filtros
                <i class="fa fa-chevron-down ms-2" id="icone-filtro"></i>
            </button>
        </div>
    </div>
    
    <div id="filtros-container" style="display: none;">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-2">
                    <label>Data Inicial</label>
                    <input type="date" class="form-control form-control-sm" id="data_inicio" onchange="aplicarFiltros()">
                </div>
                
                <div class="col-md-2">
                    <label>Data Final</label>
                    <input type="date" class="form-control form-control-sm" id="data_fim" onchange="aplicarFiltros()">
                </div>

                <div class="col-md-2">
                    <label>Tipo</label>
                    <select class="form-select form-select-sm" id="tipo_movimento" onchange="aplicarFiltros()">
                        <option value="">Todos</option>
                        <option value="credito">Créditos</option>
                        <option value="debito">Débitos</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label>Valor Mínimo</label>
                    <input type="text" class="form-control form-control-sm" id="valor_min" onkeyup="mascara_valor(this)" onchange="aplicarFiltros()">
                </div>

                <div class="col-md-2">
                    <label>Valor Máximo</label>
                    <input type="text" class="form-control form-control-sm" id="valor_max" onkeyup="mascara_valor(this)" onchange="aplicarFiltros()">
                </div>
            </div>

            <div class="row mt-2">
                <div class="col-12 text-end">
                    <button onclick="limparFiltros()" class="btn btn-sm btn-light">
                        <i class="fa fa-eraser"></i> Limpar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$query = $pdo->query("SELECT * from bancos order by banco asc");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);
if ($linhas > 0) {
?>
	<ul class="nav nav-tabs" id="myTab" role="tablist" style="background: #FFF">


		<li class="nav-item" role="presentation">
			<a onclick="buscarCat('')" class="nav-link active" data-bs-toggle="tab" data-bs-target="#home" type="button" role="tab" aria-controls="home" aria-selected="true">Todos</a>
		</li>

		<?php
		for ($i = 0; $i < $linhas; $i++) {
			$id = $res[$i]['id'];
			$nome = $res[$i]['banco'];

		?>

			<li class="nav-item" role="presentation">
				<a onclick="buscarCat('<?php echo $id ?>')" class="nav-link " id="home-tab" data-bs-toggle="tab" data-bs-target="#home" type="button" role="tab" aria-controls="home" aria-selected="true"><?php echo $nome ?></a>
			</li>
		<?php } ?>
	</ul>
<?php } ?>


<div class="row row-sm">
	<div class="col-lg-12">
		<div class="card custom-card">
			<div class="card-body" id="listar">

			</div>
		</div>
	</div>
</div>

<input type="hidden" id="ids">

<script src="paginas/js/<?php echo $pag; ?>/materiais.js" defer></script>

<!-- Modal Saida-->
<div class="modal fade" id="modalSaida" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header bg-primary text-white">
				<h4 class="modal-title" id="exampleModalLabel"><span id="nome_saida"></span></h4>
				<button id="btn-fechar-saida" aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"><span class="text-white" aria-hidden="true">&times;</span></button>
			</div>

			<div class="modal-body">
				<form id="form-saida">

					<div class="row">
						<div class="col-md-4">
							<div class="form-group">

								<input type="text" class="form-control" id="quantidade_saida" name="quantidade_saida" placeholder="Quantidade Saída" required onkeyup="mascara_decimal('quantidade_saida')">
							</div>
						</div>

						<div class="col-md-5">
							<div class="form-group">
								<input type="text" class="form-control" id="motivo_saida" name="motivo_saida" placeholder="Motivo Saída" required>
							</div>
						</div>
						<div class="col-md-3">
							<button type="submit" class="btn btn-primary">Salvar</button>

						</div>
					</div>

					<input type="hidden" id="id_saida" name="id">
					<input type="hidden" id="estoque_saida" name="estoque">

				</form>

				<br>
				<small>
					<div id="mensagem-saida" align="center"></div>
				</small>
			</div>


		</div>
	</div>
</div>





<!-- Modal Entrada-->
<div class="modal fade" id="modalEntrada" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header bg-primary text-white">
				<h4 class="modal-title" id="exampleModalLabel"><span id="nome_entrada"></span></h4>
				<button id="btn-fechar-entrada" aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"><span class="text-white" aria-hidden="true">&times;</span></button>
			</div>

			<div class="modal-body">
				<form id="form-entrada">

					<div class="row">
						<div class="col-md-4">
							<div class="form-group">

								<input type="text" class="form-control" id="quantidade_entrada" name="quantidade_entrada" placeholder="Quantidade Entrada" required onkeyup="mascara_decimal('quantidade_entrada')">
							</div>
						</div>

						<div class="col-md-5">
							<div class="form-group">
								<input type="text" class="form-control" id="motivo_entrada" name="motivo_entrada" placeholder="Motivo Entrada" required>
							</div>
						</div>
						<div class="col-md-3">
							<button type="submit" class="btn btn-primary">Salvar</button>

						</div>
					</div>

					<input type="hidden" id="id_entrada" name="id">
					<input type="hidden" id="estoque_entrada" name="estoque">

				</form>

				<br>
				<small>
					<div id="mensagem-entrada" align="center"></div>
				</small>
			</div>


		</div>
	</div>
</div>




<script type="text/javascript">
	var pag = "<?= $pag ?>"
</script>
<script src="js/ajax.js"></script>

<script type="text/javascript">
	$(document).ready(function() {
		$('.sel2').select2({
			dropdownParent: $('#modalForm')
		});
	});
</script>


<script type="text/javascript">
	function carregarImg() {
		var target = document.getElementById('target');
		var file = document.querySelector("#foto").files[0];

		var reader = new FileReader();

		reader.onloadend = function() {
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
	function buscarCat(id) {
		$('#cat').val(id);
		aplicarFiltros();
		$.ajax({
			url: 'paginas/detalhes_bancos/buscar_totais.php',
			method: 'POST',
			data: {id_banco: id},
			dataType: "json",
			success: function(result){
				// Atualiza os valores nos cards
				$('#total_creditos').html('R$ ' + result.total_creditos);
				$('#total_debitos').html('R$ ' + result.total_debitos);
				$('#saldo_total').html('R$ ' + result.saldo_total);
				
				// Atualiza as classes de cores baseado no saldo
				if(parseFloat(result.saldo_total.replace(',','.')) >= 0){
					$('#saldo_total').removeClass('text-danger').addClass('text-success');
					$('#saldo_total').parent().parent().parent().find('.card-header')
						.removeClass('bg-danger').addClass('bg-success');
				} else {
					$('#saldo_total').removeClass('text-success').addClass('text-danger');
					$('#saldo_total').parent().parent().parent().find('.card-header')
						.removeClass('bg-success').addClass('bg-danger');
				}
			}
		});
	}
</script>



<script type="text/javascript">
	$("#form_itens").submit(function() {

		event.preventDefault();
		var formData = new FormData(this);

		$('#mensagem').text('Salvando...')
		$('#btn_salvar').hide();

		$.ajax({
			url: 'paginas/' + pag + "/salvar.php",
			type: 'POST',
			data: formData,

			success: function(mensagem) {
				$('#mensagem').text('');
				$('#mensagem').removeClass()
				if (mensagem.trim() == "Salvo com Sucesso") {

					$('#btn-fechar').click();
					var id = $('#cat').val()
					listar(id)

					$('#mensagem').text('')

				} else {

					$('#mensagem').addClass('text-danger')
					$('#mensagem').text(mensagem)
				}

				$('#btn_salvar').show();

			},

			cache: false,
			contentType: false,
			processData: false,

		});

	});
</script>




<script type="text/javascript">
	$("#form-saida").submit(function() {

		event.preventDefault();
		var formData = new FormData(this);

		$.ajax({
			url: 'paginas/' + pag + "/saida.php",
			type: 'POST',
			data: formData,

			success: function(mensagem) {
				$('#mensagem-saida').text('');
				$('#mensagem-saida').removeClass()
				if (mensagem.trim() == "Salvo com Sucesso") {

					$('#btn-fechar-saida').click();
					listar();

				} else {

					$('#mensagem-saida').addClass('text-danger')
					$('#mensagem-saida').text(mensagem)
				}


			},

			cache: false,
			contentType: false,
			processData: false,

		});

	});
</script>





<script type="text/javascript">
	$("#form-entrada").submit(function() {

		event.preventDefault();
		var formData = new FormData(this);

		$.ajax({
			url: 'paginas/' + pag + "/entrada.php",
			type: 'POST',
			data: formData,

			success: function(mensagem) {
				$('#mensagem-entrada').text('');
				$('#mensagem-entrada').removeClass()
				if (mensagem.trim() == "Salvo com Sucesso") {

					$('#btn-fechar-entrada').click();
					listar();

				} else {

					$('#mensagem-entrada').addClass('text-danger')
					$('#mensagem-entrada').text(mensagem)
				}


			},

			cache: false,
			contentType: false,
			processData: false,

		});

	});
</script>

<script>
function toggleFiltros() {
    const container = $('#filtros-container');
    const icone = $('#icone-filtro');
    container.slideToggle(300, function() {
        icone.toggleClass('fa-chevron-down fa-chevron-up');
    });
}

function mascara_valor(input) {
    var valor = input.value.replace(/\D/g, '');
    valor = (valor/100).toFixed(2);
    valor = valor.replace(".", ",");
    valor = valor.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
    valor = valor.replace(/(\d)(\d{3}),/g, "$1.$2,");
    input.value = valor;
}

function aplicarFiltros() {
    var banco_id = $('#cat').val();
    
    // Preparar valores monetários
    var valor_min = $('#valor_min').val() ? $('#valor_min').val().replace('.', '').replace(',', '.') : '';
    var valor_max = $('#valor_max').val() ? $('#valor_max').val().replace('.', '').replace(',', '.') : '';
    
    $.ajax({
        url: 'paginas/detalhes_bancos/carregar_tabela.php',
        method: 'POST',
        data: {
            p1: banco_id,
            data_inicio: $('#data_inicio').val(),
            data_fim: $('#data_fim').val(),
            tipo_movimento: $('#tipo_movimento').val(),
            valor_min: valor_min,
            valor_max: valor_max
        },
        success: function(response) {
            $('#listar').html(response);
        }
    });
}

function limparFiltros() {
    $('#data_inicio').val('');
    $('#data_fim').val('');
    $('#tipo_movimento').val('');
    $('#valor_min').val('');
    $('#valor_max').val('');
    aplicarFiltros();
}

// Inicializar filtros quando a página carregar
$(document).ready(function() {
    aplicarFiltros();
});
</script>