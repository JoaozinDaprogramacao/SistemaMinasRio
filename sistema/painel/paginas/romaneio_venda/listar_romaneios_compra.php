<?php
require_once('../../../conexao.php');
@session_start();

$clienteId = @$_POST['cliente_id'];
// Recebe o ID do romaneio de compra que JÁ ESTÁ SALVO na venda (se houver)
$idCompraSalva = @$_POST['id_compra_salva'];

if (empty($clienteId) || $clienteId == '0') {
    echo '<p class="text-secondary text-center">Selecione um Cliente válido.</p>';
    exit();
}

try {
    $sql = "SELECT rc.*, f.nome_atacadista 
            FROM romaneio_compra rc 
            LEFT JOIN fornecedores f ON rc.fornecedor = f.id 
            WHERE rc.cliente = :clienteId";

    // --- A LÓGICA CORRIGIDA ---
    if (!empty($idCompraSalva)) {
        // Mostra os livres (usado=0) OU o específico que já é desta venda (id = idCompraSalva)
        $sql .= " AND (rc.usado = 0 OR rc.id = :idCompraSalva)";
    } else {
        $sql .= " AND rc.usado = 0";
    }

    $sql .= " ORDER BY rc.id DESC";

    $query = $pdo->prepare($sql);
    $query->bindValue(':clienteId', $clienteId);

    if (!empty($idCompraSalva)) {
        $query->bindValue(':idCompraSalva', $idCompraSalva);
    }

    $query->execute();
    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    $linhas = @count($res);

    if ($linhas > 0) {
        foreach ($res as $row) {
            $data_formatada = date('d/m/Y', strtotime($row['data']));
            $total_formatado = number_format($row['total_liquido'], 2, ',', '.');

            // Verifica se este é o item salvo para marcar visualmente
            $isRecovered = (!empty($idCompraSalva) && $row['id'] == $idCompraSalva) ? 'style="border-left: 3px solid #f0ad4e; background-color: #fffbf2;"' : '';

            echo "<div class='romaneio-item' data-id='{$row['id']}' onclick='toggleRomaneio(this, {$row['id']})' {$isRecovered}>
                    <strong>Nº {$row['id']}</strong> - {$row['nome_atacadista']} <br>
                    Data: {$data_formatada} - Total: R$ {$total_formatado}
                  </div>";
        }
    } else {
        echo '<p class="text-secondary text-center">Nenhum romaneio disponível.</p>';
    }
} catch (PDOException $e) {
    echo '<p class="text-danger">Erro: ' . $e->getMessage() . '</p>';
}
