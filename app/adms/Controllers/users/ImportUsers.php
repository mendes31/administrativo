<?php

namespace App\adms\Controllers\users;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

class ImportUsers
{
    private array|string|null $data = null;

    public function index(string $action = ''): void
    {
        // Checagem básica de sessão
        if (empty($_SESSION['user_id'])) {
            $_SESSION['error'] = 'Sessão inválida! Faça login para continuar.';
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            return;
        }

        // Se a URL vier com /template, serve o arquivo diretamente
        if ($action === 'template') {
            $this->template();
            return;
        }

        $this->data['form'] = $_POST ?? [];

        if (!empty($_FILES['file']) && isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_import_users', $this->data['form']['csrf_token'])) {
            $this->processFile();
            return;
        }

        $this->view();
    }

    private function view(): void
    {
        $pageElements = [
            'title_head' => 'Importar Usuários',
            'menu' => 'list-users',
            'buttonPermission' => ['ListUsers'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/users/importUsers', $this->data);
        $loadView->loadView();
    }

    private function processFile(): void
    {
        $file = $_FILES['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->data['errors'][] = 'Falha ao enviar o arquivo.';
            $this->view();
            return;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'xlsx', 'xls'], true)) {
            $this->data['errors'][] = 'Formato inválido. Envie um arquivo CSV (recomendado).';
            $this->view();
            return;
        }

        // Suporte inicial: CSV (UTF-8 com cabeçalho). Planilhas podem ser exportadas para CSV.
        $handled = $this->processCsv($file['tmp_name']);
        if (!$handled) {
            $this->data['errors'][] = 'Não foi possível processar o arquivo. Verifique o template.';
        }
        $this->view();
    }

