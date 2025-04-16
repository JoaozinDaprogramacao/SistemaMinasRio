<?php
require_once("../../../conexao.php");

$id_banco = $_POST['id_banco'] ?? '';

try {
    if(!empty($id_banco)) {
        $query = $pdo->query("SELECT 
            SUM(credito) as total_creditos,
            SUM(debito) as total_debitos,
            (SELECT saldo FROM linha_bancos WHERE id_banco = '$id_banco' ORDER BY id DESC LIMIT 1) as saldo_total
        FROM linha_bancos 
        WHERE id_banco = '$id_banco'");
    } else {
        $query = $pdo->query("SELECT 
            SUM(credito) as total_creditos,
            SUM(debito) as total_debitos,
            (SELECT saldo FROM linha_bancos ORDER BY id DESC LIMIT 1) as saldo_total
        FROM linha_bancos");
    }
    
    $res = $query->fetch(PDO::FETCH_ASSOC);

    $total_creditos = $res['total_creditos'] ?? 0;    
    $total_debitos = $res['total_debitos'] ?? 0; 
    $saldo_total = $res['saldo_total'] ?? 0;

    // Formatando os valores
    $total_creditosF = number_format($total_creditos, 2, ',', '.');
    $total_debitosF = number_format($total_debitos, 2, ',', '.');
    $saldo_totalF = number_format($saldo_total, 2, ',', '.');

    echo json_encode([
        'total_creditos' => $total_creditosF,
        'total_debitos' => $total_debitosF,
        'saldo_total' => $saldo_totalF
    ]);

} catch(PDOException $e) {
    echo json_encode([
        'total_creditos' => '0,00',
        'total_debitos' => '0,00',
        'saldo_total' => '0,00'
    ]);
}
?>