<?php
// Cadastros simples do financeiro que compartilham a mesma tela
function cadfin_config($entidade)
{
    $grupos_despesa = ['custo' => 'Custo (mercadoria / produtor)', 'pessoal' => 'Despesas com pessoal', 'operacional' => 'Despesas operacionais',
        'impostos' => 'Impostos e taxas', 'financeira' => 'Despesas financeiras', 'outras' => 'Outras despesas'];
    $grupos_receita = ['receita' => 'Receita bruta (vendas/serviços)', 'financeira' => 'Receitas financeiras', 'outras' => 'Outras receitas'];
    $cfg = [
        'categorias_receber' => [
            'titulo' => 'Categorias de Receita', 'tabela' => 'categorias_receber', 'permissao' => 'categorias_receber',
            'grupos' => $grupos_receita, 'uso' => [['receber', 'categoria_receber'], ['linha_bancos', 'categoria_receber']],
        ],
        'categorias_pagar' => [
            'titulo' => 'Categorias de Despesa', 'tabela' => 'categorias_pagar', 'permissao' => 'pagar',
            'grupos' => $grupos_despesa, 'uso' => [['pagar', 'categoria_pagar'], ['linha_bancos', 'categoria_pagar']],
        ],
        'centros_custo' => [
            'titulo' => 'Centros de Custo', 'tabela' => 'centros_custo', 'permissao' => 'centros_custo',
            'grupos' => null, 'uso' => [['receber', 'centro_custo'], ['pagar', 'centro_custo'], ['linha_bancos', 'centro_custo']],
        ],
    ];
    if (!isset($cfg[$entidade])) throw new FinErro('Cadastro inválido.');
    return $cfg[$entidade];
}
