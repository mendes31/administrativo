-- Adicionar páginas do Dashboard de Vendas SAP B1

-- Dashboard principal
INSERT INTO adms_pages (name, controller, controller_url, directory, obs, public_page, page_status, adms_packages_page_id, adms_groups_page_id)
VALUES 
('Dashboard de Vendas SAP B1', 'SalesDashboard', 'sales-dashboard', 'reports', 'Dashboard interativo de vendas do SAP B1 com filtros por ano, mês, vendedor e grupo de parceiro', 0, 1, 1, 35),
('API - Dados Dashboard Vendas', 'SalesDashboardData', 'sales-dashboard-data', 'reports', 'API para buscar dados filtrados do dashboard de vendas', 0, 1, 1, 35);

-- Para dar permissão a todos os administradores (nível 1):
-- Buscar o ID das páginas recém criadas e adicionar permissões conforme necessário

