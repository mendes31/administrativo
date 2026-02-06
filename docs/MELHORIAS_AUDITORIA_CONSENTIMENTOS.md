# Melhorias para Auditoria de Consentimentos LGPD

## 📋 O que outras ferramentas profissionais coletam

Ferramentas como **OneTrust**, **TrustArc**, **Osano** e **Metomic** coletam as seguintes informações para auditoria:

### 1. **Informações Técnicas de Coleta**
- ✅ **IP Address** (endereço IP do titular)
- ✅ **User Agent** (navegador, sistema operacional, dispositivo)
- ✅ **Geolocalização** (país, região, cidade - baseado em IP)
- ✅ **Device Fingerprint** (hash único do dispositivo)
- ✅ **Referrer URL** (de onde veio o usuário)
- ✅ **Session ID** (identificador da sessão)

### 2. **Informações de Contexto**
- ✅ **Método de Coleta** (web form, API, email, SMS, etc)
- ✅ **URL de Origem** (página onde o consentimento foi dado)
- ✅ **Idioma do Navegador** (pt-BR, en-US, etc)
- ✅ **Timezone** (fuso horário do usuário)
- ✅ **Screen Resolution** (resolução da tela - para detectar bots)

### 3. **Informações de Integridade**
- ✅ **Hash do Consentimento** (SHA-256 do conteúdo para garantir integridade)
- ✅ **Assinatura Digital** (opcional, para casos críticos)
- ✅ **Timestamp com Milissegundos** (precisão máxima)
- ✅ **Versão do Termo Aceito** (link para o termo específico)

### 4. **Informações de Auditoria de Alterações**
- ✅ **ID do Usuário que Criou** (quem registrou no sistema)
- ✅ **ID do Usuário que Revogou** (quem revogou, se aplicável)
- ✅ **ID do Usuário que Atualizou** (quem fez alterações)
- ✅ **Histórico de Alterações** (log de todas as mudanças)
- ✅ **Motivo da Revogação** (se aplicável)
- ✅ **Data/Hora da Revogação** (timestamp preciso)

### 5. **Informações de Compliance**
- ✅ **Base Legal** (consentimento, legítimo interesse, etc)
- ✅ **Categoria de Dados** (dados pessoais, sensíveis, etc)
- ✅ **Finalidade Específica** (link para finalidade cadastrada)
- ✅ **Prazo de Retenção** (quanto tempo os dados serão mantidos)
- ✅ **Compartilhamento com Terceiros** (sim/não, quais terceiros)

### 6. **Informações de Rastreamento**
- ✅ **Tentativas de Acesso** (quantas vezes tentou acessar antes de aceitar)
- ✅ **Tempo de Leitura** (quanto tempo levou para aceitar)
- ✅ **Scroll Depth** (até onde rolou a página antes de aceitar)
- ✅ **Múltiplos Dispositivos** (se aceitou em mais de um dispositivo)

---

## 🔧 Proposta de Implementação

### **Fase 1: Campos Essenciais para Auditoria**

#### 1.1. Adicionar campos na tabela `lgpd_consentimentos`

```sql
-- Campos de auditoria técnica
ip_address VARCHAR(45) NULL COMMENT 'Endereço IP do titular',
user_agent TEXT NULL COMMENT 'User Agent do navegador',
geolocation_country VARCHAR(2) NULL COMMENT 'Código do país (ISO 3166-1 alpha-2)',
geolocation_region VARCHAR(100) NULL COMMENT 'Região/Estado',
geolocation_city VARCHAR(100) NULL COMMENT 'Cidade',
device_fingerprint VARCHAR(64) NULL COMMENT 'Hash do dispositivo',
referrer_url TEXT NULL COMMENT 'URL de origem',
session_id VARCHAR(255) NULL COMMENT 'ID da sessão',

-- Campos de contexto
collection_method ENUM('web_form', 'api', 'email', 'sms', 'sistema_login', 'importacao') DEFAULT 'web_form',
origin_url TEXT NULL COMMENT 'URL da página onde foi coletado',
browser_language VARCHAR(10) NULL COMMENT 'Idioma do navegador',
timezone VARCHAR(50) NULL COMMENT 'Fuso horário',
screen_resolution VARCHAR(20) NULL COMMENT 'Resolução da tela (ex: 1920x1080)',

-- Campos de integridade
consent_hash VARCHAR(64) NULL COMMENT 'SHA-256 do conteúdo do consentimento',
timestamp_milliseconds BIGINT NULL COMMENT 'Timestamp com milissegundos',

-- Campos de auditoria de alterações
created_by_user_id INT NULL COMMENT 'ID do usuário que criou (se foi criado manualmente)',
revoked_by_user_id INT NULL COMMENT 'ID do usuário que revogou',
revoked_at DATETIME NULL COMMENT 'Data/hora da revogação',
revocation_reason TEXT NULL COMMENT 'Motivo da revogação',
updated_by_user_id INT NULL COMMENT 'ID do usuário que atualizou',

-- Campos de compliance
base_legal_id INT NULL COMMENT 'ID da base legal (FK para lgpd_bases_legais)',
categoria_dados_id INT NULL COMMENT 'ID da categoria de dados',
finalidade_id INT NULL COMMENT 'ID da finalidade específica',
prazo_retencao_dias INT NULL COMMENT 'Prazo de retenção em dias',
compartilhamento_terceiros BOOLEAN DEFAULT 0 COMMENT 'Se compartilha com terceiros',

-- Campos de rastreamento
tentativas_acesso INT DEFAULT 0 COMMENT 'Quantas vezes tentou acessar antes de aceitar',
tempo_leitura_segundos INT NULL COMMENT 'Tempo em segundos até aceitar',
scroll_depth_percent INT NULL COMMENT 'Percentual da página rolado (0-100)',
```

