<?php
$tabela = 'produtos';
require_once("../../../conexao.php");
require_once(__DIR__ . "/../../seguranca.php");
seguranca_exigir_acesso(['produtos']);
require_once("../../verificar.php");

$id = intval($_POST['id'] ?? 0);
$acao = ($_POST['acao'] ?? '') == 'Sim' ? 'Sim' : 'Não';

$pdo->prepare("UPDATE $tabela SET ativo = :acao WHERE id = :id")->execute([':acao' => $acao, ':id' => $id]);
echo 'Alterado com Sucesso';
