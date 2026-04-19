<?php
// trade_details.php - Complete with checklist, charts, and account management
$page_title = 'Trade Details';
require_once 'config.php';
require_once 'auth.php';

if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$trade_id = $_GET['id'] ?? 0;

// Get trade details
$query = "SELECT * FROM trades WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $trade_id, $user_id);
$stmt->execute();
$trade = $stmt->get_result()->fetch_assoc();

if (!$trade) {
    header('Location: trade_journal.php');
    exit;
}

// Get all user accounts (for dropdown)
$accounts_query = "SELECT id, account_name, current_balance FROM trading_accounts WHERE user_id = ? ORDER BY account_name";
$stmt = $conn->prepare($accounts_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$accounts = $stmt->get_result();

// Get allocations for this trade
$alloc_query = "SELECT ta.*, a.account_name, a.current_balance as account_balance 
                FROM trade_accounts ta
                JOIN trading_accounts a ON ta.account_id = a.id
                WHERE ta.trade_id = ?";
$stmt = $conn->prepare($alloc_query);
$stmt->bind_param("i", $trade_id);
$stmt->execute();
$allocations = $stmt->get_result();

// For backward compatibility with existing UI, use the first allocation (if any)
$first_allocation = $allocations->fetch_assoc();
if ($first_allocation) {
    $trade['account_name'] = $first_allocation['account_name'];
    $trade['account_balance'] = $first_allocation['account_balance'];
    $trade['account_id'] = $first_allocation['account_id'];
    $trade['profit_loss'] = $first_allocation['allocated_pnl'];
    $trade['r_multiple'] = $first_allocation['allocated_r'];
} else {
    $trade['account_name'] = 'No account';
    $trade['account_balance'] = 0;
    $trade['account_id'] = 0;
    $trade['profit_loss'] = 0;
    $trade['r_multiple'] = 0;
}
// Reset pointer for later use if needed (e.g., displaying all allocations)
$allocations->data_seek(0);

// Get checklist items
$checklist_query = "SELECT * FROM trade_checklists WHERE trade_id = ? ORDER BY checklist_type, id";
$stmt = $conn->prepare($checklist_query);
$stmt->bind_param("i", $trade_id);
$stmt->execute();
$checklist = $stmt->get_result();

// Get psychology
$psych_query = "SELECT * FROM trade_psychology WHERE trade_id = ?";
$stmt = $conn->prepare($psych_query);
$stmt->bind_param("i", $trade_id);
$stmt->execute();
$psychology = $stmt->get_result();

// Get charts
$charts_query = "SELECT * FROM chart_snapshots WHERE trade_id = ? ORDER BY timeframe";
$stmt = $conn->prepare($charts_query);
$stmt->bind_param("i", $trade_id);
$stmt->execute();
$charts = $stmt->get_result();

require_once 'header.php';
?>

<style>
.details-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #94a3b8;
    text-decoration: none;
    margin-bottom: 20px;
    font-size: 14px;
    transition: all 0.3s;
}

.back-link:hover {
    color: #3b82f6;
    transform: translateX(-5px);
}

.details-card {
    background: #11161f;
    border: 1px solid #1e293b;
    border-radius: 24px;
    overflow: hidden;
    margin-bottom: 20px;
}

.details-section {
    padding: 25px 30px;
    border-bottom: 1px solid #1e293b;
}

.details-section:last-child {
    border-bottom: none;
}

.details-section h3 {
    color: white;
    font-size: 18px;
    margin: 0 0 20px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Trade Header */
.trade-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    flex-wrap: wrap;
    gap: 15px;
}

.trade-pair {
    color: white;
    font-size: 28px;
    font-weight: 700;
    margin: 0;
}

.trade-grade {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: 700;
    color: white;
}

