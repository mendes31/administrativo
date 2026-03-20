(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        /**
         * Modais dentro de #layoutSidenav_content ficam presos ao stacking context (z-index:1 no mobile).
         * O modal fullscreen fica ABAIXO da navbar (sb-topnav ~1030) e o cabeçalho/botões somem.
         * Solução: anexar cada .modal ao <body>, como recomenda o Bootstrap.
         */
        function moveModalsToBody() {
            var wrapper = document.getElementById('layoutSidenav_content');
            if (!wrapper) return;
            var modals = wrapper.querySelectorAll('.modal');
            modals.forEach(function (modal) {
                if (modal.parentElement !== document.body) {
                    document.body.appendChild(modal);
                }
            });
        }

        moveModalsToBody();

        /**
         * Corrigir pointer-events nos modais quando abrirem
         * NÃO modificar backdrop - deixar CSS gerenciar
         */
        function fixModalPointerEvents() {
            const openModals = document.querySelectorAll('.modal.show');
            
            openModals.forEach(modal => {
                const modalDialog = modal.querySelector('.modal-dialog');
                const modalContent = modal.querySelector('.modal-content');
                const modalBody = modal.querySelector('.modal-body');
                
                // Corrigir pointer-events (Bootstrap define como none)
                if (modalDialog) {
                    modalDialog.style.pointerEvents = 'auto';
                }
                if (modalContent) {
                    modalContent.style.pointerEvents = 'auto';
                }
                if (modalBody) {
                    modalBody.style.pointerEvents = 'auto';
                }
                
            });
        }

        // Executar quando modal abrir
        document.addEventListener('shown.bs.modal', function() {
            fixModalPointerEvents();
        });
    });

})();
