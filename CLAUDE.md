# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

# Project Instructions

This is the **81 TRSS Courseware Development Project Tracker** — a single-user internal web app deployed on a Debian VPS at `projects.taylortoast.tech`. It tracks courseware development projects, manages requests from customer squadrons, and provides a public-facing landing page and request submission form.

## Stack

- Bare PHP 8.2-FPM (Alpine, Docker)
- SQLite (WAL mode, FK enforcement)
- Server-rendered HTML/CSS/JS (no frontend framework)
- Nginx reverse proxy (nginx-web container → port 8082)
- Dedicated subdomain: `projects.taylortoast.tech`

## Deployment

- App runs in Docker Compose: `tracker-php` (PHP-FPM) + `tracker-nginx` (Nginx)
- Port binding: `8082:80` (accessible from Docker bridge network)
- External proxy: `/srv/nginx-conf/projects.conf` → `http://host.docker.internal:8082`
- SQLite DB: `./data/tracker.db` — bind-mounted, owned by uid 82 (www-data)
- To rebuild/restart: `cd /srv/webapps/projects && docker compose up -d --build`
- PHP-FPM container name: `projects-tracker-php-1`

## Architecture

```
public/index.php          — Front controller / router ($_GET['page'])
src/config.php            — DB path, session TTL, timezone, password hash
src/db.php                — PDO singleton, schema auto-init, query helpers
src/auth.php              — Session auth, 30-min inactivity timeout
src/helpers.php           — e(), format_ts(), format_date(), markdown export
pages/                    — One file per page/action
templates/                — layout.php, flash.php, project_form_fields.php, password_form.php
public/css/style.css      — Dark theme, all custom CSS
public/js/app.js          — toggleOther(), filterPocsBySquadron()
schema.sql                — Authoritative DB schema (auto-applied on first run)
```

## Routing

| URL | Auth | Page |
|-----|------|------|
| `?page=landing` | No | Public landing / capabilities |
| `?page=request` | No | Customer project request form |
| `?page=thankyou` | No | Post-submission confirmation |
| `?page=public` | No | Active projects card view |
| `?page=login` | No | Login |
| `?page=dashboard` | Yes | Project list + filters |
| `?page=project&action=new` | Yes | Create project (supports `?req=ID` prefill) |
| `?page=project&action=edit&id=X` | Yes | Edit project |
| `?page=project&action=view&id=X` | Yes | Project detail + notes |
| `?page=project&action=delete&id=X` | Yes | Delete project |
| `?page=requests` | Yes | Review incoming requests |
| `?page=requests&id=X` | Yes | Request detail / edit |
| `?page=settings&section=X` | Yes | Global settings (statuses, squadrons, POCs, courses, tags, targets, security) |
| `?page=export` | Yes | Markdown export download |

## Key Data Relationships

- **Projects** → squadron (FK), status (FK), many POCs, many courses, many software tags, many deployment targets, many notes
- **POCs** → squadron (FK); filtered on project form by selected squadron
- **Courses** → squadron (FK), course_number; filtered on project form by selected squadron
- **Project Requests** → optional mapped squadron (FK), course (FK), status (FK); workflow status text (pending/reviewed/converted)
- Junction tables: `project_pocs`, `project_courses`, `project_software_tags`, `project_deployment_targets`

## "Other" Inline-Add Pattern

Used consistently across squadrons, POCs, courses, software tags, deployment targets. Selecting "Other…" reveals a text input; on form submit the new record is inserted (`INSERT OR IGNORE`) and the resulting ID is used. Applied on: project new/edit forms and request review form.

## Squadron Filtering

Selecting a squadron on project new/edit or request review filters both POC chips and Course chips to show only items assigned to that squadron (plus unassigned items with `squadron_id = 0`). JS: `filterPocsBySquadron()` in `app.js`, triggered by `onchange` on the squadron `<select>` and on `DOMContentLoaded` for edit forms.

## Settings Sections

| Section | Extra Fields | Notes |
|---------|-------------|-------|
| Statuses | sort_order | Drives project + request status dropdowns |
| Squadrons | — | Referenced by projects, POCs, courses, requests |
| POCs | squadron, flight, phone | Filtered by squadron on project form |
| Courses | course_number, squadron | Filtered by squadron on project form |
| Software Tags | — | |
| Deployment Targets | — | |
| Security | — | Change login password (rewrites `src/config.php`) |

## Password

Stored as bcrypt hash in `src/config.php` as `PASSWORD_HASH`. Change via Settings → Security or manually:
```bash
docker exec projects-tracker-php-1 php -r "echo password_hash('newpass', PASSWORD_BCRYPT);"
```

## Working Style

- Plan before coding.
- Make minimal, focused changes.
- Read existing structure before creating new files.
- Prefer updating existing files over introducing unnecessary new ones.
- Ask before changing deployment, Nginx, SSL, or Docker configuration.
- Confirm before destructive operations. Never delete data or files without explicit approval.

## Token Efficiency

- Read `public/index.php`, `schema.sql`, `src/config.php`, and `src/db.php` first to orient.
- Read shared includes (`project_form_fields.php`, `helpers.php`) before touching form pages.
- Do not scan the whole repo unless necessary.
