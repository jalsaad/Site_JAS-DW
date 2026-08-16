# JAS Digital Works — site vitrine

Site statique de JAS Digital Works (agence digitale, Frasnes-lez-Anvaing), qui commercialise
des sites web clé en main auprès des établissements scolaires belges (FWB/WBE).

## Ce qu'est ce site — et ce qu'il n'est pas

Ce dépôt est **le site vitrine de JAS-DW uniquement**. Sa seule fonction est
d'informer les prospects sur nos services et de recevoir des demandes de contact.
Il ne contient **aucun espace client, aucun compte, aucune donnée d'établissement**.

Les sites livrés aux écoles, et les **VPS qui les hébergent, sont provisionnés
séparément**, sur des infrastructures distinctes de celle-ci. Le fait qu'une
formule vende un « VPS dédié » ne dit **rien** des besoins techniques du présent
dépôt : ce sont deux sujets sans rapport. Ne jamais justifier un changement
d'hébergement de ce site par une fonctionnalité vendue dans une formule.

Conséquence : ce site n'aura jamais besoin d'une base de données. Si une demande
en suppose une — blog éditable, actualités gérées en ligne, espace client —
**s'arrêter et le signaler** : c'est qu'elle vise le mauvais dépôt.

## Contrainte structurante — à ne jamais contourner

Hébergement OVH **Free hosting**, inclus avec le nom de domaine :

- **100 Mo d'espace disque**, datacentre eu-west-gra, cluster129.
- **HTML, CSS, JavaScript et PHP 8.2.**
- **Aucune base de données** — l'offre n'en propose pas.
- **La messagerie n'est plus chez OVH.** Les MX pointent vers Google Workspace
  (`smtp.google.com`). Le MX Plan d'OVH n'est plus utilisé.
- **Pas de CDN**, pas de SSH. Dépôt par **FTP** uniquement.
- 3 emplacements de sites web, de quoi ajouter des démonstrations client.

En conséquence : **HTML et CSS statiques, JavaScript vanilla, sans étape de build.**
Pas de React, pas de Vue, pas de Tailwind, pas de bundler, pas de `node_modules`,
pas de Composer. Ce qui est dans le dépôt est exactement ce qui est téléversé.

PHP n'est utilisé que pour **un seul fichier**, `contact.php`. Ne pas l'étendre en
mini-framework : chaque page ajoutée en PHP cesse d'être mise en cache et
vérifiable statiquement.

## Structure

```
index.html              Accueil
tarifs.html             Offres — trois formules + tableau comparatif + FAQ
fonctionnalites.html    Fonctionnalités — blocs alternés
contact.html            Contact — formulaire
mentions-legales.html   Mentions légales — sommaire ancré, champs à compléter
404.html
assets/styles.css       Feuille unique : tokens + composants
assets/site.js          Menu mobile, marquee, envoi sans rechargement, année
assets/logo-inverse.png Lockup horizontal, lettrage blanc — fond sombre (défaut)
assets/logo.png         Lockup horizontal, lettrage #545454 — fond clair
assets/mark.png         Hexagone seul — favicon
contact.php             Traitement du formulaire — le seul fichier dynamique
lib/PHPMailer/          PHPMailer 7.1.1, déposé sans Composer — envoi SMTP
lib/.htaccess           Interdit l'accès web à la bibliothèque
.htaccess               HTTPS, URLs sans extension, cache, en-têtes sécurité
design/                 Sources logo (LogoJAS_*.png) — NE PAS téléverser
tools/                  Scripts de déploiement — NE PAS téléverser
```

Hors du dépôt, sur le serveur : `/home/jasdwbp/config-smtp.php` contient les
identifiants SMTP. Il est **un niveau au-dessus de `www/`**, donc inaccessible
par le web. Modèle dans `tools/config-smtp.exemple.php`.

Poids déployé : environ 250 Ko, hors photos.

## Charte — direction « Studio »

Trois directions avaient été maquettées (Studio sombre, Produit bento clair,
Éditorial serif). **La direction A « Studio » a été retenue** par Jalal le
9 août 2026. Les deux autres ne sont plus dans le dépôt.

