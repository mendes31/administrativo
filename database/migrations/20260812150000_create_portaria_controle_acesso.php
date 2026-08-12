<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Domínio Portaria / Controle de Acesso Físico (ADR-0009).
 * Tabelas operacionais + grupo ACL "Portaria". Páginas sem concessão em massa.
 */
final class CreatePortariaControleAcesso extends AbstractMigration
{
    private const GROUP_NAME = 'Portaria';

    public function up(): void
    {
        $this->createPontos();
        $this->createVisitantes();
        $this->createTermoAceites();
        $this->createAutorizacoes();
        $this->createAutorizacaoContatos();
        $this->createMovimentacoes();
        $this->registerGroupAndPages();
    }

    public function down(): void
    {
        $controllers = [
            'PortariaPainel',
            'PortariaVisitantes',
            'PortariaVisitantesCreate',
            'PortariaVisitantesView',
            'PortariaVisitantesUpdate',
            'PortariaPontos',
            'PortariaPontosCreate',
            'PortariaPontosUpdate',
            'PortariaAutorizacoes',
            'PortariaAutorizacoesCreate',
            'PortariaAutorizacoesView',
            'PortariaMovimentacoes',
        ];

        foreach ($controllers as $controller) {
            $row = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE controller = " . $this->quote($controller) . " LIMIT 1"
            );
            if ($row) {
                $pid = (int) $row['id'];
                if ($this->hasTable('adms_access_levels_pages')) {
                    $this->execute("DELETE FROM adms_access_levels_pages WHERE adms_page_id = {$pid}");
                }
                $this->execute("DELETE FROM adms_pages WHERE id = {$pid}");
            }
        }

        foreach ([
            'portaria_movimentacoes',
            'portaria_autorizacao_contatos',
            'portaria_autorizacoes',
            'portaria_termo_aceites',
            'portaria_visitantes',
            'portaria_pontos_controle',
        ] as $table) {
            if ($this->hasTable($table)) {
                $this->table($table)->drop()->save();
            }
        }

