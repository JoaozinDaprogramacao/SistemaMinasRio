<?php
require_once("../../../conexao.php");

$id = $_POST['id'];

try {
    $query = $pdo->prepare("SELECT plano_pagamento, prazo_pagamento FROM fornecedores WHERE id = ?");
    $query->execute([$id]);
    $resultado = $query->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado) {
        // Garantir que os valores são numéricos
        $response = [
            'plano_pagamento' => (int)$resultado['plano_pagamento'],
            'prazo_pagamento' => (int)$resultado['prazo_pagamento']
        ];
    } else {
        $response = [
            'plano_pagamento' => 0,
            'prazo_pagamento' => 0
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => $e->getMessage()]);
}
?>