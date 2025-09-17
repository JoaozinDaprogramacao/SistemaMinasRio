<?php
$tabela = 'funcionarios';
require_once("../../../conexao.php");

// ===================================================================
// FUNÇÃO AUXILIAR PARA PEGAR NOME DO CARGO (Adicionada)
// ===================================================================
function getCargoNome($pdo, $id_cargo) {
    if (empty($id_cargo)) return 'N/A';
    $query = $pdo->prepare("SELECT nome FROM cargos WHERE id = :id_cargo");
    $query->bindValue(":id_cargo", $id_cargo);
    $query->execute();
    $res = $query->fetch(PDO::FETCH_ASSOC);
    return $res ? $res['nome'] : 'Cargo Desconhecido';
}

// ===================================================================
// PASSO 1: CAPTURA E TRATAMENTO DOS DADOS DO FORMULÁRIO
// ===================================================================

$nome = $_POST['nome'];
$telefone = $_POST['telefone'];
$chave_pix = $_POST['chave_pix'];
$endereco = $_POST['endereco'];
$cargo = $_POST['cargo'];
$data_admissao = $_POST['data_admissao'];
$status = $_POST['status'];
$data_demissao = $_POST['data_demissao'];
$descricao_salario = $_POST['descricao_salario'];
$salario_folha = $_POST['salario_folha'];
$obs = $_POST['obs'];
$id = $_POST['id'];

// Tratamento de valores nulos e padrões
$data_demissao = ($status === 'Demitido' && $data_demissao !== '') ? $data_demissao : null;
$descricao_salario = ($descricao_salario === "") ? 0 : $descricao_salario;
$salario_folha = ($salario_folha === "") ? 0 : $salario_folha;

// ===================================================================
// PASSO 2: VALIDAÇÃO DE DUPLICIDADE (Telefone)
// ===================================================================

$query = $pdo->prepare("SELECT * FROM $tabela WHERE telefone = :telefone AND id != :id");
$query->bindValue(":telefone", $telefone);
$query->bindValue(":id", $id);
$query->execute();
if ($query->rowCount() > 0) {
    echo 'Telefone já cadastrado para outro funcionário!';
    exit();
}

// ===================================================================
// PASSO 3: PROCESSAMENTO DO UPLOAD DA IMAGEM (Sem alterações)
// ===================================================================
// (Seu código de upload de imagem original permanece aqui, sem alterações)
$caminho_destino = '../../images/funcionarios/';
$nome_imagem_padrao = 'sem-foto.jpg';
$nome_imagem_final = ""; 

if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
    $foto_temp = $_FILES['foto']['tmp_name'];
    $nome_original = $_FILES['foto']['name'];
    $extensao = pathinfo($nome_original, PATHINFO_EXTENSION);
    $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'webp'];
    if (in_array(strtolower($extensao), $extensoes_permitidas)) {
        $novo_nome_imagem = time() . '-' . uniqid() . '.' . $extensao;
        if (move_uploaded_file($foto_temp, $caminho_destino . $novo_nome_imagem)) {
            $nome_imagem_final = $novo_nome_imagem;
        } else {
            echo "Falha ao mover o arquivo de imagem.";
            exit();
        }
    } else {
        echo 'Formato de imagem não permitido! Use JPG, PNG ou WEBP.';
        exit();
    }
}


// ===================================================================
// PASSO 4: OPERAÇÃO NO BANCO DE DADOS (INSERT OU UPDATE)
// ===================================================================

