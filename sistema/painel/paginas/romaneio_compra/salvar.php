<?php
// sistema/painel/paginas/romaneio_compra/salvar.php
$tabela = 'romaneio_compra';
require_once("../../../conexao.php");
session_start();

// Define o cabeçalho como JSON
header('Content-Type: application/json; charset=utf-8');

// --- VARIÁVEL GLOBAL PARA ACUMULAR O DEBUG ---
$debug_log = [];

// --- FUNÇÃO PARA ADICIONAR AO ARRAY DE DEBUG ---
function addLog($mensagem, $dados = null)
{
    global $debug_log;

    $entrada = [
        'hora' => date('H:i:s'),
        'msg'  => $mensagem
    ];

    if ($dados !== null) {
        $entrada['dados'] = $dados;
    }

    $debug_log[] = $entrada;
}

try {
    addLog("INICIANDO SCRIPT SALVAR.PHP");

    if (!isset($pdo)) {
        throw new Exception("Erro crítico: Variável de conexão inexistente.");
    }

    $id_usuario = $_POST['id_usuario_request'] ?? $_SESSION['id'] ?? 0;
    addLog("Usuário da Sessão: " . $id_usuario);
    addLog("Dados Recebidos (_POST)", $_POST);

    // --- RECEBIMENTO DOS DADOS ---
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

    // Função interna para limpar valores monetários (Ex: 1.250,50 -> 1250.50)
    function limparMoeda($valor)
    {
        $limpo = str_replace(['R$', ' ', '.'], '', $valor ?? '0');
        $limpo = str_replace(',', '.', $limpo);
        return floatval($limpo);
    }

    $desc_avista = limparMoeda($_POST['desc-avista'] ?? '0');

    // --- CAPTURA DOS IMPOSTOS E TAXAS (DINÂMICOS DO HTML) ---

    // --- CAPTURA DOS IMPOSTOS E TAXAS (TOTALMENTE DINÂMICO) ---

    // Buscamos as taxas cadastradas no banco para saber quais nomes procurar no $_POST
    $query_taxas = $pdo->query("SELECT id, descricao FROM taxas_abatimentos");
    $taxas_cadastradas = $query_taxas->fetchAll(PDO::FETCH_ASSOC);

    // Inicializamos as variáveis com 0
    $desc_funrural = 0;
    $desc_ima = 0;
    $desc_abanorte = 0;
    $desc_taxaadm = 0;
    $funrural_config_info = null;
    $ima_config_info = null;
    $abanorte_config_info = null;
    $taxa_adm_config_taxa_perc = null;
    $funrural_config_preco_unit = null;
    $ima_config_preco_unit = null;
    $abanorte_config_preco_unit = null;
    $taxa_adm_config_preco_unit = null;

    foreach ($taxas_cadastradas as $taxa) {
        $tid = $taxa['id'];
        $nome_taxa = strtoupper(trim($taxa['descricao']));

        // Pegamos os valores do POST usando o ID dinâmico (valor_2, valor_3...)
        $v_valor = limparMoeda($_POST['valor_' . $tid] ?? '0');
        $v_info  = $_POST['info_' . $tid] ?? null;
        $v_unit  = isset($_POST['preco_unit_' . $tid]) ? limparMoeda($_POST['preco_unit_' . $tid]) : null;

        // Agora mapeamos para a COLUNA correta do banco romaneio_compra baseada no NOME da taxa
        if ($nome_taxa == 'FUNRURAL') {
            $desc_funrural = $v_valor;
            $funrural_config_info = $v_info;
            $funrural_config_preco_unit = $v_unit;
        } else if ($nome_taxa == 'IMA') {
            $desc_ima = $v_valor;
            $ima_config_info = $v_info;
            $ima_config_preco_unit = $v_unit;
        } else if ($nome_taxa == 'ABANORTE') {
            $desc_abanorte = $v_valor;
            $abanorte_config_info = $v_info;
            $abanorte_config_preco_unit = $v_unit;
        } else if ($nome_taxa == 'TAXA ADM') {
            $desc_taxaadm = $v_valor;
            // Se $v_info estiver vazio, define como 0 para não dar erro no SQL
            $taxa_adm_config_taxa_perc = ($v_info === '' || $v_info === null) ? 0 : limparMoeda($v_info);
            $taxa_adm_config_preco_unit = $v_unit;
        }   
    }

    // --- PROCESSAMENTO DE DESCONTOS DIVERSOS ---
    $tipos   = $_POST['desconto_tipo']  ?? [];
    $valores = $_POST['desconto_valor'] ?? [];
    $obs     = $_POST['desconto_obs']   ?? [];
    $descontos_diversos = [];

    foreach ($tipos as $i => $tipo) {
        $v_limpo = limparMoeda($valores[$i] ?? '0');

        if ($v_limpo > 0 && $tipo !== '') {
            $descontos_diversos[] = [
                'tipo'  => $tipo,
                'valor' => $v_limpo,
                'obs'   => trim($obs[$i] ?? '')
            ];
        }
    }
    $descontos_json = count($descontos_diversos) > 0 ? json_encode($descontos_diversos, JSON_UNESCAPED_UNICODE) : null;
    addLog("Descontos Diversos Processados", $descontos_diversos);

    // --- ARRAYS DE PRODUTOS ---
    $quant_caixa_1 = $_POST['quant_caixa_1'] ?? [];
    $produto_1     = $_POST['produto_1']     ?? [];
    $preco_kg_1    = $_POST['preco_kg_1']    ?? [];
    $tipo_cx_1     = $_POST['tipo_cx_1']     ?? [];
    $preco_unit_1  = $_POST['preco_unit_1']  ?? [];
    $valor_1       = $_POST['valor_1']       ?? [];

    // --- VALIDAÇÕES ---
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
        $erros[] = "Para pagamento à vista, o desconto percentual é obrigatório.";
    }

    // --- CÁLCULO DE TOTAIS ---
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

    addLog("Cálculo Financeiro Realizado", [
        'Total Bruto' => $total_bruto,
        'Desc Avista' => $desc_avista,
        'Total Liquido' => $total_liquido
    ]);

    // Validação de Produtos
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
        addLog("Erros de validação encontrados", $erros);
        throw new Exception(implode("<br>", $erros));
    }

    // Filtra arrays
    function filtrar(array $a)
    {
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
    // BANCO DE DADOS
    // =================================================================================
    addLog("Iniciando Transação (beginTransaction)");
    $pdo->beginTransaction();

    // Tratamento data
    $data_mysql = $data;
    if (!empty($data) && strpos($data, '/') !== false) {
        $timestamp = strtotime(str_replace('/', '-', $data));
        $data_mysql = $timestamp ? date('Y-m-d H:i:s', $timestamp) : date('Y-m-d H:i:s');
    }

    // Parâmetros
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
        addLog("Modo INSERT detectado");
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

        addLog("Executando SQL Romaneio (Insert)", ['sql' => $sql, 'params' => $params]);

        $stmt = $pdo->prepare($sql);
        if (!$stmt->execute($params)) {
            $err = $stmt->errorInfo();
            throw new Exception("Erro INSERT Romaneio: " . $err[2]);
        }

        $romaneioId = $pdo->lastInsertId();
        addLog("Romaneio Inserido com Sucesso. ID: $romaneioId");
    } else {
        // --- UPDATE ---
        addLog("Modo UPDATE detectado (ID: $id)");
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

        addLog("Executando SQL Romaneio (Update)", ['sql' => $sql, 'params' => $params]);

        $stmt = $pdo->prepare($sql);
        if (!$stmt->execute($params)) {
            $err = $stmt->errorInfo();
            throw new Exception("Erro UPDATE Romaneio: " . $err[2]);
        }
        $romaneioId = $id;
        addLog("Romaneio Atualizado com Sucesso.");
    }

    // 4. PRODUTOS
    addLog("Limpando produtos antigos do ID $romaneioId");
    $delProd = $pdo->prepare("DELETE FROM linha_produto_compra WHERE id_romaneio = ?");
    $delProd->execute([$romaneioId]);

    if (count($quant_caixa_1_val) > 0) {
        $sqlProd = "INSERT INTO linha_produto_compra
             (id_romaneio, quant, variedade, preco_kg, tipo_caixa, preco_unit, valor)
             VALUES (?, ?, ?, ?, ?, ?, ?)";
        $insertLinha = $pdo->prepare($sqlProd);

        foreach ($quant_caixa_1_val as $key => $q_val) {
            if (!isset($produto_1_val[$key], $valor_1_val[$key], $tipo_cx_1_val[$key])) continue;

            $v_final    = str_replace(',', '.', $valor_1_val[$key]);
            $var_final  = $produto_1_val[$key];
            $pkg_final  = isset($preco_kg_1_val[$key]) ? str_replace(',', '.', $preco_kg_1_val[$key]) : '0';
            $pun_final  = isset($preco_unit_1_val[$key]) ? str_replace(',', '.', $preco_unit_1_val[$key]) : '0';
            $tipo_cx_str = $tipo_cx_1_val[$key];

            // Verifica tipo caixa
            $stmtTipoCx = $pdo->prepare("SELECT id FROM tipo_caixa WHERE tipo = ?");
            $stmtTipoCx->execute([$tipo_cx_str]);
            $tipoCxId = $stmtTipoCx->fetchColumn();

            if (!$tipoCxId) {
                addLog("Criando novo Tipo de Caixa: $tipo_cx_str");
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

            addLog("Inserindo produto index $key", $paramsProd);

            if (!$insertLinha->execute($paramsProd)) {
                $errP = $insertLinha->errorInfo();
                throw new Exception("Erro INSERT Produto: " . $errP[2]);
            }
        }
    }

    // 5. FINANCEIRO
    addLog("Limpando financeiro antigo (Pagar)");
    $deleteRec = $pdo->prepare("DELETE FROM pagar WHERE id_ref = :id_ref AND referencia = 'romaneio_compra'");
    $deleteRec->execute([':id_ref' => $romaneioId]);

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

        addLog("Gerando Conta a Pagar", $paramsPagar);

        if (!$insRec->execute($paramsPagar)) {
            $errFin = $insRec->errorInfo();
            throw new Exception("Erro INSERT Financeiro: " . $errFin[2]);
        }
    } else {
        addLog("Financeiro pulado: Valor Líquido <= 0");
    }

    $pdo->commit();
    addLog("COMMIT realizado com sucesso");

    // RETORNO DE SUCESSO COM O DEBUG COMPLETO
    echo json_encode([
        'status'   => 'sucesso',
        'mensagem' => 'Salvo com sucesso!',
        'id'       => $romaneioId,
        'debug'    => $debug_log // <--- AQUI ESTÁ A MÁGICA
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    // ERRO
    $msgErro = $e->getMessage();
    addLog("ERRO FATAL: " . $msgErro);
    addLog("Linha: " . $e->getLine());

    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
        addLog("ROLLBACK executado.");
    }

    // RETORNO DE ERRO COM O DEBUG COMPLETO
    echo json_encode([
        'status'   => 'erro',
        'mensagem' => 'Erro: ' . $msgErro,
        'debug'    => $debug_log // <--- DEBUG MESMO NO ERRO
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
