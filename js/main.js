// ==========================================
// APPSGAIN TECHNOLOGIES – Main JavaScript
// ==========================================

document.addEventListener('DOMContentLoaded', function () {

  // ---- Mobile Drawer ----
  const hamburger    = document.querySelector('.hamburger');
  const drawer       = document.getElementById('mobileDrawer');
  const drawerClose  = document.getElementById('drawerClose');
  const drawerOverlay = document.getElementById('drawerOverlay');

  function openDrawer() {
    if (!drawer) return;
    drawer.classList.add('open');
    drawerOverlay && drawerOverlay.classList.add('active');
    hamburger && hamburger.classList.add('open');
    document.body.style.overflow = 'hidden';

    // Stagger slide-in links beautifully
    const drawerLinks = drawer.querySelectorAll('.drawer-menu > li');
    drawerLinks.forEach((link, idx) => {
      link.style.opacity = '0';
      link.style.transform = 'translateX(24px)';
      link.style.transition = 'opacity 0.4s cubic-bezier(0.16, 1, 0.3, 1), transform 0.4s cubic-bezier(0.16, 1, 0.3, 1)';
      setTimeout(() => {
        link.style.opacity = '1';
        link.style.transform = 'translateX(0)';
      }, 120 + idx * 60);
    });
  }
  function closeDrawer() {
    if (!drawer) return;
    drawer.classList.remove('open');
    drawerOverlay && drawerOverlay.classList.remove('active');
    hamburger && hamburger.classList.remove('open');
    document.body.style.overflow = '';

    // Reset styles on drawer links
    const drawerLinks = drawer.querySelectorAll('.drawer-menu > li');
    drawerLinks.forEach((link) => {
      link.style.opacity = '';
      link.style.transform = '';
      link.style.transition = '';
    });
  }
  hamburger     && hamburger.addEventListener('click', openDrawer);
  drawerClose   && drawerClose.addEventListener('click', closeDrawer);
  drawerOverlay && drawerOverlay.addEventListener('click', closeDrawer);
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDrawer(); });

  // Drawer sub-menu accordion
  document.querySelectorAll('.drawer-toggle-btn').forEach(btn => {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      const sub = this.closest('.drawer-parent').querySelector('.drawer-sub');
      if (!sub) return;
      const isOpen = sub.classList.contains('open');
      document.querySelectorAll('.drawer-sub').forEach(s => s.classList.remove('open'));
      document.querySelectorAll('.drawer-toggle-btn').forEach(b => b.classList.remove('open'));
      if (!isOpen) {
        sub.classList.add('open');
        this.classList.add('open');
      }
    });
  });

  // ---- Sticky Header glass on scroll ----
  const header = document.querySelector('.header');
  if (header) {
    const onScroll = () => header.classList.toggle('scrolled', window.scrollY > 60);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  // ---- Back To Top ----
  const backBtn = document.querySelector('.back-to-top');
  if (backBtn) {
    window.addEventListener('scroll', () => backBtn.classList.toggle('show', window.scrollY > 400), { passive: true });
    backBtn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
  }

  // ---- Cookie consent (see agCookie block in the footer) ----

  // ---- FAQ Accordion ----
  document.querySelectorAll('.faq-question').forEach(q => {
    q.addEventListener('click', () => {
      const item   = q.closest('.faq-item');
      const answer = item && item.querySelector('.faq-answer');
      const isOpen = q.classList.contains('active');
      document.querySelectorAll('.faq-question').forEach(oq => {
        oq.classList.remove('active');
        const oa = oq.closest('.faq-item') && oq.closest('.faq-item').querySelector('.faq-answer');
        if (oa) { oa.style.maxHeight = '0'; oa.classList.remove('open'); }
      });
      if (!isOpen && answer) {
        q.classList.add('active');
        answer.classList.add('open');
        answer.style.maxHeight = answer.scrollHeight + 'px';
      }
    });
  });

  // ---- Counter Animation ----
  function animateCounter(el) {
    const target = parseInt(el.getAttribute('data-target') || el.textContent, 10);
    if (isNaN(target)) return;
    const suffix = el.getAttribute('data-suffix') || '';
    const prefix = el.getAttribute('data-prefix') || '';
    const duration = 1800;
    const start = performance.now();
    el.textContent = prefix + '0' + suffix;
    function step(now) {
      const elapsed = now - start;
      const progress = Math.min(elapsed / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 3);
      el.textContent = prefix + Math.round(target * eased) + suffix;
      if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }

  // ---- Scroll Reveal (IntersectionObserver) ----
  const revealEls = document.querySelectorAll('.reveal, .reveal-left, .reveal-right');
  const revealGroups = document.querySelectorAll('.reveal-group');
  const counterEls = document.querySelectorAll('.counter');
  const progressBars = document.querySelectorAll('.progress-bar-fill');

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12 });

  revealEls.forEach(el => observer.observe(el));
  revealGroups.forEach(el => observer.observe(el));

  // Counter observer
  const counterObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        animateCounter(entry.target);
        counterObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.4 });
  counterEls.forEach(el => counterObserver.observe(el));

  // Progress bar observer
  const progressObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const width = entry.target.getAttribute('data-width') || '0';
        entry.target.style.width = width + '%';
        progressObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.4 });
  progressBars.forEach(el => progressObserver.observe(el));

  // ---- Testimonial Slider ----
  const sliderTrack = document.querySelector('.slider-track');
  const sliderDots  = document.querySelectorAll('.slider-dot');
  const prevBtn     = document.querySelector('.slider-btn.prev');
  const nextBtn     = document.querySelector('.slider-btn.next');

  if (sliderTrack) {
    const cards = sliderTrack.querySelectorAll('.testimonial-card');
    const total = cards.length;
    let current = 0;
    let autoTimer;

    function goTo(idx) {
      current = (idx + total) % total;
      sliderTrack.style.transform = `translateX(-${current * 100}%)`;
      sliderDots.forEach((d, i) => d.classList.toggle('active', i === current));
      
      // Update active card class for premium visual focus transitions
      cards.forEach((card, i) => {
        card.classList.toggle('active-slide', i === current);
      });
    }

    function startAuto() {
      clearInterval(autoTimer);
      autoTimer = setInterval(() => goTo(current + 1), 4500);
    }

    prevBtn && prevBtn.addEventListener('click', () => { goTo(current - 1); startAuto(); });
    nextBtn && nextBtn.addEventListener('click', () => { goTo(current + 1); startAuto(); });
    sliderDots.forEach((dot, i) => dot.addEventListener('click', () => { goTo(i); startAuto(); }));

    goTo(0);
    startAuto();

    // Touch/swipe support
    let touchStartX = 0;
    sliderTrack.addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; }, { passive: true });
    sliderTrack.addEventListener('touchend', e => {
      const diff = touchStartX - e.changedTouches[0].clientX;
      if (Math.abs(diff) > 40) { diff > 0 ? goTo(current + 1) : goTo(current - 1); startAuto(); }
    }, { passive: true });
  }

  // ---- Services Tab Filter (index.html) ----
  const tabBtns = document.querySelectorAll('.service-tab-btn');
  const serviceCards = document.querySelectorAll('.service-card[data-cat]');

  if (tabBtns.length) {
    tabBtns.forEach(btn => {
      btn.addEventListener('click', function () {
        tabBtns.forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const cat = this.getAttribute('data-cat');
        serviceCards.forEach(card => {
          if (cat === 'all' || card.getAttribute('data-cat') === cat) {
            card.style.display = '';
            card.style.opacity = '0';
            card.style.transform = 'translateY(16px)';
            requestAnimationFrame(() => {
              card.style.transition = 'opacity 0.35s ease, transform 0.35s ease';
              card.style.opacity = '1';
              card.style.transform = 'none';
            });
          } else {
            card.style.display = 'none';
          }
        });
      });
    });
  }

  const techFilterBtns = document.querySelectorAll('.tech-filter-btn');
  const techCategories = document.querySelectorAll('.tech-cat[data-tech-cat]');

  if (techFilterBtns.length && techCategories.length) {
    techFilterBtns.forEach(btn => {
      btn.addEventListener('click', function () {
        techFilterBtns.forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const filter = this.getAttribute('data-filter');
        techCategories.forEach(card => {
          if (filter === 'all' || card.getAttribute('data-tech-cat') === filter) {
            card.style.display = '';
            card.style.opacity = '0';
            card.style.transform = 'translateY(16px)';
            requestAnimationFrame(() => {
              card.style.transition = 'opacity 0.35s ease, transform 0.35s ease';
              card.style.opacity = '1';
              card.style.transform = 'none';
            });
          } else {
            card.style.display = 'none';
          }
        });
      });
    });
  }

  // ---- Projects Filter ----
  const filterBtns = document.querySelectorAll('.filter-btn:not(.blog-filter-btn)');
  const projectCards = document.querySelectorAll('.project-card[data-filter]');

  if (filterBtns.length) {
    filterBtns.forEach(btn => {
      btn.addEventListener('click', function () {
        filterBtns.forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const filter = this.getAttribute('data-filter');
        projectCards.forEach(card => {
          if (filter === 'all' || card.getAttribute('data-filter') === filter) {
            card.style.display = '';
            card.style.opacity = '0';
            card.style.transform = 'translateY(14px)';
            requestAnimationFrame(() => {
              card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
              card.style.opacity = '1';
              card.style.transform = 'none';
            });
          } else {
            card.style.display = 'none';
          }
        });
      });
    });
  }

  // ---- Interactive Contact Form Validation ----
  const contactForm = document.querySelector('.contact-form-el');
  if (contactForm) {
    const inputs = contactForm.querySelectorAll('input[required], textarea[required], input[type="email"], input[type="tel"]');

    function getErrorMessage(input) {
      const val = input.value.trim();
      if (input.required && !val) {
        return `${getLabelText(input)} is required.`;
      }
      if (input.type === 'email' && val) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(val)) return 'Please enter a valid email address.';
      }
      if (input.type === 'tel' && val) {
        const phoneRegex = /^\+?[0-9\s-]{10,15}$/;
        if (!phoneRegex.test(val)) return 'Please enter a valid phone number (10-15 digits).';
      }
      if (input.tagName === 'TEXTAREA' && val.length < 15) {
        return 'Please describe your project in at least 15 characters.';
      }
      return '';
    }

    function getLabelText(input) {
      const grp = input.closest('.form-group');
      const lbl = grp ? grp.querySelector('label') : null;
      if (lbl) {
        return lbl.textContent.replace('*', '').trim();
      }
      return input.placeholder || 'This field';
    }

    function validateField(input) {
      const grp = input.closest('.form-group');
      if (!grp) return true;

      const errorMsg = getErrorMessage(input);
      let hint = grp.querySelector('.field-error-hint');

      if (errorMsg) {
        grp.classList.remove('field-success');
        grp.classList.add('field-error');
        if (!hint) {
          hint = document.createElement('span');
          hint.className = 'field-error-hint';
          grp.appendChild(hint);
        }
        hint.textContent = errorMsg;
        return false;
      } else {
        grp.classList.remove('field-error');
        if (input.value.trim() !== '') {
          grp.classList.add('field-success');
        } else {
          grp.classList.remove('field-success');
        }
        if (hint) {
          hint.remove();
        }
        return true;
      }
    }

    inputs.forEach(input => {
      input.addEventListener('blur', () => {
        validateField(input);
      });

      input.addEventListener('input', () => {
        const grp = input.closest('.form-group');
        if (grp && grp.classList.contains('field-error')) {
          validateField(input);
        }
      });
    });

    contactForm.addEventListener('submit', function (e) {
      e.preventDefault();
      
      let isFormValid = true;
      let firstInvalidInput = null;

      inputs.forEach(input => {
        const isValid = validateField(input);
        if (!isValid) {
          isFormValid = false;
          if (!firstInvalidInput) {
            firstInvalidInput = input;
          }
        }
      });

      if (!isFormValid) {
        if (firstInvalidInput) {
          firstInvalidInput.focus();
        }
        return;
      }

      const btn = contactForm.querySelector('.form-submit-btn');
      const originalText = btn ? btn.innerHTML : '';
      if (btn) {
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        btn.disabled = true;
      }

      setTimeout(() => {
        if (btn) {
          btn.innerHTML = '<i class="fas fa-check"></i> Sent Successfully!';
          btn.style.background = 'linear-gradient(135deg, var(--emerald), var(--emerald-light))';
        }
        const successMsg = contactForm.querySelector('.form-success');
        if (successMsg) successMsg.classList.add('show');
        
        inputs.forEach(input => {
          const grp = input.closest('.form-group');
          if (grp) grp.classList.remove('field-success');
        });

        setTimeout(() => {
          contactForm.reset();
          if (btn) {
            btn.innerHTML = originalText;
            btn.disabled = false;
            btn.style.background = '';
          }
          if (successMsg) successMsg.classList.remove('show');
        }, 4000);
      }, 1400);
    });
  }

  // ---- Newsletter Form ----
  document.querySelectorAll('.newsletter-form').forEach(form => {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      const input = form.querySelector('input[type="email"]');
      const btn   = form.querySelector('button');
      if (!input || !input.value.trim()) return;
      const orig = btn ? btn.innerHTML : '';
      if (btn) btn.innerHTML = '<i class="fas fa-check"></i>';
      setTimeout(() => {
        if (input) input.value = '';
        if (btn) btn.innerHTML = orig;
      }, 2500);
    });
  });

  // ---- Services sticky sub-nav active state ----
  const subNavLinks = document.querySelectorAll('.services-sub-nav a[href^="#"]');
  if (subNavLinks.length) {
    const subSections = Array.from(subNavLinks).map(link => {
      const id = link.getAttribute('href').slice(1);
      return document.getElementById(id);
    }).filter(Boolean);

    const subObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const id = entry.target.id;
          subNavLinks.forEach(link => {
            link.classList.toggle('active', link.getAttribute('href') === '#' + id);
          });
        }
      });
    }, { rootMargin: '-30% 0px -60% 0px' });

    subSections.forEach(s => subObserver.observe(s));
  }

  // ---- Particles Canvas ----
  const canvas = document.getElementById('particlesCanvas');
  if (canvas) {
    const ctx = canvas.getContext('2d');
    let W, H, particles = [];

    function resize() {
      W = canvas.width  = canvas.offsetWidth;
      H = canvas.height = canvas.offsetHeight;
    }
    resize();
    window.addEventListener('resize', resize, { passive: true });

    const colors = ['rgba(106,0,255,0.6)', 'rgba(85,0,204,0.5)', 'rgba(106,0,255,0.5)', 'rgba(255,255,255,0.35)'];

    function createParticle() {
      return {
        x: Math.random() * W,
        y: Math.random() * H,
        r: Math.random() * 2 + 0.5,
        dx: (Math.random() - 0.5) * 0.6,
        dy: (Math.random() - 0.5) * 0.6,
        color: colors[Math.floor(Math.random() * colors.length)],
        alpha: Math.random() * 0.6 + 0.2
      };
    }

    for (let i = 0; i < 70; i++) particles.push(createParticle());

    function drawParticles() {
      ctx.clearRect(0, 0, W, H);
      particles.forEach(p => {
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx.fillStyle = p.color;
        ctx.globalAlpha = p.alpha;
        ctx.fill();
        p.x += p.dx; p.y += p.dy;
        if (p.x < 0) p.x = W; if (p.x > W) p.x = 0;
        if (p.y < 0) p.y = H; if (p.y > H) p.y = 0;
      });
      ctx.globalAlpha = 1;
      requestAnimationFrame(drawParticles);
    }
    drawParticles();
  }

  // ---- Blog Category Filter ----
  const blogFilterBtns = document.querySelectorAll('.blog-filter-btn');
  const blogCards = document.querySelectorAll('.blog-card-h[data-cat]');
  if (blogFilterBtns.length) {
    blogFilterBtns.forEach(btn => {
      btn.addEventListener('click', function () {
        blogFilterBtns.forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const cat = this.getAttribute('data-cat');
        blogCards.forEach(card => {
          card.style.display = (cat === 'all' || card.getAttribute('data-cat') === cat) ? '' : 'none';
        });
      });
    });
  }

  // ---- Portfolio Lightbox Modal ----
  const projectDetails = {
    "bankease": {
      title: "BankEase – Digital Banking Platform",
      category: "FinTech / Web App",
      description: "BankEase is a secure, high-performance digital banking platform built for modern consumers. It features real-time financial tracking, multi-currency support, AI-driven spend analytics, and instant transfers. Developed with deep emphasis on bank-grade security protocols, the application supports over 50,000 active daily users with zero latency, providing personalized insights to help users manage their money smarter.",
      client: "FinCloud Technologies",
      date: "Dec 2025",
      link: "https://bankease.appsgain.demo",
      tech: ["React", "Node.js", "PostgreSQL", "AWS", "Docker"],
      icon: "fa-university"
    },
    "shopnow": {
      title: "ShopNow – Multi-Vendor Marketplace",
      category: "E-Commerce / Mobile App",
      description: "ShopNow is a multi-vendor fashion marketplace featuring dynamic product catalogs, optimized search filters, razor-fast checkout pipelines, and localized payment solutions. Accompanied by a custom merchant dashboard, it processes over 80,000 active monthly transactions. The React Native architecture ensures near-native responsiveness across iOS and Android platforms.",
      client: "ShopNow Inc.",
      date: "Oct 2025",
      link: "https://shopnow.appsgain.demo",
      tech: ["Next.js", "React Native", "MongoDB", "Razorpay", "Redis"],
      icon: "fa-shopping-bag"
    },
    "smartdesk": {
      title: "SmartDesk – AI Customer Support SaaS",
      category: "AI / SaaS",
      description: "SmartDesk is an intelligent ticketing and support automation platform. Powered by GPT-4 and custom fine-tuned classifiers, it automates incoming customer query categorization, generates auto-responses, and flags high-priority complaints through sentiment analysis. Deployed globally, it has reduced response and resolution timeframes by up to 60%.",
      client: "HelpSaaS Inc.",
      date: "Aug 2025",
      link: "https://smartdesk.appsgain.demo",
      tech: ["Python", "OpenAI API", "FastAPI", "Vue.js", "MongoDB"],
      icon: "fa-brain"
    },
    "healthtrack-pro": {
      title: "HealthTrack Pro – Wellness & Telehealth App",
      category: "Healthcare / Mobile App",
      description: "HealthTrack Pro is a HIPAA-compliant medical application designed to bridge the gap between patients and practitioners. Integrating seamlessly with wearables, it tracks vital metrics in real-time, supports video-consultation calls via secure WebRTC lines, and hosts an online pharmacy catalog with localized prescription deliveries.",
      client: "HealthCare Global Group",
      date: "Jun 2025",
      link: "https://healthtrack.appsgain.demo",
      tech: ["Flutter", "Firebase", "Node.js", "GCP", "WebRTC"],
      icon: "fa-heartbeat"
    },
    "freightflow": {
      title: "FreightFlow – Cloud Logistics Platform",
      category: "Logistics / SaaS",
      description: "FreightFlow is a real-time shipping, tracking, and route-optimization software. Tailored for logistics giants, it consolidates freight scheduling, vehicle assignments, automated invoice generations, and live GPS metrics into a single web application, improving overall dispatch speeds by 25%.",
      client: "TransSpeed India",
      date: "Apr 2025",
      link: "https://freightflow.appsgain.demo",
      tech: ["React", "Django", "Redis", "Google Maps API", "Celery"],
      icon: "fa-truck"
    },
    "tastybite": {
      title: "TastyBite – Hyperlocal Delivery Platform",
      category: "FoodTech / Web & Mobile",
      description: "TastyBite is a localized food order and delivery system featuring real-time partner order tracking, merchant inventory status logs, and dynamic user notifications. The platform launched seamlessly within three cities in less than 90 days, handling over 15,000 weekly active users.",
      client: "TastyBite Ventures",
      date: "Feb 2025",
      link: "https://tastybite.appsgain.demo",
      tech: ["Next.js", "React Native", "Socket.io", "Stripe", "PostgreSQL"],
      icon: "fa-utensils"
    },
    "risklens": {
      title: "RiskLens – Credit Risk ML Engine",
      category: "FinTech / AI & ML",
      description: "RiskLens is a credit scoring and default risk estimation model that analyzes over 200 consumer data fields. Built using modern ensemble classifiers, it assists loan underwriters in making precise credit decisions, boosting approval speeds by 80% while decreasing credit defaults by 34%.",
      client: "RetailMax Finance NBFC",
      date: "Jan 2025",
      link: "https://risklens.appsgain.demo",
      tech: ["Python", "XGBoost", "FastAPI", "AWS SageMaker", "Docker"],
      icon: "fa-chart-line"
    },
    "learnsphere": {
      title: "LearnSphere – White-Label LMS",
      category: "EdTech / Web App",
      description: "LearnSphere is a white-label virtual academy hosting interactive whiteboard classes, dynamic course catalogs, assignment checkouts, and custom certifications. Built-in subscription systems enable educators to easily launch their classes and manage billings globally.",
      client: "EduTech Global Academy",
      date: "Nov 2024",
      link: "https://learnsphere.appsgain.demo",
      tech: ["React", "Node.js", "WebRTC", "MySQL", "Stripe"],
      icon: "fa-graduation-cap"
    },
    "nestfinder": {
      title: "NestFinder – PropTech Rental App",
      category: "PropTech / Mobile App",
      description: "NestFinder is an advanced real-estate exploration app. It incorporates AI-driven listing matching, Virtual Reality (VR) property walkthroughs, secure message logs between landlords and renters, and digital lease signings across India's top tier-1 cities.",
      client: "NestFinder Pvt Ltd",
      date: "Sep 2024",
      link: "https://nestfinder.appsgain.demo",
      tech: ["React Native", "Python", "Elasticsearch", "Azure", "Three.js"],
      icon: "fa-home"
    }
  };

  function initPortfolioModal() {
    let modal = document.querySelector('.portfolio-modal');
    if (!modal) {
      modal = document.createElement('div');
      modal.className = 'portfolio-modal';
      modal.id = 'portfolioModal';
      modal.innerHTML = `
        <div class="portfolio-modal-overlay"></div>
        <div class="portfolio-modal-container">
          <button class="portfolio-modal-close" aria-label="Close modal"><i class="fas fa-times"></i></button>
          <div class="portfolio-modal-hero">
            <i class="fas fa-laptop-code"></i>
          </div>
          <div class="portfolio-modal-content">
            <span class="portfolio-modal-category">Category</span>
            <h3 class="portfolio-modal-title">Project Title</h3>
            <p class="portfolio-modal-description">Description</p>
            <div class="portfolio-modal-meta">
              <div class="portfolio-modal-meta-item">
                <span class="portfolio-modal-meta-label">Client</span>
                <span class="portfolio-modal-meta-value modal-client">Client</span>
              </div>
              <div class="portfolio-modal-meta-item">
                <span class="portfolio-modal-meta-label">Date</span>
                <span class="portfolio-modal-meta-value modal-date">Date</span>
              </div>
              <div class="portfolio-modal-meta-item">
                <span class="portfolio-modal-meta-label">Technologies</span>
                <div class="portfolio-modal-tech"></div>
              </div>
              <div class="portfolio-modal-meta-item">
                <span class="portfolio-modal-meta-label">Live Link</span>
                <span class="portfolio-modal-meta-value modal-link"><a href="#" target="_blank">Visit Site <i class="fas fa-external-link-alt" style="font-size:10px;margin-left:4px;"></i></a></span>
              </div>
            </div>
          </div>
        </div>
      `;
      document.body.appendChild(modal);
    }

    const overlay = modal.querySelector('.portfolio-modal-overlay');
    const closeBtn = modal.querySelector('.portfolio-modal-close');

    function closeModal() {
      modal.classList.remove('open');
      const drawer = document.getElementById('mobileDrawer');
      if (!drawer || !drawer.classList.contains('open')) {
        document.body.style.overflow = '';
      }
    }

    overlay && overlay.addEventListener('click', closeModal);
    closeBtn && closeBtn.addEventListener('click', closeModal);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

    // Click handler for opening the modal
    document.addEventListener('click', function (e) {
      const card = e.target.closest('.project-card');
      if (card) {
        // Don't open modal if we clicked on standard anchor tags
        if (e.target.closest('a') && !e.target.closest('.proj-overlay')) {
          return;
        }
        e.preventDefault();

        const h3 = card.querySelector('h3');
        if (!h3) return;
        const titleText = h3.textContent.trim().split('–')[0].trim();
        
        const key = titleText.toLowerCase()
          .replace(/[^a-z0-9\s-]/g, '')
          .replace(/\s+/g, '-')
          .replace(/-+/g, '-');
        
        const details = projectDetails[key];

        // Retrieve properties
        const title = details ? details.title : titleText;
        const category = details ? details.category : (card.querySelector('.project-tags span, .proj-cat')?.textContent.trim() || 'Software Solutions');
        const description = details ? details.description : (card.querySelector('.project-info p, .project-body p')?.textContent.trim() || '');
        const client = details ? details.client : 'Confidential';
        const date = details ? details.date : 'Recent';
        const link = details ? details.link : '#';
        const tech = details ? details.tech : Array.from(card.querySelectorAll('.project-tech span, .project-tags span')).map(el => el.textContent.trim());
        const iconClass = details ? details.icon : (card.querySelector('.project-img i, .project-thumb .proj-icon, .project-thumb i')?.className.split(' ').find(cls => cls.startsWith('fa-')) || 'fa-laptop-code');

        // Apply visual states to modal
        const imgEl = card.querySelector('.project-img, .project-thumb');
        const heroBg = imgEl ? window.getComputedStyle(imgEl).backgroundImage || window.getComputedStyle(imgEl).background : '';
        const modalHero = modal.querySelector('.portfolio-modal-hero');
        if (modalHero) {
          modalHero.style.background = heroBg || 'linear-gradient(135deg, var(--primary-light), var(--primary-dark))';
          modalHero.innerHTML = `<i class="fas ${iconClass}"></i>`;
        }

        modal.querySelector('.portfolio-modal-title').textContent = title;
        modal.querySelector('.portfolio-modal-category').textContent = category;
        modal.querySelector('.portfolio-modal-description').textContent = description;
        modal.querySelector('.modal-client').textContent = client;
        modal.querySelector('.modal-date').textContent = date;

        const techContainer = modal.querySelector('.portfolio-modal-tech');
        if (techContainer) {
          techContainer.innerHTML = tech.map(t => `<span>${t}</span>`).join('');
        }

        const linkAnchor = modal.querySelector('.modal-link a');
        if (linkAnchor) {
          if (link && link !== '#') {
            linkAnchor.href = link;
            linkAnchor.style.display = 'inline-flex';
          } else {
            linkAnchor.style.display = 'none';
          }
        }

        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
      }
    });
  }

  // Initialize Portfolio Modal — only where there is something to open.
  // main.js loads on every page from the shared footer, but the modal's
  // stylesheet (style.css) does not; on pages without it the injected
  // markup rendered as visible text below the footer.
  if (document.querySelector('.project-card')) initPortfolioModal();

  // ---- Service Page FAQ Accordion ----
  document.querySelectorAll('.faq-service-item').forEach(function(item) {
    var btn = item.querySelector('.faq-service-q');
    if (!btn) return;
    btn.addEventListener('click', function() {
      var isOpen = item.classList.contains('open');
      document.querySelectorAll('.faq-service-item').forEach(function(i) { i.classList.remove('open'); });
      if (!isOpen) item.classList.add('open');
    });
  });

});


