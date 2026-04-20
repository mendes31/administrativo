/**
 * Swal Lite
 * Compatibilidade mínima para `Swal.fire(...)` sem dependência de CDN.
 * Cobre os usos atuais do projeto (confirmações e alertas simples).
 */
(function () {
  'use strict';

  function normalize(options) {
    if (typeof options === 'string') {
      return { title: options };
    }
    return options || {};
  }

  function buildMessage(opts) {
    var title = opts.title ? String(opts.title) : '';
    var text = opts.text ? String(opts.text) : '';
    if (title && text) {
      return title + '\n\n' + text;
    }
    return title || text || 'Atenção';
  }

  function fire(options) {
    var opts = normalize(options);
    var message = buildMessage(opts);
    var hasCancel = !!opts.showCancelButton;

    if (hasCancel) {
      var confirmed = window.confirm(message);
      return Promise.resolve({
        isConfirmed: confirmed,
        isDenied: false,
        isDismissed: !confirmed
      });
    }

    window.alert(message);
    return Promise.resolve({
      isConfirmed: true,
      isDenied: false,
      isDismissed: false
    });
  }

  window.Swal = window.Swal || {};
  window.Swal.fire = fire;
})();
