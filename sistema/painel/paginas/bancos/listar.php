<?php
@session_start();
$mostrar_registros = @$_SESSION['registros'];
$id_usuario = @$_SESSION['id'];
$tabela = 'bancos';
require_once("../../../conexao.php");
require_once("../../verificar.php");

if ($mostrar_registros == 'Não') {
	$sql_usuario_lanc = " WHERE usuario_lanc = '$id_usuario '";
} else {
	$sql_usuario_lanc = " ";
}

$query = $pdo->query("SELECT * from $tabela $sql_usuario_lanc order by id desc");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);

if ($linhas > 0) {
	echo <<<HTML
<small>
<table class="table table-bordered text-nowrap border-bottom dt-responsive" id="tabela">
    <thead> 
        <tr> 
            <th align="center" width="5%" class="text-center">Selecionar</th>
            <th>Correntista</th>
            <th>Banco</th>
            <th>Agência</th>
            <th>Conta</th>
            <th>Saldo R$</th>
            <th>Ações</th>
        </tr> 
    </thead>
    <tbody>
HTML;

	for ($i = 0; $i < $linhas; $i++) {
		$id = $res[$i]['id'];
		$correntista = $res[$i]['correntista'];
		$banco = $res[$i]['banco'];
		$agencia = $res[$i]['agencia'];
		$conta = $res[$i]['conta'];
		$saldo = number_format($res[$i]['saldo'], 2, ',', '.');

		echo <<<HTML
		<tr>
			<td align="center">
				<div class="custom-checkbox custom-control">
					<input type="checkbox" class="custom-control-input" id="seletor-{$id}" onchange="selecionar('{$id}')">
					<label for="seletor-{$id}" class="custom-control-label mt-1 text-dark"></label>
				</div>
			</td>
			<td>{$correntista}</td>
			<td>{$banco}</td>
			<td>{$agencia}</td>
			<td>{$conta}</td>
			<td>R$ {$saldo}</td>
			<td>
				<big>
					<a class="btn btn-info btn-sm" href="#" onclick="editar('{$id}','{$correntista}','{$banco}','{$agencia}','{$conta}','{$saldo}')" title="Editar Dados">
						<i class="fa fa-edit"></i>
					</a>

					<div class="dropdown" style="display: inline-block;">
						<a class="btn btn-danger btn-sm" href="#" aria-expanded="false" aria-haspopup="true" data-bs-toggle="dropdown" class="dropdown">
							<i class="fa fa-trash"></i>
						</a>
						<div class="dropdown-menu tx-13">
							<div class="dropdown-item-text botao_excluir">
								<p>Confirmar Exclusão? <a href="#" onclick="excluir('{$id}')"><span class="text-danger">Sim</span></a></p>
							</div>
						</div>
					</div>
				</big>
			</td>
		</tr>
HTML;
	}

	echo <<<HTML
	</tbody>
    <small><div align="center" id="mensagem-excluir"></div></small>
</table>
</small>
HTML;
} else {
	echo 'Nenhum registro cadastrado!';
}
?>

<script type="text/javascript">
	$(document).ready(function() {
		$('#tabela').DataTable({
			"ordering": false,
			"stateSave": true,
			"language": {
				"url": "//cdn.datatables.net/plug-ins/1.13.2/i18n/pt-BR.json"
			}
		});
	});
</script>