/* ── PREMIUM MICRO-INTERACTIONS v2 ── */
(function() {

  /* Tilt card effect */
  document.querySelectorAll('.tilt-card').forEach(function(card) {
    card.addEventListener('mousemove', function(e) {
      var r = card.getBoundingClientRect();
      var x = (e.clientX - r.left) / r.width  - 0.5;
      var y = (e.clientY - r.top)  / r.height - 0.5;
      card.style.transform = 'perspective(600px) rotateX(' + (-y * 8) + 'deg) rotateY(' + (x * 8) + 'deg) translateZ(6px)';
    });
    card.addEventListener('mouseleave', function() {
      card.style.transform = '';
    });
  });

  /* Page transition wrapper */
  document.querySelectorAll('.page-transition-wrap').forEach(function(el) {
    el.style.opacity = '0';
    el.style.transform = 'translateY(12px)';
    setTimeout(function() {
      el.style.transition = 'opacity .5s cubic-bezier(.16,1,.3,1), transform .5s cubic-bezier(.16,1,.3,1)';
      el.style.opacity = '1';
      el.style.transform = 'none';
    }, 60);
  });

  /* Hover shimmer — add class to service cards and why cards */
  document.querySelectorAll('.service-card, .why-card, .project-card').forEach(function(el) {
    el.classList.add('hover-shimmer');
  });

  /* Counter flash on complete */
  document.querySelectorAll('.counter').forEach(function(el) {
    var observer = new MutationObserver(function() {
      el.classList.remove('counter-flash');
      void el.offsetWidth;
      el.classList.add('counter-flash');
    });
    observer.observe(el, { childList: true });
  });

  /* Link underline class auto-add on nav links */
  document.querySelectorAll('.nav-link:not(.has-mega):not(.has-dropdown)').forEach(function(a) {
    a.classList.add('link-underline');
  });

})();

