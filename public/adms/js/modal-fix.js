(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        console.log('🔧 Modal fix: inicializando...');

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
                
                console.log('✅ Modal pointer-events corrigidos');
            });
        }

        // Executar quando modal abrir
        document.addEventListener('shown.bs.modal', function(e) {
            console.log('🔧 Modal aberto - aplicando correções');
            fixModalPointerEvents();
        });

        console.log('✅ Modal fix aplicado');
    });

})();
