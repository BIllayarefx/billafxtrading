<?php
$page_title = 'Profile';
require_once 'config.php';
require_once 'auth.php';

if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle profile update (same logic)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $primary_session = $_POST['primary_session'];
    $trading_style = $_POST['trading_style'];
    $experience = $_POST['experience'];
    $bio = trim($_POST['bio']);
    
    $update_user = "UPDATE users SET full_name = ?, email = ? WHERE id = ?";
    $stmt = $conn->prepare($update_user);
    $stmt->bind_param("ssi", $full_name, $email, $user_id);
    $stmt->execute();
    
    $check_profile = "SELECT id FROM trader_profile WHERE user_id = ?";
    $stmt = $conn->prepare($check_profile);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $profile_exists = $stmt->get_result()->fetch_assoc();
    
    if ($profile_exists) {
        $update_profile = "UPDATE trader_profile SET primary_session = ?, trading_style = ?, experience_level = ?, bio = ? WHERE user_id = ?";
        $stmt = $conn->prepare($update_profile);
        $stmt->bind_param("ssssi", $primary_session, $trading_style, $experience, $bio, $user_id);
    } else {
        $insert_profile = "INSERT INTO trader_profile (user_id, primary_session, trading_style, experience_level, bio) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_profile);
        $stmt->bind_param("issss", $user_id, $primary_session, $trading_style, $experience, $bio);
    }
    $stmt->execute();
    
    $_SESSION['full_name'] = $full_name;
    $success = "Profile updated successfully!";
}

