<style>
.adms-view-rich-content img {
    cursor: zoom-in;
}
[data-adms-image-preview] {
    cursor: zoom-in;
}
</style>
<script>
(function () {
    'use strict';

    if (typeof window.showAdmsImagePreviewModal === 'function') {
        return;
    }

    window.showAdmsImagePreviewModal = function (url) {
        var modalEl = document.getElementById('admsImagePreviewModal');
        var imgEl;

        if (!modalEl) {
            modalEl = document.createElement('div');
            modalEl.id = 'admsImagePreviewModal';
            modalEl.className = 'modal fade';
            modalEl.setAttribute('tabindex', '-1');
            modalEl.setAttribute('aria-hidden', 'true');
            modalEl.innerHTML = ''
                + '<div class="modal-dialog modal-dialog-centered modal-fullscreen-md-down modal-xl">'
                + '  <div class="modal-content border-0 bg-dark bg-opacity-75">'
                + '    <div class="modal-header border-0">'
                + '      <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>'
                + '    </div>'
                + '    <div class="modal-body d-flex align-items-center justify-content-center p-1 p-md-3">'
                + '      <img id="admsImagePreviewModalImg" src="" alt="Imagem" class="img-fluid" style="max-height:90vh;object-fit:contain;">'
                + '    </div>'
                + '  </div>'
                + '</div>';
            document.body.appendChild(modalEl);
        }

        imgEl = document.getElementById('admsImagePreviewModalImg');
        if (imgEl) {
            imgEl.src = url;
        }

        var modal = bootstrap.Modal.getOrCreateInstance(modalEl, {
            backdrop: 'static',
            keyboard: true
        });
        modal.show();
    };

    document.addEventListener('click', function (event) {
        var thumb = event.target.closest('[data-adms-image-preview]');
        if (!thumb) {
            thumb = event.target.closest('.adms-view-rich-content img');
        }
        if (!thumb || thumb.tagName !== 'IMG') {
            return;
        }
        var url = thumb.getAttribute('data-adms-image-preview') || thumb.currentSrc || thumb.src;
        if (!url || url.indexOf('data:') === 0) {
            return;
        }
        event.preventDefault();
        window.showAdmsImagePreviewModal(url);
    });
})();
</script>
