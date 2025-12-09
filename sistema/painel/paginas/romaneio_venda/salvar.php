<?php
// sistema/painel/paginas/romaneio_venda/salvar.php
$tabela = 'romaneio_venda';
require_once("../../../conexao.php");

@session_start();
$id_usuario = @$_SESSION['id'];

// Configuração de cabeçalho JSON
header('Content-Type: application/json; charset=utf-8');

// --- FUNÇÃO DE LOG ---
function gravarLog($mensagem) {
    $arquivo = 'debug_log_venda.txt';
    $dataHora = date('d/m/Y H:i:s');
    if (is_array($mensagem) || is_object($mensagem)) {
        $mensagem = json_encode($mensagem, JSON_UNESCAPED_UNICODE);
    }
    $texto = "[$dataHora] $mensagem" . PHP_EOL;
    file_put_contents($arquivo, $texto, FILE_APPEND);
}

try {
    gravarLog("----------------------------------------------------------------");
    gravarLog("INICIANDO SALVAR VENDA");
    
    // Log do POST recebido
    gravarLog("DADOS RECEBIDOS (_POST):");
    gravarLog($_POST);

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

    $desc_avista = !empty($_POST['desc-avista']) ? $_POST['desc-avista'] : 0;

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
        gravarLog("ERROS DE VALIDAÇÃO:");
        gravarLog($erros);
        echo json_encode([
            'status' => 'erro',
            'mensagem' => implode("<br>", $erros)
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // --- VARIÁVEIS DO POST ---
    $id = $_POST['id'];
    $romaneios_selecionados = $_POST['romaneios_selecionados'] ?? '';
    $atacadista = $_POST['cliente'];
    $data = $_POST['data'];
    $plano_pgto = $_POST['plano_pgto'];
    $nota_fiscal = $_POST['nota_fiscal'];
    $vencimento = $_POST['vencimento'];
    $quant_dias = $_POST['quant_dias'] ?: 0;
    $adicional = $_POST['valor_adicional'] ?? 0;
    $descricao_a = $_POST['descricao_adicional'] ?? '';
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

    // --- ÁREA DE CÁLCULO ---
    gravarLog("Iniciando Cálculos Matemáticos...");

    // 1. Soma do total bruto de produtos (Banana)
    $total_bruto = 0;
    foreach ($valor_1 as $key => $v) {
        if (!empty($v) && !empty($produto_1[$key])) {
            $total_bruto += floatval(str_replace(',', '.', $v));
        }
    }
    gravarLog("Total Bruto Produtos: $total_bruto");

    // 2. Soma das comissões
    $total_comissao = 0;
    $arr_desc_2 = $_POST['desc_2'] ?? [];
    $arr_valor_2 = $_POST['valor_2'] ?? [];

    if (!empty($arr_valor_2)) {
        foreach ($arr_valor_2 as $key => $v2) {
            $desc = $arr_desc_2[$key] ?? '';
            if (!empty($v2) && !empty($desc)) {
                $total_comissao += floatval(str_replace(',', '.', $v2));
            }
        }
    }
    gravarLog("Total Comissões: $total_comissao");

    // 3. Soma dos materiais
    $total_materiais = 0;
    $arr_obs_3 = $_POST['obs_3'] ?? [];
    $arr_material = $_POST['material'] ?? [];
    $arr_valor_3 = $_POST['valor_3'] ?? [];

    if (!empty($arr_valor_3)) {
        foreach ($arr_valor_3 as $key => $v3) {
            $obs = $arr_obs_3[$key] ?? '';
            $mat = $arr_material[$key] ?? '';
            if (!empty($v3) && (!empty($obs) || !empty($mat))) {
                $total_materiais += floatval(str_replace(',', '.', $v3));
            }
        }
    }
    gravarLog("Total Materiais: $total_materiais");

    // --- 4) adicional fixo ---
    $valor_adicional = floatval(str_replace(',', '.', $adicional));

    // --- 5) desconto fixo ---
    $valor_desconto = floatval(str_replace(',', '.', $desconto));

    // --- 6) desconto à vista (%) sobre o total bruto ---
    $perc_avista        = floatval(str_replace(',', '.', $desc_avista));
    $valor_desc_avista = $total_bruto * ($perc_avista / 100);

    // --- 7) total líquido final ---
    $total_liquido = $total_bruto
        + $total_comissao
        + $total_materiais
        + $valor_adicional
        - $valor_desconto
        - $valor_desc_avista;
    
    gravarLog("Total Líquido Final Calculado: $total_liquido");

    // validação básica
    if ($total_bruto <= 0) {
        $erros[] = "O total bruto deve ser maior que zero";
    }

    if (!empty($erros)) {
        gravarLog("ERRO FINAL CÁLCULO:");
        gravarLog($erros);
        echo json_encode([
            'status' => 'erro',
            'mensagem' => implode("<br>", $erros)
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }


    // =================================================================================
    // INÍCIO DA TRANSAÇÃO
    // =================================================================================
    gravarLog("Iniciando Transação (beginTransaction)");
    $pdo->beginTransaction();

    if ($id == "") {
        // INSERT
        $sql = "INSERT INTO $tabela SET 
            atacadista   = :atacadista, 
            data             = :data, 
            nota_fiscal  = :nota_fiscal, 
            plano_pgto   = :plano_pgto, 
            vencimento   = :vencimento, 
            total_liquido    = :total_liquido, 
            quant_dias   = :quant_dias,
            adicional        = :adicional,
            descricao_a  = :descricao_a,
            desconto         = :desconto,
            descricao_d  = :descricao_d,
            desc_avista  = :desc_avista
        ";
        gravarLog("Executando INSERT CABEÇALHO");
        $query = $pdo->prepare($sql);
    } else {
        // UPDATE
        $sql = "UPDATE $tabela SET 
            atacadista   = :atacadista, 
            data             = :data, 
            nota_fiscal  = :nota_fiscal, 
            plano_pgto   = :plano_pgto, 
            vencimento   = :vencimento, 
            total_liquido    = :total_liquido, 
            quant_dias   = :quant_dias,
            adicional        = :adicional,
            descricao_a  = :descricao_a,
            desconto         = :desconto,
            descricao_d  = :descricao_d,
            desc_avista  = :desc_avista
          WHERE id = :id";
        gravarLog("Executando UPDATE CABEÇALHO ID: $id");
        $query = $pdo->prepare($sql);
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

    $valor_para_banco = floatval(str_replace(',', '.', $desc_avista));
    $query->bindValue(":desc_avista", $valor_para_banco);

    if (!$query->execute()) {
        $erroBanco = $query->errorInfo();
        gravarLog("ERRO SQL CABEÇALHO: " . $erroBanco[2]);
        throw new Exception("Erro ao salvar cabeçalho: " . $erroBanco[2]);
    }

    $romaneio_venda_id = $id ?: $pdo->lastInsertId();
    gravarLog("ID Romaneio Venda: " . $romaneio_venda_id);

    // Limpar relacionamentos e linhas anteriores se for update
    if ($id != "") {
        gravarLog("Limpando dados antigos...");
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

            $paramsProd = [
                $romaneio_venda_id,
                $quant_caixa_1[$key],
                $produto_1[$key],
                str_replace(',', '.', $preco_kg_1[$key]),
                $tipo_cx_1[$key],
                str_replace(',', '.', $preco_unit_1[$key]),
                str_replace(',', '.', $valor)
            ];

            if (!$query->execute($paramsProd)) {
                $errP = $query->errorInfo();
                gravarLog("Erro insert produto: " . $errP[2]);
            }
        }
    }

    // Campos das comissões
    $desc_2 = $_POST['desc_2'] ?? [];
    $quant_caixa_2 = $_POST['quant_caixa_2'] ?? [];
    $preco_kg_2 = $_POST['preco_kg_2'] ?? [];
    $tipo_cx_2 = $_POST['tipo_cx_2'] ?? [];
    $preco_unit_2 = $_POST['preco_unit_2'] ?? [];
    $valor_2 = $_POST['valor_2'] ?? [];

    if ($id != "") {
        $pdo->prepare("DELETE FROM linha_comissao WHERE id_romaneio = ?")->execute([$romaneio_venda_id]);
    }

    foreach ($desc_2 as $key => $descricao) {
        if (!empty($descricao) && !empty($valor_2[$key])) {
            $query = $pdo->prepare("INSERT INTO linha_comissao (
                id_romaneio, quant_caixa, descricao, preco_kg, tipo_caixa, preco_unit, valor
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

    if ($id != "") {
        $pdo->prepare("DELETE FROM linha_observacao WHERE id_romaneio = ?")->execute([$romaneio_venda_id]);
    }

    // ==========================================================
    // INÍCIO: LÓGICA DE OBSERVAÇÃO E ESTOQUE
    // ==========================================================
    foreach ($obs_3 as $key => $observacao) {
        if (!empty($observacao) || !empty($material[$key])) {

            // 1) Inserção da observação
            $query = $pdo->prepare("INSERT INTO linha_observacao (
                id_romaneio, observacoes, descricao, quant, preco_unit, valor
            ) VALUES (?, ?, ?, ?, ?, ?)");
            
            $query->execute([
                $romaneio_venda_id,
                $observacao,
                $material[$key] ?? null,
                $quant_3[$key] ?? 0,
                str_replace(',', '.', $preco_unit_3[$key] ?? 0),
                str_replace(',', '.', $valor_3[$key] ?? 0)
            ]);

            // 2) Atualizar estoque do material
            $matId = (int)($material[$key] ?? 0);
            $qtdUsada = (int)($quant_3[$key] ?? 0);

            if ($matId <= 0 || $qtdUsada <= 0) {
                continue;
            }

            gravarLog("Verificando estoque - Material ID: $matId, Qtd: $qtdUsada");

            $check = $pdo->prepare("SELECT nome, tem_estoque, estoque FROM materiais WHERE id = ?");
            $check->execute([$matId]);
            $row = $check->fetch(PDO::FETCH_ASSOC);

            if (!$row) continue;

            // Incrementar vendas
            $upd2 = $pdo->prepare("UPDATE materiais SET vendas = vendas + ? WHERE id = ?");
            $upd2->execute([$qtdUsada, $matId]);

            // Checagem de estoque
            if (strtoupper($row['tem_estoque']) === 'SIM') {
                if ($row['estoque'] < $qtdUsada) {
                    $nomeMaterial = $row['nome'] ?? "ID {$matId}";
                    gravarLog("ERRO ESTOQUE: $nomeMaterial. Disp: {$row['estoque']}, Req: $qtdUsada");
                    throw new Exception("Estoque insuficiente para o material: '{$nomeMaterial}'");
                }

                $upd = $pdo->prepare("UPDATE materiais SET estoque = estoque - ? WHERE id = ?");
                $upd->execute([$qtdUsada, $matId]);
                gravarLog("Estoque debitado com sucesso.");
            }
        }
    }


    // ==========================================================
    // INÍCIO: LANÇAMENTO/ATUALIZAÇÃO NO CONTAS A RECEBER
    // ==========================================================
    gravarLog("Iniciando Contas a Receber...");

    $descricao_receber = "Venda Romaneio Nº " . $romaneio_venda_id;
    $total_liquido_final = $total_liquido;

    if ($id != "") {
        // UPDATE
        $check_receber = $pdo->prepare("SELECT id FROM receber WHERE id_ref = ? AND referencia = 'Romaneio Venda'");
        $check_receber->execute([$romaneio_venda_id]);

        if ($check_receber->rowCount() > 0) {
            $query_receber = $pdo->prepare("UPDATE receber SET 
            cliente = :cliente,
            valor = :valor,
            vencimento = :vencimento,
            pago = 'Não',
            data_pgto = NULL,
            usuario_pgto = 0
            WHERE id_ref = :id_ref AND referencia = 'Romaneio Venda'");
            gravarLog("Atualizando Receber existente.");
        } else {
            $id = ""; // Força insert
        }
    }

    if ($id == "") {
        // INSERT
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
        usuario_pgto = 0
        ");
        $query_receber->bindValue(":descricao", $descricao_receber);
        $query_receber->bindValue(":usuario_lanc", $id_usuario);
        gravarLog("Inserindo novo Receber.");
    }

    $query_receber->bindValue(":cliente", $atacadista);
    $query_receber->bindValue(":valor", $total_liquido_final);
    $query_receber->bindValue(":vencimento", $vencimento);
    $query_receber->bindValue(":id_ref", $romaneio_venda_id);
    
    if (!$query_receber->execute()) {
        $errR = $query_receber->errorInfo();
        gravarLog("ERRO RECEBER: " . $errR[2]);
        throw new Exception("Erro ao salvar contas a receber: " . $errR[2]);
    }


    // Atualiza romaneios de compra como usados
    $ids_romaneios_compra = $_POST['romaneios_selecionados'];
    $ids_array = explode(',', $ids_romaneios_compra);

    if (!empty($ids_array)) {
        gravarLog("Marcando romaneios de compra como usados: $ids_romaneios_compra");
        $placeholders = implode(',', array_fill(0, count($ids_array), '?'));
        $stmt_update = $pdo->prepare("UPDATE romaneio_compra SET usado = 1 WHERE id IN ($placeholders)");
        $stmt_update->execute($ids_array);
    }

    $pdo->commit();
    gravarLog("Sucesso! Commit realizado.");

    echo json_encode([
        'status' => 'sucesso',
        'mensagem' => 'Romaneio salvo com sucesso!'
    ]);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    gravarLog("ERRO PDO CATCH: " . $e->getMessage());
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Erro de Banco de Dados: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    gravarLog("ERRO GERAL CATCH: " . $e->getMessage());
    echo json_encode([
        'status' => 'erro',
        'mensagem' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>