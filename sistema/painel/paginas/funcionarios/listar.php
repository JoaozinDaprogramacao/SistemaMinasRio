<?php
$tabela = 'funcionarios';
require_once("../../../conexao.php");

// Query atualizada para buscar o nome do cargo junto com os dados do funcionário
$query = $pdo->query("SELECT f.*, c.nome as nome_cargo FROM {$tabela} f LEFT JOIN cargos c ON f.cargo = c.id ORDER BY f.id DESC");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$linhas = @count($res);

if ($linhas > 0) {
    echo <<<HTML
    <table class="table table-bordered text-nowrap border-bottom dt-responsive" id="tabela">
    <thead> 
    <tr> 
    <th align="center" width="5%" class="text-center">Selecionar</th>
    <th>Nome</th> 
    <th class="esc">Cargo</th> 
    <th class="esc">Salário Folha</th>
    <th class="esc">Status</th>
    <th>Ações</th>
    </tr> 
    </thead> 
    <tbody> 
HTML;

    for ($i = 0; $i < $linhas; $i++) {
        // Coleta de todos os dados do banco
        $id = $res[$i]['id'];
        $nome = $res[$i]['nome'];
        $telefone = $res[$i]['telefone'];
        $foto = $res[$i]['foto'];
        $endereco = $res[$i]['endereco'];
        $data_cad = $res[$i]['data_cad'];
        $chave_pix = $res[$i]['chave_pix'];
        
        $cargo_id = $res[$i]['cargo'];
        $cargo_nome = $res[$i]['nome_cargo'];
        $data_admissao = $res[$i]['data_admissao'];
        $status = $res[$i]['status'];
        $data_demissao = $res[$i]['data_demissao'];
        $descricao_salario = $res[$i]['descricao_salario'];
        $salario_folha = $res[$i]['salario_folha'];
        $obs = $res[$i]['obs'];
        
        // Formatação de dados para exibição
        $data_cadF = implode('/', array_reverse(@explode('-', $data_cad)));
        $data_admissaoF = implode('/', array_reverse(@explode('-', $data_admissao)));
        $data_demissaoF = $data_demissao ? implode('/', array_reverse(@explode('-', $data_demissao))) : 'N/A';
        $salario_folhaF = 'R$ ' . number_format($salario_folha, 2, ',', '.');
        
        $classe_status = $status == 'Ativo' ? 'text-success' : 'text-danger';
        $icone_status = $status == 'Ativo' ? 'fa-check-square' : 'fa-square-o';
        $titulo_status = $status == 'Ativo' ? 'Desativar Funcionário' : 'Ativar Funcionário';
        $acao_status = $status == 'Ativo' ? 'Demitido' : 'Ativo';
        
        // Preparando dados para passar ao Javascript
        $nome_js = htmlspecialchars($nome, ENT_QUOTES);
        $endereco_js = htmlspecialchars($endereco, ENT_QUOTES);
        $obs_js = htmlspecialchars($obs, ENT_QUOTES);

        echo <<<HTML
<tr>
<td align="center">
<div class="custom-checkbox custom-control">
<input type="checkbox" class="custom-control-input" id="seletor-{$id}" onchange="selecionar('{$id}')">
<label for="seletor-{$id}" class="custom-control-label mt-1 text-dark"></label>
</div>
</td>
<td>{$nome}</td>
<td class="esc">{$cargo_nome}</td>
<td class="esc">{$salario_folhaF}</td>
<td class="esc"><span class="{$classe_status}">{$status}</span></td>
<td>
    <a class="btn btn-info btn-sm" href="#" onclick="editar('{$id}', '{$nome_js}', '{$telefone}', '{$endereco_js}', '{$chave_pix}', '{$cargo_id}', '{$data_admissao}', '{$status}', '{$data_demissao}', '{$descricao_salario}', '{$obs_js}', '{$foto}')" title="Editar Dados"><i class="fa fa-edit"></i></a>
    <a class="btn btn-primary btn-sm" href="#" onclick="mostrar('{$nome_js}', '{$telefone}', '{$endereco_js}', '{$chave_pix}', '{$cargo_nome}', '{$data_admissaoF}', '{$status}', '{$data_demissaoF}', '{$salario_folhaF}', '{$obs_js}', '{$foto}', '{$data_cadF}')" title="Mostrar Dados"><i class="fa fa-info-circle"></i></a>
    <a class="btn btn-secondary btn-sm" href="#" onclick="arquivo('{$id}', '{$nome_js}')" title="Anexar Arquivos"><i class="fa fa-paperclip"></i></a>
    
    <div class="dropdown" style="display: inline-block;">
        <button class="btn btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Mais Ações">
            <i class="fa fa-cogs"></i>
        </button>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="#" onclick="abrirModalGratificacao({$id}, '{$nome_js}')"><i class="fa fa-plus text-success"></i> Lançar Gratificação</a></li>
            <li><a class="dropdown-item" href="#" onclick="abrirModalAdiantamento({$id}, '{$nome_js}', '{$salario_folha}')"><i class="fa fa-dollar text-warning"></i> Lançar Vale</a></li>
            <li><a class="dropdown-item" href="#" onclick="abrirModalHistorico({$id}, '{$nome_js}')"><i class="fa fa-list text-info"></i> Ver Histórico</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="#" onclick="mudarStatus('{$id}', '{$acao_status}')" title="{$titulo_status}"><i class="fa {$icone_status} text-secondary"></i> Mudar Status</a></li>
            <li><a class="dropdown-item" href="#" onclick="excluir('{$id}')"><i class="fa fa-trash text-danger"></i> Excluir</a></li>
        </ul>
    </div>
</td>
</tr>
HTML;
    }
} else {
    echo 'Não possui nenhum cadastro!';
}

