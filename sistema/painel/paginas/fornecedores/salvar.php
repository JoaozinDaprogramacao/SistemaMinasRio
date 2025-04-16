<?php
@session_start();
$id_usuario = @$_SESSION['id'];
$tabela = 'fornecedores';
require_once("../../../conexao.php");



$id = $_POST['id']; // ID da tabela (int, AI, PK)
$nome_atacadista = $_POST['nome_atacadista']; // Nome do atacadista (varchar(50))
$razao_social = $_POST['razao_social']; // Razão social (varchar(50))
$cnpj = $_POST['cnpj']; // CNPJ (varchar(18))
$ie = $_POST['ie']; // Inscrição Estadual (varchar(50))
$cpf = $_POST['cpf']; // CPF (varchar(14))
$rg = $_POST['rg']; // RG (varchar(12))
$rua = $_POST['rua']; // Rua (varchar(45))
$numero = $_POST['numero']; // Número (int)
$bairro = $_POST['bairro']; // Bairro (varchar(45))
$cidade = $_POST['cidade']; // Cidade (varchar(45))
$cep = $_POST['cep']; // CEP (varchar(15))
$uf = $_POST['uf']; // Unidade Federativa (varchar(3))
$complemento = $_POST['complemento']; // Complemento (varchar(100))
$contato = $_POST['contato']; // Contato (varchar(15))
$site = $_POST['site']; // Site (varchar(45))
$plano_pagamento = $_POST['plano_pagamento']; // Plano de recebimento (int)
$forma_pagamento = $_POST['forma_pagamento']; // Forma de recebimento (int)
$prazo_pagamento = $_POST['prazo_pagamento']; // Prazo de pagamento (int)
$email = $_POST['email']; // E-mail (varchar(64))



//validacao email
if ($email != "") {
    $query = $pdo->query("SELECT * from $tabela where email = '$email'");
    $res = $query->fetchAll(PDO::FETCH_ASSOC);
    $id_reg = @$res[0]['id'];
    if (@count($res) > 0 and $id != $id_reg) {
        echo 'Email já Cadastrado!';
        exit();
    }
}

//validacao telefone
$query = $pdo->query("SELECT * from $tabela where contato = '$contato'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$id_reg = @$res[0]['id'];
if (@count($res) > 0 and $id != $id_reg) {
    echo 'Telefone já Cadastrado!';
    exit();
}

if ($id == "") {
    // Inserção de novo registro
    $query = $pdo->prepare("
        INSERT INTO $tabela SET 
            data_cadastro = curDate(),
            nome_atacadista = :nome_atacadista,
            razao_social = :razao_social,
            cnpj = :cnpj,
            ie = :ie,
            cpf = :cpf,
            rg = :rg,
            rua = :rua,
            numero = :numero,
            bairro = :bairro,
            cidade = :cidade,
            cep = :cep,
            uf = :uf,
            complemento = :complemento,
            contato = :contato,
            site = :site,
            plano_pagamento = :plano_pagamento,
            forma_pagamento = :forma_pagamento,
            prazo_pagamento = :prazo_pagamento,
            email = :email
    ");
} else {
    // Atualização de registro existente
    $query = $pdo->prepare("
        UPDATE $tabela SET 
            nome_atacadista = :nome_atacadista,
            razao_social = :razao_social,
            cnpj = :cnpj,
            ie = :ie,
            cpf = :cpf,
            rg = :rg,
            rua = :rua,
            numero = :numero,
            bairro = :bairro,
            cidade = :cidade,
            cep = :cep,
            uf = :uf,
            complemento = :complemento,
            contato = :contato,
            site = :site,
            plano_pagamento = :plano_pagamento,
            forma_pagamento = :forma_pagamento,
            prazo_pagamento = :prazo_pagamento,
            email = :email
        WHERE id = '$id'
    ");
}

// Bind dos valores
$query->bindValue(":nome_atacadista", "$nome_atacadista");
$query->bindValue(":razao_social", "$razao_social");
$query->bindValue(":cnpj", "$cnpj");
$query->bindValue(":ie", "$ie");
$query->bindValue(":cpf", "$cpf");
$query->bindValue(":rg", "$rg");
$query->bindValue(":rua", "$rua");
$query->bindValue(":numero", "$numero");
$query->bindValue(":bairro", "$bairro");
$query->bindValue(":cidade", "$cidade");
$query->bindValue(":cep", "$cep");
$query->bindValue(":uf", "$uf");
$query->bindValue(":complemento", "$complemento");
$query->bindValue(":contato", "$contato");
$query->bindValue(":site", "$site");
$query->bindValue(":plano_pagamento", "$plano_pagamento");
$query->bindValue(":forma_pagamento", "$forma_pagamento");
$query->bindValue(":prazo_pagamento", "$prazo_pagamento");
$query->bindValue(":email", "$email");

// Executa a consulta
$query->execute();

echo 'Salvo com Sucesso';
