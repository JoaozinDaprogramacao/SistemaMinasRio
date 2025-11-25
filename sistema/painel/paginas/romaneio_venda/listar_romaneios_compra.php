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
// 3. REQUISITO: FILTRAR SOMENTE OS NÃO USADOS (rc.usado = 0)
$query = $pdo->prepare("SELECT rc.*, f.nome_atacadista 
                        FROM romaneio_compra rc 
                        LEFT JOIN fornecedores f ON rc.fornecedor = f.id 
                        WHERE rc.cliente = :clienteId 
                        AND rc.usado = 0 
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
        echo '<p class="text-secondary text-center">Nenhum romaneio de compra DISPONÍVEL encontrado para este cliente.</p>';
    }
} catch (PDOException $e) {
    // Se o erro de coluna persistir, exiba uma mensagem informativa
    if (strpos($e->getMessage(), 'Unknown column') !== false) {
        // Modifique a mensagem para refletir a nova coluna 'usado'
        echo '<p class="text-danger text-center">ERRO: Verifique se as colunas `cliente` e `usado` existem na tabela `romaneio_compra`.</p>';
    } else {
        echo '<p class="text-danger text-center">Erro no banco de dados: ' . $e->getMessage() . '</p>';
    }
}
