<?php
$tabela = 'romaneio_venda';
require_once("../../../conexao.php");


@session_start();
$id_usuario = @$_SESSION['id'];

$id = $_POST['id'];
$atacadista = $_POST['cliente']; // Simples string
$data = $_POST['data']; // Simples string
$plano_pgto = $_POST['plano_pgto'];
$quant_dias = $_POST['quant_dias'];
$nota_fiscal = $_POST['nota_fiscal'];
$vencimento = $_POST['vencimento']; // Entrada como string

// Campos que podem ser arrays

$quant_caixa_1 = $_POST['quant_caixa_1'] ?? [];
$produto_1 = $_POST['produto_1'] ?? [];
$preco_kg_1 = $_POST['preco_kg_1'] ?? [];
$tipo_cx_1 = $_POST['tipo_cx_1'] ?? [];
$preco_unit_1 = $_POST['preco_unit_1'] ?? [];
$valor_1 = $_POST['valor_1'] ?? [];

$desc_avista = $_POST['desc-avista'] ?? null; // Único valor
$desc_2 = $_POST['desc_2'] ?? [];
$quant_caixa_2 = $_POST['quant_caixa_2'] ?? [];
$preco_kg_2 = $_POST['preco_kg_2'] ?? [];
$tipo_cx_2 = $_POST['tipo_cx_2'] ?? [];
$preco_unit_2 = $_POST['preco_unit_2'] ?? [];
$valor_2 = $_POST['valor_2'] ?? [];

$obs_3 = $_POST['obs_3'] ?? [];
$material = $_POST['material'] ?? [];
$quant_3 = $_POST['quant_3'] ?? [];
$preco_unit_3 = $_POST['preco_unit_3'] ?? [];
$valor_3 = $_POST['valor_3'] ?? [];

$descricao_option = $_POST['descricao'] ?? null;
$option = $_POST['option'] ?? null;
$valor = $_POST['valor'] ?? '0.0'; // Valor padrão caso esteja vazio
$valor_liquido = $_POST['valor_liquido'] ?? null;

// Inicializando os arrays validados
$quant_caixa_1_val = [];
$produto_1_val = [];
$preco_kg_1_val = [];
$tipo_cx_1_val = [];
$preco_unit_1_val = [];
$valor_1_val = [];

$desc_avista_val = null;
$desc_2_val = [];
$quant_caixa_2_val = [];
$preco_kg_2_val = [];
$tipo_cx_2_val = [];
$preco_unit_2_val = [];
$valor_2_val = [];

$obs_3_val = [];
$material_val = [];
$quant_3_val = [];
$preco_unit_3_val = [];
$valor_3_val = [];

$valor_val = null;
$valor_liquido_val = null;

function validarCampo($campo)
{
	$campo_validado = [];
	if (is_array($campo)) { // Verifica se o campo é array
		foreach ($campo as $valor) {
			if ($valor != "") {
				$campo_validado[] = $valor;
			}
		}
	}
	return $campo_validado;
}

// Aplicando a validação para cada campo
$quant_caixa_1_val = validarCampo($_POST['quant_caixa_1'] ?? []);
$produto_1_val = validarCampo($_POST['produto_1'] ?? []);
$preco_kg_1_val = validarCampo($_POST['preco_kg_1'] ?? []);
$tipo_cx_1_val = validarCampo($_POST['tipo_cx_1'] ?? []);
$preco_unit_1_val = validarCampo($_POST['preco_unit_1'] ?? []);
$valor_1_val = validarCampo($_POST['valor_1'] ?? []);

$desc_2_val = validarCampo($_POST['desc_2'] ?? []);
$quant_caixa_2_val = validarCampo($_POST['quant_caixa_2'] ?? []);
$preco_kg_2_val = validarCampo($_POST['preco_kg_2'] ?? []);
$tipo_cx_2_val = validarCampo($_POST['tipo_cx_2'] ?? []);
$preco_unit_2_val = validarCampo($_POST['preco_unit_2'] ?? []);
$valor_2_val = validarCampo($_POST['valor_2'] ?? []);

$obs_3_val = validarCampo($_POST['obs_3'] ?? []);
$material_val = validarCampo($_POST['material'] ?? []);
$quant_3_val = validarCampo($_POST['quant_3'] ?? []);
$preco_unit_3_val = validarCampo($_POST['preco_unit_3'] ?? []);
$valor_3_val = validarCampo($_POST['valor_3'] ?? []);

$adicional = false;
if ($option == "adicional") {
	$adicional = true;
}

if ($id == "") {
	$query = $pdo->prepare("INSERT INTO $tabela SET descricao = :descricao, adicional = :adicional, adicional_val = :adicional_val, atacadista = :atacadista, quant_dias = :quant_dias, data = :data, nota_fiscal = :nota_fiscal, plano_pgto = :plano_pgto, vencimento = :vencimento, total_liquido = :total_liquido");
} else {
	$query = $pdo->prepare("UPDATE $tabela SET descricao = :descricao, adicional = :adicional, adicional_val = :adicional_val, atacadista = :atacadista, quant_dias = :quant_dias, data = :data, nota_fiscal = :nota_fiscal, plano_pgto = :plano_pgto, vencimento = :vencimento, total_liquido = :total_liquido where id = '$id'");
}

$valor_liquido = str_replace(',', '.', $valor_liquido);
$adicional_val = str_replace(',', '.', $valor);

if ($adicional_val === '' || !is_numeric($adicional_val)) {
	$adicional_val = '0.0'; // Valor padrão para campos decimais
}


