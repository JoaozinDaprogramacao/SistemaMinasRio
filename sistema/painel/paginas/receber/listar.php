<?php
@session_start();
$mostrar_registros = @$_SESSION['registros'];
$id_usuario = @$_SESSION['id'];
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['receber', 'clientes']);
require_once("../../verificar.php");
require_once(__DIR__ . "/../../funcoes/financeiro.php");

$filtro = $_POST['p1'] ?? '';
$dataInicial = fin_data_valida($_POST['p2'] ?? '') ? $_POST['p2'] : date('Y-m-01');
$dataFinal = fin_data_valida($_POST['p3'] ?? '') ? $_POST['p3'] : date('Y-m-t');
$tipo_data = in_array($_POST['p4'] ?? '', ['vencimento', 'data_lanc', 'data_pgto'], true) ? $_POST['p4'] : 'vencimento';
$cliente = (int) ($_POST['p5'] ?? 0);
$forma = (int) ($_POST['p6'] ?? 0);
$tipo_conta = $_POST['p7'] ?? '';
$categoria = (int) ($_POST['p8'] ?? 0);
$status = $_POST['p9'] ?? '';
$centro = (int) ($_POST['p10'] ?? 0);

$where = ["t.$tipo_data BETWEEN :ini AND :fim", "COALESCE(t.cancelada, '') <> 'Sim'"];
$p = [':ini' => $dataInicial, ':fim' => $dataFinal];
if ($mostrar_registros == 'Não') { $where[] = "t.usuario_lanc = :usu"; $p[':usu'] = $id_usuario; }
if ($cliente) { $where[] = "t.cliente = :cli"; $p[':cli'] = $cliente; }
if ($forma) { $where[] = "t.forma_pgto = :forma"; $p[':forma'] = $forma; }
if ($tipo_conta !== '') { $where[] = "pl.nome = :plano"; $p[':plano'] = $tipo_conta; }
if ($categoria) { $where[] = "t.categoria_receber = :cat"; $p[':cat'] = $categoria; }
if ($centro) { $where[] = "t.centro_custo = :cc"; $p[':cc'] = $centro; }
$pago = "COALESCE(NULLIF(t.pago, ''), 'Não')";
if ($status === 'aberto') $where[] = "$pago IN ('Não', 'Parcial')";
if ($status === 'pago') $where[] = "$pago = 'Sim'";
if ($status === 'parcial') $where[] = "$pago = 'Parcial'";
if ($status === 'vencido') $where[] = "$pago IN ('Não', 'Parcial') AND t.vencimento < curDate()";

$from = " FROM receber t
    LEFT JOIN romaneio_venda r ON t.id_romaneio = r.id
    LEFT JOIN planos_pgto pl ON r.plano_pgto = pl.id
    LEFT JOIN clientes c ON c.id = t.cliente
    LEFT JOIN categorias_receber cr ON cr.id = t.categoria_receber
    LEFT JOIN centros_custo cc ON cc.id = t.centro_custo
    WHERE " . implode(' AND ', $where);