    private function processCsv(string $tmpPath): bool
    {
        $fp = fopen($tmpPath, 'r');
        if (!$fp) return false;

        $header = fgetcsv($fp, 0, ';');
        if (!$header) {
            fclose($fp);
            return false;
        }

        // Cabeçalhos esperados
        $expected = ['name','email','username','department_id','position_id','password','status','bloqueado','tentativas_login','senha_nunca_expira','modificar_senha_proximo_logon','data_nascimento'];
        $map = [];
        foreach ($expected as $col) {
            $idx = array_search($col, $header, true);
            $map[$col] = $idx !== false ? (int)$idx : null;
        }

        $repo = new UsersRepository();
        $created = 0; $updated = 0; $skipped = 0; $errors = 0; $rows = 1;
        $this->data['report'] = [];

        while (($row = fgetcsv($fp, 0, ';')) !== false) {
            $rows++;
            if (count(array_filter($row, fn($v)=> trim((string)$v) !== '')) === 0) continue;

            // Helpers de normalização
            $toBoolLabel = function ($val): string {
                $v = strtolower(trim((string)$val));
                // remover acentos básicos
                $v = strtr($v, ['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','é'=>'e','ê'=>'e','í'=>'i','ó'=>'o','ô'=>'o','õ'=>'o','ú'=>'u','ç'=>'c']);
                if (in_array($v, ['sim','s','yes','y','true','1'], true)) return 'Sim';
                if (in_array($v, ['nao','não','n','no','false','0','nao.','nao '], true)) return 'Não';
                return 'Não';
            };
            $toDate = function ($val): ?string {
                $v = trim((string)$val);
                if ($v === '') return null;
                // dd/mm/yyyy -> yyyy-mm-dd
                if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $v, $m)) {
                    return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
                }
                // yyyy-mm-dd já compatível
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) return $v;
                // Tentar via strtotime
                $t = strtotime($v);
                return $t ? date('Y-m-d', $t) : null;
            };

            $payload = [
                'name' => trim((string)($row[$map['name']] ?? '')),
                'email' => trim((string)($row[$map['email']] ?? '')),
                'username' => trim((string)($row[$map['username']] ?? '')),
                'user_department_id' => (int)($row[$map['department_id']] ?? 0),
                'user_position_id' => (int)($row[$map['position_id']] ?? 0),
                'password' => (string)($row[$map['password']] ?? ''),
                'status' => (string)($row[$map['status']] ?? 'Ativo'),
                'bloqueado' => $toBoolLabel($row[$map['bloqueado']] ?? 'Não'),
                'tentativas_login' => (int)($row[$map['tentativas_login']] ?? 0),
                'senha_nunca_expira' => $toBoolLabel($row[$map['senha_nunca_expira']] ?? 'Não'),
                'modificar_senha_proximo_logon' => $toBoolLabel($row[$map['modificar_senha_proximo_logon']] ?? 'Não'),
                'data_nascimento' => $toDate($row[$map['data_nascimento']] ?? null),
                'image' => null,
            ];

            try {
                // Upsert por email/username
                $existing = $repo->getUserByEmailOrUsername($payload['email'], $payload['username']);
                if ($existing) {
                    $payload['id'] = (int)$existing['id'];
                    // Import não altera senha em usuários existentes (há fluxo próprio para senha)
                    if (isset($payload['password'])) unset($payload['password']);
                    // Preencher campos obrigatórios com valores atuais caso venham vazios/zerados no CSV
                    $payload['name'] = $payload['name'] !== '' ? $payload['name'] : ($existing['name'] ?? '');
                    $payload['email'] = $payload['email'] !== '' ? $payload['email'] : ($existing['email'] ?? '');
                    $payload['username'] = $payload['username'] !== '' ? $payload['username'] : ($existing['username'] ?? '');
                    $payload['user_department_id'] = $payload['user_department_id'] > 0 ? $payload['user_department_id'] : (int)($existing['user_department_id'] ?? 0);
                    $payload['user_position_id'] = $payload['user_position_id'] > 0 ? $payload['user_position_id'] : (int)($existing['user_position_id'] ?? 0);
                    if (empty($payload['status']) && !empty($existing['status'])) $payload['status'] = $existing['status'];
                    if (empty($payload['bloqueado']) && !empty($existing['bloqueado'])) $payload['bloqueado'] = $existing['bloqueado'];
                    if (empty($payload['senha_nunca_expira']) && !empty($existing['senha_nunca_expira'])) $payload['senha_nunca_expira'] = $existing['senha_nunca_expira'];
                    if (empty($payload['modificar_senha_proximo_logon']) && !empty($existing['modificar_senha_proximo_logon'])) $payload['modificar_senha_proximo_logon'] = $existing['modificar_senha_proximo_logon'];
                    if (empty($payload['data_nascimento']) && !empty($existing['data_nascimento'])) $payload['data_nascimento'] = $existing['data_nascimento'];
                    // Verificar diferenças e só atualizar se houver
                    $keysToCompare = [
                        'name','email','username','user_department_id','user_position_id',
                        'status','bloqueado','senha_nunca_expira','modificar_senha_proximo_logon','data_nascimento'
                    ];
                    $hasDiff = false;
                    foreach ($keysToCompare as $k) {
                        $newVal = $payload[$k] ?? null;
                        $oldVal = $existing[$k] ?? null;
                        if (in_array($k, ['user_department_id','user_position_id'])) {
                            $newVal = (int)$newVal; $oldVal = (int)$oldVal;
                        } else {
                            $newVal = is_string($newVal) ? trim((string)$newVal) : $newVal;
                            $oldVal = is_string($oldVal) ? trim((string)$oldVal) : $oldVal;
                        }
                        if ($newVal !== $oldVal) { $hasDiff = true; break; }
                    }

                    if (!$hasDiff) {
                        $skipped++;
                        $this->data['report'][] = ['linha'=>$rows, 'acao'=>'ignorado', 'email'=>$payload['email']];
                        continue;
                    }

                    $ok = $repo->updateUser($payload);
                    if ($ok) {
                        $updated++;
                        $this->data['report'][] = ['linha'=>$rows, 'acao'=>'atualizado', 'email'=>$payload['email']];
                    } else {
                        $errors++;
                        $this->data['report'][] = ['linha'=>$rows, 'acao'=>'erro', 'email'=>$payload['email'], 'msg'=>'Falha ao atualizar (verifique logs DEBUG updateUser)'];
                    }
                } else {
                    if ($payload['password'] === '') {
                        // Gera senha temporária segura para novos usuários sem senha
                        $payload['password'] = bin2hex(random_bytes(6));
                    }
                    $ok = $repo->createUser($payload);
                    if ($ok) {
                        $created++;
                        $this->data['report'][] = ['linha'=>$rows, 'acao'=>'criado', 'email'=>$payload['email']];
                    } else {
                        $errors++;
                        $this->data['report'][] = ['linha'=>$rows, 'acao'=>'erro', 'email'=>$payload['email'], 'msg'=>'Falha ao criar'];
                    }
                }
            } catch (\Throwable $e) {
                $errors++;
                $this->data['report'][] = ['linha'=>$rows, 'acao'=>'erro', 'email'=>$payload['email'], 'msg'=>$e->getMessage()];
                GenerateLog::generateLog('error','Falha ao importar usuário.', ['email'=>$payload['email'], 'e'=>$e->getMessage()]);
            }
        }
        fclose($fp);

        $this->data['summary'] = compact('created','updated','skipped','errors');
        $_SESSION['success'] = "Importação concluída: criados {$created}, atualizados {$updated}, erros {$errors}.";
        return true;
    }

    // Download do template CSV
    public function template(): void
    {
        $filename = 'template_importacao_usuarios.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        $out = fopen('php://output', 'w');
        // Cabeçalho com ; como separador
        fputcsv($out, ['name','email','username','department_id','position_id','password','status','bloqueado','tentativas_login','senha_nunca_expira','modificar_senha_proximo_logon','data_nascimento'], ';');
        // Linha exemplo
        fputcsv($out, ['Maria Silva','maria@empresa.com','maria.silva',1,2,'SenhaForte123!','Ativo','Não',0,'Não','Não','20/08/1990'], ';');
        fclose($out);
        exit;
    }
}


