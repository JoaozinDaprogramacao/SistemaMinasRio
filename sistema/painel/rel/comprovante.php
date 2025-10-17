<?php
// 1. INCLUSÃO E VARIÁVEIS INICIAIS
include('../../conexao.php');

// Define um valor padrão para impressao_automatica caso não esteja definido
// Isso evita um 'Undefined variable' no script de impressão
$impressao_automatica = @$impressao_automatica ?? 'Não';

// Coleta e sanitiza o ID da URL, garantindo que seja um inteiro
$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

// Verifica se o ID é válido
if (!$id) {
	die('ID de registro inválido ou não fornecido.');
}

// 2. BUSCAR AS INFORMAÇÕES DO REGISTRO (Tabela receber) - SEGURANÇA: Prepared Statement
try {
	$stmt = $pdo->prepare("SELECT * FROM receber WHERE id = :id");
	// PDO::PARAM_INT garante que o dado seja tratado como um número inteiro
	$stmt->bindValue(':id', $id, PDO::PARAM_INT);
	$stmt->execute();
	$res = $stmt->fetchAll(PDO::FETCH_ASSOC);

	if (count($res) === 0) {
		die('Registro não encontrado na tabela "receber".');
	}

	// Atribuição de variáveis
	$dados = $res[0];

	// As colunas foram mantidas com os nomes originais (baseado na estrutura fornecida)
	$id_receber = $dados['id']; // ID real do registro receber
	$descricao = $dados['descricao'];
	$cliente = $dados['cliente'];
	$valor = $dados['valor'];
	$data_lanc = $dados['data_lanc'];
	$data_venc = $dados['vencimento']; // coluna 'vencimento'
	$data_pgto = $dados['data_pgto'];
	$usuario_lanc = $dados['usuario_lanc'];
	$usuario_pgto = $dados['usuario_pgto'];
	$frequencia = $dados['frequencia'];

	// ATENÇÃO: Renomeado para ID da Forma de Pgto para evitar conflito
	$saida_id = $dados['forma_pgto'];

	$arquivo = $dados['arquivo'];
	$pago = $dados['pago'];
	$obs = $dados['obs'];
	$desconto = $dados['desconto'];
	$troco = $dados['troco'];
	$hora = $dados['hora'];
	$cancelada = $dados['cancelada'];
	$tipo_desconto = $dados['tipo_desconto'];
	$total_venda = $dados['subtotal']; // coluna 'subtotal'
	$valor_restante = $dados['valor_restante'];

	// ATENÇÃO: Assumindo que existe uma coluna ID para a forma de pgto restante
	$forma_pgto_restante_id = $dados['forma_pgto_restante'];

	$data_restante = $dados['data_restante'];
	$id_ref = $dados['id_ref'];
	$referencia = $dados['referencia'];
	$frete = $dados['frete'];

	// Variável que o código original inicializa como vazia
	$garantia_venda = '';
} catch (PDOException $e) {
	// Tratamento de erro de banco de dados
	die("Erro ao buscar dados do registro: " . $e->getMessage());
}

// 3. CÁLCULOS E FORMATAÇÕES DE DATAS E VALORES

// Lógica de Vencimento
$data_venc_1 = '';
if (strtotime($data_venc) > strtotime($data_lanc)) {
	$data_venc_1 = $data_venc;
}

$data_venc_2 = '';
if (strtotime($data_restante) > strtotime($data_lanc)) {
	$data_venc_2 = $data_restante;
}

// Cálculo de Troco (Assumindo que $troco é o valor recebido)
$total_troco = 0;
if ($troco > 0) {
	// O troco é o valor recebido ($troco) menos o valor final devido ($valor)
	$total_troco = max(0, floatval($troco) - floatval($valor));
}


// Formatação de Datas (função 'implode/array_reverse/explode' é mantida)
$data_venc_1F = $data_venc_1 ? implode('/', array_reverse(explode('-', $data_venc_1))) : '';
$data_venc_2F = $data_venc_2 ? implode('/', array_reverse(explode('-', $data_venc_2))) : '';
$data_lancF = $data_lanc ? implode('/', array_reverse(explode('-', $data_lanc))) : '';
$data_vencF = $data_venc ? implode('/', array_reverse(explode('-', $data_venc))) : '';
$data_pgtoF = $data_pgto ? implode('/', array_reverse(explode('-', $data_pgto))) : '';

