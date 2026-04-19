<?php
$page_title = 'Dashboard';
require_once 'config.php';
require_once 'auth.php';

if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get accounts (for account selector in multi‑account modal – not shown here for brevity)
$accounts_query = "SELECT id, account_name, current_balance FROM trading_accounts WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($accounts_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$accounts = $stmt->get_result();

// Fetch templates
$templates_query = "SELECT id, name FROM templates WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($templates_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$templates = $stmt->get_result();

// Today's ritual
$today = date('Y-m-d');
$ritual_query = "SELECT * FROM daily_rituals WHERE user_id = ? AND ritual_date = ?";
$stmt = $conn->prepare($ritual_query);
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$today_ritual = $stmt->get_result()->fetch_assoc();

// Recent trades for sidebar (we keep the same as before)
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

$psychology_emotions = [
    'FOMO', 'Calm', 'Fear', 'Greed', 'Overconfident',
    'Hesitation', 'Revenge', 'Impatient', 'Focused', 'Uncertain'
];

require_once 'header.php';
?>

<div class="space-y-8" x-data="tradeChecklist()" x-init="init()">
    <!-- Ritual Alert -->
    <?php if (!$today_ritual || !$today_ritual['completed']): ?>
    <div class="bg-yellow-500/10 border border-yellow-500 rounded-lg p-4 flex items-center gap-4 cursor-pointer hover:bg-yellow-500/20 transition" @click="showDailyRitual()">
        <i class="fas fa-exclamation-circle text-yellow-500 text-xl"></i>
        <div>
            <h4 class="text-white font-semibold">Daily ritual not completed!</h4>
            <p class="text-gray-400 text-sm">Click here to complete your pre-market readiness check.</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="flex flex-wrap justify-between items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white">A+ Trade Confirmation</h1>
            <p class="text-gray-400 mt-1">Confirm your trade setup before entry</p>
        </div>
        <button class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition" @click="openNewTradeModal()">
            <i class="fas fa-plus mr-2"></i> Quick Trade
        </button>
    </div>

    <!-- Trade Setup Card -->
    <div class="bg-gray-900 border border-gray-800 rounded-xl shadow-xl overflow-hidden">
        <div class="p-6 border-b border-gray-800 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-white">Trade Setup</h2>
            <span class="bg-gray-800 text-blue-400 px-3 py-1 rounded-full text-sm font-medium" x-text="progressPercent + '% Complete'"></span>
        </div>

        <!-- Template Dropdown -->
        <div class="p-6 border-b border-gray-800">
            <label class="block text-gray-400 text-sm font-semibold mb-2">Checklist Template</label>
            <div class="relative" x-data="{ open: false }">
                <div @click="open = !open" class="bg-gray-800 border border-gray-700 rounded-lg p-3 flex justify-between items-center cursor-pointer hover:border-blue-500 transition">
                    <span x-text="selectedTemplateName || 'Select Template'"></span>
                    <i class="fas fa-chevron-down text-gray-400"></i>
                </div>
                <div x-show="open" @click.away="open = false" class="absolute z-10 mt-2 w-full bg-gray-800 border border-gray-700 rounded-lg shadow-lg overflow-hidden">
                    <template x-for="tpl in templates" :key="tpl.id">
                        <div @click="selectTemplate(tpl)" class="px-4 py-2 hover:bg-gray-700 cursor-pointer flex justify-between items-center">
                            <span x-text="tpl.name"></span>
                            <i class="fas fa-check text-blue-500" x-show="selectedTemplateId == tpl.id"></i>
                        </div>
                    </template>
                    <div class="border-t border-gray-700">
                        <div class="px-4 py-2 hover:bg-gray-700 cursor-pointer" @click="openCreateTemplateModal()">
                            <i class="fas fa-plus-circle text-blue-500 mr-2"></i> Create Template
                        </div>
                        <div class="px-4 py-2 hover:bg-gray-700 cursor-pointer" @click="openManageTemplatesModal()">
                            <i class="fas fa-cog text-blue-500 mr-2"></i> Manage Templates
                        </div>
                    </div>
                </div>
            </div>
            <input type="hidden" id="selectedTemplateId" x-model="selectedTemplateId">
        </div>

        <!-- Direction & Session -->
        <div class="grid md:grid-cols-2 gap-6 p-6 border-b border-gray-800">
            <div>
                <label class="block text-gray-400 text-sm font-semibold mb-2">Direction</label>
                <div class="flex gap-3">
                    <button @click="direction = 'Bullish'" :class="direction === 'Bullish' ? 'bg-green-600 border-green-600' : 'bg-gray-800 border-gray-700'" class="flex-1 py-2 rounded-lg border font-semibold transition">Bullish</button>
                    <button @click="direction = 'Bearish'" :class="direction === 'Bearish' ? 'bg-red-600 border-red-600' : 'bg-gray-800 border-gray-700'" class="flex-1 py-2 rounded-lg border font-semibold transition">Bearish</button>
                </div>
            </div>
            <div>
                <label class="block text-gray-400 text-sm font-semibold mb-2">Session</label>
                <div class="flex gap-3">
                    <button @click="session = 'London'" :class="session === 'London' ? 'bg-blue-600 border-blue-600' : 'bg-gray-800 border-gray-700'" class="flex-1 py-2 rounded-lg border font-semibold transition">London</button>
                    <button @click="session = 'New York'" :class="session === 'New York' ? 'bg-blue-600 border-blue-600' : 'bg-gray-800 border-gray-700'" class="flex-1 py-2 rounded-lg border font-semibold transition">New York</button>
                </div>
            </div>
        </div>

        <!-- Pair Watchlist -->
        <div class="p-6 border-b border-gray-800">
            <label class="block text-gray-400 text-sm font-semibold mb-2">Pair Watchlist</label>
            <div class="flex flex-wrap gap-2 items-center">
                <template x-for="pair in presetPairs" :key="pair">
                    <button @click="selectedPair = pair" :class="selectedPair === pair ? 'bg-blue-600 border-blue-600' : 'bg-gray-800 border-gray-700'" class="px-3 py-1 rounded-full border text-sm font-medium transition">
                        <span x-text="pair"></span>
                    </button>
                </template>
                <div class="flex gap-2">
                    <input type="text" x-model="customPair" @keyup.enter="addCustomPair" placeholder="e.g. NAS100" class="bg-gray-800 border border-gray-700 rounded-full px-3 py-1 text-sm focus:outline-none focus:border-blue-500">
                    <button @click="addCustomPair" class="bg-gray-800 hover:bg-gray-700 px-3 py-1 rounded-full text-sm">Add</button>
                </div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="px-6 pb-2">
            <div class="flex justify-between text-sm text-gray-400 mb-1">
                <span>Checklist Progress</span>
                <span x-text="checklistPercent + '%'"></span>
            </div>
            <div class="h-2 bg-gray-800 rounded-full overflow-hidden">
                <div class="h-full bg-blue-600 rounded-full transition-all duration-500" :style="{ width: checklistPercent + '%' }"></div>
            </div>
        </div>

        <!-- Two‑Column Checklist -->
        <div class="grid md:grid-cols-2 gap-6 p-6">
            <!-- HTF Column -->
            <div>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-white font-semibold">HTF Bias</h3>
                    <span class="text-gray-400 text-sm" x-text="htfMet + '/' + htfRequired"></span>
                </div>
                <div class="space-y-2">
                    <template x-for="rule in htfRules" :key="rule.id">
                        <div class="flex items-start gap-2 p-2 bg-gray-800 rounded-lg border border-gray-700 hover:border-blue-500 transition">
                            <input type="checkbox" x-model="checkedRules" :value="rule.id" class="mt-1 accent-blue-600">
                            <label class="text-gray-200 text-sm cursor-pointer flex-1" x-text="rule.label"></label>
                        </div>
                    </template>
                </div>
            </div>

            <!-- LTF Column -->
            <div>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-white font-semibold">LTF Entry Model</h3>
                    <span class="text-gray-400 text-sm" x-text="ltfMet + '/' + ltfRequired"></span>
                </div>
                <div class="space-y-2">
                    <template x-for="rule in ltfRules" :key="rule.id">
                        <div class="flex items-start gap-2 p-2 bg-gray-800 rounded-lg border border-gray-700 hover:border-blue-500 transition">
                            <input type="checkbox" x-model="checkedRules" :value="rule.id" class="mt-1 accent-blue-600">
                            <label class="text-gray-200 text-sm cursor-pointer flex-1" x-text="rule.label"></label>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Chart Uploads (simplified, just icons) -->
        <div class="p-6 border-b border-gray-800">
            <div class="flex items-center gap-2 text-white font-semibold mb-4">
                <i class="fas fa-camera text-blue-500"></i>
                <span>Chart Snapshots</span>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                <div class="bg-gray-800 border border-gray-700 rounded-lg p-3 text-center cursor-pointer hover:border-blue-500 transition" @click="uploadChart('daily')">
                    <i class="fas fa-calendar-day text-blue-500 text-xl mb-1 block"></i>
                    <span class="text-white text-sm">Daily</span>
                    <input type="file" id="dailyProof" style="display: none;" accept="image/*">
                </div>
                <div class="bg-gray-800 border border-gray-700 rounded-lg p-3 text-center cursor-pointer hover:border-blue-500 transition" @click="uploadChart('4h')">
                    <i class="fas fa-chart-line text-blue-500 text-xl mb-1 block"></i>
                    <span class="text-white text-sm">4 Hour</span>
                    <input type="file" id="fourHProof" style="display: none;" accept="image/*">
                </div>
                <div class="bg-gray-800 border border-gray-700 rounded-lg p-3 text-center cursor-pointer hover:border-blue-500 transition" @click="uploadChart('1h')">
                    <i class="fas fa-clock text-blue-500 text-xl mb-1 block"></i>
                    <span class="text-white text-sm">1 Hour</span>
                    <input type="file" id="oneHProof" style="display: none;" accept="image/*">
                </div>
                <div class="bg-gray-800 border border-gray-700 rounded-lg p-3 text-center cursor-pointer hover:border-blue-500 transition" @click="uploadChart('entry')">
                    <i class="fas fa-chart-line text-blue-500 text-xl mb-1 block"></i>
                    <span class="text-white text-sm">Entry</span>
                    <input type="file" id="entryProof" style="display: none;" accept="image/*">
                </div>
                <div class="bg-gray-800 border border-gray-700 rounded-lg p-3 text-center cursor-pointer hover:border-blue-500 transition" @click="uploadChart('after')">
                    <i class="fas fa-check-circle text-blue-500 text-xl mb-1 block"></i>
                    <span class="text-white text-sm">After</span>
                    <input type="file" id="afterProof" style="display: none;" accept="image/*">
                </div>
            </div>
        </div>

        <!-- Psychology -->
        <div class="p-6 border-b border-gray-800">
            <div class="flex items-center gap-2 text-white font-semibold mb-4">
                <i class="fas fa-brain text-purple-500"></i>
                <span>Psychology (Before Entry)</span>
            </div>
            <div class="flex flex-wrap gap-2">
                <template x-for="emotion in psychologyEmotions" :key="emotion">
                    <button @click="toggleEmotion(emotion)" :class="selectedEmotions.includes(emotion) ? 'bg-blue-600 text-white' : 'bg-gray-800 text-gray-400'" class="px-3 py-1 rounded-full text-sm transition hover:scale-105">
                        <span x-text="emotion"></span>
                    </button>
                </template>
                <button @click="openCustomEmotionModal" class="bg-gray-800 text-gray-400 px-3 py-1 rounded-full text-sm hover:bg-gray-700">+ Custom</button>
            </div>
            <input type="text" id="customNote" placeholder="💭 Optional note..." class="mt-4 w-full bg-gray-800 border border-gray-700 rounded-lg p-2 text-white focus:outline-none focus:border-blue-500">
        </div>

        <!-- Trade Summary -->
        <div class="p-6 bg-gray-800">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-white text-lg font-semibold">Trade Summary</h3>
                <span class="px-3 py-1 rounded-full text-lg font-bold" :class="gradeClass" x-text="grade"></span>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                <div><span class="text-gray-400 text-xs uppercase">Direction</span><p class="text-white text-lg font-bold" x-text="direction"></p></div>
                <div><span class="text-gray-400 text-xs uppercase">Session</span><p class="text-white text-lg font-bold" x-text="session"></p></div>
                <div><span class="text-gray-400 text-xs uppercase">Pair</span><p class="text-white text-lg font-bold" x-text="selectedPair"></p></div>
                <div><span class="text-gray-400 text-xs uppercase">Grade</span><p class="text-white text-lg font-bold" x-text="grade"></p></div>
            </div>
            <div class="flex gap-4 text-sm text-gray-400">
                <div>HTF Met: <strong class="text-white" x-text="htfMet"></strong></div>
                <div>LTF Met: <strong class="text-white" x-text="ltfMet"></strong></div>
                <div>Compliance: <strong class="text-white" x-text="checklistPercent + '%'"></strong></div>
            </div>
        </div>

        <!-- Decision Buttons -->
        <div class="p-6 flex gap-4">
            <button @click="submitTrade('TAKE')" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg transition">
                <i class="fas fa-check-circle mr-2"></i> TAKE TRADE
            </button>
            <button @click="showSkipModal" class="flex-1 bg-gray-700 hover:bg-gray-600 text-white font-bold py-3 rounded-lg transition">
                <i class="fas fa-times-circle mr-2"></i> SKIP TRADE
            </button>
        </div>
    </div>

    <!-- Recent Trades -->
    <?php if ($recent_trades->num_rows > 0): ?>
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
        <h3 class="text-white font-semibold mb-4">Recent Trades</h3>
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
        <div class="flex justify-between items-center p-4 bg-gray-800 rounded-lg mb-3 cursor-pointer hover:bg-gray-700 transition" onclick="window.location.href='trade_details.php?id=<?php echo $trade['id']; ?>'">
            <div>
                <h4 class="text-white font-medium"><?php echo $trade['pair']; ?></h4>
                <div class="text-gray-400 text-sm flex gap-3 mt-1">
                    <span><i class="far fa-calendar-alt"></i> <?php echo date('M j, g:i A', strtotime($trade['created_at'])); ?></span>
                    <?php if (!empty($trade['account_names'])): ?>
                    <span><i class="fas fa-wallet"></i> <?php echo $trade['account_names']; ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $grade_class; ?>"><?php echo $grade; ?></span>
                <span class="<?php echo $trade['outcome'] == 'Win' ? 'text-green-500' : ($trade['outcome'] == 'Loss' ? 'text-red-500' : 'text-gray-400'); ?>"><?php echo $trade['outcome']; ?></span>
                <i class="fas fa-chevron-right text-gray-500"></i>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>

    <!-- Performance Chart -->
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
        <h3 class="text-white font-semibold mb-4">Recent PnL (last 5 trades)</h3>
        <canvas id="recentPnlChart" height="150" class="w-full h-auto"></canvas>
    </div>
