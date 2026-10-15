# Stratégie de sauvegarde et de reprise

Cahier §47 — « Prévoir : sauvegarde automatique de la base ; sauvegarde des
fichiers ; stratégie de rétention ; restauration testée ; journal des
sauvegardes ; procédure de reprise après incident. »

Ce document est la procédure d'exploitation : il décrit **ce** qui est
sauvegardé, **comment** le restaurer et **comment** on vérifie que la
restauration fonctionne encore. Toute modification de l'infrastructure doit
être répercutée ici.

---

## 1. Objectifs

| Objectif | Valeur visée | Comment c'est tenu |
|---|---|---|
| **RPO** (perte de données maximale) | 24 h | sauvegarde complète quotidienne + copie hors site |
| **RPO** en journée | 1 h | journal binaire MySQL (binlog) activé sur le serveur |
| **RTO** (remise en service) | 2 h | procédure de restauration automatisée et testée (§8) |
| Durée de conservation | 12 mois | rotation quotidienne / hebdomadaire / mensuelle (§5) |

Le RPO d'une heure suppose les journaux binaires MySQL (`log_bin`,
`binlog_expire_logs_seconds`) : ils permettent de rejouer les transactions
depuis la dernière sauvegarde complète. Ils se configurent côté serveur
(`my.ini` / `my.cnf`), pas dans l'application.

---

## 2. Ce qui est sauvegardé

| Élément | Contenu | Fréquence | Pourquoi |
|---|---|---|---|
| Base `boulangerie_soumahoro` | toutes les données métier (ventes, stocks, écritures, audit) | quotidien 02:30 | irremplaçable |
| `storage/app/private` | pièces jointes des charges, exports générés | quotidien 02:30 | irremplaçable (hors `exports/`, régénérable) |
| `.env` | clés d'application, accès base, messagerie | à chaque changement | contient `APP_KEY` : sans lui, les données chiffrées (2FA) sont illisibles |
| Code source | dépôt Git | à chaque livraison | la version déployée est étiquetée (`git tag`) |

Ne sont **jamais** sauvegardés (régénérables) : `vendor/`,
`node_modules/`, `public/build/`, `storage/framework/`,
`storage/app/private/exports/`.

> **`APP_KEY` fait partie de la sauvegarde.** Le secret TOTP et les codes de
> secours des comptes protégés par la 2FA sont chiffrés avec cette clé : une
> restauration de base avec une autre `APP_KEY` rendrait ces comptes
> inutilisables (il faudrait réinitialiser la 2FA, ce que sait faire
> `App\Domain\Security\TwoFactorService::disable()`).

---

## 3. Script de sauvegarde

Un seul script, idempotent, qui écrit dans `backups/` **hors** du dossier
applicatif (un incident sur le code ne doit pas emporter les sauvegardes) :

```bash
#!/usr/bin/env bash
# /opt/boulangerie/backup.sh — sauvegarde base + fichiers (cahier §47)
set -euo pipefail

APP_DIR="/var/www/boulangerie"
BACKUP_DIR="/srv/backups/boulangerie"
STAMP="$(date +%Y-%m-%d_%H%M)"
RETENTION_DAYS=7

# Les identifiants viennent de l'environnement (jamais en argument : la
# ligne de commande est visible dans « ps »).
: "${DB_HOST:=127.0.0.1}"
: "${DB_DATABASE:=boulangerie_soumahoro}"
: "${DB_USERNAME:?}"
: "${DB_PASSWORD:?}"

mkdir -p "${BACKUP_DIR}/daily" "${BACKUP_DIR}/weekly" "${BACKUP_DIR}/monthly"

# --- 1. Base : --single-transaction laisse l'application en service ---
MYSQL_PWD="${DB_PASSWORD}" mysqldump \
    --host="${DB_HOST}" --user="${DB_USERNAME}" \
    --single-transaction --quick --routines --events \
    --default-character-set=utf8mb4 \
    "${DB_DATABASE}" | gzip -9 > "${BACKUP_DIR}/daily/db_${STAMP}.sql.gz"

# --- 2. Fichiers : pièces jointes et exports, hors régénérable ---
tar czf "${BACKUP_DIR}/daily/files_${STAMP}.tar.gz" \
    -C "${APP_DIR}" \
    --exclude='storage/app/private/exports' \
    storage/app/private \
    .env

# --- 3. Contrôle : un fichier vide ou corrompu ne compte pas comme une sauvegarde ---
for file in "${BACKUP_DIR}/daily/db_${STAMP}.sql.gz" "${BACKUP_DIR}/daily/files_${STAMP}.tar.gz"; do
    test -s "${file}" || { echo "ECHEC fichier vide : ${file}" >&2; exit 1; }
    gzip -t "${file}"
done
gunzip -c "${BACKUP_DIR}/daily/db_${STAMP}.sql.gz" | head -c 64 | grep -q "MySQL dump" \
    || { echo "ECHEC dump illisible" >&2; exit 1; }

# --- 4. Journal des sauvegardes (une ligne par exécution) ---
printf '%s;%s;%s;%s;OK\n' \
    "$(date -Iseconds)" "complet" \
    "${STAMP}" "$(du -sh "${BACKUP_DIR}/daily/db_${STAMP}.sql.gz" | cut -f1)" \
    >> "${BACKUP_DIR}/journal.csv"

# --- 5. Copie hors site : jamais le même disque que la production ---
rsync -az --delete "${BACKUP_DIR}/daily/" "sauvegarde@srv-distante:/srv/backups/boulangerie/daily/"

# --- 6. Rotation ---
find "${BACKUP_DIR}/daily" -type f -mtime "+${RETENTION_DAYS}" -delete

# Hebdomadaire (dimanche) et mensuelle (1er du mois) : copies figées.
if [ "$(date +%u)" -eq 7 ]; then
    cp "${BACKUP_DIR}/daily/db_${STAMP}.sql.gz"   "${BACKUP_DIR}/weekly/"
    cp "${BACKUP_DIR}/daily/files_${STAMP}.tar.gz" "${BACKUP_DIR}/weekly/"
fi
if [ "$(date +%d)" -eq 1 ]; then
    cp "${BACKUP_DIR}/daily/db_${STAMP}.sql.gz"   "${BACKUP_DIR}/monthly/"
    cp "${BACKUP_DIR}/daily/files_${STAMP}.tar.gz" "${BACKUP_DIR}/monthly/"
fi
```

