# Deployment split: FTP frontend + Railway backend

## Domains

- Upload the portal frontend to the IONOS FTP document root serving `www.zerothelegend.com`.
- Use `https://games.zerothelegend.com` for browser games and APIs. Attach this domain to Railway and configure only its DNS target shown by Railway in IONOS. Leave `api.zerothelegend.com` on its current IONOS Nextcloud service.

The DNS destinations are assigned by Railway and must be copied from its custom-domain settings; do not guess CNAME targets.

## Railway

1. Push the whole `tttt` project to a GitHub repository (or connect the repository that contains this `tttt` folder) and create a Railway project from it.
2. Set the Railway service root directory to `/tttt` if the Git repository root is its parent folder; leave it at `/` if `tttt` itself is the Git repository root. Railway must see `Dockerfile` and `railway.json` together at the selected root.
3. Deploy the service. The Docker image serves the PHP application from `public/`.

Set these variables in the Railway service. Use the production database values from the hosting provider, never values from `.env.example`.

| Variable | Required | Purpose |
|---|---:|---|
| `DB_HOST` | yes | MySQL server |
| `DB_PORT` | yes | MySQL port, usually `3306` |
| `DB_NAME` | yes | Database name |
| `DB_USER` | yes | Database user |
| `DB_PASS` | yes | Database password |
| `INSTALL_KEY` | if installer is used | Random installer key |
| `PAYMENTS_MODE` | no | Defaults to `demo` |
| `AUTH_SECRET` | Agar API | Random secret, at least 48 characters |
| `SERVER_KEY` | Agar API | Different random secret, at least 48 characters |
| `ALLOWED_ORIGINS` | yes | Comma-separated exact origins: `https://zerothelegend.com,https://www.zerothelegend.com,https://games.zerothelegend.com` |
| `FRONTEND_URL` | no | `https://www.zerothelegend.com` |
| `FB_APP_ID` | Facebook login | Facebook app ID |
| `FB_APP_SECRET` | Facebook login | Facebook app secret |

Keep `.env` local and out of Git. Configure environment variables in Railway's service settings.

## Authentication and API checks

The active API bootstrap provides the shared database, session, authentication, and JSON helpers used by the login and profile endpoints. Its login rate limiter creates an `auth_attempts` table automatically, so the Railway database user needs permission to create tables. Before relying on account persistence, configure Railway's MySQL variables and verify registration, login, account-name display, refresh/session restoration, and logout against the production database. The existing database must include the columns used by `public/api/auth.php` and the profile endpoints; keep a database backup before schema changes.

## FTP

In IONOS, open the FTP access details for the hosting package assigned to `www.zerothelegend.com`, connect with an FTP client (for example FileZilla), and open the document root shown in the IONOS hosting panel (often `htdocs`, but use the path IONOS displays). Copy these local items from `tttt/public/` to that document root, preserving the folder structure:

- `index.php`
- the complete `portal/` folder
- `assets/js/deployment.js`
- `assets/js/auto-locale.js`

Do **not** upload `api/`, `games/`, `.env`, `.git`, `Dockerfile`, `railway.json`, or `DEPLOYMENT.md` to the FTP web root. Railway receives the full project from its connected Git repository and Dockerfile.

The portal loads `assets/js/deployment.js`, which sends API requests and game links to `games.zerothelegend.com`, and `assets/js/auto-locale.js` for automatic localization. Keep both files on the FTP site. On every page load and when the page is shown, gains focus, or reconnects to the network (with a 30-second request limit), automatic localization queries `ipapi.co` again so a changed public IP/network can select a new language. The provider receives the visitor's IP; the site does not store the IP, country, or selected language. If GeoIP lookup fails, the browser language is used. Only languages already supported by the portal's dictionaries are selected; this does not translate unsupported content.

## Before switching DNS

1. Deploy the Railway service and set all required variables.
2. Attach `games.zerothelegend.com` in Railway and update its DNS record in IONOS to Railway's displayed target. Do not change `api.zerothelegend.com`.
3. Upload only the listed frontend files and folders to the IONOS web root as described above.
4. Confirm the FTP site loads over HTTPS and the two frontend scripts return successfully. Test GeoIP localization and gameplay.
5. Open `https://games.zerothelegend.com/api/auth.php?action=me`; it must return JSON, not an IONOS placeholder page. If it returns HTML or cannot be reached, the `games` DNS record is not connected to the Railway service yet.
6. Verify login, registration, account-name display, profile persistence, and logout against Railway. Verify score saving and Agar login separately before announcing those flows ready.

If Facebook login is enabled, add `https://games.zerothelegend.com/api/fb_callback.php` as the OAuth redirect URI in the Facebook app settings.
