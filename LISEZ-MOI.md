# Site JAS Digital Works — site statique pour OVH Free hosting

Site vitrine de l'agence, direction graphique « Studio » (fond sombre,
typographie massive, dégradé de marque en accent). Sept pages, aucune dépendance,
aucune étape de build.

## Périmètre

Ce dépôt est **le site vitrine de JAS-DW, et rien d'autre**. Il informe les
prospects et reçoit des demandes de contact. Pas d'espace client, pas de compte,
aucune donnée d'établissement.

Les sites livrés aux écoles et **les VPS qui les hébergent sont provisionnés à
part**, sur des infrastructures séparées. Une formule qui vend un « VPS dédié »
ne dit donc rien des besoins de ce dépôt-ci : l'hébergement gratuit lui suffit.

## L'hébergement de ce site

Offre OVH **Free hosting** : 100 Mo, **HTML/CSS/JavaScript et PHP 8.2**,
**aucune base de données**, pas de CDN, datacentre eu-west-gra. Dépôt par FTP.
La messagerie n'est plus chez OVH : les MX pointent vers Google Workspace.

Poids actuel : **environ 590 Ko**, soit 0,6 % du budget. Le reste est pour les
photos.

Ce qui reste impossible : tout ce qui suppose une base de données (blog éditable,
espace client, actualités gérées en ligne). Une telle demande signifie qu'il faut
changer d'hébergement, pas contourner la contrainte.

## Contenu

```
index.html              Accueil — héros, marquee, services, hébergement, formules
tarifs.html             Offres — trois formules, tableau comparatif, FAQ
fonctionnalites.html    Fonctionnalités — cinq blocs alternés, illustrations SVG
projets.html            Nos projets — frise chronologique, logos partenaires
contact.html            Contact — formulaire
mentions-legales.html   Mentions légales — sommaire ancré
404.html                Page d'erreur
assets/styles.css       Tous les styles et les variables de charte
assets/site.js          Menu mobile, marquee, envoi sans rechargement, année
assets/logo-inverse.png Logo fond sombre (utilisé partout sur le site)
assets/logo.png         Logo fond clair (impression, documents)
assets/mark.png         Hexagone seul — favicon
contact.php             Traitement du formulaire (PHP 8.2, sans base de données)
lib/PHPMailer/          PHPMailer 7.1.1, déposé sans Composer — envoi SMTP
.htaccess               HTTPS, URLs sans extension, cache, en-têtes de sécurité
design/                 Sources graphiques — ne pas téléverser
tools/                  Scripts de déploiement — ne pas téléverser
robots.txt / sitemap.xml
```

## Mise en ligne sur OVH

Le déploiement est outillé, il n'y a plus à manipuler un client FTP :

```bash
bash tools/ftp-credentials.sh                            # une seule fois
python3 tools/deploy-ftp.py --dry-run                    # liste sans envoyer
python3 tools/deploy-ftp.py --credentials ~/.jasdw-ftp   # envoi réel
```

Le script tente **SFTP** (chiffré, port 22) et se rabat sur FTP. Il applique les
exclusions, retire les liens symboliques d'OVH — `www/index.html` en était un,
pointant vers la page d'accueil par défaut — et empreinte `styles.css` et
`site.js` d'un `?v=…` pour casser le cache d'un mois.

Si `.htaccess` provoque une erreur 500 (module absent sur le cluster), le
retirer : le site fonctionne sans lui, seules les URLs sans extension et les
en-têtes sont perdus.

## Formulaire de contact

Traité par `contact.php`, sur le serveur, sans service tiers : les données des
prospects ne quittent pas l'infrastructure OVH. C'est cohérent avec l'argument
RGPD vendu aux établissements.

L'envoi se fait en deux temps : **SMTP authentifié chez Google** d'abord, avec
PHPMailer ; **repli sur `mail()`** si le SMTP échoue. Aucune demande n'est perdue.

Les identifiants SMTP vivent dans `/home/jasdwbp/config-smtp.php`, **au-dessus de
`www/`** — le web ne peut pas les atteindre. Pour les (re)déposer :

```bash
bash tools/smtp-config.sh
```

L'adresse du visiteur est placée en `Reply-To`, la réponse part donc d'un clic.

Le formulaire fonctionne **sans JavaScript** : envoi natif puis page de
confirmation générée par `contact.php`. `site.js` ne fait qu'éviter le
rechargement, et repasse à l'envoi natif s'il échoue.

Protection anti-robot : champ masqué `site_web`. S'il est rempli, la demande est
silencieusement ignorée.

### Authentification du domaine

La messagerie est chez **Google Workspace**, plus chez OVH :

```
MX      10 smtp.google.com
SPF     v=spf1 include:_spf.google.com include:mx.ovh.com -all
DKIM    google._domainkey — publié
DMARC   v=DMARC1; p=quarantine; pct=25
```

Le flux du formulaire a été vérifié à **10/10 sur mail-tester** : SPF, DKIM et
DMARC alignés.

Le DMARC est au premier palier : un message non conforme sur quatre part en
quarantaine. Avant de monter à `pct=100` puis à `p=reject`, lire les rapports
reçus sur `contact@` — via dmarcian.com ou postmarkapp.com/dmarc — pour vérifier
qu'aucun outil tiers n'écrit sous le domaine sans être authentifié.

## Ce qui reste ouvert

Le site est complet et en production. Les coordonnées, les mentions légales et
la grille tarifaire sont renseignées.

Deux sujets restent à votre main :

- **Durcir le DMARC**, par paliers, une fois les rapports lus (voir plus haut).
- **Les témoignages.** La section d'origine contenait une citation fictive ; elle
  a été retirée. À réintroduire seulement avec l'accord écrit d'un établissement
  cité nommément.

Aucune photographie d'élève n'est publiée, faute d'accord écrit des parents. La
page Projets l'explique — c'est un argument commercial autant qu'une obligation
pour un prestataire qui vend de la conformité à des écoles.

## Photos

Les emplacements sont des `<figure class="media">` avec un motif hexagonal en
CSS. Pour insérer une vraie image :

```html
<figure class="media media-block">
  <img src="assets/photo-ecole.webp" alt="Cour de récréation" loading="lazy">
</figure>
```

Le filtre noir et blanc est déjà appliqué par le CSS. Exporter en **WebP,
largeur 1600 px max, qualité 75** — comptez 80 à 150 Ko par photo. La contrainte
n'est pas l'espace disque mais le temps de chargement, l'offre n'ayant pas de CDN.

Un fond sombre pardonne mal les emplacements vides : cette direction graphique
demande de vraies photos plus vite qu'une direction claire.

## Si l'offre gratuite devient contraignante

Le déclencheur ne sera ni l'espace disque, ni les fonctions vendues dans les
formules (elles tournent sur les VPS clients, provisionnés séparément). Le seul
motif plausible est l'absence de CDN, si les temps de chargement deviennent
gênants hors de Belgique.

Dans ce cas : **Cloudflare Pages ou Netlify** — gratuit, CDN mondial, déploiement
Git, toujours sans base de données ni build. Le domaine reste chez OVH et pointe
par DNS.

L'offre gratuite comprend **3 sites web** : de quoi héberger, en plus de ce site,
deux démonstrations client sur des sous-domaines.

## Adapter le design

Tout est piloté par les variables CSS en haut de `assets/styles.css` : surfaces,
texte, accent, dégradé de marque, échelle typographique, rythme d'espacement,
largeur du conteneur. Les contrastes ont été vérifiés (texte principal 18,3:1,
secondaire 5,9:1, liens 7,8:1 — tous conformes AA).
