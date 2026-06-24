<?php
/** Lotes 4-8: estoque, financeiro, RH, GP, salas, SAC, LGPD, SST upgrades */
declare(strict_types=1);
function w(string $rel, string $c): void {
    $p = dirname(__DIR__) . '/docs/manual/content/' . $rel;
    if (!is_dir(dirname($p))) mkdir(dirname($p), 0775, true);
    file_put_contents($p, $c);
    echo "OK $rel\n";
}

$docs = [
// ESTOQUE
'estoque/est-visao-geral.html' => '<h1>Visão geral — Estoque</h1>
<p>Controle de itens, movimentações (entrada, saída, transferência, ajuste), custeio de produção e relatórios de saldo.</p>
<p><strong>Quem acessa:</strong> almoxarifado, produção e financeiro com permissões <em>Inventory*</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Cadastre <strong>unidades, categorias, estoques e posições</strong>.</li>
<li>Registre <strong>itens</strong> com SKU, unidade e estoque mínimo.</li>
<li>Execute <strong>movimentações</strong> conforme operação (entrada NF, saída consumo).</li>
<li>Feche <strong>períodos de custeio</strong> e simule custos de produção.</li>
<li>Consulte <strong>relatórios</strong> de saldo e histórico.</li>
</ol>
<h2>Campos e telas</h2>
<div class="help-field"><strong>Item</strong> Produto ou insumo controlado.</div>
<div class="help-field"><strong>Estoque / Posição</strong> Local físico ou lógico.</div>
<div class="help-field"><strong>Movimentação</strong> Entrada, saída, transferência, ajuste.</div>
<div class="help-field"><strong>Custeio</strong> Lotes produzidos e períodos de custo.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>Saldo negativo</dt><dd>Saída sem entrada prévia; ajuste ou estorne movimentação.</dd>
<dt>Item não aparece na busca</dt><dd>Inativo ou filtro de estoque; confirme cadastro.</dd>
<dt>Custo zerado no relatório</dt><dd>Período de custeio não fechado ou lote sem apontamento.</dd>
<dt>Transferência não conclui</dt><dd>Permissão ou saldo insuficiente na origem.</dd>
</dl>',

'estoque/est-cadastros.html' => '<h1>Cadastros de estoque</h1>
<p>Base de dados: unidades de medida, categorias, estoques, posições internas, operações e recursos de produção.</p>
<p><strong>Quem acessa:</strong> almoxarifado com <em>ListInventoryUnits</em>, <em>ListInventoryCategories</em>, <em>ListInventoryStocks</em>, etc.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Cadastre <strong>Unidades de Medida</strong> (UN, KG, CX).</li>
<li>Crie <strong>Categorias</strong> para classificar itens.</li>
<li>Registre <strong>Estoques</strong> (almoxarifado principal, produção).</li>
<li>Defina <strong>Posições internas</strong> (corredor, prateleira).</li>
<li>Configure <strong>Operações de produção</strong> e recursos se usar custeio.</li>
</ol>
<h2>Campos e telas</h2>
<div class="help-field"><strong>Unidade — Sigla</strong> Código usado nos itens.</div>
<div class="help-field"><strong>Categoria</strong> Agrupamento para relatórios.</div>
<div class="help-field"><strong>Estoque — Filial</strong> Vínculo multi-unidade.</div>
<div class="help-field"><strong>Posição</strong> Endereçamento WMS simplificado.</div>
<div class="help-field"><strong>Operação MO</strong> Roteiro de produção para custeio.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>Unidade incompatível na movimentação</dt><dd>Item usa UN; conversão não configurada.</dd>
<dt>Estoque duplicado</dt><dd>Padronize nomes; inative duplicatas.</dd>
<dt>Posição obrigatória não preenchida</dt><dd>Configure se posição é exigida por estoque.</dd>
<dt>Recurso de produção ocioso</dt><dd>Vincule a operações no roteiro de custeio.</dd>
</dl>',

'estoque/est-movimentacoes.html' => '<h1>Movimentações de estoque</h1>
<p>Entrada, saída, transferência entre estoques e ajuste de inventário com rastreabilidade.</p>
<p><strong>Quem acessa:</strong> almoxarifado com <em>CreateInventoryEntry</em>, <em>CreateInventoryExit</em>, <em>CreateInventoryTransfer</em>, <em>CreateInventoryAdjust</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Escolha tipo: <strong>Entrada</strong> (compra/devolução), <strong>Saída</strong> (consumo/venda), <strong>Transferência</strong> ou <strong>Ajuste</strong>.</li>
<li>Selecione estoque origem/destino e data.</li>
<li>Adicione itens, quantidades e lote se aplicável.</li>
<li>Informe documento referência (NF, OP).</li>
<li>Confirme; saldo atualiza imediatamente.</li>
</ol>
<h2>Campos e telas</h2>
<div class="help-field"><strong>Tipo</strong> Entrada, saída, transferência, ajuste.</div>
<div class="help-field"><strong>Item / Quantidade</strong> Linhas da movimentação.</div>
<div class="help-field"><strong>Documento</strong> NF, pedido ou ordem de produção.</div>
<div class="help-field"><strong>Observação</strong> Justificativa, especialmente em ajustes.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>Saldo insuficiente na saída</dt><dd>Verifique saldo por estoque/posição; transfira antes se necessário.</dd>
<dt>Ajuste sem aprovação</dt><dd>Alguns perfis só visualizam; solicite permissão de ajuste.</dd>
<dt>Transferência parcial</dt><dd>Confirme quantidade disponível na posição origem.</dd>
<dt>Movimentação não aparece no histórico</dt><dd>Filtro de data; movimentação pode ter sido estornada.</dd>
</dl>',

'estoque/est-custeio.html' => '<h1>Custeio e produção</h1>
<p>Lotes produzidos, períodos de custeio, simulação de custo e apontamentos de mão de obra e recursos.</p>
<p><strong>Quem acessa:</strong> controladoria e produção com <em>ListInvCostPeriods</em>, <em>SimulateInventoryCost</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Abra <strong>Período de Custeio</strong> para o mês/ano.</li>
<li>Registre <strong>Lotes produzidos</strong> com consumo de insumos.</li>
<li>Aponte <strong>recursos de produção</strong> e MO por operação.</li>
<li>Execute <strong>simulação</strong> antes do fechamento.</li>
<li>Feche período; custos alimentam relatórios e itens.</li>
</ol>
<h2>Campos e telas</h2>
<div class="help-field"><strong>Período</strong> Mês/ano de competência do custo.</div>
<div class="help-field"><strong>Lote</strong> Ordem de produção com quantidade boa.</div>
<div class="help-field"><strong>Insumos</strong> Materiais consumidos no lote.</div>
<div class="help-field"><strong>MO / Recursos</strong> Horas e custo de máquina.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>Período fechado — não edita</dt><dd>Reabra com permissão ou crie período correção.</dd>
<dt>Custo unitário divergente</dt><dd>Confira apontamentos e rateio de MO.</dd>
<dt>Insumo não baixa do estoque</dt><dd>Movimentação de saída pode ser manual ou automática conforme config.</dd>
<dt>Simulação lenta</dt><dd>Reduza lotes no período ou execute fora do horário de pico.</dd>
</dl>',

'estoque/est-relatorios.html' => '<h1>Relatórios de estoque</h1>
<p>Saldos atuais e histórico de movimentações por item, estoque, período e categoria.</p>
<p><strong>Quem acessa:</strong> usuários com <em>ReportInventoryBalance</em>, <em>ReportInventoryHistory</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Acesse <strong>Estoque → Relatórios → Saldos</strong> ou <strong>Histórico</strong>.</li>
<li>Aplique filtros: estoque, categoria, item, data.</li>
<li>Analise saldo, valor médio e movimentações.</li>
<li>Exporte Excel/PDF quando disponível.</li>
<li>Confronte saldo com inventário físico periódico.</li>
</ol>
<h2>Campos e telas</h2>
<div class="help-field"><strong>Saldo</strong> Quantidade disponível por item/estoque.</div>
<div class="help-field"><strong>Histórico</strong> Entradas, saídas e saldo acumulado.</div>
<div class="help-field"><strong>Filtros</strong> Período, estoque, categoria.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>Saldo diferente do físico</dt><dd>Execute inventário e ajuste; verifique movimentações não lançadas.</dd>
<dt>Histórico truncado</dt><dd>Limite de registros; estreite filtro de data.</dd>
<dt>Valor zerado</dt><dd>Item sem custo médio; feche custeio ou informe preço na entrada.</dd>
<dt>Exportação falha</dt><dd>Muitos registros; filtre ou exporte por categoria.</dd>
</dl>',

// FINANCEIRO
'financeiro/fin-visao-geral.html' => '<h1>Visão geral — Financeiro</h1>
<p>Contas a pagar e receber, plano de contas, bancos, fluxo de caixa e relatórios por centro de custo.</p>
<p><strong>Quem acessa:</strong> financeiro com permissões <em>ListPayments</em>, <em>ListReceipts</em>, <em>CashFlow</em>, etc.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Cadastre <strong>plano de contas, bancos, formas de pagamento</strong>.</li>
<li>Lance <strong>contas a pagar</strong> e <strong>a receber</strong>.</li>
<li>Baixe títulos conforme pagamento/recebimento.</li>
<li>Consulte <strong>fluxo de caixa</strong> e extrato.</li>
<li>Analise <strong>resumo por centro de custo</strong>.</li>
</ol>
<h2>Campos e telas</h2>
<div class="help-field"><strong>Título AP/AR</strong> Lançamento financeiro com vencimento.</div>
<div class="help-field"><strong>Plano de contas</strong> Classificação contábil.</div>
<div class="help-field"><strong>Centro de custo</strong> Rateio gerencial.</div>
<div class="help-field"><strong>Banco / Conta</strong> Conta corrente para movimentação.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>Saldo bancário divergente</dt><dd>Confira títulos em aberto e transferências entre contas.</dd>
<dt>CC não aparece</dt><dd>Cadastro inativo em Cadastro → Centros de Custo.</dd>
<dt>Fluxo de caixa vazio</dt><dd>Período sem movimentações ou filtro de conta.</dd>
<dt>Erro ao baixar título</dt><dd>Valor pago diferente do saldo; use juros/desconto.</dd>
</dl>',

'financeiro/fin-cadastros.html' => '<h1>Cadastros financeiros</h1>
<p>Bancos, plano de contas, formas de pagamento, frequências e transferências entre contas.</p>
<p><strong>Quem acessa:</strong> financeiro com <em>ListBanks</em>, <em>ListAccountsPlan</em>, <em>ListPaymentMethods</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Cadastre <strong>Bancos</strong> e contas correntes.</li>
<li>Monte <strong>Plano de Contas</strong> (receitas, despesas, ativo).</li>
<li>Registre <strong>Formas de Pagamento</strong> (PIX, boleto, cartão).</li>
<li>Configure <strong>Frequências</strong> para lançamentos recorrentes.</li>
<li>Use <strong>Transferência entre contas</strong> para movimentação interna.</li>
</ol>
<h2>Campos e telas</h2>
<div class="help-field"><strong>Banco — Agência/Conta</strong> Identificação bancária.</div>
<div class="help-field"><strong>Plano — Código</strong> Estrutura hierárquica contábil.</div>
<div class="help-field"><strong>Forma de pagamento</strong> Meio usado na baixa.</div>
<div class="help-field"><strong>Frequência</strong> Mensal, semanal para recorrência.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>Conta contábil errada no lançamento</dt><dd>Plano desatualizado; inative contas obsoletas.</dd>
<dt>Transferência duplicada</dt><dd>Verifique extrato antes de relançar.</dd>
<dt>Forma de pagamento não listada</dt><dd>Cadastro inativo; reative em Formas de Pagamento.</dd>
<dt>Saldo inicial incorreto</dt><dd>Ajuste via lançamento de abertura ou conciliação.</dd>
</dl>',

'financeiro/fin-pagar-receber.html' => '<h1>Contas a pagar e receber</h1>
<p>Lançamento, baixa e acompanhamento de títulos a pagar (fornecedores) e a receber (clientes).</p>
<p><strong>Quem acessa:</strong> financeiro com <em>ListPayments</em>, <em>ListReceipts</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Acesse <strong>Pagar</strong> ou <strong>Receber</strong> e clique em <strong>Novo</strong>.</li>
<li>Selecione parceiro (fornecedor/cliente), valor e vencimento.</li>
<li>Informe plano de contas, centro de custo e documento (NF).</li>
<li>Salve; título fica em aberto até baixa.</li>
<li>Na baixa, informe data, valor pago, juros/desconto e conta bancária.</li>
</ol>
<h2>Campos e telas</h2>
<div class="help-field"><strong>Parceiro</strong> Fornecedor ou cliente do cadastro.</div>
<div class="help-field"><strong>Valor / Vencimento</strong> Montante e data de pagamento prevista.</div>
<div class="help-field"><strong>Status</strong> Aberto, pago, parcial, cancelado.</div>
<div class="help-field"><strong>Baixa</strong> Registro efetivo do pagamento/recebimento.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>Título não aparece na listagem</dt><dd>Filtro de status ou período; inclua "todos".</dd>
<dt>Baixa parcial não permitida</dt><dd>Verifique configuração ou permissão de baixa parcial.</dd>
<dt>Parceiro não encontrado</dt><dd>Cadastre em Parceiros de Negócio → Fornecedores/Clientes.</dd>
<dt>Duplicidade de NF</dt><dd>Sistema alerta documento repetido; confira antes de salvar.</dd>
</dl>',

'financeiro/fin-relatorios.html' => '<h1>Relatórios financeiros</h1>
<p>Extrato de caixa, fluxo diário, resumo por competência e análise por centro de custo.</p>
<p><strong>Quem acessa:</strong> financeiro e gestores com <em>Movements</em>, <em>CashFlow</em>, <em>FlowCashCompetence</em>, <em>CostCenterSummary</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Escolha relatório: extrato, fluxo diário ou resumo competência.</li>
<li>Defina período, conta bancária e centro de custo.</li>
<li>Analise entradas, saídas e saldo.</li>
<li>Exporte PDF/Excel para contabilidade.</li>
<li>Confronte com <strong>Rel Centro de Custo</strong> para visão gerencial.</li>
</ol>
<h2>Campos e telas</h2>
<div class="help-field"><strong>Extrato</strong> Movimentações por conta e data.</div>
<div class="help-field"><strong>Fluxo diário</strong> Projeção dia a dia.</div>
<div class="help-field"><strong>Competência</strong> Receitas/despesas pelo regime.</div>
<div class="help-field"><strong>Resumo CC</strong> Total por centro de custo.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>Totais não batem com banco</dt><dd>Títulos em aberto não entram no extrato realizado.</dd>
<dt>Competência vs. caixa divergem</dt><dd>Regime de competência difere do caixa; use relatório correto.</dd>
<dt>CC sem movimentação</dt><dd>Lançamentos sem CC atribuído aparecem em "sem centro".</dd>
<dt>Export lento</dt><dd>Reduza intervalo de datas.</dd>
</dl>',

// PARCEIROS + QUALIDADE
'parceiros/parceiros-negocio.html' => '<h1>Parceiros de negócio</h1>
<p>Cadastro unificado de clientes e fornecedores usados no financeiro, estoque e integrações.</p>
<p><strong>Quem acessa:</strong> cadastro comercial/financeiro com <em>ListCustomers</em>, <em>ListSuppliers</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Acesse <strong>Parceiros → Clientes</strong> ou <strong>Fornecedores</strong>.</li>
<li>Cadastre razão social, documento, endereço e contatos.</li>
<li>Informe dados fiscais (IE, regime) quando necessário.</li>
<li>Vincule condição de pagamento padrão.</li>
<li>Use o parceiro em títulos AP/AR e pedidos.</li>
</ol>
<h2>Campos e telas</h2>
<div class="help-field"><strong>Tipo</strong> Cliente ou fornecedor.</div>
<div class="help-field"><strong>CNPJ/CPF</strong> Documento único.</div>
<div class="help-field"><strong>Endereço</strong> Faturamento e entrega.</div>
<div class="help-field"><strong>Contato</strong> E-mail e telefone principal.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>Documento duplicado</dt><dd>Parceiro já cadastrado; busque antes de criar.</dd>
<dt>Não aparece na busca de título</dt><dd>Tipo errado (cliente vs. fornecedor).</dd>
<dt>Integração SAP não sincroniza</dt><dd>Verifique código externo e config SAP API.</dd>
<dt>Endereço incompleto para NF</dt><dd>Preencha CEP e município IBGE.</dd>
</dl>',

'qualidade/qualidade-documentos.html' => '<h1>Documentos — Garantia da Qualidade</h1>
<p>Gestão documental da qualidade: procedimentos, instruções, registros e controle de revisões.</p>
<p><strong>Quem acessa:</strong> qualidade com <em>ListDocuments</em>, <em>CreateDocument</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Cadastre documento com código, título e categoria.</li>
<li>Informe revisão, data de vigência e responsável.</li>
<li>Anexe arquivo PDF na versão atual.</li>
<li>Publique; colaboradores consultam conforme permissão.</li>
<li>Obsoletar versões antigas ao emitir nova revisão.</li>
</ol>
<h2>Campos e telas</h2>
<div class="help-field"><strong>Código</strong> Identificador único (ex.: POP-001).</div>
<div class="help-field"><strong>Revisão</strong> Número da versão vigente.</div>
<div class="help-field"><strong>Categoria</strong> POP, IT, formulário, manual.</div>
<div class="help-field"><strong>Vigência</strong> Data início/fim da versão.</div>
<div class="help-field"><strong>Anexo</strong> Arquivo controlado.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>Documento obsoleto ainda visível</dt><dd>Marque revisão anterior como obsoleta.</dd>
<dt>Código duplicado</dt><dd>Padronize nomenclatura por área.</dd>
<dt>Download negado</dt><dd>Permissão de visualização ausente no nível de acesso.</dd>
<dt>Revisão sem anexo</dt><dd>Exige upload para publicação.</dd>
</dl>',
];

foreach ($docs as $rel => $c) w($rel, $c);
echo count($docs) . " arquivos lote 4.\n";
