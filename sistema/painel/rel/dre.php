<?php
require_once("../../conexao.php");
require_once("../verificar_permissoes.php");
require_once("data_formatada.php");
require_once("../funcoes/extenso.php");

//verificar se ele tem a permissão de estar nessa página
if(@$funcionarios == 'ocultar'){
    echo "<script>window.location='index'</script>";
    exit();
}
 ?>

$data_atual = date('Y-m-d');
$ano_atual = date('Y');
$mes_atual = date('m');

// Definir período
$data_inicio = $ano_atual."-01-01";
$data_final = $ano_atual."-12-31";

// RECEITAS
$total_receitas = 0;
$query = $pdo->query("SELECT * FROM receber WHERE data_pgto >= '$data_inicio' AND data_pgto <= '$data_final' AND pago = 'Sim'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
foreach($res as $rec){
    $total_receitas += $rec['valor'];
}

// DESPESAS
$total_despesas = 0;
$query = $pdo->query("SELECT * FROM pagar WHERE data_pgto >= '$data_inicio' AND data_pgto <= '$data_final' AND pago = 'Sim'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
foreach($res as $desp){
    $total_despesas += $desp['valor'];
}

// Cálculos DRE
$resultado_operacional = $total_receitas - $total_despesas;

// Formatação
$total_receitasF = number_format($total_receitas, 2, ',', '.');
$total_despesasF = number_format($total_despesas, 2, ',', '.');
$resultado_operacionalF = number_format($resultado_operacional, 2, ',', '.');

// Classe para resultado
$classe_resultado = ($resultado_operacional >= 0) ? 'text-success' : 'text-danger';

?>

<!DOCTYPE html>
<html>
<head>
    <title>DRE - <?php echo $nome_sistema ?></title>
    <link href="../css/style.css" rel="stylesheet">
    <style>
        @page { margin: 20px; }
        
        .titulo{
            text-align: center;
            margin-bottom: 20px;
        }
        
        .tabela {
            width: 100%;
            margin-bottom: 20px;
        }
        
        .tabela th, .tabela td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        
        .total {
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="titulo">
    <h2>Demonstração do Resultado do Exercício - <?php echo $ano_atual ?></h2>
    <p><?php echo $data_hoje ?></p>
</div>

<table class="tabela">
    <tr>
        <th colspan="2">DEMONSTRAÇÃO DO RESULTADO</th>
    </tr>
    
    <tr>
        <td>RECEITA OPERACIONAL BRUTA</td>
        <td align="right">R$ <?php echo $total_receitasF ?></td>
    </tr>
    
    <tr>
        <td>(-) DESPESAS OPERACIONAIS</td>
        <td align="right">R$ <?php echo $total_despesasF ?></td>
    </tr>
    
    <tr class="total">
        <td>(=) RESULTADO OPERACIONAL</td>
        <td align="right" class="<?php echo $classe_resultado ?>">
            R$ <?php echo $resultado_operacionalF ?>
        </td>
    </tr>
</table>

<div style="margin-top:40px">
    <p>Resultado por Extenso: <?php echo valor_por_extenso($resultado_operacional) ?></p>
</div>

</body>
</html> 