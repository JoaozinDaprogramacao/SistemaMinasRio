<?php
require_once("verificar.php");
require_once("../conexao.php"); // Caminho ajustado para a conexão

$pag = 'receber';

// Recuperando variáveis de sessão para os filtros e modais
$id_usuario = @$_SESSION['id'];
$mostrar_registros = @$_SESSION['registros'];

if (@$receber == 'ocultar') {
	echo "<script>window.location='index'</script>";
	exit();
}

// DEFINIÇÃO DE DATAS (Necessário para filtros.php e cards.php)
$data_hoje = date('Y-m-d');
$data_atual = date('Y-m-d');
$mes_atual = date('m');
$ano_atual = date('Y');
$data_inicio_mes = $ano_atual . "-" . $mes_atual . "-01";

if ($mes_atual == '04' || $mes_atual == '06' || $mes_atual == '09' || $mes_atual == '11') {
	$data_final_mes = $ano_atual . '-' . $mes_atual . '-30';
} else if ($mes_atual == '02') {
	$bissexto = date('L', mktime(0, 0, 0, 1, 1, $ano_atual));
	$data_final_mes = ($bissexto == 1) ? $ano_atual . '-' . $mes_atual . '-29' : $ano_atual . '-' . $mes_atual . '-28';
} else {
	$data_final_mes = $ano_atual . '-' . $mes_atual . '-31';
}
?>

<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

<div class="left-content mt-2 mb-3">
    <a style="margin-bottom: 10px; margin-top: 5px" 
       class="btn ripple btn-primary text-white" 
       onclick="inserir()" 
       type="button">
       <i class="fe fe-plus me-2"></i>Adicionar Conta
    </a>
</div>

<div class="justify-content-between">
	<?php include_once("../painel/paginas/receber/receber/filtros.php"); ?>
	<?php include_once("../painel/paginas/receber/receber/cards.php"); ?>
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

<?php include_once("../painel/paginas/receber/receber/modais.php"); ?>

<script type="text/javascript">
	var pag = "<?= $pag ?>";
</script>

<script src="../painel/paginas/receber/receber/receber_scripts.js"></script>
<script src="js/ajax.js"></script>