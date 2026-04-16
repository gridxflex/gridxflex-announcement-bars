# 🎯 Gridxflex Announcement Bars with CTA

<p align="center">
  <img src="https://img.shields.io/badge/version-1.1.0-blue?style=for-the-badge" alt="Version">
  <img src="https://img.shields.io/badge/WordPress-5.0%2B-0073AA?style=for-the-badge&logo=wordpress&logoColor=white" alt="WordPress">
  <img src="https://img.shields.io/badge/PHP-7.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/License-GPL%20v2-green?style=for-the-badge" alt="License">
</p>

<p align="center">
  Transform your WordPress website with a stunning, fully responsive <strong>Gridxflex Announcement Bars with CTA</strong> — a lightweight, easy-to-use plugin for displaying customizable announcement bars. Perfect for announcements, promotions, or important notifications without slowing down your site.
</p>

---

## ✨ Features at a Glance

### 📋 Announcement Bar Management

- **Unlimited Announcement Bars** — Create and manage as many announcement bars as you need
- **Priority Ordering** — Control display order when multiple bars are active
- **Enable / Disable Toggle** — Instantly activate or deactivate any announcement bar from the list
- **Duplicate Notice** — Clone any existing announcement bar as a starting point for a new one

### 🎨 Fully Customizable Design

- **Background Color** — Choose any color with the built-in color picker
- **Text Color** — Customize your notice text color
- **Button Color** — Full color control for CTA button
- **Padding & Font Size** — Adjustable spacing and typography
- **Entrance Animation Style** — Slide, Fade, Reveal, Pop, Bounce, Flip or No Animation!
- **Animation Duration** — Set how long the entrance animation takes (100ms–2000ms)
- **Text Alignment** — Set notice text alignment to left, center, or right per bar
- **Font Weight** — Control notice text weight (normal, medium, semi-bold, bold)
- **Button Text Color** — Independent color picker for the button label
- **Button Padding** — Separate vertical (↕) and horizontal (↔) padding controls
- **Button Border Radius** — From square corners (0px) to pill shape (50px)

### 📍 Flexible Display Options

- **Position** — Display at top or bottom of page
- **Sticky or Static** — Fixed (sticky) position or inline (non-sticky) display
- **Location** — Show on entire site, homepage only, specific pages, categories, tags, or selected post types
- **User Role Targeting** — Restrict notice to specific WordPress user roles
- **User Control** — Option to hide from logged-in users
- **Dismissible** — Allow visitors to close the notice with cookie memory (30 days)

### 📅 Schedule Settings

- **Start Date** — Automatically show a announcement bar from a specific date and time
- **End Date** — Automatically hide a announcement bar after a specific date and time
- Leave either field empty for open-ended or always-on notices

### ⚡ Trigger Settings

- **Time Delay** — Show notice after a configurable number of seconds (0 = immediately)
- **Scroll Percentage** — Show notice when the user scrolls a set percentage down the page (0 = disabled)
- **Exit Intent** — Show notice when user moves cursor toward closing the tab (desktop only)

### 🛒 Call-to-Action Button

- Optional CTA button with custom text and URL
- Open link in new tab option
- Fully styled and responsive

### ✨ Smart Analytics

- Click tracking for CTA button
- View count tracking

### 📐 Responsive Settings

- **Mobile Layout** — Choose Auto (default responsive), Row (always side-by-side), or Column (always stacked) for screens under 768px
- **Mobile Font Size** — Override font size on mobile only (0 = inherit from desktop setting)
- **Mobile Padding** — Override bar padding on mobile only (0 = inherit from desktop setting)

### 📱 Responsive & Lightweight

- Mobile-first responsive design
- Works on all devices (desktop, tablet, mobile)
- Minimal CSS and JavaScript
- No external dependencies
- Zero impact on site performance

---

## ⚙️ Requirements

| Requirement | Minimum Version |
| ----------- | --------------- |
| WordPress   | 5.0+            |
| PHP         | 7.2+            |
| Browser     | Modern browser  |

---

## 🚀 Installation

### Via WordPress Admin (Recommended)

