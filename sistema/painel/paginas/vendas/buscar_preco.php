<?php
require_once("../../../conexao.php");

$id_material = $_POST['id_material'] ?? 0;
$valor_encontrado = null; // Inicia a variável de preço como nula

if ($id_material > 0) {
    // --- ETAPA 1: BUSCAR TODOS OS DADOS DO MATERIAL ---
    // Em vez de buscar só uma coluna, pegamos tudo para ter mais flexibilidade.
    $query = $pdo->prepare("SELECT * FROM materiais WHERE id = :id");
    $query->execute([':id' => $id_material]);
    $material_data = $query->fetch(PDO::FETCH_ASSOC);

    // --- ETAPA 2: LÓGICA INTELIGENTE PARA ENCONTRAR O PREÇO ---
    if ($material_data) {
        // Lista de nomes de coluna mais comuns para o preço de venda
        $possiveis_nomes_coluna = ['preco_venda', 'valor', 'preco'];

        foreach ($possiveis_nomes_coluna as $nome_coluna) {
            // Verifica se a coluna com o nome da vez existe nos dados do material
            if (isset($material_data[$nome_coluna])) {
                $valor_encontrado = $material_data[$nome_coluna];
                break; // Encontrou o preço, então para o loop
            }
        }
    }
}

// --- ETAPA 3: RETORNAR O RESULTADO EM FORMATO JSON ---
// O JavaScript que chama este arquivo espera receber um JSON.
header('Content-Type: application/json');

// O JSON terá a chave 'valor', que conterá o preço encontrado ou null se não achar.
echo json_encode(['valor' => $valor_encontrado]);
?>