        $group = $this->fetchRow(
            "SELECT id FROM adms_groups_pages WHERE name = " . $this->quote(self::GROUP_NAME) . " LIMIT 1"
        );
        if ($group) {
            $gid = (int) $group['id'];
            $still = $this->fetchRow(
                "SELECT id FROM adms_pages WHERE adms_groups_page_id = {$gid} LIMIT 1"
            );
            if (!$still) {
                $this->execute("DELETE FROM adms_groups_pages WHERE id = {$gid}");
            }
        }
    }

    private function quote(string $value): string
    {
        return $this->getAdapter()->getConnection()->quote($value);
    }

    private function createPontos(): void
    {
        if ($this->hasTable('portaria_pontos_controle')) {
            return;
        }

        $this->table('portaria_pontos_controle', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('nome', 'string', ['limit' => 120])
            ->addColumn('codigo', 'string', ['limit' => 40, 'null' => true])
            ->addColumn('adms_branch_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('ativo', 'boolean', ['default' => 1])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'null' => true,
            ])
            ->addIndex(['codigo'], ['unique' => true, 'name' => 'uq_portaria_pontos_codigo'])
            ->addIndex(['ativo'], ['name' => 'idx_portaria_pontos_ativo'])
            ->create();
    }

    private function createVisitantes(): void
    {
        if ($this->hasTable('portaria_visitantes')) {
            return;
        }

        $this->table('portaria_visitantes', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('nome', 'string', ['limit' => 180])
            ->addColumn('documento', 'string', ['limit' => 40, 'null' => true])
            ->addColumn('telefone', 'string', ['limit' => 30, 'null' => true])
            ->addColumn('empresa', 'string', ['limit' => 180, 'null' => true])
            ->addColumn('email', 'string', ['limit' => 180, 'null' => true])
            ->addColumn('observacoes', 'text', ['null' => true])
            ->addColumn('ativo', 'boolean', ['default' => 1])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'null' => true,
            ])
            ->addIndex(['documento'], ['name' => 'idx_portaria_visitantes_documento'])
            ->addIndex(['nome'], ['name' => 'idx_portaria_visitantes_nome'])
            ->addIndex(['ativo'], ['name' => 'idx_portaria_visitantes_ativo'])
            ->create();
    }

    private function createTermoAceites(): void
    {
        if ($this->hasTable('portaria_termo_aceites')) {
            return;
        }

        $this->table('portaria_termo_aceites', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('visitante_id', 'integer', ['signed' => false])
            ->addColumn('lgpd_termo_id', 'integer', ['signed' => false])
            ->addColumn('metodo', 'string', ['limit' => 40, 'default' => 'presencial'])
            ->addColumn('porteiro_user_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('conferencia_identidade', 'boolean', ['default' => 0])
            ->addColumn('aceito_em', 'datetime')
            ->addColumn('valido_ate', 'datetime')
            ->addColumn('dispositivo', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('observacoes', 'text', ['null' => true])
            ->addColumn('revogado_em', 'datetime', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'null' => true,
            ])
            ->addIndex(['visitante_id'], ['name' => 'idx_portaria_aceites_visitante'])
            ->addIndex(['lgpd_termo_id'], ['name' => 'idx_portaria_aceites_termo'])
            ->addIndex(['valido_ate'], ['name' => 'idx_portaria_aceites_valido'])
            ->create();
    }

    private function createAutorizacoes(): void
    {
        if ($this->hasTable('portaria_autorizacoes')) {
            return;
        }

        $this->table('portaria_autorizacoes', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('protocolo', 'string', ['limit' => 32, 'null' => true])
            ->addColumn('visitante_id', 'integer', ['signed' => false])
            ->addColumn('anfitriao_user_id', 'integer', ['signed' => false])
            ->addColumn('criado_por_user_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('destino_departamento_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('motivo', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('tipo', 'string', ['limit' => 40, 'default' => 'periodo'])
            ->addColumn('data_inicio', 'date')
            ->addColumn('data_fim', 'date')
            ->addColumn('hora_inicio', 'time', ['null' => true])
            ->addColumn('hora_fim', 'time', ['null' => true])
            ->addColumn('dias_permitidos', 'string', ['limit' => 40, 'null' => true, 'comment' => 'Ex.: 1,2,3,4,5 (seg-sex)'])
            ->addColumn('status', 'string', ['limit' => 30, 'default' => 'aguardando'])
            ->addColumn('origem', 'string', ['limit' => 30, 'default' => 'agendada', 'comment' => 'agendada|nao_programada'])
            ->addColumn('veiculo_placa', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('observacoes', 'text', ['null' => true])
            ->addColumn('autorizado_em', 'datetime', ['null' => true])
            ->addColumn('autorizado_por_user_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'null' => true,
            ])
            ->addIndex(['protocolo'], ['unique' => true, 'name' => 'uq_portaria_autorizacoes_protocolo'])
            ->addIndex(['visitante_id'], ['name' => 'idx_portaria_aut_visitante'])
            ->addIndex(['anfitriao_user_id'], ['name' => 'idx_portaria_aut_anfitriao'])
            ->addIndex(['status'], ['name' => 'idx_portaria_aut_status'])
            ->addIndex(['data_inicio', 'data_fim'], ['name' => 'idx_portaria_aut_periodo'])
            ->create();
    }

    private function createAutorizacaoContatos(): void
    {
        if ($this->hasTable('portaria_autorizacao_contatos')) {
            return;
        }

        $this->table('portaria_autorizacao_contatos', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('autorizacao_id', 'integer', ['signed' => false])
            ->addColumn('canal', 'string', ['limit' => 30])
            ->addColumn('resultado', 'string', ['limit' => 40, 'null' => true])
            ->addColumn('porteiro_user_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('ocorrido_em', 'datetime')
            ->addColumn('observacoes', 'text', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['autorizacao_id'], ['name' => 'idx_portaria_aut_contatos_aut'])
            ->create();
    }

    private function createMovimentacoes(): void
    {
        if ($this->hasTable('portaria_movimentacoes')) {
            return;
        }

        $this->table('portaria_movimentacoes', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'integer', ['identity' => true, 'signed' => false])
            ->addColumn('autorizacao_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('visitante_id', 'integer', ['signed' => false])
            ->addColumn('tipo', 'string', ['limit' => 20])
            ->addColumn('ponto_controle_id', 'integer', ['signed' => false])
            ->addColumn('porteiro_user_id', 'integer', ['signed' => false])
            ->addColumn('ocorrido_em', 'datetime')
            ->addColumn('regularizada', 'boolean', ['default' => 0])
            ->addColumn('motivo_regularizacao', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('movimentacao_entrada_id', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('permanencia_excedida', 'boolean', ['default' => 0])
            ->addColumn('observacoes', 'text', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['visitante_id', 'ocorrido_em'], ['name' => 'idx_portaria_mov_visitante_data'])
            ->addIndex(['autorizacao_id'], ['name' => 'idx_portaria_mov_aut'])
            ->addIndex(['tipo'], ['name' => 'idx_portaria_mov_tipo'])
            ->addIndex(['ponto_controle_id'], ['name' => 'idx_portaria_mov_ponto'])
            ->create();
    }

    private function registerGroupAndPages(): void
    {
        if (!$this->hasTable('adms_pages') || !$this->hasTable('adms_groups_pages')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $conn = $this->getAdapter()->getConnection();

        $existsGroup = $this->fetchRow(
            'SELECT id FROM adms_groups_pages WHERE name = ' . $conn->quote(self::GROUP_NAME) . ' LIMIT 1'
        );
        if (!$existsGroup) {
            $hasUpdated = $this->table('adms_groups_pages')->hasColumn('updated_at');
            if ($hasUpdated) {
                $this->execute(
                    'INSERT INTO adms_groups_pages (name, obs, created_at, updated_at) VALUES ('
                    . $conn->quote(self::GROUP_NAME) . ', '
                    . $conn->quote('Controle de acesso físico, visitas e portaria (ADR-0009)') . ', '
                    . $conn->quote($now) . ', '
                    . $conn->quote($now) . ')'
                );
            } else {
                $this->execute(
                    'INSERT INTO adms_groups_pages (name, obs, created_at) VALUES ('
                    . $conn->quote(self::GROUP_NAME) . ', '
                    . $conn->quote('Controle de acesso físico, visitas e portaria (ADR-0009)') . ', '
                    . $conn->quote($now) . ')'
                );
            }
        }

        $groupRow = $this->fetchRow(
            'SELECT id FROM adms_groups_pages WHERE name = ' . $conn->quote(self::GROUP_NAME) . ' LIMIT 1'
        );
        if (!$groupRow) {
            return;
        }
        $gid = (int) $groupRow['id'];

        $pages = [
            ['Painel Portaria', 'PortariaPainel', 'portaria-painel', 'Dashboard operacional da portaria.'],
            ['Visitantes (Portaria)', 'PortariaVisitantes', 'portaria-visitantes', 'Lista e auditoria de visitantes/termos.'],
            ['Cadastrar Visitante (Portaria)', 'PortariaVisitantesCreate', 'portaria-visitantes-create', 'Cadastrar visitante externo.'],
            ['Visualizar Visitante (Portaria)', 'PortariaVisitantesView', 'portaria-visitantes-view', 'Ficha do visitante, termos e histórico.'],
            ['Editar Visitante (Portaria)', 'PortariaVisitantesUpdate', 'portaria-visitantes-update', 'Editar cadastro do visitante.'],
            ['Pontos de Controle', 'PortariaPontos', 'portaria-pontos', 'Pontos físicos de entrada/saída.'],
            ['Cadastrar Ponto de Controle', 'PortariaPontosCreate', 'portaria-pontos-create', 'Cadastrar ponto de controle.'],
            ['Editar Ponto de Controle', 'PortariaPontosUpdate', 'portaria-pontos-update', 'Editar ponto de controle.'],
            ['Autorizações de Visita', 'PortariaAutorizacoes', 'portaria-autorizacoes', 'Agendamentos e não programados.'],
            ['Cadastrar Autorização', 'PortariaAutorizacoesCreate', 'portaria-autorizacoes-create', 'Agendar visita ou registrar não programado.'],
            ['Visualizar Autorização', 'PortariaAutorizacoesView', 'portaria-autorizacoes-view', 'Detalhe, contatos e liberação.'],
            ['Movimentações Portaria', 'PortariaMovimentacoes', 'portaria-movimentacoes', 'Registrar entrada/saída e regularização.'],
        ];

        foreach ($pages as [$name, $controller, $url, $obs]) {
            $existing = $this->fetchRow(
                'SELECT id FROM adms_pages WHERE controller = ' . $conn->quote($controller) . ' LIMIT 1'
            );
            if ($existing) {
                continue;
            }

            $this->execute(
                'INSERT INTO adms_pages
                    (name, controller, controller_url, directory, obs, public_page, default_page, page_status,
                     adms_packages_page_id, adms_groups_page_id, created_at, updated_at)
                 VALUES ('
                . $conn->quote($name) . ', '
                . $conn->quote($controller) . ', '
                . $conn->quote($url) . ', '
                . $conn->quote('portaria') . ', '
                . $conn->quote($obs) . ', '
                . '0, 0, 1, 1, '
                . $gid . ', '
                . $conn->quote($now) . ', '
                . $conn->quote($now)
                . ')'
            );
        }

        $cacheDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'system';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0775, true);
        }
        @file_put_contents(
            $cacheDir . DIRECTORY_SEPARATOR . 'menu_permission_version.txt',
            (string) time()
        );
    }
}
