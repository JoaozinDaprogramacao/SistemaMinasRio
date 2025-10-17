<?php
$tabela = 'itens_venda';
require_once("../../../conexao.php");
@session_start();
$id_usuario = $_SESSION['id'];


// --- Lógica de totais (sem alterações) ---
$desconto = floatval(str_replace(',', '.', $_POST['desconto'] ?? '0'));
$troco = floatval(str_replace(',', '.', $_POST['troco'] ?? '0'));
$tipo_desconto = $_POST['tipo_desconto'] ?? '';
$frete = floatval(str_replace(',', '.', $_POST['frete'] ?? '0'));
$subtotal_itens = 0;
$ids_itens = [];

$query = $pdo->prepare("SELECT * FROM $tabela WHERE funcionario = :id_usuario AND id_venda = 0 ORDER BY id ASC");
$query->execute([':id_usuario' => $id_usuario]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);

if ($linhas > 0) {
    foreach ($res as $item) {
        $subtotal_itens += $item['total'];
        $ids_itens[] = $item['id'];
    }
}
$valor_desconto = ($tipo_desconto == '%') ? ($subtotal_itens * ($desconto / 100)) : $desconto;
$total_final = $subtotal_itens - $valor_desconto + $frete;
$total_troco = ($troco > 0 && $troco > $total_final) ? ($troco - $total_final) : 0;
?>

