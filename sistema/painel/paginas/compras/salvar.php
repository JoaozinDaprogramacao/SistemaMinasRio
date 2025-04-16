<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$tabela = 'pagar';
require_once("../../../conexao.php");

@session_start();
$id_usuario = $_SESSION['id'];

// Define a data atual no início do arquivo
$data_atual = date('Y-m-d');

// Função para converter datas do formato DD/MM/YYYY para YYYY-MM-DD
function convertDateToMySQL($date, $default_date) {
    if (!empty($date)) {
        $dateParts = explode('/', $date);
        if (count($dateParts) == 3) {
            return $dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0];
        }
    }
    return $default_date;
}

try {
    // Log inicial
    error_log("Iniciando processamento da compra");
    
    // Verifica se há itens com valor zero
    $query = $pdo->query("SELECT COUNT(*) as total FROM itens_compra WHERE funcionario = '$id_usuario' AND id_compra = 0 AND (valor = 0 OR valor IS NULL)");
    $res = $query->fetch(PDO::FETCH_ASSOC);
    
    if ($res['total'] > 0) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Existem itens sem valor definido.'
        ]);
        exit();
    }

    // Verifica se o total da compra é zero
    $query = $pdo->query("SELECT SUM(total) as total FROM itens_compra WHERE funcionario = '$id_usuario' AND id_compra = 0");
    $res = $query->fetch(PDO::FETCH_ASSOC);
    if (floatval($res['total']) <= 0) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'O valor total da compra não pode ser zero!'
        ]);
        exit();
    }

    // Captura e trata os dados do POST
    $desconto = floatval(str_replace(',', '.', $_POST['desconto'] ?? '0'));
    $fornecedor = !empty($_POST['fornecedor']) ? intval($_POST['fornecedor']) : 0;
    $saida = $_POST['saida'] ?? '';
    $data = convertDateToMySQL($_POST['data2'] ?? '', $data_atual);
    $frete = floatval(str_replace(',', '.', $_POST['frete'] ?? '0'));
    $tipo_desconto = $_POST['tipo_desconto'] ?? '';
    $subtotal_compra = floatval(str_replace(',', '.', $_POST['subtotal_compra'] ?? '0'));
    $valor_restante = floatval(str_replace(',', '.', $_POST['valor_restante'] ?? '0'));
    $valor_pago = floatval(str_replace(',', '.', $_POST['valor_pago'] ?? '0'));
    $data_restante = convertDateToMySQL($_POST['data_restante'] ?? '', $data_atual);
    $forma_pgto2 = $_POST['forma_pgto2'] ?? '';

    // Verifica se há itens na compra
    $query = $pdo->query("SELECT COUNT(*) as total FROM itens_compra WHERE funcionario = '$id_usuario' AND id_compra = 0");
    $res = $query->fetch(PDO::FETCH_ASSOC);

    if ($res['total'] == 0) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Não há itens na compra!'
        ]);
        exit();
    }

    // Verifica se o pagamento é à vista ou a prazo
    $pago = ($valor_restante > 0) ? 'Não' : 'Sim';
    $data_pgto = ($pago == 'Sim') ? $data : null;
    $usuario_pgto = ($pago == 'Sim') ? $id_usuario : null;

    // Verifica se o caixa está aberto
    $query = $pdo->query("SELECT * FROM caixas WHERE operador = '$id_usuario' AND data_fechamento IS NULL ORDER BY id DESC LIMIT 1");
    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    $id_caixa = count($res) > 0 ? $res[0]['id'] : 0;

    // Calcula o total da compra
    $query = $pdo->query("SELECT SUM(total) as total FROM itens_compra WHERE funcionario = '$id_usuario' AND id_compra = 0");
    $res = $query->fetch(PDO::FETCH_ASSOC);
    $total_compra = floatval($res['total']);

    // Aplica desconto
    if ($tipo_desconto == '%') {
        if ($desconto > 0 && $total_compra > 0) {
            $desconto_valor = $total_compra * ($desconto / 100);
            $total_compra = $total_compra - $desconto_valor;
        }
    } else {
        $total_compra = $total_compra - floatval($desconto);
    }

    // Adiciona frete
    $total_compra = $total_compra + floatval($frete);

    // Log dos valores importantes
    error_log("Total da compra: " . $total_compra);
    error_log("Fornecedor: " . $fornecedor);
    error_log("Data: " . $data);
    error_log("ID Caixa: " . $id_caixa);

    // Antes do INSERT, verifique os valores
    error_log("Preparando para inserir na tabela pagar");
    error_log("Valor total: " . $total_compra);
    error_log("Pago: " . $pago);
    error_log("Data pgto: " . $data_pgto);

    try {
        // Execute o INSERT dentro de uma transação
        $pdo->beginTransaction();

        // Insere a compra com o valor total correto
        $stmt = $pdo->prepare("INSERT INTO pagar (
            descricao, valor, vencimento, data_lanc, data_pgto, 
            usuario_lanc, arquivo, pago, usuario_pgto, fornecedor, 
            referencia, hora, forma_pgto, desconto, frete, 
            tipo_desconto, subtotal, caixa, funcionario, frequencia
        ) VALUES (
            :descricao, :valor, :vencimento, CURDATE(), :data_pgto,
            :usuario_lanc, :arquivo, :pago, :usuario_pgto, :fornecedor,
            :referencia, CURTIME(), :forma_pgto, :desconto, :frete,
            :tipo_desconto, :subtotal, :caixa, :funcionario, :frequencia
        )");
        
        $stmt->execute([
            ':descricao' => 'Nova Compra',
            ':valor' => $total_compra,
            ':vencimento' => $data,
            ':data_pgto' => $data_pgto,
            ':usuario_lanc' => $id_usuario,
            ':arquivo' => 'sem-foto.png',
            ':pago' => $pago,
            ':usuario_pgto' => $usuario_pgto,
            ':fornecedor' => $fornecedor,
            ':referencia' => 'Compra',
            ':forma_pgto' => $saida,
            ':desconto' => $desconto,
            ':frete' => $frete,
            ':tipo_desconto' => $tipo_desconto,
            ':subtotal' => $subtotal_compra,
            ':caixa' => $id_caixa,
            ':funcionario' => $id_usuario,
            ':frequencia' => 0
        ]);

        $id_compra = $pdo->lastInsertId();
        
        // Atualiza os itens da compra
        $pdo->query("UPDATE itens_compra SET id_compra = '$id_compra' WHERE id_compra = 0 AND funcionario = '$id_usuario'");
        
        // Após inserir a compra, atualizar o estoque dos materiais
        $query = $pdo->query("SELECT ic.material, ic.quantidade, m.tem_estoque, m.estoque, m.compras 
                              FROM itens_compra ic 
                              JOIN materiais m ON ic.material = m.id 
                              WHERE ic.funcionario = '$id_usuario' AND ic.id_compra = '$id_compra'");
        $itens = $query->fetchAll(PDO::FETCH_ASSOC);

        foreach($itens as $item) {
            if($item['tem_estoque'] == 'Sim') {
                $novo_estoque = $item['estoque'] + $item['quantidade'];
                $novas_compras = $item['compras'] + $item['quantidade'];
                
                $pdo->query("UPDATE materiais SET 
                             estoque = '$novo_estoque', 
                             compras = '$novas_compras' 
                             WHERE id = '{$item['material']}'");
            }
        }

        // Commit a transação
        $pdo->commit();

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'mensagem' => 'Compra finalizada com sucesso!',
            'limpar_campos' => true
        ]);
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Erro na transação: " . $e->getMessage());
        
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Erro ao salvar a compra: ' . $e->getMessage()
        ]);
        exit();
    }

} catch (Exception $e) {
    error_log("Erro geral: " . $e->getMessage());
    
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Erro no processamento: ' . $e->getMessage()
    ]);
    exit();
}