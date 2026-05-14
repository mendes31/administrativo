<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\AdmsSapServiceLayerConnectionRepository;

/**
 * Cliente HTTP para a **API intermediária** que o administrativo usa em vez da Service Layer directa.
 *
 * A API (desenvolvida à parte) é que mantém sessão/credenciais com o SAP B1 Service Layer.
 * Este cliente apenas faz pedidos HTTP à URL configurada (ex.: health-check).
 */
final class SapGatewayHttpClient
{
    /**
     * GET no endpoint de health da conexão (base_url + health_path).
     *
     * @return array{success: bool, message: string, http_code?: int}
     */
    public static function ping(int $connectionId): array
    {
        $repo = new AdmsSapServiceLayerConnectionRepository();
        $row = $repo->getById($connectionId);
        if (!$row) {
            return ['success' => false, 'message' => 'Conexão não encontrada.'];
        }
        if (empty($row['is_active'])) {
            return ['success' => false, 'message' => 'Conexão inactiva.'];
        }

        $base = rtrim((string) ($row['base_url'] ?? ''), '/');
        if ($base === '') {
            return ['success' => false, 'message' => 'URL base em falta.'];
        }

        $path = isset($row['health_path']) ? '/' . ltrim((string) $row['health_path'], '/') : '/health';
        $url = $base . $path;

        $user = trim((string) ($row['username'] ?? ''));
        $pass = (string) ($row['password'] ?? '');

        $ch = curl_init($url);
        $headers = ['Accept: application/json, */*;q=0.8'];

        if ($user !== '' && $pass !== '') {
            curl_setopt($ch, CURLOPT_USERPWD, $user . ':' . $pass);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        } elseif ($pass !== '') {
            $headers[] = 'Authorization: Bearer ' . $pass;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPGET => true,
        ]);

        $body = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err !== '') {
            return ['success' => false, 'message' => 'Erro de rede: ' . $err, 'http_code' => $httpCode];
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'message' => "API respondeu com HTTP {$httpCode} em {$path}.",
                'http_code' => $httpCode,
            ];
        }

        return [
            'success' => false,
            'message' => "A API devolveu HTTP {$httpCode}. Ajuste a URL ou o caminho de health.",
            'http_code' => $httpCode,
        ];
    }
}
