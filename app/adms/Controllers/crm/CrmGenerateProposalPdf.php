<?php

namespace App\adms\Controllers\crm;

use App\adms\Models\Repository\CrmOpportunitiesRepository;
use Mpdf\Mpdf;

/**
 * Geração de Proposta Comercial Profissional (PDF) para Oportunidade CRM
 * 
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmGenerateProposalPdf
{
    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            header('Location: ' . $_ENV['URL_ADM'] . 'crm-kanban-pipeline');
            return;
        }

        $repo = new CrmOpportunitiesRepository();
        $opp = $repo->getOpportunity((int) $id);
        
        if (!$opp) {
            header('Location: ' . $_ENV['URL_ADM'] . 'crm-kanban-pipeline');
            return;
        }

        // Preparar dados
        $code = htmlspecialchars($opp['code'] ?? 'N/A');
        $title = htmlspecialchars($opp['title'] ?? 'Proposta Comercial');
        $partnerName = htmlspecialchars($opp['partner_name'] ?? 'N/A');
        $partnerEmail = htmlspecialchars($opp['partner_email'] ?? '');
        $partnerPhone = htmlspecialchars($opp['partner_phone'] ?? '');
        $partnerMobile = htmlspecialchars($opp['partner_mobile'] ?? '');
        $value = number_format((float)($opp['value'] ?? 0), 2, ',', '.');
        $probability = (int)($opp['probability'] ?? 0);
        $expectedDate = $opp['expected_close_date'] ? date('d/m/Y', strtotime($opp['expected_close_date'])) : 'A definir';
        $description = nl2br(htmlspecialchars($opp['description'] ?? ''));
        $responsible = htmlspecialchars($opp['responsible_name'] ?? 'N/A');
        $createdDate = date('d/m/Y', strtotime($opp['created_at']));
        $validUntil = date('d/m/Y', strtotime('+30 days'));
        
        // Construir HTML da proposta com layout profissional
        $html = $this->getProposalHTML($code, $title, $partnerName, $partnerEmail, $partnerPhone, $partnerMobile, 
                                       $value, $probability, $expectedDate, $description, $responsible, 
                                       $createdDate, $validUntil);

        // Gerar PDF com mPDF
        try {
            $mpdf = new Mpdf([
                'tempDir' => sys_get_temp_dir(),
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 15,
                'margin_bottom' => 15,
                'margin_header' => 0,
                'margin_footer' => 0,
                'autoPageBreak' => true,
                'setAutoTopMargin' => 'pad',
                'setAutoBottomMargin' => 'pad'
            ]);
            
            $mpdf->WriteHTML($html);
            
            $filename = 'Proposta_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $code) . '.pdf';
            $mpdf->Output($filename, 'I'); // I = inline no navegador
            
        } catch (\Exception $e) {
            error_log("Erro ao gerar PDF: " . $e->getMessage());
            $_SESSION['msg'] = "Erro ao gerar PDF da proposta: " . $e->getMessage();
            $_SESSION['msg_type'] = "danger";
            header('Location: ' . $_ENV['URL_ADM'] . 'crm-view-opportunity/' . $id);
        }
    }

    /**
     * Gerar HTML da proposta comercial PREMIUM
     */
    private function getProposalHTML(
        string $code, string $title, string $partnerName, string $partnerEmail, 
        string $partnerPhone, string $partnerMobile, string $value, int $probability, 
        string $expectedDate, string $description, string $responsible, 
        string $createdDate, string $validUntil
    ): string {
        $companyName = $_ENV['COMPANY_NAME'] ?? 'Sua Empresa';
        $companyEmail = $_ENV['COMPANY_EMAIL'] ?? 'contato@suaempresa.com.br';
        $companyPhone = $_ENV['COMPANY_PHONE'] ?? '(00) 0000-0000';
        $companyAddress = $_ENV['COMPANY_ADDRESS'] ?? 'Endereço da Empresa';
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', 'Helvetica', Arial, sans-serif;
            color: #2c3e50;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }
        
        /* ===== CABEÇALHO CORPORATIVO ===== */
        .page-header {
            background: linear-gradient(135deg, #1f6b45 0%, #2E9263 50%, #3daf78 100%);
            color: white;
            padding: 30px 40px;
            position: relative;
            overflow: hidden;
            page-break-inside: avoid;
            margin-bottom: 20px;
        }
        .page-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }
        .company-info {
            position: relative;
            z-index: 2;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin: 0 0 5px 0;
            letter-spacing: 1px;
        }
        .company-tagline {
            font-size: 12px;
            opacity: 0.9;
            margin: 0 0 15px 0;
        }
        .proposal-header {
            margin-top: 20px;
            border-top: 2px solid rgba(255,255,255,0.3);
            padding-top: 20px;
        }
        .proposal-title {
            font-size: 32px;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .proposal-meta {
            display: table;
            width: 100%;
            margin-top: 15px;
            font-size: 11px;
        }
        .meta-item {
            display: table-cell;
            padding: 8px 15px;
            background: rgba(255,255,255,0.15);
            border-right: 1px solid rgba(255,255,255,0.1);
        }
        .meta-item:last-child {
            border-right: none;
        }
        .meta-label {
            display: block;
            opacity: 0.8;
            font-size: 10px;
            margin-bottom: 3px;
        }
        .meta-value {
            font-weight: bold;
            font-size: 12px;
        }
        
        /* ===== CONTEÚDO ===== */
        .content-wrapper {
            padding: 20px 30px;
        }
        
        /* Cards Modernos */
        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border: 1px solid #e8ecef;
            page-break-inside: avoid;
        }
        .card-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid #2E9263;
        }
        .card-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #2E9263, #1f6b45);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 22px;
            margin-right: 15px;
            flex-shrink: 0;
        }
        .card-title {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
            margin: 0;
        }
        
        /* Grid de Informações */
        .info-grid {
            display: table;
            width: 100%;
        }
        .info-row {
            display: table-row;
        }
        .info-cell {
            display: table-cell;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .info-cell:first-child {
            width: 35%;
            font-weight: 600;
            color: #555;
        }
        .info-cell:last-child {
            color: #2c3e50;
        }
        
        /* Valor em Destaque PREMIUM */
        .value-showcase {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            margin: 20px 0;
            box-shadow: 0 10px 30px rgba(255, 215, 0, 0.3);
            border: 3px solid #f4c430;
            position: relative;
            overflow: hidden;
            page-break-inside: avoid;
        }
        .value-showcase::before {
            content: '★';
            position: absolute;
            top: 10px;
            left: 20px;
            font-size: 60px;
            color: rgba(255,255,255,0.3);
        }
        .value-showcase::after {
            content: '★';
            position: absolute;
            bottom: 10px;
            right: 20px;
            font-size: 60px;
            color: rgba(255,255,255,0.3);
        }
        .value-label {
            font-size: 14px;
            color: #705c00;
            font-weight: bold;
            margin: 0 0 10px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .value-amount {
            font-size: 48px;
            font-weight: bold;
            color: #1f6b45;
            margin: 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
            position: relative;
            z-index: 2;
        }
        .value-subtext {
            font-size: 12px;
            color: #705c00;
            margin: 10px 0 0 0;
            font-weight: 600;
        }
        
        /* Tabela Profissional */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .data-table thead {
            background: linear-gradient(135deg, #2E9263, #1f6b45);
            color: white;
        }
        .data-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .data-table td {
            padding: 15px;
            border-bottom: 1px solid #e8ecef;
            font-size: 14px;
        }
        .data-table tr:hover {
            background: #f8f9fa;
        }
        .data-table tr:last-child td {
            border-bottom: none;
        }
        
        /* Badge de Status */
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        /* Descrição com Formatação */
        .description-content {
            background: #fafbfc;
            border-left: 4px solid #2E9263;
            padding: 20px;
            border-radius: 0 8px 8px 0;
            line-height: 1.8;
            font-size: 14px;
            color: #34495e;
        }
        
        /* Seção de Termos */
        .terms-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            page-break-inside: avoid;
        }
        .terms-title {
            font-size: 16px;
            font-weight: bold;
            color: #2E9263;
            margin: 0 0 15px 0;
            display: flex;
            align-items: center;
        }
        .terms-title::before {
            content: '⚖';
            margin-right: 10px;
            font-size: 20px;
        }
        .terms-list {
            margin: 0;
            padding-left: 20px;
        }
        .terms-list li {
            margin-bottom: 10px;
            color: #555;
            font-size: 13px;
            line-height: 1.6;
        }
        
        /* Assinatura Moderna */
        .signature-section {
            margin-top: 40px;
            page-break-inside: avoid;
        }
        .signature-grid {
            display: table;
            width: 100%;
            margin-top: 40px;
        }
        .signature-box {
            display: table-cell;
            width: 48%;
            text-align: center;
            padding: 20px;
        }
        .signature-box:first-child {
            padding-right: 30px;
        }
        .signature-box:last-child {
            padding-left: 30px;
        }
        .signature-line {
            border-top: 2px solid #2c3e50;
            margin: 80px auto 15px auto;
            width: 100%;
        }
        .signature-name {
            font-weight: bold;
            color: #2c3e50;
            font-size: 14px;
            margin: 5px 0;
        }
        .signature-role {
            font-size: 11px;
            color: #7f8c8d;
            margin: 3px 0;
        }
        .signature-date {
            font-size: 10px;
            color: #95a5a6;
            margin: 10px 0 0 0;
        }
        
        /* Rodapé Corporativo */
        .page-footer {
            background: #2c3e50;
            color: white;
            padding: 20px 30px;
            margin-top: 30px;
            font-size: 11px;
            line-height: 1.8;
            page-break-inside: avoid;
        }
        .footer-content {
            text-align: center;
        }
        .footer-company {
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 8px;
        }
        .footer-contact {
            opacity: 0.8;
        }
        
        /* Alert Box */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 5px solid;
            page-break-inside: avoid;
        }
        .alert-info {
            background: #e7f3ff;
            border-color: #2196F3;
            color: #0d47a1;
        }
        .alert-success {
            background: #e8f5e9;
            border-color: #4CAF50;
            color: #1b5e20;
        }
    </style>
</head>
<body>

<!-- ============ PÁGINA 1: CAPA E DADOS ============ -->
<div class="page-header">
    <div class="company-info">
        <div class="company-name">{$companyName}</div>
        <div class="company-tagline">Excelência em Soluções Comerciais</div>
    </div>
    <div class="proposal-header">
        <div class="proposal-title">Proposta Comercial</div>
        <div class="proposal-meta">
            <div class="meta-item">
                <span class="meta-label">CÓDIGO</span>
                <span class="meta-value">{$code}</span>
            </div>
            <div class="meta-item">
                <span class="meta-label">EMISSÃO</span>
                <span class="meta-value">{$createdDate}</span>
            </div>
            <div class="meta-item">
                <span class="meta-label">VALIDADE</span>
                <span class="meta-value">{$validUntil}</span>
            </div>
            <div class="meta-item">
                <span class="meta-label">RESPONSÁVEL</span>
                <span class="meta-value">{$responsible}</span>
            </div>
        </div>
    </div>
</div>

<div class="content-wrapper">
    <!-- DADOS DO CLIENTE -->
    <div class="card">
        <div class="card-header">
            <div class="card-icon">👤</div>
            <div class="card-title">Dados do Cliente</div>
        </div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-cell">Nome/Razão Social</div>
                <div class="info-cell">{$partnerName}</div>
            </div>
            {$this->renderInfoRow('E-mail', $partnerEmail)}
            {$this->renderInfoRow('Telefone', $partnerPhone)}
            {$this->renderInfoRow('Celular', $partnerMobile)}
        </div>
    </div>

    <!-- VALOR EM DESTAQUE -->
    <div class="value-showcase">
        <p class="value-label">Valor Total da Proposta</p>
        <p class="value-amount">R$ {$value}</p>
        <p class="value-subtext">Valor sujeito a negociação comercial</p>
    </div>

    <!-- DETALHES DA OPORTUNIDADE -->
    <div class="card">
        <div class="card-header">
            <div class="card-icon">📊</div>
            <div class="card-title">Detalhes da Oportunidade</div>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 40%;">Descrição</th>
                    <th>Informação</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Título do Projeto</strong></td>
                    <td>{$title}</td>
                </tr>
                <tr>
                    <td><strong>Consultor Responsável</strong></td>
                    <td>{$responsible}</td>
                </tr>
                <tr>
                    <td><strong>Probabilidade de Fechamento</strong></td>
                    <td><span class="badge badge-success">{$probability}%</span></td>
                </tr>
                <tr>
                    <td><strong>Previsão de Fechamento</strong></td>
                    <td><span class="badge badge-info">{$expectedDate}</span></td>
                </tr>
            </tbody>
        </table>
    </div>

    {$this->renderDescriptionSection($description)}

    <!-- CONDIÇÕES COMERCIAIS -->
    <div class="card">
        <div class="card-header">
            <div class="card-icon">💼</div>
            <div class="card-title">Condições Comerciais</div>
        </div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-cell">Forma de Pagamento</div>
                <div class="info-cell">A combinar conforme negociação comercial</div>
            </div>
            <div class="info-row">
                <div class="info-cell">Prazo de Entrega</div>
                <div class="info-cell">Conforme especificado em contrato</div>
            </div>
            <div class="info-row">
                <div class="info-cell">Validade da Proposta</div>
                <div class="info-cell">{$validUntil}</div>
            </div>
            <div class="info-row">
                <div class="info-cell">Impostos e Encargos</div>
                <div class="info-cell">Conforme legislação vigente</div>
            </div>
            <div class="info-row">
                <div class="info-cell">Garantia</div>
                <div class="info-cell">Conforme termos contratuais</div>
            </div>
        </div>
    </div>

    <!-- TERMOS E CONDIÇÕES -->
    <div class="terms-section">
        <div class="terms-title">Termos e Condições Gerais</div>
        <ul class="terms-list">
            <li><strong>Validade:</strong> Esta proposta é válida até {$validUntil} e está sujeita à disponibilidade de estoque e capacidade de produção.</li>
            <li><strong>Confidencialidade:</strong> As informações contidas nesta proposta são confidenciais e destinadas exclusivamente ao cliente especificado.</li>
            <li><strong>Aceite:</strong> O aceite desta proposta deverá ser formalizado por escrito através de assinatura em contrato específico.</li>
            <li><strong>Alterações:</strong> Quaisquer alterações nas especificações ou quantidades poderão impactar os valores apresentados.</li>
            <li><strong>Faturamento:</strong> O faturamento será realizado após confirmação do pedido e verificação de crédito.</li>
            <li><strong>Entrega:</strong> Os prazos de entrega começam a contar após a aprovação do pedido e confirmação de pagamento.</li>
            <li><strong>Cancelamento:</strong> Pedidos cancelados após confirmação estarão sujeitos a taxas conforme política comercial.</li>
            <li><strong>Garantia:</strong> Os produtos/serviços possuem garantia conforme especificações técnicas e legislação vigente.</li>
        </ul>
    </div>

    <!-- INFORMAÇÕES IMPORTANTES -->
    <div class="alert alert-info">
        <strong>📌 Importante:</strong> Para aceitar esta proposta ou solicitar esclarecimentos, entre em contato com {$responsible} através dos canais de comunicação informados no rodapé deste documento.
    </div>

    <!-- ASSINATURA -->
    <div class="signature-section">
        <div class="alert alert-success" style="text-align: center;">
            <strong>✓ Aceite da Proposta</strong><br>
            Ao assinar este documento, confirmo que li, entendi e concordo com todos os termos apresentados nesta proposta comercial.
        </div>
        
        <div class="signature-grid">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-name">{$companyName}</div>
                <div class="signature-role">{$responsible}</div>
                <div class="signature-role">Consultor Comercial</div>
                <div class="signature-date">Data: ____/____/________</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-name">{$partnerName}</div>
                <div class="signature-role">Cliente</div>
                <div class="signature-date">Data: ____/____/________</div>
            </div>
        </div>
    </div>
</div>

<!-- RODAPÉ CORPORATIVO -->
<div class="page-footer">
    <div class="footer-content">
        <div class="footer-company">{$companyName}</div>
        <div class="footer-contact">
            📧 {$companyEmail} | 📞 {$companyPhone}<br>
            📍 {$companyAddress}
        </div>
    </div>
</div>

</body>
</html>
HTML;
    }

    /**
     * Renderizar linha de informação (para grid)
     */
    private function renderInfoRow(string $label, string $value): string
    {
        if (empty($value)) {
            return '';
        }
        return <<<HTML
            <div class="info-row">
                <div class="info-cell">{$label}</div>
                <div class="info-cell">{$value}</div>
            </div>
HTML;
    }

    /**
     * Renderizar seção de descrição detalhada
     */
    private function renderDescriptionSection(string $description): string
    {
        if (empty($description)) {
            return '';
        }
        return <<<HTML
    <div class="card">
        <div class="card-header">
            <div class="card-icon">📝</div>
            <div class="card-title">Descrição Detalhada</div>
        </div>
        <div class="description-content">
            {$description}
        </div>
    </div>
HTML;
    }
}


