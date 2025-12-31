-- Database Schema for OBJSIS V.2

-- Tables are created in the database selected in config/db.php

-- Users (Employees)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    pin_hash VARCHAR(255) NOT NULL, -- Storing hashed PINs for security
    role ENUM('admin', 'cook', 'waiter') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Menu Categories
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    sort_order INT DEFAULT 0
);

-- Menu Items
CREATE TABLE IF NOT EXISTS menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image_url VARCHAR(255),
    is_available BOOLEAN DEFAULT TRUE,
    allergens VARCHAR(255), -- Comma separated list of allergens or codes
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Orders
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_number INT NOT NULL,
    status ENUM('received', 'preparing', 'ready', 'delivered', 'paid', 'cancelled') DEFAULT 'received',
    total_price DECIMAL(10, 2) DEFAULT 0.00,
    note TEXT, -- Order-level note
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Order Items
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    quantity INT DEFAULT 1,
    price_at_time DECIMAL(10, 2) NOT NULL, -- Price at the time of order
    note TEXT, -- Item-specific note
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
);

-- Coupons (Phase 2)
CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    type ENUM('fixed', 'percent') NOT NULL,
    value DECIMAL(10, 2) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
