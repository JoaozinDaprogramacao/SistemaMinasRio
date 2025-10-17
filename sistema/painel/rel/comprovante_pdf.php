<?php
// ###################################################################################
// INÍCIO DO BLOCO DE GERAÇÃO DE PDF (REQUER DOMPDF)
// ###################################################################################
require_once '../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Inicia o buffer de saída para capturar o HTML (Necessário para o Dompdf)
ob_start();
// ###################################################################################


// 1. INCLUSÃO E VARIÁVEIS INICIAIS
include('../../conexao.php');

// Define um valor padrão para impressao_automatica (não será usado no PDF, mas mantido)
$impressao_automatica = @$impressao_automatica ?? 'Não';

// Coleta e sanitiza o ID da URL, garantindo que seja um inteiro
$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

// Verifica se o ID é válido
if (!$id) {
    if (!headers_sent()) {
        header("HTTP/1.0 400 Bad Request");
    }
    die('ID de registro inválido ou não fornecido.');
}

// 2. BUSCAR AS INFORMAÇÕES DO REGISTRO (Tabela receber) - SEGURANÇA: Prepared Statement
try {
    $stmt = $pdo->prepare("SELECT * FROM receber WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($res) === 0) {
        die('Registro não encontrado na tabela "receber".');
    }

    // Atribuição de variáveis
    $dados = $res[0];

    $id_receber = $dados['id'];
    $descricao = $dados['descricao'];
    $cliente = $dados['cliente'];
    $valor = $dados['valor'];
    $data_lanc = $dados['data_lanc'];
    $data_venc = $dados['vencimento'];
    $data_pgto = $dados['data_pgto'];
    $usuario_lanc = $dados['usuario_lanc'];
    $usuario_pgto = $dados['usuario_pgto'];
    $frequencia = $dados['frequencia'];
    $saida_id = $dados['forma_pgto'];
    $arquivo = $dados['arquivo'];
    $pago = $dados['pago'];
    $obs = $dados['obs'];
    $desconto = $dados['desconto'];
    $troco = $dados['troco'];
    $hora = $dados['hora'];
    $cancelada = $dados['cancelada'];
    $tipo_desconto = $dados['tipo_desconto'];
    $total_venda = $dados['subtotal'];
    $valor_restante = $dados['valor_restante'];
    $forma_pgto_restante_id = $dados['forma_pgto_restante'];
    $data_restante = $dados['data_restante'];
    $id_ref = $dados['id_ref'];
    $referencia = $dados['referencia'];
    $frete = $dados['frete'];
    $garantia_venda = '';
} catch (PDOException $e) {
    die("Erro ao buscar dados do registro: " . $e->getMessage());
}

// 3. CÁLCULOS E FORMATAÇÕES DE DATAS E VALORES

// Lógica de Vencimento
$data_venc_1 = (strtotime($data_venc) > strtotime($data_lanc)) ? $data_venc : '';
$data_venc_2 = (strtotime($data_restante) > strtotime($data_lanc)) ? $data_restante : '';

// Cálculo de Troco
$total_troco = max(0, floatval($troco) - floatval($valor));


// Formatação de Datas (Usando função de alta ordem para clareza)
$format_date = function ($date) {
    return $date ? implode('/', array_reverse(explode('-', $date))) : '';
};

$data_venc_1F = $format_date($data_venc_1);
$data_venc_2F = $format_date($data_venc_2);
$data_lancF = $format_date($data_lanc);
$data_vencF = $format_date($data_venc);
$data_pgtoF = $format_date($data_pgto);

// Formatação de Valores
$format_value = fn($val) => number_format(floatval($val), 2, ',', '.');

$valorF = $format_value($valor);
$trocoF = $format_value($troco);
$total_trocoF = $format_value($total_troco);
$total_vendaF = $format_value($total_venda);
$valor_restanteF = $format_value($valor_restante);

// Desconto Percentual para exibição
$descontoFP = number_format(floatval($desconto), 0, ',', '.');

$freteF = $format_value($frete);

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


// BUSCA DADOS DO CLIENTE (Corrigido para coluna 'contato')
$nome_cliente = 'Não Informado';
$tel_cliente = '';
$stmt_cli = $pdo->prepare("SELECT nome, contato FROM clientes WHERE id = :cliente");
$stmt_cli->bindValue(':cliente', $cliente, PDO::PARAM_INT);
$stmt_cli->execute();
$res_cli = $stmt_cli->fetch(PDO::FETCH_ASSOC);
if ($res_cli) {
    $nome_cliente = $res_cli['nome'];
    $tel_cliente = $res_cli['contato'];
}


// LÓGICA DE REFERÊNCIA (MANTIDA)
$id_principal = ($id_ref != "" && $referencia == 'Venda') ? $id_ref : $id_receber;


// BUSCA NOME DA FORMA DE PAGAMENTO (SAÍDA)
$saida = '';
$stmt_saida = $pdo->prepare("SELECT nome FROM formas_pgto WHERE id = :saida_id");
$stmt_saida->bindValue(':saida_id', $saida_id, PDO::PARAM_INT);
$stmt_saida->execute();
$res_saida = $stmt_saida->fetch(PDO::FETCH_ASSOC);
if ($res_saida) {
    $saida = $res_saida['nome'];
}


