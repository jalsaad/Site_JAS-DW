<?php
/**
 * JAS Digital Works — traitement du formulaire de contact.
 *
 * Hébergement OVH « Free hosting » (100 Mo, PHP 8.2, sans base de données).
 * Aucune dépendance, aucun service tiers : les demandes partent par mail()
 * vers la boîte incluse dans l'offre.
 *
 * Deux modes de réponse :
 *  - requête classique (JavaScript désactivé) → page de confirmation HTML ;
 *  - requête fetch() depuis site.js → JSON.
 */

declare(strict_types=1);

// ── Configuration ────────────────────────────────────────────────────────
// L'expéditeur DOIT être une adresse du domaine hébergé : OVH rejette les
// envois usurpant un domaine tiers (gmail.com, etc.). L'adresse du visiteur
// va dans Reply-To, ce qui permet de répondre d'un clic.
//
// Le MX Plan gratuit inclus avec le domaine ne fournit qu'un nombre limité de
// boîtes. On utilise donc la même adresse en expéditeur et en destinataire :
// c'est valide, et ça fonctionne avec une seule boîte créée. Si vous disposez
// d'une seconde adresse, remettez EXPEDITEUR à 'site@jas-dw.be' — vos règles
// de tri s'en trouveront simplifiées.
const DESTINATAIRE = 'contact@jas-dw.be';
const EXPEDITEUR   = 'contact@jas-dw.be';
const NOM_SITE     = 'Site JAS Digital Works';

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
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="assets/styles.css">
</head>
<body>

<a class="skip" href="#main">Aller au contenu</a>

<header class="site-header">
  <nav class="nav" aria-label="Navigation principale">
    <a class="nav-logo" href="index.html" aria-label="JAS Digital Works — accueil">
      <img src="assets/logo-inverse.png" alt="JAS Digital Works" width="480" height="94">
    </a>
    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="nav-links" aria-label="Ouvrir le menu"><span></span></button>
    <div class="nav-links" id="nav-links">
      <a href="index.html">Accueil</a>
      <a href="tarifs.html">Offres</a>
      <a href="fonctionnalites.html">Fonctionnalités</a>
      <a href="contact.html">Contact</a>
      <a class="btn btn-primary" href="contact.html">Demander une démo</a>
    </div>
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
      <img src="assets/logo-inverse.png" alt="JAS Digital Works" width="480" height="94">
    </a>
    <nav class="footer-links" aria-label="Liens de pied de page">
      <a href="tarifs.html">Offres</a>
      <a href="fonctionnalites.html">Fonctionnalités</a>
      <a href="contact.html">Contact</a>
      <a href="mentions-legales.html">Mentions légales</a>
    </nav>
  </div>
  <p class="footer-legal">© <span id="year">2026</span> JAS Digital Works — Tournai, Belgique.</p>
</footer>

<script src="assets/site.js"></script>
</body>
</html><?php
    exit;
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
    'Pack souhaité   : ' . $pack,
    '',
    '--- Message ---',
    $message !== '' ? $message : '(aucun message)',
    '',
    '---',
    'Envoyé depuis ' . ($_SERVER['HTTP_HOST'] ?? 'jas-dw.be') . ' le ' . date('d/m/Y à H:i'),
]);

$entetes = implode("\n", [
    'From: ' . NOM_SITE . ' <' . EXPEDITEUR . '>',
    'Reply-To: ' . $nom . ' <' . $email . '>',
    'Content-Type: text/plain; charset=UTF-8',
    'Content-Transfer-Encoding: 8bit',
    'X-Mailer: PHP/' . PHP_VERSION,
]);

$sujet  = 'Demande de démo — ' . $etablissement;
$envoye = @mail(DESTINATAIRE, $sujet, $corps, $entetes, '-f' . EXPEDITEUR);

if ($envoye) {
    repondre(true, 'Demande envoyée. Nous revenons vers vous sous 24 h ouvrées.');
}

repondre(
    false,
    "L'envoi a échoué. Écrivez-nous directement à " . DESTINATAIRE . ", nous traiterons votre demande de la même manière.",
    500
);
