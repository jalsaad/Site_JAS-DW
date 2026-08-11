#!/usr/bin/env bash
# Enregistre les identifiants FTP/SFTP hors du dépôt, en lecture pour vous seul.
#
#   bash tools/ftp-credentials.sh
#
# Le serveur et l'utilisateur sont proposés par défaut (valeurs de l'espace
# client OVH, ou de la saisie précédente) : appuyez sur Entrée pour les garder.
# Le mot de passe est saisi masqué — il n'apparaît ni à l'écran, ni dans
# l'historique du shell, ni dans la liste des processus. Le fichier produit
# (~/.jasdw-ftp) n'est pas dans le dépôt et n'est jamais téléversé.

set -euo pipefail

CIBLE="${HOME}/.jasdw-ftp"

# Valeurs par défaut : celles déjà enregistrées, sinon celles d'OVH.
DEF_HOTE="ftp.cluster129.hosting.ovh.net"
DEF_USER="jasdwbp"
if [ -f "$CIBLE" ]; then
  v=$(grep -E '^host=' "$CIBLE" | cut -d= -f2- || true); [ -n "${v:-}" ] && DEF_HOTE="$v"
  v=$(grep -E '^user=' "$CIBLE" | cut -d= -f2- || true); [ -n "${v:-}" ] && DEF_USER="$v"
fi

echo "Identifiants de l'hébergement OVH — jas-dw.be"
echo "Entrée = garder la valeur entre crochets."
echo

read -rp  "Serveur   [$DEF_HOTE] : " HOTE
read -rp  "Login     [$DEF_USER] : " UTILISATEUR
HOTE="${HOTE:-$DEF_HOTE}"
UTILISATEUR="${UTILISATEUR:-$DEF_USER}"

# Une valeur contenant un espace vient forcément d'un collage malencontreux.
case "$HOTE$UTILISATEUR" in
  *" "*) echo; echo "Erreur : le serveur ou le login contient une espace — collage accidentel ?" >&2; exit 1;;
esac

read -rsp "Mot de passe (masqué)  : " MOTDEPASSE
echo
read -rsp "Confirmez le mot de passe : " MOTDEPASSE2
echo

if [ "$MOTDEPASSE" != "$MOTDEPASSE2" ]; then
  echo "Erreur : les deux saisies diffèrent. Rien n'a été enregistré." >&2
  exit 1
fi
if [ -z "$MOTDEPASSE" ]; then
  echo "Erreur : mot de passe vide." >&2
  exit 1
fi

umask 077
printf 'host=%s\nuser=%s\npass=%s\n' "$HOTE" "$UTILISATEUR" "$MOTDEPASSE" > "$CIBLE"
chmod 600 "$CIBLE"
unset MOTDEPASSE MOTDEPASSE2

echo
echo "Enregistré dans $CIBLE (permissions $(stat -c %a "$CIBLE"))."
echo "Serveur : $HOTE   Login : $UTILISATEUR   Mot de passe : saisi deux fois, identique."
echo "Dites-le moi : je teste la connexion, puis je téléverse."
