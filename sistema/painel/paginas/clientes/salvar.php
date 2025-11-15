<?php
$tabela = 'clientes';
require_once("../../../conexao.php");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
$id_usuario = $_SESSION['id'] ?? null;

// Recebe dados do formulário
$nome            = $_POST['nome_cliente']    ?? '';
$email           = $_POST['email']           ?? '';
$contato         = $_POST['contato']         ?? '';
$data_nasc       = $_POST['data_nasc']       ?? null;
$cpf             = $_POST['cpf']             ?? '';
$rg              = $_POST['rg']              ?? '';
$tipo_pessoa     = $_POST['tipo_pessoa']     ?? '';
$razao_social    = $_POST['razao_social']    ?? '';
$cnpj            = $_POST['cnpj']            ?? '';
$ie              = $_POST['ie']              ?? '';
$site            = $_POST['site']            ?? '';
$plano_pagamento = $_POST['plano_pagamento'] ?? null;
$forma_pagamento = $_POST['forma_pagamento'] ?? null;
$prazo_pagamento = $_POST['prazo_pagamento'] ?? null;
$cep             = $_POST['cep']             ?? '';
$endereco        = $_POST['rua']             ?? '';
$numero          = $_POST['numero']          ?? '';
$bairro          = $_POST['bairro']          ?? '';
$cidade          = $_POST['cidade']          ?? '';
$uf              = $_POST['uf']              ?? '';
$complemento     = $_POST['complemento']     ?? '';
$id              = $_POST['id']              ?? null;

// 1) Definição de campos obrigatórios por tipo
$campos_comuns = [
    'nome'                => $nome,
    'email'               => $email,
    'contato'             => $contato,
    'CEP'                 => $cep,
    'endereço'            => $endereco,
    'número'              => $numero,
    'bairro'              => $bairro,
    'cidade'              => $cidade,
    'UF'                  => $uf,
    'plano de pagamento'  => $plano_pagamento,
    'forma de pagamento'  => $forma_pagamento,
    'prazo de pagamento'  => $prazo_pagamento,
];

$campos_fisica = [
    'data de nascimento'  => $data_nasc,
    'CPF'                 => $cpf,
];

$campos_juridica = [
    'razão social'        => $razao_social,
    'CNPJ'                => $cnpj,
    'IE'                  => $ie,
];

// 2) Monta o array de obrigatórios conforme tipo
$campos_obrigatorios = $campos_comuns;
if ($tipo_pessoa === 'fisica') {
    $campos_obrigatorios = array_merge($campos_obrigatorios, $campos_fisica);
} elseif ($tipo_pessoa === 'cnpj') {
    $campos_obrigatorios = array_merge($campos_obrigatorios, $campos_juridica);
} else {
    echo 'Selecione um tipo de pessoa válido!';
    exit;
}

// 3) Loop de validação genérica
foreach ($campos_obrigatorios as $label => $valor) {
    if (is_null($valor) || $valor === '' || $valor === '0' || $valor === 0) {
        echo "O campo “{$label}” é obrigatório e não foi preenchido!";
        exit;
    }
}

// 4) Validação de e-mail único
if (!empty($email)) {
    $stmt = $pdo->prepare("SELECT id FROM $tabela WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $res = $stmt->fetch();
    if ($res && $res['id'] != $id) {
        echo 'Email já cadastrado!';
        exit;
    }
}

// 5) Validação de contato único
if (!empty($contato)) {
    $stmt = $pdo->prepare("SELECT id FROM $tabela WHERE contato = ? LIMIT 1");
    $stmt->execute([$contato]);
    $res = $stmt->fetch();
    if ($res && $res['id'] != $id) {
        echo 'Telefone/Celular já cadastrado!';
        exit;
    }
}

// 6) Montagem do SQL (INSERT ou UPDATE)
if (empty($id)) {
    $sql = "INSERT INTO $tabela (
                nome, email, contato, data_cad,
                endereco, numero, bairro, cidade, uf, cep, complemento,
                tipo_pessoa, cpf, rg, data_nasc,
                razao_social, cnpj, ie, site,
                plano_pagamento, forma_pagamento, prazo_pagamento,
                usuario
            ) VALUES (
                :nome, :email, :contato, CURDATE(),
                :endereco, :numero, :bairro, :cidade, :uf, :cep, :complemento,
                :tipo_pessoa, :cpf, :rg, :data_nasc,
                :razao_social, :cnpj, :ie, :site,
                :plano_pagamento, :forma_pagamento, :prazo_pagamento,
                :usuario
            )";
} else {
    $sql = "UPDATE $tabela SET
                nome = :nome,
                email = :email,
                contato = :contato,
                endereco = :endereco,
                numero = :numero,
                bairro = :bairro,
                cidade = :cidade,
                uf = :uf,
                cep = :cep,
                complemento = :complemento,
                tipo_pessoa = :tipo_pessoa,
                cpf = :cpf,
                rg = :rg,
                data_nasc = :data_nasc,
                razao_social = :razao_social,
                cnpj = :cnpj,
                ie = :ie,
                site = :site,
                plano_pagamento = :plano_pagamento,
                forma_pagamento = :forma_pagamento,
                prazo_pagamento = :prazo_pagamento,
                usuario = :usuario
            WHERE id = :id";
}

$stmt = $pdo->prepare($sql);

// 7) Bind dos parâmetros
$stmt->bindValue(':nome', $nome);
$stmt->bindValue(':email', $email);
$stmt->bindValue(':contato', $contato);
$stmt->bindValue(':endereco', $endereco);
$stmt->bindValue(':numero', $numero);
$stmt->bindValue(':bairro', $bairro);
$stmt->bindValue(':cidade', $cidade);
$stmt->bindValue(':uf', $uf);
$stmt->bindValue(':cep', $cep);
$stmt->bindValue(':complemento', $complemento);
$stmt->bindValue(':tipo_pessoa', $tipo_pessoa);
$stmt->bindValue(':cpf', $cpf);
$stmt->bindValue(':rg', $rg);
$stmt->bindValue(':data_nasc', $data_nasc);
$stmt->bindValue(':razao_social', $razao_social);
$stmt->bindValue(':cnpj', $cnpj);
$stmt->bindValue(':ie', $ie);
$stmt->bindValue(':site', $site);
$stmt->bindValue(':plano_pagamento', $plano_pagamento);
$stmt->bindValue(':forma_pagamento', $forma_pagamento);
$stmt->bindValue(':prazo_pagamento', $prazo_pagamento);
$stmt->bindValue(':usuario', $id_usuario);

if (!empty($id)) {
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
}

// 8) Execução
$stmt->execute();

echo 'Salvo com Sucesso';