/* ══════════════════════════════════════════════════════
   HERO CAMPAIGN CAROUSEL
   Autoplay · pause on hover/focus · swipe · keyboard · dots
   Respects prefers-reduced-motion (no autoplay).
══════════════════════════════════════════════════════ */
(function () {
  var track = document.getElementById('hbTrack');
  if (!track) return;

  var slides = Array.prototype.slice.call(track.querySelectorAll('.hb-slide'));
  if (slides.length < 2) return;

  var dots  = Array.prototype.slice.call(document.querySelectorAll('#hbDots .hb-dot'));
  var prev  = document.getElementById('hbPrev');
  var next  = document.getElementById('hbNext');
  var hero  = track.closest('.hb');
  var cur   = 0;
  var timer = null;
  var DELAY = 6000;
  var calm  = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function show(next, dir) {
    next = (next + slides.length) % slides.length;
    if (next === cur) return;

    var out = slides[cur], into = slides[next];
    out.classList.toggle('is-leaving', dir !== -1);
    out.classList.remove('is-active');
    out.setAttribute('aria-hidden', 'true');

    into.classList.remove('is-leaving');
    into.classList.add('is-active');
    into.removeAttribute('aria-hidden');

    /* clear the leaving offset once the transition has run */
    window.setTimeout(function () { out.classList.remove('is-leaving'); }, 620);

    dots.forEach(function (d, i) {
      d.classList.toggle('is-on', i === next);
      d.setAttribute('aria-selected', i === next ? 'true' : 'false');
    });
    cur = next;
  }

  function start() {
    if (calm) return;
    stop();
    timer = window.setInterval(function () { show(cur + 1, 1); }, DELAY);
  }
  function stop() { if (timer) { window.clearInterval(timer); timer = null; } }

  dots.forEach(function (d, i) {
    d.addEventListener('click', function () { show(i, i > cur ? 1 : -1); start(); });
  });

  /* Arrows drive the same show(), so dots, autoplay and the arrows
     can never disagree about which slide is current. */
  if (prev) prev.addEventListener('click', function () { show(cur - 1, -1); start(); });
  if (next) next.addEventListener('click', function () { show(cur + 1,  1); start(); });

  /* Left/right arrow keys when the hero has focus */
  hero.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowLeft')  { show(cur - 1, -1); start(); }
    if (e.key === 'ArrowRight') { show(cur + 1,  1); start(); }
  });

  /* Pause while the pointer or keyboard focus is inside the hero */
  ['mouseenter', 'focusin'].forEach(function (ev) { hero.addEventListener(ev, stop); });
  ['mouseleave', 'focusout'].forEach(function (ev) { hero.addEventListener(ev, start); });

  /* Don't animate a carousel nobody is looking at */
  document.addEventListener('visibilitychange', function () {
    document.hidden ? stop() : start();
  });

  /* Keyboard */
  hero.setAttribute('tabindex', '-1');
  hero.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowRight') { e.preventDefault(); show(cur + 1, 1); start(); }
    if (e.key === 'ArrowLeft')  { e.preventDefault(); show(cur - 1, -1); start(); }
  });

  /* Touch swipe */
  var x0 = null, y0 = null;
  hero.addEventListener('touchstart', function (e) {
    x0 = e.touches[0].clientX; y0 = e.touches[0].clientY; stop();
  }, { passive: true });
  hero.addEventListener('touchend', function (e) {
    if (x0 === null) return;
    var dx = e.changedTouches[0].clientX - x0;
    var dy = e.changedTouches[0].clientY - y0;
    /* horizontal intent only, so vertical page scrolling still works */
    if (Math.abs(dx) > 45 && Math.abs(dx) > Math.abs(dy)) {
      show(cur + (dx < 0 ? 1 : -1), dx < 0 ? 1 : -1);
    }
    x0 = y0 = null;
    start();
  }, { passive: true });

  /* Track height = tallest slide, so switching never shifts the page.
     Slides are absolutely positioned, so the track needs an explicit
     height; recalculated on resize and once webfonts have settled. */
  function sizeTrack() {
    var tallest = 0;
    slides.forEach(function (sl) {
      var wasActive = sl.classList.contains('is-active');
      if (!wasActive) {
        sl.style.visibility = 'hidden';
        sl.style.opacity = '0';
        sl.style.display = 'flex';
      }
      tallest = Math.max(tallest, sl.scrollHeight);
      if (!wasActive) {
        sl.style.display = '';
        sl.style.visibility = '';
        sl.style.opacity = '';
      }
    });
    if (tallest > 0) track.style.height = tallest + 'px';
  }
  sizeTrack();
  window.addEventListener('resize', sizeTrack);
  window.addEventListener('load', sizeTrack);
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(sizeTrack);

  start();
})();

