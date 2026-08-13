#!/usr/bin/env python3
"""Téléverse le site vers l'hébergement OVH par FTP.

Le dépôt est exactement ce qui part en production, à quatre exclusions près
(design/, tools/, les deux Markdown, les fichiers de dépôt). Ce script
applique ces exclusions et rien d'autre : il n'y a aucune étape de build.

Identifiants — jamais dans le dépôt, jamais en argument de ligne de commande
(la ligne de commande est visible dans l'historique du shell et dans `ps`) :

    export FTP_HOST=ftpxxxxx.cluster129.hosting.ovh.net
    export FTP_USER=xxxxxxx
    export FTP_PASS='…'
    python3 tools/deploy-ftp.py --dry-run

ou dans un fichier hors du dépôt, en lecture seule pour vous :

    printf 'host=…\\nuser=…\\npass=…\\n' > ~/.jasdw-ftp && chmod 600 ~/.jasdw-ftp
    python3 tools/deploy-ftp.py --credentials ~/.jasdw-ftp --dry-run

Commencez toujours par --dry-run : il liste ce qui partirait, sans rien
envoyer ni se connecter.
"""

from __future__ import annotations

import argparse
import ftplib
import re
import os
import ssl
import sys
from pathlib import Path

RACINE = Path(__file__).resolve().parent.parent

# Ne partent jamais en production.
DOSSIERS_EXCLUS = {".git", "design", "tools", "screenshots", "node_modules", ".vscode", ".devcontainer"}
FICHIERS_EXCLUS = {"CLAUDE.md", "LISEZ-MOI.md", "README.md", ".gitignore", ".DS_Store", "Thumbs.db"}
SUFFIXES_EXCLUS = {".swp", ".bak", ".orig"}


def fichiers_a_envoyer() -> list[tuple[Path, str]]:
    """(chemin local, chemin distant relatif), triés dossiers d'abord."""
    sortie: list[tuple[Path, str]] = []
    for chemin in sorted(RACINE.rglob("*")):
        rel = chemin.relative_to(RACINE)
        if any(p in DOSSIERS_EXCLUS for p in rel.parts[:-1]) or rel.parts[0] in DOSSIERS_EXCLUS:
            continue
        if not chemin.is_file():
            continue
        if chemin.name in FICHIERS_EXCLUS or chemin.suffix in SUFFIXES_EXCLUS:
            continue
        sortie.append((chemin, rel.as_posix()))
    return sortie


def empreinter_ressources() -> list[str]:
    """Réécrit `styles.css?v=…` et `site.js?v=…` avec l'empreinte du fichier.

    L'.htaccess demande un cache d'un mois sur la CSS et le JS. Sans cette
    empreinte, un visiteur déjà venu garde l'ancienne feuille pendant trente
    jours — et le HTML neuf s'affiche avec des styles périmés. Les images en
    portent la trace la plus visible : privées de leurs règles, elles se
    replient sur les attributs width/height et s'étirent.
    """
    import hashlib

    versions = {}
    for nom in ("styles.css", "site.js"):
        chemin = RACINE / "assets" / nom
        if chemin.exists():
            versions[nom] = hashlib.sha256(chemin.read_bytes()).hexdigest()[:8]

    touches = []
    for cible in list(RACINE.glob("*.html")) + [RACINE / "contact.php"]:
        if not cible.exists():
            continue
        avant = texte = cible.read_text(encoding="utf-8")
        for nom, v in versions.items():
            texte = re.sub(rf"(assets/{re.escape(nom)})(\?v=[0-9a-f]+)?", rf"\1?v={v}", texte)
        if texte != avant:
            cible.write_text(texte, encoding="utf-8")
            touches.append(cible.name)
    if versions:
        print("Empreintes : " + ", ".join(f"{n}?v={v}" for n, v in versions.items()))
        print(f"  {len(touches)} fichier(s) mis à jour" if touches else "  déjà à jour")
    return touches


