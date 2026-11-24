<?php
// =====================================================================
// NÚCLEO FINANCEIRO
// ---------------------------------------------------------------------
// Toda movimentação de dinheiro passa por aqui. Regras:
//  1. O extrato (linha_bancos) é a única fonte do saldo. bancos.saldo é
//     só um espelho recalculado a cada movimento (fin_recalcular_saldo).
//  2. Toda linha do extrato gerada pelo sistema sabe de onde veio
//     (origem + id_origem). Estornar/excluir algo remove exatamente as
//     linhas que ele gerou.
//  3. Linha conciliada não é removida: é preciso desconciliar antes.
//  4. Nada é lançado/estornado com data dentro do período bloqueado.
//  5. Toda operação relevante grava auditoria (fin_auditoria).
// As funções não abrem transação própria quando já existe uma aberta,
// então podem ser chamadas de dentro do fluxo de vendas/romaneios.
// =====================================================================

if (!defined('FIN_VERSAO_ESQUEMA')) {
    define('FIN_VERSAO_ESQUEMA', 5);
}

class FinErro extends Exception
{
}

// ---------------------------------------------------------------------
// Utilidades
// ---------------------------------------------------------------------

// "1.234,56" -> 1234.56 | "2.60" -> 2.60 | "" -> 0
function fin_num($v)
{
    if (is_int($v) || is_float($v)) return round((float) $v, 2);
    $v = trim(str_replace(['R$', ' '], '', (string) $v));
    if ($v === '') return 0.0;
    if (preg_match('/^-?\d+(\.\d{1,2})?$/', $v)) return round((float) $v, 2);
    return round((float) str_replace(',', '.', str_replace('.', '', $v)), 2);
}

function fin_moeda($v)
{
    return 'R$ ' . number_format((float) $v, 2, ',', '.');
}

function fin_data_br($d)
{
    if (!$d || $d == '0000-00-00') return '';
    return date('d/m/Y', strtotime($d));
}

function fin_data_valida($d)
{
    if (!is_string($d) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) return false;
    [$a, $m, $dia] = explode('-', $d);
    return checkdate((int) $m, (int) $dia, (int) $a);
}

function fin_usuario()
{
    @session_start();
    return (int) (@$_SESSION['id'] ?: 0);
}

