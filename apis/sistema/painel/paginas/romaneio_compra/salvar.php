<?php
$tabela = 'romaneio_compra';
require_once("../../../conexao.php");


@session_start();
$id_usuario = @$_SESSION['id'];

$id = $_POST['id'];
$fornecedor = $_POST['fornecedor'];
$data = $_POST['data'];
$plano_pgto = $_POST['plano_pgto'];
$quant_dias = $_POST['quant_dias']; // Valor de quant_dias
$nota_fiscal = $_POST['nota_fiscal'];
$vencimento = $_POST['vencimento'];
$fazenda = $_POST['fazenda'];

// Se quant_dias estiver vazio, atribui o valor padrão 0
if ($quant_dias === '' || !is_numeric($quant_dias)) {
    $quant_dias = 0; // Pode ser também NULL, dependendo da sua necessidade no banco de dados
}

// Campos que podem ser arrays
$quant_caixa_1 = $_POST['quant_caixa_1'] ?? [];
$produto_1 = $_POST['produto_1'] ?? [];
$preco_kg_1 = $_POST['preco_kg_1'] ?? [];
$tipo_cx_1 = $_POST['tipo_cx_1'] ?? [];
$preco_unit_1 = $_POST['preco_unit_1'] ?? [];
$valor_1 = $_POST['valor_1'] ?? [];

// Se $total_liquido for passado, converte para float, caso contrário, calcula
$total_liquido = $_POST['valor_liquido'] ?? null;
if ($total_liquido === null) {
    $total_liquido = array_reduce($valor_1, function ($carry, $item) {
        return $carry + (float) str_replace(',', '.', $item); // Garante que a string será convertida para float
    }, 0);
}

// Converte o valor para float e depois aplica o number_format
$valor_liquido = number_format((float) $total_liquido, 2, '.', '');

// Inicializando os arrays validados
$quant_caixa_1_val = [];
$produto_1_val = [];
$preco_kg_1_val = [];
$tipo_cx_1_val = [];
$preco_unit_1_val = [];
$valor_1_val = [];

function validarCampo($campo)
{
    $campo_validado = [];
    if (is_array($campo)) {
        foreach ($campo as $valor) {
            if ($valor != "") {
                $campo_validado[] = $valor;
            }
        }
    }
    return $campo_validado;
}

// Aplicando a validação para cada campo
$quant_caixa_1_val = validarCampo($quant_caixa_1);
$produto_1_val = validarCampo($produto_1);
$preco_kg_1_val = validarCampo($preco_kg_1);
$tipo_cx_1_val = validarCampo($tipo_cx_1);
$preco_unit_1_val = validarCampo($preco_unit_1);
$valor_1_val = validarCampo($valor_1);

if ($id == "") {
    $query = $pdo->prepare("INSERT INTO $tabela SET fornecedor = :fornecedor, quant_dias = :quant_dias, data = :data, nota_fiscal = :nota_fiscal, plano_pgto = :plano_pgto, vencimento = :vencimento, total_liquido = :total_liquido, fazenda = :fazenda");
} else {
    $query = $pdo->prepare("UPDATE $tabela SET fornecedor = :fornecedor, quant_dias = :quant_dias, data = :data, nota_fiscal = :nota_fiscal, plano_pgto = :plano_pgto, vencimento = :vencimento, total_liquido = :total_liquido, fazenda = :fazenda where id = :id");
}

$query->bindValue(":fornecedor", $fornecedor);
$query->bindValue(":data", $data);
$query->bindValue(":nota_fiscal", $nota_fiscal);
$query->bindValue(":plano_pgto", $plano_pgto);
$query->bindValue(":vencimento", DateTime::createFromFormat('Y-m-d', $vencimento)->format('Y-m-d'));
$query->bindValue(":total_liquido", $total_liquido);
$query->bindValue(":quant_dias", $quant_dias);  // Valor agora está validado
$query->bindValue(":fazenda", $fazenda);
if ($id != "") {
    $query->bindValue(":id", $id);
}
$query->execute();

$romaneio_id = $pdo->lastInsertId();

foreach ($valor_1_val as $key => $val) {
    $quantidade = $quant_caixa_1_val[$key];
    $variedade = $produto_1_val[$key];
    $preco_kg = number_format((float) str_replace(',', '.', $preco_kg_1_val[$key]), 2, '.', '');
    $tipo_caixa = $tipo_cx_1_val[$key];
    $preco_unit = number_format((float) str_replace(',', '.', $preco_unit_1_val[$key]), 2, '.', '');
    $valor = number_format((float) str_replace(',', '.', $val), 2, '.', '');

    $query = $pdo->prepare("INSERT INTO linha_produto_compra (quant, variedade, preco_kg, tipo_caixa, preco_unit, valor, id_romaneio) VALUES (:quant, :variedade, :preco_kg, :tipo_caixa, :preco_unit, :valor, :id_romaneio)");
    $query->bindValue(":quant", $quantidade);
    $query->bindValue(":variedade", $variedade);
    $query->bindValue(":preco_kg", $preco_kg);
    $query->bindValue(":tipo_caixa", $tipo_caixa);
    $query->bindValue(":preco_unit", $preco_unit);
    $query->bindValue(":valor", $valor);
    $query->bindValue(":id_romaneio", $romaneio_id);
    $query->execute();
}


$pgto = " ,data_pgto = '$vencimento'";
$usu_pgto = " ,usuario_pgto = '$id_usuario'";
$pago = 'Nao';

$query = $pdo->prepare("INSERT INTO pagar SET id_romaneio = :romaneio_id, descricao = :descricao, fornecedor = :fornecedor, funcionario = :funcionario, valor = :valor, vencimento = '$vencimento' $pgto, data_lanc = curDate(), forma_pgto = '', frequencia = '', obs = :obs, arquivo = '', subtotal = :valor, usuario_lanc = '$id_usuario' $usu_pgto, pago = '$pago', referencia = 'Conta', hora = curTime() ");

$descricao = "Romaneio de Compra ID: " . number_format($romaneio_id);


$query->bindValue(":romaneio_id", "$romaneio_id");
$query->bindValue(":descricao", "$descricao");
$query->bindValue(":fornecedor", "$fornecedor");
$query->bindValue(":funcionario", "");
$query->bindValue(":valor", "$total_liquido");
$query->bindValue(":obs", "");
$query->execute();
$ultimo_id = $pdo->lastInsertId();

echo 'Salvo com Sucesso';
