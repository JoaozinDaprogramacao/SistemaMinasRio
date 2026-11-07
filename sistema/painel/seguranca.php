<?php
// Proteção dos arquivos chamados pelas telas do painel (ajax) e dos relatórios.
// Incluir logo depois do conexao.php.
@session_start();

function seguranca_logado()
{
	return @$_SESSION['id'] != '' and @$_SESSION['aut_token_075'] == 'xss_010203';
}

// Mesmo comportamento do verificar.php: sem sessão, volta para o login
function seguranca_exigir_login()
{
	if (!seguranca_logado()) {
		echo '<script>window.location="../"</script>';
		exit();
	}
}

// Permissões que o verificar_permissoes.php controla (as que começam como 'ocultar').
// Tela que confere uma variável fora dessa lista fica liberada para qualquer usuário logado,
// então o endpoint dela também fica.
function seguranca_chaves_controladas()
{
	preg_match_all('/^\$([a-z_]+)\s*=\s*\'ocultar\';/m', file_get_contents(__DIR__ . '/verificar_permissoes.php'), $m);
	return $m[1];
}

function seguranca_chaves_do_usuario($id_usuario)
{
	$query = $GLOBALS['pdo']->prepare("SELECT a.chave FROM usuarios_permissoes up JOIN acessos a ON a.id = up.permissao WHERE up.usuario = :usuario");
	$query->execute([':usuario' => $id_usuario]);
	return $query->fetchAll(PDO::FETCH_COLUMN);
}

// Libera se o usuário pode abrir alguma das telas que usam este endpoint.
// Administrador não passa pelo verificar_permissoes.php no index.php, então passa direto aqui também.
function seguranca_exigir_acesso($chaves)
{
	seguranca_exigir_login();
	if (@$_SESSION['nivel'] == 'Administrador') {
		return;
	}
	$controladas = seguranca_chaves_controladas();
	$do_usuario = seguranca_chaves_do_usuario($_SESSION['id']);
	foreach ($chaves as $chave) {
		if (!in_array($chave, $controladas) or in_array($chave, $do_usuario)) {
			return;
		}
	}
	echo 'Você não tem permissão para esta ação!';
	exit();
}

// Os *_class.php geram o PDF buscando o HTML do relatório pela URL do sistema, sem a sessão
// do usuário. Essa busca leva um token assinado com a senha do banco, válido por alguns minutos.
function seguranca_token_rel($janelas_atras = 0)
{
	$janela = floor(time() / 300) - $janelas_atras;
	return hash_hmac('sha256', 'rel|' . $janela, $GLOBALS['banco'] . '|' . $GLOBALS['senha']);
}

function seguranca_exigir_login_ou_token()
{
	$token = (string) @$_GET['token'];
	if (seguranca_logado() or hash_equals(seguranca_token_rel(), $token) or hash_equals(seguranca_token_rel(1), $token)) {
		return;
	}
	echo '<script>window.location="../../"</script>';
	exit();
}
