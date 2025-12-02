<?php
// sistema/painel/paginas/romaneio_compra/salvar.php
$tabela = 'romaneio_compra';
require_once("../../../conexao.php");
session_start();

// Define o cabeçalho como JSON para garantir que o JavaScript entenda a resposta
header('Content-Type: application/json; charset=utf-8');

try {
    // 1. DICA DA VPS: Verificar conexão imediatamente
    if (!isset($pdo)) {
        throw new Exception("Erro crítico: A variável de conexão ($pdo) não foi carregada.");
    }

    $id_usuario = $_SESSION['id'] ?? null;

    // --- RECEBIMENTO DOS DADOS ---
    $id          = $_POST['id']          ?? '';
    $fornecedor  = $_POST['fornecedor']  ?? '';
    $cliente     = $_POST['cliente']     ?? '';
    $data        = $_POST['data']        ?? '';
    $plano_pgto  = $_POST['plano_pgto']  ?? '';
    $quant_dias  = $_POST['quant_dias']  ?? '';
    $nota_fiscal = $_POST['nota_fiscal'] ?? '';
    $vencimento  = $_POST['vencimento']  ?? '';
    $fazenda     = $_POST['fazenda']     ?? '';
    $desc_avista = floatval(str_replace(',', '.', $_POST['desc-avista'] ?? '0'));

    // descontos fixos
    $desc_funrural = floatval(str_replace(',', '.', $_POST['desc_funrural']     ?? '0'));
    $desc_ima      = floatval(str_replace(',', '.', $_POST['desc_ima']          ?? '0'));
    $desc_abanorte = floatval(str_replace(',', '.', $_POST['desc_abanorte']     ?? '0'));
    $desc_taxaadm  = floatval(str_replace(',', '.', $_POST['valor_taxa_adm']    ?? '0'));

    // Configurações extras
    $funrural_config_info       = $_POST['info_funrural'] ?? null;
    $funrural_config_preco_unit = isset($_POST['preco_unit_funrural']) && $_POST['preco_unit_funrural'] !== '' ? floatval(str_replace(',', '.', $_POST['preco_unit_funrural'])) : null;

    $ima_config_info       = $_POST['info_ima'] ?? null;
    $ima_config_preco_unit = isset($_POST['preco_unit_ima']) && $_POST['preco_unit_ima'] !== '' ? floatval(str_replace(',', '.', $_POST['preco_unit_ima'])) : null;

    $abanorte_config_info       = $_POST['info_abanorte'] ?? null;
    $abanorte_config_preco_unit = isset($_POST['preco_unit_abanorte']) && $_POST['preco_unit_abanorte'] !== '' ? floatval(str_replace(',', '.', $_POST['preco_unit_abanorte'])) : null;

    $taxa_adm_config_taxa_perc  = isset($_POST['taxa_adm_percent']) && $_POST['taxa_adm_percent'] !== '' ? floatval(str_replace(',', '.', $_POST['taxa_adm_percent'])) : null;
    $taxa_adm_config_preco_unit = isset($_POST['preco_unit_taxa_adm']) && $_POST['preco_unit_taxa_adm'] !== '' ? floatval(str_replace(',', '.', $_POST['preco_unit_taxa_adm'])) : null;


    // --- PROCESSAMENTO DE DESCONTOS DIVERSOS ---
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


    // --- ARRAYS DE PRODUTOS ---
    $quant_caixa_1 = $_POST['quant_caixa_1'] ?? [];
    $produto_1     = $_POST['produto_1']     ?? [];
    $preco_kg_1    = $_POST['preco_kg_1']    ?? [];
    $tipo_cx_1     = $_POST['tipo_cx_1']     ?? [];
    $preco_unit_1  = $_POST['preco_unit_1']  ?? [];
    $valor_1       = $_POST['valor_1']       ?? [];

    // --- VALIDAÇÕES BÁSICAS ---
    $erros = [];
    if (empty($fornecedor) || $fornecedor == '0') $erros[] = "Selecione um fornecedor";
    if (empty($cliente)    || $cliente    == '0') $erros[] = "Selecione um cliente";
    if (empty($data))                             $erros[] = "Data é obrigatória";
    if (empty($plano_pgto) || $plano_pgto == '0') $erros[] = "Selecione um plano de pagamento";

    // valida desconto à vista
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
    $total_bruto      = array_reduce($valor_1, fn($c, $v_prod) => $c + floatval(str_replace(',', '.', $v_prod)), 0);
    $total_bruto_desc = $total_bruto * (1 - ($desc_avista / 100));
    $soma_outros_descontos_fixos = $desc_funrural + $desc_ima + $desc_abanorte + $desc_taxaadm;
    $total_liquido = $total_bruto_desc - $soma_outros_descontos_fixos;

    // Adicionar soma dos descontos diversos ao total_liquido
    $soma_descontos_diversos_val = 0;
    foreach ($descontos_diversos as $dd_item) {
        if ($dd_item['tipo'] === '-') {
            $soma_descontos_diversos_val -= $dd_item['valor'];
        } else if ($dd_item['tipo'] === '+') {
            $soma_descontos_diversos_val += $dd_item['valor'];
        }
    }
    $total_liquido += $soma_descontos_diversos_val;

    // --- VALIDAÇÃO DE PRODUTOS ---
    $tem_produtos = false;
    foreach ($valor_1 as $k_prod => $v_prod_str) {
        $v_prod = floatval(str_replace(',', '.', $v_prod_str));
        if ($v_prod_str !== '' && $v_prod > 0) {
            $tem_produtos = true;
            if (empty($produto_1[$k_prod])) {
                $erros[] = "Selecione variedade em todos os produtos com valor";
                break;
            }
            if (empty($tipo_cx_1[$k_prod])) {
                $erros[] = "Selecione tipo de caixa em todos os produtos com valor";
                break;
            }
            if (empty($quant_caixa_1[$k_prod]) || floatval(str_replace(',', '.', $quant_caixa_1[$k_prod])) <= 0) {
                $erros[] = "Quantidade de caixas deve ser maior que zero para produtos com valor";
                break;
            }
        }
    }
    if (!$tem_produtos && count(array_filter($valor_1)) > 0) {
        $erros[] = "Adicione produtos válidos ao romaneio.";
    } else if (count(array_filter($valor_1)) == 0) {
        $erros[] = "Adicione pelo menos um produto ao romaneio.";
    }

    if ($erros) {
        // Lançamos exceção para cair no catch e retornar JSON
        throw new Exception(implode("<br>", $erros));
    }

    // --- PREPARAÇÃO DOS ARRAYS DE PRODUTOS ---
    function filtrar(array $a) {
        return array_values(array_filter($a, function ($v) {
            return $v !== '' && $v !== null;
        }));
    }
    $quant_caixa_1_val = filtrar($quant_caixa_1);
    $produto_1_val     = filtrar($produto_1);
    $preco_kg_1_val    = filtrar($preco_kg_1);
    $tipo_cx_1_val     = filtrar($tipo_cx_1);
    $preco_unit_1_val  = filtrar($preco_unit_1);
    $valor_1_val       = filtrar($valor_1);


    // =================================================================================
    // INÍCIO DO BLOCO DE TRANSAÇÃO E SQL
    // =================================================================================

    $pdo->beginTransaction(); // Inicia transação segura

    // 2. TRATAMENTO DA DATA (Dica do HEAD: converte dd/mm/aaaa)
    $data_mysql = $data;
    if (!empty($data) && strpos($data, '/') !== false) {
        $timestamp = strtotime(str_replace('/', '-', $data));
        if ($timestamp) {
            $data_mysql = date('Y-m-d H:i:s', $timestamp);
        } else {
            $data_mysql = date('Y-m-d H:i:s');
        }
    } elseif (empty($data)) {
        $data_mysql = date('Y-m-d H:i:s');
    }

    // 3. INSERT OU UPDATE DO CABEÇALHO
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
              :df,   :di,    :dab,   :dtadm,    :dd,
              :fci,  :fcpu,
              :ici,  :icpu,
              :aci,  :acpu,
              :atctp, :atcpu,
              0)";
        
        $stmt = $pdo->prepare($sql);
        
        // CHECK DE ERRO DO INSERT (Dedo Duro)
        if (!$stmt->execute($params)) {
            $err = $stmt->errorInfo();
            throw new Exception("Erro fatal ao inserir Romaneio: " . $err[2]);
        }
        
        $romaneioId = $pdo->lastInsertId();
        
        if (empty($romaneioId)) {
            throw new Exception("O banco de dados não retornou um ID válido.");
        }

    } else {
        $sql = "UPDATE {$tabela} SET
             fornecedor      = :forn, cliente        = :cli, quant_dias      = :qd,
             data            = :dt,      nota_fiscal     = :nf, plano_pgto   = :pp,
             vencimento      = :ven,    total_liquido   = :tl, fazenda           = :faz,
             desc_avista     = :da,
             desc_funrural   = :df,      desc_ima        = :di, desc_abanorte    = :dab,
             desc_taxaadm    = :dtadm, descontos_diversos = :dd,
             funrural_config_info = :fci, funrural_config_preco_unit = :fcpu,
             ima_config_info = :ici, ima_config_preco_unit = :icpu,
             abanorte_config_info = :aci, abanorte_config_preco_unit = :acpu,
             taxa_adm_config_taxa_perc = :atctp, taxa_adm_config_preco_unit = :atcpu
           WHERE id = :id_val";
        
        $params[':id_val'] = $id;
        $stmt = $pdo->prepare($sql);
        
        // CHECK DE ERRO DO UPDATE (Dedo Duro)
        if (!$stmt->execute($params)) {
            $err = $stmt->errorInfo();
            throw new Exception("Erro fatal ao atualizar Romaneio: " . $err[2]);
        }
        $romaneioId = $id;
    }

    // 4. INSERÇÃO DOS PRODUTOS
    $delProd = $pdo->prepare("DELETE FROM linha_produto_compra WHERE id_romaneio = ?");
    if (!$delProd->execute([$romaneioId])) {
        throw new Exception("Erro ao limpar produtos antigos.");
    }

    if (count($quant_caixa_1_val) > 0) {
        $insertLinha = $pdo->prepare(
            "INSERT INTO linha_produto_compra
             (id_romaneio, quant, variedade, preco_kg, tipo_caixa, preco_unit, valor)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        foreach ($quant_caixa_1_val as $key => $q_val) {
            if (!isset($produto_1_val[$key], $valor_1_val[$key], $tipo_cx_1_val[$key])) {
                continue;
            }

            $v_final     = str_replace(',', '.', $valor_1_val[$key]);
            $var_final   = $produto_1_val[$key];
            $pkg_final   = isset($preco_kg_1_val[$key]) ? str_replace(',', '.', $preco_kg_1_val[$key]) : '0';
            $pun_final   = isset($preco_unit_1_val[$key]) ? str_replace(',', '.', $preco_unit_1_val[$key]) : '0';
            $tipo_cx_str = $tipo_cx_1_val[$key];

            $stmtTipoCx = $pdo->prepare("SELECT id FROM tipo_caixa WHERE tipo = ?");
            $stmtTipoCx->execute([$tipo_cx_str]);
            $tipoCxId = $stmtTipoCx->fetchColumn();

            if (!$tipoCxId) {
                $stmtInsTipoCx = $pdo->prepare("INSERT INTO tipo_caixa (tipo, unidade_medida) VALUES (?,1)");
                if (!$stmtInsTipoCx->execute([$tipo_cx_str])) {
                     throw new Exception("Erro ao cadastrar novo tipo de caixa: $tipo_cx_str");
                }
                $tipoCxId = $pdo->lastInsertId();
            }

            if (!$insertLinha->execute([
                $romaneioId,
                floatval(str_replace(',', '.', $q_val)),
                $var_final,
                $pkg_final,
                $tipoCxId,
                $pun_final,
                $v_final
            ])) {
                $errP = $insertLinha->errorInfo();
                throw new Exception("Erro ao inserir produto (Linha $key): " . $errP[2]);
            }
        }
    }

    // 5. CONTAS A PAGAR
    $deleteRec = $pdo->prepare("DELETE FROM pagar WHERE id_ref = :id_ref AND referencia = 'romaneio_compra'");
    if (!$deleteRec->execute([':id_ref' => $romaneioId])) {
        throw new Exception("Erro ao limpar financeiro antigo.");
    }

    if ($total_liquido > 0) {
        $formaPgtoRec   = is_numeric($plano_pgto) ? (int)$plano_pgto : null;
        $usuarioLancRec = $id_usuario;

        $insRec = $pdo->prepare("
        INSERT INTO pagar
            (descricao, fornecedor, valor, vencimento, data_lanc, forma_pgto, frequencia, referencia, id_romaneio, usuario_lanc, usuario_pgto, funcionario, id_ref)
        VALUES
            (:descricao, :fornecedor, :valor, :vencimento, :data_lanc, :forma_pgto, '0', 'romaneio_compra', :id_romaneio_fk, :usuario_lanc, :usuario_pgto, '0', :id_ref_pagar)
        ");

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

        if (!$insRec->execute($paramsPagar)) {
             $errFin = $insRec->errorInfo();
             throw new Exception("Erro ao gerar Contas a Pagar: " . $errFin[2]);
        }
    }

    // SE CHEGOU AQUI, TUDO DEU CERTO.
    $pdo->commit();

    echo json_encode([
        'status'   => 'sucesso',
        'mensagem' => 'Salvo com sucesso!',
        'id'       => $romaneioId
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) { // Usamos Throwable para pegar Erros Fatais e Exceptions
    
    // SE DER ERRO EM QUALQUER LUGAR (Mesmo antes da transaction)
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Retorna JSON para o Javascript não quebrar
    echo json_encode([
        'status'   => 'erro',
        'mensagem' => 'Erro: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>