# LittleBloom Notice Board: Architecture Notes

## Public release components

- Free: LittleBloom Notice Board v0.3.6, directory `littlebloom-notice-board`.
- Optional paid add-on: Nursery Notice Board Pro v0.5.3, distributed separately, not bundled with the Free plugin.
- The LittleBloom theme is not required.

## Storage and backwards compatibility

- Custom post type: `littlebloom_notice`.
- Settings option: `lbnb_settings`.
- Shortcodes: `[littlebloom_notice_button]`, `[littlebloom_notices_archive]`.
- Existing notice posts, settings, page references and custom colours are preserved by ordinary upgrades.
- Existing display-window metadata from Pro is honoured by the Free frontend after Pro is disabled. Published notices should not be used to store confidential material; use WordPress Draft or Private instead.

## Free and add-on boundaries

The Free plugin provides creating notices, a sticky-card popup, a simple paginated archive and colour controls without a paid licence. The separate Pro add-on provides advanced search, date/category filters, display scheduling controls and alternative layouts. Pro 0.5.3 requires Free 0.3.6 or later.

The bundled Freemius SDK provides optional administrator opt-in and optional add-on/licence management. It must not be required to use the Free notice features. The SDK bootstrap is exposed through `lbnb_freemius()` and its `lbnb_freemius_loaded` action. The Free product ID is 42743. Premium licence checks belong in the separately distributed Pro product (ID 42745).

The Free distribution is intended for WordPress.org. Any separately sold add-on is maintained and distributed outside the WordPress.org Free package. No executable Pro code is included here.

## Extension points

- `lbnb_free_ready` action, called on `init` at priority 25 with Free version.
- `lbnb_popup_query_args` filter, used before the popup's notice query.
- `lbnb_archive_query_args` filter, used before the archive notice query.

## Important update sequencing

Update Free to v0.3.6 before updating the separate Pro add-on to v0.5.3. Pro includes a minimum Free version guard, and pauses its features when an incompatible Free version is present. This check does not remove the Free plugin's existing content.

## Distribution note

WordPress.org reviewers independently determine final directory compliance and slug assignment. When approved, Free updates should be distributed by WordPress.org. Do not automatically install an executable paid add-on from third-party servers into a WordPress.org-hosted Free plugin.
