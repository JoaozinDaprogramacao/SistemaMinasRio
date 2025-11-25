<?php
// Inclua seu arquivo de conexão e configurações (ajuste o caminho se necessário)
require_once('../../../conexao.php'); 
@session_start();

// O ID do cliente é enviado via AJAX POST
$clienteId = @$_POST['cliente_id'];

if (empty($clienteId) || $clienteId == '0') {
    // Retorna placeholder se o cliente não for selecionado
    echo '<p class="text-secondary text-center">Selecione um Cliente válido.</p>';
    exit();
}

// 1. REQUISITO: ORDENAR POR ID DECRESCENTE (rc.id DESC)
// 2. REQUISITO: FILTRAR PELO CLIENTE (rc.cliente = :clienteId)
// ⚠️ ATENÇÃO: Corrigi 'rc.cliente_id' para 'rc.cliente', 
// que é o nome de coluna mais provável que linka o romaneio de compra ao cliente.
$query = $pdo->prepare("SELECT rc.*, f.nome_atacadista 
                        FROM romaneio_compra rc 
                        LEFT JOIN fornecedores f ON rc.fornecedor = f.id 
                        WHERE rc.cliente = :clienteId 
                        ORDER BY rc.id DESC");
$query->bindValue(':clienteId', $clienteId);

try {
    $query->execute();
    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    $linhas = @count($res);

    if ($linhas > 0) {
        foreach ($res as $row) {
            $data_formatada = date('d/m/Y', strtotime($row['data']));
            $total_formatado = number_format($row['total_liquido'], 2, ',', '.');
            
            // Esta é a estrutura HTML que será injetada no modal
            echo "<div class='romaneio-item' data-id='{$row['id']}' onclick='toggleRomaneio(this, {$row['id']})'>
                      <strong>Nº {$row['id']}</strong> - {$row['nome_atacadista']} <br>
                      Data: {$data_formatada} - Total: R$ {$total_formatado}
                  </div>";
        }
    } else {
        echo '<p class="text-secondary text-center">Nenhum romaneio de compra encontrado para este cliente.</p>';
    }
} catch (PDOException $e) {
    // Se o erro de coluna persistir, exiba uma mensagem informativa
    if (strpos($e->getMessage(), 'Unknown column') !== false) {
        echo '<p class="text-danger text-center">ERRO: Coluna de Cliente Incorreta. Verifique se a coluna na tabela `romaneio_compra` que se refere ao cliente é `cliente` ou altere a consulta no código PHP.</p>';
    } else {
        echo '<p class="text-danger text-center">Erro no banco de dados: ' . $e->getMessage() . '</p>';
    }
}
?>