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
     * Modal com iframe só em desktop — em mobile o iframe mostra ecrã "Abrir" extra.
     */
    if (typeof window.admsPreferDocumentPreviewModal !== 'function') {
        window.admsPreferDocumentPreviewModal = function () {
            if (window.matchMedia('(max-width: 991.98px)').matches) {
                return false;
            }
            if (window.matchMedia('(hover: none) and (pointer: coarse)').matches) {
                return false;
            }
            return true;
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
        window.location.href = url;
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
                + '<div class="modal-dialog modal-dialog-centered modal-fullscreen-lg-down modal-xl modal-dialog-scrollable">'
                + '  <div class="modal-content border-0 bg-dark bg-opacity-90">'
                + '    <div class="modal-header border-0 py-2">'
                + '      <span class="text-white-50 small"><i class="fas fa-file-pdf text-danger me-1"></i>Documento PDF</span>'
                + '      <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>'
                + '    </div>'
                + '    <div class="modal-body p-0 d-flex flex-column position-relative" style="min-height:70vh;">'
                + '      <div id="admsDocumentPreviewModalLoading" class="position-absolute top-50 start-50 translate-middle text-center">'
                + '        <div class="spinner-border text-light" role="status" aria-hidden="true"></div>'
                + '        <div class="text-white-50 small mt-2">Carregando documento...</div>'
                + '      </div>'
                + '      <iframe id="admsDocumentPreviewModalFrame" title="Visualização do PDF" class="flex-grow-1 w-100 border-0 bg-white" style="min-height:70vh;height:85vh;opacity:0;"></iframe>'
                + '    </div>'
                + '    <div class="modal-footer border-0 py-2 justify-content-between">'
                + '      <a id="admsDocumentPreviewModalDownload" href="#" class="btn btn-sm btn-outline-light" download><i class="fas fa-download me-1"></i>Download</a>'
                + '      <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Fechar</button>'
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
