# WordPress.org plugin directory assets

These files are NOT part of the plugin itself — they live in WordPress.org's SVN repo under the `/assets/` directory and show up on the plugin's listing page on wordpress.org/plugins/.

## Files

| File | Purpose | Spec |
|---|---|---|
| `icon-256x256.png` | Plugin icon shown in the directory + WP admin's plugin browser | 256×256 PNG |
| `icon-256x256.svg` | Source for the icon (so we can re-render at higher quality / iterate) | — |
| `banner-1544x500.png` | Banner shown at the top of the plugin's WP.org page | 1544×500 PNG |
| `banner-1544x500.svg` | Source for the banner | — |
| `screenshot-N.png` | Screenshots shown in the **Screenshots** tab on the plugin page | ≤ 1200×900 (recommended), captions in `gamenight-league/readme.txt` under `== Screenshots ==` |

## How they get to WP.org

After the plugin is approved, WordPress.org provisions an SVN repo at `https://plugins.svn.wordpress.org/gamenight-league/`. The deploy flow is:

```
svn co https://plugins.svn.wordpress.org/gamenight-league/ wporg-svn
cp -r ../gamenight-league/* wporg-svn/trunk/         # plugin code → trunk
cp wporg-assets/* wporg-svn/assets/                  # visuals → assets
svn cp wporg-svn/trunk wporg-svn/tags/0.4.3          # tag the release
svn ci -m "release 0.4.3"
```

`/assets/` is sibling to `/trunk/` and `/tags/` in the SVN repo — totally separate from the plugin code that gets installed on users' WordPress sites.

## Re-rendering the PNGs

Both PNGs were generated from PowerShell + System.Drawing (no external tooling). The source SVGs are kept in case we want to:

- iterate on the design without rebuilding from scratch
- re-render at 2× DPI for higher-density displays (WP.org also accepts `icon-128x128.png` and an HD `banner-3088x1000.png`)
- hand off to a designer for polish

## Branding

- Primary blue: `#2563eb` (matches the GameNight site's dominant brand color)
- Accent blue: `#1e40af` (gradient end-stop on the banner)
- Suit accent: `#fca5a5` (light pink for hearts/diamonds)
- White (`#ffffff`) for text and the spade/club glyphs
