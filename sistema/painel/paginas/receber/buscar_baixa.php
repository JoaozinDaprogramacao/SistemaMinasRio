<?php
require_once("../../../conexao.php"); 
@session_start();

$id = $_POST['id'];

// 1. Busca primeiro os dados da conta a receber
$query = $pdo->query("SELECT * FROM receber WHERE id = '$id'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);

if (@count($res) > 0) {
    // Salvamos essas variáveis para "caçar" a transação na outra tabela
    $banco = $res[0]['banco'];
    $data_pgto = $res[0]['data_pgto'];
    $subtotal = $res[0]['subtotal'];
    $usuario_pgto = $res[0]['usuario_pgto'];

    // 2. Busca na tabela linha_bancos (Sem JOIN, usando os dados para achar a correspondência)
    $query_banco = $pdo->query("SELECT n_fiscal, descricao FROM linha_bancos 
                                WHERE id_banco = '$banco' 
                                AND data = '$data_pgto' 
                                AND credito = '$subtotal' 
                                AND remetente = '$usuario_pgto' 
                                ORDER BY id DESC LIMIT 1");
    $res_banco = $query_banco->fetchAll(PDO::FETCH_ASSOC);

    // Se encontrou a linha do banco, pega a operação e obs. Se não, deixa em branco.
    $numero_operacao = (@count($res_banco) > 0) ? $res_banco[0]['n_fiscal'] : '';
    
    // Se a descrição for apenas "(Resíduo) ...", ou "Baixa de Título" padrão, ela vem pra cá também
    $obs_baixa = (@count($res_banco) > 0) ? $res_banco[0]['descricao'] : '';

    // 3. Monta o array com tudo junto
    $dados = array(
        'valor_pago'      => $res[0]['valor'], 
        'data_pgto'       => $res[0]['data_pgto'],
        'forma_pgto'      => $res[0]['forma_pgto'],
        'banco'           => $res[0]['banco'],
        'multa'           => $res[0]['multa'],
        'juros'           => $res[0]['juros'],
        'acrescimo'       => $res[0]['taxa'], 
        'desconto'        => $res[0]['desconto'],
        
        // Puxados da linha_bancos:
        'numero_operacao' => $numero_operacao,
        'obs'             => $obs_baixa
    );
    
    echo json_encode($dados);
} else {
    echo json_encode(['erro' => 'Registro não encontrado']);
}
?>