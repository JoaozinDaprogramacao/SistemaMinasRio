<?php
require_once("../../../conexao.php");
@session_start();

$id_venda = @$_POST['id'];
$id_usuario = $_SESSION['id'];

if (empty($id_venda)) {
    echo 'ID da venda não fornecido.';
    exit();
}

try {
    // Limpa itens temporários anteriores do mesmo usuário
    $query_limpa = $pdo->prepare("DELETE FROM itens_venda WHERE id_venda = 0 AND id_usuario = :id_usuario");
    $query_limpa->execute([':id_usuario' => $id_usuario]);

    // Busca todos os itens da venda original
    $query_itens = $pdo->prepare("SELECT * FROM itens_venda WHERE id_venda_real = :id_venda");
    $query_itens->execute([':id_venda' => $id_venda]);
    $itens_venda = $query_itens->fetchAll(PDO::FETCH_ASSOC);

    if (count($itens_venda) == 0) {
        echo 'sucesso'; // Retorna sucesso para abrir a edição mesmo que vazia
        exit();
    }

    // Itera sobre cada item encontrado e insere uma cópia temporária
    foreach ($itens_venda as $item) {
        // ** CORREÇÃO AQUI **
        // Usando os nomes de coluna corretos da sua tabela
        $id_material = $item['material'];
        $quantidade = $item['quantidade'];
        $valor = $item['valor']; // << Usando 'valor' em vez de 'valor_unitario'
        $total = $item['total'];
        $funcionario = $item['funcionario'];
        $codigo = $item['codigo'];

        // ** CORREÇÃO AQUI **
        // Usando 'valor' na lista de colunas do INSERT
        $query_insere = $pdo->prepare(
            "INSERT INTO itens_venda (material, quantidade, valor, total, funcionario, codigo, id_usuario, id_venda) 
             VALUES (:material, :quantidade, :valor, :total, :funcionario, :codigo, :id_usuario, 0)"
        );
        
        $query_insere->execute([
            ':material' => $id_material,
            ':quantidade' => $quantidade,
            ':valor' => $valor, // << Usando a variável e o parâmetro corretos
            ':total' => $total,
            ':funcionario' => $funcionario,
            ':codigo' => $codigo,
            ':id_usuario' => $id_usuario
        ]);
    }

    echo 'sucesso';

} catch (Exception $e) {
    echo 'Ocorreu um erro ao processar a solicitação: ' . $e->getMessage();
}

?>