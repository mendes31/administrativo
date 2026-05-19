<?php

declare(strict_types=1);

namespace App\adms\Helpers;

use App\adms\Models\Repository\CompanyEventsRepository;

/**
 * Upload e persistência de imagens (até 6) e anexo em eventos corporativos.
 */
final class CompanyEventMediaService
{
    public function alertHtml(string $type, string $message): string
    {
        return sprintf(
            '<div class="alert alert-%s" role="alert">%s</div>',
            htmlspecialchars($type),
            htmlspecialchars($message)
        );
    }

    /**
     * @return array<int, string>|false
     */
    private function collectUploadedImagePaths(): array|false
    {
        $files = CompanyEventUploadHelper::collectImageFilesFromRequest();
        if ($files === false) {
            $_SESSION['msg'] = $this->alertHtml('warning', 'Erro no envio de uma ou mais imagens.');

            return false;
        }
        if ($files === []) {
            return [];
        }
        if (count($files) > CompanyEventUploadHelper::MAX_EVENT_IMAGES) {
            $_SESSION['msg'] = $this->alertHtml(
                'warning',
                'Você pode enviar até ' . CompanyEventUploadHelper::MAX_EVENT_IMAGES . ' imagens por evento.'
            );

            return false;
        }

        $paths = [];
        foreach ($files as $file) {
            $path = CompanyEventUploadHelper::uploadFile($file, 'company_events/imagens');
            if ($path === null) {
                foreach ($paths as $saved) {
                    CompanyEventUploadHelper::deleteStoredFile($saved);
                }
                $_SESSION['msg'] = $this->alertHtml(
                    'warning',
                    'Não foi possível enviar uma das imagens. Use PNG, JPG, GIF ou WebP (máx. 20MB cada).'
                );

                return false;
            }
            $paths[] = $path;
        }

        return $paths;
    }

    /**
     * @return string|null|false
     */
    private function collectUploadedAnexoPath(?string $oldFile = null): string|null|false
    {
        if (empty($_FILES['anexo'])) {
            return null;
        }
        $file = $_FILES['anexo'];
        $err = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($err !== UPLOAD_ERR_OK) {
            $_SESSION['msg'] = $this->alertHtml('warning', 'Erro no upload do anexo. Código: ' . $err);

            return false;
        }
        $path = CompanyEventUploadHelper::uploadFile($file, 'company_events/anexos', $oldFile);
        if ($path === null) {
            $_SESSION['msg'] = $this->alertHtml(
                'warning',
                'Não foi possível enviar o anexo. Verifique tipo e tamanho (máx. 20MB).'
            );

            return false;
        }

        return $path;
    }

    public function persistMediaAfterCreate(int $eventId, CompanyEventsRepository $repo): bool
    {
        $imagePaths = $this->collectUploadedImagePaths();
        if ($imagePaths === false) {
            return false;
        }
        if ($imagePaths !== []) {
            $repo->addImages($eventId, $imagePaths);
        }

        $anexo = $this->collectUploadedAnexoPath();
        if ($anexo === false) {
            return false;
        }
        if ($anexo !== null) {
            $repo->updateAnexo($eventId, $anexo);
        }

        return true;
    }

    /**
     * @param array<string, mixed> $existing
     */
    public function persistMediaAfterUpdate(int $eventId, CompanyEventsRepository $repo, array $existing): bool
    {
        $removeIds = array_map('intval', (array)($_POST['remove_image_ids'] ?? []));
        if ($removeIds !== []) {
            $repo->deleteImagesByIds($eventId, $removeIds);
        }

        $currentCount = $repo->countImagesForEvent($eventId);
        $newPaths = $this->collectUploadedImagePaths();
        if ($newPaths === false) {
            return false;
        }
        $totalAfter = $currentCount + count($newPaths);
        if ($totalAfter > CompanyEventUploadHelper::MAX_EVENT_IMAGES) {
            foreach ($newPaths as $p) {
                CompanyEventUploadHelper::deleteStoredFile($p);
            }
            $_SESSION['msg'] = $this->alertHtml(
                'warning',
                'O evento pode ter no máximo ' . CompanyEventUploadHelper::MAX_EVENT_IMAGES . ' imagens.'
            );

            return false;
        }
        if ($newPaths !== []) {
            $repo->addImages($eventId, $newPaths);
        }

        $anexo = (string)($existing['anexo'] ?? '');
        if (!empty($_POST['remove_anexo'])) {
            CompanyEventUploadHelper::deleteStoredFile($anexo);
            $anexo = '';
            $repo->updateAnexo($eventId, null);
        }

        $newAnexo = $this->collectUploadedAnexoPath($anexo !== '' ? $anexo : null);
        if ($newAnexo === false) {
            return false;
        }
        if ($newAnexo !== null) {
            $repo->updateAnexo($eventId, $newAnexo);
        }

        return true;
    }
}
