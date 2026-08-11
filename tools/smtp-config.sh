#!/usr/bin/env bash
# Dépose la configuration SMTP sur l'hébergement, HORS de la racine web.
#
#   bash tools/smtp-config.sh
#
# Le fichier atterrit dans /home/jasdwbp/config-smtp.php, un niveau au-dessus
# de www/ : aucune URL ne peut l'atteindre. Il n'est jamais écrit dans le dépôt,
# et le mot de passe est saisi masqué — absent de l'écran, de l'historique du
# shell et de la liste des processus.
#
# Prérequis côté Google : validation en deux étapes activée, puis un mot de
# passe d'application généré sur myaccount.google.com → Sécurité.

set -euo pipefail
cd "$(dirname "$0")/.."

CREDS="${HOME}/.jasdw-ftp"
[ -f "$CREDS" ] || { echo "Identifiants FTP absents. Lancez d'abord : bash tools/ftp-credentials.sh" >&2; exit 1; }

echo "Configuration SMTP — envoi du formulaire par Google"
echo

read -rp  "Compte qui s'authentifie   [contact@jas-dw.be] : " USER_SMTP
USER_SMTP="${USER_SMTP:-contact@jas-dw.be}"

read -rp  "Expéditeur affiché         [site@jas-dw.be]    : " FROM_SMTP
FROM_SMTP="${FROM_SMTP:-site@jas-dw.be}"

read -rp  "Destinataire des demandes  [contact@jas-dw.be] : " TO_SMTP
TO_SMTP="${TO_SMTP:-contact@jas-dw.be}"

echo
echo "Mot de passe d'application Google — 16 caractères, les espaces sont ignorés."
read -rsp "Mot de passe : " PASS1; echo
read -rsp "Confirmez    : " PASS2; echo

[ "$PASS1" = "$PASS2" ] || { echo "Les deux saisies diffèrent. Rien n'a été envoyé." >&2; exit 1; }
PASS1="${PASS1// /}"                      # Google affiche la clé par groupes de 4
[ -n "$PASS1" ] || { echo "Mot de passe vide." >&2; exit 1; }
if [ ${#PASS1} -ne 16 ]; then
  echo "Attention : ${#PASS1} caractères au lieu de 16. Ce n'est peut-être pas un mot de passe d'application." >&2
  read -rp "Continuer quand même ? [o/N] " R; [ "${R,,}" = "o" ] || exit 1
fi

umask 077
TMP=$(mktemp)
trap 'rm -f "$TMP"' EXIT
cat > "$TMP" <<PHP
<?php
// Configuration SMTP — déposée par tools/smtp-config.sh.
// Hors de www/ : le serveur web ne peut pas la servir.
return [
    'host'      => 'smtp.gmail.com',
    'port'      => 587,
    'user'      => '${USER_SMTP}',
    'pass'      => '${PASS1}',
    'from'      => '${FROM_SMTP}',
    'from_name' => 'Site JAS Digital Works',
    'to'        => '${TO_SMTP}',
];
PHP
unset PASS1 PASS2

CHEMIN="$TMP" python3 - <<'PY'
import os, paramiko
from pathlib import Path
v = {}
for l in (Path.home()/".jasdw-ftp").read_text().splitlines():
    k, _, val = l.partition("="); v[k.strip()] = val.strip()
cli = paramiko.SSHClient(); cli.set_missing_host_key_policy(paramiko.AutoAddPolicy())
cli.connect(v["host"], 22, v["user"], v["pass"], look_for_keys=False, allow_agent=False, timeout=25)
sftp = cli.open_sftp()
cible = "config-smtp.php"                      # relatif au home, donc au-dessus de www/
sftp.put(os.environ["CHEMIN"], cible)
sftp.chmod(cible, 0o600)
st = sftp.stat(cible)
print(f"Déposé : {sftp.normalize(cible)}  ({st.st_size} octets, permissions {oct(st.st_mode)[-3:]})")
sftp.close(); cli.close()
PY

echo
echo "Fait. Rien n'a été écrit dans le dépôt."
echo "Dites-le moi : je vérifie que le fichier est inaccessible par le web, puis on teste un envoi."
