=== SCM SaaS (Demand Planning & Procurement) ===
Contributors: siphiwethemba
Tags: supply chain, demand forecasting, inventory, procurement, analytics
Requires at least: 5.0
Tested up to: 6.3
Requires PHP: 7.4
Stable tag: 0.1
License: MIT
License URI: https://opensource.org/licenses/MIT

A free supply chain analytics and demand planning platform for WordPress. Features demand forecasting, inventory optimization, supplier performance, and KPI analytics.

== Description ==

SCM SaaS is a comprehensive supply chain management plugin integrating demand forecasting powered by AI, inventory optimization, supplier performance tracking, and KPI analytics—all accessible within your WordPress admin dashboard.

Key features include:
* Upload your sales data and generate sales forecasts using moving average methods or advanced Prophet AI forecasts.
* Calculate economic order quantities (EOQ), safety stock, and reorder points (ROP) with customizable parameters.
* Monitor supplier on-time delivery, cost variance, defect rates, and lead times with performance scoring.
* View KPIs like forecast accuracy, inventory turnover, and supplier OTIF (On-Time In-Full) metrics.
* Interactive dashboards powered by Chart.js for intuitive data visualization.
* Secure role-based access control via WordPress capabilities.

== Installation ==

1. Upload the `saas-plugin` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Upon activation, plugin database tables will be created automatically.
4. Access the SCM SaaS dashboard from the WordPress admin menu.
5. Use the Demand Forecasting and Inventory Optimization modules to upload data and perform analytics.

== Frequently Asked Questions ==

= Which PHP version is required? =
PHP 7.4 or newer is required due to dependencies and performance.

= Can I upload CSV files for sales data? =
Yes. Use the Demand Forecasting module to upload CSV sales data; the plugin expects columns for sale date, product ID, quantity, and price.

= How does the AI forecasting work? =
The plugin uses Facebook’s Prophet model via a Python script executed on your server to generate AI-powered forecasts.

== Screenshots ==

1. Dashboard overview with menu navigation and module summaries.
2. Demand Forecasting upload form and forecast visualization chart.
3. Inventory Optimization calculation form and results display.
4. Supplier performance scoring table.
5. KPI analytics dashboard with charts.

== Changelog ==

= 0.1 =
* Initial release with demand forecasting, inventory optimization, supplier tracking, KPIs, and admin UI.

== Upgrade Notice ==

= 0.1 =
First stable release. Please back up your database before upgrading.

== License ==

This plugin is licensed under the MIT License (https://opensource.org/licenses/MIT).

== Additional Notes ==

- Ensure your server supports executing Python scripts for the AI forecast feature.
- Enable WordPress debugging for troubleshooting.
- Compatible with WordPress 5.0 and above, tested up to 6.3.

