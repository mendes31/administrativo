(function () {
  var QUERY = '(max-width: 991.98px)';

  function toggleLists() {
    var mq = window.matchMedia(QUERY);
    var desktops = document.querySelectorAll('.list-desktop, .log-desktop');
    var mobiles = document.querySelectorAll('.list-mobile, .log-mobile');

    if (mq.matches) {
      desktops.forEach(function (el) { el.classList.add('d-none'); });
      mobiles.forEach(function (el) { el.classList.remove('d-none'); });
    } else {
      desktops.forEach(function (el) { el.classList.remove('d-none'); });
      mobiles.forEach(function (el) { el.classList.add('d-none'); });
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    toggleLists();
    var mq = window.matchMedia(QUERY);
    if (typeof mq.addEventListener === 'function') {
      mq.addEventListener('change', toggleLists);
    } else if (typeof mq.addListener === 'function') {
      mq.addListener(toggleLists);
    }
    window.addEventListener('resize', toggleLists);
  });
})();


