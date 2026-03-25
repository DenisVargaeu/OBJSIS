<?php
// includes/sidebar.php (Now a Top Navigation Bar)
$current_page = basename($_SERVER['PHP_SELF']);
$user_role = $_SESSION['user_role'] ?? 'waiter';
$user_name = $_SESSION['user_name'] ?? 'User';

function isActive($page, $current)
{
    return $page === $current ? 'active' : '';
}
?>

<nav class="top-navbar" id="mainNavbar">
    <div class="navbar-brand">
        <a href="dashboard.php" style="display:flex; align-items:center; gap:10px;">
            <i class="fas fa-utensils" style="color: var(--primary-color);"></i>
            <span class="navbar-logo-text"><?= htmlspecialchars(getSetting('restaurant_name', 'OBJSIS')) ?></span>
        </a>
    </div>

    <ul class="navbar-nav">
        <?php if (hasPermission('dashboard.php')): ?>
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link <?= isActive('dashboard.php', $current_page) ?>">
                    <i class="fas fa-chart-pie"></i> <span>Dashboard</span>
                </a>
            </li>
        <?php endif; ?>
        <?php if (hasPermission('kitchen.php')): ?>
            <li class="nav-item">
                <a href="kitchen.php" class="nav-link <?= isActive('kitchen.php', $current_page) ?>">
                    <i class="fas fa-fire-burner"></i> <span>Kitchen</span>
                </a>
            </li>
        <?php endif; ?>
        <?php if (hasPermission('orders.php')): ?>
            <li class="nav-item">
                <a href="orders.php" class="nav-link <?= isActive('orders.php', $current_page) ?>">
                    <i class="fas fa-receipt"></i> <span>Orders</span>
                </a>
            </li>
        <?php endif; ?>
        <?php if (hasPermission('tables.php')): ?>
            <li class="nav-item">
                <a href="tables.php" class="nav-link <?= isActive('tables.php', $current_page) ?>">
                    <i class="fas fa-chair"></i> <span>Tables</span>
                </a>
            </li>
        <?php endif; ?>
        <?php if (hasPermission('new_order.php')): ?>
            <li class="nav-item">
                <a href="new_order.php" class="nav-link <?= isActive('new_order.php', $current_page) ?>">
                    <i class="fas fa-plus-circle"></i> <span>New Order</span>
                </a>
            </li>
        <?php endif; ?>

        <!-- Management Dropdown -->
        <li class="nav-item dropdown">
            <a href="#" class="nav-link menu-dropdown-toggle">
                <i class="fas fa-briefcase"></i> <span>Management</span> <i class="fas fa-chevron-down" style="font-size:0.6rem; margin-left:4px;"></i>
            </a>
            <ul class="dropdown-menu">
                <?php if (hasPermission('menu.php')): ?><li><a href="menu.php"><i class="fas fa-utensils"></i> Menu</a></li><?php endif; ?>
                <?php if (hasPermission('categories.php')): ?><li><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li><?php endif; ?>
                <?php if (hasPermission('inventory.php')): ?><li><a href="inventory.php"><i class="fas fa-boxes"></i> Inventory</a></li><?php endif; ?>
                <?php if (hasPermission('coupons.php')): ?><li><a href="coupons.php"><i class="fas fa-ticket"></i> Coupons</a></li><?php endif; ?>
                <?php if (hasPermission('shifts.php')): ?><li><a href="shifts.php"><i class="fas fa-clock"></i> Shifts</a></li><?php endif; ?>
            </ul>
        </li>

        <!-- System Dropdown -->
        <li class="nav-item dropdown">
            <a href="#" class="nav-link menu-dropdown-toggle">
                <i class="fas fa-desktop"></i> <span>System</span> <i class="fas fa-chevron-down" style="font-size:0.6rem; margin-left:4px;"></i>
            </a>
            <ul class="dropdown-menu">
                <?php if (hasPermission('users.php')): ?><li><a href="users.php"><i class="fas fa-users"></i> Staff</a></li><?php endif; ?>
                <?php if (hasPermission('roles.php')): ?><li><a href="roles.php"><i class="fas fa-shield"></i> Roles</a></li><?php endif; ?>
                <?php if (hasPermission('stats.php')): ?><li><a href="stats.php"><i class="fas fa-chart-line"></i> Analytics</a></li><?php endif; ?>
                <?php if (hasPermission('reports.php')): ?><li><a href="reports.php"><i class="fas fa-file-invoice"></i> Reports</a></li><?php endif; ?>
                <?php if (hasPermission('history.php')): ?><li><a href="history.php"><i class="fas fa-history"></i> History</a></li><?php endif; ?>
                <?php if (hasPermission('updates.php')): ?><li><a href="updates.php"><i class="fas fa-sync"></i> Updates</a></li><?php endif; ?>
                <?php if (hasPermission('manage_system')): ?><li><a href="addons.php"><i class="fas fa-puzzle-piece"></i> Addons</a></li><?php endif; ?>
                <?php if (hasPermission('settings.php')): ?><li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li><?php endif; ?>
                <?php 
                    if (isset($GLOBALS['addon_system_links'])) {
                        foreach ($GLOBALS['addon_system_links'] as $link) {
                            echo "<li>$link</li>";
                        }
                    }
                ?>
            </ul>
        </li>
    </ul>
    
    <div class="addon-navbar-items" style="display:flex; align-items:center;">
        <?php 
            if (isset($GLOBALS['addon_navbar_items'])) {
                foreach ($GLOBALS['addon_navbar_items'] as $item) {
                    echo $item;
                }
            }
        ?>
    </div>

    <div class="navbar-user">
        <button onclick="toggleTheme()" class="theme-toggle" title="Toggle Theme">
            <i class="fas fa-moon theme-toggle-icon"></i>
        </button>
        
        <div class="nav-item dropdown user-dropdown" style="position:relative;">
            <a href="#" class="nav-link menu-dropdown-toggle" style="display:flex; align-items:center; gap:12px; padding: 4px; border-radius:30px;">
                <div style="text-align:right; display:flex; flex-direction:column; justify-content:center;">
                    <span style="font-weight:700; font-size:0.95rem; line-height:1.2; color:var(--text-main);"><?= htmlspecialchars($user_name) ?></span>
                    <span style="font-size:0.75rem; color:var(--primary-color); text-transform:uppercase; font-weight:800; letter-spacing:0.5px;"><?= htmlspecialchars($user_role) ?></span>
                </div>
                <div style="width: 42px; height: 42px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-color), var(--primary-hover)); display:flex; align-items:center; justify-content:center; color:white; font-weight:bold; font-size:1.2rem; box-shadow: 0 4px 10px rgba(249,115,22,0.3);">
                    <?= strtoupper(substr($user_name, 0, 1)) ?>
                </div>
            </a>
            <ul class="dropdown-menu" style="right: 0; left: auto; min-width: 200px; padding:8px;">
                <li><a href="profile.php"><i class="fas fa-user" style="color:var(--primary-color);"></i> My Profile</a></li>
                <li style="border-top: 1px solid var(--border-color; margin-top:5px; padding-top:5px;"><a href="../logout.php" style="color:var(--danger);"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <button class="mobile-toggle" onclick="toggleMobileNav()"><i class="fas fa-bars"></i></button>
    </div>