// BUSCA NOME DA FORMA DE PAGAMENTO RESTANTE
$forma_pgto_restante = '';
$stmt_rest = $pdo->prepare("SELECT nome FROM formas_pgto WHERE id = :forma_pgto_restante_id");
$stmt_rest->bindValue(':forma_pgto_restante_id', $forma_pgto_restante_id, PDO::PARAM_INT);
$stmt_rest->execute();
$res_rest = $stmt_rest->fetch(PDO::FETCH_ASSOC);
if ($res_rest) {
    $forma_pgto_restante = $res_rest['nome'];
}


// 5. INÍCIO DO HTML E CSS PARA PDF
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Comprovante de Venda #<?php echo $id_principal ?></title>
    <style type="text/css">
        /* CSS OTIMIZADO PARA DOMPDF */
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            margin: 0;
            padding: 0;
            color: #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }

        .header-info {
            font-size: 9pt;
            text-align: center;
        }

        .header-info th {
            padding: 5px 0;
            border-bottom: 1px solid #000;
        }

        .section-title {
            font-size: 12pt;
            font-weight: bold;
            text-align: center;
            padding: 8px 0;
            border-bottom: 2px solid #000;
            margin-top: 15px;
        }

        .item-table th,
        .item-table td {
            border-bottom: 1px dashed #ccc;
            padding: 5px 0;
            text-align: left;
        }

        .item-table th:nth-child(2),
        .item-table td:nth-child(2) {
            text-align: right;
        }

        .item-table th {
            font-weight: bold;
        }

        .totals-table td {
            padding: 3px 0;
            font-size: 10pt;
        }

        .totals-table .total-line td {
            border-top: 1px solid #000;
            padding-top: 5px;
            font-size: 11pt;
            font-weight: bold;
        }

        .footer-signature {
            margin-top: 50px;
            text-align: center;
        }

        .logo-container {
            text-align: center;
            padding-top: 10px;
        }
    </style>
</head>