// Formatação de Valores
$valorF = number_format(floatval($valor), 2, ',', '.');
$trocoF = number_format(floatval($troco), 2, ',', '.');
$total_trocoF = number_format(floatval($total_troco), 2, ',', '.');
$total_vendaF = number_format(floatval($total_venda), 2, ',', '.');
$valor_restanteF = number_format(floatval($valor_restante), 2, ',', '.');

// Desconto Percentual para exibição
$descontoFP = number_format(floatval($desconto), 0, ',', '.'); // Se for porcentagem, mostra sem casas decimais

$freteF = number_format(floatval($frete), 2, ',', '.');

// 4. BUSCAS DE RELACIONAMENTOS (Clientes, Usuários, Formas de Pgto) - SEGURANÇA: Prepared Statements

// BUSCA DADOS DO USUÁRIO LANÇAMENTO
$nome_usu_lanc = 'Sem Usuário';
$stmt_usu = $pdo->prepare("SELECT nome FROM usuarios WHERE id = :usuario_lanc");
$stmt_usu->bindValue(':usuario_lanc', $usuario_lanc, PDO::PARAM_INT);
$stmt_usu->execute();
$res_usu = $stmt_usu->fetch(PDO::FETCH_ASSOC);
if ($res_usu) {
	$nome_usu_lanc = $res_usu['nome'];
}


// BUSCA DADOS DO CLIENTE (Coluna 'telefone' foi substituída por 'contato')
$nome_cliente = 'Não Informado';
$tel_cliente = ''; // Mantemos o nome da variável PHP como $tel_cliente para o restante do código
$stmt_cli = $pdo->prepare("SELECT nome, contato FROM clientes WHERE id = :cliente");
$stmt_cli->bindValue(':cliente', $cliente, PDO::PARAM_INT);
$stmt_cli->execute();
$res_cli = $stmt_cli->fetch(PDO::FETCH_ASSOC); // Usamos fetch() já que esperamos apenas um resultado
if ($res_cli) {
	$nome_cliente = $res_cli['nome'];
	$tel_cliente = $res_cli['contato']; // CORRIGIDO: O campo agora é 'contato'
}


// LÓGICA DE REFERÊNCIA (MANTIDA)
if ($id_ref != "" && $referencia == 'Venda') {
	$id_principal = $id_ref; // Usa o id de referência para buscar os itens da venda
} else {
	$id_principal = $id_receber; // Usa o id do registro 'receber'
}


// BUSCA NOME DA FORMA DE PAGAMENTO (SAÍDA)
$saida = '';
$stmt_saida = $pdo->prepare("SELECT nome FROM formas_pgto WHERE id = :saida_id");
$stmt_saida->bindValue(':saida_id', $saida_id, PDO::PARAM_INT);
$stmt_saida->execute();
$res_saida = $stmt_saida->fetch(PDO::FETCH_ASSOC);
if ($res_saida) {
	$saida = $res_saida['nome'];
}


// BUSCA NOME DA FORMA DE PAGAMENTO RESTANTE (ATENÇÃO: USA A COLUNA ASSUMIDA)
$forma_pgto_restante = '';
// Se a coluna 'forma_pgto_restante' for realmente um ID, a consulta abaixo funciona:
$stmt_rest = $pdo->prepare("SELECT nome FROM formas_pgto WHERE id = :forma_pgto_restante_id");
$stmt_rest->bindValue(':forma_pgto_restante_id', $forma_pgto_restante_id, PDO::PARAM_INT);
$stmt_rest->execute();
$res_rest = $stmt_rest->fetch(PDO::FETCH_ASSOC);
if ($res_rest) {
	$forma_pgto_restante = $res_rest['nome'];
}


// 5. INÍCIO DO HTML E CSS (MANTIDOS)
?>


<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<?php if (@$impressao_automatica == 'Sim' && @$_GET['imprimir'] != 'Não') { ?>
	<script type="text/javascript">
		$(document).ready(function() {
			// Pequeno delay para garantir que a página carregou antes de imprimir
			setTimeout(function() {
				window.print();
				window.close();
			}, 500);
		});
	</script>
<?php } ?>


