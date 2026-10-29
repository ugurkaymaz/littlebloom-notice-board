=== LittleBloom Notice Board ===
Contributors: ugur44kaymaz
Tags: announcements, nursery, notice board, sticky notes
Requires at least: 6.4
Requires PHP: 7.4
Tested up to: 7.1
Stable tag: 0.3.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A nursery notice board with sticky notes, a popup, custom colours, and an announcement archive. Advanced filters use a separate Pro add-on.

== Description ==
LittleBloom Notice Board (Nursery Notice Board — Free) lets WordPress site administrators create announcements and display them in a sticky-note popup or a simple archive. You can customise the popup heading, card and pin colours, and use shortcodes to place notices in your site.

The Free edition operates without a paid licence or mandatory Freemius registration. Advanced search, category and date filters, display scheduling controls and Pro card designs require a separately installed Pro add-on, distributed outside WordPress.org. Pro 0.5.3 requires Free 0.3.6 or later.

== Installation ==
For a manual installation, go to WordPress > Plugins > Add New > Upload Plugin and select the Free ZIP package.
Activate Nursery Notice Board — Free, then go to Nursery Notices > Settings to customise the display.
The plugin may create an Announcements page on activation if it cannot find a compatible existing page. No Freemius account or licence purchase is required for Free features.
For an upgrade, back up your site first; the plugin retains the existing `littlebloom_notice` post type and previously created archive pages.

== Usage ==
Place [littlebloom_notice_button] in a Shortcode block on any page to show the popup button.
Place [littlebloom_notices_archive] in a Shortcode block to display the archive.
On activation, the plugin creates an Announcements page if no existing compatible page is found.

== Configuration ==
Nursery Notices > Settings:
- Edit the button text, popup title, archive title, descriptions, and small heading.
- Set four default sticky-note card colours and the default pushpin icon colour.

Nursery Notices > Add New / Edit Notice:
- In the Sticky Card and Pin Colours meta box, tick 'Use a custom card colour' to override that notice's card colour.
- Tick 'Use a custom pin colour' to override its pin colour.
- Save or update the notice. Untick a box to revert to the global palette.
- On the public site, clicking a pin icon cycles the note through the four palette colours. This is a VISITOR-ONLY visual preview and does not save into WordPress.

Free archive:
- Show published and currently visible notices, newest first.
- Display a simple notice grid and pagination when needed.
- Do not show Search, From date, To date, Sort or Direction controls.
- Ignore advanced filter GET parameters when Pro is inactive.

Pro archive (separate active add-on):
- Search notice titles and contents.
- Filter by publication date range and category.
- Sort by publication date or title, ascending or descending.
- Combine filters and preserve them across pagination.

== Privacy and security ==
The colour preview does not send user data anywhere or store it between visits.
Free shows a newest-first archive of published, currently visible notices. Pro enables advanced WordPress search, filters and sorting.
Saved Pro Show From / Show Until windows continue to restrict public notice queries when Pro is deactivated; date windows are not a substitute for private/draft posts when dealing with confidential content.
The Free plugin bundles the Freemius WordPress SDK. This external service is used for optional administrator opt-in, add-on discovery, and account and licensing flows for the separately distributed Pro add-on. Free notice creation, display, and colour features do not require a paid licence or Freemius registration.

When an administrator explicitly opts in or uses relevant account/licensing features, Freemius may receive site, software and administrator/account information needed for those services, as described on the SDK consent screen and Freemius privacy policy. Please review the consent screen before opting in. The plugin's visitor-only card-colour preview does not itself transmit information to Freemius.

