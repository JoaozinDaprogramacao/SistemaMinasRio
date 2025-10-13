<?php 
require_once("../../../conexao.php");

// O ID do cliente a ser selecionado (vindo da edição)
$cliente_selecionado_id = @$_POST['valor'];

// Gera o <select> com o name e id corretos
// IMPORTANTE: Removido o onchange daqui, pois o JS principal cuidará disso.
echo '<select class="sel2" name="cliente" id="cliente" style="width:100%;">';

echo '<option value="">Selecione um Cliente</option>';

$query = $pdo->query("SELECT * FROM clientes order by nome asc");
$res = $query->fetchAll(PDO::FETCH_ASSOC);

for($i=0; $i < @count($res); $i++){
    $id_atual = $res[$i]['id'];
    $nome_atual = $res[$i]['nome'];
    $cpf_atual = $res[$i]['cpf'];

    // Adiciona o atributo 'selected' se o ID for o que queremos editar
    $selected = ($id_atual == $cliente_selecionado_id) ? 'selected' : '';

    echo '<option value="'.$id_atual.'" '.$selected.'>'.$nome_atual.' - '.$cpf_atual.'</option>';
}

echo '</select>';
?>