1. Go to **WordPress Admin → Plugins → Add New → Upload Plugin**
2. Upload the plugin ZIP file
3. Click **Install Now**
4. Click **Activate Plugin**
5. Navigate to **Dashboard → Announcement Bars** to manage your announcement bars

### Manual Installation

1. Extract the plugin ZIP file
2. Upload the `gridxflex-announcement-bars` folder to `/wp-content/plugins/`
3. Activate the plugin through the **Plugins** menu in WordPress
4. Navigate to **Dashboard → Announcement Bars** to manage your announcement bars

---

## 📖 Usage

After installation:

1. Navigate to **Dashboard > Announcement Bars**
2. Click **Add New** to create a announcement bar
3. **Basic Settings** — Enable the announcement bar, set title, notice text, position, sticky mode, and priority
4. **Button Settings** — Add optional CTA button with custom URL
5. **Design Settings** — Customize colors, padding, and font size
6. **Visibility Settings** — Choose where and to whom to display the announcement bar
7. **Schedule Settings** — Set optional start and end dates
8. **Trigger Settings** — Configure time delay, scroll percentage, or exit intent
9. Click **Save Notice** to apply changes

---

## 🎯 Configuration Examples

### Example 1: Homepage Announcement

```
Position: Top
Text: Welcome! Check out our new product line
Button Text: Shop Now
Button URL: https://yoursite.com/products
Display: Homepage Only
Dismissible: Yes
```

### Example 2: Site-wide Notification

```
Position: Top
Text: We're moving servers this weekend for maintenance
Button Text: Learn More
Button URL: https://yoursite.com/maintenance
Display: Show on Entire Site
Dismissible: Yes
```

### Example 3: Limited Offer with Schedule

```
Position: Top
Text: Limited Time Offer - Use code SAVE20 for 20% off!
Button Text: Shop Now
Button URL: https://yoursite.com/shop
Display: Show on Entire Site
Start Date: 2025-01-01 00:00
End Date: 2025-01-31 23:59
Dismissible: Yes
```

### Example 4: Exit Intent Upsell

```
Position: Bottom
Text: Wait! Before you go — grab 10% off your first order.
Button Text: Claim Offer
Button URL: https://yoursite.com/offer
Trigger: Exit Intent
Display: Show on Entire Site
Dismissible: Yes
```

### Example 5: Delayed Scroll-triggered Notice

```
Position: Bottom
Text: Enjoying the article? Subscribe to our newsletter.
Button Text: Subscribe
Trigger: Show on Scroll 50%
Display: Show on Entire Site
Dismissible: Yes
```

---

## ⚙️ Technical Details

### Plugin Structure

```
gridxflex-announcement-bars/
├── gridxflex-announcement-bars.php    Main plugin file
├── includes/
│   ├── class-gabc-loader.php      Hook loader
│   ├── class-gabc-core.php        Core plugin class
│   └── class-gabc-admin.php       Admin settings
├── assets/
│   ├── css/
│   │   ├── public.css             Frontend styles
│   │   └── admin.css              Admin styles
│   └── js/
│       ├── public.js              Frontend logic
│       └── admin.js               Admin logic
├── readme.txt                     WordPress readme
├── README.md                      GitHub readme
└── index.php                      Security files
```

### Code Pattern

The plugin follows the same architecture pattern as the Product Carousel Slider for WooCommerce:

- **Loader Class** — Manages all WordPress hooks (actions/filters)
- **Core Class** — Orchestrates plugin functionality
- **Admin Class** — Handles admin settings page
- **Separation of Concerns** — Clean, maintainable code structure

### WordPress Hooks Used

- `plugins_loaded` — Plugin initialization
- `wp_enqueue_scripts` — Frontend assets
- `admin_enqueue_scripts` — Admin assets
- `admin_menu` — Settings menu
- `wp_ajax_*` — AJAX handlers
- `register_activation_hook` — Activation
- `register_deactivation_hook` — Deactivation

### Database Storage

Settings stored in a custom database table `{prefix}_gabc_notices`:

- One row per announcement bar
- Supports unlimited announcement bars
- Indexed on `enabled`, `priority`, `start_date`, `end_date` for fast queries

---

## 🔒 Security

