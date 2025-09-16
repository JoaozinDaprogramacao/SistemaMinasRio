<?php
require_once("../../../conexao.php");

// Define o cabeçalho da resposta como JSON
header('Content-Type: application/json');

// Pega o ID do funcionário de forma segura
$id = $_POST['id'];

// Prepara a query usando prepared statements para evitar SQL Injection
// Selecionamos campos distintos para cada tipo de lançamento, usando NULL para manter a estrutura da união
$query = $pdo->prepare("
    (SELECT
        id AS id_lancamento,
        'Gratificação' AS tipo,
        valor,
        data,
        descricao,
        NULL AS forma_pgto
    FROM gratificacoes
    WHERE id_funcionario = :id)

    UNION ALL

    (SELECT
        id AS id_lancamento,
        'Adiantamento' AS tipo,
        valor,
        data,
        NULL AS descricao,
        forma_pgto
    FROM adiantamentos
    WHERE id_funcionario = :id)

    ORDER BY data DESC, id_lancamento DESC
");

// Executa a query passando o ID de forma segura
$query->execute([':id' => $id]);

$resultado = $query->fetchAll(PDO::FETCH_ASSOC);

// Retorna o resultado como uma string JSON
echo json_encode($resultado);
?>