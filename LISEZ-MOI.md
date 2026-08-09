# Site JAS Digital Works — site statique pour OVH Free hosting

Site vitrine de l'agence, direction graphique « Studio » (fond sombre,
typographie massive, dégradé de marque en accent). Six pages, aucune dépendance,
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
**aucune base de données**, MX Plan à nombre d'adresses limité, pas de CDN,
datacentre eu-west-gra. Dépôt par FTP.

Poids actuel : **environ 250 Ko**, soit 0,25 % du budget. Le reste est pour les
photos.

Ce qui reste impossible : tout ce qui suppose une base de données (blog éditable,
espace client, actualités gérées en ligne). Une telle demande signifie qu'il faut
changer d'hébergement, pas contourner la contrainte.

## Contenu

```
index.html              Accueil — héros, marquee, services, hébergement, formules
tarifs.html             Offres — trois formules, tableau comparatif, FAQ
fonctionnalites.html    Fonctionnalités — cinq blocs alternés
contact.html            Contact — formulaire
mentions-legales.html   Mentions légales — sommaire ancré, champs à compléter
404.html                Page d'erreur
assets/styles.css       Tous les styles et les variables de charte
assets/site.js          Menu mobile, marquee, envoi sans rechargement, année
assets/logo-inverse.png Logo fond sombre (utilisé partout sur le site)
assets/logo.png         Logo fond clair (impression, documents)
assets/mark.png         Hexagone seul — favicon
contact.php             Traitement du formulaire (PHP 8.2, sans base de données)
.htaccess               HTTPS, URLs sans extension, cache, en-têtes de sécurité
design/                 Sources logo — ne pas téléverser
robots.txt / sitemap.xml
```

## Mise en ligne sur OVH

1. Créer un accès FTP depuis l'espace client, ainsi que la boîte
   `contact@jas-dw.be`.
2. Se connecter en FTP et téléverser **le contenu** du dépôt dans `www/`
   (pas le dossier lui-même). Ne pas envoyer `design/`, `CLAUDE.md`,
   `LISEZ-MOI.md`, `.gitignore`.
3. Vérifier que les fichiers cachés sont visibles dans le client FTP, sinon
   `.htaccess` ne sera pas envoyé.
4. Activer le certificat SSL gratuit (Let's Encrypt) depuis l'espace client,
   puis attendre la propagation avant de tester la redirection HTTPS.
5. Envoyer une demande de test depuis `contact.html` et vérifier la réception,
   indésirables compris. C'est le seul point qui ne peut pas être validé hors
   production.

Si `.htaccess` provoque une erreur 500 (module absent sur le cluster), le
retirer : le site fonctionne sans lui, seules les URLs sans extension et les
en-têtes sont perdus.

## Formulaire de contact

Traité par `contact.php`, sur le serveur, sans service tiers : les données des
prospects ne quittent pas l'infrastructure OVH. C'est cohérent avec l'argument
RGPD vendu aux établissements.

```php
const DESTINATAIRE = 'contact@jas-dw.be';   // où arrivent les demandes
const EXPEDITEUR   = 'contact@jas-dw.be';   // doit appartenir au domaine hébergé
```

Les deux valent la même adresse parce que le **MX Plan gratuit limite le nombre
de boîtes**. Si une seconde adresse est créée, remettre `site@jas-dw.be` en
expéditeur : le tri s'en trouvera simplifié. L'adresse du visiteur est placée en
`Reply-To`, la réponse part donc d'un clic.

Le formulaire fonctionne **sans JavaScript** : envoi natif puis page de
confirmation générée par `contact.php`. `site.js` ne fait qu'éviter le
rechargement, et repasse à l'envoi natif s'il échoue.

Protection anti-robot : champ masqué `site_web`. S'il est rempli, la demande est
silencieusement ignorée.

### Si mail() ne part pas

L'envoi par `mail()` n'est pas garanti sur l'offre gratuite. Tester tôt, avec un
vrai destinataire, et vérifier les indésirables. En cas d'échec, le repli est le
**SMTP authentifié** via la boîte incluse (`ssl0.ovh.net:465`), avec PHPMailer
déposé dans `lib/` — meilleure délivrabilité, mais un mot de passe à protéger
sur le serveur.

## Points à compléter avant la mise en production

Les coordonnées sont à **reprendre depuis le site actuel** (`jas-dw.be` et
`jas-digital-works.odoo.com`). Elles n'ont pas pu l'être pendant la refonte :
l'environnement d'exécution n'avait pas accès à ces deux domaines.

| Fichier | À faire |
|---|---|
| `contact.html` | Rétablir le bloc téléphone (laissé en commentaire) avec le numéro réel ; compléter l'adresse postale |
| `mentions-legales.html` | Tous les champs entre crochets : forme juridique, adresse, BCE/TVA, responsable de la publication, durée de conservation, mention HT/TTC |
| `contact.php` | Créer la boîte, puis test d'envoi réel (indésirables compris) |
| toutes les pages | Remplacer `https://jas-dw.be` dans `canonical` et `og:` si le domaine diffère |
| `tarifs.html`, `index.html` | Vérifier que les prix correspondent à ceux du site actuel |

La section témoignage a été retirée : elle contenait une citation fictive. À
réintroduire seulement avec l'accord écrit d'un établissement cité nommément.

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
