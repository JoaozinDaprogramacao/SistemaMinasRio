<?php
// sistema/painel/paginas/romaneio_compra/salvar.php
$tabela = 'romaneio_compra';
require_once("../../../conexao.php");
session_start();

header('Content-Type: application/json; charset=utf-8');

$id_usuario      = $_SESSION['id'] ?? null;

// campos do POST (existentes)
$id              = $_POST['id']             ?? '';
$fornecedor      = $_POST['fornecedor']     ?? '';
$cliente          = $_POST['cliente']         ?? '';
$data              = $_POST['data']             ?? '';
$plano_pgto      = $_POST['plano_pgto']     ?? '';
$quant_dias      = $_POST['quant_dias']     ?? '';
$nota_fiscal      = $_POST['nota_fiscal']     ?? '';
$vencimento      = $_POST['vencimento']     ?? '';
$fazenda          = $_POST['fazenda']         ?? '';
$desc_avista      = floatval(str_replace(',', '.', $_POST['desc-avista'] ?? '0'));

// descontos fixos (valores finais - já existentes)
$desc_funrural     = floatval(str_replace(',', '.', $_POST['desc_funrural']     ?? '0')); // Valor final
$desc_ima          = floatval(str_replace(',', '.', $_POST['desc_ima']             ?? '0')); // Valor final
$desc_abanorte     = floatval(str_replace(',', '.', $_POST['desc_abanorte']     ?? '0')); // Valor final
$desc_taxaadm     = floatval(str_replace(',', '.', $_POST['valor_taxa_adm']     ?? '0')); // Valor final

// NOVOS campos para detalhes de configuração das comissões/deduções
$funrural_config_info          = $_POST['info_funrural'] ?? null;
$funrural_config_preco_unit  = isset($_POST['preco_unit_funrural']) && $_POST['preco_unit_funrural'] !== '' ? floatval(str_replace(',', '.', $_POST['preco_unit_funrural'])) : null;

$ima_config_info              = $_POST['info_ima'] ?? null;
$ima_config_preco_unit          = isset($_POST['preco_unit_ima']) && $_POST['preco_unit_ima'] !== '' ? floatval(str_replace(',', '.', $_POST['preco_unit_ima'])) : null;

$abanorte_config_info          = $_POST['info_abanorte'] ?? null;
$abanorte_config_preco_unit  = isset($_POST['preco_unit_abanorte']) && $_POST['preco_unit_abanorte'] !== '' ? floatval(str_replace(',', '.', $_POST['preco_unit_abanorte'])) : null;

$taxa_adm_config_taxa_perc      = isset($_POST['taxa_adm_percent']) && $_POST['taxa_adm_percent'] !== '' ? floatval(str_replace(',', '.', $_POST['taxa_adm_percent'])) : null;
$taxa_adm_config_preco_unit  = isset($_POST['preco_unit_taxa_adm']) && $_POST['preco_unit_taxa_adm'] !== '' ? floatval(str_replace(',', '.', $_POST['preco_unit_taxa_adm'])) : null;


// descontos diversos (dinâmicos) - já existente
$tipos              = $_POST['desconto_tipo']     ?? [];
$valores          = $_POST['desconto_valor'] ?? [];
$obs              = $_POST['desconto_obs']     ?? [];
$descontos_diversos = [];
foreach ($tipos as $i => $tipo) {
    $v_str = str_replace(',', '.', $valores[$i] ?? '0'); // Corrigido para usar $valores[$i]
    if ($tipo !== '' && is_numeric($v_str)) { // Permitir valor 0 se for o caso, ou ajuste > 0 se necessário
        $v = floatval($v_str);
        // Removido if (floatval($v) > 0) para permitir salvar valor 0 se for intencional. Ajuste se necessário.
        $descontos_diversos[] = [
            'tipo'     => $tipo,
            'valor' => $v,
            'obs'     => trim($obs[$i] ?? '')
        ];
    }
}
$descontos_json = count($descontos_diversos) > 0 ? json_encode($descontos_diversos, JSON_UNESCAPED_UNICODE) : null;


// arrays de produtos (já existentes)
$quant_caixa_1     = $_POST['quant_caixa_1']     ?? [];
$produto_1          = $_POST['produto_1']         ?? [];
$preco_kg_1     = $_POST['preco_kg_1']         ?? [];
$tipo_cx_1          = $_POST['tipo_cx_1']         ?? [];
$preco_unit_1     = $_POST['preco_unit_1']     ?? [];
$valor_1          = $_POST['valor_1']         ?? [];

// validações (já existentes)
$erros = [];
if (empty($fornecedor) || $fornecedor == '0')     $erros[] = "Selecione um fornecedor";
if (empty($cliente)     || $cliente     == '0')     $erros[] = "Selecione um cliente";
if (empty($data))                                 $erros[] = "Data é obrigatória";
if (empty($plano_pgto) || $plano_pgto == '0')     $erros[] = "Selecione um plano de pagamento";


