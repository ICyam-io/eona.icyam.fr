# EonA

Application web personnelle de suivi santé — déficit calorique, poids, tension artérielle.

Deux utilisateurs initiaux (Alexandre + sa femme). Architecture multi-user dès la v1.

---

## Stack

| Couche | Technologie |
|---|---|
| Backend | PHP 8.3 (Apache) |
| Base de données | MariaDB 11.6 — instance partagée VPS (`mariadb` container) |
| Automatisation | n8n — webhook GPT-4o Vision pour l'analyse des repas |
| Infra | Docker + Traefik (VPS OVH) |
| CSS | Variables dark mode ICyam, Lexend, mobile-first |

---

## URLs

| Environnement | URL | Auth |
|---|---|---|
| Dev desktop | `https://eona.icyam.fr` | Basic auth : `icyam` / `dev2026!` |
| Dev mobile | `https://eona.icyam.fr` | Même URL — tester depuis navigateur natif mobile |
| Prod | `https://eona.icyam.fr` | Basic auth désactivée au go-live |

> Pour tester depuis un mobile : ouvrir `https://eona.icyam.fr` dans Safari (iOS) ou Chrome (Android).
> Le navigateur demandera l'identifiant/mot de passe de la basic auth, puis accèdera à l'app.

---

## Structure

```
/opt/stacks/eona/
  compose.yml         # Stack Docker
  Dockerfile          # PHP 8.3 Apache + pdo_mysql + exif
  apache.conf         # VirtualHost — uploads hors DocumentRoot
  .env                # Variables sensibles — jamais dans git
  .gitignore
  README.md           # Ce fichier — à maintenir à chaque évolution
  uploads/
    pending/          # Photos éphémères en attente GPT-4o nocturne
  www/
    index.php         # Redirect → /journal.php ou /login.php
    login.php
    logout.php
    register.php
    daily.php         # Onglet 1 — données journalières
    journal.php       # Onglet 2 — repas + tension
    dashboard.php     # Onglet 3 — tableau de bord
    profile.php
    assets/
      css/main.css    # Variables CSS charte EonA
      js/main.js
    includes/
      config.php      # Constantes app
      db.php          # Connexion PDO singleton
      auth.php        # Sessions 30 jours, login/logout
      helpers.php     # BMR, score sommeil, formatage
```

---

## Base de données

Instance MariaDB partagée du VPS (`mariadb` container, `private-net`).

Base : `eona_db` — User : `eona_user`

**6 tables :** `users` · `sessions` · `daily_logs` · `meals` · `blood_pressure` · `pending_analyses`

Schéma complet → `02_PROJETS/EonA/2026-04-25_schema_bdd_eona.md` (vault Obsidian)

---

## Démarrage

```bash
cd /opt/stacks/eona

# Premier lancement (build + démarrage)
docker compose up -d --build

# Relancer après modification PHP (rechargement à chaud via volume)
# Aucune action nécessaire — les fichiers www/ sont montés en volume

# Rebuild après modification Dockerfile ou composer
docker compose up -d --build

# Logs en temps réel
docker logs -f eona

# Arrêter
docker compose down
```

---

## Variables d'environnement

Copier `.env.example` → `.env` et renseigner les valeurs manquantes.

| Variable | Description |
|---|---|
| `DB_PASSWORD` | Mot de passe `eona_user` — voir credentials vault |
| `OPENAI_API_KEY` | Clé API OpenAI pour GPT-4o Vision |
| `N8N_WEBHOOK_URL` | URL du webhook n8n (à créer lors du WF-10x) |
| `N8N_WEBHOOK_SECRET` | Token secret du webhook n8n |
| `BASIC_AUTH_USERS` | Hash htpasswd pour la basic auth dev |

---

## Roadmap

- [x] Phase 0 — Fondations (BDD + Docker + structure PHP) `2026-04-25`
- [ ] Phase 1 — Fonctionnalités core (formulaires, webhook n8n, graphiques) `deadline: 2026-05-31`
- [ ] Phase 2 — Polissage + déploiement prod `deadline: 2026-06-30`
- [ ] Phase 3 — Stabilisation + v2 OpenFoodFacts `deadline: 2026-07-30`
