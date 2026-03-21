
# 🍽️ OBJSIS V2 - Restaurant Management System

![Version](https://img.shields.io/badge/version-2.5.0-orange)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-blue)
![License](https://img.shields.io/badge/license-All%20Rights%20Reserved-red)


A comprehensive, modern restaurant management system with order tracking, employee management, inventory control, and customer-facing kiosk interface.

## ✨ Features (v2.5.0 Highlight)

* 📊 **Real-time AJAX Dashboard** - Flicker-free data updates with interactive **Chart.js** analytics.
* 👨‍🍳 **Elite KDS (Kitchen Display System)** - High-visibility "Kitchen Mode" with urgency color-coding and audio alerts.
* 🌓 **V2 Theme Engine** - Premium Glassmorphic design across all 15+ administrative pages.
* 🔔 **Live Navigation Badges** - Global real-time order count indicators in the sidebar.
* 🔐 **Secure PIN-based login** with role management (Admin, Cook, Waiter, Manager).
* 📱 **Customer kiosk** for self-service ordering with category-based navigation.
* 🍔 **Dynamic menu management** with categories and availability toggle.
* 🎟️ **Advanced coupon system** with expiration dates and usage limits.
* 🧾 **Receipt generation** and printing.
* 📉 **Business Intelligence** - Detailed sales reports and inventory stock tracking.
* 🔄 **Built-in Updater** - One-click system updates and patching.

---

## 🚀 Quick Start Installation

### Prerequisites

* **XAMPP** (or similar) with PHP 7.4+ and MySQL/MariaDB
* Web browser
* Basic knowledge of PHP/MySQL

### Installation Steps

1. **Place files in XAMPP directory**

   ```
   C:\xampp\htdocs\objsis-v2\
   ```
2. **Configure database connection**
   Edit `config/db.php`:
   ```php
   $host = 'localhost';
   $db_name = 'objsis_v2';
   $username = 'main_app';
   $password = '';
   ```
3. **Create database user** (in phpMyAdmin or MySQL console)

   ```sql
   CREATE DATABASE objsis_v2;
   CREATE USER 'main_app'@'localhost';
   GRANT ALL PRIVILEGES ON objsis_v2.* TO 'main_app'@'localhost';
   FLUSH PRIVILEGES;
   ```
4. **Run the installer** at: `http://localhost/objsis-v2/install.php`

   * Creates tables
   * Inserts default admin PIN `1234`
   * Sets up sample data
5. **Delete installer** for security (`install.php`)
6. **Login and configure**

   * PIN: `1234`
   * Change admin PIN
   * Configure restaurant name

---

## 📋 Project Structure

```
OBJSIS V2/
├── admin/              # Modernized admin panel pages
├── api/                # Real-time backend API (AJAX)
├── assets/             # CSS (Glassmorphism), JS, Charts
├── includes/           # Functions & Layout helpers
├── sql/                # Database schema (v2.5.0)
└── index.php           # Customer kiosk
```

---

## 👥 User Roles

* **Admin**: Full access, manage employees/menu/tables/coupons, view BI stats
* **Cook**: Specialized KDS view with urgency alerts
* **Waiter**: Take/update orders with live status feedback
* **Manager**: Manage stock, recipes, and detailed reports

---

## 🔐 Security Notes

* Change default admin PIN immediately
* Delete installer after setup
* Keep database credentials secure
* Use HTTPS in production

---

## 📄 License

**Copyright © 2026 Denis Varga**
**All Rights Reserved**

This source code may **not** be copied, modified, or distributed without explicit permission from the author.

---

## 🗺️ Roadmap (Updated)

**Phase 6 (Current Focus)**

* [x] Real-time Dashboard (AJAX)
* [x] Kitchen Display System (KDS)
* [x] Advanced Business Intelligence (Charts)
* [x] Global Sidebar Badges
* [ ] Training mode logic
* [ ] Multi-language support (SK, EN, DE) - *Structural prep complete*

**History (Completed)**

* [x] Inventory management & Recipe deduction
* [x] V2 Glassmorphic Theme Engine
* [x] Built-in Software Updater

---

**Developed with ❤️ for restaurant efficiency**
**Star ⭐ this repository if you find it helpful!**