if ($id == "") {
    // --- INSERT (Novo Funcionário) ---
    $nome_imagem_final = ($nome_imagem_final == "") ? $nome_imagem_padrao : $nome_imagem_final;
    
    $sql = "INSERT INTO $tabela SET 
                nome = :nome, telefone = :telefone, chave_pix = :chave_pix, 
                endereco = :endereco, cargo = :cargo, data_admissao = :data_admissao, 
                status = :status, data_demissao = :data_demissao, 
                descricao_salario = :descricao_salario, salario_folha = :salario_folha, 
                obs = :obs, data_cad = curDate(), foto = :foto";
    $query = $pdo->prepare($sql);

} else {
    // --- UPDATE (Funcionário Existente) ---
    
    // --- INÍCIO DA LÓGICA DE HISTÓRICO PARA UPDATE ---
    $query_antigos = $pdo->prepare("SELECT * FROM $tabela WHERE id = :id");
    $query_antigos->bindValue(":id", $id);
    $query_antigos->execute();
    $dados_antigos = $query_antigos->fetch(PDO::FETCH_ASSOC);

    // 1. VERIFICA MUDANÇA DE CARGO (PROMOÇÃO)
    if ($dados_antigos['cargo'] != $cargo) {
        $nome_cargo_antigo = getCargoNome($pdo, $dados_antigos['cargo']);
        $nome_cargo_novo = getCargoNome($pdo, $cargo);
        $desc_historico = "Promovido(a) de '$nome_cargo_antigo' para '$nome_cargo_novo'.";

        $query_hist = $pdo->prepare("INSERT INTO historico_funcionarios SET id_funcionario = :id_func, tipo_evento = 'PROMOCAO', descricao = :desc, valor_antigo = :val_antigo, valor_novo = :val_novo, data_evento = NOW()");
        $query_hist->bindValue(":id_func", $id);
        $query_hist->bindValue(":desc", $desc_historico);
        $query_hist->bindValue(":val_antigo", $dados_antigos['cargo']);
        $query_hist->bindValue(":val_novo", $cargo);
        $query_hist->execute();
    }

    // 2. VERIFICA MUDANÇA SALARIAL
    if ($dados_antigos['descricao_salario'] != $descricao_salario) {
        $desc_historico = "Salário (multiplicador) alterado de '{$dados_antigos['descricao_salario']}' para '$descricao_salario'.";
        
        $query_hist = $pdo->prepare("INSERT INTO historico_funcionarios SET id_funcionario = :id_func, tipo_evento = 'ALTERACAO_SALARIAL', descricao = :desc, valor_antigo = :val_antigo, valor_novo = :val_novo, data_evento = NOW()");
        $query_hist->bindValue(":id_func", $id);
        $query_hist->bindValue(":desc", $desc_historico);
        $query_hist->bindValue(":val_antigo", $dados_antigos['descricao_salario']);
        $query_hist->bindValue(":val_novo", $descricao_salario);
        $query_hist->execute();
    }
    // --- FIM DA LÓGICA DE HISTÓRICO PARA UPDATE ---


    if ($nome_imagem_final != "") {
        $query_foto_antiga = $pdo->prepare("SELECT foto FROM $tabela WHERE id = :id");
        $query_foto_antiga->bindValue(":id", $id);
        $query_foto_antiga->execute();
        $res_foto = $query_foto_antiga->fetch(PDO::FETCH_ASSOC);
        $foto_antiga = $res_foto['foto'];

        if ($foto_antiga != $nome_imagem_padrao && file_exists($caminho_destino . $foto_antiga)) {
            unlink($caminho_destino . $foto_antiga);
        }
    }

    $sql = "UPDATE $tabela SET 
                nome = :nome, telefone = :telefone, chave_pix = :chave_pix, 
                endereco = :endereco, cargo = :cargo, data_admissao = :data_admissao, 
                status = :status, data_demissao = :data_demissao, 
                descricao_salario = :descricao_salario, salario_folha = :salario_folha, 
                obs = :obs " .
                ($nome_imagem_final != "" ? ", foto = :foto " : "") . 
           "WHERE id = :id";

    $query = $pdo->prepare($sql);
    $query->bindValue(":id", $id);
}

// ===================================================================
// PASSO 5: BINDING DOS PARÂMETROS E EXECUÇÃO
// ===================================================================

$query->bindValue(":nome", $nome);
$query->bindValue(":telefone", $telefone);
$query->bindValue(":chave_pix", $chave_pix);
$query->bindValue(":endereco", $endereco);
$query->bindValue(":cargo", $cargo);
$query->bindValue(":data_admissao", $data_admissao);
$query->bindValue(":status", $status);
$query->bindValue(":data_demissao", $data_demissao);
$query->bindValue(":descricao_salario", $descricao_salario);
$query->bindValue(":salario_folha", $salario_folha);
$query->bindValue(":obs", $obs);

if ($nome_imagem_final != "") {
    $query->bindValue(":foto", $nome_imagem_final);
}

$query->execute();
$id_final = ($id == "") ? $pdo->lastInsertId() : $id; // Pega o ID para o histórico de contratação

// ===================================================================
// PASSO 6: REGISTRO DE HISTÓRICO PARA NOVO FUNCIONÁRIO (CONTRATAÇÃO)
// ===================================================================
if ($id == "") {
    $nome_cargo_novo = getCargoNome($pdo, $cargo);
    $desc_historico = "Funcionário(a) contratado(a) para o cargo de '$nome_cargo_novo'.";

    $query_hist = $pdo->prepare("INSERT INTO historico_funcionarios SET id_funcionario = :id_func, tipo_evento = 'CONTRATACAO', descricao = :desc, valor_novo = :val_novo, data_evento = NOW()");
    $query_hist->bindValue(":id_func", $id_final);
    $query_hist->bindValue(":desc", $desc_historico);
    $query_hist->bindValue(":val_novo", $cargo);
    $query_hist->execute();
}

echo 'Salvo com Sucesso';
?>