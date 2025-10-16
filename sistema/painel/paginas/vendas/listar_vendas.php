<?php
$tabela = 'itens_venda';
require_once("../../../conexao.php");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
@session_start();
$id_usuario = $_SESSION['id'];

// Captura e formata os valores do POST
$desconto = floatval(str_replace(',', '.', $_POST['desconto'] ?? '0'));
$troco = floatval(str_replace(',', '.', $_POST['troco'] ?? '0'));
$tipo_desconto = $_POST['tipo_desconto'] ?? '';
$frete = floatval(str_replace(',', '.', $_POST['frete'] ?? '0'));

// Inicializa o subtotal dos itens
$subtotal_itens = 0;
$ids_itens = [];

// Busca os itens temporários do usuário e calcula o subtotal
$query = $pdo->prepare("SELECT * FROM $tabela WHERE funcionario = :id_usuario AND id_venda = 0 ORDER BY id ASC");
$query->execute([':id_usuario' => $id_usuario]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);

if ($linhas > 0) {
    foreach ($res as $item) {
        $subtotal_itens += $item['total'];
        $ids_itens[] = $item['id']; // Coleta os IDs dos itens
    }
}

// Calcula o valor do desconto
$valor_desconto = 0;
if ($tipo_desconto == '%') {
    $valor_desconto = $subtotal_itens * ($desconto / 100);
} else {
    $valor_desconto = $desconto;
}

// Calcula o total final da venda
$total_final = $subtotal_itens - $valor_desconto + $frete;
$total_troco = 0;

if ($troco > 0 && $troco > $total_final) {
    $total_troco = $troco - $total_final;
}
?>

<style>
    .lista-vendas-container {
        overflow-y: auto;
        max-height: 250px; /* Aumentei um pouco a altura */
        width: 100%;
        scrollbar-width: thin;
        scrollbar-color: #888 #f1f1f1;
        border-top: 1px solid #eee;
        border-bottom: 1px solid #eee;
        padding-top: 5px;
    }

    .item-venda {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 5px;
        border-bottom: 1px solid #f0f0f0;
    }
    .item-venda:last-child {
        border-bottom: none;
    }

    .item-detalhes {
        display: flex;
        flex-direction: column;
        gap: 8px; /* Espaço entre o nome e os controles */
    }

    .nome-produto {
        font-size: 14px;
        font-weight: 500;
        color: #333;
    }

    .controles-produto {
        display: flex;
        align-items: center;
        gap: 8px; /* Espaço entre os controles */
    }
    
    .controle-qtd a {
        color: #555;
        text-decoration: none;
    }

    .controle-qtd big {
        font-size: 1.2em;
    }
    
    .controle-qtd .text-danger:hover {
        color: #d9534f;
    }
    .controle-qtd .text-success:hover {
        color: #5cb85c;
    }

    .controle-preco label {
        font-size: 11px;
        color: #666;
    }

    .input-preco-produto {
        width: 90px;
        height: 28px;
        font-size: 13px;
        padding: 5px;
    }

    .item-acoes {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .preco-total-item {
        font-size: 14px;
        font-weight: bold;
        color: #2c2c2c;
        min-width: 70px;
        text-align: right;
    }

    .btn-remover-item {
        color: #7d1107;
        text-decoration: none;
        font-size: 1.1em;
    }
    .btn-remover-item:hover {
        color: #a9180b;
    }

    /* Estilo do Rodapé */
    .rodape-venda {
        margin-top: 15px;
        padding-top: 10px;
        font-size: 14px;
        border-top: 1px solid #ccc;
    }
    .rodape-linha {
        display: flex;
        justify-content: space-between;
        padding: 2px 5px;
    }
    .rodape-linha span:last-child {
        font-weight: bold;
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

        // Busca o nome do material
        $query2 = $pdo->prepare("SELECT nome FROM materiais WHERE id = :material_id");
        $query2->execute([':material_id' => $material_id]);
        $nome_produto = $query2->fetchColumn();
        
        // Formatação
        $quantidadeF = (fmod($quantidade, 1) == 0) ? intval($quantidade) : $quantidade;
        $valorF = number_format($valor, 2, ',', '.');
        $totalF = number_format($total, 2, ',', '.');
?>
        <div class="item-venda">
            <div class="item-detalhes">
                <div class="nome-produto"><?php echo $nome_produto; ?></div>
                <div class="controles-produto">
                    <div class="controle-qtd">
                        <a href="#" onclick="diminuir(<?php echo $id; ?>, <?php echo $quantidade; ?>)"><big><i class="fa fa-minus-circle text-danger"></i></big></a>
                        <span style="margin: 0 5px;"><?php echo $quantidadeF; ?></span>
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
            </div>

            <div class="item-acoes">
                <span class="preco-total-item">R$ <?php echo $totalF; ?></span>
                <a href="#" onclick="confirmarExclusao(<?php echo $id; ?>)" class="btn-remover-item" title="Remover Item">
                    <i class="fa fa-times"></i>
                </a>
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
    
    // NOVA FUNÇÃO PARA CONFIRMAR EXCLUSÃO
    function confirmarExclusao(id) {
        if (confirm("Deseja realmente remover este item?")) {
            excluirItem(id);
        }
    }

    function excluirItem(id) {
        $.ajax({
            url: 'paginas/' + pag + "/excluir-item.php",
            method: 'POST',
            data: { id },
            success: function(mensagem) {
                if (mensagem.trim() == "Excluído com Sucesso") {
                    listarVendas();
                } else {
                    alert(mensagem);
                }
            }
        });
    }

    function diminuir(id, quantidade) {
        $.ajax({
            url: 'paginas/' + pag + "/diminuir.php",
            method: 'POST',
            data: { id, quantidade },
            success: function(mensagem) {
                if (mensagem.trim() == "Excluído com Sucesso" || mensagem.trim() == "Atualizado com Sucesso") {
                    listarVendas();
                } else {
                    alert(mensagem);
                }
            }
        });
    }

    function aumentar(id, quantidade) {
        $.ajax({
            url: 'paginas/' + pag + "/aumentar.php",
            method: 'POST',
            data: { id, quantidade },
            success: function(mensagem) {
                if (mensagem.trim() == "Atualizado com Sucesso") {
                    listarVendas();
                } else {
                    alert(mensagem);
                }
            }
        });
    }

    $('.input-preco-produto').on('blur', function() {
        var id = $(this).data('id');
        var preco = $(this).val();
        
        preco = preco.replace(/\./g, '').replace(',', '.').replace('R$ ', '');
        
        if (preco !== "" && !isNaN(preco)) {
            $.ajax({
                url: 'paginas/' + pag + "/atualizar-preco.php",
                method: 'POST',
                data: { id: id, preco: preco },
                success: function(response) {
                    if (response.trim() === "Atualizado com Sucesso") {
                        listarVendas();
                    }
                }
            });
        }
    });

    // Funções de máscara permanecem as mesmas
    function mascara(o,f){
        v_obj=o;
        v_fun=f;
        setTimeout("execmascara()",1);
    }
    function execmascara(){
        v_obj.value=v_fun(v_obj.value);
    }
    function moeda(v){
        v=v.replace(/\D/g,"");
        v=v.replace(/(\d)(\d{2})$/,"$1,$2");
        v=v.replace(/(?=(\d{3})+(\D))\B/g,".");
        return v;
    }
</script>