(function () {
  'use strict';

  var motionQuery = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;
  var dataQuery = window.matchMedia ? window.matchMedia('(prefers-reduced-data: reduce)') : null;
  var connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;

  function shouldStayStatic() {
    return Boolean(
      (motionQuery && motionQuery.matches) ||
      (dataQuery && dataQuery.matches) ||
      (connection && connection.saveData)
    );
  }

  function watchPreferences(callback) {
    [motionQuery, dataQuery, connection].forEach(function (source) {
      if (source && typeof source.addEventListener === 'function') {
        source.addEventListener('change', callback);
      }
    });
  }

  function initHeroCarousel() {
    var hero = document.querySelector('[data-mags-hero-carousel]');

    if (!hero) {
      return;
    }

    var slides = Array.prototype.slice.call(hero.querySelectorAll('[data-mags-hero-slide]'));
    var badges = Array.prototype.slice.call(hero.querySelectorAll('[data-mags-hero-badge]'));
    var previousButton = hero.querySelector('[data-mags-hero-previous]');
    var nextButton = hero.querySelector('[data-mags-hero-next]');
    var toggleButton = hero.querySelector('[data-mags-hero-toggle]');
    var toggleIcon = hero.querySelector('[data-mags-hero-toggle-icon]');
    var currentLabel = hero.querySelector('.mags-home-hero__current');
    var status = hero.querySelector('[data-mags-hero-status]');
    var media = hero.querySelector('.mags-home-hero__media');
    var interval = Math.max(3500, parseInt(hero.getAttribute('data-interval'), 10) || 5500);
    var transitionTime = 950;
    var activeIndex = 0;
    var timer = 0;
    var transitionTimer = 0;
    var isTransitioning = false;
    var isPaused = shouldStayStatic();
    var isInView = true;
    var pointerStartX = null;

    hero.style.setProperty('--mags-hero-interval', interval + 'ms');

    function setInactive(element, inactive) {
      if (!element) {
        return;
      }

      element.setAttribute('aria-hidden', inactive ? 'true' : 'false');
      if (inactive) {
        element.setAttribute('inert', '');
      } else {
        element.removeAttribute('inert');
      }
    }

    function syncBadge(index) {
      badges.forEach(function (badge, badgeIndex) {
        var active = badgeIndex === index;
        badge.classList.toggle('is-active', active);
        setInactive(badge, !active);
      });
    }

    function syncLabels() {
      var humanIndex = activeIndex + 1;
      if (currentLabel) {
        currentLabel.textContent = String(humanIndex).padStart(2, '0');
      }
      if (status) {
        status.textContent = 'Slide ' + humanIndex + ' of ' + slides.length;
      }
      hero.setAttribute('aria-label', 'School highlights, slide ' + humanIndex + ' of ' + slides.length);
    }

    function stopTimer() {
      window.clearTimeout(timer);
      timer = 0;
      hero.classList.remove('is-playing');
    }

    function startTimer() {
      stopTimer();
      if (slides.length < 2 || isPaused || !isInView || document.hidden || shouldStayStatic()) {
        return;
      }

      void hero.offsetWidth;
      hero.classList.add('is-playing');
      timer = window.setTimeout(function () {
        goTo(activeIndex + 1, 1);
      }, interval);
    }

    function syncToggle() {
      if (!toggleButton) {
        return;
      }
      toggleButton.setAttribute('aria-pressed', isPaused ? 'true' : 'false');
      toggleButton.setAttribute('aria-label', isPaused ? 'Play photograph rotation' : 'Pause photograph rotation');
      if (toggleIcon) {
        toggleIcon.textContent = isPaused ? '▶' : 'Ⅱ';
      }
    }

    function setPaused(paused) {
      isPaused = Boolean(paused);
      syncToggle();
      if (isPaused) {
        stopTimer();
      } else {
        startTimer();
      }
    }

    function goTo(requestedIndex, direction) {
      if (slides.length < 2 || isTransitioning) {
        return;
      }

      var nextIndex = (requestedIndex + slides.length) % slides.length;
      if (nextIndex === activeIndex) {
        return;
      }

      stopTimer();
      isTransitioning = true;
      window.clearTimeout(transitionTimer);

      var currentSlide = slides[activeIndex];
      var nextSlide = slides[nextIndex];
      var forward = direction >= 0;

      nextSlide.classList.remove('is-active', 'is-before', 'is-after');
      nextSlide.classList.add(forward ? 'is-after' : 'is-before');
      setInactive(nextSlide, false);
      void nextSlide.offsetWidth;

      currentSlide.classList.remove('is-active', 'is-before', 'is-after');
      currentSlide.classList.add(forward ? 'is-before' : 'is-after');
      nextSlide.classList.remove('is-before', 'is-after');
      nextSlide.classList.add('is-active');

      activeIndex = nextIndex;
      syncBadge(activeIndex);
      syncLabels();

      transitionTimer = window.setTimeout(function () {
        currentSlide.classList.remove('is-before', 'is-after');
        setInactive(currentSlide, true);
        isTransitioning = false;
        startTimer();
      }, transitionTime);
    }

    if (previousButton) {
      previousButton.addEventListener('click', function () {
        goTo(activeIndex - 1, -1);
      });
    }

    if (nextButton) {
      nextButton.addEventListener('click', function () {
        goTo(activeIndex + 1, 1);
      });
    }

    if (toggleButton) {
      toggleButton.addEventListener('click', function () {
        setPaused(!isPaused);
      });
    }

    hero.addEventListener('focusin', function () {
      setPaused(true);
    });

    if (media && window.PointerEvent) {
      media.addEventListener('pointerdown', function (event) {
        if (event.isPrimary) {
          pointerStartX = event.clientX;
        }
      });

      media.addEventListener('pointerup', function (event) {
        if (pointerStartX === null || !event.isPrimary) {
          return;
        }
        var distance = event.clientX - pointerStartX;
        pointerStartX = null;
        if (Math.abs(distance) >= 44) {
          goTo(activeIndex + (distance < 0 ? 1 : -1), distance < 0 ? 1 : -1);
        }
      });

      media.addEventListener('pointercancel', function () {
        pointerStartX = null;
      });
    }

    if ('IntersectionObserver' in window) {
      var heroObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          isInView = entry.isIntersecting;
          if (isInView) {
            startTimer();
          } else {
            stopTimer();
          }
        });
      }, { threshold: 0.05 });
      heroObserver.observe(hero);
    }

    document.addEventListener('visibilitychange', function () {
      if (document.hidden) {
        stopTimer();
      } else {
        startTimer();
      }
    });

    watchPreferences(function () {
      if (shouldStayStatic()) {
        setPaused(true);
      }
    });

    syncBadge(activeIndex);
    syncLabels();
    syncToggle();
    startTimer();
  }

  function initJourneyBackdrop() {
    var journey = document.querySelector('.mags-home-history');

    if (!journey) {
      return;
    }

    var isInView = false;

    function syncJourney() {
      var staticMode = shouldStayStatic();
      var shouldPlay = isInView && !staticMode && !document.hidden;

      journey.classList.toggle('is-journey-static', staticMode);
      journey.classList.toggle('is-journey-active', shouldPlay);
    }

    if ('IntersectionObserver' in window) {
      var journeyObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          isInView = entry.isIntersecting;
          syncJourney();
        });
      }, {
        rootMargin: '20% 0px',
        threshold: 0.01
      });

      journeyObserver.observe(journey);
    } else {
      isInView = true;
    }

    document.addEventListener('visibilitychange', syncJourney);
    watchPreferences(syncJourney);
    syncJourney();
  }

  function initSectionTransitions() {
    var home = document.querySelector('.mags-home');

    if (
      !home ||
      shouldStayStatic() ||
      !('IntersectionObserver' in window) ||
      !window.CSS ||
      !window.CSS.supports('clip-path', 'inset(0)')
    ) {
      return;
    }

    var sectionGroups = [
      ['.mags-home-purpose', '.mags-home-purpose__story, .mags-home-mission'],
      ['.mags-home-principles', '.mags-home-heading, .mags-home-principles__grid li'],
      ['.mags-home-history', '.mags-home-history__intro, .mags-home-history__facts'],
      ['.mags-home-leadership', '.mags-home-heading, .mags-home-leader'],
      ['.mags-home-campus', '.mags-home-heading, .mags-home-campus__grid figure, .mags-home-facilities'],
      ['.mags-home-support', '.mags-home-heading, .mags-home-support__grid article'],
      ['.mags-home-news', '.mags-home-heading, .mags-home-news__grid article'],
      ['.mags-home-updates', '.mags-home-notices, .mags-home-gallery']
    ];
    var sections = [];

    sectionGroups.forEach(function (group) {
      var section = home.querySelector(group[0]);

      if (!section) {
        return;
      }

      var items = Array.prototype.slice.call(section.querySelectorAll(group[1]));
      if (!items.length) {
        return;
      }

      section.classList.add('mags-home-reveal');

      items.forEach(function (item, itemIndex) {
        item.classList.add('mags-home-reveal__item');
        item.style.setProperty('--mags-reveal-order', Math.min(itemIndex, 4));
      });

      sections.push(section);
    });

    var passages = Array.prototype.slice.call(home.querySelectorAll('[data-mags-passage]'));

    if (!sections.length && !passages.length) {
      return;
    }

    home.classList.add('has-section-motion');

    var revealObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) {
          return;
        }

        entry.target.classList.add('is-revealed');
        revealObserver.unobserve(entry.target);
      });
    }, {
      rootMargin: '0px 0px -10% 0px',
      threshold: 0.08
    });

    sections.concat(passages).forEach(function (element) {
      revealObserver.observe(element);
    });

    watchPreferences(function () {
      if (!shouldStayStatic()) {
        return;
      }

      sections.forEach(function (section) {
        section.classList.add('is-revealed');
        revealObserver.unobserve(section);
      });
      passages.forEach(function (passage) {
        passage.classList.add('is-revealed');
        revealObserver.unobserve(passage);
      });
    });
  }

  initHeroCarousel();
  initJourneyBackdrop();
  initSectionTransitions();
}());
