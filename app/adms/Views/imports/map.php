<?php

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Services\Imports\ImportProfileInterface;

/** @var ImportProfileInterface $profile */
$profile = $this->data['profile'];
$job = $this->data['job'] ?? [];
$headers = $this->data['headers'] ?? [];
$suggested = $this->data['suggested'] ?? [];
$preview = $this->data['preview'] ?? [];
$urlAdm = $_ENV['URL_ADM'] ?? '';

$colLetter = static function (int $i): string {
    $s = '';
    $n = $i + 1;
    while ($n > 0) {
        $n--;
        $s = chr(65 + ($n % 26)) . $s;
        $n = intdiv($n, 26);
    }
    return $s;
};
?>

<div class="container-fluid px-4">
    <div class="mb-1 d-flex flex-column flex-sm-row gap-2">
        <h2 class="mt-3">Mapear colunas — <?php echo htmlspecialchars($profile->label(), ENT_QUOTES, 'UTF-8'); ?></h2>
        <ol class="breadcrumb mb-3 mt-0 mt-sm-3 ms-auto">
            <li class="breadcrumb-item"><a class="text-decoration-none" href="<?php echo $urlAdm; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a class="text-decoration-none" href="<?php echo $urlAdm; ?>import-center">Importações</a></li>
            <li class="breadcrumb-item">Mapeamento</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header">Associe cada campo do sistema a uma coluna do arquivo</div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <div class="alert alert-info small">
                Arquivo: <strong><?php echo htmlspecialchars((string) ($job['original_filename'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                · Operação: <strong><?php echo htmlspecialchars((string) ($job['operation'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                · Vazios: <strong><?php echo htmlspecialchars((string) ($job['empty_policy'] ?? 'skip'), ENT_QUOTES, 'UTF-8'); ?></strong>
                <?php if (!empty($job['dry_run'])): ?> · <span class="badge text-bg-warning">simulação</span><?php endif; ?>
            </div>

            <?php if ($preview !== [] && $headers !== []): ?>
                <p class="small text-muted mb-1">Prévia (até 5 linhas)</p>
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <?php foreach ($headers as $i => $h): ?>
                                    <th><?php echo htmlspecialchars($colLetter((int) $i) . ' — ' . (string) $h, ENT_QUOTES, 'UTF-8'); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($preview as $row): ?>
                                <tr>
                                    <?php foreach ($headers as $i => $_h): ?>
                                        <td><?php echo htmlspecialchars((string) ($row[$i] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <form method="POST" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?php echo CSRFHelper::generateCSRFToken('form_import_center_map'); ?>">

                <div class="col-md-4">
                    <label class="form-label" for="key_field">Chave única</label>
                    <select class="form-select" name="key_field" id="key_field" required>
                        <?php foreach ($profile->keyFields() as $kf): ?>
                            <option value="<?php echo htmlspecialchars($kf, ENT_QUOTES, 'UTF-8'); ?>"
                                <?php echo $kf === $profile->defaultKeyField() ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($profile->fields()[$kf] ?? $kf, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">A coluna desta chave precisa estar mapeada.</div>
                </div>

                <div class="col-12">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Campo do sistema</th>
                                    <th>Coluna do arquivo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($profile->fields() as $field => $label):
                                    $sel = $suggested[$field] ?? '';
                                    ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                                            <code class="small"><?php echo htmlspecialchars($field, ENT_QUOTES, 'UTF-8'); ?></code>
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm" name="field_map[<?php echo htmlspecialchars($field, ENT_QUOTES, 'UTF-8'); ?>]">
                                                <option value="">não importar</option>
                                                <?php foreach ($headers as $i => $h): ?>
                                                    <option value="<?php echo (int) $i; ?>" <?php echo (string) $sel === (string) $i ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($colLetter((int) $i) . ' — ' . (string) $h, ENT_QUOTES, 'UTF-8'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-play"></i>
                        <?php echo !empty($job['dry_run']) ? 'Simular importação' : 'Importar'; ?>
                    </button>
                    <a class="btn btn-outline-secondary" href="<?php echo $urlAdm; ?>import-center">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