#### 1.2. Criar tabela de histórico de alterações

```sql
CREATE TABLE lgpd_consentimentos_historico (
    id INT PRIMARY KEY AUTO_INCREMENT,
    consentimento_id INT NOT NULL,
    acao ENUM('criado', 'atualizado', 'revogado', 'reativado', 'expirado') NOT NULL,
    usuario_id INT NULL COMMENT 'ID do usuário que fez a ação',
    dados_anteriores JSON NULL COMMENT 'Snapshot dos dados antes da alteração',
    dados_novos JSON NULL COMMENT 'Snapshot dos dados após a alteração',
    motivo TEXT NULL COMMENT 'Motivo da alteração',
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_consentimento (consentimento_id),
    INDEX idx_acao (acao),
    INDEX idx_usuario (usuario_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (consentimento_id) REFERENCES lgpd_consentimentos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### **Fase 2: Funcionalidades de Coleta**

#### 2.1. Helper para coletar informações técnicas

```php
// app/adms/Helpers/LgpdAuditHelper.php
class LgpdAuditHelper {
    public static function collectTechnicalData(): array {
        return [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'geolocation_country' => self::getCountryFromIP($_SERVER['REMOTE_ADDR'] ?? ''),
            'referrer_url' => $_SERVER['HTTP_REFERER'] ?? null,
            'session_id' => session_id() ?: null,
            'browser_language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null,
            'timezone' => self::getClientTimezone(),
            'screen_resolution' => $_POST['screen_resolution'] ?? null,
            'device_fingerprint' => self::generateDeviceFingerprint(),
            'timestamp_milliseconds' => (int)(microtime(true) * 1000),
        ];
    }
    
    private static function generateDeviceFingerprint(): string {
        $data = [
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            $_POST['screen_resolution'] ?? '',
        ];
        return hash('sha256', implode('|', $data));
    }
    
    private static function getCountryFromIP(string $ip): ?string {
        // Usar serviço de geolocalização (MaxMind, IP2Location, etc)
        // Por enquanto, retornar null
        return null;
    }
    
    private static function getClientTimezone(): ?string {
        return $_POST['timezone'] ?? null;
    }
}
```

#### 2.2. Gerar hash de integridade

```php
public static function generateConsentHash(array $consentData): string {
    $dataToHash = [
        $consentData['titular_email'],
        $consentData['finalidade'],
        $consentData['data_consentimento'],
        $consentData['versao_termo'],
        $consentData['status'],
    ];
    
    return hash('sha256', json_encode($dataToHash));
}
```

---

### **Fase 3: Registro de Histórico**

#### 3.1. Repository com histórico automático

```php
// Adicionar método no LgpdConsentimentosRepository
public function createWithHistory(array $data, ?int $userId = null): bool {
    $conn = $this->getConnection();
    $conn->beginTransaction();
    
    try {
        // Coletar dados técnicos
        $auditData = LgpdAuditHelper::collectTechnicalData();
        $data = array_merge($data, $auditData);
        
        // Gerar hash
        $data['consent_hash'] = LgpdAuditHelper::generateConsentHash($data);
        
        // Criar consentimento
        $success = $this->create($data);
        
        if ($success) {
            $consentId = $conn->lastInsertId();
            
            // Registrar no histórico
            $this->addHistory($consentId, 'criado', $userId, null, $data);
        }
        
        $conn->commit();
        return $success;
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Erro ao criar consentimento com histórico: " . $e->getMessage());
        return false;
    }
}

