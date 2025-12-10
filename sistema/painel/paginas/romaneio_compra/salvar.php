<?php
// sistema/painel/paginas/romaneio_compra/salvar.php
$tabela = 'romaneio_compra';
require_once("../../../conexao.php");
session_start();

// Define o cabeçalho como JSON
header('Content-Type: application/json; charset=utf-8');

// --- FUNÇÃO DE LOG PARA DEBUG ULTRA DETALHADO ---
function gravarLog($mensagem) {
    $arquivo = 'debug_romaneio_log.txt'; // Nome do arquivo de log
    $dataHora = date('d/m/Y H:i:s');
    
    // Formata arrays e objetos para leitura humana
    if (is_array($mensagem) || is_object($mensagem)) {
        $mensagem = json_encode($mensagem, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    
    $texto = "[$dataHora] > $mensagem" . PHP_EOL;
    // Usa LOCK_EX para evitar conflito se duas pessoas salvarem ao mesmo tempo
    file_put_contents($arquivo, $texto, FILE_APPEND | LOCK_EX);
}

try {
    // Limpa visualmente o log a cada nova requisição (opcional, se quiser histórico remova a linha abaixo)
    // file_put_contents('debug_romaneio_log.txt', "=== NOVO PROCESSO DE SALVAMENTO INICIADO ===\n");

    gravarLog("==================================================================");
    gravarLog("INICIANDO SCRIPT SALVAR.PHP");

    // 1. VERIFICAR CONEXÃO
    if (!isset($pdo)) {
        throw new Exception("Erro crítico: A variável de conexão (\$pdo) não foi carregada.");
    }
    gravarLog("Conexão com Banco de Dados: OK");

    $id_usuario = $_SESSION['id'] ?? null;
    gravarLog("Usuário da Sessão ID: " . ($id_usuario ?? 'NÃO LOGADO'));

    // --- RECEBIMENTO DOS DADOS ---
    gravarLog("--- RECEBENDO DADOS BRUTOS (_POST) ---");
    gravarLog($_POST);

    $id          = $_POST['id']          ?? '';
    $fornecedor  = $_POST['fornecedor']  ?? '';
    $cliente     = $_POST['cliente']     ?? '';
    $data        = $_POST['data']        ?? '';
    $plano_pgto  = $_POST['plano_pgto']  ?? '';
    $quant_dias  = $_POST['quant_dias']  ?? '';
    $nota_fiscal = $_POST['nota_fiscal'] ?? '';
    $vencimento  = $_POST['vencimento']  ?? '';
    $fazenda     = $_POST['fazenda']     ?? '';
    
    // Tratamento numérico
    $desc_avista = floatval(str_replace(',', '.', $_POST['desc-avista'] ?? '0'));
    
    // Descontos fixos
    $desc_funrural = floatval(str_replace(',', '.', $_POST['desc_funrural']     ?? '0'));
    $desc_ima      = floatval(str_replace(',', '.', $_POST['desc_ima']          ?? '0'));
    $desc_abanorte = floatval(str_replace(',', '.', $_POST['desc_abanorte']     ?? '0'));
    $desc_taxaadm  = floatval(str_replace(',', '.', $_POST['valor_taxa_adm']    ?? '0'));

    // Configurações extras (serialize/json)
    $funrural_config_info       = $_POST['info_funrural'] ?? null;
    $funrural_config_preco_unit = isset($_POST['preco_unit_funrural']) && $_POST['preco_unit_funrural'] !== '' ? floatval(str_replace(',', '.', $_POST['preco_unit_funrural'])) : null;

    $ima_config_info       = $_POST['info_ima'] ?? null;
    $ima_config_preco_unit = isset($_POST['preco_unit_ima']) && $_POST['preco_unit_ima'] !== '' ? floatval(str_replace(',', '.', $_POST['preco_unit_ima'])) : null;

    $abanorte_config_info       = $_POST['info_abanorte'] ?? null;
    $abanorte_config_preco_unit = isset($_POST['preco_unit_abanorte']) && $_POST['preco_unit_abanorte'] !== '' ? floatval(str_replace(',', '.', $_POST['preco_unit_abanorte'])) : null;

    $taxa_adm_config_taxa_perc  = isset($_POST['taxa_adm_percent']) && $_POST['taxa_adm_percent'] !== '' ? floatval(str_replace(',', '.', $_POST['taxa_adm_percent'])) : null;
    $taxa_adm_config_preco_unit = isset($_POST['preco_unit_taxa_adm']) && $_POST['preco_unit_taxa_adm'] !== '' ? floatval(str_replace(',', '.', $_POST['preco_unit_taxa_adm'])) : null;

    // --- PROCESSAMENTO DE DESCONTOS DIVERSOS ---
    gravarLog("--- PROCESSANDO DESCONTOS DIVERSOS ---");
    $tipos   = $_POST['desconto_tipo']  ?? [];
    $valores = $_POST['desconto_valor'] ?? [];
    $obs     = $_POST['desconto_obs']   ?? [];
    $descontos_diversos = [];
    
    foreach ($tipos as $i => $tipo) {
        $v_str = str_replace(',', '.', $valores[$i] ?? '0');
        if ($tipo !== '' && is_numeric($v_str)) {
            $v = floatval($v_str);
            $descontos_diversos[] = [
                'tipo'  => $tipo,
                'valor' => $v,
                'obs'   => trim($obs[$i] ?? '')
            ];
        }
    }
    $descontos_json = count($descontos_diversos) > 0 ? json_encode($descontos_diversos, JSON_UNESCAPED_UNICODE) : null;
    gravarLog("JSON Gerado para Descontos Diversos: " . ($descontos_json ?? 'NULL'));

    // --- ARRAYS DE PRODUTOS ---
    $quant_caixa_1 = $_POST['quant_caixa_1'] ?? [];
    $produto_1     = $_POST['produto_1']     ?? [];
    $preco_kg_1    = $_POST['preco_kg_1']    ?? [];
    $tipo_cx_1     = $_POST['tipo_cx_1']     ?? [];
    $preco_unit_1  = $_POST['preco_unit_1']  ?? [];
    $valor_1       = $_POST['valor_1']       ?? [];

    // --- VALIDAÇÕES ---
    gravarLog("--- INICIANDO VALIDAÇÕES ---");
    $erros = [];
    if (empty($fornecedor) || $fornecedor == '0') $erros[] = "Selecione um fornecedor";
    if (empty($cliente)    || $cliente    == '0') $erros[] = "Selecione um cliente";
    if (empty($data))                             $erros[] = "Data é obrigatória";
    if (empty($plano_pgto) || $plano_pgto == '0') $erros[] = "Selecione um plano de pagamento";

    // Valida desconto à vista
    $nomePlanoPgtoSelecionado = '';
    if (!empty($plano_pgto) && is_numeric($plano_pgto)) {
        $queryPlano = $pdo->prepare("SELECT nome FROM planos_pgto WHERE id = ?");
        $queryPlano->execute([$plano_pgto]);
        $nomePlanoPgtoSelecionado = $queryPlano->fetchColumn();
    }
    if (strtoupper(trim($nomePlanoPgtoSelecionado ?? '')) === 'À VISTA' && $desc_avista <= 0) {
        $erros[] = "Para pagamento à vista, o desconto percentual é obrigatório e deve ser maior que zero.";
    }

    // --- CÁLCULO DE TOTAIS ---
    gravarLog("--- CALCULANDO TOTAIS ---");
    $total_bruto      = array_reduce($valor_1, fn($c, $v_prod) => $c + floatval(str_replace(',', '.', $v_prod)), 0);
    $total_bruto_desc = $total_bruto * (1 - ($desc_avista / 100));
    $soma_outros_descontos_fixos = $desc_funrural + $desc_ima + $desc_abanorte + $desc_taxaadm;
    $total_liquido = $total_bruto_desc - $soma_outros_descontos_fixos;

    // Adicionar soma dos descontos diversos
    $soma_descontos_diversos_val = 0;
    foreach ($descontos_diversos as $dd_item) {
        if ($dd_item['tipo'] === '-') {
            $soma_descontos_diversos_val -= $dd_item['valor'];
        } else if ($dd_item['tipo'] === '+') {
            $soma_descontos_diversos_val += $dd_item['valor'];
        }
    }
    $total_liquido += $soma_descontos_diversos_val;
    
    gravarLog("Total Bruto: $total_bruto");
    gravarLog("Total Líquido Final: $total_liquido");

    // Validação de Produtos (existência)
    $tem_produtos = false;
    foreach ($valor_1 as $k_prod => $v_prod_str) {
        $v_prod = floatval(str_replace(',', '.', $v_prod_str));
        if ($v_prod_str !== '' && $v_prod > 0) {
            $tem_produtos = true;
            if (empty($produto_1[$k_prod])) {
                $erros[] = "Selecione variedade na linha " . ($k_prod + 1);
            }
        }
    }
    if (!$tem_produtos && count(array_filter($valor_1)) > 0) {
        $erros[] = "Adicione produtos válidos.";
    }

    if ($erros) {
        gravarLog("ERRO DE VALIDAÇÃO DETECTADO: " . json_encode($erros));
        throw new Exception(implode("<br>", $erros));
    }

    // Prepara arrays limpos
    function filtrar(array $a) {
        return array_values(array_filter($a, function ($v) { return $v !== '' && $v !== null; }));
    }
    $quant_caixa_1_val = filtrar($quant_caixa_1);
    $produto_1_val     = filtrar($produto_1);
    $preco_kg_1_val    = filtrar($preco_kg_1);
    $tipo_cx_1_val     = filtrar($tipo_cx_1);
    $preco_unit_1_val  = filtrar($preco_unit_1);
    $valor_1_val       = filtrar($valor_1);

    // =================================================================================
    // BANCO DE DADOS - INÍCIO
    // =================================================================================
    gravarLog(">>> INICIANDO TRANSAÇÃO (PDO beginTransaction) <<<");
    $pdo->beginTransaction(); 

    // Conversão Data
    $data_mysql = $data;
    if (!empty($data) && strpos($data, '/') !== false) {
        $timestamp = strtotime(str_replace('/', '-', $data));
        $data_mysql = $timestamp ? date('Y-m-d H:i:s', $timestamp) : date('Y-m-d H:i:s');
    }

    // Parâmetros para Insert/Update
    $params = [
        ':forn'   => $fornecedor,
        ':cli'    => $cliente,
        ':qd'     => $quant_dias,
        ':dt'     => $data_mysql,
        ':nf'     => $nota_fiscal,
        ':pp'     => $plano_pgto,
        ':ven'    => $vencimento,
        ':tl'     => $total_liquido,
        ':faz'    => $fazenda,
        ':da'     => $desc_avista,
        ':df'     => $desc_funrural,
        ':di'     => $desc_ima,
        ':dab'    => $desc_abanorte,
        ':dtadm'  => $desc_taxaadm,
        ':dd'     => $descontos_json,
        ':fci'    => $funrural_config_info,
        ':fcpu'   => $funrural_config_preco_unit,
        ':ici'    => $ima_config_info,
        ':icpu'   => $ima_config_preco_unit,
        ':aci'    => $abanorte_config_info,
        ':acpu'   => $abanorte_config_preco_unit,
        ':atctp'  => $taxa_adm_config_taxa_perc,
        ':atcpu'  => $taxa_adm_config_preco_unit
    ];

    if ($id === '') {
        // --- INSERT ---
        gravarLog("Malandragem: ID vazio, então é INSERT.");
        $sql = "INSERT INTO {$tabela}
            (fornecedor, cliente, quant_dias, data, nota_fiscal, plano_pgto, vencimento,
             total_liquido, fazenda, desc_avista,
             desc_funrural, desc_ima, desc_abanorte, desc_taxaadm, descontos_diversos,
             funrural_config_info, funrural_config_preco_unit, 
             ima_config_info, ima_config_preco_unit,
             abanorte_config_info, abanorte_config_preco_unit,
             taxa_adm_config_taxa_perc, taxa_adm_config_preco_unit,
             usado)
            VALUES
             (:forn, :cli, :qd, :dt, :nf, :pp, :ven, :tl, :faz, :da,
              :df,    :di,    :dab,    :dtadm,    :dd,
              :fci,  :fcpu,
              :ici,  :icpu,
              :aci,  :acpu,
              :atctp, :atcpu,
              0)";
        
        gravarLog("SQL PREPARADO: " . $sql);
        gravarLog("PARÂMETROS INSERT: " . json_encode($params));

        $stmt = $pdo->prepare($sql);
        if (!$stmt->execute($params)) {
            $err = $stmt->errorInfo();
            throw new Exception("Erro INSERT Tabela Principal: " . $err[2]);
        }
        
        $romaneioId = $pdo->lastInsertId();
        gravarLog("SUCESSO: Romaneio Criado com ID: " . $romaneioId);

    } else {
        // --- UPDATE ---
        gravarLog("Malandragem: ID existente ($id), então é UPDATE.");
        $sql = "UPDATE {$tabela} SET
             fornecedor       = :forn, cliente        = :cli, quant_dias       = :qd,
             data             = :dt,      nota_fiscal      = :nf, plano_pgto       = :pp,
             vencimento       = :ven,     total_liquido    = :tl, fazenda           = :faz,
             desc_avista      = :da,
             desc_funrural    = :df,      desc_ima         = :di, desc_abanorte    = :dab,
             desc_taxaadm     = :dtadm, descontos_diversos = :dd,
             funrural_config_info = :fci, funrural_config_preco_unit = :fcpu,
             ima_config_info = :ici, ima_config_preco_unit = :icpu,
             abanorte_config_info = :aci, abanorte_config_preco_unit = :acpu,
             taxa_adm_config_taxa_perc = :atctp, taxa_adm_config_preco_unit = :atcpu
            WHERE id = :id_val";
        
        $params[':id_val'] = $id;

        gravarLog("SQL PREPARADO: " . $sql);
        gravarLog("PARÂMETROS UPDATE: " . json_encode($params));

        $stmt = $pdo->prepare($sql);
        if (!$stmt->execute($params)) {
            $err = $stmt->errorInfo();
            throw new Exception("Erro UPDATE Tabela Principal: " . $err[2]);
        }
        $romaneioId = $id;
        gravarLog("SUCESSO: Romaneio ID $id Atualizado.");
    }

    // 4. PRODUTOS
    gravarLog("=== INICIO ETAPA PRODUTOS ===");
    
    // Deletar antigos
    gravarLog("Deletando itens antigos do romaneio $romaneioId...");
    $delProd = $pdo->prepare("DELETE FROM linha_produto_compra WHERE id_romaneio = ?");
    $delProd->execute([$romaneioId]);
    gravarLog("Itens antigos deletados.");

    // Inserir novos
    if (count($quant_caixa_1_val) > 0) {
        $sqlProd = "INSERT INTO linha_produto_compra
             (id_romaneio, quant, variedade, preco_kg, tipo_caixa, preco_unit, valor)
             VALUES (?, ?, ?, ?, ?, ?, ?)";
        $insertLinha = $pdo->prepare($sqlProd);

        foreach ($quant_caixa_1_val as $key => $q_val) {
            if (!isset($produto_1_val[$key], $valor_1_val[$key], $tipo_cx_1_val[$key])) continue;

            // Prepara dados do loop
            $v_final    = str_replace(',', '.', $valor_1_val[$key]);
            $var_final  = $produto_1_val[$key];
            $pkg_final  = isset($preco_kg_1_val[$key]) ? str_replace(',', '.', $preco_kg_1_val[$key]) : '0';
            $pun_final  = isset($preco_unit_1_val[$key]) ? str_replace(',', '.', $preco_unit_1_val[$key]) : '0';
            $tipo_cx_str= $tipo_cx_1_val[$key];

            // Verifica tipo caixa
            $stmtTipoCx = $pdo->prepare("SELECT id FROM tipo_caixa WHERE tipo = ?");
            $stmtTipoCx->execute([$tipo_cx_str]);
            $tipoCxId = $stmtTipoCx->fetchColumn();

            if (!$tipoCxId) {
                gravarLog("Aviso: Tipo de caixa '$tipo_cx_str' não existe. Criando agora.");
                $stmtInsTipoCx = $pdo->prepare("INSERT INTO tipo_caixa (tipo, unidade_medida) VALUES (?,1)");
                $stmtInsTipoCx->execute([$tipo_cx_str]);
                $tipoCxId = $pdo->lastInsertId();
            }

            $paramsProd = [
                $romaneioId,
                floatval(str_replace(',', '.', $q_val)),
                $var_final,
                $pkg_final,
                $tipoCxId,
                $pun_final,
                $v_final
            ];

            gravarLog("Inserindo Produto [Index $key]: " . json_encode($paramsProd));
            
            if (!$insertLinha->execute($paramsProd)) {
                $errP = $insertLinha->errorInfo();
                throw new Exception("Erro INSERT Produto $key: " . $errP[2]);
            }
        }
    } else {
        gravarLog("Atenção: Nenhum produto válido encontrado nos arrays para inserir.");
    }

    // 5. FINANCEIRO (CONTAS A PAGAR)
    gravarLog("=== INICIO ETAPA FINANCEIRO ===");
    
    // Deleta anterior
    $deleteRec = $pdo->prepare("DELETE FROM pagar WHERE id_ref = :id_ref AND referencia = 'romaneio_compra'");
    $deleteRec->execute([':id_ref' => $romaneioId]);
    gravarLog("Financeiro antigo deletado.");

    if ($total_liquido > 0) {
        $formaPgtoRec   = is_numeric($plano_pgto) ? (int)$plano_pgto : null;
        $usuarioLancRec = $id_usuario;

        $sqlPagar = "INSERT INTO pagar
            (descricao, fornecedor, valor, vencimento, data_lanc, forma_pgto, frequencia, referencia, id_romaneio, usuario_lanc, usuario_pgto, funcionario, id_ref)
        VALUES
            (:descricao, :fornecedor, :valor, :vencimento, :data_lanc, :forma_pgto, '0', 'romaneio_compra', :id_romaneio_fk, :usuario_lanc, :usuario_pgto, '0', :id_ref_pagar)";

        $insRec = $pdo->prepare($sqlPagar);

        $paramsPagar = [
            'descricao'      => "Romaneio Compra #{$romaneioId}",
            'fornecedor'     => $fornecedor,
            'valor'          => $total_liquido,
            'vencimento'     => $vencimento,
            'data_lanc'      => date('Y-m-d'),
            'forma_pgto'     => $formaPgtoRec,
            'id_romaneio_fk' => $romaneioId,
            'usuario_lanc'   => $usuarioLancRec,
            'usuario_pgto'   => null,
            'id_ref_pagar'   => $romaneioId
        ];

        gravarLog("Criando Conta a Pagar: " . json_encode($paramsPagar));

        if (!$insRec->execute($paramsPagar)) {
             $errFin = $insRec->errorInfo();
             throw new Exception("Erro INSERT Financeiro: " . $errFin[2]);
        }
        gravarLog("Financeiro gerado com sucesso.");
    } else {
        gravarLog("Pulei o financeiro: Total Líquido é zero ou negativo.");
    }

    // CONFIRMA TUDO
    $pdo->commit();
    gravarLog(">>> COMMIT REALIZADO COM SUCESSO <<<");
    gravarLog("Script finalizado corretamente.");

    echo json_encode([
        'status'   => 'sucesso',
        'mensagem' => 'Salvo com sucesso!',
        'id'       => $romaneioId
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    // ERRO
    $msgErro = $e->getMessage();
    $linhaErro = $e->getLine();
    
    gravarLog("!!! ERRO FATAL !!!");
    gravarLog("Mensagem: " . $msgErro);
    gravarLog("Linha: " . $linhaErro);

    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
        gravarLog("ROLLBACK executado (alterações desfeitas).");
    }
    
    echo json_encode([
        'status'   => 'erro',
        'mensagem' => 'Erro: ' . $msgErro
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>