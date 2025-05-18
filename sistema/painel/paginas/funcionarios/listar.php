<?php 
$tabela = 'funcionarios';
require_once("../../../conexao.php");
require_once("../../verificar.php");

$query = $pdo->query("SELECT * from $tabela ORDER BY id DESC");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);

if($linhas > 0){
echo <<<HTML
	<table class="table table-bordered text-nowrap border-bottom dt-responsive" id="tabela">
	<thead> 
	<tr> 
	<th align="center" width="5%" class="text-center">Selecionar</th>
	<th>Nome</th>	
	<th class="esc">Telefone</th>	
	<th class="esc">Email</th>	
	<th class="esc">Foto</th>	
	<th>Ações</th>
	</tr> 
	</thead> 
	<tbody>	
HTML;

for($i=0; $i<$linhas; $i++){
	$id = $res[$i]['id'];
	$nome = $res[$i]['nome'];
	$telefone = $res[$i]['telefone'];
	$email = $res[$i]['email'];
	$foto = $res[$i]['foto'];
	$endereco = $res[$i]['endereco'];
	$ativo = $res[$i]['ativo'];
	$data = $res[$i]['data_cad'];	
	$chave_pix = $res[$i]['chave_pix'];
	$comissao = $res[$i]['comissao'];

	$dataF = implode('/', array_reverse(@explode('-', $data)));

	$classe_ativo = $ativo == 'Sim' ? '' : '#c4c4c4';
	$icone = $ativo == 'Sim' ? 'fa-check-square' : 'fa-square-o';
	$titulo_link = $ativo == 'Sim' ? 'Desativar Funcionário' : 'Ativar Funcionário';
	$acao = $ativo == 'Sim' ? 'Não' : 'Sim';

	$emailF = str_replace(substr($email, -13), '*************', $email);
	$telefoneF = str_replace(substr($telefone, -5), '*****', $telefone);

echo <<<HTML
<tr>
<td align="center">
<div class="custom-checkbox custom-control">
<input type="checkbox" class="custom-control-input" id="seletor-{$id}" onchange="selecionar('{$id}')">
<label for="seletor-{$id}" class="custom-control-label mt-1 text-dark"></label>
</div>
</td>
<td style="color:{$classe_ativo}">{$nome}</td>
<td style="color:{$classe_ativo}" class="esc">{$telefoneF}</td>
<td style="color:{$classe_ativo}" class="esc">{$emailF}</td>
<td style="color:{$classe_ativo}" class="esc"><img src="images/perfil/{$foto}" width="25px"></td>
<td>
	<big><a class="btn btn-info btn-sm" href="#" onclick="editar('{$id}','{$nome}','{$email}','{$telefone}','{$endereco}','{$chave_pix}','{$comissao}')" title="Editar Dados"><i class="fa fa-edit"></i></a></big>

	<div class="dropdown" style="display: inline-block;">                      
		<a class="btn btn-danger btn-sm" href="#" data-bs-toggle="dropdown"><i class="fa fa-trash"></i></a>
		<div class="dropdown-menu tx-13">
			<div class="dropdown-item-text botao_excluir">
				<p>Confirmar Exclusão? <a href="#" onclick="excluir('{$id}')"><span class="text-danger">Sim</span></a></p>
			</div>
		</div>
	</div>

	<big><a class="btn btn-primary btn-sm" href="#" onclick="mostrar('{$nome}','{$email}','{$telefone}','{$endereco}','{$ativo}','{$dataF}','{$foto}','{$chave_pix}','{$comissao}')" title="Mostrar Dados"><i class="fa fa-info-circle"></i></a></big>

	<big><a class="btn btn-success btn-sm" href="#" onclick="ativar('{$id}', '{$acao}')" title="{$titulo_link}"><i class="fa {$icone}"></i></a></big>

	<big><a class="btn btn-primary btn-sm" href="#" onclick="arquivo('{$id}', '{$nome}')" title="Inserir / Ver Arquivos"><i class="fa fa-file-o"></i></a></big>
</td>
</tr>
HTML;
}
}else{
	echo 'Não possui nenhum cadastro!';
}

echo <<<HTML
</tbody>
<small><div align="center" id="mensagem-excluir"></div></small>
</table>
HTML;
?>

<!-- Scripts DataTable e JS de interação permanecem iguais -->




<script type="text/javascript">
	$(document).ready( function () {		
    $('#tabela').DataTable({
    	"language" : {
            //"url" : '//cdn.datatables.net/plug-ins/1.13.2/i18n/pt-BR.json'
        },
        "ordering": false,
		"stateSave": true
    });
} );
</script>

<script type="text/javascript">
	function editar(id, nome, email, telefone, endereco, nivel, chave_pix, comissao){
		$('#mensagem').text('');
    	$('#titulo_inserir').text('Editar Registro');

    	$('#id').val(id);
    	$('#nome').val(nome);
    	$('#email').val(email);
    	$('#telefone').val(telefone);
    	$('#endereco').val(endereco);
    	$('#nivel').val(nivel).change();


    	$('#comissao').val(comissao);
    	mascara_decimal('comissao');
    	
    	$('#chave_pix').val(chave_pix);

    	$('#modalForm').modal('show');
	}


	function mostrar(nome, email, telefone, endereco, ativo, data,  nivel, foto, chave_pix, comissao){
		    	
    	$('#titulo_dados').text(nome);
    	$('#email_dados').text(email);
    	$('#telefone_dados').text(telefone);
    	$('#endereco_dados').text(endereco);
    	$('#ativo_dados').text(ativo);
    	$('#data_dados').text(data);
    	$('#comissao_dados').text(comissao);
    	
    	$('#nivel_dados').text(nivel);
    	$('#foto_dados').attr("src", "images/perfil/" + foto);
    	
    	$('#pix_dados').text(chave_pix);

    	$('#modalDados').modal('show');
	}

	function limparCampos(){
		var comissao_sistema = "<?=$comissao_sistema?>";
		$('#id').val('');
    	$('#nome').val('');
    	$('#email').val('');
    	$('#telefone').val('');
    	$('#endereco').val('');
    	$('#comissao').val(comissao_sistema);
    	mascara_decimal('comissao');
    	
    	$('#chave_pix').val('');

    	$('#ids').val('');
    	$('#btn-deletar').hide();	
	}

	function selecionar(id){

		var ids = $('#ids').val();

		if($('#seletor-'+id).is(":checked") == true){
			var novo_id = ids + id + '-';
			$('#ids').val(novo_id);
		}else{
			var retirar = ids.replace(id + '-', '');
			$('#ids').val(retirar);
		}

		var ids_final = $('#ids').val();
		if(ids_final == ""){
			$('#btn-deletar').hide();
		}else{
			$('#btn-deletar').show();
		}
	}

	function deletarSel(){
		var ids = $('#ids').val();
		var id = ids.split("-");
		
		for(i=0; i<id.length-1; i++){
			excluirMultiplos(id[i]);			
		}

		setTimeout(() => {
		  	listar();	
		}, 1000);

		limparCampos();
	}


	function arquivo(id, nome){
    $('#id-arquivo').val(id);    
    $('#nome-arquivo').text(nome);
    $('#modalArquivos').modal('show');
    $('#mensagem-arquivo').text(''); 
    $('#arquivo_conta').val('');
    listarArquivos();   
}
</script>