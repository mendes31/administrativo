(function () {
  function setupLogAccessResponsive() {
    var desktop = document.querySelector('.log-desktop');
    var mobile = document.querySelector('.log-mobile');
    if (!desktop && !mobile) return;

    var mq = window.matchMedia('(max-width: 991.98px)');

    var apply = function () {
      if (mq.matches) {
        if (desktop) desktop.classList.add('d-none');
        if (mobile) mobile.classList.remove('d-none');
      } else {
        if (desktop) desktop.classList.remove('d-none');
        if (mobile) mobile.classList.add('d-none');
      }
    };

    apply();

    if (typeof mq.addEventListener === 'function') {
      mq.addEventListener('change', apply);
    } else if (typeof mq.addListener === 'function') {
      mq.addListener(apply);
    }

    window.addEventListener('resize', apply);
  }

  document.addEventListener('DOMContentLoaded', setupLogAccessResponsive);
})();


