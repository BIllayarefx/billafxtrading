<?php
$page_title = 'Accounts';
require_once 'config.php';
require_once 'auth.php';

if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle create account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_account'])) {
    $account_name = trim($_POST['account_name']);
    $starting_balance = floatval($_POST['starting_balance']);
    $risk_mode = $_POST['risk_mode'];
    $risk_percent = floatval($_POST['risk_percent']);
    
    if (empty($account_name)) {
        $error = "Account name is required";
    } elseif ($starting_balance <= 0) {
        $error = "Starting balance must be greater than 0";
    } else {
        $query = "INSERT INTO trading_accounts (user_id, account_name, starting_balance, current_balance, risk_mode, risk_percent) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isddsd", $user_id, $account_name, $starting_balance, $starting_balance, $risk_mode, $risk_percent);
        
        if ($stmt->execute()) {
            header('Location: accounts.php?created=1');
            exit;
        } else {
            $error = "Failed to create account";
        }
    }
}

// Handle delete account
if (isset($_GET['delete'])) {
    $account_id = intval($_GET['delete']);
    $check = $conn->prepare("SELECT COUNT(*) as count FROM trades WHERE account_id = ?");
    $check->bind_param("i", $account_id);
    $check->execute();
    $result = $check->get_result()->fetch_assoc();
    
    if ($result['count'] == 0) {
        $delete = $conn->prepare("DELETE FROM trading_accounts WHERE id = ? AND user_id = ?");
        $delete->bind_param("ii", $account_id, $user_id);
        $delete->execute();
        header('Location: accounts.php?deleted=1');
        exit;
    } else {
        header('Location: accounts.php?error=has_trades');
        exit;
    }
}

// Get filter parameters
$filter_date_range = $_GET['date_range'] ?? 'All Time';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';

// Get account ID from URL or use first account
$account_id = $_GET['id'] ?? null;
if (!$account_id) {
    $first_account = $conn->query("SELECT id FROM trading_accounts WHERE user_id = $user_id LIMIT 1");
    if ($first_account->num_rows > 0) {
        $account_id = $first_account->fetch_assoc()['id'];
    }
}

// Get selected account details
if ($account_id) {
    $account_query = "SELECT * FROM trading_accounts WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($account_query);
    $stmt->bind_param("ii", $account_id, $user_id);
    $stmt->execute();
    $selected_account = $stmt->get_result()->fetch_assoc();
}

// Get all accounts
$all_accounts = $conn->query("SELECT * FROM trading_accounts WHERE user_id = $user_id ORDER BY created_at DESC");