// Get user data
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get profile data
$profile_query = "SELECT * FROM trader_profile WHERE user_id = ?";
$stmt = $conn->prepare($profile_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

if (!$profile) {
    $insert = "INSERT INTO trader_profile (user_id) VALUES (?)";
    $stmt = $conn->prepare($insert);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt = $conn->prepare($profile_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
}

// Get overall statistics
$stats_query = "SELECT 
                COUNT(DISTINCT t.id) as total_trades,
                SUM(CASE WHEN t.outcome = 'Win' THEN 1 ELSE 0 END) as wins,
                SUM(CASE WHEN t.outcome = 'Loss' THEN 1 ELSE 0 END) as losses,
                SUM(CASE WHEN t.outcome = 'Skipped' THEN 1 ELSE 0 END) as skipped,
                SUM(CASE WHEN t.trade_grade = 'A+' THEN 1 ELSE 0 END) as a_plus_trades,
                AVG(ta.allocated_r) as avg_r,
                SUM(ta.allocated_r) as total_r,
                SUM(ta.allocated_pnl) as total_pnl,
                COUNT(DISTINCT t.pair) as pairs_traded,
                (SELECT pair FROM trades WHERE user_id = ? AND outcome IN ('Win', 'Loss') 
                 GROUP BY pair ORDER BY COUNT(*) DESC LIMIT 1) as favorite_pair,
                (SELECT session FROM trades WHERE user_id = ? AND outcome IN ('Win', 'Loss') 
                 GROUP BY session ORDER BY COUNT(*) DESC LIMIT 1) as best_session
                FROM trades t
                LEFT JOIN trade_accounts ta ON t.id = ta.trade_id
                WHERE t.user_id = ?";

$stmt = $conn->prepare($stats_query);
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

$stats['total_trades'] = $stats['total_trades'] ?? 0;
$stats['wins'] = $stats['wins'] ?? 0;
$stats['losses'] = $stats['losses'] ?? 0;
$stats['skipped'] = $stats['skipped'] ?? 0;
$stats['a_plus_trades'] = $stats['a_plus_trades'] ?? 0;
$stats['avg_r'] = $stats['avg_r'] ?? 0;
$stats['total_r'] = $stats['total_r'] ?? 0;
$stats['total_pnl'] = $stats['total_pnl'] ?? 0;
$stats['pairs_traded'] = $stats['pairs_traded'] ?? 0;
$stats['favorite_pair'] = $stats['favorite_pair'] ?? 'N/A';
$stats['best_session'] = $stats['best_session'] ?? 'N/A';

$completed_trades = $stats['wins'] + $stats['losses'];
$win_rate = $completed_trades > 0 ? round(($stats['wins'] / $completed_trades) * 100, 1) : 0;

// Get recent trades
$recent_query = "SELECT t.*, 
                        GROUP_CONCAT(DISTINCT a.account_name SEPARATOR ', ') as account_names
                 FROM trades t 
                 LEFT JOIN trade_accounts ta ON t.id = ta.trade_id
                 LEFT JOIN trading_accounts a ON ta.account_id = a.id
                 WHERE t.user_id = ?
                 GROUP BY t.id
                 ORDER BY t.created_at DESC 
                 LIMIT 5";
$stmt = $conn->prepare($recent_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_trades = $stmt->get_result();

$accounts_summary = $conn->query("SELECT COUNT(*) as total_accounts, SUM(current_balance) as total_balance FROM trading_accounts WHERE user_id = $user_id")->fetch_assoc();

require_once 'header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <!-- Profile Header -->
    <div class="bg-gray-900/80 backdrop-blur-sm border border-gray-800 rounded-2xl p-6 flex flex-wrap items-center gap-6">
        <div class="w-24 h-24 bg-gradient-to-r from-blue-600 to-purple-600 rounded-3xl flex items-center justify-center text-white text-5xl font-bold">
            <?php echo strtoupper(substr($user['username'] ?? 'U', 0, 1)); ?>
        </div>
        <div class="flex-1">
            <h1 class="text-3xl font-bold text-white"><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></h1>
            <p class="text-blue-500">@<?php echo htmlspecialchars($user['username']); ?></p>
            <span class="inline-block mt-2 px-3 py-1 bg-gray-800 rounded-full text-sm text-gray-400"><?php echo $user['profile_badge'] ?? 'Student'; ?></span>
        </div>
        <div class="flex gap-3">
            <button class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-5 rounded-xl transition" onclick="$('#editProfileModal').modal('show')">
                <i class="fas fa-edit"></i> Edit Profile
            </button>
            <a href="logout.php" class="bg-gray-800 hover:bg-red-600 text-red-500 hover:text-white border border-red-500 font-semibold py-2 px-5 rounded-xl transition">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-gray-900/80 backdrop-blur-sm border border-gray-800 rounded-2xl p-5">
            <div class="text-gray-400 text-sm">Total Trades</div>
            <div class="text-white text-3xl font-bold"><?php echo $stats['total_trades']; ?></div>
            <div class="text-gray-400 text-sm"><?php echo $stats['wins']; ?>W / <?php echo $stats['losses']; ?>L</div>
        </div>
        <div class="bg-gray-900/80 backdrop-blur-sm border border-gray-800 rounded-2xl p-5">
            <div class="text-gray-400 text-sm">Win Rate</div>
            <div class="text-white text-3xl font-bold"><?php echo $win_rate; ?>%</div>
            <div class="<?php echo $win_rate >= 50 ? 'text-green-500' : 'text-red-500'; ?> text-sm"><?php echo $win_rate >= 50 ? 'Good' : 'Needs Improvement'; ?></div>
        </div>
        <div class="bg-gray-900/80 backdrop-blur-sm border border-gray-800 rounded-2xl p-5">
            <div class="text-gray-400 text-sm">Total PnL</div>
            <div class="<?php echo $stats['total_pnl'] >= 0 ? 'text-green-500' : 'text-red-500'; ?> text-3xl font-bold">
                <?php echo $stats['total_pnl'] >= 0 ? '+' : ''; ?>$<?php echo number_format($stats['total_pnl'], 2); ?>
            </div>
            <div class="text-gray-400 text-sm">From <?php echo $completed_trades; ?> trades</div>
        </div>
        <div class="bg-gray-900/80 backdrop-blur-sm border border-gray-800 rounded-2xl p-5">
            <div class="text-gray-400 text-sm">Total R</div>
            <div class="<?php echo $stats['total_r'] >= 0 ? 'text-green-500' : 'text-red-500'; ?> text-3xl font-bold">
                <?php echo $stats['total_r'] >= 0 ? '+' : ''; ?><?php echo number_format($stats['total_r'], 1); ?>R
            </div>
            <div class="text-gray-400 text-sm">Avg: <?php echo number_format($stats['avg_r'], 2); ?>R</div>
        </div>
    </div>

    <!-- Profile Grid -->
    <div class="grid md:grid-cols-2 gap-6">
        <div class="bg-gray-900/80 backdrop-blur-sm border border-gray-800 rounded-2xl p-6">
            <h3 class="text-white text-lg font-semibold flex items-center gap-2 mb-4"><i class="fas fa-user text-blue-500"></i> Personal Information</h3>
            <div class="space-y-3">
                <div class="flex justify-between"><span class="text-gray-400">Email</span><span class="text-white"><?php echo htmlspecialchars($user['email']); ?></span></div>
                <div class="flex justify-between"><span class="text-gray-400">Member Since</span><span class="text-white"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span></div>
                <div class="flex justify-between"><span class="text-gray-400">Primary Session</span><span class="text-white"><?php echo $profile['primary_session'] ?? 'New York'; ?></span></div>
                <div class="flex justify-between"><span class="text-gray-400">Trading Style</span><span class="text-white"><?php echo $profile['trading_style'] ?? 'Swing'; ?></span></div>
                <div class="flex justify-between"><span class="text-gray-400">Experience</span><span class="text-white"><?php echo $profile['experience_level'] ?? 'Beginner'; ?></span></div>
            </div>
        </div>
        <div class="bg-gray-900/80 backdrop-blur-sm border border-gray-800 rounded-2xl p-6">
            <h3 class="text-white text-lg font-semibold flex items-center gap-2 mb-4"><i class="fas fa-chart-line text-blue-500"></i> Trading Statistics</h3>
            <div class="space-y-3">
                <div class="flex justify-between"><span class="text-gray-400">Accounts</span><span class="text-white"><?php echo $accounts_summary['total_accounts'] ?? 0; ?></span></div>
                <div class="flex justify-between"><span class="text-gray-400">Total Balance</span><span class="text-white">$<?php echo number_format($accounts_summary['total_balance'] ?? 0, 2); ?></span></div>
                <div class="flex justify-between"><span class="text-gray-400">Pairs Traded</span><span class="text-white"><?php echo $stats['pairs_traded']; ?></span></div>
                <div class="flex justify-between"><span class="text-gray-400">Favorite Pair</span><span class="text-white"><?php echo $stats['favorite_pair']; ?></span></div>
                <div class="flex justify-between"><span class="text-gray-400">Best Session</span><span class="text-white"><?php echo $stats['best_session']; ?></span></div>
                <div class="flex justify-between"><span class="text-gray-400">A+ Trades</span><span class="text-white"><?php echo $stats['a_plus_trades']; ?></span></div>
            </div>
        </div>
    </div>

    <!-- Performance Overview -->
    <div class="bg-gray-900/80 backdrop-blur-sm border border-gray-800 rounded-2xl p-6">
        <div class="flex flex-wrap justify-between items-center mb-6">
            <h3 class="text-white text-lg font-semibold">Performance Overview</h3>
            <div class="flex gap-6">
                <div><span class="text-gray-400 text-sm">Total Trades</span><div class="text-white text-xl font-bold"><?php echo $stats['total_trades']; ?></div></div>
                <div><span class="text-gray-400 text-sm">Wins</span><div class="text-green-500 text-xl font-bold"><?php echo $stats['wins']; ?></div></div>
                <div><span class="text-gray-400 text-sm">Losses</span><div class="text-red-500 text-xl font-bold"><?php echo $stats['losses']; ?></div></div>
                <div><span class="text-gray-400 text-sm">Skipped</span><div class="text-gray-400 text-xl font-bold"><?php echo $stats['skipped']; ?></div></div>
            </div>
        </div>
        <div class="mt-4">
            <div class="flex justify-between text-sm text-gray-400 mb-1"><span>Win Rate</span><span><?php echo $win_rate; ?>%</span></div>
            <div class="h-2 bg-gray-800 rounded-full overflow-hidden"><div class="h-full bg-gradient-to-r from-blue-600 to-purple-600 rounded-full" style="width: <?php echo $win_rate; ?>%"></div></div>
            <div class="flex justify-between text-sm text-gray-400 mt-3">
                <span>Expected value: <?php echo number_format($stats['avg_r'] * ($win_rate/100), 2); ?>R per trade</span>
                <span>Profit factor: <?php 
                    $avg_win = $stats['wins'] > 0 ? ($stats['total_pnl'] > 0 ? $stats['total_pnl'] / $stats['wins'] : 0) : 0;
                    $avg_loss = $stats['losses'] > 0 ? ($stats['total_pnl'] < 0 ? abs($stats['total_pnl']) / $stats['losses'] : 0) : 1;
                    echo $avg_loss > 0 ? number_format($avg_win / $avg_loss, 2) : '0';
                ?></span>
            </div>
        </div>
    </div>

    <!-- Recent Trades -->
    <?php if ($recent_trades->num_rows > 0): ?>
    <div class="bg-gray-900/80 backdrop-blur-sm border border-gray-800 rounded-2xl p-6">
        <h3 class="text-white text-lg font-semibold mb-4">Recent Trades</h3>
        <?php while($trade = $recent_trades->fetch_assoc()): 
            $grade_class = '';
            $grade = $trade['trade_grade'] ?? 'C';
            switch($grade) {
                case 'A+': $grade_class = 'bg-green-600'; break;
                case 'A': $grade_class = 'bg-blue-600'; break;
                case 'B+': $grade_class = 'bg-yellow-600'; break;
                case 'B': $grade_class = 'bg-orange-600'; break;
                default: $grade_class = 'bg-red-600';
            }
        ?>
        <div class="flex justify-between items-center p-4 bg-gray-800/50 rounded-xl mb-3 cursor-pointer hover:bg-gray-800 transition" onclick="window.location.href='trade_details.php?id=<?php echo $trade['id']; ?>'">
            <div>
                <h4 class="text-white font-medium"><?php echo $trade['pair']; ?></h4>
                <div class="text-gray-400 text-sm flex gap-3 mt-1">
                    <span><i class="far fa-calendar-alt"></i> <?php echo date('M j, Y - g:i A', strtotime($trade['created_at'])); ?></span>
                    <?php if (!empty($trade['account_names'])): ?>
                    <span><i class="fas fa-wallet"></i> <?php echo $trade['account_names']; ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $grade_class; ?>"><?php echo $grade; ?></span>
                <span class="<?php echo $trade['outcome'] == 'Win' ? 'text-green-500' : ($trade['outcome'] == 'Loss' ? 'text-red-500' : 'text-gray-400'); ?>"><?php echo $trade['outcome']; ?></span>
                <?php if ($trade['profit_loss']): ?>
                <span class="<?php echo $trade['profit_loss'] >= 0 ? 'text-green-500' : 'text-red-500'; ?> font-semibold">
                    <?php echo $trade['profit_loss'] >= 0 ? '+' : ''; ?>$<?php echo number_format($trade['profit_loss'], 2); ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="bg-gray-900 border border-gray-800 rounded-2xl shadow-2xl">
            <div class="p-5 border-b border-gray-800 flex justify-between items-center">
                <h5 class="text-white text-xl font-semibold">Edit Profile</h5>
                <button type="button" class="text-gray-400 hover:text-white" data-bs-dismiss="modal"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST">
                <div class="p-5 space-y-4">
                    <input type="hidden" name="update_profile" value="1">
                    <div>
                        <label class="block text-gray-400 text-sm mb-1">Full Name</label>
                        <input type="text" name="full_name" class="w-full bg-gray-800 border border-gray-700 rounded-xl p-3 text-white" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>">
                    </div>
                    <div>
                        <label class="block text-gray-400 text-sm mb-1">Email</label>
                        <input type="email" name="email" class="w-full bg-gray-800 border border-gray-700 rounded-xl p-3 text-white" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div>
                        <label class="block text-gray-400 text-sm mb-1">Primary Trading Session</label>
                        <select name="primary_session" class="w-full bg-gray-800 border border-gray-700 rounded-xl p-3 text-white">
                            <option value="London" <?php echo ($profile['primary_session'] ?? '') == 'London' ? 'selected' : ''; ?>>London</option>
                            <option value="New York" <?php echo ($profile['primary_session'] ?? '') == 'New York' ? 'selected' : ''; ?>>New York</option>
                            <option value="Asian" <?php echo ($profile['primary_session'] ?? '') == 'Asian' ? 'selected' : ''; ?>>Asian</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-400 text-sm mb-1">Trading Style</label>
                        <select name="trading_style" class="w-full bg-gray-800 border border-gray-700 rounded-xl p-3 text-white">
                            <option value="Scalping" <?php echo ($profile['trading_style'] ?? '') == 'Scalping' ? 'selected' : ''; ?>>Scalping</option>
                            <option value="Day Trading" <?php echo ($profile['trading_style'] ?? '') == 'Day Trading' ? 'selected' : ''; ?>>Day Trading</option>
                            <option value="Swing" <?php echo ($profile['trading_style'] ?? '') == 'Swing' ? 'selected' : ''; ?>>Swing</option>
                            <option value="Position" <?php echo ($profile['trading_style'] ?? '') == 'Position' ? 'selected' : ''; ?>>Position</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-400 text-sm mb-1">Experience Level</label>
                        <select name="experience" class="w-full bg-gray-800 border border-gray-700 rounded-xl p-3 text-white">
                            <option value="Beginner" <?php echo ($profile['experience_level'] ?? '') == 'Beginner' ? 'selected' : ''; ?>>Beginner</option>
                            <option value="Intermediate" <?php echo ($profile['experience_level'] ?? '') == 'Intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                            <option value="Advanced" <?php echo ($profile['experience_level'] ?? '') == 'Advanced' ? 'selected' : ''; ?>>Advanced</option>
                            <option value="Professional" <?php echo ($profile['experience_level'] ?? '') == 'Professional' ? 'selected' : ''; ?>>Professional</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-400 text-sm mb-1">Bio</label>
                        <textarea name="bio" class="w-full bg-gray-800 border border-gray-700 rounded-xl p-3 text-white" rows="3" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($profile['bio'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="p-5 border-t border-gray-800 flex justify-end gap-3">
                    <button type="button" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-xl text-white transition" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-xl text-white font-semibold transition">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if (isset($success)): ?>
<script>
Swal.fire({ icon: 'success', title: 'Success!', text: '<?php echo $success; ?>', background: '#11161f', color: 'white', timer: 2000, showConfirmButton: false });
</script>
<?php endif; ?>