<?php
$tabela = 'romaneio_venda';
require_once("../../../conexao.php");

@session_start();
$id_usuario = @$_SESSION['id'];

// Debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// No início do arquivo, após require_once
header('Content-Type: application/json; charset=utf-8');

// Campos obrigatórios
$campos_obrigatorios = [
	'cliente' => 'cliente',
	'data' => 'Data',
	'plano_pgto' => 'Plano de Pagamento',
	'quant_dias' => 'Quantidade de Dias',
	'vencimento' => 'Vencimento'
];

// Validações básicas dos campos obrigatórios
$erros = [];

// Validação do Atacadista/Fornecedor
if (empty($_POST['cliente']) || $_POST['cliente'] == '0') {
	$erros[] = "Selecione um cliente";
}

// Validação da Data
if (empty($_POST['data'])) {
	$erros[] = "Data é obrigatória";
}

// Validação do Plano de Pagamento
if (empty($_POST['plano_pgto']) || $_POST['plano_pgto'] == '0') {
	$erros[] = "Selecione um plano de pagamento";
}

// Validação do desconto à vista
if (!empty($_POST['plano_pgto'])) {
	$query = $pdo->prepare("SELECT nome FROM planos_pgto WHERE id = ?");
	$query->execute([$_POST['plano_pgto']]);
	$plano = $query->fetch(PDO::FETCH_ASSOC);
	
	$plano_nome = strtoupper($plano['nome']);
	if ($plano && ($plano_nome === 'À VISTA' || $plano_nome === 'Á VISTA')) {
		$desconto = $_POST['desc-avista'] ?? '';
		
		if (empty($desconto) || floatval(str_replace(',', '.', $desconto)) <= 0) {
			$erros[] = "Para pagamento à vista, o desconto é obrigatório";
		}
	}
}

$desc_avista = $_POST['desc-avista'] ?? 0;

// Validação da Nota Fiscal (apenas verificar duplicidade se for informada)
if (!empty($_POST['nota_fiscal'])) {
	$nota = $_POST['nota_fiscal'];
	$id = $_POST['id'] ?? '';
	$query = $pdo->prepare("SELECT id FROM romaneio_venda WHERE nota_fiscal = ? AND id != ?");
	$query->execute([$nota, $id]);
	if ($query->rowCount() > 0) {
		$erros[] = "Esta nota fiscal já está cadastrada";
	}
}

// Validação dos Produtos
$tem_produtos = false;
if (isset($_POST['valor_1'])) {
	foreach ($_POST['valor_1'] as $key => $valor) {
		if (!empty($valor)) {
			$tem_produtos = true;
			
			// Validação dos campos do produto
			if (empty($_POST['produto_1'][$key])) {
				$erros[] = "Selecione a variedade para todos os produtos";
				break;
			}
			if (empty($_POST['tipo_cx_1'][$key])) {
				$erros[] = "Selecione o tipo de caixa para todos os produtos";
				break;
			}
			if (empty($_POST['quant_caixa_1'][$key]) || $_POST['quant_caixa_1'][$key] <= 0) {
				$erros[] = "Quantidade de caixas deve ser maior que zero";
				break;
			}
		}
	}
}

if (!$tem_produtos) {
	$erros[] = "Adicione pelo menos um produto";
}

// Se houver erros, retorna
if (!empty($erros)) {
	echo json_encode([
		'status' => 'erro',
		'mensagem' => implode("<br>", $erros)
	], JSON_UNESCAPED_UNICODE);
	exit;
}

// Se passou pelas validações, continua com o código existente...
$id = $_POST['id'];
$romaneios_selecionados = $_POST['romaneios_selecionados'] ?? '';
$atacadista = $_POST['cliente'];
$data = $_POST['data'];
$plano_pgto = $_POST['plano_pgto'];
$nota_fiscal = $_POST['nota_fiscal'];
$vencimento = $_POST['vencimento'];
$quant_dias = $_POST['quant_dias'] ?: 0;
$adicional = $_POST['valor_adicional'] ?: 0;
$descricao_a = $_POST['descricao_adicional'] ?: '';
$desconto = $_POST['valor_desconto'] ?: 0;
$descricao_d = $_POST['descricao_desconto'] ?: '';

