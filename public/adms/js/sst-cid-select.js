/**
 * Select2 AJAX para campos CID no módulo SST.
 */
(function () {
    'use strict';

    function initSstCidSelects() {
        if (typeof jQuery === 'undefined' || !jQuery.fn.select2) {
            return;
        }

        jQuery('.sst-cid-select').each(function () {
            var $el = jQuery(this);
            if ($el.data('select2')) {
                return;
            }

            var url = $el.data('search-url') || '';
            var frequentes = $el.data('frequentes') === 1 || $el.data('frequentes') === '1';

            $el.select2({
                width: '100%',
                placeholder: 'Buscar CID por código ou descrição...',
                allowClear: true,
                minimumInputLength: 0,
                ajax: {
                    url: url,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term || '',
                            frequentes: params.term ? '0' : (frequentes ? '1' : '0')
                        };
                    },
                    processResults: function (data) {
                        return data;
                    },
                    cache: true
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSstCidSelects);
    } else {
        initSstCidSelects();
    }
})();
