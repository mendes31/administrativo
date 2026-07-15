/**
 * Busca de endereço por CEP (ViaCEP) — mesmo racional do CRM de parceiros.
 * Campos esperados: #cep, #btn-search-cep, #no-cep-checkbox, #pais_residencia_iso,
 * #uf, #municipio, #bairro, #endereco, #numero_endereco
 */
(function () {
    function qs(id) {
        return document.getElementById(id);
    }

    function setHighlight(el, on) {
        if (!el) return;
        el.style.backgroundColor = on ? '#e3f2fd' : '';
    }

    function setAddressFieldsEnabled(enabled) {
        ['uf', 'municipio', 'bairro', 'endereco', 'numero_endereco', 'complemento_endereco', 'pais_residencia_iso'].forEach(function (id) {
            var el = qs(id);
            if (!el) return;
            el.disabled = !enabled;
            if (!enabled) {
                setHighlight(el, false);
            }
        });
    }

    function showCepMsg(type, message) {
        var loading = qs('cep-loading');
        var success = qs('cep-success');
        var error = qs('cep-error');
        if (loading) loading.classList.add('d-none');
        if (success) success.classList.add('d-none');
        if (error) {
            error.classList.add('d-none');
            error.textContent = '';
        }
        if (type === 'loading' && loading) {
            loading.classList.remove('d-none');
        } else if (type === 'success' && success) {
            success.classList.remove('d-none');
            setTimeout(function () {
                success.classList.add('d-none');
            }, 3000);
        } else if (type === 'error' && error) {
            error.classList.remove('d-none');
            error.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + message;
        }
    }

    function formatCepValue(value) {
        var digits = String(value || '').replace(/\D/g, '').slice(0, 8);
        if (digits.length > 5) {
            return digits.slice(0, 5) + '-' + digits.slice(5);
        }
        return digits;
    }

    function searchCep() {
        var cepInput = qs('cep');
        if (!cepInput) return;
        var cep = cepInput.value.replace(/\D/g, '');
        if (cep.length !== 8) {
            showCepMsg('error', 'CEP deve ter 8 dígitos.');
            return;
        }

        showCepMsg('loading');
        fetch('https://viacep.com.br/ws/' + cep + '/json/')
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (data.erro) {
                    showCepMsg('error', 'CEP não encontrado. Marque "Não sei o CEP" para preencher manualmente.');
                    return;
                }

                setAddressFieldsEnabled(true);

                var pais = qs('pais_residencia_iso');
                if (pais) {
                    pais.value = 'BR';
                }

                var uf = qs('uf');
                if (uf && data.uf) {
                    uf.value = data.uf;
                    setHighlight(uf, true);
                }

                var municipio = qs('municipio');
                if (municipio) {
                    municipio.value = data.localidade || '';
                    setHighlight(municipio, !!data.localidade);
                }

                var bairro = qs('bairro');
                if (bairro) {
                    bairro.value = data.bairro || '';
                    setHighlight(bairro, !!data.bairro);
                }

                var endereco = qs('endereco');
                if (endereco) {
                    endereco.value = data.logradouro || '';
                    setHighlight(endereco, !!data.logradouro);
                }

                showCepMsg('success');
                var numero = qs('numero_endereco');
                if (numero) {
                    numero.focus();
                }
            })
            .catch(function () {
                showCepMsg('error', 'Erro ao buscar CEP. Verifique a conexão com a internet.');
            });
    }

    function enableManualMode(checked) {
        setAddressFieldsEnabled(true);
        if (checked) {
            ['uf', 'municipio', 'bairro', 'endereco'].forEach(function (id) {
                setHighlight(qs(id), false);
            });
            var pais = qs('pais_residencia_iso');
            if (pais && !pais.value) {
                pais.value = 'BR';
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var root = qs('user-address-fields');
        if (!root) return;

        var cepInput = qs('cep');
        var btnSearch = qs('btn-search-cep');
        var noCep = qs('no-cep-checkbox');
        var hasAddress = root.getAttribute('data-has-address') === '1';

        if (!hasAddress && (!noCep || !noCep.checked)) {
            setAddressFieldsEnabled(false);
            if (cepInput) {
                cepInput.disabled = false;
            }
            if (btnSearch) {
                btnSearch.disabled = false;
            }
        }

        if (cepInput) {
            cepInput.addEventListener('input', function (e) {
                e.target.value = formatCepValue(e.target.value);
            });
            cepInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchCep();
                }
            });
        }

        if (btnSearch) {
            btnSearch.addEventListener('click', searchCep);
        }

        if (noCep) {
            noCep.addEventListener('change', function () {
                enableManualMode(this.checked);
                if (this.checked) {
                    showCepMsg('clear');
                    var uf = qs('uf');
                    if (uf) uf.focus();
                } else if (!hasAddress) {
                    setAddressFieldsEnabled(false);
                    if (cepInput) cepInput.disabled = false;
                    if (btnSearch) btnSearch.disabled = false;
                }
            });
        }

        // Campos disabled não vão no POST — reabilitar antes de enviar
        var form = cepInput ? cepInput.closest('form') : null;
        if (form) {
            form.addEventListener('submit', function () {
                setAddressFieldsEnabled(true);
            });
        }
    });
})();