- All inputs are sanitized with WordPress functions
- Output properly escaped
- Nonce verification for AJAX requests and page actions
- Capability checks for admin access
- No external dependencies or third-party databases

---

## ♿ Accessibility

- WCAG 2.1 compliant
- Keyboard navigation support
- Screen reader friendly
- ARIA labels on buttons
- Focus management
- Reduced motion support

---

## 🐛 Troubleshooting

### Notice bar not showing?

- Check if enabled in settings (Dashboard > Announcement Bars)
- Verify visibility location matches current page
- Check if a start/end date is restricting display
- Check if a trigger (delay/scroll/exit) is delaying display
- Clear browser cache and cookies
- Try clearing WordPress cache if using cache plugin

### Styles not applying?

- Clear all caches (browser, WordPress, server)
- Verify no conflicting theme CSS
- Check browser console for errors
- Try a different browser

### Dismissal not working?

- Verify JavaScript is enabled
- Check browser cookie settings
- Clear browser cookies
- Try in private/incognito mode

### Trigger not firing?

- Delay trigger: wait the configured number of seconds after page load
- Scroll trigger: scroll down to the configured percentage of the page
- Exit intent: move your cursor quickly toward the top of the browser window (desktop only, does not fire on mobile)

---

## 🌐 Browser Support

| Browser                                 | Support         |
| --------------------------------------- | --------------- |
| Chrome                                  | ✅ Latest       |
| Firefox                                 | ✅ Latest       |
| Safari                                  | ✅ Latest       |
| Edge                                    | ✅ Latest       |
| Mobile (iOS Safari, Chrome for Android) | ✅ Full support |

> **Note:** Exit intent trigger is desktop-only. It does not fire on touch/mobile devices.

---

## ❓ Frequently Asked Questions

### Can I have multiple announcement bars?

Yes! The plugin supports unlimited announcement bars. Each announcement bar has its own settings, scheduling, targeting, and trigger options. Use the Priority field to control which notice appears first when multiple bars are active at the same time.

### How long does the dismissal cookie last?

30 days. After 30 days, the notice will appear again for visitors.

### Can I use HTML in the notice text?

Yes, basic HTML tags are allowed for formatting.

### What is the difference between sticky and non-sticky?

A sticky announcement bar is fixed to the top or bottom of the viewport and stays visible while the user scrolls. A non-sticky announcement bar is rendered inline in the page flow and scrolls away with the content.

### Can I schedule a announcement bar to run only during a sale or event?

Yes. Each announcement bar has a Start Date and End Date field. Set both to run a time-limited campaign. Leave either blank for open-ended notices.

### Does the exit intent trigger work on mobile?

No. Exit intent detection relies on mouse cursor movement and only works on desktop browsers.

### Does this work with cache plugins?

Yes, but you may need to exclude the announcement bar from cache or clear cache after settings changes.

### Is the plugin mobile-friendly?

Absolutely. The plugin is fully responsive and touch-friendly on all devices.

---

## 📋 Changelog

### v1.1.0

- **New:** Text Alignment control (left / center / right) per bar
- **New:** Font Weight control (normal / medium / semi-bold / bold)
- **New:** Button Text Color — independent picker from button background
- **New:** Button Padding — separate vertical and horizontal controls
- **New:** Button Border Radius — square to pill-shaped buttons
- **New:** Responsive Settings section — Mobile Layout (auto / row / column)
- **New:** Mobile Font Size override per bar
- **New:** Mobile Padding override per bar
- **Improvement:** Button color and text color use CSS custom properties
- **Improvement:** Text alignment and font weight driven by CSS variables
- **DB:** 9 new columns added to `gabc_notices` table (auto-migrated via `dbDelta`)

### v1.0.0

- Initial release

## 📄 License

**GPL v2 or later** — [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)

---

## 👨‍💻 Author

**GridXFlex**

- Website: [https://gridxflex.com](https://gridxflex.com)

---

## 🙏 Credits

- Built with ❤️ using **WordPress**
- Follows WordPress coding standards and best practices

---

## 📞 Support

For support:

- Check the plugin documentation
- Review the troubleshooting section above
- Contact plugin support team

---

> **Note:** Regular updates and improvements are planned. Keep your plugin updated for the latest features and security patches!