.trade-meta {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.meta-badge {
    padding: 6px 16px;
    border-radius: 30px;
    font-size: 13px;
    font-weight: 600;
}

.badge-bullish {
    background: rgba(16,185,129,0.2);
    color: #10b981;
    border: 1px solid rgba(16,185,129,0.3);
}

.badge-bearish {
    background: rgba(239,68,68,0.2);
    color: #ef4444;
    border: 1px solid rgba(239,68,68,0.3);
}

.badge-session {
    background: #1e293b;
    color: #94a3b8;
    border: 1px solid #334155;
}

/* Outcome Buttons */
.outcome-buttons {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.outcome-btn {
    flex: 1;
    padding: 14px;
    border: 2px solid #1e293b;
    border-radius: 12px;
    background: #0f172a;
    color: #94a3b8;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.outcome-btn.win.active,
.outcome-btn.win:hover {
    background: #10b981;
    border-color: #10b981;
    color: white;
}

.outcome-btn.loss.active,
.outcome-btn.loss:hover {
    background: #ef4444;
    border-color: #ef4444;
    color: white;
}

.outcome-btn.be.active,
.outcome-btn.be:hover {
    background: #64748b;
    border-color: #64748b;
    color: white;
}

.outcome-tags {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.outcome-tag {
    padding: 8px 20px;
    background: #0f172a;
    border: 1px solid #1e293b;
    border-radius: 30px;
    color: #94a3b8;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.outcome-tag:hover {
    border-color: #3b82f6;
}

.outcome-tag.active {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

/* Account Section */
.account-selector {
    margin-bottom: 20px;
}

.account-select {
    width: 100%;
    padding: 14px;
    background: #0f172a;
    border: 2px solid #1e293b;
    border-radius: 12px;
    color: white;
    font-weight: 600;
    cursor: pointer;
}

.selected-account {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 15px 20px;
    background: #0f172a;
    border: 2px solid #3b82f6;
    border-radius: 12px;
    margin-bottom: 15px;
    transition: all 0.3s;
}

.account-info {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
}

.account-info i {
    color: #3b82f6;
    font-size: 24px;
}

.account-details {
    color: white;
    font-weight: 600;
    font-size: 16px;
}

.account-balance {
    color: #94a3b8;
    font-size: 14px;
    margin-top: 4px;
    transition: all 0.3s;
}

.unlink-btn {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: #1e293b;
    border: 1px solid #ef4444;
    color: #ef4444;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
    margin-left: 15px;
}

.unlink-btn:hover {
    background: #ef4444;
    color: white;
}

.link-account-btn {
    width: 100%;
    padding: 16px;
    background: #0f172a;
    border: 2px dashed #3b82f6;
    border-radius: 12px;
    color: #3b82f6;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.3s;
}

.link-account-btn:hover {
    background: rgba(59,130,246,0.1);
}

/* PnL Input */
.pnl-container {
    margin-top: 20px;
}

.pnl-label {
    color: #94a3b8;
    font-size: 13px;
    margin-bottom: 8px;
}

.pnl-input-group {
    display: flex;
    gap: 8px;
    align-items: center;
}

.pnl-currency {
    padding: 14px;
    background: #1e293b;
    border-radius: 10px;
    color: white;
    font-weight: 600;
}

.pnl-input {
    flex: 1;
    padding: 14px;
    background: #0f172a;
    border: 2px solid #1e293b;
    border-radius: 10px;
    color: white;
    font-size: 18px;
    font-weight: 600;
}

.pnl-input:focus {
    border-color: #3b82f6;
    outline: none;
}

.pnl-hint {
    margin-top: 8px;
    font-size: 12px;
}

.pnl-hint.win { color: #10b981; }
.pnl-hint.loss { color: #ef4444; }
.pnl-hint.be { color: #64748b; }

/* Auto-save indicator */
.auto-save {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #64748b;
    font-size: 12px;
    margin-top: 8px;
}

.auto-save i {
    color: #3b82f6;
}

/* Trade Notes */
.trade-notes {
    width: 100%;
    padding: 16px;
    background: #0f172a;
    border: 2px solid #1e293b;
    border-radius: 12px;
    color: white;
    font-size: 14px;
    line-height: 1.6;
    min-height: 120px;
    resize: vertical;
}

.trade-notes:focus {
    border-color: #8b5cf6;
    outline: none;
}

/* Checklist Section */
.checklist-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 10px;
}

.checklist-column {
    background: #0f172a;
    border-radius: 16px;
    padding: 20px;
}

.checklist-column h4 {
    color: white;
    font-size: 16px;
    margin: 0 0 15px 0;
    display: flex;
    align-items: center;
    gap: 8px;
    padding-bottom: 10px;
    border-bottom: 1px solid #1e293b;
}

.checklist-column h4 i {
    color: #3b82f6;
}

.checklist-items {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.checklist-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px;
    background: #11161f;
    border-radius: 10px;
    border: 1px solid #1e293b;
}

.checklist-item i {
    color: #10b981;
    font-size: 14px;
    margin-top: 2px;
}

.checklist-item span {
    color: #e2e8f0;
    font-size: 13px;
    line-height: 1.4;
    flex: 1;
}

/* Psychology Section */
.psychology-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.psych-tag {
    padding: 8px 16px;
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 30px;
    color: white;
    font-size: 13px;
}

/* Charts Section */
.charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 12px;
}

.chart-item {
    background: #0f172a;
    border: 1px solid #1e293b;
    border-radius: 12px;
    padding: 15px 10px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
}

.chart-item:hover {
    border-color: #3b82f6;
    transform: translateY(-3px);
    background: #1a2438;
}

.chart-item i {
    font-size: 24px;
    color: #3b82f6;
    margin-bottom: 8px;
    display: block;
}

.chart-item span {
    color: white;
    font-size: 12px;
    font-weight: 600;
}

/* Rule of Thumb */
.rule-of-thumb {
    margin-top: 20px;
    padding: 12px;
    background: rgba(59,130,246,0.1);
    border: 1px solid #3b82f6;
    border-radius: 8px;
    color: #3b82f6;
    font-size: 12px;
    text-align: center;
}

.rule-of-thumb i {
    margin-right: 6px;
}

/* Responsive */
@media (max-width: 768px) {
    .details-section {
        padding: 20px;
    }
    
    .trade-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .outcome-buttons {
        flex-direction: column;
    }
    
    .checklist-grid {
        grid-template-columns: 1fr;
    }
    
    .charts-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .selected-account {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .unlink-btn {
        align-self: flex-end;
    }
}

.mode-btn {
    flex: 1;
    padding: 10px;
    background: #0f172a;
    border: 2px solid #1e293b;
    border-radius: 8px;
    color: #94a3b8;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}
.mode-btn.active {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}
#accountMultiSelect {
    width: 100%;
    min-height: 120px;
    background: #0f172a;
    border: 2px solid #1e293b;
    border-radius: 10px;
    color: white;
    padding: 8px;
}
#accountMultiSelect option {
    padding: 8px;
}

</style>

<div class="max-w-4xl mx-auto px-4 py-8 space-y-6">
    <a href="trade_journal.php" class="inline-flex items-center gap-2 text-gray-400 hover:text-blue-500 transition mb-4">
        <i class="fas fa-arrow-left"></i> Back to Journal
    </a>

    <div class="bg-gray-900/80 backdrop-blur-sm border border-gray-800 rounded-2xl overflow-hidden shadow-xl">
        <!-- Trade Header -->
        <div class="p-6 border-b border-gray-800">
            <div class="flex justify-between items-start gap-4 flex-wrap">
                <div>
                    <h1 class="text-3xl font-bold text-white"><?php echo $trade['pair']; ?></h1>
                    <div class="flex gap-2 mt-2">
                        <span class="px-3 py-1 rounded-full text-sm font-semibold <?php echo $trade['direction'] == 'Bullish' ? 'bg-green-600/20 text-green-500' : 'bg-red-600/20 text-red-500'; ?>">
                            <i class="fas fa-arrow-<?php echo strtolower($trade['direction']) == 'bullish' ? 'up' : 'down'; ?>"></i> <?php echo $trade['direction']; ?>
                        </span>
                        <span class="px-3 py-1 rounded-full bg-gray-800 text-gray-400 text-sm"><i class="fas fa-globe"></i> <?php echo $trade['session']; ?></span>
                        <span class="px-3 py-1 rounded-full bg-gray-800 text-gray-400 text-sm"><i class="far fa-calendar"></i> <?php echo date('M j, g:i A', strtotime($trade['created_at'])); ?></span>
                    </div>
                </div>
                <div class="w-16 h-16 rounded-xl flex items-center justify-center text-2xl font-bold text-white" style="background: <?php
                    $grade_color = '';
                    switch($trade['trade_grade']) {
                        case 'A+': $grade_color = '#10b981'; break;
                        case 'A': $grade_color = '#3b82f6'; break;
                        case 'B+': $grade_color = '#f59e0b'; break;
                        case 'B': $grade_color = '#f97316'; break;
                        default: $grade_color = '#ef4444';
                    }
                    echo $grade_color;
                ?>">
                    <?php echo $trade['trade_grade']; ?>
                </div>
            </div>
        </div>

        <!-- Trade Outcome -->
        <div class="p-6 border-b border-gray-800">
            <h3 class="text-white text-lg font-semibold mb-4"><i class="fas fa-flag-checkered text-yellow-500 mr-2"></i> Trade Outcome</h3>
            <div class="flex gap-3 mb-4">
                <button class="flex-1 py-3 rounded-xl border font-semibold transition <?php echo $trade['outcome'] == 'Win' ? 'bg-green-600 border-green-600 text-white' : 'bg-gray-800 border-gray-700 text-gray-400 hover:bg-green-600/20 hover:text-green-500'; ?>" onclick="setOutcome('Win')">WIN</button>
                <button class="flex-1 py-3 rounded-xl border font-semibold transition <?php echo $trade['outcome'] == 'Loss' ? 'bg-red-600 border-red-600 text-white' : 'bg-gray-800 border-gray-700 text-gray-400 hover:bg-red-600/20 hover:text-red-500'; ?>" onclick="setOutcome('Loss')">LOSS</button>
                <button class="flex-1 py-3 rounded-xl border font-semibold transition <?php echo $trade['outcome'] == 'Breakeven' ? 'bg-gray-600 border-gray-600 text-white' : 'bg-gray-800 border-gray-700 text-gray-400 hover:bg-gray-600/20 hover:text-white'; ?>" onclick="setOutcome('Breakeven')">BE</button>
            </div>
            <div class="flex gap-2">
                <span class="px-4 py-2 rounded-full text-sm font-medium cursor-pointer <?php echo strpos($trade['notes'] ?? '', '[MESSED]') !== false ? 'bg-blue-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700'; ?>" onclick="toggleTag('MESSED')">MESSED</span>
                <span class="px-4 py-2 rounded-full text-sm font-medium cursor-pointer <?php echo strpos($trade['notes'] ?? '', '[EMOTIONAL]') !== false ? 'bg-blue-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700'; ?>" onclick="toggleTag('EMOTIONAL')">EMOTIONAL</span>
                <span class="px-4 py-2 rounded-full text-sm font-medium cursor-pointer <?php echo strpos($trade['notes'] ?? '', '[WITHDRAW]') !== false ? 'bg-blue-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700'; ?>" onclick="toggleTag('WITHDRAW')">WITHDRAW</span>
            </div>
        </div>

        <!-- Linked Accounts & PnL -->
        <div class="p-6 border-b border-gray-800">
            <h3 class="text-white text-lg font-semibold mb-4"><i class="fas fa-link text-blue-500 mr-2"></i> Linked Accounts & PnL</h3>
            <div class="mb-4">
                <label class="block text-gray-400 text-sm mb-2">Select Accounts</label>
                <select id="accountMultiSelect" multiple class="w-full bg-gray-800 border border-gray-700 rounded-xl p-3 text-white min-h-[120px]">
                    <?php 
                    $accounts->data_seek(0);
                    $linked_account_ids = [];
                    $allocations->data_seek(0);
                    while($alloc = $allocations->fetch_assoc()) {
                        $linked_account_ids[] = $alloc['account_id'];
                    }
                    $allocations->data_seek(0);
                    while($acc = $accounts->fetch_assoc()): 
                        $selected = in_array($acc['id'], $linked_account_ids) ? 'selected' : '';
                    ?>
                    <option value="<?php echo $acc['id']; ?>" data-balance="<?php echo $acc['current_balance']; ?>" <?php echo $selected; ?>>
                        <?php echo htmlspecialchars($acc['account_name']); ?> ($<?php echo number_format($acc['current_balance'], 2); ?>)
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="flex flex-wrap gap-4 mb-4">
                <div class="flex-1">
                    <label class="block text-gray-400 text-sm mb-2">Mode</label>
                    <div class="flex gap-2">
                        <button class="flex-1 py-2 rounded-xl border transition <?php echo ($trade['pnl_mode'] ?? '$') == '$' ? 'bg-blue-600 border-blue-600 text-white' : 'bg-gray-800 border-gray-700 text-gray-400 hover:bg-gray-700'; ?>" data-mode="$" onclick="setPnlMode('$')">$ (Fixed per account)</button>
                        <button class="flex-1 py-2 rounded-xl border transition <?php echo ($trade['pnl_mode'] ?? '') == '%' ? 'bg-blue-600 border-blue-600 text-white' : 'bg-gray-800 border-gray-700 text-gray-400 hover:bg-gray-700'; ?>" data-mode="%" onclick="setPnlMode('%')">% (Percent of balance)</button>
                    </div>
                </div>
                <div class="flex-1">
                    <label class="block text-gray-400 text-sm mb-2">Value</label>
                    <input type="number" id="pnlValue" class="w-full bg-gray-800 border border-gray-700 rounded-xl p-3 text-white" step="0.01" value="<?php echo $trade['pnl_value'] ?? 0; ?>" oninput="updatePreview()">
                </div>
            </div>
            <div id="allocationPreview" class="bg-gray-800/50 rounded-xl p-4 mb-4">
                <table class="w-full text-sm">
                    <thead><tr class="text-gray-400 border-b border-gray-700"><th class="text-left py-2">Account</th><th class="text-right py-2">Balance</th><th class="text-right py-2">PnL</th></tr></thead>
                    <tbody id="previewBody"></tbody>
                </table>
            </div>
            <button onclick="saveAllocations()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-xl transition">
                <i class="fas fa-save mr-2"></i> Update Accounts
            </button>
            <div class="text-gray-400 text-sm mt-2 flex items-center gap-2" id="autoSaveIndicator">
                <i class="fas fa-cloud"></i> <span>Ready</span>
            </div>
        </div>

        <!-- Trade Notes -->
        <div class="p-6 border-b border-gray-800">
            <h3 class="text-white text-lg font-semibold mb-4"><i class="fas fa-book-open text-purple-500 mr-2"></i> Trade Notes & Lessons</h3>
            <textarea id="tradeNotes" class="w-full bg-gray-800 border border-gray-700 rounded-xl p-4 text-white min-h-[120px] resize-y focus:outline-none focus:border-blue-500" placeholder="Mistake made, lesson learned, market condition, psychology notes..." oninput="autoSaveNotes()"><?php echo htmlspecialchars($trade['notes'] ?? $trade['skip_notes'] ?? ''); ?></textarea>
            <div class="text-gray-400 text-sm mt-2 flex items-center gap-2" id="notesIndicator">
                <i class="fas fa-cloud"></i> <span>Auto-saving notes...</span>
            </div>
        </div>

        <!-- Psychology Section -->
        <?php if ($psychology->num_rows > 0): ?>
        <div class="p-6 border-b border-gray-800">
            <h3 class="text-white text-lg font-semibold mb-4"><i class="fas fa-brain text-pink-500 mr-2"></i> Psychology (Before Entry)</h3>
            <div class="flex flex-wrap gap-2">
                <?php while($emotion = $psychology->fetch_assoc()): ?>
                <span class="px-3 py-1 bg-gray-800 rounded-full text-sm text-white"><?php echo $emotion['emotion']; ?><?php if (!empty($emotion['custom_note'])): ?> <span class="text-gray-400">- <?php echo $emotion['custom_note']; ?></span><?php endif; ?></span>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Checklist Section -->
        <?php 
        $htf_items = [];
        $ltf_items = [];
        if ($checklist->num_rows > 0) {
            while($item = $checklist->fetch_assoc()) {
                if ($item['checklist_type'] == 'HTF') $htf_items[] = $item;
                else $ltf_items[] = $item;
            }
        }
        ?>
        <?php if (!empty($htf_items) || !empty($ltf_items)): ?>
        <div class="p-6 border-b border-gray-800">
            <h3 class="text-white text-lg font-semibold mb-4"><i class="fas fa-check-double text-green-500 mr-2"></i> Checklist Items</h3>
            <div class="grid md:grid-cols-2 gap-6">
                <?php if (!empty($htf_items)): ?>
                <div>
                    <h4 class="text-blue-500 font-semibold mb-3"><i class="fas fa-chart-line mr-2"></i> HTF Rules Met</h4>
                    <div class="space-y-2">
                        <?php foreach($htf_items as $item): ?>
                        <div class="flex items-start gap-2 p-3 bg-gray-800/50 rounded-lg">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-200 text-sm"><?php echo htmlspecialchars($item['item_key']); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (!empty($ltf_items)): ?>
                <div>
                    <h4 class="text-purple-500 font-semibold mb-3"><i class="fas fa-clock mr-2"></i> LTF Rules Met</h4>
                    <div class="space-y-2">
                        <?php foreach($ltf_items as $item): ?>
                        <div class="flex items-start gap-2 p-3 bg-gray-800/50 rounded-lg">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-200 text-sm"><?php echo htmlspecialchars($item['item_key']); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Charts Section -->
        <?php if ($charts->num_rows > 0): ?>
        <div class="p-6 border-b border-gray-800">
            <h3 class="text-white text-lg font-semibold mb-4"><i class="fas fa-camera text-yellow-500 mr-2"></i> Chart Snapshots</h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                <?php while($chart = $charts->fetch_assoc()): ?>
                <div class="bg-gray-800/50 border border-gray-700 rounded-xl p-3 text-center cursor-pointer hover:border-blue-500 transition" onclick="previewImage('uploads/<?php echo $chart['image_path']; ?>')">
                    <i class="fas fa-image text-2xl text-blue-500 mb-2 block"></i>
                    <span class="text-white text-sm"><?php echo $chart['timeframe']; ?></span>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Rule of Thumb -->
        <div class="p-6">
            <div class="bg-blue-500/10 border border-blue-500 rounded-lg p-4 text-blue-500 text-sm text-center">
                <i class="fas fa-info-circle mr-2"></i> WIN = + (add to balance) • LOSS = - (subtract from balance) • BE = $0
            </div>
        </div>
    </div>
</div>

<!-- Image Preview Modal -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="bg-gray-900 border border-gray-800 rounded-2xl shadow-2xl">
            <div class="p-5 border-b border-gray-800 flex justify-between items-center">
                <h5 class="text-white text-xl font-semibold">Chart Preview</h5>
                <button type="button" class="text-gray-400 hover:text-white" data-bs-dismiss="modal"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-5 text-center">
                <img id="previewImage" src="" class="max-w-full max-h-[70vh] rounded-xl">
            </div>
        </div>
    </div>
</div>

<script>
// Keep all the existing JavaScript functions (setOutcome, setPnlMode, updatePreview, saveAllocations, toggleTag, autoSaveNotes, previewImage)
// They should work fine. Just make sure they reference the correct element IDs.
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ===== GLOBAL VARIABLES =====
let currentOutcome = '<?php echo $trade['outcome']; ?>';
let currentAccountId = <?php echo $trade['account_id'] ?: 'null'; ?>;
let currentAccountBalance = <?php echo $trade['account_balance'] ?: 0; ?>;
let currentPnlMode = '<?php echo $trade['pnl_mode'] ?? '$'; ?>';
let currentPnlValue = <?php echo $trade['pnl_value'] ?? 0; ?>;
let saveTimeout;
let notesTimeout;

// ===== INITIALIZATION =====
document.addEventListener('DOMContentLoaded', function() {
    updatePreview();
});

// ===== OUTCOME FUNCTIONS =====
function setOutcome(outcome) {
    currentOutcome = outcome;
    document.querySelectorAll('.outcome-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');

    // Update hint if present
    let hint = document.getElementById('pnlHint');
    if (hint) {
        if (outcome === 'Win') {
            hint.innerHTML = '✅ Profit - amount will be added to balance';
            hint.style.color = '#10b981';
        } else if (outcome === 'Loss') {
            hint.innerHTML = '❌ Loss - amount will be subtracted from balance';
            hint.style.color = '#ef4444';
        } else if (outcome === 'Breakeven') {
            hint.innerHTML = '⏸️ Breakeven - P&L set to $0';
            hint.style.color = '#64748b';
            document.getElementById('pnlValue').value = 0;
        }
    }

    updatePreview();
}

// ===== PNL MODE FUNCTIONS =====
function setPnlMode(mode) {
    currentPnlMode = mode;
    document.querySelectorAll('.mode-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelector(`.mode-btn[data-mode="${mode}"]`).classList.add('active');
    updatePreview();
}

// ===== PREVIEW UPDATE =====
function updatePreview() {
    let select = document.getElementById('accountMultiSelect');
    if (!select) return;
    
    let selectedOptions = Array.from(select.selectedOptions);
    let value = parseFloat(document.getElementById('pnlValue').value) || 0;
    let tbody = document.getElementById('previewBody');
    tbody.innerHTML = '';

    selectedOptions.forEach(opt => {
        let accountName = opt.text.split(' (')[0];
        let balance = parseFloat(opt.dataset.balance);
        let pnl = 0;
        
        if (currentPnlMode === '$') {
            pnl = value;
        } else {
            pnl = balance * (value / 100);
        }
        
        // Apply outcome sign
        if (currentOutcome === 'Loss') pnl = -Math.abs(pnl);
        else if (currentOutcome === 'Breakeven') pnl = 0;
        else pnl = Math.abs(pnl); // Win or Pending

        let row = document.createElement('tr');
        row.style.borderBottom = '1px solid #1e293b';
        row.innerHTML = `
            <td style="padding: 8px; color: white;">${accountName}</td>
            <td style="padding: 8px; text-align: right; color: #94a3b8;">$${balance.toFixed(2)}</td>
            <td style="padding: 8px; text-align: right; color: ${pnl >= 0 ? '#10b981' : '#ef4444'};">${pnl >= 0 ? '+' : ''}$${pnl.toFixed(2)}</td>
        `;
        tbody.appendChild(row);
    });
}

// ===== SAVE ALLOCATIONS =====
function saveAllocations() {
    let select = document.getElementById('accountMultiSelect');
    let selectedOptions = Array.from(select.selectedOptions);
    let value = parseFloat(document.getElementById('pnlValue').value) || 0;
    let allocations = [];

    selectedOptions.forEach(opt => {
        let accountId = opt.value;
        let balance = parseFloat(opt.dataset.balance);
        let pnl = 0;
        
        if (currentPnlMode === '$') {
            pnl = value;
        } else {
            pnl = balance * (value / 100);
        }
        
        if (currentOutcome === 'Loss') pnl = -Math.abs(pnl);
        else if (currentOutcome === 'Breakeven') pnl = 0;
        else pnl = Math.abs(pnl);

        allocations.push({ account_id: accountId, pnl: pnl });
    });

    document.getElementById('autoSaveIndicator').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

    fetch('update_trade_allocations.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            trade_id: <?php echo $trade_id; ?>,
            allocations: allocations,
            outcome: currentOutcome,
            pnl_mode: currentPnlMode,
            pnl_value: value
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById('autoSaveIndicator').innerHTML = '<i class="fas fa-check-circle" style="color:#10b981;"></i> Saved';
            setTimeout(() => {
                document.getElementById('autoSaveIndicator').innerHTML = '<i class="fas fa-cloud"></i> Ready';
            }, 2000);
            localStorage.setItem('accountUpdate', Date.now().toString());
        } else {
            alert('Error: ' + data.message);
        }
    });
}

// ===== TAG TOGGLE =====
function toggleTag(tag) {
    let element = event.target;
    element.classList.toggle('active');
    
    let notes = document.getElementById('tradeNotes').value;
    let tagText = '[' + tag + ']';
    
    if (element.classList.contains('active')) {
        if (!notes.includes(tagText)) {
            notes += (notes ? ' ' : '') + tagText;
        }
    } else {
        notes = notes.replace(tagText, '').replace(/\s+/g, ' ').trim();
    }
    
    document.getElementById('tradeNotes').value = notes;
    autoSaveNotes();
}

// ===== AUTO-SAVE NOTES =====
function autoSaveNotes() {
    clearTimeout(notesTimeout);
    
    let notes = document.getElementById('tradeNotes').value;
    
    document.getElementById('notesIndicator').innerHTML = '<i class="fas fa-cloud-upload-alt fa-spin"></i> <span>Saving...</span>';
    
    notesTimeout = setTimeout(() => {
        fetch('update_trade_notes.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ trade_id: <?php echo $trade_id; ?>, notes: notes })
        })
        .then(() => {
            document.getElementById('notesIndicator').innerHTML = '<i class="fas fa-cloud"></i> <span>Auto-saving notes...</span>';
        });
    }, 800);
}

// ===== CHART PREVIEW =====
function previewImage(src) {
    console.log('previewImage called with src:', src);
    
    var modalEl = document.getElementById('imagePreviewModal');
    if (!modalEl) {
        alert('Modal element not found!');
        return;
    }
    
    var img = document.getElementById('previewImage');
    if (img) {
        img.src = src;
    } else {
        alert('Preview image element not found!');
        return;
    }
    
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        var bsModal = new bootstrap.Modal(modalEl);
        bsModal.show();
    } else {
        // Fallback to jQuery
        if (typeof $ !== 'undefined') {
            $('#imagePreviewModal').modal('show');
        } else {
            alert('Bootstrap or jQuery not available');
        }
    }
}

// ===== REMOVE OLD FUNCTIONS THAT ARE NO LONGER NEEDED =====
// (We keep only the above. The old functions like showAccountSelector, linkAccount, etc. are removed.)
</script>


<!-- Image Preview Modal -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background: #11161f; border: 1px solid #1e293b;">
            <div class="modal-header">
                <h5 class="modal-title" style="color: white;">Chart Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: invert(1);"></button>
            </div>
            <div class="modal-body text-center">
                <img id="previewImage" src="" style="max-width: 100%; max-height: 70vh; border-radius: 8px;">
            </div>
        </div>
    </div>
</div>

</body>
</html>