function fin_e($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

// Para usar em atributos onclick="f('...')" dentro de HTML
function fin_js($s)
{
    return htmlspecialchars(addslashes((string) $s), ENT_QUOTES, 'UTF-8');
}

function fin_transacao($pdo, callable $fn)
{
    $propria = !$pdo->inTransaction();
    if ($propria) $pdo->beginTransaction();
    try {
        $r = $fn();
        if ($propria) $pdo->commit();
        return $r;
    } catch (Throwable $e) {
        if ($propria && $pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function fin_q($pdo, $sql, array $p = [])
{
    $st = $pdo->prepare($sql);
    $st->execute($p);
    return $st;
}

function fin_proximo_vencimento($data, $frequencia)
{
    $frequencia = (int) $frequencia;
    $base = strtotime($data);
    if (in_array($frequencia, [30, 31])) return date('Y-m-d', strtotime('+1 month', $base));
    if ($frequencia == 60) return date('Y-m-d', strtotime('+2 months', $base));
    if ($frequencia == 90) return date('Y-m-d', strtotime('+3 months', $base));
    if ($frequencia == 180) return date('Y-m-d', strtotime('+6 months', $base));
    if (in_array($frequencia, [360, 365])) return date('Y-m-d', strtotime('+1 year', $base));
    return date('Y-m-d', strtotime("+$frequencia days", $base));
}

// ---------------------------------------------------------------------
// Esquema (auto-migração idempotente, compatível com MySQL 5.7/8 e MariaDB)
// ---------------------------------------------------------------------

function fin_col_existe($pdo, $tabela, $coluna)
{
    $st = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $st->execute([$tabela, $coluna]);
    return $st->fetchColumn() > 0;
}

function fin_tabela_existe($pdo, $tabela)
{
    $st = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
    $st->execute([$tabela]);
    return $st->fetchColumn() > 0;
}

function fin_indice_existe($pdo, $tabela, $indice)
{
    $st = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?");
    $st->execute([$tabela, $indice]);
    return $st->fetchColumn() > 0;
}

function fin_add_col($pdo, $tabela, $coluna, $definicao)
{
    if (!fin_col_existe($pdo, $tabela, $coluna)) {
        $pdo->exec("ALTER TABLE `$tabela` ADD COLUMN `$coluna` $definicao");
    }
}

function fin_add_indice($pdo, $tabela, $indice, $colunas, $unico = false)
{
    if (!fin_indice_existe($pdo, $tabela, $indice)) {
        $pdo->exec("ALTER TABLE `$tabela` ADD " . ($unico ? 'UNIQUE ' : '') . "INDEX `$indice` ($colunas)");
    }
}

function fin_esquema($pdo)
{
    static $feito = false;
    if ($feito) return;
    $feito = true;

    $pdo->exec("CREATE TABLE IF NOT EXISTS fin_meta (chave VARCHAR(50) NOT NULL PRIMARY KEY, valor VARCHAR(255) NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $versao = (int) fin_q($pdo, "SELECT valor FROM fin_meta WHERE chave = 'versao_esquema'")->fetchColumn();
    if ($versao >= FIN_VERSAO_ESQUEMA) return;

    if ($versao < 1) fin_migracao_1($pdo);
    if ($versao < 2) fin_migracao_2($pdo);
    if ($versao < 3) fin_migracao_3($pdo);
    if ($versao < 4) fin_migracao_4($pdo);
    if ($versao < 5) fin_migracao_5($pdo);

    fin_q($pdo, "REPLACE INTO fin_meta (chave, valor) VALUES ('versao_esquema', ?)", [FIN_VERSAO_ESQUEMA]);
}

function fin_migracao_1($pdo)
{
    // Extrato bancário: rastreabilidade, conciliação e valores maiores
    $pdo->exec("ALTER TABLE linha_bancos MODIFY credito DECIMAL(15,2) NULL DEFAULT 0, MODIFY debito DECIMAL(15,2) NULL DEFAULT 0, MODIFY saldo DECIMAL(15,2) NULL");
    $pdo->exec("ALTER TABLE bancos MODIFY saldo DECIMAL(15,2) NULL DEFAULT 0");
    fin_add_col($pdo, 'linha_bancos', 'origem', "VARCHAR(30) NULL");
    fin_add_col($pdo, 'linha_bancos', 'id_origem', "INT NULL");
    fin_add_col($pdo, 'linha_bancos', 'conciliado', "TINYINT(1) NOT NULL DEFAULT 0");
    fin_add_col($pdo, 'linha_bancos', 'data_conciliacao', "DATETIME NULL");
    fin_add_col($pdo, 'linha_bancos', 'usuario_conciliacao', "INT NULL");
    fin_add_col($pdo, 'linha_bancos', 'id_transferencia', "INT NULL");
    fin_add_col($pdo, 'linha_bancos', 'categoria_receber', "INT NULL");
    fin_add_col($pdo, 'linha_bancos', 'categoria_pagar', "INT NULL");
    fin_add_col($pdo, 'linha_bancos', 'centro_custo', "INT NULL");
    fin_add_col($pdo, 'linha_bancos', 'documento', "VARCHAR(60) NULL");
    fin_add_col($pdo, 'linha_bancos', 'obs', "VARCHAR(255) NULL");
    fin_add_col($pdo, 'linha_bancos', 'data_lancamento', "DATETIME NULL");
    fin_add_indice($pdo, 'linha_bancos', 'idx_lb_banco_data', 'id_banco, data');
    fin_add_indice($pdo, 'linha_bancos', 'idx_lb_origem', 'origem, id_origem');

    // Títulos: valores maiores, categoria de receita, centro de custo, recorrência
    foreach (['receber', 'pagar'] as $t) {
        $pdo->exec("ALTER TABLE $t MODIFY valor DECIMAL(12,2) NOT NULL, MODIFY subtotal DECIMAL(12,2) NULL, MODIFY multa DECIMAL(12,2) NULL,
            MODIFY juros DECIMAL(12,2) NULL, MODIFY desconto DECIMAL(12,2) NULL, MODIFY taxa DECIMAL(12,2) NULL, MODIFY valor_restante DECIMAL(12,2) NULL");
        fin_add_col($pdo, $t, 'centro_custo', "INT NULL");
        fin_add_col($pdo, $t, 'recorrencia_gerada', "TINYINT(1) NOT NULL DEFAULT 0");
        fin_add_indice($pdo, $t, "idx_{$t}_venc", 'vencimento');
    }
    fin_add_col($pdo, 'receber', 'categoria_receber', "INT NULL");
    fin_add_col($pdo, 'pagar', 'categoria_pagar', "INT NULL");
    if (!fin_col_existe($pdo, 'pagar', 'valor_restante')) {
        $pdo->exec("ALTER TABLE pagar ADD COLUMN valor_restante DECIMAL(12,2) NULL");
    }
    fin_add_indice($pdo, 'receber_pagamentos', 'idx_rp_receber', 'id_receber');
    fin_add_indice($pdo, 'pagar_pagamentos', 'idx_pp_pagar', 'id_pagar');
    fin_add_col($pdo, 'receber_pagamentos', 'id_adiantamento', "INT NULL");
    fin_add_col($pdo, 'pagar_pagamentos', 'id_adiantamento', "INT NULL");

    // Formas de pagamento: conta padrão, prazo de compensação (cartão D+N) e taxa com centavos
    fin_add_col($pdo, 'formas_pgto', 'banco_padrao', "INT NULL");
    fin_add_col($pdo, 'formas_pgto', 'prazo_recebimento', "INT NOT NULL DEFAULT 0");
    $pdo->exec("ALTER TABLE formas_pgto MODIFY taxa DECIMAL(5,2) NULL DEFAULT 0");

    // Parâmetros
    fin_add_col($pdo, 'config', 'data_bloqueio_fin', "DATE NULL");
    fin_add_col($pdo, 'config', 'pix_chave', "VARCHAR(100) NULL");
    fin_add_col($pdo, 'config', 'pix_nome', "VARCHAR(60) NULL");
    fin_add_col($pdo, 'config', 'pix_cidade', "VARCHAR(40) NULL");

    // Categorias de receita / grupos da DRE
    $pdo->exec("CREATE TABLE IF NOT EXISTS categorias_receber (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        grupo_dre VARCHAR(20) NOT NULL DEFAULT 'receita',
        protegida TINYINT(1) NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    if (fin_q($pdo, "SELECT COUNT(*) FROM categorias_receber")->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO categorias_receber (nome, grupo_dre, protegida) VALUES
            ('Venda de Mercadorias', 'receita', 1),
            ('Prestação de Serviços', 'receita', 0),
            ('Receitas Financeiras', 'financeira', 0),
            ('Outras Receitas', 'outras', 0)");
    }
    if (!fin_tabela_existe($pdo, 'categorias_pagar')) {
        $pdo->exec("CREATE TABLE categorias_pagar (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, nome VARCHAR(100) NOT NULL, protegida TINYINT(1) NOT NULL DEFAULT 0) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    if (!fin_col_existe($pdo, 'categorias_pagar', 'grupo_dre')) {
        $pdo->exec("ALTER TABLE categorias_pagar ADD COLUMN grupo_dre VARCHAR(20) NOT NULL DEFAULT 'operacional'");
        $pdo->exec("UPDATE categorias_pagar SET grupo_dre = 'custo' WHERE nome IN ('Produtor Rural', 'Fornecedor / Compra')");
        $pdo->exec("UPDATE categorias_pagar SET grupo_dre = 'pessoal' WHERE nome IN ('Salário', 'Adiantamento', '13° Salário / Férias', 'FGTS / INSS')");
        $pdo->exec("UPDATE categorias_pagar SET grupo_dre = 'impostos' WHERE nome IN ('Imposto / Taxa')");
    }
    if (fin_q($pdo, "SELECT COUNT(*) FROM categorias_pagar WHERE nome = 'Tarifas Bancárias'")->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO categorias_pagar (nome, grupo_dre) VALUES ('Tarifas Bancárias', 'financeira')");
    }
    if (fin_q($pdo, "SELECT COUNT(*) FROM categorias_pagar WHERE nome = 'Juros e Encargos Pagos'")->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO categorias_pagar (nome, grupo_dre) VALUES ('Juros e Encargos Pagos', 'financeira')");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS centros_custo (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        ativo TINYINT(1) NOT NULL DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS fin_auditoria (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        data DATETIME NOT NULL,
        usuario INT NULL,
        acao VARCHAR(40) NOT NULL,
        entidade VARCHAR(30) NOT NULL,
        id_entidade INT NULL,
        descricao VARCHAR(500) NULL,
        dados TEXT NULL,
        INDEX idx_aud_data (data),
        INDEX idx_aud_ent (entidade, id_entidade)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS extrato_importado (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        id_banco INT NOT NULL,
        fitid VARCHAR(120) NOT NULL,
        data DATE NOT NULL,
        valor DECIMAL(15,2) NOT NULL,
        descricao VARCHAR(255) NULL,
        documento VARCHAR(60) NULL,
        arquivo VARCHAR(150) NULL,
        data_importacao DATETIME NOT NULL,
        usuario INT NULL,
        id_linha_banco INT NULL,
        status VARCHAR(15) NOT NULL DEFAULT 'pendente',
        UNIQUE KEY uq_extrato (id_banco, fitid),
        INDEX idx_ext_status (id_banco, status, data)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    fin_criar_tabela_adiantamentos($pdo);

    // Receitas existentes vieram todas de vendas/romaneios
    $id_venda = (int) fin_q($pdo, "SELECT id FROM categorias_receber WHERE nome = 'Venda de Mercadorias'")->fetchColumn();
    if ($id_venda) {
        fin_q($pdo, "UPDATE receber SET categoria_receber = ? WHERE categoria_receber IS NULL", [$id_venda]);
    }

    // Recorrências já pagas no passado: não gerar de novo
    $pdo->exec("UPDATE pagar SET recorrencia_gerada = 1 WHERE pago = 'Sim' AND frequencia > 0");
    $pdo->exec("UPDATE receber SET recorrencia_gerada = 1 WHERE pago = 'Sim' AND frequencia > 0");

    // Liga as linhas antigas do extrato aos pagamentos de contas a pagar que as geraram
    fin_vincular_legado_pagar($pdo);
    // Linhas de saldo "Estorno de Baixa" antigas já se compensam: marcamos a origem para leitura
    $pdo->exec("UPDATE linha_bancos SET origem = 'legado_estorno' WHERE origem IS NULL AND status = 'Estorno'");

    // Espelho de saldo passa a ser a soma do extrato
    foreach (fin_q($pdo, "SELECT id FROM bancos")->fetchAll(PDO::FETCH_COLUMN) as $b) {
        fin_recalcular_saldo($pdo, $b);
    }
}

function fin_migracao_2($pdo)
{
    // Permissões das telas novas (grupo Financeiro = 4)
    $grupo = (int) fin_q($pdo, "SELECT id FROM grupo_acessos WHERE nome = 'Financeiro'")->fetchColumn() ?: 4;
    $telas = [
        ['Contas Bancárias e Extrato', 'contas_bancarias', 'Sim'],
        ['Conciliação Bancária', 'conciliacao', 'Sim'],
        ['Fluxo de Caixa', 'fluxo_caixa', 'Sim'],
        ['DRE Gerencial', 'dre', 'Sim'],
        ['Categorias de Receitas', 'categorias_receber', 'Sim'],
        ['Centros de Custo', 'centros_custo', 'Sim'],
        ['Adiantamentos', 'adiantamentos', 'Sim'],
        ['Auditoria Financeira', 'auditoria_fin', 'Sim'],
        ['Parâmetros Financeiros', 'parametros_fin', 'Sim'],
        ['Estornar Baixas', 'estornar_baixas', 'Não'],
    ];
    foreach ($telas as [$nome, $chave, $pagina]) {
        if (!fin_q($pdo, "SELECT id FROM acessos WHERE chave = ?", [$chave])->fetchColumn()) {
            fin_q($pdo, "INSERT INTO acessos (nome, chave, grupo, pagina) VALUES (?, ?, ?, ?)", [$nome, $chave, $grupo, $pagina]);
        }
    }
    // Quem já trabalha com contas a pagar/receber continua vendo bancos, extrato e conciliação
    $novas = fin_q($pdo, "SELECT id FROM acessos WHERE chave IN ('contas_bancarias', 'conciliacao', 'fluxo_caixa', 'adiantamentos')")->fetchAll(PDO::FETCH_COLUMN);
    $usuarios = fin_q($pdo, "SELECT DISTINCT up.usuario FROM usuarios_permissoes up JOIN acessos a ON a.id = up.permissao WHERE a.chave IN ('receber', 'pagar')")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($usuarios as $u) {
        foreach ($novas as $p) {
            if (!fin_q($pdo, "SELECT id FROM usuarios_permissoes WHERE usuario = ? AND permissao = ?", [$u, $p])->fetchColumn()) {
                fin_q($pdo, "INSERT INTO usuarios_permissoes (usuario, permissao) VALUES (?, ?)", [$u, $p]);
            }
        }
    }
}

function fin_migracao_3($pdo)
{
    // Pagamentos órfãos (título já apagado pelo código antigo) não devem pesar em relatório nenhum
    fin_add_col($pdo, 'pagar_pagamentos', 'orfao', "TINYINT(1) NOT NULL DEFAULT 0");
    $pdo->exec("UPDATE pagar_pagamentos pp LEFT JOIN pagar p ON p.id = pp.id_pagar SET pp.orfao = 1 WHERE p.id IS NULL");
}


// "adiantamentos" já existe no sistema (adiantamento de salário dos funcionários): esta é outra tabela
function fin_criar_tabela_adiantamentos($pdo)
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS fin_adiantamentos (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        tipo VARCHAR(15) NOT NULL,
        pessoa INT NOT NULL,
        descricao VARCHAR(150) NULL,
        valor DECIMAL(12,2) NOT NULL,
        saldo DECIMAL(12,2) NOT NULL,
        data DATE NOT NULL,
        id_banco INT NULL,
        forma_pgto INT NULL,
        usuario INT NULL,
        data_lanc DATETIME NOT NULL,
        INDEX idx_adi_pessoa (tipo, pessoa)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function fin_migracao_4($pdo)
{
    fin_criar_tabela_adiantamentos($pdo);
}

// Só metadados (não muda saldo): liga linhas antigas do extrato que ficaram sem dono
function fin_migracao_5($pdo)
{
    $busca = $pdo->prepare("SELECT id FROM linha_bancos WHERE origem IS NULL AND id_banco = ? AND DATE(data) = ? AND debito = ? ORDER BY id LIMIT 1");
    // Pagamentos cujo título foi apagado pelo erro antigo do romaneio de compra
    foreach (fin_q($pdo, "SELECT * FROM pagar_pagamentos pp WHERE orfao = 1 AND banco > 0
        AND NOT EXISTS (SELECT 1 FROM linha_bancos l WHERE l.origem = 'pagar_pgto' AND l.id_origem = pp.id)")->fetchAll(PDO::FETCH_ASSOC) as $pg) {
        $busca->execute([$pg['banco'], $pg['data_pgto'], $pg['valor']]);
        if ($id = $busca->fetchColumn()) fin_q($pdo, "UPDATE linha_bancos SET origem = 'pagar_pgto', id_origem = ? WHERE id = ?", [$pg['id'], $id]);
    }
    // Rebaixas antigas: a saída original ficou no extrato e foi anulada por um "Estorno de Baixa" de mesmo valor
    foreach (fin_q($pdo, "SELECT * FROM linha_bancos WHERE origem = 'legado_estorno' AND id_origem IS NULL")->fetchAll(PDO::FETCH_ASSOC) as $e) {
        $par = fin_q($pdo, "SELECT id FROM linha_bancos WHERE origem IS NULL AND id_banco = ? AND debito = ? AND DATE(data) <= DATE(?) ORDER BY data DESC, id DESC LIMIT 1",
            [$e['id_banco'], $e['credito'], $e['data']])->fetchColumn();
        if ($par) {
            fin_q($pdo, "UPDATE linha_bancos SET origem = 'legado_estorno', id_origem = ? WHERE id = ?", [$e['id'], $par]);
            fin_q($pdo, "UPDATE linha_bancos SET id_origem = ? WHERE id = ?", [$par, $e['id']]);
        }
    }
}

// Pagamentos de contas a pagar antigos sempre geraram uma linha de débito no extrato
// com a mesma descrição, banco, data e valor. Liga um a um (o mais antigo primeiro).
function fin_vincular_legado_pagar($pdo)
{
    $pgtos = fin_q($pdo, "SELECT pp.id, pp.banco, pp.data_pgto, pp.valor, p.descricao
        FROM pagar_pagamentos pp JOIN pagar p ON p.id = pp.id_pagar
        WHERE pp.banco IS NOT NULL AND pp.banco > 0 ORDER BY pp.id")->fetchAll(PDO::FETCH_ASSOC);
    $busca = $pdo->prepare("SELECT id FROM linha_bancos WHERE origem IS NULL AND id_banco = ? AND DATE(data) = ? AND debito = ? AND descricao = ? ORDER BY id LIMIT 1");
    $liga = $pdo->prepare("UPDATE linha_bancos SET origem = 'pagar_pgto', id_origem = ? WHERE id = ?");
    foreach ($pgtos as $p) {
        $busca->execute([$p['banco'], $p['data_pgto'], $p['valor'], $p['descricao']]);
        $id = $busca->fetchColumn();
        if ($id) $liga->execute([$p['id'], $id]);
    }
}

// ---------------------------------------------------------------------
// Configuração, período bloqueado e auditoria
// ---------------------------------------------------------------------

function fin_config($pdo)
{
    static $cfg = null;
    if ($cfg === null) {
        $cfg = fin_q($pdo, "SELECT * FROM config LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];
    }
    return $cfg;
}

function fin_data_bloqueio($pdo)
{
    $d = fin_config($pdo)['data_bloqueio_fin'] ?? null;
    return ($d && $d != '0000-00-00') ? $d : null;
}

function fin_validar_periodo($pdo, $data, $acao = 'alterar')
{
    $bloqueio = fin_data_bloqueio($pdo);
    $data = substr((string) $data, 0, 10);
    if ($bloqueio && $data && $data <= $bloqueio) {
        throw new FinErro("Período fechado: não é possível $acao movimentos com data até " . fin_data_br($bloqueio) . ". Peça ao administrador para reabrir o período em Parâmetros Financeiros.");
    }
}

function fin_log($pdo, $acao, $entidade, $id_entidade, $descricao, $dados = null)
{
    fin_q($pdo, "INSERT INTO fin_auditoria (data, usuario, acao, entidade, id_entidade, descricao, dados) VALUES (NOW(), ?, ?, ?, ?, ?, ?)", [
        fin_usuario(), $acao, $entidade, $id_entidade, mb_substr((string) $descricao, 0, 500),
        $dados === null ? null : json_encode($dados, JSON_UNESCAPED_UNICODE),
    ]);
}

function fin_pode_estornar()
{
    @session_start();
    if (@$_SESSION['nivel'] == 'Administrador' || @$_SESSION['nivel'] == 'Gerente') return true;
    $st = $GLOBALS['pdo']->prepare("SELECT COUNT(*) FROM usuarios_permissoes up JOIN acessos a ON a.id = up.permissao WHERE up.usuario = ? AND a.chave = 'estornar_baixas'");
    $st->execute([fin_usuario()]);
    return $st->fetchColumn() > 0;
}

// ---------------------------------------------------------------------
// Extrato bancário (razão)
// ---------------------------------------------------------------------

function fin_recalcular_saldo($pdo, $id_banco)
{
    if (!$id_banco) return;
    fin_q($pdo, "UPDATE bancos SET saldo = (SELECT COALESCE(SUM(COALESCE(credito,0) - COALESCE(debito,0)), 0) FROM linha_bancos WHERE id_banco = ?) WHERE id = ?", [$id_banco, $id_banco]);
}

// Saldo de um banco (ou de todos) ao fim do dia $ate (inclusive). Sem data: saldo atual.
function fin_saldo($pdo, $id_banco = null, $ate = null)
{
    $sql = "SELECT COALESCE(SUM(COALESCE(credito,0) - COALESCE(debito,0)), 0) FROM linha_bancos WHERE 1=1";
    $p = [];
    if ($id_banco) { $sql .= " AND id_banco = ?"; $p[] = $id_banco; }
    if ($ate) { $sql .= " AND DATE(data) <= ?"; $p[] = $ate; }
    return round((float) fin_q($pdo, $sql, $p)->fetchColumn(), 2);
}

function fin_banco_existe($pdo, $id_banco)
{
    return $id_banco && fin_q($pdo, "SELECT COUNT(*) FROM bancos WHERE id = ?", [$id_banco])->fetchColumn() > 0;
}

function fin_nome_banco($pdo, $id_banco)
{
    return (string) fin_q($pdo, "SELECT banco FROM bancos WHERE id = ?", [$id_banco])->fetchColumn();
}

// Insere uma linha no extrato. $l: id_banco, data, descricao, credito|debito, origem, id_origem,
// documento, obs, categoria_receber, categoria_pagar, centro_custo, id_transferencia
function fin_lancar($pdo, array $l)
{
    $id_banco = (int) ($l['id_banco'] ?? 0);
    if (!fin_banco_existe($pdo, $id_banco)) throw new FinErro('Conta bancária inválida ou não informada.');
    $data = substr((string) ($l['data'] ?? ''), 0, 10);
    if (!fin_data_valida($data)) throw new FinErro('Data do lançamento inválida.');
    $credito = round((float) ($l['credito'] ?? 0), 2);
    $debito = round((float) ($l['debito'] ?? 0), 2);
    if ($credito < 0 || $debito < 0 || ($credito == 0 && $debito == 0)) throw new FinErro('Valor do lançamento inválido.');
    fin_validar_periodo($pdo, $data, 'lançar');

    fin_q($pdo, "INSERT INTO linha_bancos SET id_banco = ?, data = ?, descricao = ?, credito = ?, debito = ?, saldo = NULL,
        remetente = ?, n_fiscal = ?, classificacao = ?, mes_ref = ?, status = 'Confirmado', origem = ?, id_origem = ?,
        documento = ?, obs = ?, categoria_receber = ?, categoria_pagar = ?, centro_custo = ?, id_transferencia = ?, data_lancamento = NOW()", [
        $id_banco, $data, mb_substr((string) ($l['descricao'] ?? ''), 0, 255), $credito, $debito,
        fin_usuario(), mb_substr((string) ($l['documento'] ?? ''), 0, 35), $credito > 0 ? 1 : 2, (int) date('m', strtotime($data)),
        $l['origem'] ?? 'manual', $l['id_origem'] ?? null,
        ($l['documento'] ?? '') ?: null, ($l['obs'] ?? '') ?: null,
        ($l['categoria_receber'] ?? null) ?: null, ($l['categoria_pagar'] ?? null) ?: null, ($l['centro_custo'] ?? null) ?: null,
        $l['id_transferencia'] ?? null,
    ]);
    $id = (int) $pdo->lastInsertId();
    fin_recalcular_saldo($pdo, $id_banco);
    return $id;
}

// Remove as linhas geradas por uma origem. Recusa se alguma estiver conciliada ou em período fechado.
function fin_remover_lancamentos($pdo, $origem, $id_origem)
{
    $linhas = fin_q($pdo, "SELECT * FROM linha_bancos WHERE origem = ? AND id_origem = ?", [$origem, $id_origem])->fetchAll(PDO::FETCH_ASSOC);
    foreach ($linhas as $l) fin_validar_remocao_linha($pdo, $l);
    foreach ($linhas as $l) fin_apagar_linha($pdo, $l);
    return count($linhas);
}

function fin_validar_remocao_linha($pdo, array $l)
{
    if ((int) $l['conciliado'] === 1) {
        throw new FinErro('O lançamento de ' . fin_moeda($l['credito'] > 0 ? $l['credito'] : $l['debito']) . ' em ' . fin_data_br($l['data']) . ' já está conciliado com o extrato do banco. Desconcilie-o na tela de Conciliação antes de continuar.');
    }
    fin_validar_periodo($pdo, $l['data'], 'estornar');
}

function fin_apagar_linha($pdo, array $l)
{
    fin_q($pdo, "UPDATE extrato_importado SET id_linha_banco = NULL, status = 'pendente' WHERE id_linha_banco = ?", [$l['id']]);
    fin_q($pdo, "DELETE FROM linha_bancos WHERE id = ?", [$l['id']]);
    fin_recalcular_saldo($pdo, $l['id_banco']);
}

function fin_transferir($pdo, $origem, $destino, $valor, $data, $descricao = '', $documento = '')
{
    $valor = round((float) $valor, 2);
    if ($origem == $destino) throw new FinErro('Escolha contas diferentes para a transferência.');
    if ($valor <= 0) throw new FinErro('Informe o valor da transferência.');
    return fin_transacao($pdo, function () use ($pdo, $origem, $destino, $valor, $data, $descricao, $documento) {
        $desc = $descricao ?: ('Transferência ' . fin_nome_banco($pdo, $origem) . ' → ' . fin_nome_banco($pdo, $destino));
        $id1 = fin_lancar($pdo, ['id_banco' => $origem, 'data' => $data, 'descricao' => $desc, 'debito' => $valor, 'origem' => 'transferencia', 'documento' => $documento]);
        $id2 = fin_lancar($pdo, ['id_banco' => $destino, 'data' => $data, 'descricao' => $desc, 'credito' => $valor, 'origem' => 'transferencia', 'documento' => $documento]);
        fin_q($pdo, "UPDATE linha_bancos SET id_transferencia = ?, id_origem = ? WHERE id IN (?, ?)", [$id1, $id1, $id1, $id2]);
        fin_log($pdo, 'transferencia', 'linha_bancos', $id1, "$desc: " . fin_moeda($valor) . ' em ' . fin_data_br($data));
        return $id1;
    });
}

// Exclui um lançamento feito direto no extrato (manual, transferência, saldo inicial ou legado sem vínculo)
function fin_excluir_lancamento($pdo, $id)
{
    return fin_transacao($pdo, function () use ($pdo, $id) {
        $l = fin_q($pdo, "SELECT * FROM linha_bancos WHERE id = ?", [$id])->fetch(PDO::FETCH_ASSOC);
        if (!$l) throw new FinErro('Lançamento não encontrado.');
        $origem = $l['origem'];
        if (in_array($origem, ['receber_pgto', 'pagar_pgto'])) {
            throw new FinErro('Este lançamento veio da baixa de um título. Estorne a baixa em Contas a ' . ($origem == 'receber_pgto' ? 'Receber' : 'Pagar') . '.');
        }
        if ($origem == 'adiantamento') throw new FinErro('Este lançamento veio de um adiantamento. Exclua-o na tela de Adiantamentos.');
        $linhas = [$l];
        if ($origem == 'transferencia' && $l['id_transferencia']) {
            $linhas = fin_q($pdo, "SELECT * FROM linha_bancos WHERE id_transferencia = ?", [$l['id_transferencia']])->fetchAll(PDO::FETCH_ASSOC);
        }
        foreach ($linhas as $x) fin_validar_remocao_linha($pdo, $x);
        foreach ($linhas as $x) fin_apagar_linha($pdo, $x);
        fin_log($pdo, 'excluir_lancamento', 'linha_bancos', $id, 'Excluiu lançamento "' . $l['descricao'] . '" de ' . fin_moeda($l['credito'] > 0 ? $l['credito'] : $l['debito']) . ' em ' . fin_data_br($l['data']), $l);
        return count($linhas);
    });
}

// Saldo inicial: uma linha por conta, origem 'saldo_inicial'. Valor negativo = conta começou devedora.
function fin_definir_saldo_inicial($pdo, $id_banco, $valor, $data)
{
    return fin_transacao($pdo, function () use ($pdo, $id_banco, $valor, $data) {
        $atual = fin_q($pdo, "SELECT * FROM linha_bancos WHERE id_banco = ? AND origem = 'saldo_inicial'", [$id_banco])->fetch(PDO::FETCH_ASSOC);
        if ($atual) {
            if (round($atual['credito'] - $atual['debito'], 2) == round($valor, 2) && substr($atual['data'], 0, 10) == $data) return;
            fin_validar_remocao_linha($pdo, $atual);
            fin_apagar_linha($pdo, $atual);
        }
        if (round($valor, 2) != 0) {
            fin_lancar($pdo, ['id_banco' => $id_banco, 'data' => $data, 'descricao' => 'Saldo inicial',
                'credito' => $valor > 0 ? $valor : 0, 'debito' => $valor < 0 ? -$valor : 0, 'origem' => 'saldo_inicial', 'id_origem' => $id_banco]);
        }
        fin_log($pdo, 'saldo_inicial', 'bancos', $id_banco, 'Saldo inicial definido em ' . fin_moeda($valor) . ' na data ' . fin_data_br($data));
    });
}

// Descrição legível de uma linha do extrato (linhas antigas guardavam o id de descricao_banco)
function fin_descricao_linha($pdo, $descricao)
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (fin_q($pdo, "SELECT id, descricao FROM descricao_banco")->fetchAll(PDO::FETCH_ASSOC) as $r) $cache[(string) $r['id']] = $r['descricao'];
    }
    $descricao = (string) $descricao;
    if ($descricao === '' || $descricao === '0') return 'Lançamento sem descrição';
    if (ctype_digit($descricao) && isset($cache[$descricao])) return $cache[$descricao];
    return $descricao;
}

// ---------------------------------------------------------------------
// Títulos (contas a receber / pagar)
// ---------------------------------------------------------------------

function fin_cfg($tipo)
{
    if ($tipo === 'receber') {
        return ['tabela' => 'receber', 'tab_pg' => 'receber_pagamentos', 'fk' => 'id_receber', 'origem' => 'receber_pgto', 'adi' => 'cliente', 'verbo' => 'Receb.'];
    }
    if ($tipo === 'pagar') {
        return ['tabela' => 'pagar', 'tab_pg' => 'pagar_pagamentos', 'fk' => 'id_pagar', 'origem' => 'pagar_pgto', 'adi' => 'fornecedor', 'verbo' => 'Pagto'];
    }
    throw new FinErro('Tipo de título inválido.');
}

function fin_titulo($pdo, $tipo, $id)
{
    $c = fin_cfg($tipo);
    $t = fin_q($pdo, "SELECT * FROM {$c['tabela']} WHERE id = ?", [$id])->fetch(PDO::FETCH_ASSOC);
    if (!$t) throw new FinErro('Título não encontrado.');
    return $t;
}

function fin_pagamentos($pdo, $tipo, $id)
{
    $c = fin_cfg($tipo);
    return fin_q($pdo, "SELECT * FROM {$c['tab_pg']} WHERE {$c['fk']} = ? ORDER BY id", [$id])->fetchAll(PDO::FETCH_ASSOC);
}

function fin_total_pago($pdo, $tipo, $id)
{
    $c = fin_cfg($tipo);
    return round((float) fin_q($pdo, "SELECT COALESCE(SUM(valor), 0) FROM {$c['tab_pg']} WHERE {$c['fk']} = ?", [$id])->fetchColumn(), 2);
}

function fin_forma($pdo, $id_forma)
{
    static $cache = [];
    if (!isset($cache[$id_forma])) {
        $cache[$id_forma] = fin_q($pdo, "SELECT * FROM formas_pgto WHERE id = ?", [$id_forma])->fetch(PDO::FETCH_ASSOC) ?: [];
    }
    return $cache[$id_forma];
}

function fin_nome_pessoa_titulo($pdo, $tipo, array $t)
{
    if ($tipo === 'receber') {
        return (string) fin_q($pdo, "SELECT nome FROM clientes WHERE id = ?", [(int) $t['cliente']])->fetchColumn();
    }
    if (!empty($t['fornecedor'])) {
        return (string) fin_q($pdo, "SELECT nome_atacadista FROM fornecedores WHERE id = ?", [(int) $t['fornecedor']])->fetchColumn();
    }
    if (!empty($t['funcionario'])) {
        return (string) fin_q($pdo, "SELECT nome FROM funcionarios WHERE id = ?", [(int) $t['funcionario']])->fetchColumn();
    }
    return '';
}

// Desfaz os pagamentos de um título: remove as linhas do extrato que eles geraram (ou compensa as
// antigas sem vínculo), devolve adiantamentos usados e apaga os registros de pagamento.
function fin_estornar_pagamentos($pdo, $tipo, $id)
{
    $c = fin_cfg($tipo);
    $pgtos = fin_pagamentos($pdo, $tipo, $id);
    $t = fin_q($pdo, "SELECT * FROM {$c['tabela']} WHERE id = ?", [$id])->fetch(PDO::FETCH_ASSOC) ?: ['descricao' => ''];
    if (!$pgtos && in_array($t['pago'] ?? '', ['Sim', 'Parcial'], true)) {
        // Baixa gravada pela versão antiga, sem registro de pagamento: o extrato não saberia o que desfazer
        throw new FinErro('A baixa de "' . $t['descricao'] . '" foi feita pela versão antiga do sistema. Peça ao administrador para executar o Saneamento Financeiro antes de alterá-la.');
    }

    // Valida tudo antes de mexer em qualquer coisa
    foreach ($pgtos as $pg) {
        fin_validar_periodo($pdo, $pg['data_pgto'], 'estornar');
        foreach (fin_q($pdo, "SELECT * FROM linha_bancos WHERE origem = ? AND id_origem = ?", [$c['origem'], $pg['id']])->fetchAll(PDO::FETCH_ASSOC) as $l) {
            fin_validar_remocao_linha($pdo, $l);
        }
    }

    foreach ($pgtos as $pg) {
        $removidas = fin_remover_lancamentos($pdo, $c['origem'], $pg['id']);
        if (!empty($pg['id_adiantamento'])) {
            fin_q($pdo, "UPDATE fin_adiantamentos SET saldo = saldo + ? WHERE id = ?", [$pg['valor'], $pg['id_adiantamento']]);
        } elseif ($removidas == 0 && $tipo === 'pagar' && !empty($pg['banco'])) {
            // Pagamento antigo cujo débito no extrato não pôde ser identificado: compensa com um crédito
            fin_lancar($pdo, ['id_banco' => $pg['banco'], 'data' => date('Y-m-d'), 'descricao' => 'Estorno de baixa: ' . $t['descricao'],
                'credito' => $pg['valor'], 'origem' => 'estorno_legado', 'id_origem' => $id]);
        }
    }
    fin_q($pdo, "DELETE FROM {$c['tab_pg']} WHERE {$c['fk']} = ?", [$id]);
    return $pgtos;
}

// Grava (ou regrava) a baixa de um título.
// $pagamentos: lista de ['valor','data','forma','banco','operacao','id_adiantamento']
// $ajustes: ['multa','juros','acrescimo','desconto']
function fin_baixar($pdo, $tipo, $id, array $pagamentos, array $ajustes, $obs = '')
{
    $c = fin_cfg($tipo);
    return fin_transacao($pdo, function () use ($pdo, $tipo, $c, $id, $pagamentos, $ajustes, $obs) {
        $t = fin_titulo($pdo, $tipo, $id);
        if (($t['cancelada'] ?? '') === 'Sim') throw new FinErro('Este título está cancelado.');

        $multa = fin_num($ajustes['multa'] ?? 0);
        $juros = fin_num($ajustes['juros'] ?? 0);
        $acrescimo = fin_num($ajustes['acrescimo'] ?? 0);
        $desconto = fin_num($ajustes['desconto'] ?? 0);
        $neg = [];
        foreach (['multa' => $multa, 'juros' => $juros, 'acréscimo' => $acrescimo, 'desconto' => $desconto] as $n => $v) if ($v < 0) $neg[] = $n;
        if ($neg) throw new FinErro('Valor impossível: ' . implode(', ', $neg) . ' não pode ser negativo.');
        $bruto = round($t['valor'] + $multa + $juros + $acrescimo, 2);
        if ($desconto > $bruto) throw new FinErro('Valor impossível: o desconto (' . fin_moeda($desconto) . ') supera o total do título (' . fin_moeda($bruto) . ').');
        $subtotal = round($bruto - $desconto, 2);

        // Normaliza e valida as linhas de pagamento
        $validos = [];
        foreach ($pagamentos as $pg) {
            $v = fin_num($pg['valor'] ?? 0);
            if ($v <= 0) continue;
            $data = $pg['data'] ?? '';
            if (!fin_data_valida($data)) throw new FinErro('Informe uma data válida em cada linha de pagamento.');
            if ($data > date('Y-m-d', strtotime('+1 day'))) throw new FinErro('A data de pagamento ' . fin_data_br($data) . ' está no futuro.');
            $adi = (int) ($pg['id_adiantamento'] ?? 0);
            $banco = (int) ($pg['banco'] ?? 0);
            $forma = (int) ($pg['forma'] ?? 0);
            if (!$adi) {
                if (!$forma) throw new FinErro('Selecione a forma de pagamento em cada linha.');
                if (!$banco) $banco = (int) (fin_forma($pdo, $forma)['banco_padrao'] ?? 0);
                if (!fin_banco_existe($pdo, $banco)) throw new FinErro('Selecione a conta bancária em cada linha de pagamento.');
            }
            $validos[] = ['valor' => $v, 'data' => $data, 'forma' => $forma, 'banco' => $adi ? null : $banco, 'operacao' => trim((string) ($pg['operacao'] ?? '')), 'id_adiantamento' => $adi ?: null];
        }

        $estava_pago = ($t['pago'] === 'Sim');
        $anteriores = fin_estornar_pagamentos($pdo, $tipo, $id);

        $total = 0;
        $pessoa = fin_nome_pessoa_titulo($pdo, $tipo, $t);
        foreach ($validos as $pg) {
            fin_validar_periodo($pdo, $pg['data'], 'baixar');
            if ($pg['id_adiantamento']) {
                $adi = fin_q($pdo, "SELECT * FROM fin_adiantamentos WHERE id = ? FOR UPDATE", [$pg['id_adiantamento']])->fetch(PDO::FETCH_ASSOC);
                $pessoa_titulo = $tipo === 'receber' ? (int) $t['cliente'] : (int) $t['fornecedor'];
                if (!$adi || $adi['tipo'] !== $c['adi'] || (int) $adi['pessoa'] !== $pessoa_titulo) throw new FinErro('Adiantamento inválido para esta pessoa.');
                if ($pg['valor'] > round($adi['saldo'], 2)) throw new FinErro('O adiantamento #' . $adi['id'] . ' só tem ' . fin_moeda($adi['saldo']) . ' disponível.');
                fin_q($pdo, "UPDATE fin_adiantamentos SET saldo = saldo - ? WHERE id = ?", [$pg['valor'], $adi['id']]);
            }
            fin_q($pdo, "INSERT INTO {$c['tab_pg']} SET {$c['fk']} = ?, valor = ?, data_pgto = ?, forma_pgto = ?, banco = ?, numero_operacao = ?, id_adiantamento = ?", [
                $id, $pg['valor'], $pg['data'], $pg['forma'] ?: null, $pg['banco'], $pg['operacao'], $pg['id_adiantamento'],
            ]);
            $id_pg = (int) $pdo->lastInsertId();
            if ($pg['banco']) {
                // Cartão e afins: o dinheiro cai na conta D+N
                $prazo = $tipo === 'receber' ? (int) (fin_forma($pdo, $pg['forma'])['prazo_recebimento'] ?? 0) : 0;
                $data_extrato = $prazo > 0 ? date('Y-m-d', strtotime("+$prazo days", strtotime($pg['data']))) : $pg['data'];
                fin_lancar($pdo, [
                    'id_banco' => $pg['banco'], 'data' => $data_extrato,
                    'descricao' => $c['verbo'] . ' ' . $t['descricao'] . ($pessoa ? ' - ' . $pessoa : ''),
                    $tipo === 'receber' ? 'credito' : 'debito' => $pg['valor'],
                    'origem' => $c['origem'], 'id_origem' => $id_pg, 'documento' => $pg['operacao'],
                    'categoria_receber' => $tipo === 'receber' ? ($t['categoria_receber'] ?? null) : null,
                    'categoria_pagar' => $tipo === 'pagar' ? ($t['categoria_pagar'] ?? null) : null,
                    'centro_custo' => $t['centro_custo'] ?? null,
                ]);
            }
            $total += $pg['valor'];
        }
        $total = round($total, 2);

        $status = 'Não';
        if ($total > 0) $status = ($total >= $subtotal - 0.009) ? 'Sim' : 'Parcial';
        $restante = max(0, round($subtotal - $total, 2));
        $primeiro = $validos[0] ?? null;
        $ultimo_data = $validos ? max(array_column($validos, 'data')) : null;

        fin_q($pdo, "UPDATE {$c['tabela']} SET pago = ?, subtotal = ?, taxa = ?, juros = ?, multa = ?, desconto = ?, valor_restante = ?,
            obs = ?, data_pgto = ?, usuario_pgto = ?, hora = curTime()" . ($primeiro && $primeiro['forma'] ? ", forma_pgto = " . (int) $primeiro['forma'] : '') .
            ($tipo === 'receber' ? ", banco = " . ($primeiro && $primeiro['banco'] ? (int) $primeiro['banco'] : 'NULL') : '') . " WHERE id = ?", [
            $status, $subtotal, $acrescimo, $juros, $multa, $desconto, $status == 'Não' ? null : $restante,
            mb_substr($obs !== '' ? $obs : ($t['obs'] ?? ''), 0, $tipo === 'receber' ? 1000 : 100),
            $status == 'Não' ? null : $ultimo_data, $status == 'Não' ? 0 : fin_usuario(), $id,
        ]);

        // Recorrência: só uma vez por título, quando ele fica totalmente pago
        if ($status === 'Sim' && (int) $t['frequencia'] > 0 && !(int) $t['recorrencia_gerada']) {
            fin_gerar_recorrencia($pdo, $tipo, $t);
        }

        if (!empty($t['id_venda_real']) && $tipo === 'receber' && function_exists('venda_recalcular_status')) {
            venda_recalcular_status($pdo, $t['id_venda_real']);
        }

        fin_log($pdo, $anteriores ? 'rebaixa' : 'baixa', $c['tabela'], $id,
            ($anteriores ? 'Alterou a baixa de "' : 'Baixou "') . $t['descricao'] . '": ' . fin_moeda($total) . " ($status)",
            ['pagamentos' => $validos, 'anteriores' => $anteriores, 'ajustes' => compact('multa', 'juros', 'acrescimo', 'desconto')]);

        return ['status' => $status, 'total' => $total, 'restante' => $restante, 'estava_pago' => $estava_pago];
    });
}

function fin_gerar_recorrencia($pdo, $tipo, array $t)
{
    $c = fin_cfg($tipo);
    $novo = [
        'descricao' => $t['descricao'], 'valor' => $t['valor'], 'subtotal' => $t['valor'],
        'vencimento' => fin_proximo_vencimento($t['vencimento'], $t['frequencia']),
        'data_lanc' => date('Y-m-d'), 'frequencia' => $t['frequencia'], 'forma_pgto' => $t['forma_pgto'] ?: 0,
        'arquivo' => 'sem-foto.png', 'pago' => 'Não', 'referencia' => $t['referencia'] ?: 'Conta',
        'usuario_lanc' => fin_usuario(), 'usuario_pgto' => 0, 'centro_custo' => $t['centro_custo'] ?? null, 'hora' => date('H:i:s'),
    ];
    if ($tipo === 'receber') {
        $novo += ['cliente' => $t['cliente'] ?: 0, 'categoria_receber' => $t['categoria_receber'] ?? null];
    } else {
        $novo += ['fornecedor' => $t['fornecedor'] ?: 0, 'funcionario' => $t['funcionario'] ?: 0, 'categoria_pagar' => $t['categoria_pagar'] ?? null];
    }
    $sets = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($novo)));
    $p = [];
    foreach ($novo as $k => $v) $p[":$k"] = $v;
    fin_q($pdo, "INSERT INTO {$c['tabela']} SET $sets", $p);
    $novo_id = (int) $pdo->lastInsertId();
    fin_q($pdo, "UPDATE {$c['tabela']} SET recorrencia_gerada = 1 WHERE id = ?", [$t['id']]);
    fin_log($pdo, 'recorrencia', $c['tabela'], $novo_id, 'Gerou a próxima ocorrência de "' . $t['descricao'] . '" para ' . fin_data_br($novo['vencimento']));
    return $novo_id;
}

// Volta o título para "em aberto", desfazendo todos os pagamentos
function fin_estornar_baixa($pdo, $tipo, $id, $motivo = '')
{
    $c = fin_cfg($tipo);
    return fin_transacao($pdo, function () use ($pdo, $tipo, $c, $id, $motivo) {
        $t = fin_titulo($pdo, $tipo, $id);
        $pgtos = fin_estornar_pagamentos($pdo, $tipo, $id);
        if (!$pgtos && $t['pago'] === 'Não') throw new FinErro('Este título não tem baixa para estornar.');
        fin_q($pdo, "UPDATE {$c['tabela']} SET pago = 'Não', data_pgto = NULL, usuario_pgto = 0, valor_restante = NULL, subtotal = valor,
            multa = 0, juros = 0, taxa = 0, desconto = 0 WHERE id = ?", [$id]);
        if (!empty($t['id_venda_real']) && $tipo === 'receber' && function_exists('venda_recalcular_status')) {
            venda_recalcular_status($pdo, $t['id_venda_real']);
        }
        fin_log($pdo, 'estorno', $c['tabela'], $id, 'Estornou a baixa de "' . $t['descricao'] . '"' . ($motivo ? ": $motivo" : ''), ['pagamentos' => $pgtos]);
        return count($pgtos);
    });
}

// Exclui o título por completo (estornando antes o que tiver sido pago)
function fin_excluir_titulo($pdo, $tipo, $id, $motivo = '')
{
    $c = fin_cfg($tipo);
    return fin_transacao($pdo, function () use ($pdo, $tipo, $c, $id, $motivo) {
        $t = fin_q($pdo, "SELECT * FROM {$c['tabela']} WHERE id = ?", [$id])->fetch(PDO::FETCH_ASSOC);
        if (!$t) return false;
        $pgtos = fin_estornar_pagamentos($pdo, $tipo, $id);
        if (!$pgtos) fin_validar_periodo($pdo, $t['data_pgto'] ?: $t['data_lanc'], 'excluir');
        fin_q($pdo, "DELETE FROM {$c['tabela']} WHERE id = ?", [$id]);
        fin_log($pdo, 'excluir', $c['tabela'], $id, 'Excluiu "' . $t['descricao'] . '" de ' . fin_moeda($t['valor']) . ($motivo ? " ($motivo)" : ''), ['titulo' => $t, 'pagamentos' => $pgtos]);
        return true;
    });
}

// Depois que o valor de um título muda (ex.: romaneio editado), refaz subtotal/restante/status
// a partir do que já foi pago, sem mexer nos pagamentos.
function fin_recalcular_titulo($pdo, $tipo, $id)
{
    $c = fin_cfg($tipo);
    $t = fin_titulo($pdo, $tipo, $id);
    $pago = fin_total_pago($pdo, $tipo, $id);
    $subtotal = round($t['valor'] + (float) $t['multa'] + (float) $t['juros'] + (float) $t['taxa'] - (float) $t['desconto'], 2);
    if ($pago <= 0 && in_array($t['pago'], ['Sim', 'Parcial'], true) && !fin_pagamentos($pdo, $tipo, $id)) {
        return $t['pago']; // baixa antiga sem registro de pagamento: não rebaixa o status (o saneamento cuida disso)
    }
    if ($pago <= 0) {
        $status = 'Não';
        $restante = null;
        $subtotal = $t['valor'];
    } else {
        $status = $pago >= $subtotal - 0.009 ? 'Sim' : 'Parcial';
        $restante = max(0, round($subtotal - $pago, 2));
    }
    fin_q($pdo, "UPDATE {$c['tabela']} SET pago = ?, subtotal = ?, valor_restante = ? WHERE id = ?", [$status, $subtotal, $restante, $id]);
    return $status;
}

// Mantém o(s) título(s) gerados por um documento (romaneio, baldeio...) alinhados com ele sem
// perder pagamentos. $where/$params localizam os títulos do documento; $dados são os campos do título.
// - nenhum título: cria um
// - um título: atualiza e recalcula status a partir do que já foi pago
// - vários (foi parcelado): se o total não mudou só atualiza dados gerais; se mudou e nada foi pago,
//   volta a ser um título único; se mudou e há parcelas pagas, recusa com explicação
// - valor zero: exclui (estornando o que houver)
function fin_sincronizar_titulo($pdo, $tipo, $where, array $params, array $dados)
{
    $c = fin_cfg($tipo);
    $titulos = fin_q($pdo, "SELECT * FROM {$c['tabela']} WHERE $where ORDER BY id", $params)->fetchAll(PDO::FETCH_ASSOC);
    $valor = round((float) $dados['valor'], 2);

    $inserir = function () use ($pdo, $c, $dados) {
        $dados += ['pago' => 'Não', 'usuario_pgto' => 0, 'subtotal' => $dados['valor'], 'hora' => date('H:i:s'), 'frequencia' => 0];
        $sets = implode(', ', array_map(fn($k) => "`$k` = :$k", array_keys($dados)));
        $p = [];
        foreach ($dados as $k => $v) $p[":$k"] = $v;
        fin_q($pdo, "INSERT INTO {$c['tabela']} SET $sets", $p);
        return (int) $pdo->lastInsertId();
    };

    if ($valor <= 0) {
        foreach ($titulos as $t) fin_excluir_titulo($pdo, $tipo, $t['id'], 'documento de origem zerado');
        return null;
    }
    if (!$titulos) return $inserir();

    $gerais = $dados;
    unset($gerais['valor'], $gerais['subtotal'], $gerais['descricao'], $gerais['vencimento'], $gerais['usuario_lanc'], $gerais['pago'], $gerais['usuario_pgto']);

    if (count($titulos) == 1) {
        $t = $titulos[0];
        $upd = $gerais + ['valor' => $valor, 'vencimento' => $dados['vencimento'] ?? $t['vencimento']];
        if ($t['pago'] === 'Não') $upd['subtotal'] = $valor;
        $sets = implode(', ', array_map(fn($k) => "`$k` = :$k", array_keys($upd)));
        $p = [':id' => $t['id']];
        foreach ($upd as $k => $v) $p[":$k"] = $v;
        fin_q($pdo, "UPDATE {$c['tabela']} SET $sets WHERE id = :id", $p);
        if (round($t['valor'], 2) != $valor) {
            fin_recalcular_titulo($pdo, $tipo, $t['id']);
            fin_log($pdo, 'alterar_valor', $c['tabela'], $t['id'], '"' . $t['descricao'] . '" mudou de ' . fin_moeda($t['valor']) . ' para ' . fin_moeda($valor) . ' pelo documento de origem');
        }
        return (int) $t['id'];
    }

    $soma = round(array_sum(array_column($titulos, 'valor')), 2);
    if ($soma == $valor) {
        if ($gerais) {
            $sets = implode(', ', array_map(fn($k) => "`$k` = :$k", array_keys($gerais)));
            foreach ($titulos as $t) {
                $p = [':id' => $t['id']];
                foreach ($gerais as $k => $v) $p[":$k"] = $v;
                fin_q($pdo, "UPDATE {$c['tabela']} SET $sets WHERE id = :id", $p);
            }
        }
        return (int) $titulos[0]['id'];
    }
    foreach ($titulos as $t) {
        if ($t['pago'] !== 'Não' || fin_pagamentos($pdo, $tipo, $t['id'])) {
            throw new FinErro('O valor do documento mudou de ' . fin_moeda($soma) . ' para ' . fin_moeda($valor) . ', mas o título foi parcelado e já tem parcela paga. Estorne as parcelas pagas no financeiro antes de alterar o valor.');
        }
    }
    foreach ($titulos as $t) fin_excluir_titulo($pdo, $tipo, $t['id'], 'reagrupado pelo documento de origem');
    return $inserir();
}

// Lança um título já pago na criação (conta avulsa paga no ato, entrada de venda/compra à vista)
function fin_titulo_pago_na_criacao($pdo, $tipo, $id, $data, $forma, $banco = 0, $operacao = '')
{
    $t = fin_titulo($pdo, $tipo, $id);
    if (!$banco) $banco = (int) (fin_forma($pdo, $forma)['banco_padrao'] ?? 0);
    if (!$banco) {
        throw new FinErro('Informe a conta bancária em que o valor ' . ($tipo === 'receber' ? 'entrou' : 'saiu') .
            ' (ou defina uma conta padrão para a forma de pagamento em Cadastros > Formas de Pagamento).');
    }
    // O título nasce em aberto e a baixa registra o pagamento + extrato
    $c = fin_cfg($tipo);
    fin_q($pdo, "UPDATE {$c['tabela']} SET pago = 'Não' WHERE id = ?", [$id]);
    return fin_baixar($pdo, $tipo, $id, [['valor' => $t['valor'], 'data' => $data, 'forma' => $forma, 'banco' => $banco, 'operacao' => $operacao]], []);
}

function fin_parcelar($pdo, $tipo, $id, $qtd, $frequencia)
{
    $c = fin_cfg($tipo);
    $qtd = (int) $qtd;
    $frequencia = (int) $frequencia;
    if ($qtd < 2 || $qtd > 360) throw new FinErro('Informe de 2 a 360 parcelas.');
    if ($frequencia <= 0) throw new FinErro('Informe a frequência das parcelas.');
    return fin_transacao($pdo, function () use ($pdo, $tipo, $c, $id, $qtd, $frequencia) {
        $t = fin_titulo($pdo, $tipo, $id);
        if (fin_pagamentos($pdo, $tipo, $id) || $t['pago'] !== 'Não') throw new FinErro('Só é possível parcelar um título sem pagamentos. Estorne a baixa primeiro.');
        fin_validar_periodo($pdo, $t['data_lanc'], 'parcelar');

        $centavos = (int) round($t['valor'] * 100);
        $base = intdiv($centavos, $qtd);
        $sobra = $centavos - $base * $qtd;
        $ignorar = ['id', 'valor', 'subtotal', 'vencimento', 'descricao', 'valor_restante', 'data_pgto', 'recorrencia_gerada', 'hash',
            'multa', 'juros', 'desconto', 'taxa', 'banco'];
        $ids = [];
        for ($i = 1; $i <= $qtd; $i++) {
            $valor = ($base + ($i == $qtd ? $sobra : 0)) / 100;
            $venc = $i == 1 ? $t['vencimento'] : fin_proximo_vencimento_n($t['vencimento'], $frequencia, $i - 1);
            $novo = [];
            foreach ($t as $k => $v) if (!in_array($k, $ignorar, true)) $novo[$k] = $v;
            $novo['descricao'] = mb_substr($t['descricao'] . " - Parcela $i/$qtd", 0, 100);
            $novo['valor'] = $valor;
            $novo['subtotal'] = $valor;
            $novo['vencimento'] = $venc;
            $novo['pago'] = 'Não';
            $novo['frequencia'] = 0;
            $sets = implode(', ', array_map(fn($k) => "`$k` = :$k", array_keys($novo)));
            $p = [];
            foreach ($novo as $k => $v) $p[":$k"] = $v;
            fin_q($pdo, "INSERT INTO {$c['tabela']} SET $sets", $p);
            $ids[] = (int) $pdo->lastInsertId();
        }
        fin_q($pdo, "DELETE FROM {$c['tabela']} WHERE id = ?", [$id]);
        fin_log($pdo, 'parcelar', $c['tabela'], $id, 'Parcelou "' . $t['descricao'] . '" (' . fin_moeda($t['valor']) . ") em $qtd parcelas", ['novos_ids' => $ids]);
        return $ids;
    });
}

function fin_proximo_vencimento_n($data, $frequencia, $n)
{
    for ($k = 0; $k < $n; $k++) $data = fin_proximo_vencimento($data, $frequencia);
    return $data;
}

// ---------------------------------------------------------------------
// Adiantamentos (crédito de cliente / adiantamento a fornecedor)
// ---------------------------------------------------------------------

function fin_adiantamento_criar($pdo, $tipo, $pessoa, $valor, $data, $id_banco, $forma, $descricao)
{
    if (!in_array($tipo, ['cliente', 'fornecedor'])) throw new FinErro('Tipo inválido.');
    $valor = round((float) $valor, 2);
    if ($valor <= 0) throw new FinErro('Informe o valor.');
    if (!$pessoa) throw new FinErro('Selecione o ' . $tipo . '.');
    return fin_transacao($pdo, function () use ($pdo, $tipo, $pessoa, $valor, $data, $id_banco, $forma, $descricao) {
        fin_q($pdo, "INSERT INTO fin_adiantamentos (tipo, pessoa, descricao, valor, saldo, data, id_banco, forma_pgto, usuario, data_lanc) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
            [$tipo, $pessoa, $descricao, $valor, $valor, $data, $id_banco, $forma ?: null, fin_usuario()]);
        $id = (int) $pdo->lastInsertId();
        $nome = $tipo == 'cliente'
            ? fin_q($pdo, "SELECT nome FROM clientes WHERE id = ?", [$pessoa])->fetchColumn()
            : fin_q($pdo, "SELECT nome_atacadista FROM fornecedores WHERE id = ?", [$pessoa])->fetchColumn();
        fin_lancar($pdo, ['id_banco' => $id_banco, 'data' => $data,
            'descricao' => ($tipo == 'cliente' ? 'Adiantamento recebido de ' : 'Adiantamento pago a ') . $nome . ($descricao ? " - $descricao" : ''),
            $tipo == 'cliente' ? 'credito' : 'debito' => $valor, 'origem' => 'adiantamento', 'id_origem' => $id]);
        fin_log($pdo, 'adiantamento', 'adiantamentos', $id, "Adiantamento de $tipo $nome: " . fin_moeda($valor));
        return $id;
    });
}

function fin_adiantamento_excluir($pdo, $id)
{
    return fin_transacao($pdo, function () use ($pdo, $id) {
        $a = fin_q($pdo, "SELECT * FROM fin_adiantamentos WHERE id = ? FOR UPDATE", [$id])->fetch(PDO::FETCH_ASSOC);
        if (!$a) throw new FinErro('Adiantamento não encontrado.');
        if (round($a['saldo'], 2) != round($a['valor'], 2)) throw new FinErro('Este adiantamento já foi usado em baixas. Estorne essas baixas antes de excluí-lo.');
        fin_remover_lancamentos($pdo, 'adiantamento', $id);
        fin_q($pdo, "DELETE FROM fin_adiantamentos WHERE id = ?", [$id]);
        fin_log($pdo, 'excluir_adiantamento', 'adiantamentos', $id, 'Excluiu adiantamento de ' . fin_moeda($a['valor']), $a);
    });
}

function fin_adiantamentos_disponiveis($pdo, $tipo, $pessoa)
{
    return fin_q($pdo, "SELECT * FROM fin_adiantamentos WHERE tipo = ? AND pessoa = ? AND saldo > 0 ORDER BY data", [$tipo, $pessoa])->fetchAll(PDO::FETCH_ASSOC);
}

// ---------------------------------------------------------------------
// Reprocessamento: recebimentos antigos que nunca entraram no extrato
// ---------------------------------------------------------------------

function fin_recebimentos_fora_do_extrato($pdo)
{
    return fin_q($pdo, "SELECT rp.*, r.descricao, r.cliente, r.categoria_receber, r.centro_custo
        FROM receber_pagamentos rp JOIN receber r ON r.id = rp.id_receber
        JOIN bancos b ON b.id = rp.banco
        WHERE rp.id_adiantamento IS NULL AND rp.valor > 0
          AND NOT EXISTS (SELECT 1 FROM linha_bancos l WHERE l.origem = 'receber_pgto' AND l.id_origem = rp.id)
        ORDER BY rp.data_pgto, rp.id")->fetchAll(PDO::FETCH_ASSOC);
}

function fin_reprocessar_recebimentos($pdo)
{
    require_once __DIR__ . '/financeiro_saneamento.php';
    $r = fin_transacao($pdo, fn() => san_lancar_recebimentos($pdo));
    return ['quantidade' => $r['lancados'] + $r['vinculados'], 'total' => $r['total']];
}

function fin_reprocessar_recebimentos_antigo($pdo)
{
    return fin_transacao($pdo, function () use ($pdo) {
        $lista = fin_recebimentos_fora_do_extrato($pdo);
        $total = 0;
        $bloqueio = fin_data_bloqueio($pdo);
        $n = 0;
        foreach ($lista as $rp) {
            if ($bloqueio && $rp['data_pgto'] <= $bloqueio) continue;
            if (!fin_data_valida((string) $rp['data_pgto'])) continue;
            $nome = fin_q($pdo, "SELECT nome FROM clientes WHERE id = ?", [(int) $rp['cliente']])->fetchColumn();
            fin_lancar($pdo, ['id_banco' => $rp['banco'], 'data' => $rp['data_pgto'],
                'descricao' => 'Receb. ' . $rp['descricao'] . ($nome ? " - $nome" : ''), 'credito' => $rp['valor'],
                'origem' => 'receber_pgto', 'id_origem' => $rp['id'], 'documento' => $rp['numero_operacao'],
                'categoria_receber' => $rp['categoria_receber'], 'centro_custo' => $rp['centro_custo']]);
            $total += $rp['valor'];
            $n++;
        }
        fin_log($pdo, 'reprocessar', 'linha_bancos', null, "Lançou no extrato $n recebimentos antigos que estavam fora dele: " . fin_moeda($total));
        return ['quantidade' => $n, 'total' => $total];
    });
}

// ---------------------------------------------------------------------
// Conciliação bancária (OFX)
// ---------------------------------------------------------------------

function fin_ofx_parse($conteudo)
{
    $conteudo = str_replace(["\r\n", "\r"], "\n", $conteudo);
    if (!mb_check_encoding($conteudo, 'UTF-8')) $conteudo = mb_convert_encoding($conteudo, 'UTF-8', 'ISO-8859-1');
    if (!preg_match_all('/<STMTTRN>(.*?)(<\/STMTTRN>|(?=<STMTTRN>)|(?=<\/BANKTRANLIST>))/si', $conteudo, $m)) return [];
    $campo = function ($bloco, $tag) {
        return preg_match('/<' . $tag . '>([^<\n]*)/i', $bloco, $x) ? trim(html_entity_decode($x[1], ENT_QUOTES, 'UTF-8')) : '';
    };
    $lista = [];
    foreach ($m[1] as $bloco) {
        $dt = $campo($bloco, 'DTPOSTED');
        $valor = str_replace(',', '.', $campo($bloco, 'TRNAMT'));
        if (strlen($dt) < 8 || !is_numeric($valor)) continue;
        $data = substr($dt, 0, 4) . '-' . substr($dt, 4, 2) . '-' . substr($dt, 6, 2);
        $memo = $campo($bloco, 'MEMO');
        $name = $campo($bloco, 'NAME');
        $doc = $campo($bloco, 'CHECKNUM') ?: $campo($bloco, 'REFNUM');
        $fitid = $campo($bloco, 'FITID') ?: md5($data . $valor . $memo . $doc);
        $lista[] = ['fitid' => $fitid, 'data' => $data, 'valor' => round((float) $valor, 2),
            'descricao' => trim($name && stripos($memo, $name) === false ? "$name $memo" : ($memo ?: $name)), 'documento' => $doc];
    }
    return $lista;
}

function fin_importar_ofx($pdo, $id_banco, $conteudo, $arquivo)
{
    if (!fin_banco_existe($pdo, $id_banco)) throw new FinErro('Selecione a conta bancária do extrato.');
    $lista = fin_ofx_parse($conteudo);
    if (!$lista) throw new FinErro('Nenhum lançamento encontrado no arquivo. Confira se é um extrato OFX exportado pelo banco.');
    return fin_transacao($pdo, function () use ($pdo, $id_banco, $lista, $arquivo) {
        $novos = 0;
        $ins = $pdo->prepare("INSERT IGNORE INTO extrato_importado (id_banco, fitid, data, valor, descricao, documento, arquivo, data_importacao, usuario) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)");
        foreach ($lista as $l) {
            $ins->execute([$id_banco, $l['fitid'], $l['data'], $l['valor'], mb_substr($l['descricao'], 0, 255), mb_substr($l['documento'], 0, 60), $arquivo, fin_usuario()]);
            $novos += $ins->rowCount();
        }
        $auto = fin_conciliar_automatico($pdo, $id_banco);
        fin_log($pdo, 'importar_ofx', 'extrato_importado', $id_banco, "Importou $arquivo: " . count($lista) . " lançamentos ($novos novos, $auto conciliados automaticamente)");
        return ['lidos' => count($lista), 'novos' => $novos, 'conciliados' => $auto];
    });
}

// Casa cada linha importada pendente com uma linha do sistema da mesma conta, mesmo valor e
// mesmo sentido, com data até 3 dias de diferença. Só concilia quando o melhor par é único.
function fin_conciliar_automatico($pdo, $id_banco)
{
    $pend = fin_q($pdo, "SELECT * FROM extrato_importado WHERE id_banco = ? AND status = 'pendente' ORDER BY data, id", [$id_banco])->fetchAll(PDO::FETCH_ASSOC);
    $n = 0;
    foreach ($pend as $e) {
        $col = $e['valor'] >= 0 ? 'credito' : 'debito';
        $cand = fin_q($pdo, "SELECT id, ABS(DATEDIFF(DATE(data), ?)) dist FROM linha_bancos
            WHERE id_banco = ? AND conciliado = 0 AND $col = ? AND DATE(data) BETWEEN DATE_SUB(?, INTERVAL 3 DAY) AND DATE_ADD(?, INTERVAL 3 DAY)
            ORDER BY dist, id", [$e['data'], $id_banco, abs($e['valor']), $e['data'], $e['data']])->fetchAll(PDO::FETCH_ASSOC);
        if (!$cand) continue;
        if (count($cand) > 1 && $cand[0]['dist'] == $cand[1]['dist']) continue;
        fin_vincular_extrato($pdo, $e['id'], $cand[0]['id'], false);
        $n++;
    }
    return $n;
}

function fin_vincular_extrato($pdo, $id_extrato, $id_linha, $registrar = true)
{
    $e = fin_q($pdo, "SELECT * FROM extrato_importado WHERE id = ?", [$id_extrato])->fetch(PDO::FETCH_ASSOC);
    $l = fin_q($pdo, "SELECT * FROM linha_bancos WHERE id = ?", [$id_linha])->fetch(PDO::FETCH_ASSOC);
    if (!$e || !$l) throw new FinErro('Lançamento não encontrado.');
    if ($e['id_banco'] != $l['id_banco']) throw new FinErro('Os lançamentos são de contas diferentes.');
    if ($l['conciliado']) throw new FinErro('O lançamento do sistema já está conciliado.');
    $valor_l = round($l['credito'] - $l['debito'], 2);
    if ($valor_l != round($e['valor'], 2)) throw new FinErro('Os valores não batem: banco ' . fin_moeda($e['valor']) . ' × sistema ' . fin_moeda($valor_l) . '.');
    fin_q($pdo, "UPDATE extrato_importado SET id_linha_banco = ?, status = 'conciliado' WHERE id = ?", [$id_linha, $id_extrato]);
    fin_q($pdo, "UPDATE linha_bancos SET conciliado = 1, data_conciliacao = NOW(), usuario_conciliacao = ? WHERE id = ?", [fin_usuario(), $id_linha]);
    if ($registrar) fin_log($pdo, 'conciliar', 'linha_bancos', $id_linha, 'Conciliou "' . $l['descricao'] . '" com o extrato do banco (' . fin_moeda($e['valor']) . ' em ' . fin_data_br($e['data']) . ')');
}

// Marca/desmarca conciliação manual de uma linha (sem OFX: conferência visual com o extrato em papel/app)
function fin_marcar_conciliado($pdo, $id_linha, $conciliado)
{
    $l = fin_q($pdo, "SELECT * FROM linha_bancos WHERE id = ?", [$id_linha])->fetch(PDO::FETCH_ASSOC);
    if (!$l) throw new FinErro('Lançamento não encontrado.');
    if (!$conciliado) {
        fin_q($pdo, "UPDATE extrato_importado SET id_linha_banco = NULL, status = 'pendente' WHERE id_linha_banco = ?", [$id_linha]);
    }
    fin_q($pdo, "UPDATE linha_bancos SET conciliado = ?, data_conciliacao = ?, usuario_conciliacao = ? WHERE id = ?",
        [$conciliado ? 1 : 0, $conciliado ? date('Y-m-d H:i:s') : null, $conciliado ? fin_usuario() : null, $id_linha]);
    fin_log($pdo, $conciliado ? 'conciliar' : 'desconciliar', 'linha_bancos', $id_linha, ($conciliado ? 'Conciliou' : 'Desconciliou') . ' "' . $l['descricao'] . '"');
}

// Cria no sistema um lançamento que só existe no banco (tarifa, rendimento, PIX não identificado...) e já concilia
function fin_lancar_do_extrato($pdo, $id_extrato, $descricao, $categoria_receber, $categoria_pagar, $centro_custo)
{
    return fin_transacao($pdo, function () use ($pdo, $id_extrato, $descricao, $categoria_receber, $categoria_pagar, $centro_custo) {
        $e = fin_q($pdo, "SELECT * FROM extrato_importado WHERE id = ? AND status = 'pendente'", [$id_extrato])->fetch(PDO::FETCH_ASSOC);
        if (!$e) throw new FinErro('Lançamento do extrato não encontrado ou já tratado.');
        $id = fin_lancar($pdo, ['id_banco' => $e['id_banco'], 'data' => $e['data'], 'descricao' => $descricao ?: $e['descricao'],
            $e['valor'] >= 0 ? 'credito' : 'debito' => abs($e['valor']), 'origem' => 'manual', 'documento' => $e['documento'],
            'categoria_receber' => $e['valor'] >= 0 ? $categoria_receber : null, 'categoria_pagar' => $e['valor'] < 0 ? $categoria_pagar : null,
            'centro_custo' => $centro_custo]);
        fin_vincular_extrato($pdo, $id_extrato, $id, false);
        fin_log($pdo, 'lancar_do_extrato', 'linha_bancos', $id, 'Criou lançamento a partir do extrato: "' . ($descricao ?: $e['descricao']) . '" ' . fin_moeda($e['valor']));
        return $id;
    });
}

// ---------------------------------------------------------------------
// PIX (BR Code estático — "copia e cola")
// ---------------------------------------------------------------------

function fin_pix_payload($chave, $nome, $cidade, $valor = 0, $txid = '***')
{
    $limpa = function ($s, $max) {
        $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string) $s);
        $s = preg_replace('/[^A-Za-z0-9 .\-@]/', '', $s);
        return mb_substr(strtoupper(trim($s)), 0, $max);
    };
    $f = fn($id, $v) => $id . str_pad(strlen($v), 2, '0', STR_PAD_LEFT) . $v;
    $conta = $f('00', 'br.gov.bcb.pix') . $f('01', trim($chave));
    $p = $f('00', '01') . $f('26', $conta) . $f('52', '0000') . $f('53', '986');
    if ($valor > 0) $p .= $f('54', number_format($valor, 2, '.', ''));
    $p .= $f('58', 'BR') . $f('59', $limpa($nome, 25) ?: 'RECEBEDOR') . $f('60', $limpa($cidade, 15) ?: 'BRASIL');
    $p .= $f('62', $f('05', preg_replace('/[^A-Za-z0-9*]/', '', $txid) ?: '***'));
    $p .= '6304';
    $crc = 0xFFFF;
    for ($i = 0; $i < strlen($p); $i++) {
        $crc ^= ord($p[$i]) << 8;
        for ($b = 0; $b < 8; $b++) $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) : ($crc << 1);
        $crc &= 0xFFFF;
    }
    return $p . strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
}

// Upload do anexo de um título para images/contas. Retorna o nome do arquivo final.
function fin_upload_anexo($campo, $atual = 'sem-foto.png')
{
    if (empty($_FILES[$campo]['name']) || ($_FILES[$campo]['error'] ?? 1) !== UPLOAD_ERR_OK) return $atual ?: 'sem-foto.png';
    $ext = strtolower(pathinfo($_FILES[$campo]['name'], PATHINFO_EXTENSION));
    $permitidas = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'pdf', 'rar', 'zip', 'doc', 'docx', 'xls', 'xlsx', 'xlsm', 'xml', 'ofx'];
    if (!in_array($ext, $permitidas, true)) throw new FinErro('Extensão de arquivo não permitida!');
    $nome = preg_replace('/[^A-Za-z0-9._-]+/', '-', date('d-m-Y-H-i-s') . '-' . basename($_FILES[$campo]['name']));
    $destino = __DIR__ . '/../images/contas/' . $nome;
    if (!move_uploaded_file($_FILES[$campo]['tmp_name'], $destino)) throw new FinErro('Não foi possível salvar o arquivo.');
    if ($atual && $atual !== 'sem-foto.png' && $atual !== $nome && is_file(__DIR__ . '/../images/contas/' . basename($atual))) {
        @unlink(__DIR__ . '/../images/contas/' . basename($atual));
    }
    return $nome;
}

// Lê as linhas de pagamento postadas pelo modal de baixa
function fin_pagamentos_do_post()
{
    $v = $_POST['valor_baixar'] ?? [];
    $lista = [];
    foreach ($v as $i => $valor) {
        // Uso de adiantamento vem na própria forma de pagamento: "adi:<id>"
        $forma = (string) ($_POST['saida_baixar'][$i] ?? '');
        $adi = '';
        if (strpos($forma, 'adi:') === 0) {
            $adi = (int) substr($forma, 4);
            $forma = '';
        }
        $lista[] = [
            'valor' => $valor,
            'data' => $_POST['data_baixar'][$i] ?? '',
            'forma' => $forma,
            'banco' => $adi ? '' : ($_POST['banco_baixar'][$i] ?? ''),
            'operacao' => $_POST['numero_operacao'][$i] ?? '',
            'id_adiantamento' => $adi,
        ];
    }
    return $lista;
}

// ---------------------------------------------------------------------
// Respostas padrão dos endpoints
// ---------------------------------------------------------------------

// Executa a ação e responde texto puro (padrão das telas antigas: "Salvo com Sucesso" etc.)
function fin_responder(callable $fn)
{
    try {
        echo $fn();
    } catch (FinErro $e) {
        echo $e->getMessage();
    } catch (Throwable $e) {
        error_log('[financeiro] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        echo 'Erro inesperado: ' . $e->getMessage();
    }
}

function fin_json($dados)
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
}

// Opções de <select> a partir de uma consulta id/nome
function fin_options($pdo, $sql, $selecionado = null, $vazio = null)
{
    $html = $vazio !== null ? '<option value="">' . fin_e($vazio) . '</option>' : '';
    foreach (fin_q($pdo, $sql)->fetchAll(PDO::FETCH_NUM) as $r) {
        $html .= '<option value="' . fin_e($r[0]) . '"' . ((string) $selecionado === (string) $r[0] ? ' selected' : '') . '>' . fin_e($r[1]) . '</option>';
    }
    return $html;
}

if (isset($pdo) && $pdo instanceof PDO) {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    fin_esquema($pdo);
}
