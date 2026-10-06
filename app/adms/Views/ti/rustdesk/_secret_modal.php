<?php

$url = (string) ($_ENV['URL_ADM'] ?? '');
$csrfReveal = (string) ($this->data['csrf_reveal'] ?? '');
$canReveal = in_array('TiRustdeskReveal', $this->data['buttonPermission'] ?? [], true);
$encryptionOk = !empty($this->data['encryption_ok']);
?>
<div class="modal fade" id="tiRdRevealModal" tabindex="-1" aria-labelledby="tiRdRevealTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tiRdRevealTitle">Confirme sua senha</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-2">
                    Para <strong id="tiRdRevealActionLabel">visualizar</strong> a senha do RustDesk, informe a senha da
                    <em>sua conta neste sistema</em> — não a senha da máquina.
                </p>
                <label for="tiRdUserPassword" class="form-label">Sua senha do Administrativo</label>
                <input type="password" class="form-control" id="tiRdUserPassword" autocomplete="current-password">
                <div class="alert alert-danger py-2 mt-2 mb-0 d-none" id="tiRdRevealError"></div>
                <div class="mt-3 d-none" id="tiRdRevealResult">
                    <label class="form-label">Senha do RustDesk</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="tiRdPlainPassword" readonly>
                        <button type="button" class="btn btn-outline-secondary" id="tiRdCopyPlain" title="Copiar">
                            <i class="fa-regular fa-copy"></i>
                        </button>
                    </div>
                    <div class="form-text">Ocultada automaticamente em 30 segundos.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary btn-sm" id="tiRdRevealConfirm">Confirmar</button>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var urlBase = <?= json_encode($url, JSON_UNESCAPED_SLASHES) ?>;
    var csrf = <?= json_encode($csrfReveal) ?>;
    var canReveal = <?= $canReveal ? 'true' : 'false' ?>;
    var encryptionOk = <?= $encryptionOk ? 'true' : 'false' ?>;
    var modalEl = document.getElementById('tiRdRevealModal');
    if (!modalEl) return;
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    var pendingId = 0;
    var pendingAcao = 'view';
    var hideTimer = null;

    function toast(msg, ok) {
        var wrap = document.getElementById('tiRdToastWrap');
        if (!wrap) {
            wrap = document.createElement('div');
            wrap.id = 'tiRdToastWrap';
            wrap.className = 'position-fixed top-0 end-0 p-3';
            wrap.style.zIndex = '1080';
            document.body.appendChild(wrap);
        }
        var el = document.createElement('div');
        el.className = 'alert alert-' + (ok ? 'success' : 'danger') + ' shadow-sm py-2 px-3 mb-2';
        el.textContent = msg;
        wrap.appendChild(el);
        setTimeout(function () { el.remove(); }, 2800);
    }

    function copyText(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text);
        }
        return new Promise(function (resolve, reject) {
            var ta = document.createElement('textarea');
            ta.value = text;
            document.body.appendChild(ta);
            ta.select();
            try {
                document.execCommand('copy');
                resolve();
            } catch (e) {
                reject(e);
            }
            ta.remove();
        });
    }

    window.tiRustdeskCopyId = function (idFmt) {
        copyText(String(idFmt).replace(/\s+/g, '')).then(function () {
            toast('ID copiado.', true);
        }).catch(function () {
            toast('Não foi possível copiar.', false);
        });
    };

    window.tiRustdeskAskSecret = function (id, acao) {
        if (!canReveal) {
            toast('Sem permissão para revelar a senha.', false);
            return;
        }
        if (!encryptionOk) {
            toast('Não foi possível abrir a senha (criptografia indisponível).', false);
            return;
        }
        pendingId = parseInt(id, 10) || 0;
        pendingAcao = acao === 'copy' ? 'copy' : 'view';
        document.getElementById('tiRdRevealActionLabel').textContent = pendingAcao === 'copy' ? 'copiar' : 'visualizar';
        document.getElementById('tiRdUserPassword').value = '';
        document.getElementById('tiRdRevealError').classList.add('d-none');
        document.getElementById('tiRdRevealResult').classList.add('d-none');
        document.getElementById('tiRdPlainPassword').value = '';
        document.getElementById('tiRdRevealConfirm').classList.remove('d-none');
        modal.show();
        setTimeout(function () { document.getElementById('tiRdUserPassword').focus(); }, 300);
    };

    document.getElementById('tiRdRevealConfirm').addEventListener('click', function () {
        var pwd = document.getElementById('tiRdUserPassword').value;
        var err = document.getElementById('tiRdRevealError');
        err.classList.add('d-none');
        if (!pwd) {
            err.textContent = 'Informe sua senha.';
            err.classList.remove('d-none');
            return;
        }
        var btn = this;
        btn.disabled = true;
        var body = new URLSearchParams();
        body.set('csrf_token', csrf);
        body.set('password', pwd);
        body.set('acao', pendingAcao);
        fetch(urlBase + 'ti-rustdesk-reveal/' + pendingId, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: body,
            credentials: 'same-origin'
        }).then(function (r) { return r.json(); }).then(function (data) {
            btn.disabled = false;
            if (!data || !data.success) {
                err.textContent = (data && data.message) ? data.message : 'Não foi possível abrir a senha.';
                err.classList.remove('d-none');
                return;
            }
            if (pendingAcao === 'copy') {
                copyText(data.senha).then(function () {
                    modal.hide();
                    toast('Senha copiada.', true);
                }).catch(function () {
                    err.textContent = 'Senha obtida, mas a cópia falhou. Use Visualizar.';
                    err.classList.remove('d-none');
                });
                return;
            }
            document.getElementById('tiRdPlainPassword').value = data.senha;
            document.getElementById('tiRdRevealResult').classList.remove('d-none');
            document.getElementById('tiRdRevealConfirm').classList.add('d-none');
            if (hideTimer) clearTimeout(hideTimer);
            hideTimer = setTimeout(function () {
                document.getElementById('tiRdPlainPassword').value = '';
                document.getElementById('tiRdRevealResult').classList.add('d-none');
                modal.hide();
            }, 30000);
        }).catch(function () {
            btn.disabled = false;
            err.textContent = 'Falha de comunicação. Tente novamente.';
            err.classList.remove('d-none');
        });
    });

    document.getElementById('tiRdCopyPlain').addEventListener('click', function () {
        var val = document.getElementById('tiRdPlainPassword').value;
        if (!val) return;
        copyText(val).then(function () { toast('Senha copiada.', true); });
    });

    document.getElementById('tiRdUserPassword').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('tiRdRevealConfirm').click();
        }
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        document.getElementById('tiRdUserPassword').value = '';
        document.getElementById('tiRdPlainPassword').value = '';
        document.getElementById('tiRdRevealResult').classList.add('d-none');
        document.getElementById('tiRdRevealConfirm').classList.remove('d-none');
        if (hideTimer) clearTimeout(hideTimer);
    });
});
</script>
