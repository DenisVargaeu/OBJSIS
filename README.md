# 🍽️ OBJSIS V2 - Restaurant Management System

![Version](https://img.shields.io/badge/version-2.6.0-orange)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-blue)
![License](https://img.shields.io/badge/license-All%20Rights%20Reserved-red)


A comprehensive, modern restaurant management system with order tracking, employee management, inventory control, and a powerful **Modular Addon Ecosystem**.

## ✨ Features (v2.6.0 Highlight)

* 🔌 **Modular Addon Ecosystem** - Easily extend the system with one-click installable plugins.
* 🔑 **API Manager PRO** - Enterprise-grade API with multi-key management and IP whitelisting.
* 💻 **Terminal Pro Ultra** - Full-screen interactive CLI with system monitoring and boot sequence.
* 🔔 **System Notifications** - Real-time alerts for new orders and low stock (via inventory logs).
* 📊 **Real-time Dashboard** - Flicker-free data updates with interactive **Chart.js** analytics.
* 👨‍🍳 **Elite KDS (Kitchen Display System)** - High-visibility "Kitchen Mode" with urgency color-coding and audio alerts.
* 🌓 **V2 Theme Engine** - Premium Glassmorphic design across all 15+ administrative pages.
* 🪙 **Multi-Currency** - Real-time price conversion for international guests (EUR, USD, GBP, HUF).
* ⚡ **Quick Actions** - Floating speed-dial for the most frequent administrative tasks.

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
5. **Enable Addons**: Visit `admin/addons.php` to activate new modules.

---

## 📋 Project Structure

```
OBJSIS V2/
├── admin/              # Modernized admin panel pages
├── addons/             # Modular extension system (NEW)
├── api/                # Real-time backend API (AJAX)
├── assets/             # CSS (Glassmorphism), JS, Charts
├── includes/           # Functions & Layout helpers
├── sql/                # Database schema (v2.6.0)
└── index.php           # Customer kiosk
```

---

## 👥 User Roles

* **Admin**: Full access, manage addons, keys, employees, menu, tables, view BI stats
* **Cook**: Specialized KDS view with urgency alerts
* **Waiter**: Take/update orders with live status feedback
* **Manager**: Manage stock, recipes, and detailed reports

---

## 🔐 Security Notes

* Manage API keys securely in the **API Manager**.
* Use **IP Whitelisting** to restrict external access.
* Change default admin PIN immediately.
* Delete installer after setup.

---

## 📄 License

**Copyright © 2026 Denis Varga**
**All Rights Reserved**

This source code may **not** be copied, modified, or distributed without explicit permission from the author.

---

## 🗺️ Roadmap (Updated)

**Phase 7 (Next Focus)**

* [x] Modular Addon System
* [x] Enterprise API Integrations
* [x] Advanced System Terminal
* [ ] Training mode logic
* [ ] Multi-language support (SK, EN, DE) - *Structural prep complete*

**History (Completed)**

* [x] Real-time Dashboard & KDS
* [x] Inventory management & Recipe deduction
* [x] V2 Glassmorphic Theme Engine
* [x] Built-in Software Updater

---

**Developed with ❤️ for restaurant efficiency**
**Star ⭐ this repository if you find it helpful!**
