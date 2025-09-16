<?php
$tabela = 'funcionarios';
require_once("../../../conexao.php");

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
// PASSO 3: PROCESSAMENTO DO UPLOAD DA IMAGEM
// ===================================================================

$caminho_destino = '../../images/funcionarios/';
$nome_imagem_padrao = 'sem-foto.jpg';
$nome_imagem_final = ""; // Variável que guardará o nome do arquivo a ser salvo

// Verifica se um arquivo foi enviado no campo 'foto'
if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
    $foto_temp = $_FILES['foto']['tmp_name'];
    $nome_original = $_FILES['foto']['name'];
    
    // Pega a extensão do arquivo
    $extensao = pathinfo($nome_original, PATHINFO_EXTENSION);

    // Valida a extensão (apenas imagens)
    $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'webp'];
    if (in_array(strtolower($extensao), $extensoes_permitidas)) {
        
        // Gera um nome de arquivo único para evitar conflitos
        $novo_nome_imagem = time() . '-' . uniqid() . '.' . $extensao;

        // Tenta mover o arquivo para a pasta de destino
        if (move_uploaded_file($foto_temp, $caminho_destino . $novo_nome_imagem)) {
            $nome_imagem_final = $novo_nome_imagem; // Sucesso!
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
    
    // Se uma nova imagem foi enviada, apaga a antiga do servidor
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

    // A query de UPDATE só altera o campo 'foto' se uma nova imagem foi enviada
    $sql = "UPDATE $tabela SET 
                nome = :nome, telefone = :telefone, chave_pix = :chave_pix, 
                endereco = :endereco, cargo = :cargo, data_admissao = :data_admissao, 
                status = :status, data_demissao = :data_demissao, 
                descricao_salario = :descricao_salario, salario_folha = :salario_folha, 
                obs = :obs " .
                ($nome_imagem_final != "" ? ", foto = :foto " : "") . // Adição condicional do campo foto
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

// O bind do parâmetro :foto só é feito se necessário
if ($nome_imagem_final != "") {
    $query->bindValue(":foto", $nome_imagem_final);
}

$query->execute();

echo 'Salvo com Sucesso';
?>