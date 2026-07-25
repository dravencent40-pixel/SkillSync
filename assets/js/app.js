// SkillSync AI — shared frontend behaviour

// ---- Mobile hamburger menu ----
(function () {
  var hamburger = document.getElementById('hamburgerBtn');
  var mobileMenu = document.getElementById('mobileMenu');
  if (!hamburger || !mobileMenu) return;

  hamburger.addEventListener('click', function () {
    var isOpen = mobileMenu.classList.contains('open');
    mobileMenu.classList.toggle('open');
    hamburger.classList.toggle('active');
    document.body.style.overflow = isOpen ? '' : 'hidden';
  });

  // Close on nav link click
  mobileMenu.querySelectorAll('a').forEach(function (link) {
    link.addEventListener('click', function () {
      mobileMenu.classList.remove('open');
      hamburger.classList.remove('active');
      document.body.style.overflow = '';
    });
  });

  // Close on escape
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && mobileMenu.classList.contains('open')) {
      mobileMenu.classList.remove('open');
      hamburger.classList.remove('active');
      document.body.style.overflow = '';
    }
  });
})();

// ---- Spotlight-border hover effect ----
document.querySelectorAll('.spot-card').forEach(function (card) {
  card.addEventListener('mousemove', function (e) {
    var rect = card.getBoundingClientRect();
    card.style.setProperty('--x', (e.clientX - rect.left) + 'px');
    card.style.setProperty('--y', (e.clientY - rect.top) + 'px');
  });
});

// ---- Animate score rings ----
document.querySelectorAll('.score-ring').forEach(function (ring) {
  var circle = ring.querySelector('circle.progress');
  if (!circle) return;
  var score = parseFloat(ring.dataset.score || '0');
  var radius = circle.r.baseVal.value;
  var circumference = 2 * Math.PI * radius;
  circle.style.strokeDasharray = circumference;
  circle.style.strokeDashoffset = circumference;

  // Animate on intersection
  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        setTimeout(function () {
          circle.style.transition = 'stroke-dashoffset 1s cubic-bezier(0.16, 1, 0.3, 1)';
          circle.style.strokeDashoffset = circumference - (score / 100) * circumference;
        }, 100);
        observer.unobserve(ring);
      }
    });
  }, { threshold: 0.3 });
  observer.observe(ring);
});

// ---- Scroll-triggered fade-in animation ----
(function () {
  var animateElements = document.querySelectorAll('.animate-on-scroll');
  if (animateElements.length === 0) return;

  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('animate-fade-up');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

  animateElements.forEach(function (el) {
    observer.observe(el);
  });
})();

// ---- Code textarea: tab key behaviour ----
document.querySelectorAll('textarea.code-editor').forEach(function (ta) {
  ta.addEventListener('keydown', function (e) {
    if (e.key === 'Tab') {
      e.preventDefault();
      var start = ta.selectionStart, end = ta.selectionEnd;
      ta.value = ta.value.substring(0, start) + '    ' + ta.value.substring(end);
      ta.selectionStart = ta.selectionEnd = start + 4;
    }
  });
});

// ---- Modal preview for CVs ----
(function () {
  function openModal(url, metaHtml) {
    var overlay = document.getElementById('previewModal');
    if (!overlay) return;
    var iframe = overlay.querySelector('iframe.modal-iframe');
    var meta = overlay.querySelector('.modal-meta');
    iframe.src = url;
    if (meta && metaHtml) meta.innerHTML = metaHtml;
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    var overlay = document.getElementById('previewModal');
    if (!overlay) return;
    var iframe = overlay.querySelector('iframe.modal-iframe');
    iframe.src = 'about:blank';
    overlay.classList.remove('open');
    document.body.style.overflow = '';
  }

  document.addEventListener('click', function (e) {
    var t = e.target.closest('[data-preview]');
    if (t) {
      e.preventDefault();
      var url = t.getAttribute('data-preview');
      var meta = t.getAttribute('data-meta') || '';
      openModal(url, meta);
    }

    if (e.target.matches('#previewModal .modal-close') || e.target.matches('#previewModal')) {
      closeModal();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeModal();
  });
})();

// ---- Header scroll effect ----
(function () {
  var header = document.getElementById('mainHeader');
  if (!header) return;
  var lastScroll = 0;

  window.addEventListener('scroll', function () {
    var currentScroll = window.pageYOffset;
    if (currentScroll > 10) {
      header.style.boxShadow = '0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04)';
    } else {
      header.style.boxShadow = 'none';
    }
    lastScroll = currentScroll;
  }, { passive: true });
})();

// ---- Smooth scroll for anchor links ----
document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
  anchor.addEventListener('click', function (e) {
    var target = document.querySelector(this.getAttribute('href'));
    if (target) {
      e.preventDefault();
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });
});
