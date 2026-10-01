=== bitfox's EU founding banner - Hungary ===
Contributors: bitfox.creative.studio
Donate link: https://www.bitfox.hu/termek/wordpress-eu-palyazatok-beepulo-modul-tamogatoi-szoftverlicensz/
Tags: eu, támogatás, pályázat, széchenyi terv plusz, rrf
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.2
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Manage official EU funding visibility blocks, project information, and a clickable website banner for EU-funded projects in Hungary.

== Description ==

**EU Projects Banner - Hungary** helps websites display information about projects co-financed by the European Union in Hungary. It provides a central interface for managing project information, required communication details, and official visual identity blocks displayed to website visitors.

**Features**

- Manage and reorder multiple projects.
- Built-in official Széchenyi Terv Plusz / EU co-financing and RRF / NextGenerationEU information blocks.
- Select a built-in visual identity block or upload an official project-specific asset.
- Manage project descriptions, objectives, results, beneficiary information, project identifiers, and funding amounts.
- Compact, clickable, positionable corner banner.
- Detailed project information is displayed in an accessible popup.
- Detailed project information is loaded only after the visitor clicks the banner using a REST API request, reducing the amount of content loaded during the initial page load.
- `[eu_support id="123"]` shortcode for displaying detailed information about a single project.
- `[eu_supports]` shortcode for displaying all projects with complete information.
- Custom CSS field for overriding the appearance.
- Projects with incomplete information are excluded from the public banner and project listing shortcode.

This plugin is not an official government or European Union application. It does not replace the need to check the applicable grant agreement, funding call, or the current visual identity and communication requirements issued by the relevant managing authority.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install it through the WordPress admin area.
2. Activate the **EU Projects Banner - Hungary** plugin.
3. Open the **Projects** menu and enter your project information.
4. Open **Projects → Display** to configure the banner.
5. Optionally add the `[eu_support]` or `[eu_supports]` shortcode to your website.

== Frequently Asked Questions ==

= Is the banner required? =

No. The plugin is intended to help with website communication and visibility. The requirements of the applicable grant agreement and funding call always take precedence.

= Why is a project not displayed in the banner? =

Only published projects with complete required information are displayed. Check the project data and the selected or uploaded official information block.

= Are the detailed project data loaded on every page load? =

No. The banner initially contains only the compact official visual block. Detailed project information is loaded after the visitor clicks the banner through a separate REST API request.

= Is the plugin free? =

Yes. All plugin functionality is available free of charge. An optional supporter software license may be purchased, but it does not provide additional functionality and is not required to use the plugin.

== Repository ==

The source code for this plugin is available on GitHub:

https://github.com/banditoth/wp-eu-finance-banner-hungary

== Changelog ==

= 1.0.2 =

- Replaced the separate project listing page with a clickable popup that loads project information on demand.
- Replaced the supporter license admin menu item with an informational notice displayed at the top of the Projects admin screens.
- Added a WordPress.org-formatted readme.

= 1.0.1 =

- Added the Projects admin menu, built-in official information blocks, and automatically generated project titles.

== Upgrade Notice ==

= 1.0.2 =

Update to use the project information popup with on-demand loading and the updated admin information notice.