=== Gridxflex Announcement Bars with CTA ===
Contributors: gridxflex
Tags: announcement bar, announcement bar, custom notice, dismissible, banner
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 1.1.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight, fully customizable announcement bar with display options and cookie-based dismissibility.

== Description ==

Transform your WordPress website with a stunning, fully responsive **Gridxflex Announcement Bars with CTA** — a lightweight, easy-to-use plugin that allows you to display customizable announcement bars on your website. Perfect for announcements, promotions, or important notifications without slowing down your site.

= Features =

* **Multiple Announcement Bars** — Create and manage unlimited announcement bars with individual settings
* **Priority Ordering** — Control which announcement bars appear first using priority values
* **Enable / Disable Toggle** — Instantly activate or deactivate any announcement bar from the list
* **Duplicate Notice** — Clone any existing announcement bar as a starting point for a new one
* **Flexible Announcement Bar** — Display notice at top or bottom of website
* **Sticky or Static** — Choose between fixed (sticky) or inline (non-sticky) display
* **Fully Customizable Text** — Edit notice text directly from admin panel with HTML support
* **CTA Button** — Add a custom button with URL and open-in-new-tab option
* **Design Controls** — Customize colors, padding, and font size
* **Scheduling** — Set start and end dates to automatically show or hide a announcement bar
* **Trigger: Time Delay** — Show notice after a configurable number of seconds
* **Trigger: Scroll Percentage** — Show notice when the user scrolls a set percentage down the page
* **Trigger: Exit Intent** — Show notice when the user moves their cursor toward closing the tab (desktop only)
* **Visibility Controls** — Show on entire site, homepage only, specific pages, specific categories, specific tags, or selected post types
* **User Role Targeting** — Show notice only to selected user roles
* **Hide from Logged-in Users** — Option to show notice only to visitors
* **Dismissible Option** — Allow users to close the bar with cookie-based memory (30 days)
* **Lightweight** — No external libraries, minimal CSS and JavaScript
* **Fully Responsive** — Works perfectly on mobile, tablet, and desktop devices
* **Analytics** — Smart analytics with total views and clicks count and auto CTR calculation

= Design Controls =

* Background color picker
* Text color picker
* Button color customization
* Adjustable padding & font size
* Entrance animation style picker (slide, fade, reveal, pop, bounce, flip)
* Animation duration control (milliseconds)

= Visibility Settings =

* Show on entire site
* Show only on homepage
* Show on specific pages
* Show on specific categories
* Show on specific tags
* Show on specific post types
* Show to specific user roles only
* Option to hide for logged-in users

= Schedule Settings =

* Set a start date and time for a notice to automatically appear
* Set an end date and time for a notice to automatically disappear
* Leave either field empty for open-ended scheduling

= Trigger Settings =

* Show after a configurable time delay (seconds)
* Show after the user scrolls a set percentage down the page
* Show on exit intent — when user moves cursor toward closing the tab (desktop only)

= Dismissible Feature =

* Allow users to close the bar with a close button
* Cookie-based memory for dismissed notices (30 days)

== Installation ==

1. Download the plugin ZIP file
2. In your WordPress dashboard, go to **Plugins → Add New → Upload Plugin**
3. Upload the ZIP file and click **Install Now**
4. Click **Activate Plugin**
5. Navigate to **Dashboard > Announcement Bars** to manage your announcement bars
6. Click **Add New** to create your first announcement bar
7. Customize text, colors, position, visibility, schedule, and trigger options
8. Click **Save Notice** and the announcement bar will appear on your website

Alternatively, you can upload the plugin files manually to `/wp-content/plugins/gridxflex-announcement-bars/` and activate it through the Plugins menu.

== Frequently Asked Questions ==

= Does this plugin require any other plugins? =

No. Gridxflex Announcement Bars with CTA is a standalone plugin with no external dependencies.

= Can I have multiple announcement bars? =

Yes! The plugin supports unlimited announcement bars. Each announcement bar has its own settings, scheduling, targeting, and trigger options. Use the Priority field to control which notice appears first when multiple bars are active.

= How long is the dismissal cookie stored? =

The dismissal cookie is stored for 30 days. After 30 days, the notice will appear again for users.

= Can I customize the announcement bar colors? =

