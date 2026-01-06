<?php
// includes/sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
$user_role = $_SESSION['user_role'] ?? 'waiter';
$user_name = $_SESSION['user_name'] ?? 'User';

function isActive($page, $current)
{
    return $page === $current ? 'active' : '';
}
?>

<aside class="sidebar" id="mainSidebar">
    <div class="sidebar-header">
        <span class="sidebar-logo-text">
            <?= htmlspecialchars(getSetting('restaurant_name', 'OBJSIS')) ?>
        </span>
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <i class="fas fa-chevron-left"></i>
        </button>
    </div>

    <div class="nav-groups">
        <!-- Group: POS & Orders -->
        <div class="nav-group" id="group-pos">
            <div class="nav-group-header" onclick="toggleNavGroup('group-pos')">
                <span>POS & Orders</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <ul class="nav-links">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link <?= isActive('dashboard.php', $current_page) ?>"
                        title="Dashboard">
                        <i class="fas fa-home"></i> <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="orders.php" class="nav-link <?= isActive('orders.php', $current_page) ?>"
                        title="Active Orders">
                        <i class="fas fa-receipt"></i> <span>Active Orders</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="tables.php" class="nav-link <?= isActive('tables.php', $current_page) ?>" title="Tables">
                        <i class="fas fa-chair"></i> <span>Tables</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Group: Management -->
        <div class="nav-group" id="group-mgmt">
            <div class="nav-group-header" onclick="toggleNavGroup('group-mgmt')">
                <span>Management</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <ul class="nav-links">
                <li class="nav-item">
                    <a href="menu.php" class="nav-link <?= isActive('menu.php', $current_page) ?>" title="Menu">
                        <i class="fas fa-utensils"></i> <span>Menu</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="inventory.php" class="nav-link <?= isActive('inventory.php', $current_page) ?>"
                        title="Inventory">
                        <i class="fas fa-boxes"></i> <span>Inventory</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="coupons.php" class="nav-link <?= isActive('coupons.php', $current_page) ?>"
                        title="Coupons">
                        <i class="fas fa-ticket-alt"></i> <span>Coupons</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="shifts.php" class="nav-link <?= isActive('shifts.php', $current_page) ?>" title="Shifts">
                        <i class="fas fa-clock"></i> <span>Shifts</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Group: System & History -->
        <?php if ($user_role === 'admin'): ?>
            <div class="nav-group" id="group-system">
                <div class="nav-group-header" onclick="toggleNavGroup('group-system')">
                    <span>System & Logs</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <ul class="nav-links">
                    <li class="nav-item">
                        <a href="users.php" class="nav-link <?= isActive('users.php', $current_page) ?>" title="Employees">
                            <i class="fas fa-users"></i> <span>Employees</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="stats.php" class="nav-link <?= isActive('stats.php', $current_page) ?>" title="Statistics">
                            <i class="fas fa-chart-line"></i> <span>Statistics</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="history.php" class="nav-link <?= isActive('history.php', $current_page) ?>"
                            title="History">
                            <i class="fas fa-history"></i> <span>History</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="updates.php" class="nav-link <?= isActive('updates.php', $current_page) ?>"
                            title="Updates">
                            <i class="fas fa-sync"></i> <span>Updates</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="settings.php" class="nav-link <?= isActive('settings.php', $current_page) ?>"
                            title="Settings">
                            <i class="fas fa-cog"></i> <span>Settings</span>
                        </a>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
    </div>

    <div class="user-profile">
        <div class="user-info">
            <div class="user-name">
                <?= htmlspecialchars($user_name) ?>
            </div>
            <div class="user-role">
                <?= htmlspecialchars($user_role) ?>
            </div>
        </div>
        <div class="user-actions">
            <a href="../logout.php" title="Logout">
                <i class="fas fa-sign-out-alt"></i>
            </a>
            <button onclick="toggleTheme()" title="Toggle Theme">
                <i class="fas fa-moon theme-toggle-icon"></i>
            </button>
        </div>
    </div>
</aside>

<script>
    function toggleSidebar() {
        const body = document.body;
        const sidebar = document.getElementById('mainSidebar');
        // Toggle class on body for reliable styling
        body.classList.toggle('sidebar-collapsed-state');

        // Toggle class on sidebar (legacy support/specific animations)
        sidebar.classList.toggle('collapsed');

        // Save state
        localStorage.setItem('sidebar_collapsed', body.classList.contains('sidebar-collapsed-state'));
    }

    function toggleNavGroup(groupId) {
        const group = document.getElementById(groupId);
        if (!group) return;

        group.classList.toggle('collapsed');

        // Save state
        const groupStates = JSON.parse(localStorage.getItem('nav_group_states') || '{}');
        groupStates[groupId] = group.classList.contains('collapsed');
        localStorage.setItem('nav_group_states', JSON.stringify(groupStates));
    }

    // Restore Global Sidebar State IMMEDIATELY
    // This runs before the body is fully rendered, but classList works on body if it exists, or we add to documentElement if needed. 
    // Actually, at this point in the DOM (end of sidebar inclusion), body exists.
    if (localStorage.getItem('sidebar_collapsed') === 'true') {
        document.body.classList.add('sidebar-collapsed-state');
        document.getElementById('mainSidebar').classList.add('collapsed');
    }

    // Restore Nav Group States
    const groupStates = JSON.parse(localStorage.getItem('nav_group_states') || '{}');
    Object.keys(groupStates).forEach(groupId => {
        const group = document.getElementById(groupId);
        if (group && groupStates[groupId]) {
            group.classList.add('collapsed');
        }
    });
</script>
<script src="../assets/js/theme.js"></script>