// Campos dos produtos
$quant_caixa_1 = $_POST['quant_caixa_1'] ?? [];
$produto_1 = $_POST['produto_1'] ?? [];
$preco_kg_1 = $_POST['preco_kg_1'] ?? [];
$tipo_cx_1 = $_POST['tipo_cx_1'] ?? [];
$preco_unit_1 = $_POST['preco_unit_1'] ?? [];
$valor_1 = $_POST['valor_1'] ?? [];

// Formatar valores numéricos
$adicional = str_replace(',', '.', $adicional);
$desconto = str_replace(',', '.', $desconto);

// Converter string de IDs em array
$romaneios_array = explode(',', $romaneios_selecionados);

// --- 1) soma do total bruto de produtos ---
$total_bruto = 0;
foreach ($valor_1 as $v) {
    $total_bruto += floatval(str_replace(',', '.', $v));
}

// --- 2) soma das comissões ---
$total_comissao = 0;
if (!empty($_POST['valor_2'])) {
    foreach ($_POST['valor_2'] as $v2) {
        $total_comissao += floatval(str_replace(',', '.', $v2));
    }
}

// --- 3) soma dos materiais ---
$total_materiais = 0;
if (!empty($_POST['valor_3'])) {
    foreach ($_POST['valor_3'] as $v3) {
        $total_materiais += floatval(str_replace(',', '.', $v3));
    }
}

// --- 4) adicional fixo ---
$valor_adicional = floatval(str_replace(',', '.', $adicional));

// --- 5) desconto fixo ---
$valor_desconto   = floatval(str_replace(',', '.', $desconto));

// --- 6) desconto à vista (%) sobre o total bruto ---
$perc_avista      = floatval(str_replace(',', '.', $desc_avista));
$valor_desc_avista = $total_bruto * ($perc_avista / 100);

// --- 7) total líquido final ---
$total_liquido = $total_bruto
               + $total_comissao
               + $total_materiais
               + $valor_adicional
               - $valor_desconto
               - $valor_desc_avista;

// validação básica
if ($total_bruto <= 0) {
    $erros[] = "O total bruto deve ser maior que zero";
}


