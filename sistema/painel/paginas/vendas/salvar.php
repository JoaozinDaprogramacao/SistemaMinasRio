<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$tabela = 'receber';
require_once("../../../conexao.php");

@session_start();
$id_usuario = $_SESSION['id'];

// Configurações de data
$data_atual = date('Y-m-d');

// Função para converter datas do formato DD/MM/YYYY para YYYY-MM-DD
function convertDateToMySQL($date) {
    if (!empty($date)) {
        $dateParts = explode('/', $date);
        if (count($dateParts) == 3) {
            return $dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0];
        }
    }
    return null; // Retorna NULL se a data for inválida
}

// Captura os dados do POST
$desconto = floatval(str_replace(',', '.', $_POST['desconto'] ?? '0'));
$cliente = $_POST['cliente'] ?? '';
$saida = $_POST['saida'] ?? '';
$data = $_POST['data2'] ?? $data_atual;
$frete = floatval(str_replace(',', '.', $_POST['frete'] ?? '0'));
$tipo_desconto = $_POST['tipo_desconto'] ?? '';
$subtotal_venda = floatval(str_replace(',', '.', $_POST['subtotal_venda'] ?? '0'));
$valor_restante = floatval(str_replace(',', '.', $_POST['valor_restante'] ?? '0'));
$valor_pago = floatval(str_replace(',', '.', $_POST['valor_pago'] ?? '0'));
$data_restante = $_POST['data_restante'] ?? $data_atual;
$forma_pgto2 = $_POST['forma_pgto2'] ?? "";
$ids_itens = $_POST['ids_itens'] ?? '';

$ids_itens = ltrim($ids_itens, ',');
$ids_itens = explode(',', $ids_itens);

foreach ($ids_itens as $id) {
    $stmt = $pdo->prepare('SELECT material, quantidade FROM itens_venda WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    if ($row) {
        $material = $row['material'];
        $quantidade = $row['quantidade'];

        $stmt = $pdo->prepare('SELECT estoque, nome FROM materiais WHERE id = :id');
        $stmt->execute(['id' => $material]);
        $row = $stmt->fetch();

        if ($row) {
            $estoque = $row['estoque'];
            if ($estoque < $quantidade) {
                $nome  = $row['nome'];
                echo "Erro: Estoque insuficiente para o item $nome. Estoque disponível: $estoque";
                exit();
            }
            else {
                $estoque = $row['estoque'];
                $estoque -= $quantidade;
                $stmt = $pdo->prepare('UPDATE materiais SET estoque = :estoque WHERE id = :id');
                $stmt->execute(['estoque' => $estoque, 'id' => $material]);
            }

    } else {
        echo "Nenhum resultado encontrado para o ID $id";
    }
}
}


// Converte as datas para o formato YYYY-MM-DD
$data = convertDateToMySQL($data);
$data_restante = convertDateToMySQL($data_restante);

// Validação adicional para garantir que as datas não estão vazias
if (empty($data) || $data == '1970-01-01') {
    $data = $data_atual; // Fallback
}

if (empty($data_restante) || $data_restante == '1970-01-01') {
    $data_restante = $data_atual; // Fallback para data atual
}

// Verifica se o valor restante tem uma forma de pagamento
if ($valor_restante > 0 && $forma_pgto2 == "" && $data_restante == $data_atual) {
    echo 'Você precisa selecionar uma forma de pagamento para o valor restante!';
    exit();
}

// Verifica se o desconto e o frete estão vazios
if ($desconto == "") $desconto = 0;
if ($frete == "") $frete = 0;

// Calcula o valor final da venda
if ($tipo_desconto == '%') {
    // Aplica desconto percentual
    $desconto_valor = ($subtotal_venda * $desconto) / 100;
} else {
    // Aplica desconto fixo
    $desconto_valor = $desconto;
}

$total_final = $subtotal_venda - $desconto_valor + $frete;

// Verifica se o total final é válido
if ($total_final <= 0) {
    echo 'O valor da Venda tem que ser maior que zero';
    exit();
}