Tout est piloté par les variables de `:root` dans `assets/styles.css`, qui est la
source unique. Ne pas coder de valeur en dur ailleurs.

- Fond `#07070A`, surfaces `#0E0E14` et `#16161F`, filets `#22222E`.
- Texte `#F4F4F2`, secondaire `#8A8A99` (5,9:1 sur le fond — conforme AA).
- Typo : **Archivo** 400/600/800, chargée depuis Google Fonts.
- Titres en 800, `letter-spacing: -.04em`, `line-height: .96` — la graisse et
  l'échelle font l'essentiel de l'identité, ne pas les adoucir.
- Dégradé `linear-gradient(135deg,#8B2FF2,#4F6BF0 55%,#18D0F0)` réservé au mot
  accentué des titres (`.grad`), à l'étiquette « Recommandé » et au sélecteur de
  formule. **Jamais en aplat de fond.**
- Accent lisible : `--violet-lo` `#B98BF7` (7,8:1). Le violet brut `#8B2FF2` ne
  passe AA que sur du texte large : ne l'utiliser que dans le dégradé.
- Angles : rayon 20 px sur les cartes, 999 px sur les boutons et étiquettes.
- Conteneur 1320 px, gouttières 40 px (22 px sous 1000 px).
- Tout est **aligné à gauche**.
- Grain : `body::after`, SVG en ligne, poids nul. Purement décoratif.
- Photos en **noir et blanc** (`filter: grayscale(1)` appliqué par `.media img`).

## Conventions de code

- JS en ES5 prudent, sans dépendance, sans `import`. Un seul fichier.
- **Une seule clé de stockage local**, `jasdw-theme`, qui retient le choix
  clair/sombre. C'est la seule exception à la règle « aucun stockage » : sans
  elle, le bouton oublierait son état à chaque page. Elle est déclarée dans les
  mentions légales. Aucun cookie, aucun traceur, rien qui parte vers un serveur —
  l'argument commercial reste entier. Ne rien y ajouter d'autre.
- **Deux thèmes.** Le sombre est l'identité de la marque et le défaut ; le clair
  s'active par `data-theme="light"` sur `<html>`. Toute couleur doit passer par
  un token de `:root` : une valeur écrite en dur ne suivra pas la bascule. Les
  illustrations SVG en ligne utilisent elles aussi `var(--…)`, sauf les arrêts
  de dégradé de marque, qui ne dépendent pas du thème.
- **Le logo existe en deux versions** dans chaque en-tête et pied de page,
  `.logo-sombre` et `.logo-clair`, l'une masquée par le thème. Le lettrage blanc
  disparaîtrait sur fond clair.
- CSS : attention aux spécificités. Piège déjà rencontré — `.nav-links a`
  (0,1,1) écrase `.btn` (0,1,0) et casse le bouton du header. Les sélecteurs de
  navigation utilisent donc `:not(.btn)`. Vérifier ce genre de collision avant
  d'ajouter une règle contextuelle.
- Accessibilité : lien d'évitement, `aria-current` sur l'onglet actif,
  `:focus-visible` en cyan, `prefers-reduced-motion` respecté (marquee et
  transitions coupées). À maintenir.
- Sans JavaScript, en mobile, le bouton du menu ne peut rien ouvrir : la règle
  `@media (max-width:1000px) and (scripting: none)` déplie la navigation en
  clair. Ne pas la retirer.
- Chaque page duplique header et footer (pas de templating possible sans build).
  **Toute modification du header ou du footer doit être répercutée sur les
  6 pages, plus `contact.php`.** C'est le coût assumé du choix statique.

## Contenu commercial — règles

- **Aucun témoignage, chiffre ou référence client inventé.** Le bundle d'origine
  contenait une citation fictive : elle a été retirée. Ne rien réintroduire sans
  autorisation écrite du client cité.
