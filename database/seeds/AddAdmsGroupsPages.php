<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

/**
 * Seed de grupos de páginas.
 *
 * Anotações para análise estática (Intelephense) dos métodos herdados do Phinx.
 *
 * @method array|false fetchRow(string $sql)
 * @method void execute(string $sql)
 */
class AddAdmsGroupsPages extends AbstractSeed
{
    /**
     * Cadastra grupos na tabela `adms_groups_pages` se ainda não existirem.
     *
     * Este método é executado para popular a tabela `adms_groups_pages` com registros iniciais dos grupos.
     * Primeiro, verifica se já existe grupo na tabela com base no name. 
     * Se o grupo não existir, os dados são inseridos na tabela.
     * 
     * @return void
     */
    public function run(): void
    {
        $data = [];

        $grupos = [
            ['name' => 'Dashboard', 'obs' => ''], // Nº 1
            ['name' => 'Usuários', 'obs' => ''], // Nº 2
            ['name' => 'Nível de Acesso', 'obs' => ''], // Nº 3
            ['name' => 'Pacote de Páginas', 'obs' => ''], // Nº 4
            ['name' => 'Grupo de Páginas', 'obs' => ''], // Nº 5
            ['name' => 'Páginas', 'obs' => ''], // Nº 6
            ['name' => 'Login', 'obs' => ''],    // Nº 7
            ['name' => 'Departamento', 'obs' => ''], // Nº 8
            ['name' => 'Erros', 'obs' => ''], // Nº 9
            ['name' => 'Cargo', 'obs' => ''], // Nº 10
            ['name' => 'Permissões', 'obs' => ''], // Nº 11
            ['name' => 'Bancos', 'obs' => ''], // Nº 12
            ['name' => 'Pagar', 'obs' => ''], // Nº 13
            ['name' => 'Receber', 'obs' => ''], // Nº 14
            ['name' => 'Centros de Custo', 'obs' => ''], // Nº 15
            ['name' => 'Plano de Contas', 'obs' => ''], // Nº 16
            ['name' => 'Frequências', 'obs' => ''], // Nº 17
            ['name' => 'Clientes', 'obs' => ''], // Nº 18
            ['name' => 'Fornecedores', 'obs' => ''], // Nº 19
            ['name' => 'Formas de Pagamento', 'obs' => ''], // Nº 20
            ['name' => 'Relatórios Financeiros', 'obs' => ''], // Nº 21
            ['name' => 'Movimentos', 'obs' => ''], // Nº 22
            ['name' => 'Documentos', 'obs' => ''], // Nº 23
            ['name' => 'Treinamentos', 'obs' => ''], // Nº 24
            ['name' => 'Avaliações', 'obs' => ''], // Nº 25
            ['name' => 'Configurações', 'obs' => 'Configurações gerais do sistema'], // Nº 26
            ['name' => 'Administração de Senhas', 'obs' => 'Administração de Senhas'], // Nº 27
            ['name' => 'Logs', 'obs' => 'Páginas de auditoria e logs do sistema'], // Nº 28
            ['name' => 'Planejamento Estratégico', 'obs' => 'Gestão de planos e indicadores estratégicos'], // Nº 29
            ['name' => 'Informativos', 'obs' => 'Páginas de informativos'], // Nº 30
            ['name' => 'LGPD', 'obs' => 'Gestão da LGPD e privacidade'], // Nº 31
            ['name' => 'Sessões', 'obs' => 'Gerenciamento de sessões do sistema'], // Nº 32
            ['name' => 'Estoque', 'obs' => 'Módulo de estoque'], // Nº 33
            ['name' => 'CRM', 'obs' => 'Gestão de Relacionamento com Clientes'], // Nº 34
            ['name' => 'Relatórios Dinâmicos', 'obs' => 'Construtor de relatórios e dashboards personalizados'], // Nº 35
            ['name' => 'Gestão de Pessoas', 'obs' => 'Módulo completo de Gestão de Pessoas (RH)'], // Nº 36
            ['name' => 'Reserva de Salas', 'obs' => 'Módulo de agendamento e reserva de salas de reunião'], // Nº 37
            ['name' => 'Gestão de Projetos', 'obs' => 'Módulo de gestão de projetos'], // Nº 38
            ['name' => 'Comunicação Social', 'obs' => 'Timeline interna e eventos corporativos'], // Nº 39
            ['name' => 'SAC', 'obs' => 'Módulo de Atendimento ao Cliente (SmartSAC)'], // Nº 40
            ['name' => 'Segurança e Medicina', 'obs' => 'Módulo de Saúde e Segurança do Trabalho (SST)'], // Nº 41
            // Cisão ACL Expand (2026-07-29) - nomes canónicos; IDs variam por ambiente
            ['name' => 'Gestão de Pessoas - Talentos (ATS)', 'obs' => 'Recrutamento e seleção (vagas, candidatos, entrevistas).'],
            ['name' => 'Gestão de Pessoas - Portal / Solicitações', 'obs' => 'Portal do colaborador, solicitações e aprovações.'],
            ['name' => 'Gestão de Pessoas - Desempenho e Carreira', 'obs' => 'Desempenho, PDI, pulse, sucessão e carreira.'],
            ['name' => 'Gestão de Pessoas - Organização / Políticas', 'obs' => 'Políticas, turnos, quadro e analytics RH.'],
            ['name' => 'SST - Medicina / ASO / Exames', 'obs' => 'ASO, exames, médicos e CIDs.'],
            ['name' => 'SST - Cadastros e vínculos', 'obs' => 'Cadastros e vínculos SST.'],
            ['name' => 'SST - Treinamentos / GHE / PPP', 'obs' => 'Treinamentos SST, GHE e PPP.'],
            ['name' => 'SST - EPI', 'obs' => 'EPIs, estoque e fichas.'],
            ['name' => 'SST - Equipamentos / Vistoria', 'obs' => 'Equipamentos, vistorias e não conformidades.'],
            ['name' => 'SST - Acidentes / Afastamentos', 'obs' => 'Acidentes, afastamentos e CAT.'],
            ['name' => 'SST - Dashboard / Relatórios', 'obs' => 'Dashboard e relatórios SST.'],
            ['name' => 'LGPD - Taxonomia', 'obs' => 'Finalidades, bases legais, tipos e classificações.'],
            ['name' => 'LGPD - Inventário / ROPA / Mapping', 'obs' => 'Inventário, ROPA e data mapping.'],
            ['name' => 'LGPD - AIPD', 'obs' => 'Avaliação de impacto (AIPD).'],
            ['name' => 'LGPD - TIA', 'obs' => 'Transferência internacional (TIA).'],
            ['name' => 'LGPD - Consentimentos', 'obs' => 'Consentimentos LGPD.'],
            ['name' => 'LGPD - Dashboard / Termos / Legal', 'obs' => 'Dashboard, termos e páginas legais.'],
            ['name' => 'LGPD - RIPD', 'obs' => 'Relatório de impacto (RIPD).'],
            ['name' => 'LGPD - Titulares', 'obs' => 'Categorias e titulares.'],
            // Cisão ACL Expand P2 (2026-07-29) - Estoque / CRM
            ['name' => 'Estoque - Itens e movimentações', 'obs' => 'Itens, posições, cadastros e movimentações de estoque.'],
            ['name' => 'Estoque - Custeio', 'obs' => 'Períodos de custo, DRE, fatores, simulações e recursos de produção.'],
            ['name' => 'CRM - Operação', 'obs' => 'Pipeline, parceiros, oportunidades, atividades e relatórios CRM.'],
            ['name' => 'CRM - Integrações e configurações', 'obs' => 'WhatsApp, SAP API, MCP chat e calendário vinculados ao CRM.'],
        ];

        foreach ($grupos as $grupo) {
            $nameSql = str_replace("'", "''", (string)$grupo['name']);
            $obsSql = str_replace("'", "''", (string)$grupo['obs']);
            $existingRecord = $this->fetchRow("SELECT id FROM adms_groups_pages WHERE name = '{$nameSql}' LIMIT 1");
            if (!$existingRecord) {
                $data[] = [
                    'name' => $nameSql,
                    'obs' => $obsSql,
                    'created_at' => date("Y-m-d H:i:s"),
                ];
            }
        }

        if (!empty($data)) {
            foreach ($data as $row) {
                $createdAtSql = str_replace("'", "''", (string)$row['created_at']);
                $this->execute(
                    "INSERT INTO adms_groups_pages (name, obs, created_at)
                     VALUES ('{$row['name']}', '{$row['obs']}', '{$createdAtSql}')"
                );
            }
        }
    }
}