// Define o status de pagamento como não pago por padrão
$pago = 'Não';
$data_pgto = null;
$usuario_pgto = 0;

// Verifica se o caixa está aberto
$query1 = $pdo->query("SELECT * FROM caixas WHERE operador = '$id_usuario' AND data_fechamento IS NULL ORDER BY id DESC LIMIT 1");
$res1 = $query1->fetchAll(PDO::FETCH_ASSOC);
$id_caixa = $res1[0]['id'] ?? 0;

try {
    foreach ($ids_itens as $id) {
        $stmt = $pdo->prepare('SELECT material, quantidade, valor FROM itens_venda WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if ($row) {
            $material = $row['material'];
            $quantidade = $row['quantidade'];
            $valor = $row['valor'];
            
            $stmt = $pdo->prepare("INSERT INTO detalhes_materiais (
                material_id,
                data,
                compra,
                venda,
                valor_compra,
                valor_venda,
                preco_unidade,
                saida_estoque,
                entrada_estoque,
                descricao
                ) VALUES (
                    :material_id,
                    :data,
                    :compra,
                    :venda,
                    :valor_compra,
                    :valor_venda,
                    :preco_unidade,
                    :saida_estoque,
                    :entrada_estoque,
                    :descricao
                )");    

            $stmt->execute([
                ':material_id' => $material,
                ':data' => $data,
                ':compra' => 0,
                ':venda' => $quantidade,
                ':valor_compra' => 0,
                ':valor_venda' => $quantidade * $valor,
                ':preco_unidade' => $valor,
                ':saida_estoque' => $quantidade,
                ':entrada_estoque' => 0,
                ':descricao' => $cliente
            ]);
        }
    }
} catch (PDOException $e) {
    echo 'Erro ao inserir os dados na tabela: ' . $e->getMessage();
    exit();
}
// Insere os dados na tabela `receber`
try {
    if ($valor_restante > 0) {
        $stmt = $pdo->prepare("INSERT INTO receber SET descricao = :descricao, valor = :valor, vencimento = :vencimento, data_lanc = CURDATE(), data_pgto = :data_pgto, usuario_lanc = :usuario_lanc, arquivo = 'sem-foto.png', pago = :pago, usuario_pgto = :usuario_pgto, cliente = :cliente, referencia = 'Venda', hora = CURTIME(), forma_pgto = :forma_pgto, desconto = :desconto, frete = :frete, tipo_desconto = :tipo_desconto, subtotal = :subtotal, valor_restante = :valor_restante, forma_pgto_restante = :forma_pgto_restante, data_restante = :data_restante, caixa = :caixa");
        $stmt->execute([
            ':descricao' => 'Nova Venda',
            ':valor' => $valor_pago,
            ':vencimento' => $data,
            ':data_pgto' => $data_pgto,
            ':usuario_lanc' => $id_usuario,
            ':pago' => $pago,
            ':usuario_pgto' => $usuario_pgto,
            ':cliente' => $cliente,
            ':forma_pgto' => $saida,
            ':desconto' => $desconto,
            ':frete' => $frete,
            ':tipo_desconto' => $tipo_desconto,
            ':subtotal' => $subtotal_venda,
            ':valor_restante' => $valor_restante,
            ':forma_pgto_restante' => $forma_pgto2,
            ':data_restante' => $data_restante,
            ':caixa' => $id_caixa
        ]);
        $id_venda = $pdo->lastInsertId();

        // Insere o valor restante
        $pago2 = (strtotime($data_restante) > strtotime($data_atual)) ? 'Não' : 'Sim';
        $data_pgto2 = ($pago2 == 'Sim') ? $data_restante : null;

        $stmt = $pdo->prepare("INSERT INTO receber SET descricao = :descricao, valor = :valor, vencimento = :vencimento, data_lanc = CURDATE(), data_pgto = :data_pgto, usuario_lanc = :usuario_lanc, arquivo = 'sem-foto.png', pago = :pago, usuario_pgto = :usuario_pgto, cliente = :cliente, referencia = 'Venda', hora = CURTIME(), forma_pgto = :forma_pgto, desconto = :desconto, frete = :frete, tipo_desconto = :tipo_desconto, subtotal = :subtotal, valor_restante = :valor_restante, forma_pgto_restante = :forma_pgto_restante, data_restante = :data_restante, id_ref = :id_ref, caixa = :caixa");
        $stmt->execute([
            ':descricao' => 'Nova Venda (Restante)',
            ':valor' => $valor_restante,
            ':vencimento' => $data_restante,
            ':data_pgto' => $data_pgto2,
            ':usuario_lanc' => $id_usuario,
            ':pago' => $pago2,
            ':usuario_pgto' => $usuario_pgto,
            ':cliente' => $cliente,
            ':forma_pgto' => $forma_pgto2,
            ':desconto' => $desconto,
            ':frete' => $frete,
            ':tipo_desconto' => $tipo_desconto,
            ':subtotal' => $subtotal_venda,
            ':valor_restante' => $valor_pago,
            ':forma_pgto_restante' => $saida,
            ':data_restante' => $data,
            ':id_ref' => $id_venda,
            ':caixa' => $id_caixa
        ]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO receber SET descricao = :descricao, valor = :valor, vencimento = :vencimento, data_lanc = CURDATE(), data_pgto = :data_pgto, usuario_lanc = :usuario_lanc, arquivo = 'sem-foto.png', pago = :pago, usuario_pgto = :usuario_pgto, cliente = :cliente, referencia = 'Venda', hora = CURTIME(), forma_pgto = :forma_pgto, desconto = :desconto, frete = :frete, tipo_desconto = :tipo_desconto, subtotal = :subtotal, caixa = :caixa");
        $stmt->execute([
            ':descricao' => 'Nova Venda',
            ':valor' => $subtotal_venda,
            ':vencimento' => $data,
            ':data_pgto' => $data_pgto,
            ':usuario_lanc' => $id_usuario,
            ':pago' => $pago,
            ':usuario_pgto' => $usuario_pgto,
            ':cliente' => $cliente,
            ':forma_pgto' => $saida,
            ':desconto' => $desconto,
            ':frete' => $frete,
            ':tipo_desconto' => $tipo_desconto,
            ':subtotal' => $subtotal_venda,
            ':caixa' => $id_caixa
        ]);
        $id_venda = $pdo->lastInsertId();
    }

    // Atualiza os itens da venda
    $pdo->query("UPDATE itens_venda SET id_venda = '$id_venda' WHERE id_venda = 0 AND funcionario = '$id_usuario'");

    // Lança a comissão do vendedor
    $query1 = $pdo->query("SELECT * FROM usuarios WHERE id = '$id_usuario'");
    $res1 = $query1->fetchAll(PDO::FETCH_ASSOC);
    $comissao = $res1[0]['comissao'] ?? 0;
    if ($comissao > 0) {
        $valor_da_venda = $subtotal_venda - $frete;
        $valor_da_comissao = $valor_da_venda * $comissao / 100;

        $stmt = $pdo->prepare("INSERT INTO pagar SET descricao = :descricao, funcionario = :funcionario, valor = :valor, vencimento = :vencimento, data_lanc = CURDATE(), frequencia = '0', arquivo = 'sem-foto.png', subtotal = :subtotal, usuario_lanc = :usuario_lanc, pago = 'Não', referencia = 'Comissão', hora = CURTIME(), id_ref = :id_ref");
        $stmt->execute([
            ':descricao' => 'Comissão Venda',
            ':funcionario' => $id_usuario,
            ':valor' => $valor_da_comissao,
            ':vencimento' => $data_final_mes,
            ':subtotal' => $valor_da_comissao,
            ':usuario_lanc' => $id_usuario,
            ':id_ref' => $id_venda
        ]);
    }

    echo 'Salvo com Sucesso-' . $id_venda;
} catch (PDOException $e) {
    echo 'Erro ao salvar: ' . $e->getMessage();
}