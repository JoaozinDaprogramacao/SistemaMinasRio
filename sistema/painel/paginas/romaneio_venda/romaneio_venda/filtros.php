
		<div class="row g-2 mb-3 mt-1 align-items-center">
			<!-- Filtro de Atacadista -->
			<div class="col-auto">
				<select name="cliente" id="cliente" class="form-select form-select-sm" onchange="buscar()">
					<option value="">Cliente</option>
					<?php
					$query = $pdo->query("SELECT * FROM clientes ORDER BY id DESC");
					$res = $query->fetchAll(PDO::FETCH_ASSOC);
					for ($i = 0; $i < @count($res); $i++) {
						echo '<option value="' . $res[$i]['id'] . '">' . $res[$i]['nome'] . ' - ' . $res[$i]['cpf'] . '</option>';
					}
					?>
				</select>

			</div>
			<!-- Filtro de Data Inicial -->
			<div class="col-auto">
				<input type="date" name="dataInicial" id="dataInicial" class="form-control form-control-sm" onchange="buscar()">
			</div>

			<!-- Filtro de Data Final -->
			<div class="col-auto">
				<input type="date" name="dataFinal" id="dataFinal" class="form-control form-control-sm" onchange="buscar()">
			</div>
		</div>