// Get account statistics for selected account
if ($account_id) {
    $stats_query = "SELECT 
    COUNT(DISTINCT t.id) as total_trades,
    SUM(CASE WHEN t.outcome = 'Win' THEN 1 ELSE 0 END) as wins,
    SUM(CASE WHEN t.outcome = 'Loss' THEN 1 ELSE 0 END) as losses,
    SUM(CASE WHEN t.outcome = 'Skipped' THEN 1 ELSE 0 END) as skipped,
    SUM(ta.allocated_pnl) as total_pnl,
    SUM(ta.allocated_r) as total_r,
    AVG(CASE WHEN t.outcome IN ('Win', 'Loss') THEN ta.allocated_r ELSE NULL END) as avg_r
    FROM trades t
    JOIN trade_accounts ta ON t.id = ta.trade_id
    WHERE ta.account_id = ? AND t.user_id = ?";
    
    $stmt = $conn->prepare($stats_query);
    $stmt->bind_param("ii", $account_id, $user_id);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
    
    // Ensure stats have values
    $stats['total_trades'] = $stats['total_trades'] ?? 0;
    $stats['wins'] = $stats['wins'] ?? 0;
    $stats['losses'] = $stats['losses'] ?? 0;
    $stats['skipped'] = $stats['skipped'] ?? 0;
    $stats['total_pnl'] = $stats['total_pnl'] ?? 0;
    $stats['total_r'] = $stats['total_r'] ?? 0;
    $stats['avg_r'] = $stats['avg_r'] ?? 0;
    
    $completed = $stats['wins'] + $stats['losses'];
    $win_rate = $completed > 0 ? round(($stats['wins'] / $completed) * 100, 1) : 0;
    
    $starting = $selected_account['starting_balance'];
    $current = $selected_account['current_balance'];
    $percent_change = $starting > 0 ? round(($current - $starting) / $starting * 100, 1) : 0;
    
    // Build history query with date filters
    $history_query = "SELECT t.*, 
    DATE_FORMAT(t.trade_date, '%M %e, %Y') as formatted_date,
    (SELECT COUNT(*) FROM trade_checklists WHERE trade_id = t.id) as rules_count,
    ta.allocated_pnl, ta.allocated_r
    FROM trades t
    JOIN trade_accounts ta ON t.id = ta.trade_id
    WHERE ta.account_id = ? AND t.user_id = ?";
    
    $history_params = [$account_id, $user_id];
    $history_types = "ii";
    
    if ($filter_date_range != 'All Time') {
        switch($filter_date_range) {
            case 'Last 7 days':
                $history_query .= " AND t.trade_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                break;
            case 'Last 14 days':
                $history_query .= " AND t.trade_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)";
                break;
            case 'Last 20 days':
                $history_query .= " AND t.trade_date >= DATE_SUB(CURDATE(), INTERVAL 20 DAY)";
                break;
            case 'Last 30 days':
                $history_query .= " AND t.trade_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                break;
            case 'Custom':
                if (!empty($filter_date_from)) {
                    $history_query .= " AND t.trade_date >= ?";
                    $history_params[] = $filter_date_from;
                    $history_types .= "s";
                }
                if (!empty($filter_date_to)) {
                    $history_query .= " AND t.trade_date <= ?";
                    $history_params[] = $filter_date_to;
                    $history_types .= "s";
                }
                break;
        }
    }
    
    $history_query .= " ORDER BY t.trade_date DESC, t.created_at DESC";
    $stmt = $conn->prepare($history_query);
    $stmt->bind_param($history_types, ...$history_params);
    $stmt->execute();
    $history = $stmt->get_result();
}