def identifiants(chemin_fichier: str | None) -> tuple[str, str, str]:
    donnees = {"host": os.environ.get("FTP_HOST", ""),
               "user": os.environ.get("FTP_USER", ""),
               "pass": os.environ.get("FTP_PASS", "")}
    if chemin_fichier:
        f = Path(chemin_fichier).expanduser()
        if not f.exists():
            sys.exit(f"Fichier d'identifiants introuvable : {f}")
        mode = f.stat().st_mode & 0o077
        if mode:
            print(f"  Attention : {f} est lisible par d'autres comptes. `chmod 600 {f}`.")
        for ligne in f.read_text(encoding="utf-8").splitlines():
            ligne = ligne.strip()
            if not ligne or ligne.startswith("#") or "=" not in ligne:
                continue
            cle, _, valeur = ligne.partition("=")
            cle = cle.strip().lower()
            if cle in donnees:
                donnees[cle] = valeur.strip().strip('"').strip("'")
    manquant = [k for k, v in donnees.items() if not v]
    if manquant:
        sys.exit("Identifiants incomplets : " + ", ".join(manquant)
                 + "\nVoir l'en-tête de ce script pour les deux façons de les fournir.")
    return donnees["host"], donnees["user"], donnees["pass"]


def envoyer_par_sftp(host: str, user: str, mdp: str, dossier: str,
                     fichiers: list[tuple[Path, str]]) -> bool:
    """Tente SFTP (port 22). Rend False si indisponible, pour repli sur FTP.

    À préférer : sur cette offre OVH, `FEAT` annonce AUTH TLS mais le serveur
    le refuse (« 500 This security scheme is not implemented »). Le FTP est
    donc en clair — mot de passe compris. SFTP, lui, est chiffré.
    """
    try:
        import paramiko
    except ImportError:
        print("  paramiko absent — repli sur FTP (`pip install paramiko` pour chiffrer).")
        return False

    cli = paramiko.SSHClient()
    cli.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    try:
        cli.connect(host, port=22, username=user, password=mdp,
                    look_for_keys=False, allow_agent=False, timeout=20)
    except paramiko.AuthenticationException:
        print("  SFTP : identifiants refusés — repli sur FTP.")
        return False
    except Exception as e:
        print(f"  SFTP indisponible ({type(e).__name__}) — repli sur FTP.")
        return False

    sftp = cli.open_sftp()
    try:
        try:
            sftp.stat(dossier)
        except IOError:
            print(f"  Dossier distant « {dossier} » introuvable en SFTP — repli sur FTP.")
            return False
        import stat as stat_mod

        crees: set[str] = set()
        for local, rel in fichiers:
            distant = f"{dossier}/{rel}"
            parent = os.path.dirname(distant)
            if parent and parent not in crees:
                try:
                    sftp.stat(parent)
                except IOError:
                    sftp.mkdir(parent)
                crees.add(parent)

            # OVH livre www/index.html en lien symbolique vers sa page
            # d'accueil par défaut, hors de la portée du compte. Écrire dessus
            # suivrait le lien et échouerait : on le retire d'abord.
            try:
                infos = sftp.lstat(distant)
                if stat_mod.S_ISLNK(infos.st_mode):
                    sftp.remove(distant)
                    print(f"   lien symbolique retiré : {rel}")
            except IOError:
                pass

            sftp.put(str(local), distant)
            print(f"   envoyé  {rel}  ({local.stat().st_size/1024:.1f} Ko)")
        return True
    finally:
        sftp.close(); cli.close()


def connecter(host: str, user: str, mdp: str, tls: bool) -> ftplib.FTP:
    if tls:
        ctx = ssl.create_default_context()
        ftp = ftplib.FTP_TLS(context=ctx)
        ftp.connect(host, 21, timeout=30)
        ftp.auth()                 # AUTH TLS : le mot de passe ne circule pas en clair
        ftp.login(user, mdp)
        ftp.prot_p()               # chiffre aussi le canal de données
    else:
        ftp = ftplib.FTP()
        ftp.connect(host, 21, timeout=30)
        ftp.login(user, mdp)
    ftp.set_pasv(True)
    return ftp


