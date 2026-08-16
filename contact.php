<?php
/**
 * JAS Digital Works — traitement du formulaire de contact.
 *
 * Hébergement OVH « Free hosting » (100 Mo, PHP 8.2, sans base de données).
 *
 * Envoi en deux temps :
 *  1. SMTP authentifié chez Google, si la configuration existe. Le message est
 *     alors signé DKIM par Google avec d=jas-dw.be, donc aligné DMARC.
 *  2. Repli sur mail() si le SMTP échoue ou n'est pas configuré. Cette voie
 *     fonctionne mais n'est pas alignée : OVH réécrit l'expéditeur d'enveloppe
 *     vers son propre domaine de rebond, et n'appose aucune signature.
 *
 * Aucune demande n'est perdue : le repli garantit la remise même si Google
 * refuse la connexion.
 *
 * La configuration SMTP — mot de passe d'application compris — vit dans
 * config-smtp.php, placé UN NIVEAU AU-DESSUS de www/. Le serveur web ne peut
 * donc jamais le servir, quelle que soit l'URL demandée.
 *
 * Deux modes de réponse :
 *  - requête classique (JavaScript désactivé) → page de confirmation HTML ;
 *  - requête fetch() depuis site.js → JSON.
 */

declare(strict_types=1);

// ── Configuration ────────────────────────────────────────────────────────
const DESTINATAIRE = 'contact@jas-dw.be';
const EXPEDITEUR   = 'contact@jas-dw.be';   // doit appartenir au domaine hébergé
const NOM_SITE     = 'Site JAS Digital Works';

/** Chemin de la configuration SMTP, hors de la racine web. */
function chemin_config_smtp(): string {
    return dirname(__DIR__) . '/config-smtp.php';
}

// ── Utilitaires ──────────────────────────────────────────────────────────

function est_ajax(): bool {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'fetch';
}

/** Neutralise les injections d'en-têtes mail (CRLF dans un champ). */
function propre(string $valeur): string {
    return trim(str_replace(["\r", "\n", "%0a", "%0d"], ' ', $valeur));
}

function repondre(bool $succes, string $message, int $code = 200): never {
    http_response_code($code);

    if (est_ajax()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => $succes, 'message' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }

    page_reponse($succes, $message);
}

function page_reponse(bool $succes, string $message): never {
    $titre  = $succes ? 'Demande envoyée' : 'Envoi impossible';
    $kicker = $succes ? 'Merci' : 'Erreur';
    $h = fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    ?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $h($titre) ?> — JAS Digital Works</title>
<meta name="robots" content="noindex">
<link rel="icon" type="image/png" href="assets/mark.png">
<meta name="theme-color" content="#07070A">
<script>
/* Le thème enregistré doit être posé avant le premier rendu. site.js est en
   fin de body : sans ce bloc, la page s'affiche en sombre le temps qu'il
   s'exécute, ce qui produit un clignotement à chaque changement de page.
   C'est la seule exception à la règle « un seul fichier de script ». */
(function(){try{if(localStorage.getItem('jasdw-theme')==='light'){document.documentElement.setAttribute('data-theme','light');var m=document.querySelector('meta[name=theme-color]');if(m)m.setAttribute('content','#FAFAFC');}}catch(e){}})();
</script>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="assets/styles.css?v=6b5ec5c6">
</head>
<body>

<a class="skip" href="#main">Aller au contenu</a>

<header class="site-header">
  <nav class="nav" aria-label="Navigation principale">
    <a class="nav-logo" href="index.html" aria-label="JAS Digital Works — accueil">
      <img class="logo-sombre" src="assets/logo-inverse.png" alt="JAS Digital Works" width="480" height="94">
      <img class="logo-clair" src="assets/logo.png" alt="JAS Digital Works" width="480" height="94">
    </a>
    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="nav-links" aria-label="Ouvrir le menu"><span></span></button>
    <div class="nav-links" id="nav-links">
      <a href="index.html">Accueil</a>
      <a href="tarifs.html">Offres</a>
      <a href="fonctionnalites.html">Fonctionnalités</a>
      <a href="projets.html">Nos projets</a>
      <a href="contact.html">Contact</a>
      <a class="btn btn-primary" href="contact.html">Demander une démo</a>
    </div>
    <button class="theme-toggle" type="button" aria-pressed="false"
            aria-label="Basculer entre le mode sombre et le mode clair">
      <svg class="ico-soleil" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
        <circle cx="12" cy="12" r="4.2"/>
        <path d="M12 2.6v2.2M12 19.2v2.2M4.2 4.2l1.6 1.6M18.2 18.2l1.6 1.6M2.6 12h2.2M19.2 12h2.2M4.2 19.8l1.6-1.6M18.2 5.8l1.6-1.6"/>
      </svg>
      <svg class="ico-lune" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M20.5 14.3A8.6 8.6 0 0 1 9.7 3.5a8.6 8.6 0 1 0 10.8 10.8Z"/>
      </svg>
    </button>
  </nav>
</header>

<main id="main" class="section">
  <div class="wrap">
    <p class="kicker"><i></i> <?= $h($kicker) ?></p>
    <h1><?= $h($titre) ?></h1>
    <p class="lead" style="margin-top:var(--s6)"><?= $h($message) ?></p>
    <div class="hero-actions">
      <a class="btn btn-primary" href="index.html">Retour à l'accueil</a>
      <?php if (!$succes): ?><a class="btn btn-secondary" href="contact.html">Reprendre le formulaire</a><?php endif; ?>
    </div>
  </div>
</main>

<footer class="site-footer">
  <div class="footer-inner">
    <a href="index.html" aria-label="JAS Digital Works — accueil">
      <img class="logo-sombre" src="assets/logo-inverse.png" alt="JAS Digital Works" width="480" height="94">
      <img class="logo-clair" src="assets/logo.png" alt="JAS Digital Works" width="480" height="94">
    </a>
    <nav class="footer-links" aria-label="Liens de pied de page">
      <a href="tarifs.html">Offres</a>
      <a href="fonctionnalites.html">Fonctionnalités</a>
      <a href="projets.html">Nos projets</a>
      <a href="contact.html">Contact</a>
      <a href="mentions-legales.html">Mentions légales</a>
    </nav>
  </div>
  <p class="footer-legal">© <span id="year">2026</span> JAS Digital Works — Frasnes-lez-Anvaing, Belgique.</p>
</footer>

<script src="assets/site.js?v=71fd062e"></script>
</body>
</html><?php
    exit;
}

