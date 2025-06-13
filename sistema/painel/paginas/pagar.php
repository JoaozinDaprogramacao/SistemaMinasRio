<?php 
require_once("verificar.php");
$pag = 'pagar';

if(@$pagar == 'ocultar'){
	echo "<script>window.location='index'</script>";
	exit();
}

?>

<div class="justify-content-between">
	<form action="rel/pagar_class.php" target="_blank" method="POST">
 	<div class="left-content mt-2 mb-3">
 <a style="margin-bottom: 10px; margin-top: 5px" class="btn ripple btn-primary text-white" onclick="inserir()" type="button"><i class="fe fe-plus me-2"></i>Adicionar Conta</a>


 <div style="display: inline-block; position:absolute; right:10px; margin-bottom: 10px">
			<button style="width:40px" type="submit" class="btn btn-danger ocultar_mobile_app" title="Gerar Relatório"><i class="fa fa-file-pdf-o"></i></button>
		</div>

		<div class="dropdown" style="display: inline-block;">                      
                        <a href="#" aria-expanded="false" aria-haspopup="true" data-bs-toggle="dropdown" class="btn btn-danger dropdown" id="btn-deletar" style="display:none"><i class="fe fe-trash-2"></i> Deletar</a>
                        <div  class="dropdown-menu tx-13">
                        <div style="width: 240px; padding:15px 5px 0 10px;" class="dropdown-item-text">
                        <p>Excluir Selecionados? <a href="#" onclick="deletarSel()"><span class="text-danger">Sim</span></a></p>
                        </div>
                        </div>
                        </div>



                 <div class="dropdown" style="display: inline-block;">                      
                        <a onclick="valorBaixar()" href="#" aria-expanded="false" aria-haspopup="true" data-bs-toggle="dropdown" class="btn btn-success dropdown" id="btn-baixar" style="display:none"><i class="fa fa-check-square"></i> Baixar</a>
                        <div  class="dropdown-menu tx-13">
                        <div style="width: 240px; padding:15px 5px 0 10px;" class="dropdown-item-text">
                       <p>Baixar contas Selecionadas? <a href="#" onclick="deletarSelBaixar()"><span class="text-verde">Sim</span></a></p>
							<p><b>Total das Contas</b> R$ <span id="total_contas"></span></p>
                        </div>
                        </div>
                        </div>


                         <div class="cab_mobile"></div>       


        
   <div class="row g-2 mb-3 align-items-center">
		<!-- Filtro de Atacadista -->
		<div class="col-auto">
			<select name="atacadista" id="atacadista" class="form-select form-select-sm" onchange="buscar()">
				<option value="">Atacadista</option>
				<?php
				$query = $pdo->query("SELECT * FROM clientes ORDER BY id DESC");
				$res = $query->fetchAll(PDO::FETCH_ASSOC);
				for ($i = 0; $i < @count($res); $i++) {
					echo '<option value="' . $res[$i]['id'] . '">' . $res[$i]['nome'] . ' - ' . $res[$i]['cpf'] . '</option>';
				}
				?>
			</select>
		</div>
		
		<!-- Filtro de Forma de Pagamento -->
		<div class="col-auto">
			<select name="formaPGTO" id="formaPGTO" class="form-select form-select-sm" onchange="buscar()">
				<option value="">Forma PGTO</option>
				<?php
				$query = $pdo->query("SELECT * FROM formas_pgto");
				$res = $query->fetchAll(PDO::FETCH_ASSOC);
				for ($i = 0; $i < @count($res); $i++) {
					echo '<option value="' . $res[$i]['id'] . '">' . $res[$i]['nome'] . '</option>';
				}
				?>
			</select>
		</div>

		<!-- Filtro de Data Inicial -->
		<div class="col-auto">
			<input type="date" name="dataInicial" id="dataInicial" class="form-control form-control-sm" value="<?php echo $data_inicio_mes ?>" onchange="buscar()">
		</div>

		<!-- Filtro de Data Final -->
		<div class="col-auto">
			<input type="date" name="dataFinal" id="dataFinal" class="form-control form-control-sm" value="<?php echo $data_final_mes ?>" onchange="buscar()">
		</div>

		<!-- Filtro de Funcionario -->
		<div class="col-auto">
			<select name="funcionario" id="funcionario" class="form-select form-select-sm" onchange="buscar()">
				<option value="">Funcionário</option>
				<?php
				$query = $pdo->query("SELECT * FROM funcionarios ORDER BY id DESC");
				$res = $query->fetchAll(PDO::FETCH_ASSOC);
				for ($i = 0; $i < @count($res); $i++) {
					echo '<option value="' . $res[$i]['id'] . '">' . $res[$i]['nome'] . '</option>';
				}
				?>
			</select>
		</div>

		<div class="col-auto">
			<select name="cargo" id="cargo" class="form-select form-select-sm" onchange="buscar()">
				<option value="">Cargo</option>
				<?php
				$query = $pdo->query("SELECT * FROM cargos ORDER BY id DESC");
				$res = $query->fetchAll(PDO::FETCH_ASSOC);
				for ($i = 0; $i < @count($res); $i++) {
					echo '<option value="' . $res[$i]['id'] . '">' . $res[$i]['nome'] . '</option>';
				}
				?>
			</select>
		</div>
	</div>
		


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
    	<a href="#" onclick="$('#tipo_data_filtro').val('Hoje'); $('#pago').val(''); buscar(); ">
			<div class="card-header bg-orange border-light text-white">
	            Vence Hoje
	            <i class="fa fa-external-link pull-right"></i>
	        </div>
	        <div class="card-body">
	        	<p class="card-text" style="margin-top:-15px;">
	        		<h4><span style="color: #f05800" id="total_hoje">R$ 0,0</span></h4>
	        	</p>
	        </div>
        </a>
    </div>


    <div class="card text-center mb-5" style="width: 100%; margin-right: 10px; border-radius: 10px; height:110px">
    	<a href="#" onclick="$('#tipo_data_filtro').val('Amanha'); $('#pago').val(''); buscar(); ">
			<div class="card-header border-light text-white" style="background: gray">
	            Vence Amanhã
	            <i class="fa fa-external-link pull-right"></i>
	        </div>
	        <div class="card-body">
	        	<p class="card-text" style="margin-top:-15px;">
	        		<h4><span style="color: gray" id="total_amanha">R$ 0,0</span></h4>
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
			<div class="card-header border-light text-white" style="background: #1f1f1f;">
	            Total
	            <i class="fa fa-external-link pull-right"></i>
	        </div>
	        <div class="card-body">
	        	<p class="card-text" style="margin-top:-15px;">
	        		<h4><span  id="total_total">R$ 0,0</span></h4>
	        	</p>
	        </div>
        </a>
    </div>


