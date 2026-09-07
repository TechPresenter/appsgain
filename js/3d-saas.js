/**
 * Appsgain — 3D SaaS Animation Engine
 * Three.js hero · Card tilt · Scroll reveals · Counters · Parallax
 */
(function () {
  'use strict';

  /* ═══════════════════════════════════════════════════════
     THREE.JS HERO BACKGROUND SCENE
     Tech node network + particles + floating wireframes
  ══════════════════════════════════════════════════════════ */
  function initHeroScene() {
    const canvas = document.getElementById('heroCanvas');
    if (!canvas || typeof THREE === 'undefined') return;

    const W = canvas.parentElement.offsetWidth;
    const H = canvas.parentElement.offsetHeight || window.innerHeight;
    canvas.width  = W;
    canvas.height = H;

    const scene    = new THREE.Scene();
    const camera   = new THREE.PerspectiveCamera(60, W / H, 0.1, 1000);
    camera.position.set(0, 0, 22);

    const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
    renderer.setSize(W, H);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.setClearColor(0x000000, 0);

    /* ── Lights ── */
    scene.add(new THREE.AmbientLight(0x334477, 0.5));
    const ptLight1 = new THREE.PointLight(0x3b82f6, 3, 40);
    ptLight1.position.set(-10, 8, 10);
    scene.add(ptLight1);
    const ptLight2 = new THREE.PointLight(0x8b5cf6, 2, 40);
    ptLight2.position.set(12, -6, 8);
    scene.add(ptLight2);
    const ptLight3 = new THREE.PointLight(0x06b6d4, 1.5, 35);
    ptLight3.position.set(0, -12, 5);
    scene.add(ptLight3);

    /* ── PARTICLE FIELD ── */
    const PARTICLE_COUNT = 1200;
    const pPositions = new Float32Array(PARTICLE_COUNT * 3);
    const pColors    = new Float32Array(PARTICLE_COUNT * 3);
    const palettes   = [
      new THREE.Color(0x3b82f6),
      new THREE.Color(0x8b5cf6),
      new THREE.Color(0x06b6d4),
      new THREE.Color(0x10b981),
      new THREE.Color(0xffffff),
    ];
    for (let i = 0; i < PARTICLE_COUNT; i++) {
      pPositions[i * 3]     = (Math.random() - 0.5) * 70;
      pPositions[i * 3 + 1] = (Math.random() - 0.5) * 50;
      pPositions[i * 3 + 2] = (Math.random() - 0.5) * 40;
      const c = palettes[Math.floor(Math.random() * palettes.length)];
      pColors[i * 3] = c.r; pColors[i * 3 + 1] = c.g; pColors[i * 3 + 2] = c.b;
    }
    const pGeo = new THREE.BufferGeometry();
    pGeo.setAttribute('position', new THREE.BufferAttribute(pPositions, 3));
    pGeo.setAttribute('color',    new THREE.BufferAttribute(pColors, 3));
    const pMat = new THREE.PointsMaterial({
      size: 0.12, vertexColors: true, transparent: true, opacity: 0.55,
      sizeAttenuation: true,
    });
    const particles = new THREE.Points(pGeo, pMat);
    scene.add(particles);

    /* ── NODE NETWORK ── */
    const NODE_COUNT = 55;
    const nodeData   = [];
    const nodeMeshes = [];
    const nodeColors = [0x3b82f6, 0x8b5cf6, 0x06b6d4, 0x10b981, 0x6366f1, 0xec4899];

    for (let i = 0; i < NODE_COUNT; i++) {
      const size    = Math.random() > 0.8 ? 0.32 : (Math.random() > 0.5 ? 0.18 : 0.1);
      const color   = nodeColors[Math.floor(Math.random() * nodeColors.length)];
      const geo     = new THREE.SphereGeometry(size, 10, 10);
      const mat     = new THREE.MeshPhongMaterial({
        color, emissive: color, emissiveIntensity: 0.4, transparent: true, opacity: 0.85,
      });
      const mesh    = new THREE.Mesh(geo, mat);
      const x = (Math.random() - 0.5) * 40;
      const y = (Math.random() - 0.5) * 25;
      const z = (Math.random() - 0.5) * 15;
      mesh.position.set(x, y, z);

      const vel = new THREE.Vector3(
        (Math.random() - 0.5) * 0.012,
        (Math.random() - 0.5) * 0.012,
        (Math.random() - 0.5) * 0.006
      );
      nodeData.push({ mesh, vel, originalPos: mesh.position.clone() });
      nodeMeshes.push(mesh);
      scene.add(mesh);
    }

    /* ── CONNECTIONS between nearby nodes ── */
    const lineMat = new THREE.LineBasicMaterial({
      color: 0x3b82f6, transparent: true, opacity: 0.18,
    });
    const CONN_THRESHOLD = 10;
    const connectionGroup = new THREE.Group();
    scene.add(connectionGroup);

    function buildConnections() {
      connectionGroup.clear();
      for (let i = 0; i < NODE_COUNT; i++) {
        for (let j = i + 1; j < NODE_COUNT; j++) {
          const dist = nodeMeshes[i].position.distanceTo(nodeMeshes[j].position);
          if (dist < CONN_THRESHOLD) {
            const points = [nodeMeshes[i].position, nodeMeshes[j].position];
            const geo    = new THREE.BufferGeometry().setFromPoints(points);
            const opacity = 0.22 * (1 - dist / CONN_THRESHOLD);
            const mat = new THREE.LineBasicMaterial({
              color: 0x3b82f6, transparent: true, opacity,
            });
            connectionGroup.add(new THREE.Line(geo, mat));
          }
        }
      }
    }

    /* ── FLOATING WIREFRAME SHAPES ── */
    const wfShapes = [];
    function addWireframe(geo, color, pos, rotSpeed) {
      const mat = new THREE.MeshBasicMaterial({ color, wireframe: true, transparent: true, opacity: .14 });
      const mesh = new THREE.Mesh(geo, mat);
      mesh.position.copy(pos);
      mesh.userData = { rotSpeed };
      scene.add(mesh);
      wfShapes.push(mesh);
    }
    addWireframe(new THREE.IcosahedronGeometry(2.5, 1),  0x3b82f6, new THREE.Vector3(-14, 7, -5),  { x:.003, y:.007 });
    addWireframe(new THREE.TorusGeometry(2, 0.6, 12, 24), 0x8b5cf6, new THREE.Vector3(15, -6, -8),  { x:.005, y:.003 });
    addWireframe(new THREE.OctahedronGeometry(1.8),       0x06b6d4, new THREE.Vector3(8, 9, -6),   { x:.006, y:.004 });
    addWireframe(new THREE.IcosahedronGeometry(1.5, 0),   0xec4899, new THREE.Vector3(-12, -8, -4), { x:.004, y:.008 });
    addWireframe(new THREE.TorusKnotGeometry(1.4, .4, 60, 8), 0x10b981, new THREE.Vector3(0, -12, -7), { x:.003, y:.006 });

    /* ── MOUSE INTERACTION ── */
    let mouseX = 0, mouseY = 0;
    let targetX = 0, targetY = 0;
    const section = canvas.closest('.saas-hero') || document.body;
    section.addEventListener('mousemove', e => {
      const rect = section.getBoundingClientRect();
      mouseX = ((e.clientX - rect.left) / rect.width  - 0.5) * 2;
      mouseY = ((e.clientY - rect.top)  / rect.height - 0.5) * 2;
    });

    /* ── ANIMATION LOOP ── */
    let frameCount = 0;
    const clock = new THREE.Clock();
    function animate() {
      requestAnimationFrame(animate);
      const elapsed = clock.getElapsedTime();
      frameCount++;

      /* Smooth camera follow */
      targetX += (mouseX * 2.5 - targetX) * 0.04;
      targetY += (-mouseY * 1.8 - targetY) * 0.04;
      camera.position.x = targetX;
      camera.position.y = targetY;
      camera.lookAt(0, 0, 0);

      /* Rotate particles slowly */
      particles.rotation.y = elapsed * 0.025;
      particles.rotation.x = elapsed * 0.01;

      /* Float nodes */
      nodeData.forEach(({ mesh, vel, originalPos }) => {
        mesh.position.x += vel.x;
        mesh.position.y += vel.y;
        mesh.position.z += vel.z;
        const dist = mesh.position.distanceTo(originalPos);
        if (dist > 3) {
          vel.x *= -1; vel.y *= -1; vel.z *= -1;
        }
      });

      /* Rebuild connections every 20 frames (performance) */
      if (frameCount % 20 === 0) buildConnections();

      /* Rotate wireframes */
      wfShapes.forEach(m => {
        m.rotation.x += m.userData.rotSpeed.x;
        m.rotation.y += m.userData.rotSpeed.y;
      });

      /* Pulsing node scale */
      nodeMeshes.forEach((m, i) => {
        const s = 1 + 0.08 * Math.sin(elapsed * 1.5 + i * 0.8);
        m.scale.setScalar(s);
      });

      renderer.render(scene, camera);
    }

    buildConnections();
    animate();

    /* Resize handler */
    window.addEventListener('resize', () => {
      const W2 = canvas.parentElement.offsetWidth;
      const H2 = canvas.parentElement.offsetHeight || window.innerHeight;
      canvas.width = W2; canvas.height = H2;
      camera.aspect = W2 / H2;
      camera.updateProjectionMatrix();
      renderer.setSize(W2, H2);
    });
  }

  /* ═══════════════════════════════════════════════════════
     CARD 3D TILT EFFECT (pure JS, no dependency)
  ══════════════════════════════════════════════════════════ */
  function initCardTilt() {
    const tiltCards = document.querySelectorAll('[data-tilt]');
    tiltCards.forEach(card => {
      const strength = parseFloat(card.dataset.tiltStrength || '12');
      const glare    = card.querySelector('.card-glare');

      card.addEventListener('mousemove', e => {
        const rect    = card.getBoundingClientRect();
        const x       = e.clientX - rect.left;
        const y       = e.clientY - rect.top;
        const cx      = rect.width  / 2;
        const cy      = rect.height / 2;
        const rotY    =  ((x - cx) / cx) * strength;
        const rotX    = -((y - cy) / cy) * strength;
        const dist    = Math.sqrt((x - cx) ** 2 + (y - cy) ** 2);
        const maxDist = Math.sqrt(cx ** 2 + cy ** 2);
        const glareX  = (x / rect.width)  * 100;
        const glareY  = (y / rect.height) * 100;

        card.style.transform  = `perspective(900px) rotateX(${rotX}deg) rotateY(${rotY}deg) translateZ(8px)`;
        card.style.boxShadow  = `${-rotY * 2}px ${rotX * 2}px 30px rgba(106,0,255,.12)`;
        card.style.transition = 'none';

        if (glare) {
          glare.style.background = `radial-gradient(circle at ${glareX}% ${glareY}%, rgba(255,255,255,.15) 0%, transparent 70%)`;
          glare.style.opacity = (dist / maxDist) * 0.5;
        }
      });

      card.addEventListener('mouseleave', () => {
        card.style.transition = 'transform .6s cubic-bezier(.4,0,.2,1), box-shadow .6s';
        card.style.transform  = 'perspective(900px) rotateX(0) rotateY(0) translateZ(0)';
        card.style.boxShadow  = '';
        if (glare) glare.style.opacity = 0;
      });
    });
  }

  /* ═══════════════════════════════════════════════════════
     SCROLL REVEAL (Intersection Observer)
  ══════════════════════════════════════════════════════════ */
  function initScrollReveal() {
    const revealEls = document.querySelectorAll('.saas-reveal, .saas-reveal-left, .saas-reveal-right');
    if (!revealEls.length) return;

    const obs = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          obs.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    revealEls.forEach(el => obs.observe(el));

    /* Stat cards: mark visible too */
    const statCards = document.querySelectorAll('.saas-stat-card');
    const statObs = new IntersectionObserver(entries => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          e.target.classList.add('is-visible');
          statObs.unobserve(e.target);
        }
      });
    }, { threshold: 0.5 });
    statCards.forEach(c => statObs.observe(c));
  }

  /* ═══════════════════════════════════════════════════════
     ANIMATED COUNTERS
  ══════════════════════════════════════════════════════════ */
  function initCounters() {
    const counters = document.querySelectorAll('.saas-counter');
    if (!counters.length) return;

    const obs = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        const el     = entry.target;
        const target = parseInt(el.dataset.target, 10);
        const suffix = el.dataset.suffix || '';
        const dur    = 2200;
        const start  = performance.now();

        function step(now) {
          const t   = Math.min((now - start) / dur, 1);
          const ease = 1 - Math.pow(1 - t, 4);
          el.textContent = Math.round(ease * target) + suffix;
          if (t < 1) requestAnimationFrame(step);
          else el.textContent = target + suffix;
        }
        requestAnimationFrame(step);
        obs.unobserve(el);
      });
    }, { threshold: 0.5 });

    counters.forEach(c => obs.observe(c));
  }

  /* ═══════════════════════════════════════════════════════
     PARALLAX on hero visual elements
  ══════════════════════════════════════════════════════════ */
  function initParallax() {
    const hero = document.querySelector('.saas-hero');
    if (!hero) return;

    const layers = [
      { el: document.querySelector('.hv-float-code'),  depth: 0.025 },
      { el: document.querySelector('.hv-float-ai'),    depth: 0.018 },
      { el: document.querySelector('.hv-float-notif'), depth: 0.032 },
      { el: document.querySelector('.hv-float-stat'),  depth: 0.022 },
    ].filter(l => l.el !== null);

    let mx = 0, my = 0;
    hero.addEventListener('mousemove', e => {
      const r  = hero.getBoundingClientRect();
      mx = (e.clientX - r.left  - r.width  / 2) / (r.width  / 2);
      my = (e.clientY - r.top   - r.height / 2) / (r.height / 2);
    });

    let rAF;
    function updateParallax() {
      layers.forEach(({ el, depth }) => {
        const tx = mx * depth * 60;
        const ty = my * depth * 40;
        el.style.transform = `translate(${tx}px,${ty}px)`;
      });
      rAF = requestAnimationFrame(updateParallax);
    }

    hero.addEventListener('mouseenter', () => { rAF = requestAnimationFrame(updateParallax); });
    hero.addEventListener('mouseleave', () => {
      cancelAnimationFrame(rAF);
      layers.forEach(({ el }) => {
        el.style.transition = 'transform 1s ease';
        el.style.transform = '';
        setTimeout(() => { el.style.transition = ''; }, 1000);
      });
      mx = 0; my = 0;
    });
  }

  /* ═══════════════════════════════════════════════════════
     SERVICE FILTER TABS
  ══════════════════════════════════════════════════════════ */
  function initServiceFilter() {
    const tabs  = document.querySelectorAll('.saas-filter-tab');
    const cards = document.querySelectorAll('.saas-svc-card');
    if (!tabs.length) return;

    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        tabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        const cat = tab.dataset.cat;

        cards.forEach(card => {
          const match = cat === 'all' || card.dataset.cat === cat;
          card.style.transition = 'opacity .3s, transform .3s';
          if (match) {
            card.style.opacity = '1';
            card.style.transform = '';
            card.style.pointerEvents = '';
          } else {
            card.style.opacity = '.25';
            card.style.transform = 'scale(.97)';
            card.style.pointerEvents = 'none';
          }
        });
      });
    });
  }

  /* ═══════════════════════════════════════════════════════
     TECH STACK FILTER
  ══════════════════════════════════════════════════════════ */
  function initTechFilter() {
    const btns = document.querySelectorAll('.saas-tech-filter-btn');
    const cats = document.querySelectorAll('.saas-tech-cat');
    if (!btns.length) return;

    btns.forEach(btn => {
      btn.addEventListener('click', () => {
        btns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const f = btn.dataset.filter;
        cats.forEach(c => {
          const show = f === 'all' || c.dataset.cat === f;
          c.style.transition = 'opacity .3s, transform .3s';
          c.style.opacity    = show ? '1' : '.2';
          c.style.transform  = show ? '' : 'scale(.95)';
          c.style.pointerEvents = show ? '' : 'none';
        });
      });
    });
  }

  /* ═══════════════════════════════════════════════════════
     FAQ ACCORDION
  ══════════════════════════════════════════════════════════ */
  function initFaqAccordion() {
    document.querySelectorAll('.saas-faq-q').forEach(q => {
      q.addEventListener('click', () => {
        const item = q.closest('.saas-faq-item');
        const ans  = item.querySelector('.saas-faq-a');
        const isOpen = q.classList.contains('open');

        /* close all */
        document.querySelectorAll('.saas-faq-q.open').forEach(oq => {
          oq.classList.remove('open');
          oq.closest('.saas-faq-item').querySelector('.saas-faq-a').classList.remove('open');
        });

        if (!isOpen) {
          q.classList.add('open');
          ans.classList.add('open');
        }
      });
    });
  }

  /* ═══════════════════════════════════════════════════════
     TESTIMONIAL SLIDER
  ══════════════════════════════════════════════════════════ */
  function initTestiSlider() {
    const track = document.querySelector('.saas-testi-track');
    const dots  = document.querySelectorAll('.saas-testi-dot');
    const prev  = document.querySelector('.saas-testi-prev');
    const next  = document.querySelector('.saas-testi-next');
    if (!track || !dots.length) return;

    let current = 0;
    const total = dots.length;

    function go(idx) {
      current = (idx + total) % total;
      track.style.transform = `translateX(-${current * 100}%)`;
      dots.forEach((d, i) => d.classList.toggle('active', i === current));
    }

    prev && prev.addEventListener('click', () => go(current - 1));
    next && next.addEventListener('click', () => go(current + 1));
    dots.forEach((d, i) => d.addEventListener('click', () => go(i)));

    /* Auto-advance */
    let timer = setInterval(() => go(current + 1), 5000);
    track.addEventListener('mouseenter', () => clearInterval(timer));
    track.addEventListener('mouseleave', () => {
      timer = setInterval(() => go(current + 1), 5000);
    });
  }

  /* ═══════════════════════════════════════════════════════
     MICRO-INTERACTIONS: button ripple
  ══════════════════════════════════════════════════════════ */
  function initRipple() {
    document.querySelectorAll('.btn-saas-primary, .btn-saas-secondary').forEach(btn => {
      btn.addEventListener('click', e => {
        const rect = btn.getBoundingClientRect();
        const x    = e.clientX - rect.left;
        const y    = e.clientY - rect.top;
        const rpl  = document.createElement('span');
        rpl.style.cssText = `
          position:absolute;width:6px;height:6px;border-radius:50%;
          background:rgba(255,255,255,.5);pointer-events:none;
          left:${x - 3}px;top:${y - 3}px;z-index:10;
          animation:ripple .6s ease-out forwards;
        `;
        btn.style.position = 'relative';
        btn.style.overflow = 'hidden';
        btn.appendChild(rpl);
        setTimeout(() => rpl.remove(), 600);
      });
    });

    /* Inject ripple keyframe */
    if (!document.getElementById('rippleStyle')) {
      const s = document.createElement('style');
      s.id = 'rippleStyle';
      s.textContent = `@keyframes ripple{to{transform:scale(24);opacity:0}}`;
      document.head.appendChild(s);
    }
  }

  /* ═══════════════════════════════════════════════════════
     CURSOR GLOW (desktop only)
  ══════════════════════════════════════════════════════════ */
  function initCursorGlow() {
    if (window.matchMedia('(hover:none)').matches) return;
    const glow = document.createElement('div');
    glow.style.cssText = `
      width:28px;height:28px;border-radius:50%;pointer-events:none;
      position:fixed;z-index:99999;
      background:radial-gradient(circle,rgba(106,0,255,.35) 0%,transparent 70%);
      transition:transform .12s ease;
      transform:translate(-50%,-50%);
    `;
    document.body.appendChild(glow);
    document.addEventListener('mousemove', e => {
      glow.style.left = e.clientX + 'px';
      glow.style.top  = e.clientY + 'px';
    });
    /* Scale up over interactive elements */
    document.querySelectorAll('a,button,.saas-svc-card,.saas-why-card').forEach(el => {
      el.addEventListener('mouseenter', () => { glow.style.transform = 'translate(-50%,-50%) scale(3)'; });
      el.addEventListener('mouseleave', () => { glow.style.transform = 'translate(-50%,-50%) scale(1)'; });
    });
  }

  /* ═══════════════════════════════════════════════════════
     INIT ALL
  ══════════════════════════════════════════════════════════ */
  /* ═══════════════════════════════════════════════════════
     FORCE-REVEAL legacy .reveal / .reveal-group elements
     Uses setProperty('important') to beat any CSS rule
  ══════════════════════════════════════════════════════════ */
  function forceRevealLegacy() {
    /* 1. Add .visible to all legacy reveal containers */
    document.querySelectorAll(
      '.reveal, .reveal-left, .reveal-right, .reveal-group'
    ).forEach(el => el.classList.add('visible'));

    /* 2. Force inline important on reveal-group children
          (setProperty 'important' beats even CSS !important) */
    document.querySelectorAll('.reveal-group > *').forEach(el => {
      el.style.setProperty('opacity',   '1',    'important');
      el.style.setProperty('transform', 'none', 'important');
      el.style.setProperty('transition','transform .3s ease, box-shadow .3s ease','important');
    });

    /* 3. Force inline important on single .reveal elements */
    document.querySelectorAll('.reveal, .reveal-left, .reveal-right').forEach(el => {
      el.style.setProperty('opacity',   '1',    'important');
      el.style.setProperty('transform', 'none', 'important');
    });

    /* 4. page-load classes */
    document.querySelectorAll('.page-load-slide-up, .page-load-scale').forEach(el => {
      el.style.setProperty('opacity',   '1',    'important');
      el.style.setProperty('transform', 'none', 'important');
      el.style.setProperty('animation', 'none', 'important');
    });
  }

  function init() {
    /* Force reveal legacy scroll-reveal elements immediately */
    forceRevealLegacy();

    /* Wait for Three.js to load if using async */
    if (typeof THREE === 'undefined') {
      const script = document.createElement('script');
      script.src = 'https://cdn.jsdelivr.net/npm/three@0.160.0/build/three.min.js';
      script.onload = () => initHeroScene();
      document.head.appendChild(script);
    } else {
      initHeroScene();
    }

    initCardTilt();
    initScrollReveal();
    initCounters();
    initParallax();
    initServiceFilter();
    initTechFilter();
    initFaqAccordion();
    initTestiSlider();
    initRipple();
    initCursorGlow();
    initTypingAnimation();
    initHeroSlider();
  }

  /* ═══════════════════════════════════════════════════════
     TYPING / TYPEWRITER ANIMATION
     Reads data-items from the target element,
     splits by "•", cycles through with type/delete effect
  ══════════════════════════════════════════════════════════ */
  function initTypingAnimation() {
    const el = document.getElementById('heroTypingText');
    if (!el) return;

    const raw   = (el.dataset.items || el.textContent || '').trim();
    const items = raw.split('•').map(s => s.trim()).filter(Boolean);
    if (!items.length) return;

    let idx = 0, pos = 0, deleting = false;

    el.textContent = '';

    /* Add blinking caret */
    const caret = document.createElement('span');
    caret.style.cssText = 'display:inline-block;width:2px;height:1em;background:currentColor;margin-left:2px;vertical-align:text-bottom;animation:saasBlinkCaret .75s step-end infinite;opacity:.8;border-radius:1px;';
    el.after(caret);

    function tick() {
      const word  = items[idx];
      const speed = deleting ? 35 : 75;

      if (!deleting) {
        pos++;
        el.textContent = word.slice(0, pos);
        if (pos === word.length) {
          /* Pause at end, then start deleting */
          return setTimeout(() => { deleting = true; tick(); }, 2000);
        }
      } else {
        pos--;
        el.textContent = word.slice(0, pos);
        if (pos === 0) {
          deleting = false;
          idx = (idx + 1) % items.length;
          /* Brief pause before typing next */
          return setTimeout(tick, 450);
        }
      }
      setTimeout(tick, speed);
    }

    tick();
  }

  /* ═══════════════════════════════════════════════════════
     HERO SLIDE CAROUSEL
     Auto-advances through hero slides every 6 s
  ══════════════════════════════════════════════════════════ */
  function initHeroSlider() {
    const slides = document.querySelectorAll('.hero-slide');
    const dots   = document.querySelectorAll('.hero-slide-dot');
    if (slides.length <= 1) return;

    let current = 0;
    const total = slides.length;

    function goTo(n) {
      slides[current].classList.remove('hs-active');
      dots[current] && dots[current].classList.remove('active');
      current = (n + total) % total;
      slides[current].classList.add('hs-active');
      dots[current] && dots[current].classList.add('active');

      /* Re-trigger typing animation for new slide's badge_text */
      const newBadge = slides[current].dataset.badge;
      const typingEl = document.getElementById('heroTypingText');
      if (typingEl && newBadge) {
        typingEl.dataset.items = newBadge;
        typingEl.textContent   = '';
      }
    }

    /* Dot clicks */
    dots.forEach((d, i) => d.addEventListener('click', () => {
      clearInterval(timer);
      goTo(i);
      timer = setInterval(() => goTo(current + 1), 6000);
    }));

    /* Auto-advance */
    let timer = setInterval(() => goTo(current + 1), 6000);

    /* Pause on hover */
    const hero = document.querySelector('.saas-hero');
    if (hero) {
      hero.addEventListener('mouseenter', () => clearInterval(timer));
      hero.addEventListener('mouseleave', () => {
        timer = setInterval(() => goTo(current + 1), 6000);
      });
    }

    /* Init first slide */
    slides[0].classList.add('hs-active');
    dots[0] && dots[0].classList.add('active');
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
