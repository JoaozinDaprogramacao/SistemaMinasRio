<?php
@session_start();
$id_usuario = $_SESSION['id'] ?? null;
$tabela = 'fornecedores';
require_once("../../../conexao.php");

// Captura dados do formulário
$id               = $_POST['id']               ?? null;
$nome_atacadista  = $_POST['nome_atacadista']  ?? '';
$razao_social     = $_POST['razao_social']     ?? '';
$cnpj             = $_POST['cnpj']             ?? '';
$ie               = $_POST['ie']               ?? '';
$cpf              = $_POST['cpf']              ?? '';
$rg               = $_POST['rg']               ?? '';
$rua              = $_POST['rua']              ?? '';
$numero           = $_POST['numero']           ?? '';
$bairro           = $_POST['bairro']           ?? '';
$cidade           = $_POST['cidade']           ?? '';
$cep              = $_POST['cep']              ?? '';
$uf               = $_POST['uf']               ?? '';
$complemento      = $_POST['complemento']      ?? '';
$contato          = $_POST['contato']          ?? '';
$site             = $_POST['site']             ?? '';
$plano_pagamento  = $_POST['plano_pagamento']  ?? null;
$forma_pagamento  = $_POST['forma_pagamento']  ?? null;
$prazo_pagamento  = $_POST['prazo_pagamento']  ?? null;
$email            = $_POST['email']            ?? '';
$tipo_pessoa      = $_POST['tipo_pessoa']      ?? '';  // novo campo

// Validações
if ($email !== '') {
    $stmt = $pdo->prepare("SELECT id FROM $tabela WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $ex = $stmt->fetch();
    if ($ex && $ex['id'] != $id) {
        echo 'Email já cadastrado!';
        exit;
    }
}

if ($contato !== '') {
    $stmt = $pdo->prepare("SELECT id FROM $tabela WHERE contato = ? LIMIT 1");
    $stmt->execute([$contato]);
    $ex = $stmt->fetch();
    if ($ex && $ex['id'] != $id) {
        echo 'Telefone já cadastrado!';
        exit;
    }
}

// Monta query de INSERT ou UPDATE
if (empty($id)) {
    $sql = "INSERT INTO $tabela SET
                data_cadastro   = NOW(),
                nome_atacadista = :nome_atacadista,
                razao_social    = :razao_social,
                cnpj            = :cnpj,
                ie              = :ie,
                cpf             = :cpf,
                rg              = :rg,
                tipo_pessoa     = :tipo_pessoa,
                rua             = :rua,
                numero          = :numero,
                bairro          = :bairro,
                cidade          = :cidade,
                cep             = :cep,
                uf              = :uf,
                complemento     = :complemento,
                contato         = :contato,
                site            = :site,
                plano_pagamento = :plano_pagamento,
                forma_pagamento = :forma_pagamento,
                prazo_pagamento = :prazo_pagamento,
                email           = :email";
} else {
    $sql = "UPDATE $tabela SET
                nome_atacadista = :nome_atacadista,
                razao_social    = :razao_social,
                cnpj            = :cnpj,
                ie              = :ie,
                cpf             = :cpf,
                rg              = :rg,
                tipo_pessoa     = :tipo_pessoa,
                rua             = :rua,
                numero          = :numero,
                bairro          = :bairro,
                cidade          = :cidade,
                cep             = :cep,
                uf              = :uf,
                complemento     = :complemento,
                contato         = :contato,
                site            = :site,
                plano_pagamento = :plano_pagamento,
                forma_pagamento = :forma_pagamento,
                prazo_pagamento = :prazo_pagamento,
                email           = :email
            WHERE id = :id";
}

$stmt = $pdo->prepare($sql);

// Faz bind dos parâmetros
$stmt->bindValue(':nome_atacadista', $nome_atacadista);
$stmt->bindValue(':razao_social',    $razao_social);
$stmt->bindValue(':cnpj',            $cnpj);
$stmt->bindValue(':ie',              $ie);
$stmt->bindValue(':cpf',             $cpf);
$stmt->bindValue(':rg',              $rg);
$stmt->bindValue(':tipo_pessoa',     $tipo_pessoa);  // bind novo campo
$stmt->bindValue(':rua',             $rua);
$stmt->bindValue(':numero',          $numero);
$stmt->bindValue(':bairro',          $bairro);
$stmt->bindValue(':cidade',          $cidade);
$stmt->bindValue(':cep',             $cep);
$stmt->bindValue(':uf',              $uf);
$stmt->bindValue(':complemento',     $complemento);
$stmt->bindValue(':contato',         $contato);
$stmt->bindValue(':site',            $site);
$stmt->bindValue(':plano_pagamento', $plano_pagamento);
$stmt->bindValue(':forma_pagamento', $forma_pagamento);
$stmt->bindValue(':prazo_pagamento', $prazo_pagamento);
$stmt->bindValue(':email',           $email);

if (!empty($id)) {
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
}

$stmt->execute();

echo 'Salvo com Sucesso';
