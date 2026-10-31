<?php
if (empty($this->data['can_manage_external_invites'])) {
    return;
}
$externalInvites = $this->data['external_invites'] ?? [];
$csrf = (string) ($this->data['external_invite_csrf'] ?? '');
$imageUrl = (string) ($this->data['external_invite_image_url'] ?? '');
$eventForMessage = $ev ?? ($this->data['event'] ?? []);
$postUrl = ($urlAdm ?? ($_ENV['URL_ADM'] ?? '')) . 'view-company-event/' . (int) ($eventId ?? ($eventForMessage['id'] ?? 0));
$statusLabel = static function (string $status): string {
    return match ($status) {
        'confirmed' => 'Confirmado',
        'declined' => 'Recusado',
        default => 'Pendente',
    };
};
$statusClass = static function (string $status): string {
    return match ($status) {
        'confirmed' => 'bg-success',
        'declined' => 'bg-danger',
        default => 'bg-warning text-dark',
    };
};
?>
<div class="border-top mt-2 pt-4" id="convidados-externos">
    <h5 class="mb-1">Convidados de fora da empresa</h5>
    <p class="text-muted small">Pessoas que não têm usuário no portal. Esta lista é independente da opção <strong>Permite convidados</strong>, que vale só para acompanhantes do colaborador. O botão WhatsApp envia o texto e o link pelo número cadastrado na API de WhatsApp do sistema. A imagem do evento aparece na página do convite.</p>

    <form method="post" action="<?php echo htmlspecialchars($postUrl, ENT_QUOTES, 'UTF-8'); ?>" class="row g-2 align-items-end mb-3">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="external_invite_action" value="add">
        <div class="col-md-4">
            <label class="form-label mb-1" for="externalInviteName">Nome</label>
            <input class="form-control" id="externalInviteName" name="full_name" maxlength="200" required>
        </div>
        <div class="col-md-4">
            <label class="form-label mb-1" for="externalInviteReason">Motivo do convite</label>
            <input class="form-control" id="externalInviteReason" name="invite_reason" maxlength="255" required>
        </div>
        <div class="col-md-3">
            <label class="form-label mb-1" for="externalInvitePhone">Celular (WhatsApp)</label>
            <input class="form-control" id="externalInvitePhone" name="phone" inputmode="tel" placeholder="11999998888" required>
        </div>
        <div class="col-md-1">
            <button class="btn btn-success w-100" type="submit">Incluir</button>
        </div>
    </form>

    <?php if ($imageUrl !== ''): ?>
        <p class="small mb-3"><a href="<?php echo htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Abrir imagem do convite</a> para anexar no WhatsApp, se quiser enviar a foto junto com o texto.</p>
    <?php endif; ?>

    <?php if ($externalInvites === []): ?>
        <p class="text-muted small mb-0">Nenhum convidado externo neste evento.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Motivo</th>
                        <th>Celular</th>
                        <th>Status</th>
                        <th class="text-end">Convite</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($externalInvites as $invite): ?>
                    <?php
                    $token = (string) ($invite['token'] ?? '');
                    $inviteUrl = \App\adms\Models\Services\CompanyEventExternalInviteMessage::inviteUrl($token);
                    $text = \App\adms\Models\Services\CompanyEventExternalInviteMessage::message(
                        (string) ($invite['full_name'] ?? ''),
                        (string) ($invite['invite_reason'] ?? ''),
                        $eventForMessage,
                        $inviteUrl
                    );
                    $st = (string) ($invite['status'] ?? 'pending');
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string) ($invite['full_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string) ($invite['invite_reason'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars(\App\adms\Models\Services\CompanyEventExternalInviteMessage::formatPhone((string) ($invite['phone'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><span class="badge <?php echo $statusClass($st); ?>"><?php echo htmlspecialchars($statusLabel($st), ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td class="text-end text-nowrap">
                            <form method="post" action="<?php echo htmlspecialchars($postUrl, ENT_QUOTES, 'UTF-8'); ?>" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="external_invite_action" value="whatsapp">
                                <input type="hidden" name="invite_id" value="<?php echo (int) ($invite['id'] ?? 0); ?>">
                                <button class="btn btn-sm btn-success" type="submit">WhatsApp</button>
                            </form>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-copy="<?php echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); ?>">Copiar texto</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-copy="<?php echo htmlspecialchars($inviteUrl, ENT_QUOTES, 'UTF-8'); ?>">Copiar link</button>
                            <form method="post" action="<?php echo htmlspecialchars($postUrl, ENT_QUOTES, 'UTF-8'); ?>" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="external_invite_action" value="status">
                                <input type="hidden" name="invite_id" value="<?php echo (int) ($invite['id'] ?? 0); ?>">
                                <button class="btn btn-sm btn-outline-success" type="submit" name="status" value="confirmed">Confirmar</button>
                                <button class="btn btn-sm btn-outline-danger" type="submit" name="status" value="declined">Recusar</button>
                            </form>
                            <form method="post" action="<?php echo htmlspecialchars($postUrl, ENT_QUOTES, 'UTF-8'); ?>" class="d-inline" onsubmit="return confirm('Remover este convidado?');">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="external_invite_action" value="delete">
                                <input type="hidden" name="invite_id" value="<?php echo (int) ($invite['id'] ?? 0); ?>">
                                <button class="btn btn-sm btn-outline-secondary" type="submit">Remover</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<script>
document.querySelectorAll('#convidados-externos [data-copy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var text = btn.getAttribute('data-copy') || '';
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text);
        }
    });
});
</script>
