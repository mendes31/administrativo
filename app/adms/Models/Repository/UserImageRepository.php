<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\SlugImg;
use App\adms\Helpers\Upload;
use App\adms\Helpers\ValExtImg;
use App\adms\Models\Services\DbConnection;
use Exception;
use PDO;

/**
 * Repository responsável por operações específicas de imagem de usuário
 *
 * Esta classe fornece métodos para gerenciar uploads, exclusões e limpeza
 * de imagens de usuário de forma isolada e organizada.
 *
 * @package App\adms\Models\Repository
 * @author Rafael Mendes
 */
class UserImageRepository extends DbConnection
{
    /** @var string $uploadDir Diretório base de upload de imagens */
    private string $uploadDir = 'public/adms/uploads/users/';

    /**
     * Atualizar imagem do usuário
     *
     * @param array $data Dados contendo ID do usuário e nova imagem
     * @return bool true se sucesso, false se falha
     */
    public function updateUserImage(array $data): bool
    {
        try {
            // Validar dados recebidos
            if (!isset($data['id']) || !isset($data['image']) || !is_array($data['image'])) {
                error_log("UserImageRepository: Dados inválidos recebidos");
                return false;
            }

            // Validar tipo de imagem (confere MIME real via finfo quando possível)
            $detectedType = $data['image']['type'] ?? '';
            if (function_exists('finfo_open') && is_uploaded_file($data['image']['tmp_name'] ?? '')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $realMime = finfo_file($finfo, $data['image']['tmp_name']);
                finfo_close($finfo);
                if (!empty($realMime)) {
                    $detectedType = $realMime;
                }
            }

            $valExtImg = new ValExtImg();
            $valExtImg->validateExtImg($detectedType);
            
            if (!$valExtImg->getResult()) {
                error_log("UserImageRepository: Tipo de imagem não suportado: " . ($detectedType ?: 'desconhecido'));
                return false;
            }

            // Obter dados antigos do usuário
            $userOld = $this->getUser($data['id']);
            if (!$userOld) {
                error_log("UserImageRepository: Erro ao buscar usuário: " . $data['id']);
                return false;
            }

            // Gerar nome final (slug) e garantir consistência entre arquivo e banco
            $slugImg = new SlugImg();
            $finalImageName = $slugImg->slug($data['image']['name'] ?? ('user-' . $data['id'] . '-' . time()));
            $data['image']['name'] = $finalImageName;

            // Processar upload da nova imagem já com o nome slugificado
            error_log('UPLOAD DEBUG - repo UserImageRepository: detectedType=' . ($detectedType ?? 'null') . ' size=' . ($data['image']['size'] ?? 'null') . ' finalName=' . $finalImageName);
            if (!$this->uploadImage($data)) {
                error_log("UserImageRepository: Falha no upload da imagem");
                return false;
            }

            // Deletar imagem antiga se existir
            $this->deleteOldImage($userOld);

            // Atualizar banco de dados com o mesmo nome salvo no disco
            if (!$this->updateDatabaseImage($data)) {
                error_log("UserImageRepository: Falha ao atualizar banco de dados");
                return false;
            }

            // Limpar diretório de imagens não utilizadas
            $this->cleanUnusedImages($data['id']);

            return true;

        } catch (Exception $e) {
            error_log("UserImageRepository: Erro ao atualizar imagem: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fazer upload da nova imagem
     *
     * @param array $data Dados do usuário e imagem
     * @return bool true se sucesso
     */
    private function uploadImage(array $data): bool
    {
        try {
            // Criar diretório específico do usuário
            $userDir = $this->uploadDir . $data['id'] . '/';
            if (!is_dir($userDir)) {
                if (!mkdir($userDir, 0755, true)) {
                    error_log("UserImageRepository: Erro ao criar diretório: " . $userDir);
                    return false;
                }
                error_log("UserImageRepository: Diretório criado: " . $userDir);
            }
            
            $upload = new Upload();
            
            // Usar a API correta da classe Upload com diretório do usuário
            $result = $upload->upload(
                $userDir,                            // diretório específico do usuário
                $data['image']['tmp_name'],          // nome temporário
                $data['image']['name']               // nome do arquivo
            );
            
            if ($result && $upload->getResult()) {
                error_log("UserImageRepository: Upload realizado com sucesso em: " . $userDir);
                return true;
            }
            
            error_log("UserImageRepository: Falha no upload");
            return false;

        } catch (Exception $e) {
            error_log("UserImageRepository: Erro no upload: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletar imagem antiga do usuário
     *
     * @param array $userOld Dados antigos do usuário
     * @return void
     */
    private function deleteOldImage(array $userOld): void
    {
        try {
            if (!empty($userOld['image']) && $userOld['image'] !== 'icon_user.png') {
                $oldImagePath = $this->uploadDir . $userOld['id'] . '/' . $userOld['image'];
                
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                    error_log("UserImageRepository: Imagem antiga deletada: " . $oldImagePath);
                    
                    // Verificar se o diretório do usuário está vazio e deletar se necessário
                    $this->cleanEmptyUserDirectory($userOld['id']);
                }
            }
        } catch (Exception $e) {
            error_log("UserImageRepository: Erro ao deletar imagem antiga: " . $e->getMessage());
        }
    }

    /**
     * Atualizar imagem no banco de dados
     *
     * @param array $data Dados do usuário e imagem
     * @return bool true se sucesso
     */
    private function updateDatabaseImage(array $data): bool
    {
        try {
            // Gerar nome único para a imagem
            $slugImg = new SlugImg();
            $imageName = $slugImg->slug($data['image']['name']);
            
            // Atualizar banco com apenas o nome do arquivo (o diretório será construído dinamicamente)
            $sql = 'UPDATE adms_users SET image = :image, updated_at = :updated_at WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':image', $imageName, PDO::PARAM_STR);
            
            error_log("UserImageRepository: Salvando no banco: " . $imageName);
            $stmt->bindValue(':updated_at', date("Y-m-d H:i:s"));
            $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
            
            $result = $stmt->execute();
            
            if ($result) {
                error_log("UserImageRepository: Banco atualizado com sucesso");
                return true;
            }
            
            error_log("UserImageRepository: Falha ao atualizar banco");
            return false;

        } catch (Exception $e) {
            error_log("UserImageRepository: Erro ao atualizar banco: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Limpar imagens não utilizadas no diretório
     *
     * @param int $userId ID do usuário
     * @return void
     */
    private function cleanUnusedImages(int $userId): void
    {
        try {
            $userDir = $this->uploadDir . $userId . '/';
            
            if (!is_dir($userDir)) {
                return;
            }

            $files = glob($userDir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    // Verificar se o arquivo está sendo usado no banco
                    if (!$this->isImageUsed($file, $userId)) {
                        unlink($file);
                        error_log("UserImageRepository: Arquivo não utilizado deletado: " . $file);
                    }
                }
            }
            
            // Verificar se o diretório ficou vazio após a limpeza
            $this->cleanEmptyUserDirectory($userId);
            
        } catch (Exception $e) {
            error_log("UserImageRepository: Erro ao limpar imagens: " . $e->getMessage());
        }
    }

    /**
     * Limpar diretório vazio do usuário
     *
     * @param int $userId ID do usuário
     * @return void
     */
    private function cleanEmptyUserDirectory(int $userId): void
    {
        try {
            $userDir = $this->uploadDir . $userId . '/';
            
            if (is_dir($userDir)) {
                $files = glob($userDir . '*');
                if (empty($files)) {
                    rmdir($userDir);
                    error_log("UserImageRepository: Diretório vazio deletado: " . $userDir);
                }
            }
        } catch (Exception $e) {
            error_log("UserImageRepository: Erro ao limpar diretório vazio: " . $e->getMessage());
        }
    }

    /**
     * Verificar se uma imagem está sendo usada no banco
     *
     * @param string $filePath Caminho do arquivo
     * @param int $userId ID do usuário
     * @return bool true se está sendo usado
     */
    private function isImageUsed(string $filePath, int $userId): bool
    {
        try {
            $fileName = basename($filePath);
            $sql = 'SELECT COUNT(*) FROM adms_users WHERE id = :id AND image = :image';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':image', $fileName, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("UserImageRepository: Erro ao verificar uso da imagem: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Construir caminho completo da imagem
     *
     * @param int $userId ID do usuário
     * @param string $imageName Nome da imagem
     * @return string Caminho completo da imagem
     */
    public static function buildImagePath(int $userId, string $imageName): string
    {
        return 'users/' . $userId . '/' . $imageName;
    }

    /**
     * Deletar imagem do usuário
     *
     * @param int $userId ID do usuário
     * @param string $imageName Nome da imagem
     * @return bool true se sucesso
     */
    public function deleteUserImage(int $userId, string $imageName): bool
    {
        try {
            if (empty($imageName) || $imageName === 'icon_user.png') {
                return true; // Nada para deletar
            }

            // Remover arquivo físico
            $imgPath = $this->uploadDir . $userId . '/' . $imageName;
            if (file_exists($imgPath)) {
                unlink($imgPath);
                error_log("UserImageRepository: Arquivo removido: " . $imgPath);
            }
            
            // Verificar se o diretório ficou vazio e deletar se necessário
            $userDir = $this->uploadDir . $userId . '/';
            if (is_dir($userDir)) {
                $files = glob($userDir . '*');
                if (empty($files)) {
                    rmdir($userDir);
                    error_log("UserImageRepository: Diretório vazio removido: " . $userDir);
                }
            }

            return true;

        } catch (Exception $e) {
            error_log("UserImageRepository: Erro ao deletar imagem: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obter dados do usuário
     *
     * @param int $id ID do usuário
     * @return array|false Dados do usuário ou false se não encontrado
     */
    private function getUser(int $id): array|false
    {
        try {
            $sql = 'SELECT id, name, email, image FROM adms_users WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("UserImageRepository: Erro ao buscar usuário: " . $e->getMessage());
            return false;
        }
    }
}