</div>

<!-- Skip Modal -->
<div class="modal fade" id="skipModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="bg-gray-900 border border-gray-800 rounded-xl shadow-xl">
            <div class="p-4 border-b border-gray-800 flex justify-between items-center">
                <h5 class="text-white text-lg font-semibold">Why are you skipping?</h5>
                <button type="button" class="text-gray-400 hover:text-white" data-bs-dismiss="modal"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-4">
                <select id="skipReason" class="w-full bg-gray-800 border border-gray-700 rounded-lg p-2 text-white mb-4 focus:outline-none focus:border-blue-500">
                    <option value="">Select reason...</option>
                    <option value="Not enough HTF confluence">Not enough HTF confluence</option>
                    <option value="Poor risk/reward ratio">Poor risk/reward ratio</option>
                    <option value="Outside trading hours">Outside trading hours</option>
                    <option value="News event nearby">News event nearby</option>
                    <option value="Not comfortable with setup">Not comfortable with setup</option>
                    <option value="Following trading plan">Following trading plan</option>
                </select>
                <textarea id="skipNotes" rows="3" class="w-full bg-gray-800 border border-gray-700 rounded-lg p-2 text-white focus:outline-none focus:border-blue-500" placeholder="Additional notes..."></textarea>
            </div>
            <div class="p-4 border-t border-gray-800 flex justify-end gap-3">
                <button type="button" class="px-3 py-1 bg-gray-700 hover:bg-gray-600 rounded-lg text-white transition" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 rounded-lg text-white font-semibold transition" @click="submitSkip()">Confirm Skip</button>
            </div>
        </div>
    </div>
