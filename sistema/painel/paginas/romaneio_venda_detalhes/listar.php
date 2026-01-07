<?php
$tabela = 'romaneio_venda';
require_once("../../../conexao.php"); // Verifique se o caminho da conexão está correto

$dataInicial = @$_POST['dataInicial'];
$dataFinal = @$_POST['dataFinal'];
$cliente = @$_POST['cliente'];

// Montagem dinâmica da query
$query_cont = " WHERE 1=1 ";

if ($cliente != "") {
  $query_cont .= " AND r.atacadista = :cliente ";
}

if ($dataInicial != "") {
  $query_cont .= " AND r.data >= :dataInicial ";
}

if ($dataFinal != "") {
  $query_cont .= " AND r.data <= :dataFinal ";
}

$sql = "SELECT r.*, c.nome as nome_atacadista 
        FROM $tabela as r 
        LEFT JOIN clientes as c ON r.atacadista = c.id 
        $query_cont 
        ORDER BY r.id DESC";

$query = $pdo->prepare($sql);

// Bind dos parâmetros
if ($cliente != "") {
  $query->bindValue(":cliente", "$cliente");
}
if ($dataInicial != "") {
  $query->bindValue(":dataInicial", "$dataInicial");
}
if ($dataFinal != "") {
  $query->bindValue(":dataFinal", "$dataFinal");
}

$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = count($res);

if ($total_reg > 0) {
?>
  <table class="table table-bordered text-nowrap border-bottom dt-responsive" id="tabela_detalhes">
    <thead>
      <tr>
        <th align="center" width="5%" class="text-center">Selecionar</th>
        <th width="10%">ID</th>
        <th width="40%">Atacadista</th>
        <th width="15%">Data</th>
        <th width="15%">N. Fiscal</th>
        <th width="15%">Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php
      for ($i = 0; $i < $total_reg; $i++) {
        $id = $res[$i]['id'];
        $nota_fiscal = $res[$i]['nota_fiscal'];
        $data_f = implode('/', array_reverse(explode('-', $res[$i]['data'])));
        $atacadista = $res[$i]['nome_atacadista'] ? $res[$i]['nome_atacadista'] : 'Não Encontrado';
      ?>
        <tr>
          <td align="center">
            <div class="custom-checkbox custom-control">
              <input type="checkbox" class="custom-control-input" id="seletor-<?php echo $id ?>" onchange="selecionar('<?php echo $id ?>')">
              <label for="seletor-<?php echo $id ?>" class="custom-control-label mt-1 text-dark"></label>
            </div>
          </td>
          <td><?php echo $id ?></td>
          <td><?php echo $atacadista ?></td>
          <td><?php echo $data_f ?></td>
          <td><?php echo $nota_fiscal ?></td>
          <td>
            <big><a class="btn btn-primary btn-sm" href="#" onclick="mostrar('<?php echo $id ?>')" title="Mostrar Dados"><i class="fa fa-info-circle"></i></a></big>
          </td>
        </tr>
      <?php } ?>
    </tbody>
  </table>

<?php
} else {
  echo '<div class="alert alert-warning">Nenhum registro encontrado para este filtro!</div>';
}
?>

<script type="text/javascript">
  $(document).ready(function() {
    // Verifica se a tabela existe para evitar erros
    if ($.fn.DataTable.isDataTable('#tabela_detalhes')) {
      $('#tabela_detalhes').DataTable().destroy();
    }

    $('#tabela_detalhes').DataTable({
      "destroy": true, // Permite que a tabela seja recriada ao filtrar
      "ordering": false,
      "stateSave": true,
      "language": {
        "url": "//cdn.datatables.net/plug-ins/1.13.2/i18n/pt-BR.json"
      }
    });
  });
</script>