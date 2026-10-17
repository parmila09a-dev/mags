(function () {
  'use strict';

  function initNavigation() {
    var button = document.querySelector('.nav-toggle');
    var controlledId = button ? button.getAttribute('aria-controls') : '';
    var menu = controlledId ? document.getElementById(controlledId) : null;

    if (!menu) {
      menu = document.querySelector('.main-nav .menu');
    }

    if (!button || !menu) {
      return;
    }

    if (!menu.id) {
      menu.id = controlledId || 'primary-menu';
    }

    button.setAttribute('aria-controls', menu.id);
    var mobileQuery = window.matchMedia ? window.matchMedia('(max-width: 860px)') : null;

    function setMenuState(open, returnFocus) {
      menu.classList.toggle('is-open', open);
      button.setAttribute('aria-expanded', String(open));
      button.setAttribute('aria-label', open ? 'Close navigation menu' : 'Open navigation menu');
      button.textContent = open ? 'Close' : 'Menu';
      document.body.classList.toggle('mags-menu-open', open);

      if (!open && returnFocus) {
        button.focus();
      }
    }

    button.addEventListener('click', function () {
      setMenuState(!menu.classList.contains('is-open'), false);
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && menu.classList.contains('is-open')) {
        event.preventDefault();
        setMenuState(false, true);
      }
    });

    document.addEventListener('click', function (event) {
      if (menu.classList.contains('is-open') && !menu.contains(event.target) && !button.contains(event.target)) {
        setMenuState(false, false);
      }
    });

    menu.addEventListener('click', function (event) {
      if (event.target.closest('a') && (!mobileQuery || mobileQuery.matches)) {
        setMenuState(false, false);
      }
    });

    if (mobileQuery) {
      var resetMenu = function () {
        setMenuState(false, false);
      };

      if (typeof mobileQuery.addEventListener === 'function') {
        mobileQuery.addEventListener('change', resetMenu);
      } else if (typeof mobileQuery.addListener === 'function') {
        mobileQuery.addListener(resetMenu);
      }
    }
  }

  function init() {
    initNavigation();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
}());