echo <<<HTML
</tbody>
<small><div align="center" id="mensagem-excluir"></div></small>
</table>
HTML;
?>

<script type="text/javascript">
    $(document).ready(function() {
        $('#tabela').DataTable({
            "language": {},
            "ordering": false,
            "stateSave": true
        });
    });
</script>

<script type="text/javascript">
    // FUNÇÃO EDITAR CORRIGIDA
    function editar(id, nome, telefone, endereco, chave_pix, cargo_id, data_admissao, status, data_demissao, descricao_salario, obs, foto) {
        
        $('#mensagem').text('');
        $('#titulo_inserir').text('Editar Registro');

        $('#id').val(id);
        $('#nome').val(nome);
        $('#telefone').val(telefone);
        $('#endereco').val(endereco);
        $('#chave_pix').val(chave_pix);
        $('#cargo').val(cargo_id).change();
        $('#data_admissao').val(data_admissao);
        $('#status').val(status).change();
        $('#data_demissao').val(data_demissao);
        $('#descricao_salario').val(descricao_salario);
        $('#obs').val(obs);

        // ATUALIZA A IMAGEM DE PREVIEW
        $('#preview-foto').attr('src', 'images/funcionarios/' + foto);

        toggleDemissao();
        mascara_decimal_ponto(document.getElementById('descricao_salario'));
        calcularSalarioFolha();

        $('#modalForm').modal('show');
    }

    function mostrar(nome, telefone, endereco, chave_pix, cargo, data_admissao, status, data_demissao, salario_folha, obs, foto, data_cad) {
        $('#titulo_dados').text(nome);
        $('#telefone_dados').text(telefone);
        $('#endereco_dados').text(endereco);
        $('#pix_dados').text(chave_pix);
        $('#cargo_dados').text(cargo);
        $('#admissao_dados').text(data_admissao);
        $('#status_dados').text(status);
        $('#demissao_dados').text(data_demissao);
        $('#salario_dados').text(salario_folha);
        $('#obs_dados').text(obs);
        $('#data_cad_dados').text(data_cad);
        $('#foto_dados').attr("src", "images/funcionarios/" + foto);
        $('#modalDados').modal('show');
    }

    function limparCampos() {
        $('#id').val('');
        $('#nome').val('');
        $('#telefone').val('');
        $('#endereco').val('');
        $('#chave_pix').val('');
        $('#data_admissao').val('<?php echo date('Y-m-d'); ?>');
        $('#data_demissao').val('');
        $('#descricao_salario').val('');
        $('#salario_folha').val('');
        $('#obs').val('');
        $('#ids').val('');
        $('#btn-deletar').hide();
        $('#status').val('Ativo');
        toggleDemissao();
        if (window.resetPreviewFoto) {
            window.resetPreviewFoto();
        }
    }
    
    function mudarStatus(id, novo_status) {
        $.ajax({
            url: 'paginas/' + pag + "/mudar-status.php",
            method: 'POST',
            data: { id: id, status: novo_status },
            dataType: "html",
            success: function(mensagem) {
                if (mensagem.trim() == "Alterado com Sucesso") {
                    listar();
                } else {
                    $('#mensagem-excluir').addClass('text-danger').text(mensagem);
                }
            }
        });
    }

    function selecionar(id) {
        var ids = $('#ids').val();
        if ($('#seletor-' + id).is(":checked")) {
            $('#ids').val(ids + id + '-');
        } else {
            $('#ids').val(ids.replace(id + '-', ''));
        }
        if ($('#ids').val() == "") {
            $('#btn-deletar').hide();
        } else {
            $('#btn-deletar').show();
        }
    }

    function deletarSel() {
        var ids = $('#ids').val().split('-');
        for (i = 0; i < ids.length - 1; i++) {
            excluir(ids[i]);
        }
        setTimeout(() => { listar(); }, 1000);
        limparCampos();
    }

    function arquivo(id, nome) {
        $('#id-arquivo').val(id);
        $('#nome-arquivo').text(nome);
        $('#modalArquivos').modal('show');
        $('#mensagem-arquivo').text('');
        $('#arquivo_conta').val('');
        listarArquivos();
    }
</script>