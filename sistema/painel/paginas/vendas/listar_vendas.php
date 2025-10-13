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

// ========================================================== //
// ==================== HTML dos Itens ====================== //
// ========================================================== //
echo '<div style="overflow:auto; max-height:200px; width:100%; scrollbar-width: thin;">';
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
        
        // Formatação da quantidade (remove .00)
        $quantidadeF = (fmod($quantidade, 1) == 0) ? intval($quantidade) : $quantidade;
        $nome_produtoF = mb_strimwidth($nome_produto, 0, 24, "...");

        echo '<div class="row">';
        echo '<div class="col-md-9" style="margin-left:5px; margin-top:3px">';
        echo '<span style="font-size:13px;">';
        echo $quantidadeF . ' ' . $nome_produtoF;
        echo '</span><br>';
        echo '<div style="font-size:12px; color:#570a03; margin-top:0px; margin-left:0px">
        <a href="#" onclick="diminuir(' . $id . ', ' . $quantidade . ')"><big><i class="fa fa-minus-circle text-danger"></i></big></a>
        ' . $quantidadeF . '
        <a href="#" onclick="aumentar(' . $id . ', ' . $quantidade . ')"><big><i class="fa fa-plus-circle text-success"></i></big></a>';

        echo '<div class="dropdown head-dpdn2" style="position:absolute; top:0px; right:10px">
        <a title="Remover Item" href="#" class="dropdown" data-bs-toggle="dropdown" aria-expanded="false"><big><i class="fa fa-times" style="color:#7d1107"></i></big></a>
        <div class="dropdown-menu" style="margin-left:-50px;margin-top:-35px; background: #fcecd6">
            <div>
            <div class="notification_desc2" style="background: #fcecd6">
            <p style="font-size:12px; padding:10px">Remover Item? <a href="#" onclick="excluirItem(' . $id . ')"><span class="text-danger">Sim</span></a></p>
            </div>
            </div>
        </div>
        </div>';

        echo '<div style="margin-top:10px;">
        <label for="preco-produto-' . $id . '" style="font-size:12px;">Preço Unit.:</label>
        <input type="text" id="preco-produto-' . $id . '" 
               class="form-control input-preco-produto" 
               data-id="' . $id . '" 
               style="display:inline-block; width:100px; margin-left:5px;" 
               onkeyup="mascara(this, \'moeda\')"
               value="' . number_format($valor, 2, ',', '.') . '">
        </div>';

        echo '</div>'; // col-md-9
        echo '</div>'; // row
    }
}
echo '</div>';

// ========================================================== //
// ==================== HTML do Rodapé ====================== //
// ========================================================== //
$total_finalF = number_format($total_final, 2, ',', '.');
$total_trocoF = number_format($total_troco, 2, ',', '.');
echo '<div align="right" style="margin-top:10px; font-size:14px; border-top:1px solid #8f8f8f;" >';
echo '<br>';
echo '<span style="margin-right:40px;">Itens: <b>(' . $linhas . ')</b></span>';
echo '<span>Subtotal: </span>';
echo '<span style="font-weight:bold"> R$ ';
echo $total_finalF;
echo '</span>';
if ($troco > 0) {
    echo '<br><span>Troco: </span>';
    echo '<span style="font-weight:bold"> R$ ';
    echo $total_trocoF;
    echo '</span>';
}
echo '</div>';

$ids_itens_json = json_encode(array_values($ids_itens));
?>


<script type="text/javascript">
    var itens = <?= $linhas ?>;
    var ids_materiais = <?= $ids_itens_json ?>;

    // Atualiza os campos ocultos com os valores calculados
    $('#ids_itens').val(ids_materiais.join(','));
    $('#subtotal_venda').val('<?= $total_final ?>');
    
    // ----- AQUI ESTÁ A CORREÇÃO PRINCIPAL -----
    // Se o campo 'valor_pago' estiver vazio, preenche com o total.
    // Se ele já tiver um valor (vindo do modo de edição), não faz nada.
    if ($('#valor_pago').val() === '') {
        $('#valor_pago').val('<?= number_format($total_final, 2, ',', '.') ?>');
    }

    // Dispara a função FormaPg() para recalcular o valor restante e mostrar/ocultar a div
    FormaPg();
    
    // Controla a visibilidade dos botões
    if (itens > 0) {
        $("#btn_limpar").show();
        $("#btn_venda").show();
    } else {
        $("#btn_limpar").hide();
        $("#btn_venda").hide();
    }

    // As funções de excluir, diminuir, aumentar e atualizar preço continuam aqui
    // Elas não precisam de alteração.
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

    // Associa o evento de 'blur' (quando o usuário sai do campo) para atualizar o preço
    $('.input-preco-produto').on('blur', function() {
        var id = $(this).data('id');
        var preco = $(this).val();

        // Limpa a formatação de moeda antes de enviar para o PHP
        preco = preco.replace(/\./g, '').replace(',', '.').replace('R$ ', '');
        
        if (preco !== "" && !isNaN(preco)) {
            $.ajax({
                url: 'paginas/' + pag + "/atualizar-preco.php",
                method: 'POST',
                data: { id: id, preco: preco },
                success: function(response) {
                    if (response.trim() === "Atualizado com Sucesso") {
                        listarVendas(); // Recarrega a lista para atualizar os totais
                    }
                }
            });
        }
    });

    // Função de máscara de moeda (pode estar em um arquivo JS global)
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