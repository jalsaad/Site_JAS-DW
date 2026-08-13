# Site JAS Digital Works

Site vitrine de [JAS Digital Works](https://jas-dw.be), agence digitale à
Frasnes-lez-Anvaing, qui conçoit et maintient les sites web des établissements
scolaires belges.

**En ligne : [jas-dw.be](https://jas-dw.be)**

---

## Ce que c'est

Sept pages statiques et un seul fichier PHP. Pas de framework, pas de bundler,
pas de `node_modules`, pas de Composer : **ce qui est dans le dépôt est
exactement ce qui est téléversé.**

Ce n'est pas un choix d'esthète. L'hébergement est l'offre gratuite OVH incluse
avec le nom de domaine — 100 Mo, HTML/CSS/JS et PHP 8.2, aucune base de données,
pas de CDN, dépôt par FTP. Tout le reste en découle.

```
index.html              Accueil
tarifs.html             Offres — trois formules, tableau comparatif, FAQ
fonctionnalites.html    Fonctionnalités — blocs alternés, illustrations SVG
projets.html            Nos projets — frise chronologique
contact.html            Contact — formulaire
mentions-legales.html   Mentions légales
404.html

assets/                 Feuille de style unique, script unique, logos
contact.php             Traitement du formulaire — seul fichier dynamique
lib/PHPMailer/          PHPMailer 7.1.1, déposé sans Composer
.htaccess               HTTPS, URLs sans extension, cache, en-têtes sécurité
design/                 Sources graphiques — non téléversé
tools/                  Scripts de déploiement — non téléversé
```

Poids déployé : environ 590 Ko.

## Déployer

```bash
python3 tools/deploy-ftp.py --dry-run                    # liste sans envoyer
python3 tools/deploy-ftp.py --credentials ~/.jasdw-ftp   # envoi réel
```

Le script tente SFTP (chiffré) et se rabat sur FTP, applique les exclusions,
retire les liens symboliques d'OVH, et empreinte `styles.css` et `site.js` d'un
`?v=…` pour casser le cache d'un mois imposé par `.htaccess`.

Première utilisation : `bash tools/ftp-credentials.sh` enregistre les
identifiants dans `~/.jasdw-ftp`, hors du dépôt.

## Développer

```bash
php -S localhost:8080 -t .        # contact.php inclus
```

`python3 -m http.server` fonctionne aussi, mais n'exécute pas le PHP.

## Messagerie

Le formulaire part en **SMTP authentifié chez Google**, avec repli sur `mail()`
si le SMTP échoue — aucune demande n'est perdue. Le flux est signé DKIM et
aligné DMARC, vérifié à 10/10 sur mail-tester.

```
MX      10 smtp.google.com
SPF     v=spf1 include:_spf.google.com include:mx.ovh.com -all
DKIM    google._domainkey — publié
DMARC   v=DMARC1; p=quarantine; pct=25
```

Les identifiants SMTP vivent dans `/home/jasdwbp/config-smtp.php` sur le
serveur, **un niveau au-dessus de `www/`** : aucune URL ne peut les atteindre.
Ils ne sont jamais versionnés. Modèle dans `tools/config-smtp.exemple.php`.

## Deux règles à ne pas contourner

**Aucune base de données ne sera jamais nécessaire.** Ce dépôt est le site
vitrine, rien d'autre : pas d'espace client, pas de compte. Les VPS vendus dans
les formules sont provisionnés séparément, pour les écoles clientes, sur des
infrastructures distinctes. Si une demande suppose une base de données, elle
vise le mauvais dépôt.

**Aucun témoignage, chiffre ou référence client inventé.** Les logos partenaires
figurent avec l'accord de leur titulaire. Aucune photographie d'élève n'est
publiée sans accord écrit des parents — la page Projets l'explique, et c'est un
argument commercial autant qu'une obligation.

## Pour aller plus loin

- [LISEZ-MOI.md](LISEZ-MOI.md) — mise en ligne, formulaire, photos, évolutions
- [CLAUDE.md](CLAUDE.md) — charte graphique, conventions de code, décisions prises
