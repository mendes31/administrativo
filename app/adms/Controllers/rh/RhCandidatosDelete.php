<?php

namespace App\adms\Controllers\rh;

use App\adms\Models\Repository\RhCandidatosRepository;
use App\adms\Models\Repository\LogAlteracoesRepository;
use App\adms\Models\Repository\LogJustificativasRepository;
use App\adms\Models\Services\SensitiveActionService;

class RhCandidatosDelete
{
    public function index(int|string $id = null): void
    {
        // ID pode vir da rota ou da query string (?id=)
        if (!$id) {
            $id = $_GET['id'] ?? null;
        }

        if (!$id || !is_numeric($id)) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'ID inválido.']);
                exit;
            }

            $_SESSION['error'] = "Erro: ID inválido!";
            header("Location: " . $_ENV['URL_ADM'] . "rh-candidatos");
            exit;
        }

        $id = (int) $id;
        $repo = new RhCandidatosRepository();
        $candidato = $repo->getById($id);

        if (!$candidato) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Candidato não encontrado.']);
                exit;
            }

            $_SESSION['error'] = "Erro: Candidato não encontrado!";
            header("Location: " . $_ENV['URL_ADM'] . "rh-candidatos");
            exit;
        }

        if (!\App\adms\Models\Services\RhCandidatoPermissionService::canEditCandidato($id)) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Acesso não autorizado a este candidato.']);
                exit;
            }

            $_SESSION['error'] = 'Acesso não autorizado a este candidato.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rh-candidatos');
            exit;
        }

        // Exclusão exige POST com senha + justificativa (sem fallback GET).
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Exclusão de candidato exige confirmação com senha e justificativa.';
            header('Location: ' . $_ENV['URL_ADM'] . 'rh-candidatos-view/' . $id);
            exit;
        }

        header('Content-Type: application/json');

        $motivo = trim($_POST['motivo'] ?? '');
        $password = $_POST['password'] ?? '';

        $validacao = SensitiveActionService::validarConfirmacao($password, $motivo, true);
        if (!$validacao['success']) {
            echo json_encode(['success' => false, 'message' => $validacao['message']]);
            exit;
        }

        $conn = $repo->getConnection();
        $conn->beginTransaction();

        try {
            // Remove currículos físicos e registros de anexo antes do candidato
            $repo->deleteAnexosByCandidatoId($id);

            $stmtDel = $conn->prepare('DELETE FROM rh_candidatos WHERE id = :id');
            $stmtDel->bindValue(':id', $id, \PDO::PARAM_INT);
            $ok = $stmtDel->execute();

            if (!$ok) {
                $conn->rollBack();
                echo json_encode(['success' => false, 'message' => 'Erro ao excluir candidato.']);
                exit;
            }

            $conn->commit();
        } catch (\Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            echo json_encode(['success' => false, 'message' => 'Erro ao excluir candidato e anexos.']);
            exit;
        }

        if (!empty($_SESSION['user_id'])) {
            $logsRepo = new LogAlteracoesRepository();
            $sql = 'SELECT id FROM adms_log_alteracoes 
                    WHERE tabela = :tabela 
                      AND objeto_id = :objeto_id 
                      AND usuario_id = :usuario_id 
                    ORDER BY id DESC 
                    LIMIT 1';
            $stmt = $logsRepo->getConnection()->prepare($sql);
            $stmt->bindValue(':tabela', 'rh_candidatos', \PDO::PARAM_STR);
            $stmt->bindValue(':objeto_id', $id, \PDO::PARAM_INT);
            $stmt->bindValue(':usuario_id', (int) $_SESSION['user_id'], \PDO::PARAM_INT);
            $stmt->execute();
            $ultimoLog = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($ultimoLog && isset($ultimoLog['id'])) {
                $logJustRepo = new LogJustificativasRepository();
                $logJustRepo->insert([
                    'log_alteracao_id' => $ultimoLog['id'],
                    'justificativa' => $motivo,
                    'assinatura' => $_SESSION['user_name'] ?? 'Usuário não identificado',
                    'data_justificativa' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        echo json_encode(['success' => true, 'message' => 'Candidato excluído com sucesso!']);
        exit;
    }
}
