<?php
// Exemplo de array de planos de ação (substitua pelo seu array real)
$actionPlans = $actionPlans ?? [];

// Filtro de performance
$performanceFilter = $_GET['performance'] ?? '';

function getPerformanceStatus($grade) {
    if ($grade === null || $grade === '') {
        return ['label' => '', 'class' => ''];
    } elseif ($grade <= 5) {
        return ['label' => 'Reprovado', 'class' => 'performance-reprovado'];
    } elseif ($grade < 7) {
        return ['label' => 'Exame', 'class' => 'performance-exame'];
    } else {
        return ['label' => 'Aprovado', 'class' => 'performance-aprovado'];
    }
}
?>

<style>
.performance-reprovado {
    background-color: #ffcccc;
    color: #b20000;
    font-weight: bold;
}
.performance-exame {
    background-color: #fff3cd;
    color: #b8860b;
    font-weight: bold;
}
.performance-aprovado {
    background-color: #d4edda;
    color: #155724;
    font-weight: bold;
}
</style>

<form method="get" style="margin-bottom: 20px;">
    <label for="performance">Filtrar por Aproveitamento:</label>
    <select name="performance" id="performance" onchange="this.form.submit()">
        <option value="">Todos</option>
        <option value="aprovado" <?= $performanceFilter === 'aprovado' ? 'selected' : '' ?>>Aprovado</option>
        <option value="exame" <?= $performanceFilter === 'exame' ? 'selected' : '' ?>>Exame</option>
        <option value="reprovado" <?= $performanceFilter === 'reprovado' ? 'selected' : '' ?>>Reprovado</option>
        <option value="vazio" <?= $performanceFilter === 'vazio' ? 'selected' : '' ?>>Vazio</option>
    </select>
</form>

<table border="1" width="100%">
    <thead>
        <tr>
            <th>ID</th>
            <th>Objetivo</th>
            <th>Nota</th>
            <th>Aproveitamento</th>
            <!-- outros campos -->
        </tr>
    </thead>
    <tbody>
        <?php foreach ($actionPlans as $plan):
            $performance = getPerformanceStatus($plan['grade'] ?? null);
            // Filtro
            $filterMatch = false;
            if ($performanceFilter === '' ||
                ($performanceFilter === 'aprovado' && $performance['label'] === 'Aprovado') ||
                ($performanceFilter === 'exame' && $performance['label'] === 'Exame') ||
                ($performanceFilter === 'reprovado' && $performance['label'] === 'Reprovado') ||
                ($performanceFilter === 'vazio' && $performance['label'] === '')) {
                $filterMatch = true;
            }
            if (!$filterMatch) continue;
        ?>
        <tr>
            <td><?= htmlspecialchars($plan['id']) ?></td>
            <td><?= htmlspecialchars($plan['objective']) ?></td>
            <td><?= htmlspecialchars($plan['grade'] ?? '') ?></td>
            <td class="<?= $performance['class'] ?>"><?= $performance['label'] ?></td>
            <!-- outros campos -->
        </tr>
        <?php endforeach; ?>
    </tbody>
</table> 