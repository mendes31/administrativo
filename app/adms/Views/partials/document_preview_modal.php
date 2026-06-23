<style>
[data-adms-document-preview] {
    cursor: pointer;
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

    if (typeof window.admsOpenAttachmentUrl !== 'function') {
        window.admsOpenAttachmentUrl = function (url) {
            if (window.admsIsPdfUrl(url) && typeof window.showAdmsDocumentPreviewModal === 'function') {
                window.showAdmsDocumentPreviewModal(url);
                return;
            }
            window.location.href = url;
        };
    }

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
                + '    <div class="modal-body p-0 d-flex flex-column" style="min-height:70vh;">'
                + '      <iframe id="admsDocumentPreviewModalFrame" title="Visualização do PDF" class="flex-grow-1 w-100 border-0 bg-white" style="min-height:70vh;height:85vh;"></iframe>'
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
                    frame.src = 'about:blank';
                }
            });
        }

        iframeEl = document.getElementById('admsDocumentPreviewModalFrame');
        downloadEl = document.getElementById('admsDocumentPreviewModalDownload');
        if (iframeEl) {
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
        window.showAdmsDocumentPreviewModal(url);
    });
})();
</script>