</div>



		


		</div>	

		<input type="hidden" name="tipo_data" id="tipo_data">
		<input type="hidden" name="pago" id="pago">
		<input type="hidden" name="tipo_data_filtro" id="tipo_data_filtro">

		
		</form>
		
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
						<div class="col-md-4 mb-2 col-8">							
							<label>Descrição</label>
							<input type="text" class="form-control" id="descricao" name="descricao" placeholder="Descrição" >							
						</div>

						<div class="col-md-2 col-4">							
							<label>Valor</label>
							<input type="text" class="form-control" id="valor" name="valor" placeholder="" required>							
						</div>

						<div class="col-md-3 mb-2">							
							<label>Fornecedores</label>
							<select name="fornecedor" id="fornecedor" class="sel2" style="width:100%; height:35px">
								<option value="0">Selecione um Fornecedor</option>
								<?php 
								if($mostrar_registros == 'Não'){
									$query = $pdo->query("SELECT * from fornecedores where usuario = '$id_usuario' order by id asc");
								}else{
									$query = $pdo->query("SELECT * from fornecedores order by id asc");
								}
								$res = $query->fetchAll(PDO::FETCH_ASSOC);
								$linhas = @count($res);
								if($linhas > 0){
									for($i=0; $i<$linhas; $i++){
										echo '<option value="'.$res[$i]['id'].'">'.$res[$i]['nome'].'</option>';
									}
								}
								?>	
							</select>							
						</div>


						<div class="col-md-3">							
							<label>Funcionários</label>
							<select name="funcionario" id="funcionario" class="sel2" style="width:100%; height:35px">
								<option value="0">Selecione um Funcionário</option>
								<?php 
								$query = $pdo->query("SELECT * from funcionarios order by id asc");
								$res = $query->fetchAll(PDO::FETCH_ASSOC);
								$linhas = @count($res);
								if($linhas > 0){
									for($i=0; $i<$linhas; $i++){
										echo '<option value="'.$res[$i]['id'].'">'.$res[$i]['nome'].'</option>';
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
								if($linhas > 0){
									for($i=0; $i<$linhas; $i++){
										echo '<option value="'.$res[$i]['id'].'">'.$res[$i]['nome'].'</option>';
									}
								}else{
									echo '<option value="0">Cadastre uma Forma de Pagamento</option>';
								}
								?>	
							</select>								
						</div>

						<div class="col-md-3 mb-2 col-6">							
							<label>Frequência</label>
							<select name="frequencia" id="frequencia" class="form-select">
								<?php 
								$query = $pdo->query("SELECT * from frequencias order by id asc");
								$res = $query->fetchAll(PDO::FETCH_ASSOC);
								$linhas = @count($res);
								if($linhas > 0){
									for($i=0; $i<$linhas; $i++){
										echo '<option value="'.$res[$i]['dias'].'">'.$res[$i]['frequencia'].'</option>';
									}
								}else{
									echo '<option value="0">Cadastre uma Forma de Pagamento</option>';
								}
								?>	
							</select>								
						</div>


						
					</div>

					<div class="row">
						<div class="col-md-6 mb-2">							
							<label>Observações</label>
							<input type="text" class="form-control" id="obs" name="obs" placeholder="Observações" >							
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
										for($i=0; $i < @count($res); $i++){
											foreach ($res[$i] as $key => $value){}	

												?>	
											<option value="<?php echo $res[$i]['id'] ?>"><?php echo $res[$i]['banco'] ?></option>

										<?php } ?>

									</select>
                        </div>
                    </div>

					


					<input type="hidden" class="form-control" id="id" name="id">					

					<br>
					<small><div id="mensagem" align="center"></div></small>
				</div>
				<div class="modal-footer">       
					<button id="btn_salvar" type="submit" class="btn btn-primary">Salvar</button>
				</div>
			</form>
		</div>
	</div>
</div>



<script type="text/javascript">
    $(document).ready(function() {

        // Função para verificar as datas e mostrar/esconder o campo do banco
        function verificarDatasEExibirBanco() {
            var vencimento = $('#vencimento').val();
            var dataPgto = $('#data_pgto').val();

            // A condição é: data de pagamento não pode ser vazia E precisa ser igual ao vencimento
            if (dataPgto && vencimento && dataPgto === vencimento) {
                $('#div-banco').removeClass('d-none'); // Mostra o campo do banco
            } else {
                $('#div-banco').addClass('d-none'); // Esconde o campo do banco
            }
        }

        // Adiciona um "escutador" para quando o valor dos campos de data mudar
        $('#vencimento, #data_pgto').on('change', function() {
            verificarDatasEExibirBanco();
        });

        // Também é uma boa prática executar a função quando o modal for aberto,
        // caso os dados já venham preenchidos de uma edição.
        $('#modalForm').on('shown.bs.modal', function () {
            verificarDatasEExibirBanco();
        });

        // E garantir que o campo do banco fique escondido ao fechar o modal
        $('#modalForm').on('hidden.bs.modal', function () {
            $('#div-banco').addClass('d-none');
        });

    });
</script>

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
											<td class="bg-warning alert-warning">Pessoa</td>
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






<div class="modal fade" id="modalParcelar" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" >
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
								<label >Valor</label>
								<input type="text" class="form-control" name="valor-parcelar"  id="valor-parcelar"  readonly>
							</div>
						</div>

						<div class="col-md-2">
							<div class="mb-3">
								<label >Parcelas</label>
								<input type="number" class="form-control" name="qtd-parcelar"  id="qtd-parcelar"  required>
							</div>
						</div>

						<div class="col-md-4">
							<div class="form-group"> 
								<label>Frequência Parcelas</label> 
								<select class="form-select" name="frequencia" id="frequencia-parcelar" required style="width:100%;">
									
									<?php 
									$query = $pdo->query("SELECT * FROM frequencias order by id asc");
									$res = $query->fetchAll(PDO::FETCH_ASSOC);
									for($i=0; $i < @count($res); $i++){
										foreach ($res[$i] as $key => $value){	}
											$id_item = $res[$i]['id'];
										$nome_item = $res[$i]['frequencia'];
										$dias = $res[$i]['dias'];

										if($nome_item != 'Uma Vez' and $nome_item != 'Única' and $nome_item != 'Nenhuma'){

											?>
											<option <?php if($nome_item == 'Mensal'){ ?> selected <?php } ?> value="<?php echo $dias ?>"><?php echo $nome_item ?></option>

										<?php } } ?>


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
						<small><div id="mensagem-parcelar" align="center" class="mt-3"></div></small>					

					</div>

					<div class="modal-footer">

					</div>

				</form>

			</div>
		</div>
	</div>






	<!-- Modal -->
	<div class="modal fade" id="modalBaixar" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
		<div class="modal-dialog">
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
									<label >Multa em R$</label>
									<input onkeyup="totalizar()" type="text" class="form-control" name="valor-multa"  id="valor-multa" placeholder="Ex 15.00" value="0">
								</div>
							</div>

							<div class="col-md-3">
								<div class="mb-3">
									<label >Júros em R$</label>
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
									<label>SubTotal</label>
									<input type="text" class="form-control" name="subtotal"  id="subtotal" readonly>
								</div>	
							</div>
						</div>


						<div class="row">
							
							<div class="col-md-6">
								<div class="form-group"> 
									<label>Banco</label> 
									<select class="form-select" name="banco" id="banco" required onchange="calcularTaxa()">	
										<option value="">Selecione</option>
										<?php 
										$query = $pdo->query("SELECT * FROM bancos order by id asc");
										$res = $query->fetchAll(PDO::FETCH_ASSOC);
										for($i=0; $i < @count($res); $i++){
											foreach ($res[$i] as $key => $value){}	

												?>	
											<option value="<?php echo $res[$i]['id'] ?>"><?php echo $res[$i]['banco'] ?></option>

										<?php } ?>

									</select>
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




	<!-- Modal -->
	<div class="modal fade" id="modalResiduos" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header bg-primary text-white">
					<h4 class="modal-title" id="tituloModal">Residuos da Conta</h4>
					 <button id="btn-fechar-residuos" aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"><span class="text-white" aria-hidden="true">&times;</span></button>
				</div>
				<div class="modal-body">

					<small><div id="listar-residuos"></div></small>

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




	<script type="text/javascript">var pag = "<?=$pag?>"</script>
	<script src="js/ajax.js"></script>


	<script type="text/javascript">
		$(document).ready(function() {
			$('.sel2').select2({
				dropdownParent: $('#modalForm')
			});
		});
	</script>


	<script type="text/javascript">

		function marcarTodos(){
			let checkbox = document.getElementById('input-todos');
			var usuario = $('#id_permissoes').val();

			if(checkbox.checked) {
				adicionarPermissoes(usuario);		    
			} else {
				limparPermissoes(usuario);
			}
		}

	</script>


	<script type="text/javascript">
		function excluir(id){	
    $('#mensagem-excluir').text('Excluindo...')
    
    $.ajax({
        url: 'paginas/' + pag + "/excluir.php",
        method: 'POST',
        data: {id},
        dataType: "html",

        success:function(mensagem){
            if (mensagem.trim() == "Excluído com Sucesso") {            	
                buscar();
                limparCampos();
            } else {
                $('#mensagem-excluir').addClass('text-danger')
                $('#mensagem-excluir').text(mensagem)
            }
        }
    });
}
	</script>



	<script type="text/javascript">
		function carregarImg() {
			var target = document.getElementById('target');
			var file = document.querySelector("#arquivo").files[0];

			var arquivo = file['name'];
			resultado = arquivo.split(".", 2);

			if(resultado[1] === 'pdf'){
				$('#target').attr('src', "images/pdf.png");
				return;
			}

			if(resultado[1] === 'rar' || resultado[1] === 'zip'){
				$('#target').attr('src', "images/rar.png");
				return;
			}

			if(resultado[1] === 'doc' || resultado[1] === 'docx' || resultado[1] === 'txt'){
				$('#target').attr('src', "images/word.png");
				return;
			}


			if(resultado[1] === 'xlsx' || resultado[1] === 'xlsm' || resultado[1] === 'xls'){
				$('#target').attr('src', "images/excel.png");
				return;
			}


			if(resultado[1] === 'xml'){
				$('#target').attr('src', "images/xml.png");
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
		function buscar(){
			var filtro = $('#tipo_data_filtro').val();
			var dataInicial = $('#dataInicial').val();
			var dataFinal = $('#dataFinal').val();
			var tipo_data = $('#tipo_data').val();
			var atacadista = $('#atacadista').val();
			var formaPGTO =  $('#formaPGTO').val();
			var funcionario =  $('#funcionario').val();
			var cargo =  $('#cargo').val();


			listar(filtro, dataInicial, dataFinal, tipo_data, atacadista, formaPGTO, funcionario, cargo);
		}


		function tipoData(tipo){
			$('#tipo_data').val(tipo);
			buscar();
		}





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
		        url: 'paginas/' + pag + "/calcular_taxa.php",
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

	<script>
    	document.getElementById('relatorio').addEventListener('click', function(event) {
        event.preventDefault(); // Impede o comportamento padrão do botão

		var filtro = $('#tipo_data_filtro').val();
		var dataInicial = $('#dataInicial').val();
		var dataFinal = $('#dataFinal').val();
		var tipo_data = $('#tipo_data').val();
		var atacadista = $('#atacadista').val();
		var formaPGTO =  $('#formaPGTO').val();

		
        // Cria um novo objeto FormData
        var formData = new FormData();

        // Adiciona campos manualmente ao FormData
        formData.append('filtro', filtro); 
        formData.append('dataInicial', dataInicial);
		formData.append('dataFinal', dataFinal);
		formData.append('tipo_data', tipo_data);
		formData.append('atacadista', atacadista);
		formData.append('formaPGTO', formaPGTO);


        // Envia o FormData via AJAX
        $.ajax({
            url: 'rel/pagar_class.php", // URL do script que processará a requisição
            type: 'POST',
            data: formData, // Envia o FormData
            processData: false, // Impede o jQuery de processar os dados
            contentType: false, // Impede o jQuery de definir o contentType
            success: function(mensagem) {
                $('#mensagem-baixar').text('');
                $('#mensagem-baixar').removeClass();
                if (mensagem.trim() == "Baixado com Sucesso") {
                    $('#btn-fechar-baixar').click();
                    buscar();
                } else {
                    $('#mensagem-baixar').addClass('text-danger');
                    $('#mensagem-baixar').text(mensagem);
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro na requisição AJAX:', error);
            }
        });
    });
    </script>



	<script type="text/javascript">
			$("#form-baixar").submit(function () {
				event.preventDefault();
				var formData = new FormData(this);

				$.ajax({
					url: 'paginas/' + pag + "/baixar.php",
					type: 'POST',
					data: formData,

					success: function (mensagem) {
						$('#mensagem-baixar').text('');
						$('#mensagem-baixar').removeClass()
						if (mensagem.trim() == "Baixado com Sucesso") {                    
							$('#btn-fechar-baixar').click();
							buscar();
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



<script type="text/javascript">
			$("#form-parcelar").submit(function () {
				event.preventDefault();
				var formData = new FormData(this);

				$.ajax({
					url: 'paginas/' + pag + "/parcelar.php",
					type: 'POST',
					data: formData,

					success: function (mensagem) {
						$('#mensagem-parcelar').text('');
						$('#mensagem-parcelar').removeClass()
						if (mensagem.trim() == "Parcelado com Sucesso") {                    
							$('#btn-fechar-parcelar').click();
							buscar();
						} else {
							$('#mensagem-parcelar').addClass('text-danger')
							$('#mensagem-parcelar').text(mensagem)
						}

					},

					cache: false,
					contentType: false,
					processData: false,

				});

			});


			function valorBaixar(){
				var ids = $('#ids').val();
				
				 $.ajax({
			        url: 'paginas/' + pag + "/valor_baixar.php",
			        method: 'POST',
			        data: {ids},
			        dataType: "html",

			        success:function(result){
			            $("#total_contas").html(result);
			           
			        }
			    });
			}
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