<body>

    <div class="logo-container">
        <img id="imag" src="<?php echo @$url_sistema ?>img/logo.jpg" style="width: 220px; max-width: 100%;">
    </div>

    <table class="header-info">
        <tr>
            <th colspan="2">
                <?php echo @$endereco_sistema ?> <br />
                <?php if (@$cnpj_sistema != "") { ?> CNPJ <?php echo @$cnpj_sistema ?><?php } ?><br />
                    Contato: <?php echo @$telefone_sistema ?>
            </th>
        </tr>
    </table>

    <table style="width: 100%; margin-bottom: 10px;">
        <tr>
            <td style="width: 50%;">Cliente: <strong><?php echo $nome_cliente ?></strong></td>
            <td style="width: 50%; text-align: right;">Data: <?php echo $data_lancF ?></td>
        </tr>
        <tr>
            <td>Venda: <strong><?php echo $id_principal ?></strong></td>
            <td style="text-align: right;">Status:
                <?php
                if ($cancelada == 'Sim') {
                    echo 'CANCELADA';
                } else {
                    echo 'Pago: ' . $pago;
                }
                ?>
            </td>
        </tr>
    </table>


    <div class="section-title">
        Comprovante de Venda
        <?php if ($garantia_venda != '') { ?>
            <br><small>Garantia de <?php echo $garantia_venda ?> Dias</small>
        <?php } else { ?>
            <br><small>CUPOM NÃO FISCAL</small>
        <?php } ?>
    </div>


    <table class="item-table" style="margin-top: 15px;">
        <thead>
            <tr>
                <th style="width: 70%;">Descrição (Qtd)</th>
                <th style="width: 30%; text-align: right;">Total</th>
            </tr>
        </thead>
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
                    $quantidade = $item['quantidade'];
                    $valor_unitario = $item['valor'];
                    $sigla_unidade = ' (UNID)'; // Padronizado para (UNID)


                    // 7. BUSCA APENAS O NOME DO MATERIAL (Tabela materiais) - SEGURANÇA: Prepared Statement
                    $stmt_p = $pdo->prepare("SELECT nome FROM materiais WHERE id = :id_produto");
                    $stmt_p->bindValue(':id_produto', $id_produto, PDO::PARAM_INT);
                    $stmt_p->execute();
                    $dados_p = $stmt_p->fetch(PDO::FETCH_ASSOC);

                    $nome_produto = $dados_p ? $dados_p['nome'] : 'Produto Não Encontrado';

                    // Cálculo do total do item 
                    $total_item = floatval($valor_unitario) * floatval($quantidade);
                    $total_itens += $total_item;

                    // 8. FORMATAÇÃO
                    $qt = explode(".", $quantidade);
                    $quantidadeF = (isset($qt[1]) && floatval($qt[1]) > 0) ? number_format(floatval($quantidade), 2, ',', '.') : $qt[0];
                    $total_itemF = number_format($total_item, 2, ',', '.');
            ?>

                    <tr>
                        <td><?php echo $nome_produto ?> (<?php echo $quantidadeF ?> <?php echo trim($sigla_unidade) ?>)</td>
                        <td style="text-align: right;">R$ <?php echo $total_itemF; ?></td>
                    </tr>

                <?php } // Fim do foreach
            } else { ?>
                <tr>
                    <td colspan="2" style="text-align: center;">Nenhum item encontrado para esta venda.</td>
                </tr>
            <?php } ?>

        </tbody>
    </table>

    <table class="totals-table" style="margin-top: 15px;">

        <?php
        // **INICIALIZAÇÃO CORRIGIDA**
        $desconto_aplicado = 0.00;

        // Recálculo do desconto aplicado (para exibição)
        if ($tipo_desconto == '%' && floatval($desconto) > 0) {
            $desconto_aplicado = floatval($total_itens) * floatval($desconto) / 100;
        } else {
            $desconto_aplicado = floatval($desconto);
        }

        $descontoF = number_format($desconto_aplicado, 2, ',', '.');
        $total_itensF = number_format($total_itens, 2, ',', '.'); // Total Bruto
        ?>

        <?php if ($desconto_aplicado != 0 || floatval($frete) != 0) { ?>
            <tr>
                <td style="width: 60%;">Total Bruto</td>
                <td style="width: 40%; text-align: right;">R$ <?php echo $total_itensF ?></td>
            </tr>
        <?php } ?>

        <?php if ($desconto_aplicado != 0) { ?>
            <tr>
                <td>
                    Desconto
                    <?php if ($tipo_desconto == '%') { ?> (<?php echo $descontoFP ?>%) <?php } ?>
                </td>
                <td style="text-align: right;">- R$ <?php echo $descontoF ?></td>
            </tr>
        <?php } ?>

        <?php if (floatval($frete) != 0) { ?>
            <tr>
                <td>Frete</td>
                <td style="text-align: right;">+ R$ <?php echo $freteF ?></td>
            </tr>
        <?php } ?>

        <tr class="total-line">
            <td>TOTAL A PAGAR</td>
            <td style="text-align: right;">
                <?php if (floatval($valor_restante) > 0) { ?>
                    R$ <?php echo $total_vendaF ?>
                <?php } else { ?>
                    R$ <?php echo $valorF ?>
                <?php } ?>
            </td>
        </tr>

        <?php if (floatval($troco) != 0) { ?>
            <tr>
                <td>Valor Recebido</td>
                <td style="text-align: right;">R$ <?php echo $trocoF ?></td>
            </tr>
        <?php } ?>

        <?php if (floatval($total_troco) > 0) { ?>
            <tr>
                <td>Troco</td>
                <td style="text-align: right;">R$ <?php echo $total_trocoF ?></td>
            </tr>
        <?php } ?>

    </table>


    <table class="totals-table" style="margin-top: 15px;">
        <tr style="border-top: 1px solid #000;">
            <td style="width: 60%; padding-top: 5px;">Vendedor:</td>
            <td style="width: 40%; text-align: right; padding-top: 5px;"><?php echo $nome_usu_lanc ?></td>
        </tr>

        <?php if (floatval($valor_restante) > 0) { ?>
            <tr>
                <td>Pagamento Inicial (R$ <?php echo $valorF ?>):</td>
                <td style="text-align: right;"> <?php echo $saida ?> <?php echo $data_venc_1F ?></td>
            </tr>
            <tr>
                <td>Restante (R$ <?php echo $valor_restanteF ?>):</td>
                <td style="text-align: right;"> <?php echo $forma_pgto_restante ?> <?php echo $data_venc_2F ?></td>
            </tr>
        <?php } else { ?>
            <tr>
                <td>Forma de Pagamento:</td>
                <td style="text-align: right;"><?php echo $saida ?></td>
            </tr>
            <?php if ($pago == 'Não') { ?>
                <tr>
                    <td>Vencimento:</td>
                    <td style="text-align: right;"><?php echo $data_vencF ?></td>
                </tr>
            <?php } ?>
        <?php } ?>

    </table>

    <?php if ($pago == 'Não') { ?>
        <div class="footer-signature">
            <div style="margin-top: 40px;">_________________________________</div>
            <div style="font-size: 9pt;">Assinatura do Cliente</div>
        </div>
    <?php } ?>

</body>

</html>

<?php
// ###################################################################################
// FIM DO BLOCO DE GERAÇÃO DE PDF (DOMPDF)
// ###################################################################################
$html = ob_get_clean(); // Captura o HTML gerado

// Configuração e Geração
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true); // Necessário para carregar a imagem do logo

$dompdf = new Dompdf($options);
$dompdf->setPaper('A4', 'portrait');
$dompdf->loadHtml($html);
$dompdf->render();

// Saída do PDF para o navegador
$nome_arquivo = "Comprovante_Venda_" . $id_principal . ".pdf";
$dompdf->stream($nome_arquivo, array("Attachment" => 0)); // 0 = Visualizar no navegador
exit(0);
// ###################################################################################
?>