// valida desconto à vista (já existente)
$nomePlanoPgtoSelecionado = '';
if (!empty($plano_pgto) && is_numeric($plano_pgto)) {
    $queryPlano = $pdo->prepare("SELECT nome FROM planos_pgto WHERE id = ?");
    $queryPlano->execute([$plano_pgto]);
    $nomePlanoPgtoSelecionado = $queryPlano->fetchColumn();
}

if (strtoupper(trim($nomePlanoPgtoSelecionado ?? '')) === 'À VISTA' && $desc_avista <= 0) {
    $erros[] = "Para pagamento à vista, o desconto percentual é obrigatório e deve ser maior que zero.";
}


// cálculo de totais (já existente e corrigido anteriormente)
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
$total_liquido += $soma_descontos_diversos_val; // Subtrai se for negativo, soma se positivo

// validação produtos (já existente)
// ... (seu código de validação de produtos) ...
$tem_produtos = false;
foreach ($valor_1 as $k_prod => $v_prod_str) {
    $v_prod = floatval(str_replace(',', '.', $v_prod_str));
    if ($v_prod_str !== '' && $v_prod > 0) { // Verifica se o valor original não era vazio também
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
if (!$tem_produtos && count(array_filter($valor_1)) > 0) { // Se há tentativas de preencher produtos mas nenhum válido
    $erros[] = "Adicione produtos válidos ao romaneio.";
} else if (count(array_filter($valor_1)) == 0) { // Se nenhum produto foi adicionado
    $erros[] = "Adicione pelo menos um produto ao romaneio.";
}



if ($erros) {
    echo json_encode(['status' => 'erro', 'mensagem' => implode("<br>", $erros)], JSON_UNESCAPED_UNICODE);
    exit;
}


// INSERT ou UPDATE do cabeçalho
// Adicionamos o campo 'usado' com valor fixo 0 ao INSERT
$params = [
    ':forn' => $fornecedor,
    ':cli'     => $cliente,
    ':qd'     => $quant_dias,
    ':dt'     => $data,
    ':nf'     => $nota_fiscal,
    ':pp'     => $plano_pgto,
    ':ven'     => $vencimento,
    ':tl'     => $total_liquido,
    ':faz'     => $fazenda,
    ':da'     => $desc_avista, // Percentual do desconto à vista
    ':df'     => $desc_funrural, // Valor final
    ':di'     => $desc_ima,         // Valor final
    ':dab'     => $desc_abanorte, // Valor final
    ':dtadm' => $desc_taxaadm,     // Valor final
    ':dd'     => $descontos_json,
    // Note que ':usado' não é necessário nos params se for fixo no SQL, mas poderia ser adicionado

    // Novos campos de configuração
    ':fci'      => $funrural_config_info,
    ':fcpu'  => $funrural_config_preco_unit,
    ':ici'      => $ima_config_info,
    ':icpu'  => $ima_config_preco_unit,
    ':aci'      => $abanorte_config_info,
    ':acpu'  => $abanorte_config_preco_unit,
    ':atctp' => $taxa_adm_config_taxa_perc,
    ':atcpu' => $taxa_adm_config_preco_unit
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
		 usado
		 )
		VALUES
		 (:forn, :cli, :qd, :dt, :nf, :pp, :ven, :tl, :faz, :da,
		  :df, 	 :di, 	 :dab, 	 :dtadm, 	:dd,
		  :fci,  :fcpu,
		  :ici,  :icpu,
		  :aci,  :acpu,
		  :atctp, :atcpu,
		  0
		 )"; // <-- CAMPO USADO DEFINIDO COMO 0 AQUI
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $romaneioId = $pdo->lastInsertId();
} else {
    $sql = "UPDATE {$tabela} SET
		 fornecedor 	 = :forn, cliente 		 = :cli, quant_dias 	 = :qd,
		 data 			 = :dt, 	 nota_fiscal 	 = :nf, plano_pgto 	 = :pp,
		 vencimento 	 = :ven, 	total_liquido 	= :tl, fazenda 			 = :faz,
		 desc_avista 	 = :da,
		 desc_funrural 	 = :df, 	 desc_ima 		 = :di, desc_abanorte 	 = :dab,
		 desc_taxaadm 	 = :dtadm, descontos_diversos = :dd,
		 funrural_config_info = :fci, funrural_config_preco_unit = :fcpu,
		 ima_config_info = :ici, ima_config_preco_unit = :icpu,
		 abanorte_config_info = :aci, abanorte_config_preco_unit = :acpu,
		 taxa_adm_config_taxa_perc = :atctp, taxa_adm_config_preco_unit = :atcpu
	   WHERE id = :id_val"; // Usar um placeholder diferente para o ID no WHERE
    $params[':id_val'] = $id; // Adicionar o ID aos parâmetros para o UPDATE
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $romaneioId = $id;
}

// ... (restante do seu código para inserir produtos e contas a pagar - permanece igual) ...
// apaga itens antigos
$pdo->prepare("DELETE FROM linha_produto_compra WHERE id_romaneio = ?")
    ->execute([$romaneioId]);

// filtra valores não vazios (movido para antes da validação de produtos, mas pode ser aqui também se preferir)
function filtrar(array $a)
{
    return array_values(array_filter($a, function ($v) {
        return $v !== '' && $v !== null;
    }));
}
$quant_caixa_1_val = filtrar($quant_caixa_1);
$produto_1_val      = filtrar($produto_1);
$preco_kg_1_val     = filtrar($preco_kg_1);
$tipo_cx_1_val      = filtrar($tipo_cx_1);
$preco_unit_1_val     = filtrar($preco_unit_1);
$valor_1_val      = filtrar($valor_1);


// insere produtos
if (count($quant_caixa_1_val) > 0) { // Apenas se houver produtos válidos
    $insertLinha = $pdo->prepare(
        "INSERT INTO linha_produto_compra
		 (id_romaneio, quant, variedade, preco_kg, tipo_caixa, preco_unit, valor)
		 VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    foreach ($quant_caixa_1_val as $key => $q_val) {
        // Certificar que todos os arrays têm o índice $key antes de acessá-los
        if (!isset($produto_1_val[$key], $valor_1_val[$key], $tipo_cx_1_val[$key])) {
            continue; // Pula esta iteração se algum dado essencial do produto estiver faltando
        }

        $v_final     = str_replace(',', '.', $valor_1_val[$key]);
        $var_final = $produto_1_val[$key];
        $pkg_final = isset($preco_kg_1_val[$key]) ? str_replace(',', '.', $preco_kg_1_val[$key]) : '0';
        $pun_final = isset($preco_unit_1_val[$key]) ? str_replace(',', '.', $preco_unit_1_val[$key]) : '0';
        $tipo_cx_str = $tipo_cx_1_val[$key]; // Este é o 'tipo' (string) da caixa, não o ID ainda


        // obtém ou insere tipo_caixa (lógica existente)
        $stmtTipoCx = $pdo->prepare("SELECT id FROM tipo_caixa WHERE tipo = ?");
        $stmtTipoCx->execute([$tipo_cx_str]);
        $tipoCxId = $stmtTipoCx->fetchColumn();

        if (!$tipoCxId) {
            // Precisamos da unidade_medida aqui, que não está vindo do form para esta parte.
            // Assumindo '1' (KG) como padrão se não encontrar, ou você precisa passar/buscar essa info.
            // Para este exemplo, vou assumir que 'unidade_medida' deve ser '1' (KG) se for nova.
            // O ideal seria ter essa informação de forma mais robusta.
            $stmtInsTipoCx = $pdo->prepare("INSERT INTO tipo_caixa (tipo, unidade_medida) VALUES (?,1)");
            $stmtInsTipoCx->execute([$tipo_cx_str]);
            $tipoCxId = $pdo->lastInsertId();
        }

        $insertLinha->execute([
            $romaneioId,
            floatval(str_replace(',', '.', $q_val)),
            $var_final,
            $pkg_final,
            $tipoCxId, // Usar o ID do tipo_caixa
            $pun_final,
            $v_final
        ]);
    }
}


// Contas a pagar
$deleteRec = $pdo->prepare("
	DELETE FROM pagar 
	WHERE id_ref = :id_ref 
	  AND referencia = 'romaneio_compra'
");
$deleteRec->execute([':id_ref' => $romaneioId]);


if ($total_liquido > 0) { // Apenas cria conta a pagar se houver valor
    $formaPgtoRec     = is_numeric($plano_pgto) ? (int)$plano_pgto : null;
    $usuarioLancRec = $id_usuario;

    $insRec = $pdo->prepare("
	INSERT INTO pagar
		(descricao, fornecedor, valor, vencimento, data_lanc, forma_pgto, frequencia, referencia, id_romaneio, usuario_lanc, usuario_pgto, funcionario, id_ref)
	VALUES
		(:descricao, :fornecedor, :valor, :vencimento, :data_lanc, :forma_pgto, '0', 'romaneio_compra', :id_romaneio_fk, :usuario_lanc, :usuario_pgto, '0', :id_ref_pagar)
	");

    $insRec->execute([
        'descricao'      => "Romaneio Compra #{$romaneioId}",
        'fornecedor'      => $fornecedor,
        'valor'          => $total_liquido,
        'vencimento'      => $vencimento,
        'data_lanc'      => date('Y-m-d'),
        'forma_pgto'      => $formaPgtoRec,
        'id_romaneio_fk' => $romaneioId, // Coluna específica para FK se houver
        'usuario_lanc'      => $usuarioLancRec,
        'usuario_pgto'      => null, // Geralmente pgto é nulo no lançamento
        'id_ref_pagar'      => $romaneioId
    ]);
}


echo json_encode([
    'status'     => 'sucesso',
    'mensagem'     => 'Salvo com sucesso!', // Mensagem um pouco mais amigável
    'id'         => $romaneioId
], JSON_UNESCAPED_UNICODE);
