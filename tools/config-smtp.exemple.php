<?php
/**
 * Modèle de configuration SMTP.
 *
 * À déposer sur l'hébergement SOUS LE NOM config-smtp.php, dans /home/jasdwbp/,
 * c'est-à-dire UN NIVEAU AU-DESSUS de www/. Placé là, aucune URL ne peut
 * l'atteindre : le serveur web ne sert que le contenu de www/.
 *
 * Ne jamais committer ce fichier une fois rempli.
 */
return [
    'host'      => 'smtp.gmail.com',
    'port'      => 587,                        // 587 = STARTTLS, 465 = TLS implicite
    'user'      => 'contact@jas-dw.be',        // compte Google qui s'authentifie
    'pass'      => 'xxxxxxxxxxxxxxxx',         // mot de passe d'application, 16 caractères
    'from'      => 'site@jas-dw.be',           // expéditeur affiché — alias vérifié du compte
    'from_name' => 'Site JAS Digital Works',
    'to'        => 'contact@jas-dw.be',        // destinataire des demandes
];
