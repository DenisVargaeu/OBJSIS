
# 🍽️ OBJSIS V2 - Restaurant Management System

![Version](https://img.shields.io/badge/version-2.2.0-orange)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-blue)
![License](https://img.shields.io/badge/license-All%20Rights%20Reserved-red)


A comprehensive, modern restaurant management system with order tracking, employee management, inventory control, and customer-facing kiosk interface.

## ✨ Features

* 🌓 **V2 Theme Engine** - Premium Dark/Light mode with smooth transitions and dynamic icons
* 🔐 **Secure PIN-based login** with role management (Admin, Cook, Waiter)
* 📱 **Customer kiosk** for self-service ordering with category-based navigation
* 🍔 **Dynamic menu management** with categories and availability toggle
* 📊 **Real-time order tracking** with specialized workspace
* 🎟️ **Advanced coupon system** with expiration dates and usage limits
* 📜 **Collapsible Sidebar** - Organized navigation with per-section state persistence
* 🧾 **Receipt generation** and printing
* 📈 **Sales statistics** and inventory tracking
* 🔄 **Built-in Updater** - One-click system updates

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
├── admin/              # Admin panel pages
├── api/                # Backend API & Shift actions
├── assets/             # CSS, JS, Images
├── includes/           # Functions & helpers
├── sql/                # Database schema
└── index.php           # Customer kiosk
```

---

## 👥 User Roles

* **Admin**: Full access, manage employees/menu/tables/coupons, view stats
* **Cook**: View/update order status
* **Waiter**: Take/update orders
* **Inventory Manager**: Manage stock, recipes, logs

---

## 🔐 Security Notes

* Change default admin PIN
* Delete installer after setup
* Keep database credentials secure
* Use HTTPS in production

---

## 📄 License

**Copyright © 2026 Denis Varga**
**All Rights Reserved**

This source code may **not** be copied, modified, or distributed without explicit permission from the author.

---

## 🆘 Support

* Check README
* Review error logs
* Verify database connection

---

## 🗺️ Roadmap

**Phase 5 (Future)**

* [ ] Database backup/restore
* [ ] Training mode
* [ ] Multi-language support (SK, EN, DE)
* [ ] Mobile app for waiters
* [ ] Kitchen display system

**Phase 6 (Complete)**

* [x] Inventory management
* [x] Recipe-based stock deduction
* [x] Sold-out UI refinements

---

**Developed with ❤️ for restaurant efficiency**
**Star ⭐ this repository if you find it helpful!**

