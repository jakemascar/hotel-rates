=== Island Diaries Accordion ===
Contributors: islanddiaries
Tags: accordion, shortcode, tour, rates, itinerary
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create reusable responsive accordion sets with rates, package details, itineraries, and rich content.

== Installation ==

1. Upload the `island-diaries-accordion` folder to `/wp-content/plugins/` or upload its ZIP from Plugins > Add New > Upload Plugin.
2. Activate Island Diaries Accordion.
3. Open Accordion Sets in the WordPress dashboard.
4. Edit the automatically created Sample Tour Details set or click Add New.
5. Copy the shortcode shown in the editor sidebar and paste it into a page, post, WooCommerce product, or shortcode widget.

== Shortcode ==

Basic usage:

`[tour_details_accordion id="123"]`

Optional attributes:

* `open="saved"` uses each section's Open by default setting. This is the default.
* `open="first"` opens the first section.
* `open="all"` opens all sections when multiple is enabled.
* `open="none"` starts with all sections closed.
* `multiple="no"` keeps only one section open. This is the default.
* `multiple="yes"` allows more than one section to stay open.

Example:

`[tour_details_accordion id="123" open="first" multiple="no"]`

== Layouts ==

* Rates + package details: two rate columns with a shared rich-text details panel.
* Two-column itinerary: time/label, main activity, optional list heading, and one bullet per line.
* Rich content: WordPress visual/text editor for tips, policies, and general content.

== Changelog ==

= 1.0.1 =
* Rebuilt the distributable ZIP with Linux-compatible directory separators.

= 1.0.0 =
* Initial release.
