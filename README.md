# 81 TRSS Courseware Development — Project Tracker

An internal web application for the 81st Training Support Squadron (81 TRSS) Courseware Development team at Keesler AFB. Tracks active courseware development projects, manages incoming project requests from customer squadrons, and provides a public-facing landing page for customers to learn about the team and submit requests.

---

## Features

### Developer (Authenticated)
- **Dashboard** — filterable project list with search, status, squadron, software tag, and deployment target filters
- **Project Management** — create, edit, view, and delete projects with full field support:
  - Project number, project name, squadron, POC(s), description, status, start/completion dates
  - Courses (filtered by squadron), software tags, deployment targets
  - Append-only timestamped notes
- **Squadron Filtering** — selecting a squadron on project forms filters POC and Course chips to only show relevant options
- **Inline "Other" Add** — any dropdown or chip group has an "Other" option that inserts a new record to the database on the fly
- **Requests** — review and manage project requests submitted by customers; map free-text submissions to DB records (squadron, course, project status); create a project directly from a request
- **Settings** — manage all global reference data: Statuses, Squadrons, POCs, Courses (with course numbers), Software Tags, Deployment Targets, and password change
- **Markdown Export** — download a full `.md` export of all projects and notes
- **Public View** — card-based view of all open (no completion date) projects

### Public (No Login Required)
- **Landing Page** — team introduction, capability overview, and call-to-action (`?page=landing`)
- **Request Form** — customer-facing form to submit a project idea or meeting request (`?page=request`)
- **Thank You Page** — post-submission confirmation (`?page=thankyou`)
- **Active Projects** — live view of in-progress projects (`?page=public`)

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Language | PHP 8.2 (FPM, Alpine) |
| Database | SQLite 3 (WAL mode) |
| Web Server | Nginx (Alpine) |
| Containerization | Docker Compose |
| Frontend | Server-rendered HTML, custom CSS (dark theme), vanilla JS |

---

## Project Structure

```
├── public/                  # Document root (served by Nginx)
│   ├── index.php            # Front controller / router
│   ├── css/style.css        # All styles — dark theme
│   └── js/app.js            # toggleOther(), filterPocsBySquadron()
├── src/
│   ├── config.php           # DB path, session TTL, timezone, password hash
│   ├── db.php               # PDO connection, schema init, query helpers
│   ├── auth.php             # Session auth, 30-min inactivity timeout
│   └── helpers.php          # e(), format_ts(), format_date(), markdown export
├── pages/                   # One file per page/route
│   ├── login.php
│   ├── dashboard.php
│   ├── project_new.php
│   ├── project_edit.php
│   ├── project_view.php
│   ├── project_delete.php
│   ├── note_add.php
│   ├── settings.php
│   ├── export.php
│   ├── requests.php
│   ├── public.php
│   ├── public_landing.php
│   ├── public_request.php
│   └── public_thankyou.php
├── templates/
│   ├── layout.php           # HTML shell (nav, head, footer)
│   ├── flash.php            # Flash message partial
│   ├── project_form_fields.php  # Shared project form fields
│   └── password_form.php    # Password change form
├── data/                    # SQLite DB lives here (gitignored)
│   └── tracker.db
├── schema.sql               # Database schema — auto-applied on first run
├── Dockerfile               # PHP 8.2-FPM Alpine + pdo_sqlite
├── docker-compose.yml       # tracker-php + tracker-nginx services
└── tracker-app.conf         # Nginx config inside container
```

---

## Deployment

### Prerequisites
- Docker + Docker Compose on the host
- An existing Nginx reverse proxy container that can reach `host.docker.internal:8082`
- SSL certificate already in place for the subdomain

### First Run

```bash
# Clone the repo
git clone https://github.com/TRSS-TSU/projects.git /srv/webapps/projects
cd /srv/webapps/projects

# Create the data directory and set ownership for www-data (uid 82 in Alpine)
mkdir -p data
chown 82:82 data

# Start the containers
docker compose up -d

# The database is created automatically on first request (schema.sql is applied)
```

### Nginx External Proxy

The host Nginx (or nginx-web container) needs a server block pointing to `http://host.docker.internal:8082`. A sample config is provided at `/srv/nginx-conf/projects.conf` on the server.

### Updating

```bash
cd /srv/webapps/projects
git pull
docker compose up -d --build
```

> **Note:** The `data/` directory is gitignored. The SQLite database persists on the host filesystem and is never touched by a deploy.

---

## Configuration

All runtime configuration lives in `src/config.php`:

| Constant | Default | Description |
|----------|---------|-------------|
| `DB_PATH` | `../data/tracker.db` | Path to SQLite file |
| `SESSION_TTL` | `1800` | Inactivity timeout in seconds (30 min) |
| `APP_TIMEZONE` | `America/Chicago` | Display timezone for timestamps |
| `PASSWORD_HASH` | bcrypt hash | Login password hash |

### Changing the Password

Via the UI: **Settings → Security**

Via CLI:
```bash
docker exec projects-tracker-php-1 php -r "echo password_hash('newpassword', PASSWORD_BCRYPT);"
# Paste the output into src/config.php as the PASSWORD_HASH value
```

---

## Database Schema

The schema is in `schema.sql` and applied automatically on first run. Key tables:

- `projects` — core project records
- `statuses`, `squadrons`, `pocs`, `courses`, `software_tags`, `deployment_targets` — global reference data managed in Settings
- `project_pocs`, `project_courses`, `project_software_tags`, `project_deployment_targets` — many-to-many junction tables
- `notes` — append-only notes per project
- `project_requests` — customer-submitted project requests with developer review fields

---

## Development Notes

- All timestamps stored as UTC in SQLite; displayed in `America/Chicago` (CST/CDT)
- Squadron selection on project and request review forms filters POC and Course chip options via `filterPocsBySquadron()` in `app.js`
- The "Other" inline-add pattern is used consistently: selecting Other reveals a text input; on submit a new DB record is created and its ID is used
- Deleting a global setting is blocked if it is referenced by any project; optional references in `project_requests` are NULLed out automatically
- Password hash is stored in `src/config.php` — the Settings → Security page rewrites that file in place

---

*81 TRSS Courseware Development — Keesler AFB, MS*
