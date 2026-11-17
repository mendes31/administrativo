<?php
/**
 * Aviso de alterações não salvas ao sair da edição do item (links e fechar aba).
 */
?>
<script>
(function () {
    const form = document.getElementById('form-update-inventory-item');
    if (!form) {
        return;
    }

    const LEAVE_MSG = 'Existem alterações não salvas. Se continuar, tudo que não foi salvo será descartado.\n\nDeseja continuar?';
    let formDirty = false;

    window.invItemMarkDirty = function () {
        formDirty = true;
        if (window.AdmsUnsavedFormGuard && typeof window.AdmsUnsavedFormGuard.markDirty === 'function') {
            window.AdmsUnsavedFormGuard.markDirty(form);
        }
    };

    window.invItemClearDirty = function () {
        formDirty = false;
        if (window.AdmsUnsavedFormGuard && typeof window.AdmsUnsavedFormGuard.markClean === 'function') {
            window.AdmsUnsavedFormGuard.markClean(form);
        }
    };

    form.addEventListener('input', function () {
        formDirty = true;
    });

    form.addEventListener('change', function () {
        formDirty = true;
    });

    document.querySelectorAll('.js-inv-item-leave').forEach(function (link) {
        link.addEventListener('click', function (event) {
            if (window.AdmsUnsavedFormGuard || !formDirty) {
                return;
            }
            event.preventDefault();
            if (window.confirm(LEAVE_MSG)) {
                formDirty = false;
                window.location.href = link.href;
            }
        });
    });

    window.addEventListener('beforeunload', function (event) {
        if (window.AdmsUnsavedFormGuard || !formDirty) {
            return;
        }
        event.preventDefault();
        event.returnValue = '';
    });
})();
</script>