- Les quatre chiffres du héros d'accueil (24 h, 100 %, 0, 1) sont des
  engagements. Ne pas en ajouter — notamment aucun délai de livraison — sans
  validation de Jalal.
- Les prix doivent rester alignés entre `index.html`, `tarifs.html` (cartes
  **et** tableau comparatif) et les boutons radio de `contact.html` — quatre
  endroits à modifier ensemble.

## Logos

Les sources sont dans `design/` : `LogoJAS_F_H.png` (lettrage foncé) et
`LogoJAS_C_H.png` (lettrage blanc), lockups horizontaux 1080 × 350 déjà
détourés. Les fichiers servis sont recadrés à la boîte du contenu
(50, 62, 1058, 260), redimensionnés à 480 px de large et quantifiés en PNG-8
128 couleurs — 7 Ko pièce au lieu de 45, pour un écart moyen de 2,9/255.

Les versions verticales d'origine (`LogoJAS_F.png`, `LogoJAS_C.png`) avaient un
fond opaque incrusté ; l'alpha en avait été déduit par différence entre les deux
fonds. Elles ne sont plus utilisées : le lockup horizontal tient mieux dans un
en-tête.

## Emplacements photo

Les blocs `.media` affichent un motif hexagonal en CSS, poids nul. Pour une
vraie image :

```html
<figure class="media media-block">
  <img src="assets/photo-ecole.webp" alt="…" loading="lazy">
</figure>
```

WebP, 1600 px de large maximum, qualité 75 — soit 80 à 150 Ko pièce. Le quota
n'est pas la contrainte : c'est le temps de chargement, l'offre n'ayant pas de
CDN. Compresser sérieusement, et toujours `loading="lazy"` hors du hero.
**Un fond sombre pardonne mal les emplacements vides** : cette direction demande
de vraies photos plus vite qu'une direction claire.

## Formulaire de contact

`contact.php` envoie en deux temps : **SMTP authentifié chez Google** d'abord,
**repli sur `mail()`** si le SMTP échoue ou n'est pas configuré. Aucune demande
n'est perdue.

Pourquoi le SMTP : `mail()` fonctionne, mais OVH réécrit l'expéditeur d'enveloppe
vers son propre domaine de rebond et n'appose aucune signature. Le message
échoue donc à DMARC. Passé par Google, il est signé DKIM avec `d=jas-dw.be` et
s'aligne — vérifié à 10/10 sur mail-tester.

Points à préserver :

- **L'expéditeur doit appartenir au domaine hébergé.** OVH rejette les envois qui
  usurpent un domaine tiers. L'adresse du visiteur va en `Reply-To`.
- **Le mot de passe d'application ne doit jamais entrer dans le dépôt.** Il vit
  dans `/home/jasdwbp/config-smtp.php`, permissions 600, hors de `www/`.
  `config-smtp.php` est dans `.gitignore`.
- **Google réécrit l'expéditeur** si l'adresse n'est pas un alias vérifié du
  compte authentifié. `site@jas-dw.be` est actuellement réécrit en
  `contact@jas-dw.be` : pour l'éviter, le déclarer dans Gmail → Paramètres →
  Comptes → « Envoyer des e-mails en tant que ».
- **Le formulaire fonctionne sans JavaScript** : envoi natif, puis page de
  confirmation rendue par `contact.php`. `site.js` ne fait qu'éviter le
  rechargement et repasse à l'envoi natif si la requête échoue. Dégradation
  volontaire, ne pas la retirer.
- **Toute valeur est nettoyée des CRLF** avant d'entrer dans un en-tête mail.
- **Piège à robots** : champ masqué `site_web`, réponse en succès silencieux.

## Authentification du domaine

```
MX      10 smtp.google.com
SPF     v=spf1 include:_spf.google.com include:mx.ovh.com -all
DKIM    google._domainkey — publié
DMARC   v=DMARC1; p=none; rua=mailto:contact@jas-dw.be
```

`include:mx.ovh.com` est conservé pour couvrir le repli `mail()`.

