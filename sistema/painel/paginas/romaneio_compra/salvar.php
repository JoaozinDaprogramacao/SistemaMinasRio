<?php
$tabela = 'romaneio_compra';
require_once("../../../conexao.php");
session_start();

header('Content-Type: application/json; charset=utf-8');

$debug_log = [];

function addLog($mensagem, $dados = null)
{
    global $debug_log;
    $entrada = ['hora' => date('H:i:s'), 'msg' => $mensagem];
    if ($dados !== null) $entrada['dados'] = $dados;
    $debug_log[] = $entrada;
}

function limparMoeda($valor)
{
    if (empty($valor)) return 0;
    $limpo = str_replace(['R$', ' ', '.'], '', $valor);
    $limpo = str_replace(',', '.', $limpo);
    return floatval($limpo);
}

try {
    addLog("INICIANDO SCRIPT SALVAR.PHP");

    if (!isset($pdo)) throw new Exception("Erro crítico: Variável de conexão inexistente.");

    $id_usuario = $_POST['id_usuario_request'] ?? $_SESSION['id'] ?? 0;
    
    $id          = $_POST['id']          ?? '';
    $fornecedor  = $_POST['fornecedor']  ?? '';
    $cliente     = $_POST['cliente']     ?? '';
    $data        = $_POST['data']        ?? '';
    $plano_pgto  = $_POST['plano_pgto']  ?? '';
    $quant_dias  = $_POST['quant_dias']  ?? '';
    $nota_fiscal = $_POST['nota_fiscal'] ?? '';
    $vencimento  = $_POST['vencimento']  ?? '';
    $fazenda     = $_POST['fazenda']     ?? '';
    $desc_avista = limparMoeda($_POST['desc-avista'] ?? '0');

    $query_taxas = $pdo->query("SELECT id, descricao FROM taxas_abatimentos");
    $taxas_cadastradas = $query_taxas->fetchAll(PDO::FETCH_ASSOC);

    $desc_funrural = 0; $desc_ima = 0; $desc_abanorte = 0; $desc_taxaadm = 0;
    $funrural_config_info = null; $ima_config_info = null; $abanorte_config_info = null;
    $taxa_adm_config_taxa_perc = null; $funrural_config_preco_unit = null;
    $ima_config_preco_unit = null; $abanorte_config_preco_unit = null; $taxa_adm_config_preco_unit = null;

    foreach ($taxas_cadastradas as $taxa) {
        $tid = $taxa['id'];
        $nome_taxa = strtoupper(trim($taxa['descricao']));
        $v_valor = limparMoeda($_POST['valor_' . $tid] ?? '0');
        $v_info  = $_POST['info_' . $tid] ?? '';
        $v_unit  = isset($_POST['preco_unit_' . $tid]) ? limparMoeda($_POST['preco_unit_' . $tid]) : 0;

        if ($nome_taxa == 'FUNRURAL') {
            $desc_funrural = $v_valor; $funrural_config_info = $v_info; $funrural_config_preco_unit = $v_unit;
        } else if ($nome_taxa == 'IMA') {
            $desc_ima = $v_valor; $ima_config_info = $v_info; $ima_config_preco_unit = $v_unit;
        } else if ($nome_taxa == 'ABANORTE') {
            $desc_abanorte = $v_valor; $abanorte_config_info = $v_info; $abanorte_config_preco_unit = $v_unit;
        } else if ($nome_taxa == 'TAXA ADM') {
            $desc_taxaadm = $v_valor; $taxa_adm_config_taxa_perc = ($v_info === '') ? 0 : $v_info; $taxa_adm_config_preco_unit = $v_unit;
        }
    }

    $tipos   = $_POST['desconto_tipo']  ?? [];
    $valores = $_POST['desconto_valor'] ?? [];
    $obs     = $_POST['desconto_obs']   ?? [];
    $descontos_diversos = [];

    foreach ($tipos as $i => $tipo) {
        $v_limpo = limparMoeda($valores[$i] ?? '0');
        if ($v_limpo > 0 && $tipo !== '') {
            $descontos_diversos[] = ['tipo' => $tipo, 'valor' => $v_limpo, 'obs' => trim($obs[$i] ?? '')];
        }
    }
    $descontos_json = !empty($descontos_diversos) ? json_encode($descontos_diversos, JSON_UNESCAPED_UNICODE) : null;

    $quant_caixa_1 = $_POST['quant_caixa_1'] ?? [];
    $produto_1     = $_POST['produto_1']     ?? [];
    $preco_kg_1    = $_POST['preco_kg_1']    ?? [];
    $tipo_cx_1     = $_POST['tipo_cx_1']     ?? [];
    $preco_unit_1  = $_POST['preco_unit_1']  ?? [];
    $valor_1       = $_POST['valor_1']       ?? [];

    $erros = [];
    if (empty($fornecedor) || $fornecedor == '0') $erros[] = "Selecione um fornecedor";
    if (empty($cliente)    || $cliente    == '0') $erros[] = "Selecione um cliente";
    if (empty($data)) $erros[] = "Data é obrigatória";
    if (empty($plano_pgto) || $plano_pgto == '0') $erros[] = "Selecione um plano de pagamento";

    $total_bruto = array_reduce($valor_1, function ($c, $v) { return $c + limparMoeda($v); }, 0);
    $total_bruto_desc = $total_bruto * (1 - ($desc_avista / 100));
    $soma_outros_descontos_fixos = $desc_funrural + $desc_ima + $desc_abanorte + $desc_taxaadm;
    $total_liquido = $total_bruto_desc - $soma_outros_descontos_fixos;

    foreach ($descontos_diversos as $dd_item) {
        $total_liquido += ($dd_item['tipo'] === '-') ? -$dd_item['valor'] : $dd_item['valor'];
    }

    $tem_produtos = false;
    foreach ($valor_1 as $k_prod => $v_prod_str) {
        if (limparMoeda($v_prod_str) > 0) {
            $tem_produtos = true;
            if (empty($produto_1[$k_prod])) $erros[] = "Selecione variedade na linha " . ($k_prod + 1);
        }
    }
    if (!$tem_produtos && count(array_filter($valor_1)) > 0) $erros[] = "Adicione produtos válidos.";
    if ($erros) throw new Exception(implode("<br>", $erros));

    $pdo->beginTransaction();

    $data_mysql = $data;
    if (!empty($data) && strpos($data, '/') !== false) {
        $timestamp = strtotime(str_replace('/', '-', $data));
        $data_mysql = $timestamp ? date('Y-m-d H:i:s', $timestamp) : date('Y-m-d H:i:s');
    }

    $params = [
        ':forn' => $fornecedor, ':cli' => $cliente, ':qd' => $quant_dias, ':dt' => $data_mysql,
        ':nf' => $nota_fiscal, ':pp' => $plano_pgto, ':ven' => $vencimento, ':tl' => $total_liquido,
        ':faz' => $fazenda, ':da' => $desc_avista, ':df' => $desc_funrural, ':di' => $desc_ima,
        ':dab' => $desc_abanorte, ':dtadm' => $desc_taxaadm, ':dd' => $descontos_json,
        ':fci' => $funrural_config_info, ':fcpu' => $funrural_config_preco_unit,
        ':ici' => $ima_config_info, ':icpu' => $ima_config_preco_unit,
        ':aci' => $abanorte_config_info, ':acpu' => $abanorte_config_preco_unit,
        ':atctp' => (empty($taxa_adm_config_taxa_perc)) ? 0 : $taxa_adm_config_taxa_perc,
        ':atcpu' => $taxa_adm_config_preco_unit
    ];

    if ($id === '') {
        $sql = "INSERT INTO {$tabela} (fornecedor, cliente, quant_dias, data, nota_fiscal, plano_pgto, vencimento, total_liquido, fazenda, desc_avista, desc_funrural, desc_ima, desc_abanorte, desc_taxaadm, descontos_diversos, funrural_config_info, funrural_config_preco_unit, ima_config_info, ima_config_preco_unit, abanorte_config_info, abanorte_config_preco_unit, taxa_adm_config_taxa_perc, taxa_adm_config_preco_unit, usado) VALUES (:forn, :cli, :qd, :dt, :nf, :pp, :ven, :tl, :faz, :da, :df, :di, :dab, :dtadm, :dd, :fci, :fcpu, :ici, :icpu, :aci, :acpu, :atctp, :atcpu, 0)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $romaneioId = $pdo->lastInsertId();
    } else {
        $sql = "UPDATE {$tabela} SET fornecedor=:forn, cliente=:cli, quant_dias=:qd, data=:dt, nota_fiscal=:nf, plano_pgto=:pp, vencimento=:ven, total_liquido=:tl, fazenda=:faz, desc_avista=:da, desc_funrural=:df, desc_ima=:di, desc_abanorte=:dab, desc_taxaadm=:dtadm, descontos_diversos=:dd, funrural_config_info=:fci, funrural_config_preco_unit=:fcpu, ima_config_info=:ici, ima_config_preco_unit=:icpu, abanorte_config_info=:aci, abanorte_config_preco_unit=:acpu, taxa_adm_config_taxa_perc=:atctp, taxa_adm_config_preco_unit=:atcpu WHERE id=:id_val";
        $params[':id_val'] = $id;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $romaneioId = $id;
    }

    $pdo->prepare("DELETE FROM linha_produto_compra WHERE id_romaneio = ?")->execute([$romaneioId]);

    foreach ($quant_caixa_1 as $key => $q_val) {
        if (empty($valor_1[$key]) || limparMoeda($valor_1[$key]) <= 0) continue;

        $tipo_cx_str = $tipo_cx_1[$key];
        $stmtTipoCx = $pdo->prepare("SELECT id FROM tipo_caixa WHERE tipo = ?");
        $stmtTipoCx->execute([$tipo_cx_str]);
        $tipoCxId = $stmtTipoCx->fetchColumn();

        if (!$tipoCxId) {
            $pdo->prepare("INSERT INTO tipo_caixa (tipo, unidade_medida) VALUES (?,1)")->execute([$tipo_cx_str]);
            $tipoCxId = $pdo->lastInsertId();
        }

        $sqlProd = "INSERT INTO linha_produto_compra (id_romaneio, quant, variedade, preco_kg, tipo_caixa, preco_unit, valor) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sqlProd)->execute([
            $romaneioId, limparMoeda($q_val), $produto_1[$key], 
            limparMoeda($preco_kg_1[$key] ?? 0), $tipoCxId, 
            limparMoeda($preco_unit_1[$key] ?? 0), limparMoeda($valor_1[$key])
        ]);
    }

    $pdo->prepare("DELETE FROM pagar WHERE id_ref = :id AND referencia = 'romaneio_compra'")->execute([':id' => $romaneioId]);

    if ($total_liquido > 0) {
        $sqlPagar = "INSERT INTO pagar (descricao, fornecedor, valor, vencimento, data_lanc, forma_pgto, frequencia, referencia, id_romaneio, usuario_lanc, usuario_pgto, funcionario, id_ref) VALUES (:desc, :forn, :valor, :ven, :dtl, :fp, '0', 'romaneio_compra', :idr, :ul, null, '0', :idref)";
        $pdo->prepare($sqlPagar)->execute([
            'desc' => "Romaneio Compra #{$romaneioId}", 'forn' => $fornecedor, 'valor' => $total_liquido,
            'ven' => $vencimento, 'dtl' => date('Y-m-d'), 'fp' => is_numeric($plano_pgto) ? (int)$plano_pgto : null,
            'idr' => $romaneioId, 'ul' => $id_usuario, 'idref' => $romaneioId
        ]);
    }

    $pdo->commit();
    echo json_encode(['status' => 'sucesso', 'mensagem' => 'Salvo com sucesso!', 'id' => $romaneioId, 'debug' => $debug_log], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    addLog("ERRO FATAL: " . $e->getMessage());
    echo json_encode(['status' => 'erro', 'mensagem' => 'Erro: ' . $e->getMessage(), 'debug' => $debug_log], JSON_UNESCAPED_UNICODE);
}