<style type="text/css">
	* {
		margin: 0px;
		padding: 0px;
		background-color: #ffffff;
		font-color: #000;
		font-family: TimesNewRoman, Geneva, sans-serif;
	}

	.text {
		&-center {
			text-align: center;
		}
	}

	.ttu {
		text-transform: uppercase;
		font-weight: bold;
		font-size: 1.2em;
	}

	.printer-ticket {
		display: table !important;
		width: 100%;
		max-width: 400px;
		font-weight: light;
		line-height: 1.3em;
		padding: 0px;
		font-family: TimesNewRoman, Geneva, sans-serif;
		font-size: 11px;
		font-color: #000;
	}

	th {
		font-weight: inherit;
		padding: 5px;
		text-align: center;
		border-bottom: 1px dashed #000000;
	}

	.cor {
		color: #000000;
	}

	.margem-superior {
		padding-top: 5px;
	}

	}
</style>

<table class="printer-ticket">

	<tr>
		<td>
			<img style="margin-top: 10px; margin-left: 40px;" id="imag" src="<?php echo @$url_sistema ?>img/logo.jpg" width="220px">
		</td>
	</tr>

	<tr>
		<th class="ttu" class="title" colspan="3"></th>
	</tr>
	<tr style="font-size: 10px">
		<th colspan="3">
			<?php echo @$endereco_sistema ?> <br />
			<?php if (@$cnpj_sistema != "") { ?> CNPJ <?php echo @$cnpj_sistema ?><?php } ?><br />
				Contato: <?php echo @$telefone_sistema ?>
		</th>
	</tr>

	<tr>
		<th colspan="3">Cliente <?php echo $nome_cliente ?> - Data: <?php echo $data_lancF ?>
			<br>
			Venda: <?php echo $id_principal ?> - <?php if ($cancelada == 'Sim') {
														echo 'CANCELADA';
													} else { ?>Pago : <?php echo $pago ?> <?php } ?>


		</th>
	</tr>

	<tr>
		<th class="ttu margem-superior" colspan="3">
			Comprovante de Venda

		</th>
	</tr>
	<tr>
		<?php if ($garantia_venda != '') { ?>
			<th colspan="3">
				Garantia de <?php echo $garantia_venda ?> Dias
			</th>
		<?php } else { ?>
			<th colspan="3">
				CUMPOM NÃO FISCAL

			</th>
		<?php } ?>
	</tr>

	<tbody>

		<?php
		// 6. BUSCA ITENS DA VENDA - SEGURANÇA: Prepared Statement
		$stmt_itens = $pdo->prepare("SELECT * FROM itens_venda WHERE id_venda = :id_venda ORDER BY id ASC");
		$stmt_itens->bindValue(':id_venda', $id_principal, PDO::PARAM_INT);
		$stmt_itens->execute();
		$dados_itens = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);

		$total_itens = 0; // Total bruto dos itens

		if (count($dados_itens) > 0) {
			foreach ($dados_itens as $item) {

				$id_produto = $item['material'];
				$quantidade = $item['quantidade']; // Quantidade de itens (coluna 'quantidade')
				$valor_unitario = $item['valor']; // Valor unitário (coluna 'valor')

				// CORREÇÃO: Removemos toda a lógica de busca por unidade.
				// A sigla será padronizada para UNID.
				$sigla_unidade = ' (UNID)';


				// 7. BUSCA APENAS O NOME DO MATERIAL (Tabela materiais) - SEGURANÇA: Prepared Statement
				// Não busca mais 'unidade' ou 'valor'
				$stmt_p = $pdo->prepare("SELECT nome FROM materiais WHERE id = :id_produto");
				$stmt_p->bindValue(':id_produto', $id_produto, PDO::PARAM_INT);
				$stmt_p->execute();
				$dados_p = $stmt_p->fetch(PDO::FETCH_ASSOC);

				$nome_produto = 'Produto Não Encontrado';

				if ($dados_p) {
					$nome_produto = $dados_p['nome'];
				}

				// Cálculo do total do item 
				$total_item = floatval($valor_unitario) * floatval($quantidade);
				$total_itens += $total_item;

				// 8. FORMATAÇÃO

				// Tratamento de Quantidade (mantido para remover o '.0')
				$qt = explode(".", $quantidade);
				// Se a parte decimal for zero, exibe o inteiro; se for maior que zero, formata com duas casas.
				$quantidadeF = (isset($qt[1]) && floatval($qt[1]) > 0) ? number_format(floatval($quantidade), 2, ',', '.') : $qt[0];

				// Formatação do total do item para exibição
				$total_itemF = number_format($total_item, 2, ',', '.');

		?>

				<tr>
					<td colspan="2" width="70%"> <?php echo $quantidadeF ?> <?php echo $sigla_unidade ?> - <?php echo $nome_produto ?>
					</td>
					<td align="right">R$ <?php echo $total_itemF; ?></td>
				</tr>

			<?php } // Fim do foreach
		} else { ?>
			<tr>
				<td colspan="3" align="center">Nenhum item encontrado para esta venda.</td>
			</tr>
		<?php } ?>

	</tbody>
	<tfoot>

		<?php
		// **INICIALIZAÇÃO DA VARIÁVEL CORRIGIDA para resolver o Warning!**
		$desconto_aplicado = 0.00;

		// Recálculo do desconto aplicado (para exibição)
		if ($tipo_desconto == '%' && floatval($desconto) > 0) {
			// Desconto percentual baseado no total bruto dos itens
			$desconto_aplicado = floatval($total_itens) * floatval($desconto) / 100;
		} else {
			// Desconto em valor fixo
			$desconto_aplicado = floatval($desconto);
		}

		$descontoF = number_format($desconto_aplicado, 2, ',', '.');
		$total_itensF = number_format($total_itens, 2, ',', '.');
		?>

		<tfoot>

			<tr>
				<th class="ttu" colspan="3" class="cor">
				</th>
			</tr>

			<?php if ($desconto_aplicado != 0 || floatval($frete) != 0) { // Exibe Total Bruto se houver descontos ou frete 
			?>
				<tr>
					<td colspan="2">Total Bruto</td>
					<td align="right">R$ <?php echo $total_itensF ?></td>
				</tr>
			<?php } ?>

			<?php if ($desconto_aplicado != 0) { ?>
				<tr>
					<?php if ($tipo_desconto == '%') { ?>
						<td colspan="2">Desconto (<?php echo $descontoFP ?>%)</td>
					<?php } else { ?>
						<td colspan="2">Desconto</td>
					<?php } ?>

					<td align="right">R$ <?php echo $descontoF ?></td>

				</tr>
			<?php } ?>


			<?php if (floatval($frete) != 0) { ?>
				<tr>
					<td colspan="2">Frete</td>
					<td align="right">R$ <?php echo $freteF ?></td>
				</tr>
			<?php } ?>


			</tr>

			<tr>
				<td colspan="2"><b>SubTotal</b></td>
				<?php
				// Manter a lógica original de exibição do valor.
				if (floatval($valor_restante) > 0) { ?>
					<td align="right"><b>R$ <?php echo $total_vendaF ?></b></td>
				<?php } else { ?>
					<td align="right"><b>R$ <?php echo $valorF ?></b></td>
				<?php } ?>
			</tr>

			<?php if (floatval($troco) != 0) { // Valor recebido (coluna 'troco' da tabela 'receber') 
			?>
				<tr>
					<td colspan="2">Valor Recebido</td>
					<td align="right">R$ <?php echo $trocoF ?></td>
				</tr>
			<?php } ?>

			<?php if (floatval($total_troco) > 0) { // Troco calculado no PHP 
			?>
				<tr>
					<td colspan="2">Troco</td>
					<td align="right">R$ <?php echo $total_trocoF ?></td>
				</tr>
			<?php } ?>

			<tr>
				<th class="ttu" colspan="3" class="cor">
				</th>
			</tr>


			<?php if (floatval($valor_restante) > 0) { ?>

				<tr>
					<td colspan="2">Pgto (R$ <?php echo $valorF ?>)</td>
					<td align="right"> <?php echo $saida ?> <?php echo $data_venc_1F ?></td>
				</tr>

				<tr>
					<td colspan="2">Restante (R$ <?php echo $valor_restanteF ?>)</td>
					<td align="right"> <?php echo $forma_pgto_restante ?> <?php echo $data_venc_2F ?></td>
				</tr>



			<?php } else { ?>

				<tr>
					<td colspan="2">Forma de Pagamento</td>
					<td align="right"><?php echo $saida ?></td>
				</tr>

				<?php if ($pago == 'Não') { ?>
					<tr>
						<td colspan="2">Data de Vencimento</td>
						<td align="right"><?php echo $data_vencF ?></td>
					</tr>
				<?php } ?>

			<?php } ?>


			<tr>
				<td colspan="2">Vendedor</td>
				<td align="right"><?php echo $nome_usu_lanc ?></td>
			</tr>



		</tfoot>
</table>

<?php if ($pago == 'Não') { ?>
	<br><br>
	<div align="center">__________________________</div>
	<div align="center"><small>Assinatura do Cliente</small></div>
<?php } ?>