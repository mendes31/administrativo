<div class="fp-expl">
    <h3 class="h5">O imposto efetivo, primeiro</h3>
    <p>ICMS incide sobre o preço de venda. PIS e COFINS incidem sobre o preço já sem o ICMS.</p>
    <p class="font-monospace small bg-light p-2 rounded">Imposto efetivo = ICMS% + (1 − ICMS%) × (PIS% + COFINS%)</p>
    <p>Exemplo: 12% + (1 − 12%) × (1,65% + 7,60%) = <strong>20,14%</strong>. Fator por dentro = 1 / (1 − 20,14%) ≈ 1,2522.</p>

    <h3 class="h5 mt-4">Três métodos</h3>
    <dl>
        <dt>Lucro sobre preço bruto (PB)</dt>
        <dd>Preço = Custo / (1 − Imposto − Frete% − Comissão% − Meta%). Use quando a empresa acompanha lucro sobre o que o cliente paga.</dd>
        <dt>Margem sobre receita líquida (RL)</dt>
        <dd>Preço = Custo / ((1 − Imposto) × (1 − Meta%) − Frete% − Comissão%). Visão gerencial depois de tirar impostos.</dd>
        <dt>Markup sobre custo (MK)</dt>
        <dd>Preço = Custo × (1 + Markup%) / (1 − Imposto − Frete% − Comissão%). Lucro como % do custo; pode passar de 100%. Indicado para produção para terceiros.</dd>
    </dl>
    <p>Sem frete, comissão e desconto, as três metas <em>calibradas</em> (ex.: 20,70% / 25,93% / 35%) geram o mesmo preço. Com despesas, os preços se separam: o markup sobe menos que PB e RL para preservar o mesmo lucro em reais.</p>

    <h3 class="h5 mt-4">As % reais — como na planilha</h3>
    <p>Em cada aba você informa só o % daquele método. O sistema acha o preço e, à direita, mostra o que <em>esse mesmo lucro</em> representa nas três réguas, na ordem das abas: <strong>azul</strong> = lucro/preço bruto, <strong>verde</strong> = lucro/receita líquida, <strong>âmbar</strong> = markup (as mesmas cores do comparativo). Não são outras metas: são a leitura do resultado. Valores em reais trazem R$ e percentuais trazem % em símbolo pequeno.</p>

    <h3 class="h5 mt-4">Colunas ocultas</h3>
    <p>Ficam fora da grade (como H, Q, AG, AH e AI no Excel): fator imposto por dentro, status da entrada, validação, lucro do projeto (forecast × lucro) e receita bruta vendida (forecast × preço). O botão <strong>PDF da análise</strong> traz todas elas, destacadas em amarelo.</p>

    <h3 class="h5 mt-4">Preço informado pelo parceiro</h3>
    <p>Se o cliente diz quanto quer pagar, preencha só <strong>Preço informado</strong> e deixe as metas em branco. O sistema calcula impostos, receita líquida, lucro e as três margens reais.</p>

    <h3 class="h5 mt-4">Desconto %</h3>
    <p>O desconto não muda o lucro nem as margens — só o preço de tabela (preço vendido / (1 − desconto)).</p>

    <h3 class="h5 mt-4">Por que a margem não chega a 100% (e o markup chega)</h3>
    <p>Margem divide o lucro pelo preço ou pela receita — e esse total ainda precisa cobrir custo, imposto, frete e comissão. Markup divide pelo custo, que pode ser bem menor que o preço.</p>
    <p>Teto teórico PB = 1 − Imposto − Frete% − Comissão%. Teto RL = 1 − (Frete% + Comissão%) / (1 − Imposto). No teto exato o preço iria ao infinito; a mensagem INVÁLIDO sugere um valor um pouco abaixo.</p>
</div>
