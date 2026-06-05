<?php if (!isset($this)) { exit; } ?>
<?php
if (!isset($invResourceTypeLabels)) {
    $invResourceTypeLabels = [
        'MACHINE' => 'Equipamento (Máq.)',
        'ENERGY' => 'Energia',
        'MIXED' => 'Misto (Máq. + En.)',
        'LABOR' => 'MO SAP',
    ];
}