</nav>

<script>
    // Handle Dropdowns
    document.querySelectorAll('.menu-dropdown-toggle').forEach(el => {
        el.addEventListener('click', (e) => {
            e.preventDefault();
            const parent = e.target.closest('.dropdown');
            parent.classList.toggle('show');
            // Close others
            document.querySelectorAll('.dropdown').forEach(d => {
                if (d !== parent) d.classList.remove('show');
            });
        });
    });

    // Close dropdowns on outside click
    window.addEventListener('click', (e) => {
        if (!e.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown').forEach(d => d.classList.remove('show'));
        }
    });

    function toggleMobileNav() {
        document.querySelector('.navbar-nav').classList.toggle('mobile-open');
    }

    // Modern Real-time Badges Update
    async function updateNavbarBadges() {
        try {
            const res = await fetch('../api/dashboard_fetch.php');
            const data = await res.json();
            if (data.success) {
                const count = data.stats.active;
                const links = document.querySelectorAll('.nav-link');
                links.forEach(link => {
                    if (link.href.includes('dashboard.php') || link.href.includes('orders.php')) {
                        let badge = link.querySelector('.nav-badge');
                        if (count > 0) {
                            if (!badge) {
                                badge = document.createElement('span');
                                badge.className = 'nav-badge';
                                badge.style.cssText = 'background: linear-gradient(135deg, #ef4444, #f43f5e); color: white; padding: 2px 8px; border-radius: 20px; font-size: 0.75rem; font-weight: 800; margin-left: 6px; box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);';
                                link.appendChild(badge);
                            }
                            badge.innerText = count;
                        } else if (badge) {
                            badge.remove();
                        }
                    }
                });
            }
        } catch (e) {}
    }
    updateNavbarBadges();
    setInterval(updateNavbarBadges, 15000);
</script>
<script src="../assets/js/theme.js"></script>
