<?php
/** Gera lote 2+ do manual. Uso: php scripts/generate_manual_batch2.php */
declare(strict_types=1);
$base = dirname(__DIR__) . '/docs/manual/content';

function w(string $rel, string $c): void {
    $p = dirname(__DIR__) . '/docs/manual/content/' . $rel;
    $d = dirname($p);
    if (!is_dir($d)) mkdir($d, 0775, true);
    file_put_contents($p, $c);
    echo "OK $rel\n";
}

$all = [
'cadastro/cad-visao-geral.html' => <<<'HTML'
<h1>Visão geral — Cadastro</h1>
<p>Base organizacional do sistema: cargos, departamentos, centros de custo, turnos, usuários, organograma e níveis de acesso.</p>
<p><strong>Quem acessa:</strong> RH, administradores e gestores com permissões em <em>Cadastro</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Cadastre <strong>Departamentos</strong> e <strong>Centros de Custo</strong> conforme estrutura contábil.</li>
<li>Registre <strong>Cargos</strong> e <strong>Turnos</strong> de trabalho.</li>
<li>Configure <strong>Níveis de Acesso</strong> (pacotes de permissão).</li>
<li>Crie <strong>Usuários</strong> vinculando cargo, departamento, filial e gestor.</li>
<li>Valide o <strong>Organograma</strong> hierárquico.</li>
</ol>
<h2>Campos e telas</h2>
<div class="help-field"><strong>Estrutura</strong> Cargos, departamentos, CC e turnos.</div>
<div class="help-field"><strong>Usuários</strong> Colaboradores e contas de acesso.</div>
<div class="help-field"><strong>Organograma</strong> Visualização da hierarquia gestor-subordinado.</div>
<div class="help-field"><strong>Níveis de acesso</strong> Perfis ACL do sistema.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>Usuário sem menu de módulo</dt><dd>Verifique nível de acesso e pacotes associados.</dd>
<dt>Cargo não reflete no SST</dt><dd>Confirme vínculos cargo/setor → riscos no módulo SST.</dd>
<dt>Organograma desatualizado</dt><dd>Atualize campo gestor imediato em cada usuário.</dd>
<dt>Centro de custo inválido no financeiro</dt><dd>CC deve estar ativo e vinculado ao departamento correto.</dd>
</dl>
HTML,

'cadastro/cad-estrutura.html' => <<<'HTML'
<h1>Estrutura organizacional</h1>
<p>Cadastro de cargos, departamentos, centros de custo e turnos — base para usuários, financeiro, SST e relatórios.</p>
<p><strong>Quem acessa:</strong> RH e administradores com <em>ListPositions</em>, <em>ListDepartments</em>, <em>ListCostCenters</em>, <em>ListWorkShifts</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Cadastre <strong>Departamentos</strong> (nome, sigla, responsável).</li>
<li>Registre <strong>Centros de Custo</strong> alinhados ao plano contábil.</li>
<li>Crie <strong>Cargos</strong> com descrição e CBO quando aplicável.</li>
<li>Configure <strong>Turnos</strong> (horários, tolerâncias) para escalas.</li>
<li>Revise consistência antes de importar ou criar usuários em massa.</li>
</ol>
<h2>Campos e telas</h2>
<div class="help-field"><strong>Cargo — Nome / CBO</strong> Identificação e classificação ocupacional.</div>
<div class="help-field"><strong>Departamento — Sigla</strong> Usado em filtros e organograma.</div>
<div class="help-field"><strong>Centro de Custo — Código</strong> Integração com financeiro e SAP.</div>
<div class="help-field"><strong>Turno — Horário</strong> Início, fim e intervalo do expediente.</div>
<div class="help-field"><strong>Status</strong> Registros inativos não aparecem em novos vínculos.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>Duplicidade de departamento</dt><dd>Padronize siglas; inative registros obsoletos em vez de duplicar.</dd>
<dt>CC não aparece em lançamento</dt><dd>Verifique status ativo e permissão do usuário na filial.</dd>
<dt>Cargo usado em SST sem riscos</dt><dd>Vincule riscos ao cargo/setor no módulo SST.</dd>
<dt>Turno incorreto em escala</dt><dd>Confirme timezone e horário de verão na configuração.</dd>
</dl>
HTML,

'cadastro/cad-usuarios.html' => <<<'HTML'
<h1>Cadastro de usuários</h1>
<p>Gestão de colaboradores e contas de acesso: dados pessoais, vínculos organizacionais, foto, gestor e credenciais.</p>
<p><strong>Quem acessa:</strong> RH e administradores com <em>ListUsers</em>, <em>CreateUser</em>, <em>UpdateUser</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Acesse <strong>Cadastro → Usuários → Listar</strong>.</li>
<li>Clique em <strong>Novo</strong> e preencha nome, e-mail, CPF/matricula e senha inicial.</li>
<li>Selecione cargo, departamento, filial, turno e gestor imediato.</li>
<li>Atribua <strong>Nível de Acesso</strong> adequado ao perfil.</li>
<li>Salve e comunique credenciais conforme política de segurança.</li>
<li>Para desligamento, inative o usuário em vez de excluir (preserva histórico).</li>
</ol>
<h2>Campos e telas</h2>
<div class="help-field"><strong>Nome / E-mail</strong> Identificação e login (e-mail único).</div>
<div class="help-field"><strong>Cargo / Departamento</strong> Define exposição SST e hierarquia.</div>
<div class="help-field"><strong>Gestor</strong> Usado em organograma e fluxos de aprovação.</div>
<div class="help-field"><strong>Filial</strong> Restringe dados no filtro global.</div>
<div class="help-field"><strong>Nível de acesso</strong> Perfil de permissões ACL.</div>
<div class="help-field"><strong>Status</strong> Ativo/inativo controla login.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>E-mail já cadastrado</dt><dd>Busque usuário inativo; reative ou use e-mail alternativo corporativo.</dd>
<dt>Login bloqueado por treinamento</dt><dd>Regularize treinamentos obrigatórios ou SST pendentes.</dd>
<dt>Usuário não vê filial correta</dt><dd>Ajuste vínculo de filial no cadastro e filtro global.</dd>
<dt>Senha não atende política</dt><dd>Consulte requisitos em Administração → Política de Senha.</dd>
</dl>
HTML,

'cadastro/cad-organograma.html' => <<<'HTML'
<h1>Organograma</h1>
<p>Visualização hierárquica da empresa com base no vínculo gestor-subordinado de cada usuário ativo.</p>
<p><strong>Quem acessa:</strong> gestores e RH com <em>OrganizationChart</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Garanta que todos os usuários ativos tenham <strong>gestor imediato</strong> preenchido.</li>
<li>Acesse <strong>Cadastro → Usuários → Organograma</strong>.</li>
<li>Navegue pelos níveis expandindo departamentos ou gestores.</li>
<li>Clique em colaborador para abrir ficha (se permitido).</li>
<li>Exporte ou imprima quando disponível para apresentações.</li>
</ol>
<h2>Campos e telas</h2>
<div class="help-field"><strong>Nó — Colaborador</strong> Nome, cargo e foto no organograma.</div>
<div class="help-field"><strong>Agrupamento</strong> Por departamento ou linha de gestão.</div>
<div class="help-field"><strong>Zoom / Expandir</strong> Controles de navegação na árvore.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>Colaborador fora da árvore</dt><dd>Gestor não cadastrado ou referência circular (A reporta B e B reporta A).</dd>
<dt>Hierarquia incompleta</dt><dd>Diretoria raiz deve ter gestor vazio ou usuário sistema definido como topo.</dd>
<dt>Departamento errado na visualização</dt><dd>Organograma usa gestor, não departamento; ajuste vínculos.</dd>
<dt>Usuário inativo ainda aparece</dt><dd>Confirme status inativo; cache pode exigir refresh.</dd>
</dl>
HTML,

'cadastro/cad-niveis-acesso.html' => <<<'HTML'
<h1>Níveis de acesso</h1>
<p>Perfis de permissão que agrupam pacotes de páginas (ACL). Cada usuário recebe um nível de acesso.</p>
<p><strong>Quem acessa:</strong> administradores com <em>ListAccessLevels</em>, <em>UpdateAccessLevel</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Liste níveis existentes e identifique perfis padrão (Colaborador, Gestor, Admin).</li>
<li>Duplique um nível similar como base para novo perfil, se disponível.</li>
<li>Marque/desmarque <strong>Pacotes</strong> conforme necessidade do perfil.</li>
<li>Salve e atribua o nível aos usuários em Cadastro → Usuários.</li>
<li>Teste login com usuário de homologação.</li>
</ol>
<h2>Campos e telas</h2>
<div class="help-field"><strong>Nome do nível</strong> Identificação do perfil (ex.: Vendedor CRM).</div>
<div class="help-field"><strong>Pacotes</strong> Conjuntos de páginas liberadas.</div>
<div class="help-field"><strong>Descrição</strong> Documentação interna do propósito do perfil.</div>
<div class="help-field"><strong>Status</strong> Níveis inativos não podem ser atribuídos.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>Perfil muito permissivo</dt><dd>Revise pacotes; princípio do menor privilégio.</dd>
<dt>Alteração não surte efeito</dt><dd>Usuário deve fazer logout/login.</dd>
<dt>Dois níveis conflitantes</dt><dd>Padronize nomenclatura; evite duplicar perfis equivalentes.</dd>
<dt>Erro 004 em tela liberada</dt><dd>Pacote pode incluir listagem mas não ação de edição; inclua página filha.</dd>
</dl>
HTML,
];

foreach ($all as $rel => $html) w($rel, $html);
echo count($all) . " arquivos.\n";
