<?php
$tabela = 'romaneio_venda';
require_once("../../../conexao.php");

$query = $pdo->query("SELECT r.id, r.nota_fiscal, c.nome as nome_atacadista 
                      FROM $tabela as r 
                      LEFT JOIN clientes as c ON r.atacadista = c.id 
                      ORDER BY r.id DESC");

$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = count($res);

if ($total_reg > 0) {
?>

  <table class="table table-bordered text-nowrap border-bottom dt-responsive" id="tabela_detalhes">
    <thead>
      <tr>
        <th align="center" width="5%" class="text-center">Selecionar</th>
        <th width="10%">ID</th>
        <th width="50%">Atacadista</th>
        <th width="15%">N. Fiscal</th>
        <th width="20%">Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php
      for ($i = 0; $i < $total_reg; $i++) {
        $id = $res[$i]['id'];
        $nota_fiscal = $res[$i]['nota_fiscal'];
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
  echo '<div class="alert alert-warning">Nenhum registro encontrado!</div>';
}
?>

<script type="text/javascript">
  $(document).ready(function() {
    $('#tabela_detalhes').DataTable({
      "ordering": false,
      "stateSave": true,
      "language": {
        "url": "//cdn.datatables.net/plug-ins/1.13.2/i18n/pt-BR.json"
      }
    });
  });
</script>