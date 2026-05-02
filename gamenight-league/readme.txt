=== GameNight League ===
Contributors: gamenight
Tags: gamenight, league, poker, events, rsvp
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 0.3.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display your GameNight league roster, events, posts, and rules on any WordPress site, and accept RSVPs — powered by the GameNight API.

== Description ==

GameNight League turns any WordPress site into a public league page for a league hosted on [gamenight.poker](https://gamenight.poker/).

Drop in a few shortcodes and you get:

* League summary
* Upcoming events with RSVP counts
* Roster
* Posts and announcements
* Rules
* RSVP form so visitors can respond yes / no / maybe

The plugin also adds a **GameNight** menu in wp-admin where league managers can:

* Browse the roster and change member roles (member ↔ manager)
* See upcoming and past events with RSVP counts
* Create, edit, and delete events (including poker details)
* Manage event invitees: add/remove people and override their RSVPs
* Author league posts: create/edit/delete with a rich-text editor, pin or hide posts, schedule future posts

Each WordPress site connects to one GameNight league via an API key minted from the league's admin page on gamenight.poker.

== Shortcodes ==

Shortcodes render the public-facing parts of your league. Drop them into any page, post, or widget.

* `[gamenight_league]` — League name, description, member count.
* `[gamenight_events]` — Upcoming events with RSVP counts. Attributes:
  * `from="YYYY-MM-DD"` — start of date window (default: today).
  * `to="YYYY-MM-DD"` — end of date window (default: +90 days).
  * `limit="N"` — cap how many events render (0 = no cap).
  * `show_past="yes"` — include past events that happen to fall in the window.
* `[gamenight_event id="123"]` — Single event detail. Attributes:
  * `id` (required) — the GameNight event id.
  * `show_invitees="yes"` — also list invitees with their RSVP status.
* `[gamenight_roster]` — League roster. Attributes:
  * `hide_pending="yes"` — omit members who haven't accepted their invite yet.
  * `sort="name|role|joined"` — sort order (default: `name`).
* `[gamenight_posts limit="10" offset="0"]` — League posts/announcements.
* `[gamenight_rules]` — The league's rules post.
* `[gamenight_rsvp event_id="123"]` — A standalone RSVP form. Anonymous visitors fill in name + email/phone + yes/maybe/no; the plugin creates them as a league member (idempotent on email/phone) and records the RSVP. Pair it with `[gamenight_event id="123"]` on the same page.

== Admin pages (managing your league) ==

After activation, a **GameNight** menu appears in your wp-admin sidebar (visible to users with the `manage_options` capability). It has three pages:

= Members =

A table of every member of your league: name, role, join date, status (active or pending). For non-owner active members, the **Role** column is a dropdown — change it to `Member` or `Manager` and the change saves instantly. Owners are read-only (the API doesn't allow changing the owner role).

= Events =

Upcoming and past events as a sortable table with RSVP counts (yes / maybe / no). Each row has three actions:

* **Edit** — opens the event in the editor.
* **Invitees** — drills into the per-event invitee management page (see below).
* **Delete** — confirms with the event title, then deletes. **Future events queue cancellation notifications to invitees**; past events delete silently.

The **+ New event** button at the top of the page opens the event editor in create mode.

= New event / Edit event =

A single form covers both creating and editing. Fields:

* **Title**, **Start**, **End** (optional) — start/end use a datetime picker in your site's timezone.
* **Description**, **Color** (limited to GameNight's allowed palette).
* **Poker event?** — toggle that reveals **Buy-in**, **Tables**, **Seats per table**, **Game type**.
* **RSVP deadline (hours before start)** — locks RSVPs once the deadline passes. Leave blank to allow RSVPs right up to start time.
* **Waitlist** (poker only) — auto-waitlist invitees beyond `seats × tables`.
* **Reminders** — toggle reminder notifications, plus a comma-separated **Reminder offsets (minutes)** field (e.g. `2880, 720` for 48h and 12h before start).

On save, the plugin redirects you back to the events list.

= Posts =

A table of league posts. Pinned posts are flagged with a "pinned" badge. Each row has **Edit** and **Delete** actions (delete confirms and cascades to comments).

The **+ New post** button opens the post editor.

= New post / Edit post =

A standard form using WordPress's built-in rich-text editor (the same TinyMCE you're used to from regular WP posts):

* **Title** (required, up to 200 characters)
* **Content** (rich text — server sanitizes scripts/handlers/untrusted iframes before storage)
* **Pinned** — sorts above unpinned posts
* **Hidden** — soft-removes the post from public feeds without deleting it (useful for drafts)
* **Publish date** (only on new posts) — leave blank to publish now, or set a future date to schedule. The date is locked once the post is created.

Hidden posts are not returned by the API, so they will not appear in the Posts list view. Unhide them by editing — but you'll need the post ID to do so (we can't list hidden posts without an API change).

= Event invitees (drill-down from Events → Invitees) =

For one event:

* A table of current invitees with **Role**, **RSVP**, and **Approval status**. The RSVP column is a dropdown — pick yes / maybe / no / no-response and the change saves instantly. **Remove** removes the invitee (with confirm).
* **Add existing member** — dropdown of league members not yet invited to this event. Members with `pending` status appear in the list grayed out, with a hint to use the **Add new person** form below to materialize them as real users.
* **Add new person** — name + email + phone form. On submit, the plugin creates the user in your league (idempotent: if email or phone matches someone already in the league, no duplicate is created — that existing person is invited instead) and adds them as an invitee in one step. The new person becomes a permanent league member as a side-effect.

== Theme template overrides ==

Every shortcode renders through a PHP template you can override from your theme. Copy any file from the plugin's `templates/` folder to a folder named `gamenight-league/` in your theme:

`wp-content/themes/your-theme/gamenight-league/events-list.php`

The plugin checks your theme first via `locate_template()` and only falls back to the bundled template if your theme doesn't provide one. Available template names:

* `league.php`, `events-list.php`, `event-card.php`, `event-detail.php`
* `roster.php`, `posts-list.php`, `post.php`, `rules.php`, `rsvp-form.php`

Each template receives the variables documented at the top of the bundled file.

== REST endpoints ==

The plugin exposes its own WordPress REST routes under the `gamenight/v1` namespace. Two are public (or capability-gated):

= Public =

* `POST /wp-json/gamenight/v1/rsvp` — Anonymous RSVP submission. Used by the `[gamenight_rsvp]` form. Body: `{ event_id, display_name, email?, phone?, rsvp }`. Protected by a WP nonce + per-IP rate limit (5 requests / 10 minutes).

= Admin (require `manage_options` + `X-WP-Nonce` header) =

* `GET    /wp-json/gamenight/v1/admin/members`
* `PATCH  /wp-json/gamenight/v1/admin/members/{user_id}` — body: `{ role: "member"|"manager" }`
* `GET    /wp-json/gamenight/v1/admin/events?from=&to=&include_past=1`
* `POST   /wp-json/gamenight/v1/admin/events` — create event
* `GET    /wp-json/gamenight/v1/admin/events/{id}`
* `PATCH  /wp-json/gamenight/v1/admin/events/{id}`
* `DELETE /wp-json/gamenight/v1/admin/events/{id}`
* `GET    /wp-json/gamenight/v1/admin/events/{id}/invitees`
* `POST   /wp-json/gamenight/v1/admin/events/{id}/invitees` — body: `{ user_id }`
* `POST   /wp-json/gamenight/v1/admin/events/{id}/invitees/new-person` — body: `{ display_name, email?, phone? }` (creates user + invites in one call)
* `PATCH  /wp-json/gamenight/v1/admin/events/{id}/invitees/{user_id}` — body: `{ rsvp?, event_role? }`
* `DELETE /wp-json/gamenight/v1/admin/events/{id}/invitees/{user_id}`
* `GET    /wp-json/gamenight/v1/admin/posts`
* `POST   /wp-json/gamenight/v1/admin/posts` — body: `{ title, content, pinned?, hidden?, published_at? }`
* `GET    /wp-json/gamenight/v1/admin/posts/{id}`
* `PATCH  /wp-json/gamenight/v1/admin/posts/{id}` — body: `{ title?, content?, pinned?, hidden? }` (publish date is locked after creation)
* `DELETE /wp-json/gamenight/v1/admin/posts/{id}`

These are server-side proxies to the GameNight API (https://github.com/Isorgcom/GameNight/blob/main/DOCS.md). The GameNight API key never reaches the browser — calls flow Browser → WP REST → PHP → GameNight.

== Installation ==

1. Install and activate the plugin.
2. Visit **Settings → GameNight League**.
3. Paste your API key (mint one from your league page on gamenight.poker → API tab).
4. Click **Run test** to verify the connection.
5. Place shortcodes on any page or post, and/or use the **GameNight** admin menu to manage your league.

== Privacy ==

This plugin connects to the GameNight API at the URL you configure (default: `https://gamenight.poker`).

**What is sent to GameNight:**

* On every page view that contains a shortcode, the plugin makes a server-side request to the GameNight API to fetch league data. Your site's URL is included in the User-Agent header for support purposes.
* When a visitor submits the RSVP form, the plugin forwards the visitor's name, email, phone (if provided), and RSVP choice to the GameNight API. This is how the visitor is identified to the league.

**What is NOT collected by this plugin:**

* The plugin does not store visitor email or phone in the WordPress database. It forwards the data to GameNight and forgets it.
* The plugin does not set tracking cookies or load any third-party scripts.
* The plugin does not phone home with usage data.

The site administrator is responsible for disclosing this data flow to visitors per applicable privacy laws (GDPR, CCPA, etc.).

== Frequently Asked Questions ==

= Where do I get an API key? =

Visit your league page on gamenight.poker, open the **API** tab, and click **Mint key**. Only league owners can mint keys.

= Can I use one plugin install for multiple leagues? =

No. Each API key is bound to one league, and the plugin stores one site-wide key. Use separate WordPress sites for separate leagues.

= How often is data refreshed? =

The plugin caches API responses for 60 seconds by default (matching the API's own cache hint). You can change this in Settings. RSVP submissions invalidate the cache immediately.

= Is the API key safe in my WordPress database? =

Yes — it's stored in `wp_options` like any other plugin setting, and only users with the `manage_options` capability can read or change it. The key is never sent to the visitor's browser; all API calls go through PHP.

= Can a non-admin WP user manage the league? =

Not in this version. The GameNight admin menu and all admin REST endpoints require the `manage_options` capability, which by default only Administrators have. If you want to grant management access to a non-Administrator WP user, use a role-management plugin to add `manage_options` to their role (this also grants other site-wide settings access — choose carefully). A future release may add a dedicated `gnl_manage_league` capability.

= Can I customize the look of the public shortcodes? =

Yes, two ways: (1) write CSS targeting the `.gnl-*` classes the plugin emits; or (2) override the bundled templates from your theme — see the **Theme template overrides** section above.

== Changelog ==

= 0.3.1 =
* New: event editor exposes RSVP deadline, waitlist toggle, reminders toggle, and reminder offsets (comma-separated minutes).
* New: "as event manager" checkbox on the event invitee picker and on the "Add new person" form, so a manager can be invited with the right role from the start.

= 0.3.0 =
* New: GameNight → Posts admin page for full post authoring (create / edit / delete).
* New: rich-text editor (`wp_editor`) for post content, with server-side sanitization.
* New: pin posts to the top of the public posts list, hide posts (soft-delete), schedule future posts via optional Publish date on creation.
* New REST endpoints: `GET/POST /wp-json/gamenight/v1/admin/posts` and `GET/PATCH/DELETE /wp-json/gamenight/v1/admin/posts/{id}`.

= 0.2.0 =
* New: GameNight admin menu (gated by `manage_options`) — Members, Events, New event.
* New: change member roles (member ↔ manager) inline.
* New: create, edit, and delete events from wp-admin (with poker details).
* New: per-event invitee management — add/remove invitees, override RSVPs, "Add new person" form that creates a league member and invites them in one step.
* New: pending members appear in the invitee picker as disabled options, pointing to the "Add new person" form.
* New REST endpoints under `/wp-json/gamenight/v1/admin/*` (capability-gated, WP nonce required).
* Docs: full admin walkthrough, theme template-override guide, and REST endpoint reference added to readme.

= 0.1.0 =
* Initial release. Read shortcodes, RSVP form with anonymous-visitor signup.