**Le DMARC est encore en `p=none`** : il observe, il ne protège pas. Avant de
durcir en `quarantine` puis `reject`, vérifier qu'aucun outil tiers n'écrit sous
le domaine (Odoo, facturation, signature électronique) — ces flux seraient
rejetés.

## Développement local

```bash
python3 -m http.server 8000    # puis http://localhost:8000
```

`contact.php` n'est pas exécuté par `http.server`. Pour le tester,
`php -S localhost:8000` (l'envoi échouera, mais la validation des champs et la
page de réponse sont vérifiables).

Contrôle visuel : Playwright, captures pleine page en 1440 px et 390 px.

## Cache — le piège à ne pas rouvrir

Deux régimes dans `.htaccess`, selon que l'URL change avec le fichier :

- **CSS et JS** sont empreintés par le script de déploiement (`?v=…`), donc
  gardés un an en `immutable`.
- **Le HTML** garde toujours la même URL : il est en `no-cache,
  must-revalidate`. Sans cela, une page modifiée continue d'être servie depuis
  le navigateur du visiteur, avec les anciennes références de styles et de
  script — ce qui s'est produit deux fois : images étirées, puis bascule de
  thème absente des pages internes.

OVH ne renvoie ni `ETag` ni `Last-Modified` sur ces URL : la revalidation ne
peut donc pas répondre 304, chaque visite retélécharge le HTML. Quelques
kilo-octets compressés, sans commune mesure avec le risque d'une page périmée.

**Les images ne sont pas empreintées** et sont gardées un mois. Remplacer un
logo sans changer son nom demande donc de la patience, ou un nom de fichier
différent.

## Déploiement

```bash
python3 tools/deploy-ftp.py --dry-run                    # liste sans envoyer
python3 tools/deploy-ftp.py --credentials ~/.jasdw-ftp   # envoi réel
```

Le script tente **SFTP** (chiffré, port 22) et se rabat sur FTP. Il applique les
exclusions et retire les liens symboliques d'OVH avant d'écrire — `www/index.html`
en est un, pointant vers la page d'accueil par défaut.

Identifiants : `bash tools/ftp-credentials.sh`, stocké dans `~/.jasdw-ftp`,
jamais dans le dépôt.

**Ne pas téléverser** `design/`, `tools/`, `CLAUDE.md`, `LISEZ-MOI.md`,
`.gitignore` — le script s'en charge.
Attention : beaucoup de clients FTP masquent `.htaccess` par défaut.
Si le serveur renvoie une 500, retirer `.htaccess` — le site fonctionne sans lui,
seules les URLs sans extension et les en-têtes de sécurité sont perdus.

Ne jamais committer d'identifiants FTP.

## À compléter avant mise en production

Les coordonnées sont à **reprendre depuis le site actuel** (`jas-dw.be` et
`jas-digital-works.odoo.com`) : elles n'ont pas pu être récupérées, l'environnement
d'exécution n'ayant pas accès à ces domaines.

- [ ] Téléphone réel dans `contact.html` (le bloc est en commentaire, prêt à rétablir)
- [ ] Adresse postale complète dans `contact.html` et `mentions-legales.html`
- [ ] `mentions-legales.html` : numéro BCE/TVA, forme juridique, responsable de
      la publication, mention HT/TTC, durée de conservation
- [ ] Créer la boîte `contact@jas-dw.be` puis tester un envoi réel
- [ ] Photos noir et blanc de l'établissement pilote
- [ ] Vérifier que les prix correspondent à ceux du site actuel

## Décisions ouvertes — ne pas trancher seul

- **Témoignages.** La section a été retirée faute de citation réelle. La
  réintroduire dès qu'un établissement pilote accepte d'être cité nommément.
- **Hébergement de ce site.** Tranché : l'offre gratuite OVH suffit et reste le
  choix retenu. Les VPS clients étant provisionnés séparément, ce site
  n'héritera jamais d'un besoin de base de données par ce biais. Le seul
  déclencheur d'un changement serait un besoin propre au site vitrine —
  typiquement un CDN, via Cloudflare Pages, qui reste sans base de données.