try {
    $pdo->beginTransaction();

    if ($id == "") {
        // 2a) Incluir desc_avista no INSERT
        $query = $pdo->prepare("INSERT INTO $tabela SET 
            atacadista      = :atacadista, 
            data            = :data, 
            nota_fiscal     = :nota_fiscal, 
            plano_pgto      = :plano_pgto, 
            vencimento      = :vencimento, 
            total_liquido   = :total_liquido, 
            quant_dias      = :quant_dias,
            adicional       = :adicional,
            descricao_a     = :descricao_a,
            desconto        = :desconto,
            descricao_d     = :descricao_d,
            desc_avista     = :desc_avista
        ");
    } else {
        // 2b) E no UPDATE
        $query = $pdo->prepare("UPDATE $tabela SET 
            atacadista      = :atacadista, 
            data            = :data, 
            nota_fiscal     = :nota_fiscal, 
            plano_pgto      = :plano_pgto, 
            vencimento      = :vencimento, 
            total_liquido   = :total_liquido, 
            quant_dias      = :quant_dias,
            adicional       = :adicional,
            descricao_a     = :descricao_a,
            desconto        = :desconto,
            descricao_d     = :descricao_d,
            desc_avista     = :desc_avista
          WHERE id = :id");
        $query->bindValue(":id", $id);
    }

	// Bind dos valores comuns
	$query->bindValue(":atacadista", $atacadista);
	$query->bindValue(":data", $data);
	$query->bindValue(":nota_fiscal", $nota_fiscal);
	$query->bindValue(":plano_pgto", $plano_pgto);
	$query->bindValue(":vencimento", $vencimento);
	$query->bindValue(":total_liquido", $total_liquido);
	$query->bindValue(":quant_dias", $quant_dias);
	$query->bindValue(":adicional", $adicional);
	$query->bindValue(":descricao_a", $descricao_a);
	$query->bindValue(":desconto", $desconto);
	$query->bindValue(":descricao_d", $descricao_d);
    $query->bindValue(":desc_avista", $desc_avista, PDO::PARAM_INT);

	$query->execute();

	$romaneio_venda_id = $id ?: $pdo->lastInsertId();

	// Limpar relacionamentos e linhas de produto anteriores se for update
	if ($id != "") {
		$pdo->prepare("DELETE FROM romaneio_venda_compra WHERE id_romaneio_venda = ?")->execute([$romaneio_venda_id]);
		$pdo->prepare("DELETE FROM linha_produto WHERE id_romaneio = ?")->execute([$romaneio_venda_id]);
	}

	// Inserir relacionamentos com romaneios de compra
	foreach ($romaneios_array as $romaneio_compra_id) {
		if (!empty($romaneio_compra_id)) {
			$pdo->prepare("INSERT INTO romaneio_venda_compra (id_romaneio_venda, id_romaneio_compra) VALUES (?, ?)")
				->execute([$romaneio_venda_id, $romaneio_compra_id]);
		}
	}

	// Inserir linhas de produto
	foreach ($valor_1 as $key => $valor) {
		if (!empty($valor)) {
			$query = $pdo->prepare("INSERT INTO linha_produto (
				id_romaneio, quant, variedade, preco_kg, tipo_caixa, preco_unit, valor
			) VALUES (?, ?, ?, ?, ?, ?, ?)");
			
			$query->execute([
				$romaneio_venda_id,
				$quant_caixa_1[$key],
				$produto_1[$key],
				str_replace(',', '.', $preco_kg_1[$key]),
				$tipo_cx_1[$key],
				str_replace(',', '.', $preco_unit_1[$key]),
				str_replace(',', '.', $valor)
			]);
		}
	}

	// Campos das comissões
	$desc_2 = $_POST['desc_2'] ?? [];
	$quant_caixa_2 = $_POST['quant_caixa_2'] ?? [];
	$preco_kg_2 = $_POST['preco_kg_2'] ?? [];
	$tipo_cx_2 = $_POST['tipo_cx_2'] ?? [];
	$preco_unit_2 = $_POST['preco_unit_2'] ?? [];
	$valor_2 = $_POST['valor_2'] ?? [];

	// Limpar comissões anteriores se for update
	if ($id != "") {
		$pdo->prepare("DELETE FROM linha_comissao WHERE id_romaneio = ?")->execute([$romaneio_venda_id]);
	}

	// Inserir linhas de comissão
	foreach ($desc_2 as $key => $descricao) {
		if (!empty($descricao) && !empty($valor_2[$key])) {
			$query = $pdo->prepare("INSERT INTO linha_comissao (
				id_romaneio, 
				quant_caixa, 
				descricao, 
				preco_kg, 
				tipo_caixa, 
				preco_unit, 
				valor
			) VALUES (?, ?, ?, ?, ?, ?, ?)");
			
			$query->execute([
				$romaneio_venda_id,
				$quant_caixa_2[$key] ?? 0,
				$descricao,
				str_replace(',', '.', $preco_kg_2[$key] ?? 0),
				$tipo_cx_2[$key] ?? '',
				str_replace(',', '.', $preco_unit_2[$key] ?? 0),
				str_replace(',', '.', $valor_2[$key])
			]);
		}
	}

	// Campos das observações
	$obs_3 = $_POST['obs_3'] ?? [];
	$material = $_POST['material'] ?? [];
	$quant_3 = $_POST['quant_3'] ?? [];
	$preco_unit_3 = $_POST['preco_unit_3'] ?? [];
	$valor_3 = $_POST['valor_3'] ?? [];

	// Limpar observações anteriores se for update
	if ($id != "") {
		$pdo->prepare("DELETE FROM linha_observacao WHERE id_romaneio = ?")->execute([$romaneio_venda_id]);
	}

	// Inserir linhas de observação
// Inserir linhas de observação
foreach ($obs_3 as $key => $observacao) {
    if (!empty($observacao) || !empty($material[$key])) {
        // 1) Inserção da observação
        $query = $pdo->prepare("
            INSERT INTO linha_observacao (
                id_romaneio,
                observacoes,
                descricao,
                quant,
                preco_unit,
                valor
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        $query->execute([
            $romaneio_venda_id,
            $observacao,
            $material[$key] ?? null,
            $quant_3[$key] ?? 0,
            str_replace(',', '.', $preco_unit_3[$key] ?? 0),
            str_replace(',', '.', $valor_3[$key] ?? 0)
        ]);

        // 2) Atualizar estoque do material
        $matId = (int)$material[$key];
        $qtdUsada = (int)$quant_3[$key];

        // (a) Opcional: checar se o material tem controle de estoque e se há saldo suficiente
        $check = $pdo->prepare("SELECT tem_estoque, estoque FROM materiais WHERE id = ?");
        $check->execute([$matId]);
        $row = $check->fetch(PDO::FETCH_ASSOC);

        if ($row && strtoupper($row['tem_estoque']) === 'SIM') {
            if ($row['estoque'] < $qtdUsada) {
                throw new Exception("Estoque insuficiente para o material ID {$matId}");
            }
        }

        // (b) Debitar do estoque
        $upd = $pdo->prepare("UPDATE materiais
                              SET estoque = estoque - ?
                              WHERE id = ?");
        $upd->execute([$qtdUsada, $matId]);

        // (c) (Opcional) incrementar contador de vendas
        $upd2 = $pdo->prepare("UPDATE materiais
                               SET vendas = vendas + ?
                               WHERE id = ?");
        $upd2->execute([$qtdUsada, $matId]);
    }
}




// ==========================================================
// INÍCIO: LANÇAMENTO/ATUALIZAÇÃO NO CONTAS A RECEBER
// ==========================================================

// Prepara os dados para a tabela 'receber'
$descricao_receber = "Venda Romaneio Nº " . $romaneio_venda_id;

// Esta variável já foi calculada no início, mas vamos usar o valor final aqui.
$total_liquido_final = floatval(str_replace(',', '.', $_POST['valor_liquido']));


if ($id != "") {
    // Se está EDITANDO, ATUALIZA a conta a receber existente
    $check_receber = $pdo->prepare("SELECT id FROM receber WHERE id_ref = ? AND referencia = 'Romaneio Venda'");
    $check_receber->execute([$romaneio_venda_id]);
    
    if ($check_receber->rowCount() > 0) {
        $query_receber = $pdo->prepare("UPDATE receber SET 
            cliente = :cliente,
            valor = :valor,
            vencimento = :vencimento,
            pago = 'Não',
            data_pgto = NULL,
            usuario_pgto = 0  -- <-- ADICIONADO AQUI
            WHERE id_ref = :id_ref AND referencia = 'Romaneio Venda'");
    } else {
        $id = ""; // Força a lógica de inserção se não encontrar um registro para atualizar
    }
}

if ($id == "") {
    // Se é NOVO (ou não encontrou na edição), INSERE uma nova conta a receber
    $query_receber = $pdo->prepare("INSERT INTO receber SET 
        descricao = :descricao,
        cliente = :cliente,
        valor = :valor,
        vencimento = :vencimento,
        data_lanc = CURDATE(),
        usuario_lanc = :usuario_lanc,
        pago = 'Não',
        referencia = 'Romaneio Venda',
        id_ref = :id_ref,
        usuario_pgto = 0  -- <-- ADICIONADO AQUI
    ");
    
    $query_receber->bindValue(":descricao", $descricao_receber);
    $query_receber->bindValue(":usuario_lanc", $id_usuario);
}

// Bind dos valores comuns para INSERT e UPDATE
$query_receber->bindValue(":cliente", $atacadista);
$query_receber->bindValue(":valor", $total_liquido_final);
$query_receber->bindValue(":vencimento", $vencimento);
$query_receber->bindValue(":id_ref", $romaneio_venda_id);
$query_receber->execute();

// ==========================================================
// FIM: LANÇAMENTO NO CONTAS A RECEBER
// ==========================================================



	$pdo->commit();
	echo json_encode([
		'status' => 'sucesso',
		'mensagem' => 'Romaneio salvo com sucesso!'
	]);

} catch (PDOException $e) {
	$pdo->rollBack();
	$mensagem_erro = 'Erro ao salvar: ';
	
	if (strpos($e->getMessage(), 'foreign key constraint')) {
		$mensagem_erro .= 'Um dos itens selecionados não existe mais no sistema';
	} else {
		$mensagem_erro .= 'Não foi possível salvar o romaneio';
	}
	
	error_log("Erro PDO: " . $e->getMessage());
	echo json_encode([
		'status' => 'erro',
		'mensagem' => $mensagem_erro . ' (Debug: ' . $e->getMessage() . ')'
	]);
} catch (Exception $e) {
	$pdo->rollBack();
	error_log("Erro genérico: " . $e->getMessage());
	echo json_encode([
		'status' => 'erro',
		'mensagem' => 'Erro inesperado ao salvar o romaneio'
	]);
}