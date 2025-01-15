<?php 
require_once("sistema/conexao.php");

 ?>
 <!DOCTYPE html>
<html lang="pt-br">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		
		 <meta name="Description" content="Template Modelo Catalogo Hugo Cursos">
        <meta name="Author" content="Hugo Vasconcelos">
        <meta name="Keywords" content="cursos hugo vasconcelos, sistemas hugo vasconcelos, sistema web, desenvolvimento web"/>

		<title><?php echo $nome_sistema ?></title>

		<!-- Google font -->
		<link href="https://fonts.googleapis.com/css?family=Montserrat:400,500,700" rel="stylesheet">

		<!-- Bootstrap -->
		<link type="text/css" rel="stylesheet" href="css/bootstrap.min.css"/>

		<!-- Slick -->
		<link type="text/css" rel="stylesheet" href="css/slick.css"/>
		<link type="text/css" rel="stylesheet" href="css/slick-theme.css"/>

		<!-- nouislider -->
		<link type="text/css" rel="stylesheet" href="css/nouislider.min.css"/>

		<!-- Font Awesome Icon -->
		<link rel="stylesheet" href="css/font-awesome.min.css">

		<!-- Custom stlylesheet -->
		<link type="text/css" rel="stylesheet" href="css/style.css"/>

		<link rel="icon" href="sistema/img/icone.png" type="image/x-icon"/>

		

    </head>
	<body>
		<!-- HEADER -->
		<header>
			<!-- TOP HEADER -->
			<div id="top-header">
				<div class="container">
					<ul class="header-links pull-left">
						<li><a  href="http://api.whatsapp.com/send?1=pt_BR&phone=<?php echo @$tel_whats ?>" target="_blank"><i class="fa fa-phone"></i><?php echo $telefone_sistema ?></a></li>
						<li><a href="mailto:contato@hugocursos.com.br"><i class="fa fa-envelope-o"></i> <?php echo $email_sistema ?></a></li>					
						<li class="pull-right"><a href="#" class="ocultar_web"><i class="fa fa-user-o"></i></a></li>
					</ul>

					<ul class="header-links pull-right">						
						<li ><a href="sistema" class="ocultar_mobile"><i class="fa fa-user-o"></i>Login</a></li>
						
					</ul>
					
				</div>
			</div>
			<!-- /TOP HEADER -->

			<!-- MAIN HEADER -->
			<div id="header">
				<!-- container -->
				<div class="container">
					<!-- row -->
					<div class="row">
						<!-- LOGO -->
						<div class="col-md-3">
							<div class="header-logo">
								<a href="#" class="logo">
									<img src="sistema/img/foto-painel.png" alt="" class="logo">
								</a>
							</div>
						</div>
						<!-- /LOGO -->

						<!-- SEARCH BAR -->
						<div class="col-md-6">
							<div class="header-search">
								<form>									
									<input id="input_buscar" onkeyup="buscar()" class="input" placeholder="Buscar Produto">
									<button onclick="buscar()" class="search-btn">Buscar</button>
								</form>
							</div>
						</div>
						<!-- /SEARCH BAR -->

						
					</div>
					<!-- row -->
				</div>
				<!-- container -->
			</div>
			<!-- /MAIN HEADER -->
		</header>
		<!-- /HEADER -->



		
	
	

		<!-- SECTION -->
		<div class="section">
			<!-- container -->
			<div class="container">

				<?php 
						$query = $pdo->query("SELECT * from categorias where ativo = 'Sim' order by nome asc");
										$res = $query->fetchAll(PDO::FETCH_ASSOC);
										$linhas = @count($res);
										if($linhas > 0){
					 ?>

				<!-- row -->
				<div class="row" id="area_categorias">

					

					<!-- section title -->
					<div class="col-md-12">
						<div class="section-title">
							<h3 class="title">Categorias</h3>
							<div class="section-nav">
								<ul class="section-tab-nav tab-nav">
									<?php 
										

											for($i=0; $i<$linhas; $i++){
										$id_cat = $res[$i]['id'];
										$nome_cat = $res[$i]['nome'];
										$foto = $res[$i]['foto'];	
										$ativo = $res[$i]['ativo'];

										if($i==0){
											$ativo_cat = 'active';
										}else{
											$ativo_cat = '';
										}
										
	
									 ?>
									<li class="<?php echo $ativo_cat ?>"><a onclick="buscar('<?php echo $id_cat ?>')" data-toggle="tab" href=""><small><?php echo $nome_cat ?></small></a></li>
									
									<?php }  ?>
								</ul>
							</div>
						</div>
					</div>
					<!-- /section title -->
				</div>

			<?php } ?>



		<!-- SECTION -->
		<div class="section">
			<!-- container -->
			<div class="container">
				<!-- row -->
				<div class="row">
					<div class="col-md-4 col-xs-6">
						<div class="section-title">
							<h4 class="title">Produtos</h4>
							<div class="section-nav">
								<div id="slick-nav-3" class="products-slick-nav"></div>
							</div>
						</div>
					</div>
				</div>

				<div class="row" id="listar_produtos">					
								
						

				</div>
				<!-- /row -->
			</div>
			<!-- /container -->
		</div>
		<!-- /SECTION -->

		

				</div>
		</div>

		<!-- FOOTER -->
		<footer id="footer">
			<!-- top footer -->
			<div class="section">
				<!-- container -->
				<div class="container">
					<!-- row -->
					<div class="row">
						<div class="col-md-12 col-xs-6">
							<div class="footer">
								<h3 class="footer-title">Sobre nós</h3>
								<p>Confira nossos produtos disponíveis, qualquer dúvida entre em contato conosco!</p>
								<ul class="footer-links">									
									<li><a href="#"><i class="fa fa-phone"></i><?php echo $telefone_sistema ?></a></li>
									<li><a href="mailto:<?php echo $email_sistema ?>"><i class="fa fa-envelope-o"></i><?php echo $email_sistema ?></a></li>
								</ul>
							</div>
						</div>


					
					</div>
					<!-- /row -->
				</div>
				<!-- /container -->
			</div>
			<!-- /top footer -->

		
		</footer>
		<!-- /FOOTER -->

		<!-- jQuery Plugins -->
		<script src="js/jquery.min.js"></script>
		<script src="js/bootstrap.min.js"></script>
		<script src="js/slick.min.js"></script>
		<script src="js/nouislider.min.js"></script>
		<script src="js/jquery.zoom.min.js"></script>
		<script src="js/main.js"></script>

	</body>
</html>


<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<script type="text/javascript">
	$(document).ready(function() {
		buscar();
	});

	function buscar(cat){		
		var buscar = $('#input_buscar').val();
		
		if(buscar != ""){
			$('#area_categorias').hide();
		}else{
			$('#area_categorias').show();
		}

		$.ajax({
					url: "listar_produtos.php",
					method: 'POST',
					data: {buscar, cat},
					dataType: "text",

					success:function(result){
						$("#listar_produtos").html(result);
					}
				});

	}
</script>


 <a title="Ir para o whatsapp" target="_blank" href="http://api.whatsapp.com/send?1=pt_BR&phone=<?php echo @$tel_whats ?>"><img src="img/logo_whats.png" width="60px" style="position:fixed; bottom:20px; right:20px"></a>