$query->bindValue(":descricao", "$descricao_option");
$query->bindValue(":adicional", (int)$adicional, PDO::PARAM_INT);
$query->bindValue(":adicional_val", "$adicional_val");
$query->bindValue(":atacadista", "$atacadista");
$query->bindValue(":data", "$data");
$query->bindValue(":nota_fiscal", "$nota_fiscal");
$query->bindValue(":plano_pgto", "$plano_pgto");
$query->bindValue(":vencimento", DateTime::createFromFormat('Y-m-d', $_POST['vencimento'])->format('Y-m-d'));
$query->bindValue(":total_liquido", "$valor_liquido");
$query->bindValue(":quant_dias", "$quant_dias");
$query->execute();

$romaneio_id = $pdo->lastInsertId();

foreach ($valor_1_val as $key => $val) {
	// Verificando se o índice existe nos outros arrays
	// Pegando os valores para cada variável, e convertendo o preço para ponto
	$quantidade = $quant_caixa_1_val[$key];
	$variedade = $produto_1_val[$key];
	$preco_kg = str_replace(',', '.', $preco_kg_1_val[$key]);  // Corrigindo separador decimal
	$tipo_caixa = $tipo_cx_1_val[$key];
	$preco_unit = str_replace(',', '.', $preco_unit_1_val[$key]);
	$valor = str_replace(',', '.', $val);;

	// Preparando a query para inserção
	$query = $pdo->prepare("INSERT INTO linha_produto (quant, variedade, preco_kg, tipo_caixa, preco_unit, valor, id_romaneio) VALUES (:quant, :variedade, :preco_kg, :tipo_caixa, :preco_unit, :valor, :id_romaneio)");

	// Vinculando os parâmetros da query

	$query->bindValue(":quant", $quantidade);
	$query->bindValue(":variedade", $variedade);
	$query->bindValue(":preco_kg", $preco_kg);
	$query->bindValue(":tipo_caixa", $tipo_caixa);
	$query->bindValue(":preco_unit", $preco_unit);
	$query->bindValue(":valor", $valor);
	$query->bindValue(":id_romaneio", $romaneio_id);

	// Executando a query
	$query->execute();
}

foreach ($valor_2_val as $key => $val) {
	// Verificando se o índice existe nos outros arrays
	// Pegando os valores para cada variável, e convertendo o preço para ponto
	$descricao = $desc_2_val[$key];
	$quant_caixa = $quant_caixa_2_val[$key];
	$preco_kg = str_replace(',', '.', $preco_kg_2_val[$key]);  // Corrigindo separador decimal
	$tipo_caixa = $tipo_cx_2_val[$key];
	$preco_unit = str_replace(',', '.', $preco_unit_2_val[$key]);
	$valor = str_replace(',', '.', $val);;

	// Preparando a query para inserção
	$query = $pdo->prepare("INSERT INTO linha_comissao (descricao, quant_caixa, preco_kg, tipo_caixa, preco_unit, valor, id_romaneio) VALUES (:descricao, :quant_caixa, :preco_kg, :tipo_caixa, :preco_unit, :valor, :id_romaneio)");

	// Vinculando os parâmetros da query
	$query->bindValue(":descricao", $descricao);
	$query->bindValue(":quant_caixa", $quant_caixa);
	$query->bindValue(":preco_kg", $preco_kg);
	$query->bindValue(":tipo_caixa", $tipo_caixa);
	$query->bindValue(":preco_unit", $preco_unit);
	$query->bindValue(":valor", $valor);
	$query->bindValue(":id_romaneio", $romaneio_id);

	// Executando a query
	$query->execute();
}


foreach ($valor_3_val as $key => $val) {
	// Verificando se o índice existe nos outros arrays
	// Pegando os valores para cada variável, e convertendo o preço para ponto
	$observacoes = $obs_3_val[$key];
	$descricao = $material_val[$key];
	$quant = $quant_3_val[$key];
	$preco_unit = str_replace(',', '.', $preco_unit_3_val[$key]);
	$valor = str_replace(',', '.', $val);;

	// Preparando a query para inserção
	$query = $pdo->prepare("INSERT INTO linha_observacao (observacoes, descricao, quant, preco_unit, valor, id_romaneio) VALUES (:observacoes, :descricao, :quant, :preco_unit, :valor, :id_romaneio)");

	// Vinculando os parâmetros da query
	$query->bindValue(":observacoes", $observacoes);
	$query->bindValue(":descricao", $descricao);
	$query->bindValue(":quant", $quant);
	$query->bindValue(":preco_unit", $preco_unit);
	$query->bindValue(":valor", $valor);
	$query->bindValue(":id_romaneio", $romaneio_id);

	// Executando a query
	$query->execute();
}

$pgto = " ,data_pgto = '$vencimento'";
$usu_pgto = " ,usuario_pgto = '$id_usuario'";
$pago = 'Nao';

$query = $pdo->prepare("INSERT INTO receber SET id_romaneio = :id_romaneio, descricao = :descricao, cliente = :cliente, valor = :valor, vencimento = '$vencimento' $pgto, data_lanc = curDate(), forma_pgto = '', frequencia = 0, obs = :obs, arquivo = '', subtotal = :valor, usuario_lanc = '$id_usuario' $usu_pgto, pago = '$pago', referencia = 'Conta', caixa = '', hora = curTime() ");

$descricao = "Romaneio de venda ID: " . number_format($romaneio_id);

$query->bindValue(":id_romaneio", "$romaneio_id");
$query->bindValue(":descricao", "$descricao");
$query->bindValue(":cliente", "$atacadista");
$query->bindValue(":valor", "$valor_liquido");
$query->bindValue(":obs", "");
$query->execute();

echo 'Salvo com Sucesso';
