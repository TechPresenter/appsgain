/**
 * Appsgain — CAPTCHA + Animated CTA JavaScript
 * Handles CAPTCHA refresh, validation, CTA ripples, and animations
 */
(function () {
  'use strict';

  /* ══════════════════════════════════════════════════
     CAPTCHA FUNCTIONS
  ══════════════════════════════════════════════════ */

  /** Refresh CAPTCHA image for a given component ID */
  window.refreshCaptcha = function (captchaId) {
    const img    = document.getElementById(captchaId + '-img');
    const btn    = document.getElementById(captchaId + '-refresh');
    const input  = document.getElementById(captchaId + '-input');
    const errEl  = document.getElementById(captchaId + '-error');

    if (!img) return;

    /* Spinning animation */
    if (btn) {
      btn.classList.add('spinning');
      setTimeout(() => btn.classList.remove('spinning'), 700);
    }

    /* Reload image with new timestamp to bust cache */
    const base = img.src.split('?')[0];
    img.src    = base + '?t=' + Date.now();

    /* Clear input and error */
    if (input) {
      input.value = '';
      input.classList.remove('captcha-error-border', 'captcha-success-border');
      input.focus();
    }
    if (errEl) errEl.style.display = 'none';
  };

  /** Validate CAPTCHA input client-side (basic length check) */
  window.validateCaptchaInput = function (input) {
    if (!input) return true;
    const val = input.value.trim();
    const err = input.closest('.captcha-wrap')?.querySelector('[id$="-error"]');
    if (val.length !== 6) {
      input.classList.add('captcha-error-border');
      input.classList.remove('captcha-success-border');
      if (err) { err.innerHTML = '<i class="fas fa-exclamation-circle"></i> Please enter the complete 6-character code.'; err.style.display = 'flex'; }
      return false;
    }
    input.classList.remove('captcha-error-border');
    input.classList.add('captcha-success-border');
    if (err) err.style.display = 'none';
    return true;
  };

  /** CAPTCHA input — allow any case (server does case-insensitive compare) */
  document.querySelectorAll('.captcha-input').forEach(function (inp) {
    inp.addEventListener('input', function () {
      /* Remove error styling as user types — do NOT force uppercase */
      this.classList.remove('captcha-error-border');
      const err = this.closest('.captcha-wrap')?.querySelector('[id$="-error"]');
      if (err) err.style.display = 'none';
    });

    inp.addEventListener('blur', function () {
      validateCaptchaInput(this);
    });
  });

  /* ══════════════════════════════════════════════════
     ANIMATED CTA BUTTON RIPPLE EFFECT
  ══════════════════════════════════════════════════ */
  function initCTARipple() {
    document.querySelectorAll('.btn-cta-animated').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        const r = this.getBoundingClientRect();
        const x = e.clientX - r.left;
        const y = e.clientY - r.top;
        const rpl = document.createElement('span');
        rpl.className = 'cta-ripple';
        rpl.style.cssText = 'left:' + x + 'px;top:' + y + 'px';
        this.appendChild(rpl);
        rpl.addEventListener('animationend', function () { rpl.remove(); });
      });
    });
  }

  /* ══════════════════════════════════════════════════
     FORM PHONE VALIDATION (mandatory)
  ══════════════════════════════════════════════════ */
  function enforcePhoneMandatory() {
    document.querySelectorAll('input[type="tel"], input[name*="phone"]').forEach(function (tel) {
      if (!tel.hasAttribute('required')) {
        tel.setAttribute('required', 'required');
      }
      /* Add visual indicator */
      const label = tel.closest('.form-group, .fg')?.querySelector('label');
      if (label && !label.querySelector('.phone-req-star')) {
        const star = document.createElement('span');
        star.className = 'phone-req-star';
        star.style.cssText = 'color:#6a00ff;margin-left:2px;font-weight:900';
        star.textContent = '*';
        label.appendChild(star);
      }
    });
  }

  /* ══════════════════════════════════════════════════
     FORM VALIDATION ENHANCEMENT
  ══════════════════════════════════════════════════ */
  function initFormValidation() {
    /* Real-time validation on blur */
    document.querySelectorAll('.enhanced-form input, .enhanced-form select, .enhanced-form textarea').forEach(function (field) {
      field.addEventListener('blur', function () {
        validateField(this);
      });
      field.addEventListener('input', function () {
        if (this.dataset.touched) validateField(this);
      });
      field.addEventListener('blur', function () {
        this.dataset.touched = '1';
      });
    });
  }

  function validateField(field) {
    const val = field.value.trim();
    let valid  = true;
    let msg    = '';

    /* Required */
    if (field.required && !val) { valid = false; msg = 'This field is required.'; }
    /* Email */
    else if (field.type === 'email' && val && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) { valid = false; msg = 'Please enter a valid email address.'; }
    /* Phone */
    else if ((field.type === 'tel' || field.name?.includes('phone')) && val && val.replace(/\D/g,'').length < 7) { valid = false; msg = 'Please enter a valid phone number.'; }
    /* URL */
    else if (field.type === 'url' && val && !/^https?:\/\/.+/.test(val)) { valid = false; msg = 'Please enter a valid URL starting with http:// or https://'; }

    /* Apply styling */
    const wrap = field.closest('.form-group, .fg');
    if (!wrap) return valid;

    let errEl = wrap.querySelector('.field-error');
    if (!errEl) {
      errEl = document.createElement('div');
      errEl.className = 'field-error';
      errEl.style.cssText = 'font-size:12px;color:#6a00ff;margin-top:4px;display:flex;align-items:center;gap:4px;font-weight:600';
      wrap.appendChild(errEl);
    }

    if (!valid) {
      field.style.borderColor = '#6a00ff';
      field.style.boxShadow   = '0 0 0 3px rgba(106,0,255,.1)';
      errEl.innerHTML = '<i class="fas fa-exclamation-circle" style="font-size:11px"></i>' + msg;
      errEl.style.display = 'flex';
    } else {
      field.style.borderColor = '#10b981';
      field.style.boxShadow   = '0 0 0 3px rgba(16,185,129,.1)';
      errEl.style.display = 'none';
    }
    return valid;
  }

  /* ══════════════════════════════════════════════════
     CAPTCHA IMAGE — reload on network error
  ══════════════════════════════════════════════════ */
  document.querySelectorAll('.captcha-img').forEach(function (img) {
    img.addEventListener('error', function () {
      setTimeout(function () {
        const base = img.src.split('?')[0];
        img.src = base + '?t=' + Date.now();
      }, 1000);
    });
  });

  /* ══════════════════════════════════════════════════
     INIT
  ══════════════════════════════════════════════════ */
  function init() {
    initCTARipple();
    enforcePhoneMandatory();
    initFormValidation();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