private function addHistory(int $consentId, string $acao, ?int $userId, ?array $dadosAnteriores, ?array $dadosNovos): void {
    $query = "INSERT INTO lgpd_consentimentos_historico 
              (consentimento_id, acao, usuario_id, dados_anteriores, dados_novos, ip_address, user_agent)
              VALUES (:consentimento_id, :acao, :usuario_id, :dados_anteriores, :dados_novos, :ip_address, :user_agent)";
    
    $stmt = $this->getConnection()->prepare($query);
    $stmt->bindValue(':consentimento_id', $consentId, PDO::PARAM_INT);
    $stmt->bindValue(':acao', $acao);
    $stmt->bindValue(':usuario_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':dados_anteriores', $dadosAnteriores ? json_encode($dadosAnteriores) : null);
    $stmt->bindValue(':dados_novos', $dadosNovos ? json_encode($dadosNovos) : null);
    $stmt->bindValue(':ip_address', $_SERVER['REMOTE_ADDR'] ?? null);
    $stmt->bindValue(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? null);
    $stmt->execute();
}
```

---

### **Fase 4: Visualização de Auditoria**

#### 4.1. Adicionar seção de auditoria na view

```php
<!-- app/adms/Views/lgpd/consentimentos/view.php -->
<!-- Adicionar após "Informações Adicionais" -->

<!-- Informações de Auditoria -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-dark">
            <div class="card-header bg-dark text-white">
                <h6 class="mb-0">
                    <i class="fas fa-shield-alt"></i>
                    Informações de Auditoria
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <strong>IP Address:</strong><br>
                        <?php echo htmlspecialchars($this->data['consentimento']['ip_address'] ?? 'N/A'); ?>
                    </div>
                    <div class="col-md-4">
                        <strong>User Agent:</strong><br>
                        <small><?php echo htmlspecialchars(substr($this->data['consentimento']['user_agent'] ?? 'N/A', 0, 100)); ?></small>
                    </div>
                    <div class="col-md-4">
                        <strong>Hash de Integridade:</strong><br>
                        <code><?php echo htmlspecialchars($this->data['consentimento']['consent_hash'] ?? 'N/A'); ?></code>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-4">
                        <strong>Método de Coleta:</strong><br>
                        <?php echo htmlspecialchars($this->data['consentimento']['collection_method'] ?? 'N/A'); ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Timestamp (ms):</strong><br>
                        <?php echo number_format($this->data['consentimento']['timestamp_milliseconds'] ?? 0); ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Device Fingerprint:</strong><br>
                        <code><?php echo htmlspecialchars(substr($this->data['consentimento']['device_fingerprint'] ?? 'N/A', 0, 32)); ?>...</code>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Histórico de Alterações -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-history"></i>
                    Histórico de Alterações
                </h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Ação</th>
                            <th>Usuário</th>
                            <th>IP</th>
                            <th>Motivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->data['historico'] ?? [] as $item): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i:s', strtotime($item['created_at'])); ?></td>
                            <td><span class="badge bg-info"><?php echo $item['acao']; ?></span></td>
                            <td><?php echo htmlspecialchars($item['usuario_nome'] ?? 'Sistema'); ?></td>
                            <td><?php echo htmlspecialchars($item['ip_address'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($item['motivo'] ?? '-'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
```

---

## 📊 Relatórios de Auditoria

### Relatórios sugeridos:

1. **Relatório de Consentimentos por IP** (detectar padrões suspeitos)
2. **Relatório de Dispositivos Múltiplos** (mesmo titular em diferentes dispositivos)
3. **Relatório de Revogações** (quem revogou, quando, por quê)
4. **Relatório de Integridade** (verificar se hashes estão corretos)
5. **Relatório de Geolocalização** (consentimentos por país/região)
6. **Relatório de Tempo de Leitura** (detectar aceites muito rápidos - possíveis bots)

---

## ✅ Prioridades de Implementação

### **Alta Prioridade** (Essencial para auditoria básica)
1. ✅ IP Address
2. ✅ User Agent
3. ✅ Hash de Integridade
4. ✅ Timestamp com milissegundos
5. ✅ Histórico de alterações
6. ✅ ID do usuário que criou/revogou

### **Média Prioridade** (Melhorias importantes)
1. ⚠️ Geolocalização (país)
2. ⚠️ Device Fingerprint
3. ⚠️ Referrer URL
4. ⚠️ Método de coleta detalhado

### **Baixa Prioridade** (Nice to have)
1. ⚠️ Screen Resolution
2. ⚠️ Scroll Depth
3. ⚠️ Tempo de leitura
4. ⚠️ Tentativas de acesso

---

## 🔒 Segurança e Privacidade

⚠️ **IMPORTANTE**: Alguns dados coletados (como IP, User Agent) são considerados dados pessoais pela LGPD. Certifique-se de:

1. ✅ Informar na política de privacidade que esses dados são coletados
2. ✅ Ter base legal para coleta (consentimento ou legítimo interesse)
3. ✅ Implementar medidas de segurança (criptografia, acesso restrito)
4. ✅ Definir prazo de retenção para dados de auditoria
5. ✅ Permitir que o titular solicite acesso/exclusão desses dados

---

## 📝 Próximos Passos

1. Criar migration para adicionar novos campos
2. Implementar `LgpdAuditHelper`
3. Atualizar `LgpdConsentimentosRepository` com métodos de histórico
4. Atualizar controllers para coletar dados técnicos
5. Criar view de histórico de alterações
6. Implementar relatórios de auditoria

