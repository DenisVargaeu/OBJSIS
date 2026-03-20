# 🚀 Release Notes - OBJSIS v2.5.0

**Release Date:** March 20, 2026
**Version:** 2.5.0 (The "Real-Time" Update)

## 🌟 Major Improvements

### 1. Real-Time Admin Dashboard
- **AJAX Polling Implementation**: The dashboard now updates automatically every 10 seconds without page reloads.
- **Interactive Data Visualization**: Integrated **Chart.js** for hourly revenue and order count tracking.
- **Performance Widgets**: Added "Top Selling Items" and "Average Ticket Size" live metrics.
- **Immediate Feedback**: Added Web Audio alerts for new incoming orders to notify staff even when not looking at the screen.

### 2. Next-Gen Kitchen Display System (KDS)
- **High-Visibility "Kitchen Mode"**: A new dark, high-contrast theme optimized for bright kitchen environments.
- **Urgency Logic**: Automated color-coding for delayed orders (>15 mins) and critical delay banners.
- **Enhanced Order Management**: Faster status transitions and improved typography for high-pressure reading.

### 3. Universal Admin Modernization
- **Glassmorphic Design System**: Over 15 admin pages have been refactored to use a consistent, premium glass-effect UI.
- **Sidebar Intelligence**: Added live order count badges that update globally across the entire admin panel.
- **Refactored CSS Architecture**: Moved from hundreds of inline styles to a centralized, efficient utility-based CSS system.

### 4. New Reporting & Analytics
- **Business Intelligence (BI) Module**: Revamped `reports.php` with bar and doughnut charts for better decision-making.
- **Shift Efficiency**: Modernized shift history with calculated duration badges and active session indicators.

## 🛠️ Technical Changes
- Created `api/dashboard_fetch.php` for unified real-time data serving.
- Refactored `admin/orders.php` to use a dynamic grid layout with AJAX syncing.
- Updated `includes/sidebar.php` with state-persistence and live badge logic.
- Standardized all administrative headers and container layouts.

## ✅ How to Update
1. Access the **System Maintenance** page in the Admin Panel.
2. Click **Check for Updates**.
3. Select **Install v2.5.0**.
4. The system will perform an automated file patch and database migration.

---
*Developed by Denis Varga*
