/* ==================================================
   Contact Page — Form Submission (frontend-only)
   ================================================== */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('contactForm');
    if (!form) return;

    if (window.OneDineValidation) window.OneDineValidation.bindFieldEvents(form);

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (window.OneDineValidation && !window.OneDineValidation.validateScope(form)) return;
      showToast('Your message has been sent. We\'ll be in touch soon!', 'success');
      form.reset();
    });
  });
})();
