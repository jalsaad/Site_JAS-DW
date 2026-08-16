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

  /* — Trame hexagonale animée du héros —
     Décorative, dessinée dans le navigateur : aucun fichier vidéo, donc
     aucun mégaoctet à télécharger sur une offre sans CDN. Le motif reprend
     l'hexagone du logo ; une onde lente le parcourt et réveille les
     hexagones qu'elle traverse, dans le dégradé de marque.

     Elle s'arrête d'elle-même quand le héros sort de l'écran ou que
     l'onglet passe en arrière-plan, et ne démarre pas du tout si
     l'utilisateur demande moins d'animation. */
  var toile = document.getElementById('hero-trame');
  if (toile && toile.getContext && !calme) {
    var ctx = toile.getContext('2d');
    var hero = toile.parentNode;
    var COTE = 34;                       /* rayon de l'hexagone */
    var LARG = Math.sqrt(3) * COTE;      /* largeur d'un hexagone pointe en haut */
    var centres = [], l = 0, h = 0, dpr = 1;

    var dimensionner = function () {
      var r = hero.getBoundingClientRect();
      l = Math.max(1, Math.round(r.width));
      h = Math.max(1, Math.round(r.height));
      dpr = Math.min(window.devicePixelRatio || 1, 2);
      toile.width = l * dpr;
      toile.height = h * dpr;
      toile.style.width = l + 'px';
      toile.style.height = h + 'px';
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

      centres = [];
      var lignes = Math.ceil(h / (COTE * 1.5)) + 2;
      var colonnes = Math.ceil(l / LARG) + 2;
      for (var y = 0; y < lignes; y++) {
        for (var x = 0; x < colonnes; x++) {
          centres.push({
            x: x * LARG + (y % 2 ? LARG / 2 : 0) - LARG,
            y: y * COTE * 1.5 - COTE
          });
        }
      }
    };

    var chemin = function (cx, cy, r) {
      ctx.beginPath();
      for (var i = 0; i < 6; i++) {
        var a = Math.PI / 180 * (60 * i - 90);
        var px = cx + r * Math.cos(a), py = cy + r * Math.sin(a);
        if (i === 0) ctx.moveTo(px, py); else ctx.lineTo(px, py);
      }
      ctx.closePath();
    };

    /* Le dégradé de la charte, échantillonné entre violet et cyan. */
    var teinte = function (t) {
      var d = [[139, 47, 242], [79, 107, 240], [24, 208, 240]];
      var p = t <= .55 ? [d[0], d[1], t / .55] : [d[1], d[2], (t - .55) / .45];
      var a = p[0], b = p[1], k = p[2];
      return Math.round(a[0] + (b[0] - a[0]) * k) + ',' +
             Math.round(a[1] + (b[1] - a[1]) * k) + ',' +
             Math.round(a[2] + (b[2] - a[2]) * k);
    };

    var t0 = null, image = null, visible = true;

    var dessiner = function (ts) {
      image = null;
      if (t0 === null) t0 = ts;
      var t = (ts - t0) / 1000;

      ctx.clearRect(0, 0, l, h);
      ctx.lineWidth = 1;

      /* L'onde décrit une ellipse lente, plus large que haute. */
      var ox = l * (.5 + .42 * Math.cos(t * .16));
      var oy = h * (.5 + .34 * Math.sin(t * .21));
      var portee = Math.max(l, h) * .34;

      for (var i = 0; i < centres.length; i++) {
        var c = centres[i];
        var dx = c.x - ox, dy = c.y - oy;
        var d = Math.sqrt(dx * dx + dy * dy);
        if (d > portee) {
          ctx.strokeStyle = 'rgba(34,34,46,.55)';        /* --line, en veille */
        } else {
          var f = 1 - d / portee;                        /* 0 au bord, 1 au centre */
          ctx.strokeStyle = 'rgba(' + teinte(Math.min(1, d / portee + t * .05 % 1)) +
                            ',' + (.10 + f * .42).toFixed(3) + ')';
        }
        chemin(c.x, c.y, COTE - 3);
        ctx.stroke();
      }

      if (visible) image = window.requestAnimationFrame(dessiner);
    };

    var relancer = function () {
      if (image === null && visible) image = window.requestAnimationFrame(dessiner);
    };
    var suspendre = function () {
      if (image !== null) { window.cancelAnimationFrame(image); image = null; }
    };

    dimensionner();
    relancer();

    var redim;
    window.addEventListener('resize', function () {
      window.clearTimeout(redim);
      redim = window.setTimeout(function () { dimensionner(); relancer(); }, 150);
    });

    document.addEventListener('visibilitychange', function () {
      visible = !document.hidden;
      if (visible) relancer(); else suspendre();
    });

    /* Hors champ, on ne dessine plus : la page ne consomme rien pendant
       que le visiteur lit le reste. */
    if (window.IntersectionObserver) {
      new IntersectionObserver(function (e) {
        visible = e[0].isIntersecting && !document.hidden;
        if (visible) relancer(); else suspendre();
      }, { threshold: 0 }).observe(hero);
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