require_once 'header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <!-- Alerts -->
    <?php if (isset($_GET['created'])): ?>
    <div class="bg-green-500/10 border border-green-500 rounded-xl p-4 flex items-center gap-3">
        <i class="fas fa-check-circle text-green-500"></i>
        <span>Account created successfully!</span>
    </div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
    <div class="bg-green-500/10 border border-green-500 rounded-xl p-4 flex items-center gap-3">
        <i class="fas fa-check-circle text-green-500"></i>
        <span>Account deleted successfully!</span>
    </div>
    <?php endif; ?>
    <?php if (isset($_GET['error']) && $_GET['error'] == 'has_trades'): ?>
    <div class="bg-red-500/10 border border-red-500 rounded-xl p-4 flex items-center gap-3">
        <i class="fas fa-exclamation-circle text-red-500"></i>
        <span>Cannot delete account that has trades</span>
    </div>
    <?php endif; ?>

    <?php if (isset($selected_account)): ?>
        <!-- Header -->
        <div class="flex flex-wrap justify-between items-center gap-4">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-500 to-purple-500 bg-clip-text text-transparent"><?php echo htmlspecialchars($selected_account['account_name']); ?></h1>
                <p class="text-gray-400 mt-1">Account Details & Performance</p>
            </div>
            <button class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-5 rounded-xl transition transform hover:scale-105" onclick="$('#createAccountModal').modal('show')">
                <i class="fas fa-plus"></i> New Account
            </button>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-gray-900/80 backdrop-blur-sm border border-gray-800 rounded-2xl p-5">
                <div class="text-gray-400 text-sm">Account Balance</div>
                <div class="text-white text-3xl font-bold">$<?php echo number_format($selected_account['current_balance'], 2); ?></div>
                <div class="<?php echo $stats['total_pnl'] >= 0 ? 'text-green-500' : 'text-red-500'; ?> text-sm mt-1">
                    <?php echo $stats['total_pnl'] >= 0 ? '+' : ''; ?>$<?php echo number_format($stats['total_pnl'], 2); ?> (<?php echo $percent_change >= 0 ? '+' : ''; ?><?php echo $percent_change; ?>%)
                </div>
            </div>
            <div class="bg-gray-900/80 backdrop-blur-sm border border-gray-800 rounded-2xl p-5">
                <div class="text-gray-400 text-sm">Total PnL ($)</div>
                <div class="<?php echo $stats['total_pnl'] >= 0 ? 'text-green-500' : 'text-red-500'; ?> text-3xl font-bold">
                    <?php echo $stats['total_pnl'] >= 0 ? '+' : ''; ?>$<?php echo number_format($stats['total_pnl'], 2); ?>
                </div>
                <div class="text-gray-400 text-sm">From <?php echo $stats['total_trades']; ?> trades</div>
            </div>
            <div class="bg-gray-900/80 backdrop-blur-sm border border-gray-800 rounded-2xl p-5">
                <div class="text-gray-400 text-sm">Total R</div>
                <div class="<?php echo $stats['total_r'] >= 0 ? 'text-green-500' : 'text-red-500'; ?> text-3xl font-bold">
                    <?php echo $stats['total_r'] >= 0 ? '+' : ''; ?><?php echo number_format($stats['total_r'], 1); ?>R
                </div>
                <div class="text-gray-400 text-sm">Avg: <?php echo number_format($stats['avg_r'], 2); ?>R</div>
            </div>
            <div class="bg-gray-900/80 backdrop-blur-sm border border-gray-800 rounded-2xl p-5">
                <div class="text-gray-400 text-sm">Trades Linked</div>
                <div class="text-white text-3xl font-bold"><?php echo $stats['total_trades']; ?></div>
                <div class="text-gray-400 text-sm"><?php echo $stats['wins']; ?>W / <?php echo $stats['losses']; ?>L</div>
            </div>
        </div>

        <!-- Account Performance Card -->
        <div class="bg-gradient-to-r from-gray-900 to-gray-800 rounded-2xl p-6 border border-gray-800 relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-500/5 to-purple-500/5 animate-pulse"></div>
            <div class="relative z-10">
                <h3 class="text-white text-xl font-semibold mb-2">Account Performance</h3>
                <div class="text-white text-4xl font-bold mb-4">$<?php echo number_format($selected_account['current_balance'], 2); ?></div>
                <div class="flex flex-wrap gap-6">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-arrow-<?php echo $stats['total_pnl'] >= 0 ? 'up' : 'down'; ?>"></i>
                        <span class="<?php echo $stats['total_pnl'] >= 0 ? 'text-green-500' : 'text-red-500'; ?> font-semibold">
                            <?php echo $stats['total_pnl'] >= 0 ? '+' : ''; ?>$<?php echo number_format($stats['total_pnl'], 2); ?>
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-percent"></i>
                        <span class="<?php echo $stats['total_r'] >= 0 ? 'text-green-500' : 'text-red-500'; ?> font-semibold">
                            <?php echo $stats['total_r'] >= 0 ? '+' : ''; ?><?php echo number_format($stats['total_r'], 1); ?>R
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-trophy"></i>
                        <span class="text-green-500 font-semibold"><?php echo $win_rate; ?>% Win Rate</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="bg-gray-900/80 backdrop-blur-sm border border-gray-800 rounded-2xl p-6">
            <div class="flex flex-wrap gap-3 items-center">
                <span class="text-gray-400">Date Range:</span>
                <div class="flex flex-wrap gap-2">
                    <a href="?id=<?php echo $account_id; ?>&date_range=All Time" class="px-4 py-2 rounded-full border border-gray-700 text-sm font-medium <?php echo $filter_date_range == 'All Time' ? 'bg-blue-600 text-white border-blue-600' : 'bg-gray-800 text-gray-400 hover:bg-gray-700'; ?>">All Time</a>
                    <a href="?id=<?php echo $account_id; ?>&date_range=Last 7 days" class="px-4 py-2 rounded-full border border-gray-700 text-sm font-medium <?php echo $filter_date_range == 'Last 7 days' ? 'bg-blue-600 text-white border-blue-600' : 'bg-gray-800 text-gray-400 hover:bg-gray-700'; ?>">Last 7 days</a>
                    <a href="?id=<?php echo $account_id; ?>&date_range=Last 14 days" class="px-4 py-2 rounded-full border border-gray-700 text-sm font-medium <?php echo $filter_date_range == 'Last 14 days' ? 'bg-blue-600 text-white border-blue-600' : 'bg-gray-800 text-gray-400 hover:bg-gray-700'; ?>">Last 14 days</a>
                    <a href="?id=<?php echo $account_id; ?>&date_range=Last 20 days" class="px-4 py-2 rounded-full border border-gray-700 text-sm font-medium <?php echo $filter_date_range == 'Last 20 days' ? 'bg-blue-600 text-white border-blue-600' : 'bg-gray-800 text-gray-400 hover:bg-gray-700'; ?>">Last 20 days</a>
                    <a href="?id=<?php echo $account_id; ?>&date_range=Last 30 days" class="px-4 py-2 rounded-full border border-gray-700 text-sm font-medium <?php echo $filter_date_range == 'Last 30 days' ? 'bg-blue-600 text-white border-blue-600' : 'bg-gray-800 text-gray-400 hover:bg-gray-700'; ?>">Last 30 days</a>
                    <span class="px-4 py-2 rounded-full border border-gray-700 text-sm font-medium cursor-pointer <?php echo $filter_date_range == 'Custom' ? 'bg-blue-600 text-white border-blue-600' : 'bg-gray-800 text-gray-400 hover:bg-gray-700'; ?>" onclick="showCustomDateModal()">Custom</span>
                </div>
            </div>
            <?php if ($filter_date_range == 'Custom'): ?>
            <div class="mt-4 flex flex-wrap gap-3">
                <input type="date" id="custom_from" value="<?php echo $filter_date_from; ?>" class="bg-gray-800 border border-gray-700 rounded-xl p-2 text-white">
                <input type="date" id="custom_to" value="<?php echo $filter_date_to; ?>" class="bg-gray-800 border border-gray-700 rounded-xl p-2 text-white">
                <button onclick="applyCustomDate(<?php echo $account_id; ?>)" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-xl text-white font-semibold">Apply</button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Trade History Table -->
        <div class="bg-gray-900/80 backdrop-blur-sm border border-gray-800 rounded-2xl overflow-hidden">
            <div class="p-6 border-b border-gray-800 flex justify-between items-center">
                <h3 class="text-white text-lg font-semibold">Trade History</h3>
                <?php if ($filter_date_range != 'All Time'): ?>
                <span class="text-blue-500 text-sm">Filtered: <?php echo $filter_date_range; ?></span>
                <?php endif; ?>
            </div>
            <?php if ($history->num_rows > 0): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="text-gray-400 text-sm uppercase border-b border-gray-800">
                        <tr>
                            <th class="p-4">Date</th>
                            <th class="p-4">Pair</th>
                            <th class="p-4">Grade</th>
                            <th class="p-4">Charts</th>
                            <th class="p-4">RR</th>
                            <th class="p-4">PnL ($)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($trade = $history->fetch_assoc()): 
                            $grade_class = '';
                            $grade = $trade['trade_grade'] ?? 'C';
                            switch($grade) {
                                case 'A+': $grade_class = 'bg-green-600'; break;
                                case 'A': $grade_class = 'bg-blue-600'; break;
                                case 'B+': $grade_class = 'bg-yellow-600'; break;
                                case 'B': $grade_class = 'bg-orange-600'; break;
                                default: $grade_class = 'bg-red-600';
                            }
                            $has_charts = $conn->query("SELECT id FROM chart_snapshots WHERE trade_id = {$trade['id']} LIMIT 1")->num_rows > 0;
                        ?>
                        <tr class="border-b border-gray-800 hover:bg-gray-800/50 transition cursor-pointer" onclick="window.location.href='trade_details.php?id=<?php echo $trade['id']; ?>'">
                            <td class="p-4"><?php echo $trade['formatted_date']; ?></td>
                            <td class="p-4"><?php echo $trade['pair']; ?></td>
                            <td class="p-4"><span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $grade_class; ?>"><?php echo $grade; ?></span></td>
                            <td class="p-4"><?php if ($has_charts): ?><i class="fas fa-image text-blue-500"></i><?php endif; ?></td>
                            <td class="p-4 <?php echo ($trade['allocated_r'] ?? 0) >= 0 ? 'text-green-500' : 'text-red-500'; ?>">
                                <?php echo ($trade['allocated_r'] ?? 0) >= 0 ? '+' : ''; ?><?php echo number_format($trade['allocated_r'] ?? 0, 1); ?>R
                            </td>
                            <td class="p-4 <?php echo ($trade['allocated_pnl'] ?? 0) >= 0 ? 'text-green-500' : 'text-red-500'; ?>">
                                <?php echo ($trade['allocated_pnl'] ?? 0) >= 0 ? '+' : ''; ?>$<?php echo number_format($trade['allocated_pnl'] ?? 0, 2); ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-4 text-gray-400 text-sm">Showing <?php echo $history->num_rows; ?> trades</div>
            <?php else: ?>
            <div class="text-center py-16 text-gray-400">
                <i class="fas fa-inbox text-5xl mb-4 text-gray-700"></i>
                <h3 class="text-white text-lg font-semibold mb-2">No trades found</h3>
                <p><?php echo ($filter_date_range != 'All Time') ? 'No trades match the selected date range.' : 'This account has no trades yet'; ?></p>
            </div>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <!-- No account selected -->
        <div class="text-center py-16 bg-gray-900/80 backdrop-blur-sm border border-gray-800 rounded-2xl">
            <i class="fas fa-wallet text-5xl text-gray-700 mb-4"></i>
            <h3 class="text-white text-xl font-semibold mb-2">No Accounts Found</h3>
            <p class="text-gray-400 mb-6">Create your first trading account to get started</p>
            <button class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-5 rounded-xl transition" onclick="$('#createAccountModal').modal('show')">
                <i class="fas fa-plus"></i> Create Account
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- Sidebar Accounts List (floating on desktop, bottom nav on mobile) -->
<div class="fixed left-4 top-24 z-40 hidden lg:block">
    <div class="bg-gray-900/90 backdrop-blur-lg border border-gray-800 rounded-2xl w-64 shadow-xl">
        <div class="p-4 border-b border-gray-800 flex justify-between items-center">
            <h3 class="text-white font-semibold">Your Accounts</h3>
            <button class="w-8 h-8 rounded-lg bg-blue-600 hover:bg-blue-700 text-white" onclick="$('#createAccountModal').modal('show')"><i class="fas fa-plus"></i></button>
        </div>
        <div class="p-2 space-y-1">
            <?php if ($all_accounts->num_rows > 0): ?>
                <?php while($acc = $all_accounts->fetch_assoc()): ?>
                <a href="accounts.php?id=<?php echo $acc['id']; ?>" class="flex justify-between items-center p-3 rounded-xl <?php echo ($acc['id'] == $account_id) ? 'bg-blue-600' : 'hover:bg-gray-800'; ?> transition">
                    <div>
                        <div class="text-white font-medium"><?php echo htmlspecialchars($acc['account_name']); ?></div>
                        <div class="text-gray-400 text-sm">$<?php echo number_format($acc['current_balance'], 2); ?></div>
                    </div>
                    <button class="text-gray-400 hover:text-red-500" onclick="event.preventDefault(); deleteAccount(<?php echo $acc['id']; ?>, '<?php echo htmlspecialchars($acc['account_name']); ?>')">
                        <i class="fas fa-times"></i>
                    </button>
                </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center text-gray-400 py-4">No accounts yet</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Create Account Modal -->
