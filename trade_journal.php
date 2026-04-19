<?php
$page_title = 'Trade Journal';
require_once 'config.php';
require_once 'auth.php';

if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Delete trade (same logic, but we'll keep it as is)
if (isset($_GET['delete'])) {
    $trade_id = intval($_GET['delete']);
    $conn->begin_transaction();
    try {
        $get_allocations = "SELECT account_id, allocated_pnl FROM trade_accounts WHERE trade_id = ?";
        $stmt = $conn->prepare($get_allocations);
        $stmt->bind_param("i", $trade_id);
        $stmt->execute();
        $allocations = $stmt->get_result();
        while ($alloc = $allocations->fetch_assoc()) {
            $reverse_pnl = -$alloc['allocated_pnl'];
            $update_balance = "UPDATE trading_accounts SET current_balance = current_balance + ? WHERE id = ? AND user_id = ?";
            $stmt2 = $conn->prepare($update_balance);
            $stmt2->bind_param("dii", $reverse_pnl, $alloc['account_id'], $user_id);
            $stmt2->execute();
        }
        $delete_query = "DELETE FROM trades WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("ii", $trade_id, $user_id);
        $stmt->execute();
        $conn->commit();
        header('Location: trade_journal.php?deleted=1');
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Failed to delete trade";
    }
}

// Get filter parameters
$filter_grade = $_GET['grade'] ?? '';
$filter_outcome = $_GET['outcome'] ?? '';
$filter_pair = $_GET['pair'] ?? '';
$filter_date_range = $_GET['date_range'] ?? 'All Time';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$query = "SELECT t.*, 
                 GROUP_CONCAT(DISTINCT a.account_name SEPARATOR ', ') as account_names,
                 (SELECT COUNT(*) FROM trade_checklists WHERE trade_id = t.id) as rules_count,
                 (SELECT GROUP_CONCAT(emotion) FROM trade_psychology WHERE trade_id = t.id) as emotions
          FROM trades t 
          LEFT JOIN trade_accounts ta ON t.id = ta.trade_id
          LEFT JOIN trading_accounts a ON ta.account_id = a.id
          WHERE t.user_id = ?";

$params = [$user_id];
$types = "i";

if (!empty($filter_grade)) {
    $query .= " AND t.trade_grade = ?";
    $params[] = $filter_grade;
    $types .= "s";
}
if (!empty($filter_outcome)) {
    $query .= " AND t.outcome = ?";
    $params[] = $filter_outcome;
    $types .= "s";
}
if (!empty($filter_pair)) {
    $query .= " AND t.pair = ?";
    $params[] = $filter_pair;
    $types .= "s";
}
if ($filter_date_range != 'All Time') {
    switch($filter_date_range) {
        case 'Last 7 days':
            $query .= " AND t.trade_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            break;
        case 'Last 14 days':
            $query .= " AND t.trade_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)";
            break;
        case 'Last 20 days':
            $query .= " AND t.trade_date >= DATE_SUB(CURDATE(), INTERVAL 20 DAY)";
            break;
        case 'Last 30 days':
            $query .= " AND t.trade_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            break;
        case 'Custom':
            if (!empty($filter_date_from)) {
                $query .= " AND t.trade_date >= ?";
                $params[] = $filter_date_from;
                $types .= "s";
            }
            if (!empty($filter_date_to)) {
                $query .= " AND t.trade_date <= ?";
                $params[] = $filter_date_to;
                $types .= "s";
            }
            break;
    }
}
if (!empty($search)) {
    $query .= " AND (t.pair LIKE ? OR t.notes LIKE ? OR t.skip_reason LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}
$query .= " GROUP BY t.id ORDER BY t.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$trades = $stmt->get_result();

// Statistics
$stats_query = "SELECT 
                COUNT(DISTINCT t.id) as total,
                SUM(CASE WHEN t.outcome = 'Win' THEN 1 ELSE 0 END) as wins,
                SUM(CASE WHEN t.outcome = 'Loss' THEN 1 ELSE 0 END) as losses,
                SUM(CASE WHEN t.outcome = 'Skipped' THEN 1 ELSE 0 END) as skipped,
                AVG(ta.allocated_r) as avg_r,
                SUM(ta.allocated_pnl) as total_pnl
                FROM trades t
                LEFT JOIN trade_accounts ta ON t.id = ta.trade_id
                WHERE t.user_id = ?";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

$pairs_query = "SELECT DISTINCT pair FROM trades WHERE user_id = ? ORDER BY pair";
$stmt = $conn->prepare($pairs_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pairs = $stmt->get_result();

require_once 'header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <!-- Header -->
    <div class="flex flex-wrap justify-between items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-500 to-purple-500 bg-clip-text text-transparent">Trade Journal</h1>
            <p class="text-gray-400 mt-1">Review and manage all your trades</p>
        </div>
        <a href="dashboard.php" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-5 rounded-xl transition transform hover:scale-105 inline-flex items-center gap-2">
            <i class="fas fa-plus"></i> New Trade
        </a>
    </div>

    <!-- Alerts -->
    <?php if (isset($_GET['deleted'])): ?>
    <div class="bg-green-500/10 border border-green-500 rounded-xl p-4 flex items-center gap-3">
        <i class="fas fa-check-circle text-green-500"></i>
        <span>Trade deleted successfully!</span>
    </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-gray-900/80 backdrop-blur-sm border border-gray-800 rounded-2xl p-5">
            <div class="text-gray-400 text-sm">Total Trades</div>
            <div class="text-white text-3xl font-bold"><?php echo $stats['total'] ?? 0; ?></div>
        </div>
        <div class="bg-gray-900/80 backdrop-blur-sm border border-gray-800 rounded-2xl p-5">
            <div class="text-gray-400 text-sm">Wins</div>
            <div class="text-green-500 text-3xl font-bold"><?php echo $stats['wins'] ?? 0; ?></div>
        </div>
        <div class="bg-gray-900/80 backdrop-blur-sm border border-gray-800 rounded-2xl p-5">
            <div class="text-gray-400 text-sm">Losses</div>
            <div class="text-red-500 text-3xl font-bold"><?php echo $stats['losses'] ?? 0; ?></div>
        </div>
        <div class="bg-gray-900/80 backdrop-blur-sm border border-gray-800 rounded-2xl p-5">
            <div class="text-gray-400 text-sm">Total PnL</div>
            <div class="<?php echo ($stats['total_pnl'] ?? 0) >= 0 ? 'text-green-500' : 'text-red-500'; ?> text-3xl font-bold">
                <?php echo ($stats['total_pnl'] ?? 0) >= 0 ? '+' : ''; ?>$<?php echo number_format($stats['total_pnl'] ?? 0, 2); ?>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-gray-900/80 backdrop-blur-sm border border-gray-800 rounded-2xl p-6">
        <form method="GET" id="filterForm" class="space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <input type="text" name="search" class="bg-gray-800 border border-gray-700 rounded-xl p-3 text-white focus:outline-none focus:border-blue-500" placeholder="Search by pair or notes..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="grade" class="bg-gray-800 border border-gray-700 rounded-xl p-3 text-white focus:outline-none focus:border-blue-500" onchange="this.form.submit()">
                    <option value="">All Grades</option>
                    <option value="A+" <?php echo $filter_grade == 'A+' ? 'selected' : ''; ?>>A+</option>
                    <option value="A" <?php echo $filter_grade == 'A' ? 'selected' : ''; ?>>A</option>
                    <option value="B+" <?php echo $filter_grade == 'B+' ? 'selected' : ''; ?>>B+</option>
                    <option value="B" <?php echo $filter_grade == 'B' ? 'selected' : ''; ?>>B</option>
                    <option value="C" <?php echo $filter_grade == 'C' ? 'selected' : ''; ?>>C</option>
                </select>
                <select name="outcome" class="bg-gray-800 border border-gray-700 rounded-xl p-3 text-white focus:outline-none focus:border-blue-500" onchange="this.form.submit()">
                    <option value="">All Outcomes</option>
                    <option value="Win" <?php echo $filter_outcome == 'Win' ? 'selected' : ''; ?>>Win</option>
                    <option value="Loss" <?php echo $filter_outcome == 'Loss' ? 'selected' : ''; ?>>Loss</option>
                    <option value="Skipped" <?php echo $filter_outcome == 'Skipped' ? 'selected' : ''; ?>>Skipped</option>
                </select>
                <select name="pair" class="bg-gray-800 border border-gray-700 rounded-xl p-3 text-white focus:outline-none focus:border-blue-500" onchange="this.form.submit()">
                    <option value="">All Pairs</option>
                    <?php while($pair = $pairs->fetch_assoc()): ?>
                    <option value="<?php echo $pair['pair']; ?>" <?php echo $filter_pair == $pair['pair'] ? 'selected' : ''; ?>><?php echo $pair['pair']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div>
                <div class="flex flex-wrap gap-2">
                    <a href="?<?php 
                        $params = $_GET;
                        $params['date_range'] = 'All Time';
                        unset($params['date_from'], $params['date_to']);
                        echo http_build_query($params);
                    ?>" class="px-4 py-2 rounded-full border border-gray-700 text-sm font-medium <?php echo $filter_date_range == 'All Time' ? 'bg-blue-600 text-white border-blue-600' : 'bg-gray-800 text-gray-400 hover:bg-gray-700'; ?>">All Time</a>
                    <a href="?<?php 
                        $params = $_GET;
                        $params['date_range'] = 'Last 7 days';
                        unset($params['date_from'], $params['date_to']);
                        echo http_build_query($params);
                    ?>" class="px-4 py-2 rounded-full border border-gray-700 text-sm font-medium <?php echo $filter_date_range == 'Last 7 days' ? 'bg-blue-600 text-white border-blue-600' : 'bg-gray-800 text-gray-400 hover:bg-gray-700'; ?>">Last 7 days</a>
                    <a href="?<?php 
                        $params = $_GET;
                        $params['date_range'] = 'Last 14 days';
                        unset($params['date_from'], $params['date_to']);
                        echo http_build_query($params);
                    ?>" class="px-4 py-2 rounded-full border border-gray-700 text-sm font-medium <?php echo $filter_date_range == 'Last 14 days' ? 'bg-blue-600 text-white border-blue-600' : 'bg-gray-800 text-gray-400 hover:bg-gray-700'; ?>">Last 14 days</a>
                    <a href="?<?php 
                        $params = $_GET;
                        $params['date_range'] = 'Last 20 days';
                        unset($params['date_from'], $params['date_to']);
                        echo http_build_query($params);
                    ?>" class="px-4 py-2 rounded-full border border-gray-700 text-sm font-medium <?php echo $filter_date_range == 'Last 20 days' ? 'bg-blue-600 text-white border-blue-600' : 'bg-gray-800 text-gray-400 hover:bg-gray-700'; ?>">Last 20 days</a>
                    <a href="?<?php 
                        $params = $_GET;
                        $params['date_range'] = 'Last 30 days';
                        unset($params['date_from'], $params['date_to']);
                        echo http_build_query($params);
                    ?>" class="px-4 py-2 rounded-full border border-gray-700 text-sm font-medium <?php echo $filter_date_range == 'Last 30 days' ? 'bg-blue-600 text-white border-blue-600' : 'bg-gray-800 text-gray-400 hover:bg-gray-700'; ?>">Last 30 days</a>
                    <span class="px-4 py-2 rounded-full border border-gray-700 text-sm font-medium cursor-pointer <?php echo $filter_date_range == 'Custom' ? 'bg-blue-600 text-white border-blue-600' : 'bg-gray-800 text-gray-400 hover:bg-gray-700'; ?>" onclick="showCustomDateModal()">Custom</span>
                </div>
                <div id="customDateInputs" class="mt-4 flex flex-wrap gap-3 <?php echo $filter_date_range == 'Custom' ? '' : 'hidden'; ?>">
                    <input type="date" name="date_from" id="date_from" value="<?php echo $filter_date_from; ?>" class="bg-gray-800 border border-gray-700 rounded-xl p-2 text-white">
                    <input type="date" name="date_to" id="date_to" value="<?php echo $filter_date_to; ?>" class="bg-gray-800 border border-gray-700 rounded-xl p-2 text-white">
                    <button type="button" onclick="applyCustomDate()" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-xl text-white font-semibold">Apply</button>
                </div>
            </div>
        </form>

        <!-- Active Filters -->
        <?php 
        $has_active_filters = !empty($filter_grade) || !empty($filter_outcome) || !empty($filter_pair) || 
                              ($filter_date_range != 'All Time') || !empty($search);
        ?>
        <?php if ($has_active_filters): ?>
        <div class="mt-5 pt-5 border-t border-gray-800 flex flex-wrap gap-2 items-center">
            <span class="text-gray-400 text-sm">Active filters:</span>
            <?php if (!empty($search)): ?>
            <span class="bg-gray-800 border border-blue-500 rounded-full px-3 py-1 text-sm flex items-center gap-2">
                Search: <?php echo htmlspecialchars($search); ?>
                <a href="?<?php $params = $_GET; unset($params['search']); echo http_build_query($params); ?>"><i class="fas fa-times text-gray-400 hover:text-red-500"></i></a>
            </span>
            <?php endif; ?>
            <?php if (!empty($filter_grade)): ?>
            <span class="bg-gray-800 border border-blue-500 rounded-full px-3 py-1 text-sm flex items-center gap-2">
                Grade: <?php echo $filter_grade; ?>
                <a href="?<?php $params = $_GET; unset($params['grade']); echo http_build_query($params); ?>"><i class="fas fa-times text-gray-400 hover:text-red-500"></i></a>
            </span>
            <?php endif; ?>
            <?php if (!empty($filter_outcome)): ?>
            <span class="bg-gray-800 border border-blue-500 rounded-full px-3 py-1 text-sm flex items-center gap-2">
                Outcome: <?php echo $filter_outcome; ?>
                <a href="?<?php $params = $_GET; unset($params['outcome']); echo http_build_query($params); ?>"><i class="fas fa-times text-gray-400 hover:text-red-500"></i></a>
            </span>
            <?php endif; ?>
            <?php if (!empty($filter_pair)): ?>
            <span class="bg-gray-800 border border-blue-500 rounded-full px-3 py-1 text-sm flex items-center gap-2">
                Pair: <?php echo $filter_pair; ?>
                <a href="?<?php $params = $_GET; unset($params['pair']); echo http_build_query($params); ?>"><i class="fas fa-times text-gray-400 hover:text-red-500"></i></a>
            </span>
            <?php endif; ?>
            <?php if ($filter_date_range != 'All Time'): ?>
            <span class="bg-gray-800 border border-blue-500 rounded-full px-3 py-1 text-sm flex items-center gap-2">
                Date: <?php echo $filter_date_range; ?>
                <a href="?<?php $params = $_GET; $params['date_range'] = 'All Time'; unset($params['date_from'], $params['date_to']); echo http_build_query($params); ?>"><i class="fas fa-times text-gray-400 hover:text-red-500"></i></a>
            </span>
            <?php endif; ?>
            <a href="trade_journal.php" class="text-blue-500 text-sm hover:underline">Clear All</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Trades List -->
    <div class="bg-gray-900/80 backdrop-blur-sm border border-gray-800 rounded-2xl overflow-hidden">
        <?php if ($trades->num_rows > 0): ?>
            <?php while($trade = $trades->fetch_assoc()): 
                $grade_class = '';
                $grade = $trade['trade_grade'] ?? 'C';
                switch($grade) {
                    case 'A+': $grade_class = 'bg-green-600'; break;
                    case 'A': $grade_class = 'bg-blue-600'; break;
                    case 'B+': $grade_class = 'bg-yellow-600'; break;
                    case 'B': $grade_class = 'bg-orange-600'; break;
                    default: $grade_class = 'bg-red-600';
                }
                $outcome_class = $trade['outcome'] == 'Win' ? 'text-green-500' : ($trade['outcome'] == 'Loss' ? 'text-red-500' : 'text-gray-400');
            ?>
            <div class="flex justify-between items-center p-5 border-b border-gray-800 hover:bg-gray-800/50 transition">
                <div class="flex-1 cursor-pointer" onclick="window.location.href='trade_details.php?id=<?php echo $trade['id']; ?>'">
                    <h3 class="text-white font-medium text-lg"><?php echo $trade['pair']; ?>
                        <span class="text-gray-400 text-sm ml-2"><?php echo !empty($trade['account_names']) ? $trade['account_names'] : 'No account'; ?></span>
                    </h3>
                    <div class="flex flex-wrap gap-3 text-gray-400 text-sm mt-1">
                        <span><i class="far fa-calendar-alt"></i> <?php echo date('M j, Y', strtotime($trade['trade_date'])); ?></span>
                        <span><i class="fas fa-clock"></i> <?php echo $trade['session']; ?></span>
                        <span><i class="fas fa-arrow-<?php echo strtolower($trade['direction']) == 'bullish' ? 'up' : 'down'; ?>"></i> <?php echo $trade['direction']; ?></span>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $grade_class; ?>"><?php echo $grade; ?></span>
                    <span class="<?php echo $outcome_class; ?> font-medium"><?php echo $trade['outcome']; ?></span>
                    <?php if ($trade['profit_loss']): ?>
                    <span class="<?php echo $trade['profit_loss'] >= 0 ? 'text-green-500' : 'text-red-500'; ?> font-semibold">
                        <?php echo $trade['profit_loss'] >= 0 ? '+' : ''; ?>$<?php echo number_format($trade['profit_loss'], 2); ?>
                    </span>
                    <?php endif; ?>
                    <button class="ml-3 w-9 h-9 rounded-lg bg-gray-800 hover:bg-red-600 border border-red-500 text-red-500 hover:text-white transition" onclick="deleteTrade(<?php echo $trade['id']; ?>, '<?php echo $trade['pair']; ?>')">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-center py-16 text-gray-400">
                <i class="fas fa-inbox text-5xl mb-4 text-gray-700"></i>
                <h3 class="text-white text-lg font-semibold mb-2">No trades found</h3>
                <p><?php echo $has_active_filters ? 'No trades match your filters.' : 'Start by adding your first trade from the dashboard.'; ?></p>
                <?php if (!$has_active_filters): ?>
                <a href="dashboard.php" class="inline-block mt-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-5 rounded-xl transition">Add Trade</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
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
                    <input type="date" id="modalDateFrom" class="w-full bg-gray-800 border border-gray-700 rounded-xl p-3 text-white">
                </div>
                <div>
                    <label class="block text-gray-400 text-sm mb-1">To</label>
                    <input type="date" id="modalDateTo" class="w-full bg-gray-800 border border-gray-700 rounded-xl p-3 text-white">
                </div>
            </div>
            <div class="p-5 border-t border-gray-800 flex justify-end gap-3">
                <button type="button" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-xl text-white transition" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-xl text-white font-semibold transition" onclick="applyModalCustomDate()">Apply</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function deleteTrade(id, pair) {
    Swal.fire({
        title: 'Delete Trade?',
        html: `Are you sure you want to delete <strong>${pair}</strong>?<br><br>This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Delete',
        background: '#11161f',
        color: 'white'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'trade_journal.php?delete=' + id;
        }
    });
}

let searchTimeout;
document.querySelector('input[name="search"]').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        document.getElementById('filterForm').submit();
    }, 800);
});

function showCustomDateModal() {
    $('#customDateModal').modal('show');
}

function applyCustomDate() {
    let from = document.getElementById('date_from').value;
    let to = document.getElementById('date_to').value;
    if (!from || !to) {
        Swal.fire({ icon: 'warning', title: 'Select Dates', text: 'Please select both from and to dates', background: '#11161f', color: 'white' });
        return;
    }
    let url = new URL(window.location.href);
    url.searchParams.set('date_range', 'Custom');
    url.searchParams.set('date_from', from);
    url.searchParams.set('date_to', to);
    window.location.href = url.toString();
}

function applyModalCustomDate() {
    let from = document.getElementById('modalDateFrom').value;
    let to = document.getElementById('modalDateTo').value;
    if (!from || !to) {
        Swal.fire({ icon: 'warning', title: 'Select Dates', text: 'Please select both from and to dates', background: '#11161f', color: 'white' });
        return;
    }
    $('#customDateModal').modal('hide');
    let url = new URL(window.location.href);
    url.searchParams.set('date_range', 'Custom');
    url.searchParams.set('date_from', from);
    url.searchParams.set('date_to', to);
    window.location.href = url.toString();
}
</script>