External service: Freemius (https://freemius.com/).
Privacy policy: https://freemius.com/privacy/.
Terms of service: https://freemius.com/terms/.


== Frequently Asked Questions ==

= Is a licence required to use the Free edition? =
No. Free announcements, popup, archive, customisation and colour features do not require a paid licence.

= Is the separate Pro add-on included in this download? =
No. Premium code is distributed separately. This Free ZIP does not contain the Pro add-on.

= Does this plugin work without the LittleBloom theme? =
Yes. It operates independently of the LittleBloom WordPress theme.

== Upgrade and rollback ==
Test v0.3.6 on a staging site before updating a live nursery website.
Keep a full WordPress file and database backup, plus a copy of the previous working ZIP for rollback.
Version 0.3.6 retains the existing plugin folder, shortcode names and notice post-type key to preserve compatibility with earlier installations.
Previously saved branding is preserved. Older LittleBloom sites without saved branding keep their original default names via a one-time migration.
New installs receive neutral Nursery Notice Board branding. No notices or pages are removed on deactivation.

== Editions ==
Free provides notices, popup, a simple archive, branding, colours and saved scheduling-window enforcement. The separately installed Pro add-on provides advanced archive filters and other premium features.
No paid licence activation is required to use Free. The bundled Freemius SDK offers optional opt-in and add-on account/licensing flows for Pro; Free features remain available without purchasing Professional.
Extension actions / filters for companion add-ons are documented in docs/ARCHITECTURE.md.

== Changelog ==

= 0.3.6 =
* Align Freemius initialization function, global variable and ready-hook names with the existing lbnb_ prefix to satisfy Plugin Check naming rules.
* Keep site content, public shortcodes, Free settings, scheduling safeguards, Freemius product identity and license-free functionality unchanged.
* Coordinate this build with Nursery Notice Board Pro v0.5.3; update Free first when upgrading an existing paired installation.
* Update version metadata and compatibility documentation.


= 0.3.5 =
* Integrate the Freemius SDK for optional Free opt-in and companion Pro add-on discovery.
* Preserve existing Free notice features, shortcodes, data and Pro extension points.
* Refresh the documentation to distinguish Free functionality from Pro licensing.

= 0.3.4 =
* Make Free a simple newest-first archive with no filter toolbar.
* Ignore keyword, date and sort query parameters while Pro is inactive; advanced filtering is not merely hidden with CSS.
* Retain the complete filter UI, category injection and query behaviour with active Pro v0.5.1, unchanged.
* Show a small informational Pro message only on the administrator Settings screen in Free mode, never to public visitors.
* Preserve v0.3.3 front-end scheduling protection, REST permissions, stored notices, branding and shortcodes.

= 0.3.3 =
* Honor previously saved Pro show-from and show-until times on dedicated public notice queries even when the Pro add-on is inactive.
* Use the WordPress site timezone and avoid duplicate visibility clauses with Pro v0.5.1.
* Do not change WordPress admin/REST editor queries, existing notices, stored metadata, shortcodes or styling.
* Display scheduling is not confidentiality protection; use Draft/Private for sensitive material.


= 0.3.2 =
* Tighten raw notice REST permissions to avoid granting all-record access solely through the broad edit_posts capability.
* Permit WordPress-authorised per-notice editors to access their own records.
* Preserve shortcode names, data, colours, front-end layouts and Pro compatibility.

= 0.3.1 =
* Limit raw notice REST API access to authorised editors, preventing accidental bypass of scheduled visibility rules in Pro.
* Public archive, popup, saved notices, settings and Gutenberg editing remain unchanged.

= 0.3.0 =
* Rename the public plugin identity to Nursery Notice Board — Free, independent of the LittleBloom theme.
* Neutral defaults on fresh installs while preserving existing saved and original unsaved legacy branding.
* Add optional extension hooks for future companion add-ons; all existing features remain free.
* Keep the installation folder, database keys, shortcodes and styles compatible with v0.2.0.

= 0.2.0 =
* Add plugin Settings for titles, button label, descriptions and card/pin palette colours.
* Add per-notice custom card and pin colours with nonce and capability checks.
* Add a clickable pin icon for visitor-only card colour previews.
* Add keyword search, date/title sort selection, ascending/descending order and combined filtering.
* Preserve the original custom post type, shortcode names and archive URLs.

= 0.1.1 =
* Prioritize explicit shortcode trigger buttons over navigation links.
* Retain LittleBloom theme header integration as compatibility fallback.

= 0.1.0 =
* Initial independent plugin migration from LittleBloom theme.