<style>
    /* SEUS ESTILOS CSS (sem alterações) */
    .lista-vendas-container { overflow-y: auto; max-height: 250px; width: 100%; scrollbar-width: thin; scrollbar-color: #888 #f1f1f1; border-top: 1px solid #eee; border-bottom: 1px solid #eee; padding-top: 5px; }
    .item-venda { display: flex; flex-direction: column; gap: 12px; padding: 12px 8px; border-bottom: 1px solid #f0f0f0; }
    .item-venda:last-child { border-bottom: none; }
    .item-header { display: flex; justify-content: space-between; align-items: flex-start; }
    .nome-produto { font-size: 14px; font-weight: 600; color: #333; padding-right: 10px; }
    .item-body { display: flex; justify-content: space-between; align-items: center; }
    .controles-produto { display: flex; align-items: center; gap: 10px; }
    .controle-qtd a { color: #555; text-decoration: none; }
    .controle-qtd big { font-size: 1.3em; }
    .controle-preco label { font-size: 11px; color: #666; }
    .input-preco-produto { width: 85px; height: 30px; font-size: 13px; padding: 5px; }
    .preco-total-item { font-size: 14px; font-weight: bold; color: #2c2c2c; }
    .btn-remover-item { color: #7d1107; text-decoration: none; font-size: 1.3em; padding: 0 5px; }
    .btn-remover-item:hover { color: #a9180b; }
    .rodape-venda { margin-top: 15px; padding-top: 10px; font-size: 14px; border-top: 1px solid #ccc; }
    .rodape-linha { display: flex; justify-content: space-between; padding: 3px 5px; }
    .rodape-linha span:last-child { font-weight: bold; }
    @media (min-width: 768px) {
        .item-venda { flex-direction: row; justify-content: space-between; align-items: center; padding: 10px 5px; }
        .item-header { flex-grow: 1; }
        .item-body { justify-content: flex-end; gap: 20px; }
        .item-body { order: 3; }
        .item-header .btn-remover-item { order: 4; padding-left: 15px; }
        .nome-produto { font-weight: 500; }
    }
</style>

<div class="lista-vendas-container">
<?php
if ($linhas > 0) {
    foreach ($res as $item) {
        $id = $item['id'];
        $material_id = $item['material'];
        $valor = $item['valor'];
        $quantidade = $item['quantidade'];
        $total = $item['total'];

        // ========================================================================//
        // ===== AQUI ESTÁ A CORREÇÃO FINAL E DEFENSIVA =========================== //
        // ========================================================================//
        // Buscamos o nome na tabela correta 'materiais'
        $query2 = $pdo->prepare("SELECT nome FROM materiais WHERE id = :material_id");
        $query2->execute([':material_id' => $material_id]);
        $nome_produto = $query2->fetchColumn();
        
        // Verificamos se a busca falhou (produto não encontrado/excluído)
        if ($nome_produto === false) {
            // Se falhou, definimos um nome substituto para exibir na tela
            $nome_produto = "[Material Excluído], material id: " . strval($material_id);
        }
        
        // Formatação
        $quantidadeF = (fmod($quantidade, 1) == 0) ? intval($quantidade) : $quantidade;
        $valorF = number_format($valor, 2, ',', '.');
        $totalF = number_format($total, 2, ',', '.');
?>
        <div class="item-venda">
            <div class="item-header">
                <div class="nome-produto"><?php echo htmlspecialchars($nome_produto); ?></div>
                <a href="#" onclick="confirmarExclusao(<?php echo $id; ?>)" class="btn-remover-item" title="Remover Item">
                    <i class="fa fa-times"></i>
                </a>
            </div>

            <div class="item-body">
                <div class="controles-produto">
                    <div class="controle-qtd">
                        <a href="#" onclick="diminuir(<?php echo $id; ?>, <?php echo $quantidade; ?>)"><big><i class="fa fa-minus-circle text-danger"></i></big></a>
                        <span style="margin: 0 8px; font-size: 14px;"><?php echo $quantidadeF; ?></span>
                        <a href="#" onclick="aumentar(<?php echo $id; ?>, <?php echo $quantidade; ?>)"><big><i class="fa fa-plus-circle text-success"></i></big></a>
                    </div>
                    <div class="controle-preco">
                        <label for="preco-produto-<?php echo $id; ?>">Unit.:</label>
                        <input type="text" id="preco-produto-<?php echo $id; ?>" 
                               class="form-control input-preco-produto" 
                               data-id="<?php echo $id; ?>" 
                               onkeyup="mascara(this, 'moeda')"
                               value="<?php echo $valorF; ?>">
                    </div>
                </div>
                <span class="preco-total-item">R$ <?php echo $totalF; ?></span>
            </div>
        </div>
<?php
    }
} else {
    echo '<p style="text-align:center; color:#888; padding: 20px 0;">Nenhum item adicionado.</p>';
}
?>
</div>

<?php
$total_finalF = number_format($total_final, 2, ',', '.');
$total_trocoF = number_format($total_troco, 2, ',', '.');
?>
<div class="rodape-venda">
    <div class="rodape-linha">
        <span>Itens:</span>
        <span><?php echo $linhas; ?></span>
    </div>
    <div class="rodape-linha" style="font-size: 16px;">
        <span>Subtotal:</span>
        <span>R$ <?php echo $total_finalF; ?></span>
    </div>
    <?php if ($troco > 0): ?>
    <div class="rodape-linha text-primary" style="margin-top: 5px;">
        <span>Troco:</span>
        <span>R$ <?php echo $total_trocoF; ?></span>
    </div>
    <?php endif; ?>
</div>

<?php
$ids_itens_json = json_encode(array_values($ids_itens));
?>

<script type="text/javascript">
    // --- Lógica JavaScript (sem alterações) ---
    var itens = <?= $linhas ?>;
    var ids_materiais = <?= $ids_itens_json ?>;
    $('#ids_itens').val(ids_materiais.join(','));
    $('#subtotal_venda').val('<?= $total_final ?>');
    if ($('#valor_pago').val() === '') {
        $('#valor_pago').val('<?= number_format($total_final, 2, ',', '.') ?>');
    }
    FormaPg();
    if (itens > 0) {
        $("#btn_limpar").show();
        $("#btn_venda").show();
    } else {
        $("#btn_limpar").hide();
        $("#btn_venda").hide();
    }
    function confirmarExclusao(id) {
        if (confirm("Deseja realmente remover este item?")) {
            excluirItem(id);
        }
    }
    function excluirItem(id) {
        $.ajax({
            url: 'paginas/' + pag + "/excluir-item.php", method: 'POST', data: { id },
            success: function(msg) { (msg.trim() == "Excluído com Sucesso") ? listarVendas() : alert(msg); }
        });
    }
    function diminuir(id, quantidade) {
        $.ajax({
            url: 'paginas/' + pag + "/diminuir.php", method: 'POST', data: { id, quantidade },
            success: function(msg) { (msg.trim() == "Excluído com Sucesso" || msg.trim() == "Atualizado com Sucesso") ? listarVendas() : alert(msg); }
        });
    }
    function aumentar(id, quantidade) {
        $.ajax({
            url: 'paginas/' + pag + "/aumentar.php", method: 'POST', data: { id, quantidade },
            success: function(msg) { (msg.trim() == "Atualizado com Sucesso") ? listarVendas() : alert(msg); }
        });
    }
    $('.input-preco-produto').on('blur', function() {
        var id = $(this).data('id');
        var preco = $(this).val().replace(/\./g, '').replace(',', '.').replace('R$ ', '');
        if (preco !== "" && !isNaN(preco)) {
            $.ajax({
                url: 'paginas/' + pag + "/atualizar-preco.php", method: 'POST', data: { id: id, preco: preco },
                success: function(res) { if (res.trim() === "Atualizado com Sucesso") { listarVendas(); } }
            });
        }
    });
    function mascara(o,f){ v_obj=o; v_fun=f; setTimeout("execmascara()",1); }
    function execmascara(){ v_obj.value=v_fun(v_obj.value); }
    function moeda(v){ v=v.replace(/\D/g,""); v=v.replace(/(\d)(\d{2})$/,"$1,$2"); v=v.replace(/(?=(\d{3})+(\D))\B/g,"."); return v; }
</script>