<?php
$tabela = 'receber';
require_once("../../../conexao.php");


$id = intval($_POST['id'] ?? 0);

echo <<<HTML
<table id="example_res" class="table table-striped table-light table-hover my-4">
<thead>
<tr>
<th>Valor</th>
<th>Data</th>
<th>Usuário</th>
</tr>
</thead>
<tbody>
HTML;


// Pagamentos parciais são lançados como resíduos pagos ligados à conta (ver baixar.php)
$query = $pdo->prepare("SELECT r.*, u.nome as nome_usuario FROM $tabela r
	LEFT JOIN usuarios u ON u.id = r.usuario_pgto
	WHERE r.id_ref = :id AND r.residuo = 'Sim' ORDER BY r.id DESC");
$query->execute([':id' => $id]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
foreach ($res as $residuo) {
		$valor = number_format($residuo['valor'], 2, ',', '.');
		$data = implode('/', array_reverse(@explode('-', $residuo['data_pgto'])));
		$nome_usuario = $residuo['nome_usuario'] ?? 'Sem Usuário';

echo <<<HTML
	<tr>
	<td>R$ {$valor}</td>
	<td>{$data}</td>
	<td>{$nome_usuario}</td>
	</tr>
HTML;
}
echo <<<HTML
</tbody>
</table>
HTML;

?>