### Sur le poste XAMPP (développement)

Même logique avec les binaires XAMPP — utile pour tester la procédure avant
de la déployer :

```bash
"/c/xampp/mysql/bin/mysqldump.exe" -u root boulangerie_soumahoro \
  --single-transaction --routines --default-character-set=utf8mb4 \
  | gzip -9 > "backups/boulangerie_$(date +%Y-%m-%d).sql.gz"
```

---

## 4. Planification

```cron
# Sauvegarde complète chaque nuit à 02:30
30 2 * * * /opt/boulangerie/backup.sh >> /var/log/boulangerie/backup.log 2>&1

# Scheduler de l'application (exports à purger, rappels) : indispensable,
# sinon les exports restent en attente et sont purgés au bout de 24 h.
* * * * * cd /var/www/boulangerie && php artisan schedule:run >> /dev/null 2>&1

# Worker de file d'attente (exports, emails d'alerte) : à superviser (systemd).
```

La sauvegarde passe **avant** la purge des exports planifiée à 03:15
(`boulangerie:exports-prune`) : elle capture donc aussi les exports de la
veille.

---

## 5. Rétention

| Niveau | Conservation | Copie hors site |
|---|---|---|
| Quotidiennes | 7 jours | oui, chaque nuit |
| Hebdomadaires (dimanche) | 4 semaines | oui |
| Mensuelles (1er du mois) | 12 mois | oui |

Après 12 mois, une sauvegarde n'est plus conservée (les données de gestion
courante n'ont pas d'obligation de conservation au-delà d'un exercice —
à ajuster si la question fiscale §54-16 est tranchée autrement).

---

## 6. Journal et supervision

- **Journal** : `backups/journal.csv`, une ligne par exécution
  (`horodatage;type;estampille;taille;statut`). C'est le registre exigé par
  le cahier §47 : il doit être consultable sans accéder au serveur.
- **Log brut** : `/var/log/boulangerie/backup.log` (sortie du script).
- **Alerte** : une surveillance doit crier si la sauvegarde n'a pas tourné.
  Le contrôle est volontairement bête — l'âge du dernier fichier :

```bash
# /opt/boulangerie/check-backup.sh — à lancer toutes les heures
set -euo pipefail
LATEST="$(ls -t /srv/backups/boulangerie/daily/db_*.sql.gz 2>/dev/null | head -1 || true)"
if [ -z "${LATEST}" ]; then echo "ALERTE : aucune sauvegarde"; exit 1; fi
AGE_H=$(( ( $(date +%s) - $(stat -c %Y "${LATEST}") ) / 3600 ))
if [ "${AGE_H}" -gt 26 ]; then echo "ALERTE : dernière sauvegarde il y a ${AGE_H} h"; exit 1; fi
```

---

## 7. Restauration — procédure

À exécuter sur l'environnement cible, **application en maintenance** :