$tot = fin_q($pdo, "SELECT
    SUM(CASE WHEN t.vencimento < curDate() AND $pago = 'Não' THEN t.valor
             WHEN t.vencimento < curDate() AND $pago = 'Parcial' THEN COALESCE(t.valor_restante, t.valor) ELSE 0 END) vencidas,
    SUM(CASE WHEN $pago = 'Sim' THEN t.subtotal WHEN $pago = 'Parcial' THEN t.subtotal - COALESCE(t.valor_restante, 0) ELSE 0 END) recebidas,
    SUM(CASE WHEN t.vencimento >= curDate() AND $pago = 'Não' THEN t.valor
             WHEN t.vencimento >= curDate() AND $pago = 'Parcial' THEN COALESCE(t.valor_restante, t.valor) ELSE 0 END) a_vencer,
    SUM(t.valor) total_bruto, SUM(COALESCE(t.desconto, 0)) desc_total,
    SUM(COALESCE(t.juros, 0) + COALESCE(t.multa, 0) + COALESCE(t.taxa, 0)) acres_total
    $from", $p)->fetch(PDO::FETCH_ASSOC);
$fmt = fn($v) => number_format((float) $v, 2, ',', '.');
$total_liquido = (float) $tot['total_bruto'] - (float) $tot['desc_total'] + (float) $tot['acres_total'];

$ordem = "ORDER BY CASE WHEN $pago IN ('Não','Parcial') AND t.vencimento < curDate() THEN 1
                        WHEN $pago IN ('Não','Parcial') THEN 2 ELSE 3 END, t.vencimento";
$where_filtro = '';
if ($filtro == 'Vencidas') $where_filtro = " AND t.vencimento < curDate() AND $pago IN ('Não','Parcial')";
if ($filtro == 'Recebidas') { $where_filtro = " AND $pago = 'Sim'"; $ordem = "ORDER BY t.data_pgto DESC"; }
if ($filtro == 'AVencer') $where_filtro = " AND t.vencimento >= curDate() AND $pago IN ('Não','Parcial')";

$res = fin_q($pdo, "SELECT t.*, $pago AS pago_n, pl.nome nome_plano, r.id id_venda, c.nome nome_cliente, c.forma_pagamento pgto_padrao,
    cr.nome nome_categoria, cc.nome nome_centro $from $where_filtro $ordem", $p)->fetchAll(PDO::FETCH_ASSOC);

$pode_estornar = fin_pode_estornar();
$pix_ativo = !empty(fin_config($pdo)['pix_chave']);
$hoje = date('Y-m-d');
$total_pago = 0;
$total_pendentes = 0;

if (!$res) {
    echo 'Nenhum Registro Encontrado!';
} else {
?>
<small>
<table class="table table-bordered text-nowrap border-bottom dt-responsive" id="tabela">
    <thead>
        <tr>
            <th class="text-center" width="5%">
                <div class="custom-checkbox custom-control">
                    <input type="checkbox" class="custom-control-input" id="selecionar-todos" onchange="selecionarTodos(this)" title="Selecionar todos (respeitando os filtros)">
                    <label for="selecionar-todos" class="custom-control-label mt-1 text-dark"></label>
                </div>
            </th>
            <th class="esc">Faturamento</th>
            <th>Descrição</th>
            <th class="esc">Categoria</th>
            <th>Valor</th>
            <th class="esc">Cliente</th>
            <th class="esc">Vencimento</th>
            <th class="esc">Recebimento</th>
            <th class="esc">Arquivo</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
<?php
    foreach ($res as $t) {
        $id = (int) $t['id'];
        $pg = $t['pago_n'];
        $valor = (float) $t['valor'];
        $restante = $t['valor_restante'] !== null ? (float) $t['valor_restante'] : $valor;
        if ($pg == 'Sim') {
            $classe = 'verde'; $total_pago += (float) $t['subtotal'];
            $valor_html = 'R$ ' . $fmt($t['subtotal']);
        } elseif ($pg == 'Parcial') {
            $classe = 'text-warning';
            $total_pago += max(0, (float) $t['subtotal'] - $restante);
            $total_pendentes += $restante;
            $valor_html = 'R$ ' . $fmt($valor) . '<br><span class="text-warning" style="font-size:11px">falta R$ ' . $fmt($restante) . '</span>';
        } else {
            $classe = 'text-danger'; $total_pendentes += $valor;
            $valor_html = 'R$ ' . $fmt($valor);
        }
        $arquivo = $t['arquivo'] ?: 'sem-foto.png';
        $ext = strtolower(pathinfo($arquivo, PATHINFO_EXTENSION));
        $thumb = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']) ? $arquivo : ($ext ? $ext . '.png' : 'sem-foto.png');
        $venc_cls = ($t['vencimento'] < $hoje && $pg != 'Sim') ? 'text-danger' : '';
        $de_romaneio = (int) $t['id_romaneio'] > 0;
        $de_venda = (int) $t['id_venda_real'] > 0;
        $manual = !$de_romaneio && !$de_venda;
        $desc_js = fin_js($t['descricao']);
        $cli_js = fin_js($t['nome_cliente'] ?? '');
        $em_aberto = $pg == 'Parcial' ? $restante : $valor;
?>
        <tr>
            <td align="center">
                <div class="custom-checkbox custom-control">
                    <input type="checkbox" class="custom-control-input" id="seletor-<?= $id ?>" data-pago="<?= $pg ?>" onchange="selecionar('<?= $id ?>')">
                    <label for="seletor-<?= $id ?>" class="custom-control-label mt-1 text-dark"></label>
                </div>
            </td>
            <td class="esc"><?= fin_data_br($t['data_lanc']) ?></td>
            <td><i class="fa fa-square <?= $classe ?> mr-1"></i> <?= fin_e($t['descricao']) ?>
                <?php if ($t['nome_centro']) { ?><br><small class="text-muted"><i class="fa fa-sitemap"></i> <?= fin_e($t['nome_centro']) ?></small><?php } ?></td>
            <td class="esc"><small class="badge bg-secondary text-white"><?= fin_e($t['nome_categoria'] ?? '') ?></small></td>
            <td><?= $valor_html ?></td>
            <td class="esc"><?= fin_e($t['nome_cliente'] ?? 'Sem Registro') ?></td>
            <td class="esc <?= $venc_cls ?>"><?= fin_data_br($t['vencimento']) ?></td>
            <td class="esc"><?= fin_data_br($t['data_pgto']) ?></td>
            <td class="esc"><a href="images/contas/<?= fin_e($arquivo) ?>" target="_blank"><img src="images/contas/<?= fin_e($thumb) ?>" width="25px"></a></td>
            <td>
                <big><a href="#" onclick="baixar('<?= $id ?>', '<?= $desc_js ?>', '<?= $valor ?>', '<?= fin_data_br($t['vencimento']) ?>', '<?= $cli_js ?>', '<?= (int) $t['id_venda'] ?>', '<?= (int) $t['pgto_padrao'] ?>', '<?= $pg ?>', '<?= fin_data_br($t['data_lanc']) ?>')" title="<?= $pg == 'Não' ? 'Receber' : 'Ver / Alterar Recebimento' ?>"><i class="fa fa-check-square text-success"></i></a></big>
                <?php if ($pg != 'Não' && $pode_estornar) { ?>
                    <big><a href="#" onclick="estornarBaixa('<?= $id ?>', '<?= $desc_js ?>')" title="Estornar recebimento"><i class="fa fa-undo text-warning"></i></a></big>
                <?php } ?>
                <?php if ($manual) { ?>
                    <big><a href="#" onclick="editar('<?= $id ?>')" title="Editar Conta"><i class="fa fa-edit text-primary"></i></a></big>
                <?php } ?>
                <?php if ($pg == 'Não') { ?>
                    <big><a href="#" onclick="parcelar('<?= $id ?>', '<?= $fmt($valor) ?>', '<?= $desc_js ?>')" title="Parcelar"><i class="fa fa-calendar-o text-secondary"></i></a></big>
                <?php } ?>
                <?php if ($pg != 'Sim') { ?>
                    <?php if ($pix_ativo) { ?>
                        <big><a href="#" onclick="cobrarPix('<?= $id ?>')" title="Cobrança PIX (QR Code / copia e cola)"><i class="fa fa-qrcode text-dark"></i></a></big>
                    <?php } ?>
                    <big><a href="#" onclick="cobrar('<?= $id ?>')" title="Enviar cobrança por WhatsApp"><i class="fa fa-whatsapp text-success"></i></a></big>
                <?php } ?>
                <?php if ($de_romaneio) { ?>
                    <big><a href="#" onclick="imprimir('<?= (int) $t['id_ref'] ?>')" title="Imprimir Romaneio"><i class="fa fa-file-pdf-o text-info"></i></a></big>
                <?php } ?>
                <?php if ($manual) { ?>
                    <div style="display:inline-block" class="dropdown">
                        <a href="#" data-bs-toggle="dropdown" title="Excluir"><i class="fa fa-trash text-danger"></i></a>
                        <div class="dropdown-menu"><div class="dropdown-item-text">Confirmar? <a href="#" onclick="excluir('<?= $id ?>')"><span class="text-danger">Sim</span></a></div></div>
                    </div>
                <?php } ?>
            </td>
        </tr>
<?php } ?>
    </tbody>
</table>
<div class="d-flex justify-content-between mt-3">
    <span class="ocultar_mobile">Filtro: <b><?= fin_e($tipo_data) ?></b></span>
    <p>Pendentes: <span class="text-danger">R$ <?= $fmt($total_pendentes) ?></span> | Recebido: <span class="text-success">R$ <?= $fmt($total_pago) ?></span></p>
</div>
</small>
<?php } ?>

<script>
    function imprimir(id) {
        window.open('rel/gerar_pdf_romaneio.php?id=' + id, '_blank');
    }
    $(document).ready(function () {
        if ($.fn.DataTable.isDataTable('#tabela')) $('#tabela').DataTable().destroy();
        if ($('#tabela').length) {
            $('#tabela').DataTable({ ordering: false, stateSave: true, language: { url: "//cdn.datatables.net/plug-ins/1.13.2/i18n/pt-BR.json" } });
        }
        $('#total_vencidas').text('R$ <?= $fmt($tot['vencidas']) ?>');
        $('#total_a_vencer').text('R$ <?= $fmt($tot['a_vencer']) ?>');
        $('#total_recebidas').text('R$ <?= $fmt($tot['recebidas']) ?>');
        $('#total_total').text('R$ <?= $fmt($tot['total_bruto']) ?>');
        $('#total_desconto').text('R$ <?= $fmt($tot['desc_total']) ?>');
        $('#total_acrescimo').text('R$ <?= $fmt($tot['acres_total']) ?>');
        $('#total_liquido').text('R$ <?= $fmt($total_liquido) ?>');
    });
</script>
