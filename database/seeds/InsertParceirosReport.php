<?php

use Phinx\Seed\AbstractSeed;

/**
 * Criar relatório de Parceiros de Negócios para usar em filtros
 */
class InsertParceirosReport extends AbstractSeed
{
    public function run(): void
    {
        $this->execute("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';");
        
        // Verificar se já existe
        $existing = $this->fetchRow("SELECT id FROM adms_dynamic_reports WHERE name = '[FILTRO] Parceiros'");
        
        if ($existing) {
            echo "ℹ️  Relatório '[FILTRO] Parceiros' já existe (ID: {$existing['id']})\n";
            return;
        }
        
        // Query fornecida pelo usuário
        $sql = 'SELECT
    T0."GroupCode" AS "GrupoPNId",
    T0."CardCode" AS "ParceiroID",
    T0."CntctPrsn" AS "PessoaContato",
    T0."CardName" AS "RazãoSocial",
    T0."Phone1" AS "Telefone",
    T0."Address" AS "Endereço",
    T0."City" AS "Cidade",
    T0."Country" AS "País",
    T0."Currency" AS "Moeda",
    T0."E_Mail" AS "Email",
    T0."CreateDate" AS "DataCadastro",
    T0."ShipToDef" AS "TipoEntrega",
    T0."Deleted" AS "Eliminado",
    T0."Priority" AS "Prioridade",
    T0."CreditLine" AS "LimiteCredito",
    T0."DebtLine" AS "LimiteCompromisso",
    T0."Balance" AS "SaldoConta",
    CASE WHEN T0."OrdersBal" < 0 
        THEN 0 
        ELSE T0."OrdersBal" 
    END AS "Pedidos",
    CASE WHEN T0."OrdersBal" < 0 
        THEN (T0."CreditLine" - T0."Balance") 
        ELSE (T0."CreditLine" - (T0."Balance" + T0."OrdersBal")) 
    END AS "LimiteDisponivel",
    CASE T0."CmpPrivate" 
        WHEN \'C\' THEN \'Empresa\' 
        WHEN \'G\' THEN \'Governo\' 
        WHEN \'I\' THEN \'PessoaFisica\' 
    END AS "TipoEmpresa",
    CASE T0."CardType" 
        WHEN \'C\' THEN \'Cliente\' 
        WHEN \'L\' THEN \'Lead\' 
        WHEN \'S\' THEN \'Fornecedor\' 
    END AS "TipoParceiro"
FROM "OCRD" T0
ORDER BY T0."CardCode"';
        
        // Inserir relatório
        $report = [
            'name' => '[FILTRO] Parceiros',
            'description' => 'Lista de parceiros de negócios (clientes/fornecedores) para usar em filtros',
            'created_by' => 1,
            'is_public' => 1,
            'data_source' => 'sap_b1',
            'custom_sql' => $sql,
            'query_mode' => 'custom_sql',
            'fields' => '[]',
            'filters' => '[]',
            'groupby' => '[]',
            'orderby' => '[]',
            'visualization_type' => 'table',
            'chart_config' => '[]',
            'category' => 'Filtros',
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $table = $this->table('adms_dynamic_reports');
        $table->insert($report)->saveData();
        
        $reportId = $this->getAdapter()->getConnection()->lastInsertId();
        
        echo "✅ Relatório '[FILTRO] Parceiros' criado! (ID: {$reportId})\n";
        echo "   Este relatório pode ser usado para popular filtros de parceiros\n";
    }
}