```bash
cd /var/www/boulangerie

# 1. Geler les écritures : aucune vente ne doit arriver pendant la restauration.
php artisan down --render="errors::503"

# 2. Arrêter les traitements de fond.
php artisan queue:restart
sudo systemctl stop boulangerie-worker.service

# 3. Restaurer la base (le dump contient les DROP/CREATE des tables).
MYSQL_PWD="$DB_PASSWORD" mysql -h "$DB_HOST" -u "$DB_USERNAME" \
    -e "CREATE DATABASE IF NOT EXISTS boulangerie_soumahoro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
gunzip -c /srv/backups/boulangerie/daily/db_2026-10-09_0230.sql.gz \
    | MYSQL_PWD="$DB_PASSWORD" mysql -h "$DB_HOST" -u "$DB_USERNAME" boulangerie_soumahoro

# 3 bis. (Recommandé) rejouer le journal binaire depuis la sauvegarde pour
#        retrouver les ventes de la journée : voir la procédure mysqlbinlog
#        côté serveur, à adapter à la position du binlog de la sauvegarde.

# 4. Restaurer les fichiers (pièces jointes, .env).
tar xzf /srv/backups/boulangerie/daily/files_2026-10-09_0230.tar.gz -C /var/www/boulangerie

# 5. Rafraîchir les caches (le cache « database » peut aussi être vidé).
php artisan optimize:clear
php artisan migrate:status          # doit finir sur « Ran » pour toutes les migrations
php artisan config:cache route:cache # caches de production
php artisan queue:restart

# 6. Contrôles avant réouverture.
php artisan tinker --execute "echo App\Models\User::count(), ' comptes';"
php artisan tinker --execute "echo App\Models\Sale::count(), ' ventes';"
php artisan tinker --execute "echo App\Models\StockMovement::count(), ' mouvements';"

# 7. Réouvrir.
sudo systemctl start boulangerie-worker.service
php artisan up
```

Puis vérifier dans le navigateur : une connexion, l'écran **Ventes** (date
du jour de la sauvegarde), l'écran **Stocks** (un seuil d'alerte), l'écran
**Audit** (les dernières actions journalisées) et **Rapports → Ventes**.

> Les exports en file d'attente ne survivent pas à la restauration : leurs
> lignes reviennent en `pending` et sont purgées au bout de 24 h
> (`boulangerie:exports-prune`). Les redemander depuis l'écran d'origine.

---

## 8. Restauration testée (tous les trois mois)

Une sauvegarde jamais restaurée n'est pas une sauvegarde. Le test se fait
sur une **base séparée**, jamais sur la production :

```bash
mysql -e "DROP DATABASE IF EXISTS boulangerie_restore; CREATE DATABASE boulangerie_restore CHARACTER SET utf8mb4;"
gunzip -c /srv/backups/boulangerie/daily/db_<derniere>.sql.gz | mysql boulangerie_restore

MSYS_NO_PATHCONV=1 DB_DATABASE=boulangerie_restore php artisan migrate:status
```

Liste de contrôle à cocher et à signer dans le journal :

- [ ] le dump s'ouvre (`gzip -t`) et contient les tables attendues ;
- [ ] la restauration se termine sans erreur ;
- [ ] `php artisan migrate:status` ne signale aucune migration en attente ;
- [ ] les comptages (comptes, ventes, mouvements de stock) sont cohérents
      avec la production du jour sauvegardé ;
- [ ] une connexion fonctionne, y compris pour un compte protégé par la 2FA
      (les colonnes chiffrées se déchiffrent → l'`APP_KEY` restaurée est la bonne) ;
- [ ] l'écran **Rapports** répond ;
- [ ] la durée totale est mesurée et comparée au RTO de 2 h ;
- [ ] le résultat est consigné dans `backups/journal.csv` (ligne `test`).

Le rythme trimestriel vaut pour un ERP en exploitation ; après chaque
changement majeur (montée de version MySQL, migration de serveur), refaire
le test immédiatement.

---

## 9. Procédure de reprise après incident

1. **Constat** — noter l'heure, le symptôme, ce qui est encore accessible.
   Ne rien redémarrer ni réinstaller avant de savoir ce qui est perdu.
2. **Diagnostic** — `storage/logs/laravel.log`, le log MySQL, l'espace disque
   (`df -h`), l'état des services (`systemctl status`).
3. **Arrêt propre** — `php artisan down` (les utilisateurs voient une page
   de maintenance plutôt qu'une erreur), `systemctl stop` du worker.
4. **Choix de la sauvegarde** — la plus récente *dont la restauration a été
   validée* ; en cas de doute sur une corruption silencieuse, préférer la
   veille et rejouer le binlog.
5. **Restauration** — §7, étapes 3 à 6.
6. **Vérification** — la liste de contrôle de §8, sur la production.
7. **Réouverture** — `php artisan up`, redémarrer le worker, prévenir les
   utilisateurs de ce qui a été perdu (le cas échéant, la fenêtre RPO).
8. **Post-mortem** — consigner dans le journal : cause, données perdues,
   durée d'indisponibilité, action corrective, mise à jour de ce document
   si la procédure a montré une faiblesse.

---

## 10. Points de vigilance

- **Une sauvegarde hors site, toujours** : une copie sur le même disque ne
  protège ni d'une panne matérielle, ni d'un rançongiciel.
- **Ne pas sauvegarder le mot de passe en clair** dans un script versionné :
  les identifiants viennent de l'environnement du serveur.
- **`APP_KEY` et `.env`** : les sauvegarder avec la base (§2), sinon les
  données chiffrées deviennent illisibles.
- **Ne jamais restaurer sur la production sans avoir testé** la sauvegarde
  choisie sur une base séparée (§8).
- **Surveiller** : une sauvegarde silencieusement à l'arrêt est le risque le
  plus courant (§6).