<div class="modal fade" id="createAccountModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="bg-gray-900 border border-gray-800 rounded-2xl shadow-2xl">
            <div class="p-5 border-b border-gray-800 flex justify-between items-center">
                <h5 class="text-white text-xl font-semibold">Create Trading Account</h5>
                <button type="button" class="text-gray-400 hover:text-white" data-bs-dismiss="modal"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST">
                <div class="p-5 space-y-4">
                    <input type="hidden" name="create_account" value="1">
                    <div>
                        <label class="block text-gray-400 text-sm mb-1">Account Name</label>
                        <input type="text" name="account_name" class="w-full bg-gray-800 border border-gray-700 rounded-xl p-3 text-white focus:outline-none focus:border-blue-500" placeholder="e.g. Main Account" required>
                    </div>
                    <div>
                        <label class="block text-gray-400 text-sm mb-1">Starting Balance ($)</label>
                        <div class="grid grid-cols-3 gap-2 mb-2">
                            <button type="button" class="bg-gray-800 hover:bg-blue-600 p-2 rounded-lg text-white transition" onclick="setBalance(1000)">$1,000</button>
                            <button type="button" class="bg-gray-800 hover:bg-blue-600 p-2 rounded-lg text-white transition" onclick="setBalance(5000)">$5,000</button>
                            <button type="button" class="bg-gray-800 hover:bg-blue-600 p-2 rounded-lg text-white transition" onclick="setBalance(10000)">$10,000</button>
                            <button type="button" class="bg-gray-800 hover:bg-blue-600 p-2 rounded-lg text-white transition" onclick="setBalance(25000)">$25,000</button>
                            <button type="button" class="bg-gray-800 hover:bg-blue-600 p-2 rounded-lg text-white transition" onclick="setBalance(50000)">$50,000</button>
                            <button type="button" class="bg-gray-800 hover:bg-blue-600 p-2 rounded-lg text-white transition" onclick="setBalance(100000)">$100,000</button>
                        </div>
                        <input type="number" name="starting_balance" id="starting_balance" class="w-full bg-gray-800 border border-gray-700 rounded-xl p-3 text-white" step="0.01" min="1" value="1000" required>
                    </div>
                    <div>
                        <label class="block text-gray-400 text-sm mb-1">Risk Mode</label>
                        <select name="risk_mode" class="w-full bg-gray-800 border border-gray-700 rounded-xl p-3 text-white">
                            <option value="percent">Percent Risk (% of balance)</option>
                            <option value="fixed">Fixed Amount ($ per trade)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-400 text-sm mb-1">Risk Percent (%)</label>
                        <input type="number" name="risk_percent" class="w-full bg-gray-800 border border-gray-700 rounded-xl p-3 text-white" step="0.1" min="0.1" max="5" value="1.0">
                    </div>
                </div>
                <div class="p-5 border-t border-gray-800 flex justify-end gap-3">
                    <button type="button" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-xl text-white transition" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-xl text-white font-semibold transition">Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Custom Date Modal -->
