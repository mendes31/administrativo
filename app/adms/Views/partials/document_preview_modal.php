<style>
[data-adms-document-preview] {
    cursor: pointer;
}
#admsDocumentPreviewModalLoading {
    z-index: 2;
    pointer-events: none;
}
#admsDocumentPreviewModalFrame {
    transition: opacity 0.2s ease;
}
/* Cabeçalho/rodapé sempre acessíveis no mobile (PWA sem barra do Chrome) */
#admsDocumentPreviewModal .modal-content {
    max-height: 100dvh;
}
#admsDocumentPreviewModal .modal-header {
    position: sticky;
    top: 0;
    z-index: 5;
    background: #1a1a1a;
    padding-top: max(0.5rem, env(safe-area-inset-top));
}
#admsDocumentPreviewModal .modal-footer {
    position: sticky;
    bottom: 0;
    z-index: 5;
    background: #1a1a1a;
    padding-bottom: max(0.5rem, env(safe-area-inset-bottom));
}
#admsDocumentPreviewModal .btn-close {
    width: 2.75rem;
    height: 2.75rem;
    opacity: 1;
}
@media (max-width: 991.98px) {
    #admsDocumentPreviewModal .modal-body {
        min-height: 0 !important;
        flex: 1 1 auto;
    }
    #admsDocumentPreviewModalFrame {
        min-height: 50vh !important;
        height: calc(100dvh - 8.5rem) !important;
    }
    #admsDocumentPreviewModal .btn-adms-doc-close {
        min-width: 5.5rem;
        font-weight: 600;
    }
}
#informativoImageModal .modal-header {
    position: sticky;
    top: 0;
    z-index: 5;
    background: rgba(0, 0, 0, 0.85);
    padding-top: max(0.5rem, env(safe-area-inset-top));
}
#informativoImageModal .btn-close {
    width: 2.75rem;
    height: 2.75rem;
    opacity: 1;
}
</style>
<script>
(function () {
    'use strict';

    if (typeof window.admsIsPdfUrl !== 'function') {
        window.admsIsPdfUrl = function (url) {
            if (!url) {
                return false;
            }
            try {
                var decoded = decodeURIComponent(String(url)).toLowerCase();
                return /\.pdf(\?|#|$)/i.test(decoded) || decoded.indexOf('.pdf') !== -1;
            } catch (e) {
                return /\.pdf/i.test(String(url));
            }
        };
    }

    /**
     * Cliente mobile (viewport estreito ou UA móvel).
     * No Chrome/Android o PDF dentro de <iframe> vira a tela intermediária "Abrir".
     */
    if (typeof window.admsIsMobileClient !== 'function') {
        window.admsIsMobileClient = function () {
            try {
                if (window.matchMedia && window.matchMedia('(max-width: 991.98px)').matches) {
                    return true;
                }
            } catch (e) {
                /* ignore */
            }
            return /Android|webOS|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent || '');
        };
    }

    /**
     * Modal com iframe só no desktop.
     * Em mobile preferimos o visualizador nativo (abre o PDF direto, sem clicar em Abrir).
     */
    if (typeof window.admsPreferDocumentPreviewModal !== 'function') {
        window.admsPreferDocumentPreviewModal = function () {
            return !window.admsIsMobileClient();
        };
    }

    function setPreviewLoading(isLoading) {
        var loader = document.getElementById('admsDocumentPreviewModalLoading');
        var frame = document.getElementById('admsDocumentPreviewModalFrame');
        if (loader) {
            loader.classList.toggle('d-none', !isLoading);
        }
        if (frame) {
            frame.style.opacity = isLoading ? '0' : '1';
        }
    }

    window.admsOpenAttachmentUrl = function (url) {
        if (
            window.admsIsPdfUrl(url)
            && window.admsPreferDocumentPreviewModal()
            && typeof window.showAdmsDocumentPreviewModal === 'function'
        ) {
            window.showAdmsDocumentPreviewModal(url);
            return;
        }
        // Mobile / fallback: navega para o PDF (visualizador nativo do browser).
        window.location.assign(url);
    };

    if (typeof window.showAdmsDocumentPreviewModal === 'function') {
        return;
    }

    window.showAdmsDocumentPreviewModal = function (url) {
        var modalEl = document.getElementById('admsDocumentPreviewModal');
        var iframeEl;
        var downloadEl;

        if (!modalEl) {
            modalEl = document.createElement('div');
            modalEl.id = 'admsDocumentPreviewModal';
            modalEl.className = 'modal fade';
            modalEl.setAttribute('tabindex', '-1');
            modalEl.setAttribute('aria-hidden', 'true');
            modalEl.innerHTML = ''
                + '<div class="modal-dialog modal-dialog-centered modal-fullscreen-lg-down modal-xl">'
                + '  <div class="modal-content border-0 bg-dark d-flex flex-column" style="min-height:100%;">'
                + '    <div class="modal-header border-0 py-2 align-items-center">'
                + '      <span class="text-white small"><i class="fas fa-file-pdf text-danger me-1"></i>Documento PDF</span>'
                + '      <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>'
                + '    </div>'
                + '    <div class="modal-body p-0 d-flex flex-column position-relative flex-grow-1" style="min-height:70vh;">'
                + '      <div id="admsDocumentPreviewModalLoading" class="position-absolute top-50 start-50 translate-middle text-center">'
                + '        <div class="spinner-border text-light" role="status" aria-hidden="true"></div>'
                + '        <div class="text-white-50 small mt-2">Carregando documento...</div>'
                + '      </div>'
                + '      <iframe id="admsDocumentPreviewModalFrame" title="Visualização do PDF" class="flex-grow-1 w-100 border-0 bg-white" style="min-height:70vh;height:85vh;opacity:0;"></iframe>'
                + '    </div>'
                + '    <div class="modal-footer border-0 py-2 justify-content-between">'
                + '      <a id="admsDocumentPreviewModalDownload" href="#" class="btn btn-sm btn-outline-light" download><i class="fas fa-download me-1"></i>Download</a>'
                + '      <button type="button" class="btn btn-sm btn-light btn-adms-doc-close" data-bs-dismiss="modal">Fechar</button>'
                + '    </div>'
                + '  </div>'
                + '</div>';
            document.body.appendChild(modalEl);

            modalEl.addEventListener('hidden.bs.modal', function () {
                var frame = document.getElementById('admsDocumentPreviewModalFrame');
                if (frame) {
                    frame.removeAttribute('src');
                    frame.style.opacity = '0';
                }
                setPreviewLoading(true);
            });
        }

        iframeEl = document.getElementById('admsDocumentPreviewModalFrame');
        downloadEl = document.getElementById('admsDocumentPreviewModalDownload');

        setPreviewLoading(true);
        if (iframeEl) {
            iframeEl.onload = function () {
                setPreviewLoading(false);
            };
            iframeEl.onerror = function () {
                setPreviewLoading(false);
            };
            iframeEl.removeAttribute('src');
            iframeEl.src = url;
        }
        if (downloadEl) {
            downloadEl.href = url;
        }

        var modal = bootstrap.Modal.getOrCreateInstance(modalEl, {
            backdrop: 'static',
            keyboard: true
        });
        modal.show();
    };

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-adms-document-preview]');
        if (!trigger) {
            return;
        }
        var url = trigger.getAttribute('data-adms-document-preview') || trigger.href;
        if (!url) {
            return;
        }
        event.preventDefault();
        window.admsOpenAttachmentUrl(url);
    });
})();
</script>
