<?php 

@session_start();
$id_usuario = $_SESSION['id'];

$home = 'ocultar';
$configuracoes = 'ocultar';
$caixas = 'ocultar';
$tarefas = 'ocultar';
$lancar_tarefas = 'ocultar';
$orcamento = 'ocultar';
$vendas = 'ocultar';
$inserir_orcamentos = 'ocultar';

//grupo pessoas
$usuarios = 'ocultar';
$fornecedores = 'ocultar';
$funcionarios = 'ocultar';
$clientes = 'ocultar';

//grupo cadastros
$grupo_acessos = 'ocultar';
$acessos = 'ocultar';
$frequencias = 'ocultar';
$cargos = 'ocultar';
$formas_pgto = 'ocultar';
$unidade_medida = 'ocultar';

//grupo financeiro
$bancos = 'bancos';
$receber = 'ocultar';
$pagar = 'ocultar';
$rel_financeiro = 'ocultar';
$rel_sintetico_despesas = 'ocultar';
$rel_sintetico_receber = 'ocultar';
$rel_balanco = 'ocultar';
$rel_inadimplementes = 'ocultar';
$lista_vendas = 'ocultar';
$comissoes = 'ocultar';

//grupo produtos
$produtos = 'ocultar';
$romaneio_venda = 'ocultar';
$categorias = 'ocultar';
$entradas = 'ocultar';
$saidas = 'ocultar';
$estoque = 'ocultar';
$ordem_compra = 'ocultar';

$dre = 'ocultar';

$query = $pdo->query("SELECT * FROM usuarios_permissoes where usuario = '$id_usuario'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){
	for($i=0; $i < $total_reg; $i++){
		foreach ($res[$i] as $key => $value){}
		$permissao = $res[$i]['permissao'];

		$query2 = $pdo->query("SELECT * FROM acessos where id = '$permissao'");
		$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
		$nome = $res2[0]['nome'];
		$chave = $res2[0]['chave'];
		$id = $res2[0]['id'];

		if($chave == 'home'){
			$home = '';
		}

		if($chave == 'configuracoes'){
			$configuracoes = '';
		}

		if($chave == 'caixas'){
			$caixas = '';
		}

		if($chave == 'tarefas'){
			$tarefas = '';
		}

		if($chave == 'lancar_tarefas'){
			$lancar_tarefas = '';
		}

		if($chave == 'vendas'){
			$vendas = '';
		}

		if($chave == 'orcamento'){
			$orcamento = '';
		}

		if($chave == 'inserir_orcamentos'){
			$inserir_orcamentos = '';
		}


	
		if($chave == 'usuarios'){
			$usuarios = '';
		}

		if($chave == 'fornecedores'){
			$fornecedores = '';
		}

		if($chave == 'funcionarios'){
			$funcionarios = '';
		}

		if($chave == 'clientes'){
			$clientes = '';
		}


		if($chave == 'grupo_acessos'){
			$grupo_acessos = '';
		}

		if($chave == 'acessos'){
			$acessos = '';
		}

		if($chave == 'frequencias'){
			$frequencias = '';
		}

		if($chave == 'cargos'){
			$cargos = '';
		}

		if($chave == 'formas_pgto'){
			$formas_pgto = '';
		}


		if($chave == 'unidade_medida'){
			$unidade_medida = '';
		}
		
		if($chave == 'bancos'){
			$bancos = '';
		}

		if($chave == 'receber'){
			$receber = '';
		}


		if($chave == 'pagar'){
			$pagar = '';
		}

		if($chave == 'rel_financeiro'){
			$rel_financeiro = '';
		}

		if($chave == 'rel_sintetico_receber'){
			$rel_sintetico_receber = '';
		}

		if($chave == 'rel_sintetico_despesas'){
			$rel_sintetico_despesas = '';
		}

		if($chave == 'rel_balanco'){
			$rel_balanco = '';
		}

		if($chave == 'rel_inadimplementes'){
			$rel_inadimplementes = '';
		}

		if($chave == 'lista_vendas'){
			$lista_vendas = '';
		}

		if($chave == 'comissoes'){
			$comissoes = '';
		}







		if($chave == 'produtos'){
			$produtos = '';
		}

		if($chave == 'categorias'){
			$categorias = '';
		}

		if($chave == 'entradas'){
			$entradas = '';
		}

		if($chave == 'saidas'){
			$saidas = '';
		}

		if($chave == 'estoque'){
			$estoque = '';
		}

		if($chave == 'ordem_compra'){
			$ordem_compra = '';
		}



		if($chave == 'romaneio_venda'){
			$romaneio_venda = '';
		}

		if($chave == 'dre'){
			$dre = '';
		}

	}

}



$pag_inicial = '';
if($home != 'ocultar'){
	$pag_inicial = 'home';
}else{
	$query = $pdo->query("SELECT * FROM usuarios_permissoes where usuario = '$id_usuario'");
	$res = $query->fetchAll(PDO::FETCH_ASSOC);
	$total_reg = @count($res);	
	if($total_reg > 0){
		for($i=0; $i<$total_reg; $i++){
			$permissao = $res[$i]['permissao'];		
			$query2 = $pdo->query("SELECT * FROM acessos where id = '$permissao'");
			$res2 = $query2->fetchAll(PDO::FETCH_ASSOC);
			if($res2[0]['pagina'] == 'Não'){
				continue;
			}else{
				$pag_inicial = $res2[0]['chave'];
				break;
			}	
				
		}
				

	}else{
		echo 'Você não tem permissão para acessar nenhuma página, acione o administrador!';
		echo '<br>';
		echo '<a href="../index.php">Clique aqui</a> para ir para o Login!';
		echo "<script>localStorage.setItem('id_usu', '')</script>";
		exit();
	}
}



if($usuarios == 'ocultar' and $funcionarios == 'ocultar' and $fornecedores == 'ocultar' and $clientes == 'ocultar'){
	$menu_pessoas = 'ocultar';
}else{
	$menu_pessoas = '';
}


if($grupo_acessos == 'ocultar' and $acessos == 'ocultar' and $cargos == 'ocultar' and $frequencias == 'ocultar' and $formas_pgto == 'ocultar' and $unidade_medida == 'ocultar'){
	$menu_cadastros = 'ocultar';
}else{
	$menu_cadastros = '';
}


if($receber == 'ocultar' and $pagar == 'ocultar' and $rel_balanco == 'ocultar' and $rel_sintetico_despesas == 'ocultar' and $rel_sintetico_receber == 'ocultar' and $rel_financeiro == 'ocultar' and $rel_inadimplementes == 'ocultar' and $lista_vendas == 'ocultar' and $comissoes == 'ocultar' and $dre == 'ocultar'){
	$menu_financeiro = 'ocultar';
}else{
	$menu_financeiro = '';
}


if($produtos == 'ocultar' and $categorias == 'ocultar' and $entradas == 'ocultar' and $saidas == 'ocultar' and $estoque == 'ocultar' and $ordem_compra == 'ocultar'){
	$menu_produtos = 'ocultar';
}else{
	$menu_produtos = '';
}


?>