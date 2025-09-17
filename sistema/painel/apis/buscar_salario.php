<?php
// buscar_salario.php

// URL da API de Dados Abertos do Governo Federal para o salário mínimo.
// Esta API fornece dados históricos, então pegamos o mais recente.
$apiUrl = "https://dados.gov.br/api/action/datastore_search?resource_id=274995f3-5e9a-45c1-8120-5e2a2253676c&limit=1&sort=Vigencia%20desc";

// Inicia a sessão cURL para buscar os dados da URL
$curl = curl_init($apiUrl);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // Retorna a resposta como string
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // Necessário em alguns servidores (localhost)

// Executa a requisição e obtém a resposta
$response = curl_exec($curl);
curl_close($curl);

$salarioMinimo = 0;

if ($response) {
    $data = json_decode($response, true);
    // Navega na estrutura do JSON para encontrar o valor
    if (isset($data['result']['records'][0]['Valor'])) {
        $valorString = $data['result']['records'][0]['Valor'];
        // Converte o formato "1.518,00" para "1518.00"
        $valorFormatado = str_replace(['.', ','], ['', '.'], $valorString);
        $salarioMinimo = (float) $valorFormatado;
    }
}

// Se a API falhar, usamos um valor padrão seguro (fallback)
if ($salarioMinimo <= 0) {
    $salarioMinimo = 1518.00; // Valor de 2025 como segurança
}

// Retorna o valor em formato JSON para o JavaScript
header('Content-Type: application/json');
echo json_encode(['valor' => $salarioMinimo]);

?>