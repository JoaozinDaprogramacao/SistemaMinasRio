<?php
require_once("../../../conexao.php");
@session_start();

// 1. OBTÉM O ID DO USUÁRIO DA SESSÃO
$id_usuario = @$_SESSION['id'];

// Recebe os dados do formulário
$id_funcionario = $_POST['id_funcionario'];
$valor = $_POST['valor'];
$data = $_POST['data'];
// A FORMA DE PAGAMENTO FOI REMOVIDA, POIS NÃO É MAIS NECESSÁRIA NO LANÇAMENTO

// Limpa o valor para o formato do banco de dados
$valor_limpo = str_replace('.', '', $valor);
$valor_limpo = str_replace(',', '.', $valor_limpo);

// Validação básica
if ($id_funcionario == "" || $valor_limpo == "" || $data == "") {
    echo 'Preencha todos os campos obrigatórios!';
    exit();
}
if ($id_usuario == "") {
    echo 'Usuário não autenticado. Faça o login novamente.';
    exit();
}

// ========================================================================
// LÓGICA PRINCIPAL (AJUSTADA PARA NÃO PRECISAR DE FORMA DE PAGAMENTO)
// ========================================================================

try {
    // 2. INSERE NA TABELA 'adiantamentos' COM STATUS 'NÃO' PAGO E FORMA PGTO VAZIA
    $query_adiant = $pdo->prepare("INSERT INTO adiantamentos (id_funcionario, valor, data, forma_pgto, pago) VALUES (:id_funcionario, :valor, :data, '', 'Não')");
    $query_adiant->bindValue(":id_funcionario", $id_funcionario);
    $query_adiant->bindValue(":valor", $valor_limpo);
    $query_adiant->bindValue(":data", $data);
    $query_adiant->execute();
    $id_adiantamento = $pdo->lastInsertId();

    // 3. BUSCA O CAIXA ATUAL DO OPERADOR
    $query_caixa = $pdo->query("SELECT * FROM caixas WHERE operador = '$id_usuario' AND data_fechamento IS NULL ORDER BY id DESC LIMIT 1");
    $res_caixa = $query_caixa->fetchAll(PDO::FETCH_ASSOC);
    $id_caixa = (@count($res_caixa) > 0) ? $res_caixa[0]['id'] : 0;
    
    // 4. BUSCA O NOME DO FUNCIONÁRIO PARA USAR NA DESCRIÇÃO
    $query_func = $pdo->query("SELECT nome FROM funcionarios WHERE id = '$id_funcionario'");
    $res_func = $query_func->fetch(PDO::FETCH_ASSOC);
    $nome_funcionario = $res_func['nome'];
    
    // 5. INSERE NA TABELA 'pagar' COMO UMA CONTA PENDENTE
    $descricao_conta = 'Adiantamento / Vale - ' . $nome_funcionario;
    $tabela = 'pagar';

    $query_pagar = $pdo->prepare("INSERT INTO $tabela SET 
        descricao = :descricao, 
        fornecedor = 0,
        funcionario = :funcionario, 
        valor = :valor, 
        vencimento = :data, 
        data_lanc = curDate(), 
        forma_pgto = 0, 
        frequencia = 0,
        subtotal = :valor, 
        usuario_lanc = :id_usuario, 
        pago = 'Não', 
        referencia = 'Adiantamento', 
        id_ref = :id_ref,
        caixa = :id_caixa, 
        hora = curTime()");

    $query_pagar->bindValue(":descricao", $descricao_conta);
    $query_pagar->bindValue(":funcionario", $id_funcionario);
    $query_pagar->bindValue(":valor", $valor_limpo);
    $query_pagar->bindValue(":data", $data);
    $query_pagar->bindValue(":id_usuario", $id_usuario);
    $query_pagar->bindValue(":id_ref", $id_adiantamento);
    $query_pagar->bindValue(":id_caixa", $id_caixa);
    $query_pagar->execute();

    echo 'Salvo com Sucesso';

} catch (Exception $e) {
    // Captura qualquer erro do banco de dados e o exibe
    echo 'Ocorreu um erro ao salvar! Detalhes: ' . $e->getMessage();
}
?>