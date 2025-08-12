# Costabilerent Theme

Development guidelines for the Costabilerent front‑end theme.

## Styles

- Main stylesheet located in `style.css`; additional styles in `assets/css/main.css`.
- Follow BEM-style naming and Totaliweb/Costabilerent branding.
- Minify and version compiled CSS for production.

## Translations

- Base language is English.
- Use the `costabilerent` text domain in all translation functions.
- Place `.pot`, `.po`, and `.mo` files inside `languages/` and load them via `load_theme_textdomain()`.

## Assets

- Enqueue CSS and JS through `functions.php` using `wp_enqueue_style()` and `wp_enqueue_script()`.
- Store unminified sources under `assets/` (`css/`, `js/`) and load only where required.
- Version assets to leverage browser caching.