/* ══════════════════════════════════════════════════════
   COOKIE CONSENT
   Both choices persist in a cookie (readable by PHP), so:
     - the banner does not reappear after Decline
     - analytics is gated server-side on the next request
   Accepting injects GA/GTM immediately, so no reload is needed.
══════════════════════════════════════════════════════ */
(function () {
  var box = document.getElementById('agCookie');
  if (!box) return;

  var KEY  = 'ag_consent';
  var DAYS = 180;

  function readConsent() {
    var m = document.cookie.match(/(?:^|;\s*)ag_consent=([^;]*)/);
    return m ? decodeURIComponent(m[1]) : '';
  }
  function writeConsent(value) {
    var d = new Date();
    d.setTime(d.getTime() + DAYS * 864e5);
    document.cookie = KEY + '=' + encodeURIComponent(value) +
      ';expires=' + d.toUTCString() + ';path=/;SameSite=Lax' +
      (location.protocol === 'https:' ? ';Secure' : '');
    try { localStorage.setItem(KEY, value); } catch (e) { /* private mode */ }
  }

  /* The banner is full-width at the bottom on a phone, where it would sit
     over the floating buttons. Publishing its height lets them move clear;
     without this the Get Enquiry button was covered and could not be tapped. */
  function markOpen(on) {
    document.body.classList.toggle('ag-cookie-open', on);
    document.body.style.setProperty('--ag-cookie-h', on ? box.offsetHeight + 'px' : '0px');
  }

  function hide() {
    box.classList.remove('is-in');
    markOpen(false);
    window.setTimeout(function () { box.hidden = true; }, 300);
  }

  /* Load the trackers the visitor just consented to, without a reload. */
  function enableAnalytics() {
    var cfg = window.__agAnalytics || {};
    if (cfg.gtm) {
      (function (w, d, s, l, i) {
        w[l] = w[l] || []; w[l].push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
        var f = d.getElementsByTagName(s)[0], j = d.createElement(s);
        j.async = true; j.src = 'https://www.googletagmanager.com/gtm.js?id=' + i;
        f.parentNode.insertBefore(j, f);
      })(window, document, 'script', 'dataLayer', cfg.gtm);
    }
    if (cfg.ga) {
      var t = document.createElement('script');
      t.async = true;
      t.src = 'https://www.googletagmanager.com/gtag/js?id=' + cfg.ga;
      document.head.appendChild(t);
      window.dataLayer = window.dataLayer || [];
      window.gtag = function () { window.dataLayer.push(arguments); };
      window.gtag('js', new Date());
      window.gtag('config', cfg.ga);
    }
  }

  /* Already decided? stay quiet. */
  if (readConsent()) return;

  window.setTimeout(function () {
    box.hidden = false;
    requestAnimationFrame(function () {
      box.classList.add('is-in');
      markOpen(true);
    });
  }, 1200);
  /* Its height changes when the text rewraps on rotation. */
  window.addEventListener('resize', function () {
    if (!box.hidden) markOpen(true);
  });

  var accept  = document.getElementById('agCookieAccept');
  var decline = document.getElementById('agCookieDecline');

  accept && accept.addEventListener('click', function () {
    writeConsent('accepted');
    enableAnalytics();
    hide();
  });
  decline && decline.addEventListener('click', function () {
    writeConsent('declined');   /* persisted — this was the bug */
    hide();
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !box.hidden) { writeConsent('declined'); hide(); }
  });
})();
