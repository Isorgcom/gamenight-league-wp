# GameNight League — WordPress plugin

A WordPress plugin that turns any WordPress site into a public league page for a poker league hosted on **[gamenight.poker](https://gamenight.poker/)**, plus a wp-admin management UI.

[![PHP Lint](https://github.com/Isorgcom/gamenight-league-wp/actions/workflows/php-lint.yml/badge.svg)](https://github.com/Isorgcom/gamenight-league-wp/actions/workflows/php-lint.yml)

## What it does

**Public surface (shortcodes for any page or post):**

- League summary, roster, posts, rules
- Upcoming events with RSVP counts
- Single-event detail with optional invitee list
- RSVP form for anonymous visitors (creates them as a league member on submit)

**Admin surface (wp-admin → GameNight menu):**

- Browse and manage the roster (change member roles)
- Create, edit, and delete events
- Per-event invitee management (add existing members, add new people, override RSVPs)

See [`gamenight-league/readme.txt`](gamenight-league/readme.txt) for the full feature list, every shortcode and its attributes, the admin walkthrough, and the REST endpoint reference.

## Installation

1. Download the latest [release ZIP](https://github.com/Isorgcom/gamenight-league-wp/releases) (or clone this repo and zip the `gamenight-league/` folder).
2. In WordPress, go to **Plugins → Add New → Upload Plugin** and upload the ZIP.
3. Activate.
4. Visit **Settings → GameNight League** and paste your API key (mint one from your league page on gamenight.poker → **API** tab).
5. Click **Run test** to verify the connection, then drop shortcodes onto pages or use the **GameNight** admin menu.

## Requirements

- WordPress 6.0+
- PHP 7.4+ (tested on 7.4, 8.0, 8.1, 8.2, 8.3 via CI)
- A GameNight league with an API key (read+write recommended; read-only works for the public shortcodes)

## Architecture (for contributors)

- All API calls go through PHP via `Api_Client` — the API key never reaches the browser.
- Reads are cached in WP transients for 60 seconds (matches the API's own `Cache-Control` hint); writes flush the cache.
- Public RSVP route: `POST /wp-json/gamenight/v1/rsvp` — anonymous-friendly, protected by WP nonce + per-IP rate limit.
- Admin routes: `/wp-json/gamenight/v1/admin/*` — gated by `manage_options` capability + WP nonce.
- Templates in `gamenight-league/templates/` are theme-overridable at `{your-theme}/gamenight-league/{template}.php`.

## Repo layout

```
gamenight-league/      ← the plugin (this is what gets zipped & shipped)
├── gamenight-league.php
├── readme.txt
├── includes/          ← classes (Api_Client, Settings, REST controller, shortcodes, admin)
├── templates/         ← theme-overridable PHP templates
└── assets/            ← CSS + vanilla JS
.github/workflows/     ← PHP lint CI
```

## Building a distribution ZIP

```powershell
# From the repo root
Compress-Archive -Path gamenight-league -DestinationPath gamenight-league.zip -Force
```

The `.distignore` file lists exclusions for the eventual WordPress.org SVN build.

## Related

- **GameNight API** (the backend this plugin talks to): https://gamenight.poker, docs at https://github.com/Isorgcom/GameNight/blob/main/DOCS.md

## License

GPL-2.0-or-later. See [`gamenight-league/LICENSE`](gamenight-league/LICENSE).