<div class="modal fade" id="customDateModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="bg-gray-900 border border-gray-800 rounded-2xl shadow-2xl">
            <div class="p-5 border-b border-gray-800 flex justify-between items-center">
                <h5 class="text-white text-xl font-semibold">Select Date Range</h5>
                <button type="button" class="text-gray-400 hover:text-white" data-bs-dismiss="modal"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-5 space-y-4">
                <div>
                    <label class="block text-gray-400 text-sm mb-1">From</label>
                    <input type="date" id="modal_from" class="w-full bg-gray-800 border border-gray-700 rounded-xl p-3 text-white">
                </div>
                <div>
                    <label class="block text-gray-400 text-sm mb-1">To</label>
                    <input type="date" id="modal_to" class="w-full bg-gray-800 border border-gray-700 rounded-xl p-3 text-white" value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            <div class="p-5 border-t border-gray-800 flex justify-end gap-3">
                <button type="button" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-xl text-white transition" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-xl text-white font-semibold transition" onclick="applyModalCustomDate(<?php echo $account_id; ?>)">Apply</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function setBalance(amount) {
    document.getElementById('starting_balance').value = amount;
}

function deleteAccount(id, name) {
    Swal.fire({
        title: 'Delete Account?',
        html: `Are you sure you want to delete <strong>${name}</strong>?<br><br>
               <span style="color: #ef4444;">This account must have no trades to be deleted.</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Delete',
        background: '#11161f',
        color: 'white'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'accounts.php?delete=' + id;
        }
    });
}

function showCustomDateModal() {
    $('#customDateModal').modal('show');
}

function applyCustomDate(accountId) {
    let from = document.getElementById('custom_from').value;
    let to = document.getElementById('custom_to').value;
    if (!from || !to) {
        Swal.fire({ icon: 'warning', title: 'Select Dates', text: 'Please select both from and to dates', background: '#11161f', color: 'white' });
        return;
    }
    window.location.href = `accounts.php?id=${accountId}&date_range=Custom&date_from=${from}&date_to=${to}`;
}

function applyModalCustomDate(accountId) {
    let from = document.getElementById('modal_from').value;
    let to = document.getElementById('modal_to').value;
    if (!from || !to) {
        Swal.fire({ icon: 'warning', title: 'Select Dates', text: 'Please select both from and to dates', background: '#11161f', color: 'white' });
        return;
    }
    $('#customDateModal').modal('hide');
    window.location.href = `accounts.php?id=${accountId}&date_range=Custom&date_from=${from}&date_to=${to}`;
}
</script>