def assurer_dossier(ftp: ftplib.FTP, chemin: str, connus: set[str]) -> None:
    if not chemin or chemin in connus:
        return
    parent = os.path.dirname(chemin)
    if parent:
        assurer_dossier(ftp, parent, connus)
    try:
        ftp.mkd(chemin)
    except ftplib.error_perm as e:
        if not str(e).startswith("550"):        # 550 = existe déjà
            raise
    connus.add(chemin)


def main() -> None:
    ap = argparse.ArgumentParser(description="Téléverse le site vers l'hébergement OVH.")
    ap.add_argument("--dry-run", action="store_true", help="liste ce qui partirait, sans se connecter")
    ap.add_argument("--credentials", metavar="FICHIER", help="fichier host=/user=/pass= hors du dépôt")
    ap.add_argument("--remote-dir", default="www", help="dossier distant (défaut : www)")
    ap.add_argument("--no-bump", action="store_true",
                    help="ne pas recalculer l'empreinte des ressources avant l'envoi")
    ap.add_argument("--force-ftp", action="store_true",
                    help="ignore SFTP et passe directement par FTP")
    ap.add_argument("--no-tls", action="store_true",
                    help="FTP en clair : le mot de passe circule en clair, à n'utiliser que si le serveur refuse AUTH TLS")
    args = ap.parse_args()

    if not args.no_bump:
        empreinter_ressources()

    fichiers = fichiers_a_envoyer()
    total = sum(f.stat().st_size for f, _ in fichiers)
    print(f"{len(fichiers)} fichiers, {total/1024:.0f} Ko\n")
    for _, rel in fichiers:
        print(f"   {rel}")

    if args.dry_run:
        print(f"\nEssai à blanc — rien n'a été envoyé. Destination prévue : {args.remote_dir}/")
        return

    host, user, mdp = identifiants(args.credentials)

    if not args.force_ftp:
        print(f"\nTentative SFTP (chiffré) sur {host}…")
        if envoyer_par_sftp(host, user, mdp, args.remote_dir, fichiers):
            print(f"\n{len(fichiers)} fichiers envoyés par SFTP, {total/1024:.0f} Ko.")
            print("Vérifiez : https://jas-dw.be/ , une URL sans extension (/tarifs), "
                  "et un envoi réel depuis le formulaire.")
            return

    print(f"\nConnexion à {host} ({'FTPS' if not args.no_tls else 'FTP en clair'})…")
    try:
        ftp = connecter(host, user, mdp, tls=not args.no_tls)
    except ssl.SSLError as e:
        sys.exit(f"Échec TLS : {e}\nSi le serveur ne propose pas AUTH TLS, relancez avec --no-tls.")
    except ftplib.error_perm as e:
        sys.exit(f"Identifiants refusés : {e}")

    try:
        try:
            ftp.cwd(args.remote_dir)
        except ftplib.error_perm:
            sys.exit(f"Dossier distant « {args.remote_dir} » introuvable. "
                     "Sur l'hébergement OVH mutualisé, la racine du site est www/.")
        print(f"Dossier distant : {ftp.pwd()}\n")

        connus: set[str] = set()
        envoyes = 0
        for local, rel in fichiers:
            dossier = os.path.dirname(rel)
            if dossier:
                assurer_dossier(ftp, dossier, connus)
            with local.open("rb") as fh:
                ftp.storbinary(f"STOR {rel}", fh)     # binaire : sûr pour le texte comme pour les PNG
            envoyes += 1
            print(f"   envoyé  {rel}  ({local.stat().st_size/1024:.1f} Ko)")

        print(f"\n{envoyes} fichiers envoyés, {total/1024:.0f} Ko.")
        print("Vérifiez ensuite : https://jas-dw.be/ , une URL sans extension (/tarifs), "
              "et un envoi réel depuis le formulaire.")
    finally:
        try:
            ftp.quit()
        except Exception:
            ftp.close()


if __name__ == "__main__":
    main()
