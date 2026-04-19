<?php
// header.php - Classic dark theme with mobile bottom navigation
if (!isset($_SESSION['user_id'])) {
    return;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo $page_title ?? 'BILLA_FX'; ?> - BILLA_FX Trading Journal</title>

    <!-- Tailwind CSS + Inter font -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <!-- jQuery + Bootstrap (for modals) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #0a0c10;
            color: #e2e8f0;
            padding-bottom: 70px; /* Space for mobile bottom nav */
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #1e293b; border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: #3b82f6; border-radius: 10px; }
        /* Remove Bootstrap's focus ring */
        .btn:focus, .form-control:focus { box-shadow: none; }
        /* Custom utility for line clamp */
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body x-data="{ mobileMenuOpen: false }">

    <!-- Top Header (Desktop) -->
    <header class="bg-gray-900 border-b border-gray-800 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center space-x-3 cursor-pointer" onclick="window.location.href='dashboard.php'">
                    <div class="w-8 h-8 bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-lg">B</span>
                    </div>
                    <span class="text-white font-bold text-xl hidden sm:inline">BILLA_FX</span>
                </div>

                <!-- Desktop Navigation -->
                <nav class="hidden md:flex space-x-1">
                    <a href="dashboard.php" class="px-4 py-2 rounded-lg transition <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white'; ?>">
                        <i class="fas fa-check-circle mr-2"></i> Checklist
                    </a>
                    <a href="trade_journal.php" class="px-4 py-2 rounded-lg transition <?php echo basename($_SERVER['PHP_SELF']) == 'trade_journal.php' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white'; ?>">
                        <i class="fas fa-book-open mr-2"></i> Journal
                    </a>
                    <a href="weekly_outlook.php" class="px-4 py-2 rounded-lg transition <?php echo basename($_SERVER['PHP_SELF']) == 'weekly_outlook.php' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white'; ?>">
                        <i class="fas fa-calendar-week mr-2"></i> Outlook
                    </a>
                    <a href="accounts.php" class="px-4 py-2 rounded-lg transition <?php echo basename($_SERVER['PHP_SELF']) == 'accounts.php' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white'; ?>">
                        <i class="fas fa-wallet mr-2"></i> Accounts
                    </a>
                    <a href="profile.php" class="px-4 py-2 rounded-lg transition <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white'; ?>">
                        <i class="fas fa-user mr-2"></i> Profile
                    </a>
                </nav>

                <!-- User Menu -->
                <div class="flex items-center space-x-3">
                    <?php
                    $today_ritual = getTodayRitual($_SESSION['user_id']);
                    $ritual_class = ($today_ritual && $today_ritual['completed']) ? 'bg-green-600' : 'bg-yellow-600 animate-pulse';
                    ?>
                    <button class="relative <?php echo $ritual_class; ?> hover:scale-105 transition rounded-full w-10 h-10 flex items-center justify-center" onclick="showDailyRitual()">
                        <i class="fas fa-sun text-white text-lg"></i>
                        <?php if (!$today_ritual || !$today_ritual['completed']): ?>
                            <span class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full animate-ping"></span>
                        <?php endif; ?>
                    </button>

                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center space-x-2 bg-gray-800 rounded-full px-3 py-2 hover:bg-gray-700 transition">
                            <div class="w-8 h-8 bg-gradient-to-r from-blue-600 to-purple-600 rounded-full flex items-center justify-center text-white font-bold">
                                <?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?>
                            </div>
                            <span class="hidden sm:inline text-sm font-medium"><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></span>
                            <i class="fas fa-chevron-down text-xs text-gray-400"></i>
                        </button>
                        <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-gray-800 rounded-lg shadow-lg border border-gray-700 py-1 z-50">
                            <a href="profile.php" class="block px-4 py-2 text-sm text-gray-300 hover:bg-gray-700">Profile</a>
                            <a href="logout.php" class="block px-4 py-2 text-sm text-red-400 hover:bg-gray-700">Logout</a>
                        </div>
                    </div>

                    <!-- Mobile menu button (hamburger) -->
                    <button class="md:hidden text-gray-300 focus:outline-none" @click="mobileMenuOpen = !mobileMenuOpen">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                </div>
            </div>

            <!-- Mobile Navigation Dropdown (full menu) -->
            <div x-show="mobileMenuOpen" x-transition.duration.300ms class="md:hidden py-4 border-t border-gray-800">
                <div class="flex flex-col space-y-2">
                    <a href="dashboard.php" class="px-4 py-2 rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800'; ?>">
                        <i class="fas fa-check-circle mr-2"></i> Checklist
                    </a>
                    <a href="trade_journal.php" class="px-4 py-2 rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'trade_journal.php' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800'; ?>">
                        <i class="fas fa-book-open mr-2"></i> Journal
                    </a>
                    <a href="weekly_outlook.php" class="px-4 py-2 rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'weekly_outlook.php' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800'; ?>">
                        <i class="fas fa-calendar-week mr-2"></i> Outlook
                    </a>
                    <a href="accounts.php" class="px-4 py-2 rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'accounts.php' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800'; ?>">
                        <i class="fas fa-wallet mr-2"></i> Accounts
                    </a>
                    <a href="profile.php" class="px-4 py-2 rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800'; ?>">
                        <i class="fas fa-user mr-2"></i> Profile
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Mobile Bottom Navigation (fixed) -->
    <nav class="fixed bottom-0 left-0 right-0 bg-gray-900 border-t border-gray-800 z-40 md:hidden">
        <div class="flex justify-around items-center h-14">
            <a href="dashboard.php" class="flex flex-col items-center text-xs <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'text-blue-500' : 'text-gray-400'; ?>">
                <i class="fas fa-check-circle text-xl"></i>
                <span>Checklist</span>
            </a>
            <a href="trade_journal.php" class="flex flex-col items-center text-xs <?php echo basename($_SERVER['PHP_SELF']) == 'trade_journal.php' ? 'text-blue-500' : 'text-gray-400'; ?>">
                <i class="fas fa-book-open text-xl"></i>
                <span>Journal</span>
            </a>
            <a href="weekly_outlook.php" class="flex flex-col items-center text-xs <?php echo basename($_SERVER['PHP_SELF']) == 'weekly_outlook.php' ? 'text-blue-500' : 'text-gray-400'; ?>">
                <i class="fas fa-calendar-week text-xl"></i>
                <span>Outlook</span>
            </a>
            <a href="accounts.php" class="flex flex-col items-center text-xs <?php echo basename($_SERVER['PHP_SELF']) == 'accounts.php' ? 'text-blue-500' : 'text-gray-400'; ?>">
                <i class="fas fa-wallet text-xl"></i>
                <span>Accounts</span>
            </a>
            <a href="profile.php" class="flex flex-col items-center text-xs <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'text-blue-500' : 'text-gray-400'; ?>">
                <i class="fas fa-user text-xl"></i>
                <span>Profile</span>
            </a>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">