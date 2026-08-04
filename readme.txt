=== Hotel Rates Manager ===
Contributors: islanddiaries
Tags: hotel, rates, travel, pricing, tourism
Requires at least: 5.8
Tested up to: 6.7
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage hotel and tour rates by region, place, and hotel with per-night and per-pax pricing.

== Description ==

Hotel Rates Manager allows travel and tour operators to maintain hotel and tour pricing in a clean, structured admin interface.

**Features:**

* **Hierarchical structure**: Region → Place → Hotel
* **Hotel Rates**: Define rates per number of nights (1N–5N) with per-pax pricing (1–8 pax)
* **Meal plan support**: X (No Breakfast), BB (Bed & Breakfast), HB (Half Board), FB (Full Board)
* **Tour Rates**: Manage tour pricing per region/place with per-pax pricing
* **Register page**: Add/edit/delete regions, places, and hotels
* **Cascading dropdowns**: AJAX-powered Region → Place → Hotel selectors
* **Night filter checkboxes**: Show only the nights you need
* **Prices in Philippine Peso (₱)**

== Package Deal Hotel Rates Shortcode ==

Add this shortcode to a WooCommerce product page that has a Package Deal linked:

`[package_deal_hotel_rates]`

Optional attributes:

* `package_id="6"` selects a Package Deal directly.
* `product_id="123"` loads the Package Deal linked to a specific product.
* `max_pax="8"` limits the displayed pax columns (default 12, maximum 12).
* `title="Hotel Options"` changes the table heading.

== Installation ==

1. Upload the `hotel-rates` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Hotel Rates → Register Hotels, Places & Tours** to add your data hierarchy.
4. Then go to **Hotel Rates** to add rates for each hotel.

== Changelog ==

= 1.0.0 =
* Initial release.
