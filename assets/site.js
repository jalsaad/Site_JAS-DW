/* JAS Digital Works — script unique du site (aucune dépendance).
   ------------------------------------------------------------------
   Le formulaire de contact est traité par contact.php, côté serveur.
   Ce script ne fait qu'améliorer l'expérience : il envoie la demande
   sans recharger la page. Sans JavaScript, le formulaire fonctionne
   à l'identique et affiche la page de confirmation de contact.php.  */

(function () {
  'use strict';

  /* — Menu mobile — */
  var toggle = document.querySelector('.nav-toggle');
  var links = document.getElementById('nav-links');

  if (toggle && links) {
    toggle.addEventListener('click', function () {
      var open = toggle.getAttribute('aria-expanded') === 'true';
      toggle.setAttribute('aria-expanded', String(!open));
      links.classList.toggle('is-open', !open);
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && links.classList.contains('is-open')) {
        links.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.focus();
      }
    });
  }

  /* — Bandeau défilant : on duplique la piste pour que la boucle soit
       continue, sans écrire deux fois le texte dans le HTML. — */
  var track = document.querySelector('.marquee-track');
  if (track && track.children.length === 1) {
    track.appendChild(track.firstElementChild.cloneNode(true));
  }

  /* — Année du pied de page — */
  var year = document.getElementById('year');
  if (year) year.textContent = String(new Date().getFullYear());

  /* — En-tête : état « défilé » pour asseoir la barre sur le contenu — */
  var header = document.querySelector('.site-header');
  if (header) {
    var scrolled = false;
    var onScroll = function () {
      var now = window.pageYOffset > 8;
      if (now !== scrolled) {
        scrolled = now;
        header.classList.toggle('is-scrolled', now);
      }
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* — Bandeau : on suspend le défilement au survol, pour laisser lire — */
  var marquee = document.querySelector('.marquee');
  if (marquee) {
    marquee.addEventListener('mouseenter', function () { marquee.classList.add('is-paused'); });
    marquee.addEventListener('mouseleave', function () { marquee.classList.remove('is-paused'); });
  }

  var calme = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* — Apparition au défilement —
     Balayage à chaque trame utile plutôt qu'IntersectionObserver : un
     défilement rapide, ou un saut d'ancre, fait manquer des éléments à
     l'observateur, qui restent alors masqués. Ici un élément entré dans
     la fenêtre est révélé, quelle que soit la vitesse.
     `js-reveal` n'est posé que par ce script : sans JavaScript, ou si
     l'utilisateur demande moins d'animation, rien n'est masqué. */
  if (!calme && window.requestAnimationFrame) {
    var attente = [].slice.call(document.querySelectorAll(
      '.sec-head, .row, .card, .pack, .feature-copy, .feature-media, .final .wrap > *, .stats div'
    ));

    if (attente.length) {
      document.documentElement.classList.add('js-reveal');

      for (var j = 0; j < attente.length; j++) {
        /* Décalage court à l'intérieur d'un même groupe : au-delà de
           quatre éléments l'attente devient perceptible. */
        var rang = 0, n = attente[j].previousElementSibling;
        while (n && rang < 4) { rang++; n = n.previousElementSibling; }
        attente[j].style.transitionDelay = (rang * 60) + 'ms';
      }

      var planifie = false;
      var balayer = function () {
        planifie = false;
        var seuil = (window.innerHeight || 0) * 0.92;
        for (var i = attente.length - 1; i >= 0; i--) {
          if (attente[i].getBoundingClientRect().top < seuil) {
            attente[i].classList.add('is-visible');
            attente.splice(i, 1);
          }
        }
        if (!attente.length) {
          window.removeEventListener('scroll', demander);
          window.removeEventListener('resize', demander);
        }
      };
      var demander = function () {
        if (planifie) return;
        planifie = true;
        window.requestAnimationFrame(balayer);
      };

      window.addEventListener('scroll', demander, { passive: true });
      window.addEventListener('resize', demander);
      balayer();
    }
  }

  /* — Sommaire des mentions légales : section courante — */
  var toc = document.querySelector('.toc');
  if (toc && window.IntersectionObserver) {
    var liens = {};
    var ancres = toc.querySelectorAll('a[href^="#"]');
    for (var k = 0; k < ancres.length; k++) {
      liens[ancres[k].getAttribute('href').slice(1)] = ancres[k];
    }
    var titres = document.querySelectorAll('.prose h2[id]');
    if (titres.length) {
      var courant = null;
      var suivi = new IntersectionObserver(function (entrees) {
        for (var i = 0; i < entrees.length; i++) {
          if (!entrees[i].isIntersecting) continue;
          var lien = liens[entrees[i].target.id];
          if (!lien || lien === courant) continue;
          if (courant) courant.removeAttribute('aria-current');
          lien.setAttribute('aria-current', 'true');
          courant = lien;
        }
      }, { rootMargin: '-96px 0px -70% 0px' });
      for (var m = 0; m < titres.length; m++) suivi.observe(titres[m]);
    }
  }

  /* — Formulaire de contact — */
  var form = document.getElementById('contact-form');
  if (!form) return;

  /* Pré-sélection du pack depuis les liens « Choisir … » (contact.html?pack=interactif) */
  var wanted = new URLSearchParams(window.location.search).get('pack');
  if (wanted) {
    var index = {
      'initial': 0,
      'integral': 1, 'intégral': 1,
      'integral-plus': 2, 'integral+': 2,
      'integral-plus-plus': 3, 'integral++': 3
    }[wanted.toLowerCase()];
    var radios = form.querySelectorAll('input[name="pack"]');
    if (index !== undefined && radios[index]) radios[index].checked = true;
  }

  var status = document.getElementById('form-status');
  var submit = form.querySelector('button[type="submit"]');

  function say(message, state) {
    status.textContent = message;
    status.setAttribute('data-state', state || '');
  }

  /* Si fetch n'existe pas (navigateur ancien), on laisse l'envoi natif faire le travail. */
  if (!window.fetch) return;

  form.addEventListener('submit', function (e) {
    e.preventDefault();

    submit.disabled = true;
    say('Envoi en cours…');

    fetch(form.action, {
      method: 'POST',
      headers: { 'X-Requested-With': 'fetch' },
      body: new FormData(form)
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.success) {
          form.reset();
          say(res.message, 'ok');
        } else {
          say(res.message, 'error');
        }
      })
      .catch(function () {
        /* Réseau ou serveur injoignable : on repasse à l'envoi natif du navigateur. */
        form.submit();
      })
      .then(function () { submit.disabled = false; });
  });
})();
