/* ==================================================
   OneDine — Global Vanilla JS
   ================================================== */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    initPageLoader();
    initNavbarScroll();
    initMobileNav();
    initThemeToggle();
    initBackToTop();
    initFadeUp();
    initCounters();
    initTestimonialSlider();
    initAccordion();
    initSmoothAnchors();
  });

  /* ---------- Elegant Page Loader ---------- */
  function initPageLoader() {
    var loader = document.getElementById('pageLoader');
    if (!loader) return;
    window.addEventListener('load', function () {
      setTimeout(function () {
        loader.classList.add('hidden');
      }, 250);
    });
  }

  /* ---------- Navbar scroll state ---------- */
  function initNavbarScroll() {
    var navbar = document.getElementById('navbar');
    if (!navbar) return;

    function update() {
      if (window.scrollY > 40) {
        navbar.classList.add('scrolled');
      } else {
        navbar.classList.remove('scrolled');
      }
    }
    update();
    window.addEventListener('scroll', update, { passive: true });
  }

  /* ---------- Mobile navigation ---------- */
  function initMobileNav() {
    var toggle = document.getElementById('navToggle');
    var nav = document.getElementById('mobileNav');
    var backdrop = document.getElementById('navBackdrop');
    if (!toggle || !nav || !backdrop) return;

    function close() {
      toggle.classList.remove('active');
      nav.classList.remove('active');
      backdrop.classList.remove('active');
      document.body.style.overflow = '';
    }

    function open() {
      toggle.classList.add('active');
      nav.classList.add('active');
      backdrop.classList.add('active');
      document.body.style.overflow = 'hidden';
    }

    toggle.addEventListener('click', function () {
      nav.classList.contains('active') ? close() : open();
    });
    backdrop.addEventListener('click', close);
    nav.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', close);
    });
  }

  /* ---------- Dark mode toggle ---------- */
  function initThemeToggle() {
    var btn = document.getElementById('themeToggle');
    var root = document.documentElement;
    var stored = localStorage.getItem('onedine-theme');

    if (stored) {
      root.setAttribute('data-theme', stored);
      updateIcon(stored);
    }

    if (!btn) return;

    btn.addEventListener('click', function () {
      var current = root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
      var next = current === 'dark' ? 'light' : 'dark';
      root.setAttribute('data-theme', next);
      localStorage.setItem('onedine-theme', next);
      updateIcon(next);
    });

    function updateIcon(theme) {
      var icon = btn.querySelector('i');
      if (!icon) return;
      icon.className = theme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
    }
  }

  /* ---------- Back to top ---------- */
  function initBackToTop() {
    var btn = document.getElementById('backToTop');
    if (!btn) return;

    window.addEventListener('scroll', function () {
      btn.classList.toggle('visible', window.scrollY > 480);
    }, { passive: true });

    btn.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  /* ---------- Fade-up on scroll ---------- */
  function initFadeUp() {
    var els = document.querySelectorAll('.fade-up');
    if (!els.length) return;

    if (!('IntersectionObserver' in window)) {
      els.forEach(function (el) { el.classList.add('in-view'); });
      return;
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('in-view');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

    els.forEach(function (el) { observer.observe(el); });
  }

  /* ---------- Animated counters ---------- */
  function initCounters() {
    var counters = document.querySelectorAll('[data-counter]');
    if (!counters.length) return;

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        animateCounter(entry.target);
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.4 });

    counters.forEach(function (el) { observer.observe(el); });

    function animateCounter(el) {
      var target = parseFloat(el.getAttribute('data-counter'));
      var suffix = el.getAttribute('data-suffix') || '';
      var duration = 1600;
      var start = null;

      function step(timestamp) {
        if (!start) start = timestamp;
        var progress = Math.min((timestamp - start) / duration, 1);
        var eased = 1 - Math.pow(1 - progress, 3);
        var value = eased * target;
        el.textContent = (target % 1 === 0 ? Math.round(value) : value.toFixed(1)) + suffix;
        if (progress < 1) requestAnimationFrame(step);
      }
      requestAnimationFrame(step);
    }
  }

  /* ---------- Modals ---------- */
  /* ---------- Testimonial slider (native scroll snap + buttons) ---------- */
  function initTestimonialSlider() {
    var track = document.querySelector('.testimonial-track');
    var prev = document.querySelector('[data-testimonial-prev]');
    var next = document.querySelector('[data-testimonial-next]');
    if (!track) return;

    function scrollByCard(dir) {
      var card = track.querySelector('.testimonial-card');
      var gap = 24;
      var amount = card ? card.offsetWidth + gap : 340;
      track.scrollBy({ left: dir * amount, behavior: 'smooth' });
    }

    if (prev) prev.addEventListener('click', function () { scrollByCard(-1); });
    if (next) next.addEventListener('click', function () { scrollByCard(1); });
  }

  /* ---------- Accordion (FAQ) ---------- */
  function initAccordion() {
    document.querySelectorAll('.accordion-item').forEach(function (item) {
      var header = item.querySelector('.accordion-header');
      var body = item.querySelector('.accordion-body');
      if (!header || !body) return;

      header.addEventListener('click', function () {
        var isActive = item.classList.contains('active');

        item.parentElement.querySelectorAll('.accordion-item').forEach(function (sibling) {
          sibling.classList.remove('active');
          var sBody = sibling.querySelector('.accordion-body');
          if (sBody) sBody.style.maxHeight = null;
        });

        if (!isActive) {
          item.classList.add('active');
          body.style.maxHeight = body.scrollHeight + 'px';
        }
      });
    });
  }

  /* ---------- Smooth scroll for in-page anchors ---------- */
  function initSmoothAnchors() {
    document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
      anchor.addEventListener('click', function (e) {
        var id = anchor.getAttribute('href');
        if (id.length < 2) return;
        var target = document.querySelector(id);
        if (!target) return;
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    });
  }

  /* ---------- Toast helper (global) ---------- */
  window.showToast = function (message, type, duration) {
    var container = document.getElementById('toastContainer');
    if (!container) return;

    var toast = document.createElement('div');
    toast.className = 'toast' + (type === 'error' ? ' toast-error' : '');
    toast.innerHTML = '<i class="fa-solid ' + (type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check') + '"></i><span>' + message + '</span>';
    container.appendChild(toast);

    requestAnimationFrame(function () { toast.classList.add('show'); });

    setTimeout(function () {
      toast.classList.remove('show');
      setTimeout(function () { toast.remove(); }, 300);
    }, duration || 3200);
  };
  function showToast(message, type) { window.showToast(message, type); }

  /* ---------- Shared form validation UI (custom, non-native tooltips) ---------- */
  function fieldErrorMessage(input) {
    var v = input.validity;
    if (v.valueMissing) return 'This field is required.';
    if (v.typeMismatch) return input.type === 'email' ? 'Enter a valid email address.' : 'Enter a valid value.';
    if (v.patternMismatch) return 'Please match the requested format.';
    if (v.tooShort) return 'Please enter at least ' + input.minLength + ' characters.';
    if (v.tooLong) return 'Please enter no more than ' + input.maxLength + ' characters.';
    if (v.rangeUnderflow || v.rangeOverflow) return 'Value is out of range.';
    return input.validationMessage || 'This field is invalid.';
  }

  function fieldWrapper(input) {
    return input.closest('.form-group') || input.parentElement;
  }

  function showFieldError(input) {
    var wrapper = fieldWrapper(input);
    if (!wrapper) return;
    wrapper.classList.add('has-error');
    var msg = wrapper.querySelector('.form-error');
    if (!msg) {
      msg = document.createElement('span');
      msg.className = 'form-error';
      msg.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i><span></span>';
      wrapper.appendChild(msg);
    }
    msg.querySelector('span').textContent = fieldErrorMessage(input);
  }

  function clearFieldError(input) {
    var wrapper = fieldWrapper(input);
    if (wrapper) wrapper.classList.remove('has-error');
  }

  function validateField(input) {
    if (input.checkValidity()) {
      clearFieldError(input);
      return true;
    }
    showFieldError(input);
    return false;
  }

  function validatableFields(scopeEl) {
    return Array.prototype.slice.call(scopeEl.querySelectorAll('input:not([type="hidden"]), select, textarea'));
  }

  window.OneDineValidation = {
    validateField: validateField,
    clearFieldError: clearFieldError,
    validateScope: function (scopeEl) {
      var firstInvalid = null;
      var valid = true;
      validatableFields(scopeEl).forEach(function (input) {
        if (!validateField(input)) {
          valid = false;
          if (!firstInvalid) firstInvalid = input;
        }
      });
      if (firstInvalid) {
        firstInvalid.focus();
        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
      return valid;
    },
    bindFieldEvents: function (scopeEl) {
      validatableFields(scopeEl).forEach(function (input) {
        input.addEventListener('blur', function () { validateField(input); });
        input.addEventListener('input', function () {
          if (input.checkValidity()) clearFieldError(input);
        });
        input.addEventListener('change', function () {
          if (input.checkValidity()) clearFieldError(input);
        });
      });
    }
  };
})();
