<?php
$tabela = 'romaneio_venda';
require_once("../../../conexao.php");

@session_start();
$id_usuario = @$_SESSION['id'];

header('Content-Type: application/json; charset=utf-8');

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
    gravarLog("INICIANDO SALVAR VENDA");

    $id = $_POST['id'] ?? '';
    $romaneios_selecionados = $_POST['romaneios_selecionados'] ?? '';
    $atacadista = $_POST['cliente'] ?? '';
    $data = $_POST['data'] ?? '';
    $plano_pgto = $_POST['plano_pgto'] ?? '';
    $nota_fiscal = $_POST['nota_fiscal'] ?? '';
    $vencimento = $_POST['vencimento'] ?? '';
    $quant_dias = $_POST['quant_dias'] ?: 0;
    $adicional = str_replace(',', '.', $_POST['valor_adicional'] ?? 0);
    $descricao_a = $_POST['descricao_adicional'] ?? '';
    $desconto_fixo = str_replace(',', '.', $_POST['valor_desconto'] ?? 0);
    $descricao_d = $_POST['descricao_desconto'] ?? '';
    $desc_avista_perc = !empty($_POST['desc-avista']) ? str_replace(',', '.', $_POST['desc-avista']) : 0;

    $quant_caixa_1 = $_POST['quant_caixa_1'] ?? [];
    $produto_1 = $_POST['produto_1'] ?? [];
    $preco_kg_1 = $_POST['preco_kg_1'] ?? [];
    $tipo_cx_1 = $_POST['tipo_cx_1'] ?? [];
    $preco_unit_1 = $_POST['preco_unit_1'] ?? [];
    $valor_1 = $_POST['valor_1'] ?? [];

    $desc_2 = $_POST['desc_2'] ?? [];
    $arr_valor_2 = $_POST['valor_2'] ?? [];
    $quant_caixa_2 = $_POST['quant_caixa_2'] ?? [];
    $preco_kg_2 = $_POST['preco_kg_2'] ?? [];
    $tipo_cx_2 = $_POST['tipo_cx_2'] ?? [];
    $preco_unit_2 = $_POST['preco_unit_2'] ?? [];

    $obs_3 = $_POST['obs_3'] ?? [];
    $material = $_POST['material'] ?? [];
    $quant_3 = $_POST['quant_3'] ?? [];
    $preco_unit_3 = $_POST['preco_unit_3'] ?? [];
    $valor_3 = $_POST['valor_3'] ?? [];

    $erros = [];
    if (empty($atacadista) || $atacadista == '0') $erros[] = "Selecione um cliente";
    if (empty($data)) $erros[] = "Data é obrigatória";
    if (empty($valor_1)) $erros[] = "Adicione pelo menos um produto";

    if (!empty($erros)) {
        echo json_encode(['status' => 'erro', 'mensagem' => implode("<br>", $erros)], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $total_bruto = 0;
    foreach ($valor_1 as $key => $v) {
        if (!empty($v) && !empty($produto_1[$key])) {
            $total_bruto += floatval(str_replace(',', '.', $v));
        }
    }

    $total_comissao = 0;
    foreach ($arr_valor_2 as $v2) {
        if (!empty($v2)) $total_comissao += floatval(str_replace(',', '.', $v2));
    }

    $total_materiais = 0;
    foreach ($valor_3 as $v3) {
        if (!empty($v3)) $total_materiais += floatval(str_replace(',', '.', $v3));
    }

    $valor_desc_avista = $total_bruto * ($desc_avista_perc / 100);
    $total_liquido = $total_bruto + $total_comissao + $total_materiais + floatval($adicional) - floatval($desconto_fixo) - $valor_desc_avista;

    $pdo->beginTransaction();

    if ($id == "") {
        $sql = "INSERT INTO $tabela SET atacadista=:atacadista, data=:data, nota_fiscal=:nota_fiscal, plano_pgto=:plano_pgto, vencimento=:vencimento, total_liquido=:total_liquido, quant_dias=:quant_dias, adicional=:adicional, descricao_a=:descricao_a, desconto=:desconto, descricao_d=:descricao_d, desc_avista=:desc_avista";
        $query = $pdo->prepare($sql);
    } else {
        // Estorno: Subtrai as vendas das categorias antes de atualizar os produtos
        $query_antigos = $pdo->prepare("SELECT lp.variedade, lp.quant, p.categoria FROM linha_produto lp INNER JOIN produtos p ON lp.variedade = p.id WHERE lp.id_romaneio = ?");
        $query_antigos->execute([$id]);
        foreach ($query_antigos->fetchAll(PDO::FETCH_ASSOC) as $antigo) {
            if (!empty($antigo['categoria'])) {
                $pdo->prepare("UPDATE categorias SET vendas = vendas - :qtd WHERE id = :id_cat")
                    ->execute(['qtd' => $antigo['quant'], 'id_cat' => $antigo['categoria']]);
            }
        }

        $sql = "UPDATE $tabela SET atacadista=:atacadista, data=:data, nota_fiscal=:nota_fiscal, plano_pgto=:plano_pgto, vencimento=:vencimento, total_liquido=:total_liquido, quant_dias=:quant_dias, adicional=:adicional, descricao_a=:descricao_a, desconto=:desconto, descricao_d=:descricao_d, desc_avista=:desc_avista WHERE id = :id";
        $query = $pdo->prepare($sql);
        $query->bindValue(":id", $id);
    }

    $query->bindValue(":atacadista", $atacadista);
    $query->bindValue(":data", $data);
    $query->bindValue(":nota_fiscal", $nota_fiscal);
    $query->bindValue(":plano_pgto", $plano_pgto);
    $query->bindValue(":vencimento", $vencimento);
    $query->bindValue(":total_liquido", $total_liquido);
    $query->bindValue(":quant_dias", $quant_dias);
    $query->bindValue(":adicional", $adicional);
    $query->bindValue(":descricao_a", $descricao_a);
    $query->bindValue(":desconto", $desconto_fixo);
    $query->bindValue(":descricao_d", $descricao_d);
    $query->bindValue(":desc_avista", $desc_avista_perc);
    $query->execute();

    $romaneio_venda_id = $id ?: $pdo->lastInsertId();

    if ($id != "") {
        $pdo->prepare("DELETE FROM romaneio_venda_compra WHERE id_romaneio_venda = ?")->execute([$romaneio_venda_id]);
        $pdo->prepare("DELETE FROM linha_produto WHERE id_romaneio = ?")->execute([$romaneio_venda_id]);
        $pdo->prepare("DELETE FROM linha_comissao WHERE id_romaneio = ?")->execute([$romaneio_venda_id]);
        $pdo->prepare("DELETE FROM linha_observacao WHERE id_romaneio = ?")->execute([$romaneio_venda_id]);
    }

    foreach ($valor_1 as $key => $valor) {
        if (!empty($valor)) {
            $pdo->prepare("INSERT INTO linha_produto (id_romaneio, quant, variedade, preco_kg, tipo_caixa, preco_unit, valor) VALUES (?, ?, ?, ?, ?, ?, ?)")
                ->execute([$romaneio_venda_id, $quant_caixa_1[$key], $produto_1[$key], str_replace(',', '.', $preco_kg_1[$key]), $tipo_cx_1[$key], str_replace(',', '.', $preco_unit_1[$key]), str_replace(',', '.', $valor)]);

            // Incrementa vendas na categoria
            $query_cat = $pdo->prepare("SELECT categoria FROM produtos WHERE id = ?");
            $query_cat->execute([$produto_1[$key]]);
            $res_c = $query_cat->fetch(PDO::FETCH_ASSOC);
            if ($res_c && !empty($res_c['categoria'])) {
                $pdo->prepare("UPDATE categorias SET vendas = vendas + :qtd WHERE id = :id_cat")
                    ->execute(['qtd' => $quant_caixa_1[$key], 'id_cat' => $res_c['categoria']]);
            }
        }
    }

    foreach ($obs_3 as $key => $observacao) {
        if (!empty($observacao) || !empty($material[$key])) {
            $pdo->prepare("INSERT INTO linha_observacao (id_romaneio, observacoes, descricao, quant, preco_unit, valor) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$romaneio_venda_id, $observacao, $material[$key] ?? null, $quant_3[$key] ?? 0, str_replace(',', '.', $preco_unit_3[$key] ?? 0), str_replace(',', '.', $valor_3[$key] ?? 0)]);
            
            $matId = (int)($material[$key] ?? 0);
            $qtdUsada = (int)($quant_3[$key] ?? 0);
            if ($matId > 0 && $qtdUsada > 0) {
                $check = $pdo->prepare("SELECT tem_estoque, estoque FROM materiais WHERE id = ?");
                $check->execute([$matId]);
                $row = $check->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $pdo->prepare("UPDATE materiais SET vendas = vendas + ? WHERE id = ?")->execute([$qtdUsada, $matId]);
                    if (strtoupper($row['tem_estoque']) === 'SIM') {
                        $pdo->prepare("UPDATE materiais SET estoque = estoque - ? WHERE id = ?")->execute([$qtdUsada, $matId]);
                    }
                }
            }
        }
    }

    foreach ($desc_2 as $key => $descricao) {
        if (!empty($descricao) && !empty($arr_valor_2[$key])) {
            $pdo->prepare("INSERT INTO linha_comissao (id_romaneio, quant_caixa, descricao, preco_kg, tipo_caixa, preco_unit, valor) VALUES (?, ?, ?, ?, ?, ?, ?)")
                ->execute([$romaneio_venda_id, $quant_caixa_2[$key] ?? 0, $descricao, str_replace(',', '.', $preco_kg_2[$key] ?? 0), $tipo_cx_2[$key] ?? '', str_replace(',', '.', $preco_unit_2[$key] ?? 0), str_replace(',', '.', $arr_valor_2[$key])]);
        }
    }

    $check_receber = $pdo->prepare("SELECT id FROM receber WHERE id_ref = ? AND referencia = 'Romaneio Venda'");
    $check_receber->execute([$romaneio_venda_id]);

    if ($check_receber->rowCount() > 0) {
        $query_receber = $pdo->prepare("UPDATE receber SET cliente=:cliente, valor=:valor, vencimento=:vencimento, usuario_pgto = 0 WHERE id_ref=:id_ref AND referencia='Romaneio Venda'");
    } else {
        $query_receber = $pdo->prepare("INSERT INTO receber SET descricao=:descricao, cliente=:cliente, valor=:valor, vencimento=:vencimento, data_lanc=CURDATE(), usuario_lanc=:usuario_lanc, pago='Não', referencia='Romaneio Venda', id_ref=:id_ref, usuario_pgto = 0");
        $query_receber->bindValue(":descricao", "Venda Romaneio Nº " . $romaneio_venda_id);
        $query_receber->bindValue(":usuario_lanc", $id_usuario);
    }
    $query_receber->bindValue(":cliente", $atacadista);
    $query_receber->bindValue(":valor", $total_liquido);
    $query_receber->bindValue(":vencimento", $vencimento);
    $query_receber->bindValue(":id_ref", $romaneio_venda_id);
    $query_receber->execute();

    if (!empty($romaneios_selecionados)) {
        $ids_array = explode(',', $romaneios_selecionados);
        $placeholders = implode(',', array_fill(0, count($ids_array), '?'));
        $pdo->prepare("UPDATE romaneio_compra SET usado = 1 WHERE id IN ($placeholders)")->execute($ids_array);
    }

    $pdo->commit();
    echo json_encode(['status' => 'sucesso', 'mensagem' => 'Romaneio salvo com sucesso!']);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    gravarLog("ERRO: " . $e->getMessage());
    echo json_encode(['status' => 'erro', 'mensagem' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>