Yes, you can fully customize the background color, text color, and button color using the color pickers in the settings.

= Does this plugin affect site performance? =

No, the plugin is lightweight with minimal CSS and JavaScript. It has no impact on site performance.

= Can I show the announcement bar on specific pages? =

Yes, you can choose to show it on the entire site, homepage only, specific pages, specific categories, specific tags, or specific post types.

= Can I target specific user roles? =

Yes, you can restrict a announcement bar to show only to selected WordPress user roles (e.g. subscribers, editors, administrators).

= Can I schedule when a announcement bar appears and disappears? =

Yes, each announcement bar has a start date and end date field. Leave them empty for always-on notices, or set both to run a time-limited campaign.

= What trigger options are available? =

Three trigger options are available: show after a time delay (seconds), show after the user scrolls a set percentage down the page, and show on exit intent when the user moves their cursor toward closing the tab (desktop only).

= What is the difference between sticky and non-sticky? =

A sticky announcement bar is fixed to the top or bottom of the viewport and stays visible while the user scrolls. A non-sticky announcement bar is rendered inline in the page flow and scrolls away with the content.

= Is the plugin mobile responsive? =

Yes, the announcement bar is fully responsive and works perfectly on all devices including mobile, tablet, and desktop.

= Can I use HTML in the notice text? =

Basic HTML tags are allowed in the notice text for formatting.

= Where can I get support? =

You can find support on the WordPress.org plugin support forum or contact the plugin author directly.

== Screenshots ==

1. Announcement Bars list page with enable/disable toggles and action buttons
2. Add / Edit announcement bar — Basic Settings
3. Add / Edit announcement bar — Button Settings
4. Add / Edit announcement bar — Design Settings
5. Add / Edit announcement bar — Visibility Settings
6. Add / Edit announcement bar — Schedule and Trigger Settings
7. Notice bar displayed at the top of a website
8. Mobile responsive view

== Changelog ==

= 1.1.0 =
* New: Text Alignment control (left / center / right) per announcement bar
* New: Font Weight control (normal / medium / semi-bold / bold) per announcement bar
* New: Button Text Color picker — independent from button background color
* New: Button Padding — separate vertical and horizontal padding controls
* New: Button Border Radius — square to pill-shaped buttons
* New: Responsive Settings section — Mobile Layout mode (auto / row / column)
* New: Mobile Font Size override (0 = use default)
* New: Mobile Padding override (0 = use default)
* Improvement: Button color and text color now use CSS custom properties for instant theming
* Improvement: Text alignment and font weight driven by CSS variables (no inline styles on text)
* DB: Added 9 new columns to gabc_notices table (auto-migrated via dbDelta)

= 1.0.0 - Initial Release =
* Core functionality for customizable announcement bars
* Unlimited announcement bars with priority ordering
* Enable / disable toggle per announcement bar
* Duplicate announcement bar action
* Admin settings page with full customization options
* Frontend display with responsive design
* Sticky and non-sticky display modes
* Dismissible with cookie-based memory
* Visibility controls — entire site, homepage, specific pages, categories, tags, post types
* User role targeting
* Schedule settings — start and end date per notice
* Trigger settings — time delay, scroll percentage, exit intent
* Color customization
* Hide from logged-in users option

== Support ==

For support, please visit the plugin documentation or contact our support team.

== License ==

Gridxflex Announcement Bars with CTA is licensed under the GPL v2 or later. See LICENSE file for details.

== Additional Info ==

= Requirements =

* WordPress 5.0 or higher
* PHP 7.2 or higher

= Browser Support =

* Chrome (latest)
* Firefox (latest)
* Safari (latest)
* Microsoft Edge (latest)
* Mobile browsers — iOS Safari, Chrome for Android

= Privacy =

This plugin does not collect, store, or transmit any user data. It only stores announcement bar settings in your WordPress database.

= Support or Feature Requests =

* [GitHub Repository](https://github.com/gridxflex/gridxflex-announcement-bars)
* [WordPress Support Forum](https://wordpress.org/support/plugin/gridxflex-announcement-bars/)

= Contributing =

Contributions are welcome! Please feel free to submit a Pull Request on GitHub.

== Credits ==

Built with ❤️ for WordPress using best practices and WordPress coding standards.