// ── Envoi ────────────────────────────────────────────────────────────────

/**
 * Envoi par SMTP authentifié. Rend true si le message est parti.
 * Toute erreur est avalée : l'appelant se rabat sur mail().
 */
function envoyer_par_smtp(string $sujet, string $corps, string $nom, string $email): bool {
    $config_php = chemin_config_smtp();
    if (!is_readable($config_php)) {
        return false;
    }
    $cfg = require $config_php;
    if (!is_array($cfg) || empty($cfg['host']) || empty($cfg['user']) || empty($cfg['pass'])) {
        return false;
    }

    $base = __DIR__ . '/lib/PHPMailer/';
    foreach (['Exception.php', 'PHPMailer.php', 'SMTP.php'] as $fichier) {
        if (!is_readable($base . $fichier)) {
            return false;
        }
        require_once $base . $fichier;
    }

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $cfg['host'];
        $mail->Port       = (int)($cfg['port'] ?? 587);
        $mail->SMTPAuth   = true;
        $mail->Username   = $cfg['user'];
        $mail->Password   = $cfg['pass'];
        $mail->SMTPSecure = ($mail->Port === 465)
            ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Timeout    = 15;
        $mail->CharSet    = 'UTF-8';
        $mail->Encoding   = '8bit';

        $mail->setFrom($cfg['from'] ?? EXPEDITEUR, $cfg['from_name'] ?? NOM_SITE);
        $mail->addAddress($cfg['to'] ?? DESTINATAIRE);
        $mail->addReplyTo($email, $nom !== '' ? $nom : $email);

        $mail->Subject = $sujet;
        $mail->Body    = $corps;
        $mail->isHTML(false);

        return $mail->send();
    } catch (Throwable $e) {
        // Journalisation discrète : jamais affichée au visiteur.
        error_log('contact.php — SMTP indisponible : ' . $e->getMessage());
        return false;
    }
}

/** Envoi par la fonction mail() de l'hébergement. Voie de repli. */
function envoyer_par_mail(string $sujet, string $corps, string $nom, string $email): bool {
    $entetes = implode("\n", [
        'From: ' . NOM_SITE . ' <' . EXPEDITEUR . '>',
        'Reply-To: ' . $nom . ' <' . $email . '>',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'X-Mailer: PHP/' . PHP_VERSION,
    ]);
    return @mail(DESTINATAIRE, $sujet, $corps, $entetes, '-f' . EXPEDITEUR);
}

// ── Traitement ───────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contact.html', true, 303);
    exit;
}

// Piège à robots : champ masqué que seuls les scripts remplissent.
// On renvoie un succès pour ne pas leur indiquer la détection.
if (!empty($_POST['site_web'] ?? '')) {
    repondre(true, 'Demande envoyée. Nous revenons vers vous sous 24 h ouvrées.');
}

$etablissement = propre((string)($_POST['etablissement'] ?? ''));
$nom           = propre((string)($_POST['nom'] ?? ''));
$email         = propre((string)($_POST['email'] ?? ''));
$pack          = propre((string)($_POST['pack'] ?? 'Non précisé'));
$message       = trim((string)($_POST['message'] ?? ''));

if ($etablissement === '' || $nom === '' || $email === '') {
    repondre(false, "L'établissement, votre nom et votre e-mail sont nécessaires pour traiter la demande.", 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    repondre(false, "L'adresse e-mail saisie n'est pas valide.", 422);
}

if (mb_strlen($message) > 5000) {
    repondre(false, 'Le message dépasse la longueur acceptée. Merci de le raccourcir.', 422);
}

$corps = implode("\n", [
    'Établissement   : ' . $etablissement,
    'Nom et fonction : ' . $nom,
    'E-mail          : ' . $email,
    'Formule         : ' . $pack,
    '',
    '--- Message ---',
    $message !== '' ? $message : '(aucun message)',
    '',
    '---',
    'Envoyé depuis ' . ($_SERVER['HTTP_HOST'] ?? 'jas-dw.be') . ' le ' . date('d/m/Y à H:i'),
]);

$sujet = 'Demande de démo — ' . $etablissement;

$envoye = envoyer_par_smtp($sujet, $corps, $nom, $email)
       || envoyer_par_mail($sujet, $corps, $nom, $email);

if ($envoye) {
    repondre(true, 'Demande envoyée. Nous revenons vers vous sous 24 h ouvrées.');
}

repondre(
    false,
    "L'envoi a échoué. Écrivez-nous directement à " . DESTINATAIRE . ", nous traiterons votre demande de la même manière.",
    500
);
