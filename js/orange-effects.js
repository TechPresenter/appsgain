/**
 * Appsgain — Premium Orange Theme Effects Engine
 * Scroll reveals · Button ripple · Scroll bar · Parallax · Counters · Glow
 * Lightweight, no dependencies, mobile-optimized
 */
(function () {
  'use strict';

  const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ═══════════════════════════════════════════════════════
     1. SCROLL PROGRESS BAR
  ══════════════════════════════════════════════════════════ */
  function initScrollBar() {
    const bar = document.createElement('div');
    bar.id = 'or-scroll-bar';
    document.body.prepend(bar);

    const update = () => {
      const scrolled = window.scrollY;
      const total    = document.documentElement.scrollHeight - window.innerHeight;
      bar.style.width = (total > 0 ? (scrolled / total) * 100 : 0) + '%';
    };
    window.addEventListener('scroll', update, { passive: true });
    update();
  }

  /* ═══════════════════════════════════════════════════════
     2. SCROLL-REVEAL (Intersection Observer)
  ══════════════════════════════════════════════════════════ */
  function initScrollReveal() {
    if (prefersReduced) {
      document.querySelectorAll('.or-reveal,.or-reveal-left,.or-reveal-right,.or-reveal-scale')
        .forEach(el => el.classList.add('or-visible'));
      return;
    }

    const obs = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('or-visible');
          obs.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

    /* Auto-tag section children that don't already have reveal classes */
    document.querySelectorAll(
      '.saas-svc-grid .saas-svc-card,' +
      '.saas-projects-grid .saas-proj-card,' +
      '.saas-blog-grid .saas-blog-card,' +
      '.saas-why-grid .saas-why-card,' +
      '.saas-process-grid .saas-process-step,' +
      '.saas-tech-cats .saas-tech-cat,' +
      '.svc-cards-grid .svc-card-item,' +
      '.apps-grid .app-card-3d,' +
      '.prod-grid .prod-card'
    ).forEach((el, i) => {
      if (!el.classList.contains('or-reveal') &&
          !el.classList.contains('or-reveal-left') &&
          !el.classList.contains('or-reveal-right')) {
        el.classList.add('or-reveal');
        el.style.transitionDelay = (i % 4 * 0.1) + 's';
      }
    });

    document.querySelectorAll('.or-reveal,.or-reveal-left,.or-reveal-right,.or-reveal-scale')
      .forEach(el => obs.observe(el));
  }

  /* ═══════════════════════════════════════════════════════
     3. BUTTON RIPPLE EFFECT
  ══════════════════════════════════════════════════════════ */
  function initRipple() {
    const rippleSel =
      '.btn-saas-primary,.btn-saas-secondary,.btn-orange,' +
      '.saas-cta .btn-saas-primary,.ft-nl-btn,' +
      '.ag-drawer-cta,.ag-mob-sub-all,.saas-filter-tab';

    document.querySelectorAll(rippleSel).forEach(btn => {
      btn.style.position = 'relative';
      btn.style.overflow = 'hidden';

      btn.addEventListener('click', function (e) {
        const r    = this.getBoundingClientRect();
        const x    = e.clientX - r.left;
        const y    = e.clientY - r.top;
        const rpl  = document.createElement('span');
        rpl.className = 'or-ripple';
        rpl.style.cssText = `left:${x}px;top:${y}px`;
        this.appendChild(rpl);
        rpl.addEventListener('animationend', () => rpl.remove());
      });
    });
  }

  /* ═══════════════════════════════════════════════════════
     4. CARD 3D TILT (orange-enhanced)
  ══════════════════════════════════════════════════════════ */
  function initCardTilt() {
    if (prefersReduced || window.matchMedia('(hover:none)').matches) return;

    document.querySelectorAll(
      '.saas-svc-card,.saas-proj-card,.saas-why-card,.saas-testi-card,.app-card-3d,.prod-card'
    ).forEach(card => {
      const strength = parseFloat(card.dataset.tiltStrength || '10');

      card.addEventListener('mousemove', e => {
        const rect = card.getBoundingClientRect();
        const cx   = rect.width  / 2;
        const cy   = rect.height / 2;
        const rx   = -((e.clientY - rect.top  - cy) / cy) * strength;
        const ry   =  ((e.clientX - rect.left - cx) / cx) * strength;

        card.style.transform  = `perspective(900px) rotateX(${rx}deg) rotateY(${ry}deg) translateZ(6px)`;
        card.style.transition = 'none';
        card.style.boxShadow  = `${-ry * 1.5}px ${rx * 1.5}px 28px rgba(255,107,0,.12)`;
      });

      card.addEventListener('mouseleave', () => {
        card.style.transition = 'transform .5s ease, box-shadow .5s ease';
        card.style.transform  = '';
        card.style.boxShadow  = '';
      });
    });
  }

  /* ═══════════════════════════════════════════════════════
     5. ANIMATED COUNTERS (enhanced orange)
  ══════════════════════════════════════════════════════════ */
  function initCounters() {
    const counters = document.querySelectorAll('.saas-counter, .counter, [data-target]');
    if (!counters.length) return;

    const obs = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        const el     = entry.target;
        const target = parseInt(el.dataset.target || el.textContent, 10);
        const suffix = el.dataset.suffix || (el.textContent.replace(/[0-9]/g, '').trim()) || '';
        if (isNaN(target)) return;

        const dur   = 2000;
        const start = performance.now();
        const anim  = now => {
          const t   = Math.min((now - start) / dur, 1);
          const ease = 1 - Math.pow(1 - t, 4);
          el.textContent = Math.round(ease * target) + suffix;
          if (t < 1) requestAnimationFrame(anim);
          else el.textContent = target + suffix;
        };
        requestAnimationFrame(anim);
        obs.unobserve(el);
      });
    }, { threshold: 0.5 });

    counters.forEach(c => obs.observe(c));
  }

  /* ═══════════════════════════════════════════════════════
     6. PARALLAX on hero floating cards
  ══════════════════════════════════════════════════════════ */
  function initParallax() {
    if (prefersReduced || window.matchMedia('(hover:none)').matches) return;

    const hero = document.querySelector('.saas-hero');
    if (!hero) return;

    const layers = [
      { el: hero.querySelector('.hv-float-code'),  depth: 0.02 },
      { el: hero.querySelector('.hv-float-ai'),    depth: 0.015 },
      { el: hero.querySelector('.hv-float-notif'), depth: 0.025 },
      { el: hero.querySelector('.hv-float-stat'),  depth: 0.018 },
    ].filter(l => l.el);

    let mx = 0, my = 0, raf = null;

    hero.addEventListener('mousemove', e => {
      const r = hero.getBoundingClientRect();
      mx = (e.clientX - r.left  - r.width  / 2) / (r.width  / 2);
      my = (e.clientY - r.top   - r.height / 2) / (r.height / 2);
    });

    const tick = () => {
      layers.forEach(({ el, depth }) => {
        el.style.transform = `translate(${mx * depth * 60}px,${my * depth * 40}px)`;
      });
      raf = requestAnimationFrame(tick);
    };

    hero.addEventListener('mouseenter', () => { raf = requestAnimationFrame(tick); });
    hero.addEventListener('mouseleave', () => {
      cancelAnimationFrame(raf);
      layers.forEach(({ el }) => {
        el.style.transition = 'transform .8s ease';
        el.style.transform  = '';
        setTimeout(() => { el.style.transition = ''; }, 800);
      });
      mx = my = 0;
    });
  }

  /* ═══════════════════════════════════════════════════════
     7. STICKY HEADER ORANGE GLOW ON SCROLL
  ══════════════════════════════════════════════════════════ */
  function initStickyHeader() {
    const hdr = document.getElementById('agHeader');
    if (!hdr) return;

    const onScroll = () => {
      const scrolled = window.scrollY > 60;
      hdr.classList.toggle('ag-scrolled', scrolled);
      if (scrolled) {
        hdr.style.boxShadow = '0 4px 20px rgba(255,107,0,.07), 0 1px 0 #eef0f8';
      } else {
        hdr.style.boxShadow = '';
      }
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* ═══════════════════════════════════════════════════════
     8. SOCIAL ICON ANIMATED WIGGLE
  ══════════════════════════════════════════════════════════ */
  function initSocialIcons() {
    document.querySelectorAll('.ft-social, .ag-tb-social, .ag-drawer-social').forEach(icon => {
      icon.addEventListener('mouseenter', () => {
        if (prefersReduced) return;
        icon.style.animation = 'orIconWiggle .45s ease forwards';
        icon.addEventListener('animationend', () => { icon.style.animation = ''; }, { once: true });
      });
    });
  }

  /* ═══════════════════════════════════════════════════════
     9. CURSOR GLOW (desktop only)
  ══════════════════════════════════════════════════════════ */
  function initCursorGlow() {
    if (prefersReduced || window.matchMedia('(hover:none)').matches) return;

    const glow = document.createElement('div');
    glow.style.cssText = `
      width:32px;height:32px;border-radius:50%;pointer-events:none;
      position:fixed;z-index:99998;
      background:radial-gradient(circle,rgba(255,107,0,.28) 0%,transparent 70%);
      transition:transform .15s ease,width .2s,height .2s;
      transform:translate(-50%,-50%);mix-blend-mode:multiply;
    `;
    document.body.appendChild(glow);

    let gx = 0, gy = 0;
    document.addEventListener('mousemove', e => {
      gx = e.clientX; gy = e.clientY;
      glow.style.left = gx + 'px';
      glow.style.top  = gy + 'px';
    });

    /* Scale up on interactive elements */
    const interSel = 'a, button, .saas-svc-card, .saas-proj-card, .saas-why-card, input, textarea';
    document.querySelectorAll(interSel).forEach(el => {
      el.addEventListener('mouseenter', () => {
        glow.style.width  = '56px';
        glow.style.height = '56px';
        glow.style.background = 'radial-gradient(circle,rgba(255,107,0,.2) 0%,transparent 70%)';
      });
      el.addEventListener('mouseleave', () => {
        glow.style.width  = '32px';
        glow.style.height = '32px';
        glow.style.background = 'radial-gradient(circle,rgba(255,107,0,.28) 0%,transparent 70%)';
      });
    });
  }

  /* ═══════════════════════════════════════════════════════
     10. IMAGE ZOOM EFFECT on thumbnails
  ══════════════════════════════════════════════════════════ */
  function initImageZoom() {
    document.querySelectorAll(
      '.saas-proj-thumb, .saas-blog-img, .svc-card-thumb, .app-card-thumb, .prod-card-thumb'
    ).forEach(wrap => {
      const img = wrap.querySelector('img');
      if (!img) return;
      img.style.transition = 'transform .55s cubic-bezier(.4,0,.2,1)';

      wrap.addEventListener('mouseenter', () => { img.style.transform = 'scale(1.08)'; });
      wrap.addEventListener('mouseleave', () => { img.style.transform = ''; });
    });
  }

  /* ═══════════════════════════════════════════════════════
     11. MOBILE MENU ORANGE TRANSITION
  ══════════════════════════════════════════════════════════ */
  function initMobileMenu() {
    const drawer = document.getElementById('agDrawer');
    if (!drawer) return;

    const observer = new MutationObserver(mutations => {
      mutations.forEach(m => {
        if (m.attributeName === 'class') {
          const isOpen = drawer.classList.contains('ag-open');
          if (isOpen) {
            drawer.querySelectorAll('.ag-mob-nav > *, .ag-mob-acc').forEach((el, i) => {
              el.style.opacity    = '0';
              el.style.transform  = 'translateX(-20px)';
              el.style.transition = 'none';
              setTimeout(() => {
                el.style.transition = `.35s cubic-bezier(.16,1,.3,1) ${50 + i * 42}ms`;
                el.style.opacity    = '1';
                el.style.transform  = '';
              }, 10);
            });
          }
        }
      });
    });
    observer.observe(drawer, { attributes: true });
  }

  /* ═══════════════════════════════════════════════════════
     12. BACK-TO-TOP BUTTON
  ══════════════════════════════════════════════════════════ */
  function initBackToTop() {
    const btn = document.querySelector('.back-to-top');
    if (!btn) return;

    const toggle = () => btn.classList.toggle('visible', window.scrollY > 400);
    window.addEventListener('scroll', toggle, { passive: true });

    btn.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
    toggle();
  }

  /* ═══════════════════════════════════════════════════════
     13. TESTIMONIAL SLIDER auto-play enhancement
  ══════════════════════════════════════════════════════════ */
  function initTestiSlider() {
    const track = document.querySelector('.saas-testi-track');
    const dots  = document.querySelectorAll('.saas-testi-dot');
    const prev  = document.querySelector('.saas-testi-prev');
    const next  = document.querySelector('.saas-testi-next');
    if (!track || !dots.length) return;

    let cur = 0;
    const total = dots.length;

    const go = idx => {
      cur = ((idx % total) + total) % total;
      track.style.transform = `translateX(-${cur * 100}%)`;
      dots.forEach((d, i) => {
        d.classList.toggle('active', i === cur);
        d.style.width  = i === cur ? '22px' : '8px';
        d.style.background = i === cur ? '#FF6B00' : '#e7e9f0';
        d.style.boxShadow  = i === cur ? '0 0 8px rgba(255,107,0,.5)' : '';
      });
    };

    prev  && prev.addEventListener('click', () => go(cur - 1));
    next  && next.addEventListener('click', () => go(cur + 1));
    dots.forEach((d, i) => d.addEventListener('click', () => go(i)));

    let timer = setInterval(() => go(cur + 1), 5000);
    track.addEventListener('mouseenter', () => clearInterval(timer));
    track.addEventListener('mouseleave', () => { timer = setInterval(() => go(cur + 1), 5000); });

    go(0);
  }

  /* ═══════════════════════════════════════════════════════
     14. ORANGE GLOW ON INTERACTIVE ELEMENTS
  ══════════════════════════════════════════════════════════ */
  function initHoverGlow() {
    if (prefersReduced) return;

    /* CTA button animated glow */
    document.querySelectorAll('.saas-cta .btn-saas-primary, .saas-hero-btns .btn-saas-primary').forEach(btn => {
      btn.style.animation = 'orGlow 3s ease-in-out infinite';
    });

    /* Service filter active tab */
    document.querySelectorAll('.saas-filter-tab').forEach(tab => {
      tab.addEventListener('click', function() {
        document.querySelectorAll('.saas-filter-tab').forEach(t => t.style.animation = '');
        this.style.animation = 'orPulse .4s ease';
        this.addEventListener('animationend', () => { this.style.animation = ''; }, { once: true });
      });
    });
  }

  /* ═══════════════════════════════════════════════════════
     15. FAQ ACCORDION (orange-enhanced)
  ══════════════════════════════════════════════════════════ */
  function initFaqAccordion() {
    document.querySelectorAll('.saas-faq-q, .faq-question').forEach(q => {
      q.addEventListener('click', () => {
        const item = q.closest('.saas-faq-item, .faq-item');
        const ans  = item?.querySelector('.saas-faq-a, .faq-answer');
        const icon = q.querySelector('.saas-faq-q-icon, i');
        if (!ans) return;

        const isOpen = q.classList.contains('open');
        document.querySelectorAll('.saas-faq-q.open, .faq-question.open').forEach(oq => {
          oq.classList.remove('open');
          const oa = oq.closest('.saas-faq-item, .faq-item')?.querySelector('.saas-faq-a, .faq-answer');
          if (oa) oa.classList.remove('open');
        });

        if (!isOpen) {
          q.classList.add('open');
          ans.classList.add('open');
          /* Micro-bounce on icon */
          if (icon && !prefersReduced) {
            icon.style.animation = 'orBounce .4s ease';
            icon.addEventListener('animationend', () => { icon.style.animation = ''; }, { once: true });
          }
        }
      });
    });
  }

  /* ═══════════════════════════════════════════════════════
     16. SERVICE FILTER with orange highlight
  ══════════════════════════════════════════════════════════ */
  function initServiceFilter() {
    const tabs  = document.querySelectorAll('.saas-filter-tab');
    const cards = document.querySelectorAll('.saas-svc-card, .svc-card-item');
    if (!tabs.length) return;

    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        tabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        const cat = tab.dataset.cat;

        cards.forEach((card, i) => {
          const match = cat === 'all' || card.dataset.cat === cat;
          card.style.transition = `opacity .3s ${i * .03}s, transform .3s ${i * .03}s`;
          if (match) {
            card.style.opacity = '1';
            card.style.transform = '';
            card.style.pointerEvents = '';
          } else {
            card.style.opacity = '.2';
            card.style.transform = 'scale(.97)';
            card.style.pointerEvents = 'none';
          }
        });
      });
    });
  }

  /* ═══════════════════════════════════════════════════════
     17. INJECT @keyframes for orIconWiggle into DOM
  ══════════════════════════════════════════════════════════ */
  function injectKeyframes() {
    if (document.getElementById('or-keyframes')) return;
    const s = document.createElement('style');
    s.id = 'or-keyframes';
    s.textContent = `
      @keyframes orIconWiggle {
        0%,100%{transform:rotate(0deg)scale(1)}
        20%{transform:rotate(-14deg)scale(1.1)}
        40%{transform:rotate(10deg)scale(1.05)}
        60%{transform:rotate(-8deg)scale(1.08)}
        80%{transform:rotate(6deg)scale(1.02)}
      }
      @keyframes orBounce {
        0%,100%{transform:scale(1)}
        40%{transform:scale(1.2)rotate(-5deg)}
        70%{transform:scale(.95)rotate(3deg)}
      }
      @keyframes orGlow {
        0%,100%{box-shadow:0 6px 24px rgba(255,107,0,.35)}
        50%{box-shadow:0 8px 36px rgba(255,107,0,.6),0 0 60px rgba(255,69,0,.2)}
      }
      @keyframes orPulse {
        0%{transform:scale(1)}50%{transform:scale(1.04)}100%{transform:scale(1)}
      }
      @keyframes orRipple {
        to{transform:scale(4);opacity:0}
      }
      .or-ripple {
        position:absolute;border-radius:50%;
        background:rgba(255,255,255,.4);
        width:10px;height:10px;
        pointer-events:none;
        animation:orRipple .65s ease-out forwards;
        transform:scale(0);margin:-5px;z-index:10;
      }
    `;
    document.head.appendChild(s);
  }

  /* ═══════════════════════════════════════════════════════
     INIT ALL
  ══════════════════════════════════════════════════════════ */
  function init() {
    injectKeyframes();
    initScrollBar();
    initScrollReveal();
    initRipple();
    if (!prefersReduced) {
      initCardTilt();
      initParallax();
      initSocialIcons();
      initCursorGlow();
      initImageZoom();
      initMobileMenu();
      initHoverGlow();
    }
    initCounters();
    initStickyHeader();
    initBackToTop();
    initTestiSlider();
    initFaqAccordion();
    initServiceFilter();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
