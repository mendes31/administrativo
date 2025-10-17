<?php
require_once 'vendor/autoload.php';
use App\adms\Models\Services\DbConnection;

try {
    $db = new DbConnection();
    
    // Verificar se a página já existe
    $stmt = $db->getConnection()->prepare('SELECT id FROM adms_pages WHERE controller_url = :url');
    $stmt->execute(['url' => 'view-plan-indicators']);
    $existing = $stmt->fetch();
    
    if ($existing) {
        echo "✅ Página view-plan-indicators já existe no banco (ID: " . $existing['id'] . ")\n";
    } else {
        // Inserir a página no banco
        $sql = "INSERT INTO adms_pages (name, controller, controller_url, directory, obs, public_page, page_status, adms_packages_page_id, adms_groups_page_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $db->getConnection()->prepare($sql);
        $result = $stmt->execute([
            'Indicadores do Plano',
            'ViewPlanIndicators',
            'view-plan-indicators',
            'strategicPlans',
            'Página para visualizar os indicadores de um plano estratégico específico.',
            0,
            1,
            1,
            29
        ]);
        
        if ($result) {
            $id = $db->getConnection()->lastInsertId();
            echo "✅ Página view-plan-indicators inserida com sucesso! (ID: $id)\n";
        } else {
            echo "❌ Erro ao inserir a página\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
?>