</div>

<!-- Custom Emotion Modal -->
<div class="modal fade" id="customEmotionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="bg-gray-900 border border-gray-800 rounded-xl shadow-xl">
            <div class="p-4 border-b border-gray-800 flex justify-between items-center">
                <h5 class="text-white text-lg font-semibold">Add Custom Emotion</h5>
                <button type="button" class="text-gray-400 hover:text-white" data-bs-dismiss="modal"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-4">
                <input type="text" id="customEmotionInput" class="w-full bg-gray-800 border border-gray-700 rounded-lg p-2 text-white focus:outline-none focus:border-blue-500" placeholder="e.g. Confident">
            </div>
            <div class="p-4 border-t border-gray-800 flex justify-end gap-3">
                <button type="button" class="px-3 py-1 bg-gray-700 hover:bg-gray-600 rounded-lg text-white transition" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 rounded-lg text-white font-semibold transition" @click="addCustomEmotion()">Add</button>
            </div>
        </div>
    </div>
</div>

<!-- Include Daily Ritual Popup -->
<?php require_once 'daily_ritual_popup.php'; ?>
<!-- Template Modals -->
<?php require_once 'template_modals.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function showDailyRitual() {
    $('#dailyRitualModal').modal('show');
}

function tradeChecklist() {
    return {
        templates: <?php echo json_encode($templates->fetch_all(MYSQLI_ASSOC)); ?>,
        selectedTemplateId: null,
        selectedTemplateName: null,
        direction: 'Bullish',
        session: 'London',
        selectedPair: 'EUR/USD',
        presetPairs: ['EUR/USD', 'GBP/USD', 'USD/JPY', 'XAUUSD'],
        customPair: '',
        checkedRules: [],
        htfRules: [],
        ltfRules: [],
        htfRequired: 0,
        ltfRequired: 0,
        psychologyEmotions: <?php echo json_encode($psychology_emotions); ?>,
        selectedEmotions: [],
        customNote: '',
        progressPercent: 0,
        checklistPercent: 0,
        grade: 'C',
        gradeClass: 'bg-red-600',

        init() {
            if (this.templates.length) this.selectTemplate(this.templates[0]);
        },
        selectTemplate(tpl) {
            this.selectedTemplateId = tpl.id;
            this.selectedTemplateName = tpl.name;
            this.loadTemplateRules(tpl.id);
        },
        loadTemplateRules(id) {
            fetch(`ajax/get_template.php?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.htfRules = data.rules.filter(r => r.rule_type === 'HTF').map(r => ({ ...r, label: this.getDirectionLabel(r) }));
                        this.ltfRules = data.rules.filter(r => r.rule_type === 'LTF').map(r => ({ ...r, label: this.getDirectionLabel(r) }));
                        this.htfRequired = this.htfRules.filter(r => r.required).length;
                        this.ltfRequired = this.ltfRules.filter(r => r.required).length;
                        this.checkedRules = [];
                        this.updateProgress();
                    }
                });
        },
        getDirectionLabel(rule) {
            if (this.direction === 'Bullish' && rule.label_bullish) return rule.label_bullish;
            if (this.direction === 'Bearish' && rule.label_bearish) return rule.label_bearish;
            return rule.label;
        },
        updateProgress() {
            let totalRequired = this.htfRequired + this.ltfRequired;
            let met = this.htfMet + this.ltfMet;
            this.checklistPercent = totalRequired ? Math.round((met / totalRequired) * 100) : 0;
            this.progressPercent = this.checklistPercent;
            this.calculateGrade();
        },
        get htfMet() {
            return this.htfRules.filter(r => r.required && this.checkedRules.includes(r.id)).length;
        },
        get ltfMet() {
            return this.ltfRules.filter(r => r.required && this.checkedRules.includes(r.id)).length;
        },
        calculateGrade() {
            let htfWeight = this.htfRequired ? (this.htfMet / this.htfRequired) * 60 : 0;
            let ltfWeight = this.ltfRequired ? (this.ltfMet / this.ltfRequired) * 40 : 0;
            let score = htfWeight + ltfWeight;
            if (score >= 90 && this.htfMet >= Math.ceil(this.htfRequired * 0.8)) this.grade = 'A+';
            else if (score >= 75 && this.htfMet >= Math.ceil(this.htfRequired * 0.6)) this.grade = 'A';
            else if (score >= 60 && this.htfMet >= Math.ceil(this.htfRequired * 0.5)) this.grade = 'B+';
            else if (score >= 45) this.grade = 'B';
            else if (score >= 30) this.grade = 'C+';
            else this.grade = 'C';
            this.gradeClass = this.grade === 'A+' ? 'bg-green-600' : (this.grade === 'A' ? 'bg-blue-600' : (this.grade === 'B+' ? 'bg-yellow-600' : (this.grade === 'B' ? 'bg-orange-600' : 'bg-red-600')));
        },
        addCustomPair() {
            let pair = this.customPair.trim().toUpperCase();
            if (pair && !this.presetPairs.includes(pair)) {
                this.presetPairs.push(pair);
                this.selectedPair = pair;
                this.customPair = '';
            } else if (pair) {
                this.selectedPair = pair;
                this.customPair = '';
            }
        },
        toggleEmotion(emotion) {
            if (this.selectedEmotions.includes(emotion)) {
                this.selectedEmotions = this.selectedEmotions.filter(e => e !== emotion);
            } else {
                this.selectedEmotions.push(emotion);
            }
        },
        openCustomEmotionModal() {
            $('#customEmotionModal').modal('show');
        },
        addCustomEmotion() {
            let emotion = document.getElementById('customEmotionInput').value.trim();
            if (emotion && !this.psychologyEmotions.includes(emotion)) {
                this.psychologyEmotions.push(emotion);
                this.selectedEmotions.push(emotion);
            }
            $('#customEmotionModal').modal('hide');
            document.getElementById('customEmotionInput').value = '';
        },
        uploadChart(type) {
            let input;
            if (type === 'daily') input = document.getElementById('dailyProof');
            else if (type === '4h') input = document.getElementById('fourHProof');
            else if (type === '1h') input = document.getElementById('oneHProof');
            else if (type === 'entry') input = document.getElementById('entryProof');
            else if (type === 'after') input = document.getElementById('afterProof');
            input.click();
            input.onchange = () => {
                if (input.files && input.files[0]) {
                    let card = input.parentElement;
                    card.style.borderColor = '#10b981';
                    card.querySelector('span').innerHTML = '<i class="fas fa-check"></i> Uploaded';
                    Swal.fire({ icon: 'success', title: 'Uploaded!', text: input.files[0].name, background: '#11161f', color: 'white', timer: 1500, showConfirmButton: false });
                }
            };
        },
        showSkipModal() {
            $('#skipModal').modal('show');
        },
        submitSkip() {
            let reason = document.getElementById('skipReason').value;
            if (!reason) {
                Swal.fire({ icon: 'warning', title: 'Reason Required', text: 'Please select a reason', background: '#11161f', color: 'white' });
                return;
            }
            this.submitTrade('SKIP');
        },
        submitTrade(decision) {
            let formData = new FormData();
            formData.append('decision', decision);
            formData.append('template_id', this.selectedTemplateId);
            formData.append('direction', this.direction);
            formData.append('session', this.session);
            formData.append('pair', this.selectedPair);
            formData.append('trade_grade', this.grade);
            formData.append('checked_rule_ids', JSON.stringify(this.checkedRules));
            formData.append('psychology', JSON.stringify(this.selectedEmotions));
            formData.append('htf_met', this.htfMet);
            formData.append('ltf_met', this.ltfMet);
            formData.append('compliance', this.checklistPercent);
            formData.append('custom_note', document.getElementById('customNote').value);

            ['dailyProof', 'fourHProof', 'oneHProof', 'entryProof', 'afterProof'].forEach(chart => {
                let input = document.getElementById(chart);
                if (input && input.files && input.files[0]) formData.append(chart, input.files[0]);
            });

            if (decision === 'SKIP') {
                formData.append('skip_reason', document.getElementById('skipReason').value);
                formData.append('skip_notes', document.getElementById('skipNotes').value);
            }

            Swal.fire({ title: 'Saving...', text: 'Please wait', allowOutsideClick: false, didOpen: () => Swal.showLoading(), background: '#11161f', color: 'white' });

            $.ajax({
                url: 'save_trade.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        $('#skipModal').modal('hide');
                        Swal.fire({ icon: 'success', title: 'Trade Saved!', text: response.message, background: '#11161f', color: 'white', timer: 2000, showConfirmButton: false }).then(() => location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: response.message, background: '#11161f', color: 'white' });
                    }
                }
            });
        },
        openNewTradeModal() {
            // Not implemented in this version – you could open a quick trade modal
        },
        openCreateTemplateModal() {
            window.location.href = 'templates.php';
        },
        openManageTemplatesModal() {
            window.location.href = 'templates.php';
        }
    };
}

// Chart.js for recent PnL
fetch('ajax/get_recent_pnl.php')
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const ctx = document.getElementById('recentPnlChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'PnL',
                        data: data.pnl,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59,130,246,0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { grid: { color: '#1e293b' } } }
                }
            });
        }
    });
</script>