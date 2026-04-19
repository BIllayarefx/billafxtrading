<?php
$page_title = 'Weekly Outlook';
require_once 'config.php';
require_once 'auth.php';

if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$outlooks_query = "SELECT * FROM weekly_outlooks WHERE user_id = ? ORDER BY week_starting DESC, created_at DESC";
$stmt = $conn->prepare($outlooks_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$outlooks = $stmt->get_result();

require_once 'header.php';
?>

<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-wrap justify-between items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white">Weekly Outlook</h1>
            <p class="text-gray-400 mt-1">Store your weekly analysis and chart setups</p>
        </div>
        <button class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition" onclick="openOutlookModal()">
            <i class="fas fa-plus mr-2"></i> New Outlook
        </button>
    </div>

    <!-- Search -->
    <div class="relative">
        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-500"></i>
        <input type="text" id="searchInput" placeholder="Search by pair..." class="w-full bg-gray-800 border border-gray-700 rounded-lg py-2 pl-10 pr-4 text-white focus:outline-none focus:border-blue-500" onkeyup="filterOutlooks()">
    </div>

    <!-- Outlook Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="outlookGrid">
        <?php if ($outlooks->num_rows > 0): ?>
            <?php while($outlook = $outlooks->fetch_assoc()): 
                $week_start = new DateTime($outlook['week_starting']);
                $week_end = clone $week_start;
                $week_end->modify('+6 days');
            ?>
            <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden hover:shadow-lg transition" data-id="<?php echo $outlook['id']; ?>" data-pair="<?php echo strtolower($outlook['pair']); ?>">
                <div class="p-4 border-b border-gray-800 flex justify-between items-center">
                    <div class="text-gray-400 text-sm flex items-center gap-2">
                        <i class="far fa-calendar-alt text-blue-500"></i>
                        <?php echo $week_start->format('M j') . ' - ' . $week_end->format('M j, Y'); ?>
                    </div>
                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $outlook['bias'] == 'Bullish' ? 'bg-green-600/20 text-green-500' : ($outlook['bias'] == 'Bearish' ? 'bg-red-600/20 text-red-500' : 'bg-gray-600/20 text-gray-400'); ?>">
                        <?php echo $outlook['bias']; ?>
                    </span>
                </div>
                <div class="p-5 cursor-pointer" onclick="viewOutlook(<?php echo htmlspecialchars(json_encode($outlook)); ?>)">
                    <h3 class="text-white text-xl font-bold mb-2"><?php echo $outlook['pair']; ?></h3>
                    <div class="text-gray-400 text-sm line-clamp-3"><?php echo nl2br(htmlspecialchars(substr($outlook['analysis'], 0, 150))); ?>...</div>
                </div>
                <div class="p-4 border-t border-gray-800 flex justify-between items-center">
                    <div class="text-blue-500 text-sm flex items-center gap-1">
                        <i class="fas fa-<?php echo $outlook['chart_image'] ? 'image' : 'chart-line'; ?>"></i>
                        <span><?php echo $outlook['chart_image'] ? 'Chart attached' : 'No chart'; ?></span>
                    </div>
                    <div class="flex gap-2">
                        <button class="w-8 h-8 rounded-lg bg-gray-800 hover:bg-blue-600 text-blue-500 hover:text-white transition" onclick="event.stopPropagation(); editOutlook(<?php echo $outlook['id']; ?>)"><i class="fas fa-edit"></i></button>
                        <button class="w-8 h-8 rounded-lg bg-gray-800 hover:bg-red-600 text-red-500 hover:text-white transition" onclick="event.stopPropagation(); deleteOutlook(<?php echo $outlook['id']; ?>)"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-span-full text-center py-16 bg-gray-900 border border-gray-800 rounded-xl">
                <i class="fas fa-calendar-week text-5xl text-gray-700 mb-4"></i>
                <h3 class="text-white text-xl font-semibold mb-2">No Weekly Outlooks</h3>
                <p class="text-gray-400 mb-6">Create your first weekly outlook to track your market analysis</p>
                <button class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition" onclick="openOutlookModal()">
                    <i class="fas fa-plus"></i> Create Outlook
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create/Edit Outlook Modal (matches the sample image) -->
<div class="modal fade" id="outlookModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="bg-gray-900 border border-gray-800 rounded-xl shadow-xl">
            <div class="p-5 border-b border-gray-800 flex justify-between items-center">
                <h5 class="text-white text-xl font-semibold" id="outlookModalTitle">Create Outlook</h5>
                <button type="button" class="text-gray-400 hover:text-white" data-bs-dismiss="modal"><i class="fas fa-times"></i></button>
            </div>
            <form id="outlookForm" enctype="multipart/form-data">
                <div class="p-5 space-y-4">
                    <input type="hidden" id="outlookId" name="id" value="0">

                    <!-- Pair and Week Start -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-400 text-sm mb-1">Pair</label>
                            <select name="pair" id="pair" class="w-full bg-gray-800 border border-gray-700 rounded-lg p-2 text-white" required>
                                <option value="EUR/USD">EUR/USD</option>
                                <option value="GBP/USD">GBP/USD</option>
                                <option value="USD/JPY">USD/JPY</option>
                                <option value="AUD/USD">AUD/USD</option>
                                <option value="USD/CAD">USD/CAD</option>
                                <option value="NZD/USD">NZD/USD</option>
                                <option value="USD/CHF">USD/CHF</option>
                                <option value="XAU/USD">XAU/USD (Gold)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-400 text-sm mb-1">Week Starting</label>
                            <input type="date" name="week_starting" id="week_starting" class="w-full bg-gray-800 border border-gray-700 rounded-lg p-2 text-white" required>
                        </div>
                    </div>

                    <!-- Bias -->
                    <div>
                        <label class="block text-gray-400 text-sm mb-1">Bias</label>
                        <div class="flex gap-3">
                            <button type="button" class="flex-1 py-2 rounded-lg border border-gray-700 text-gray-400 hover:bg-green-600/20 hover:text-green-500 transition" data-bias="Bullish" onclick="selectBias(this, 'Bullish')">Bullish</button>
                            <button type="button" class="flex-1 py-2 rounded-lg border border-gray-700 text-gray-400 hover:bg-red-600/20 hover:text-red-500 transition" data-bias="Bearish" onclick="selectBias(this, 'Bearish')">Bearish</button>
                            <button type="button" class="flex-1 py-2 rounded-lg border border-gray-700 text-gray-400 hover:bg-gray-600/20 hover:text-white transition" data-bias="Neutral" onclick="selectBias(this, 'Neutral')">Neutral</button>
                        </div>
                        <input type="hidden" name="bias" id="selectedBias" value="Bullish">
                    </div>

                    <!-- Session Focus -->
                    <div>
                        <label class="block text-gray-400 text-sm mb-1">Session Focus</label>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="px-3 py-1 rounded-full border border-gray-700 text-gray-400 hover:bg-blue-600/20 hover:text-blue-500 transition" data-session="London" onclick="selectSessionFocus(this, 'London')">London</button>
                            <button type="button" class="px-3 py-1 rounded-full border border-gray-700 text-gray-400 hover:bg-blue-600/20 hover:text-blue-500 transition" data-session="New York" onclick="selectSessionFocus(this, 'New York')">New York</button>
                            <button type="button" class="px-3 py-1 rounded-full border border-gray-700 text-gray-400 hover:bg-blue-600/20 hover:text-blue-500 transition" data-session="Asian" onclick="selectSessionFocus(this, 'Asian')">Asian</button>
                            <button type="button" class="px-3 py-1 rounded-full border border-gray-700 text-gray-400 hover:bg-blue-600/20 hover:text-blue-500 transition" data-session="Any" onclick="selectSessionFocus(this, 'Any')">Any</button>
                        </div>
                        <input type="hidden" name="session_focus" id="sessionFocus" value="Any">
                    </div>

                    <!-- Timeframes -->
                    <div>
                        <label class="block text-gray-400 text-sm mb-1">Timeframes</label>
                        <div class="flex flex-wrap gap-2">
                            <?php $timeframes = ['Monthly', 'Weekly', 'Daily', '4H', '1H', '15m', '5m']; ?>
                            <?php foreach ($timeframes as $tf): ?>
                            <label class="inline-flex items-center gap-1 cursor-pointer">
                                <input type="checkbox" name="timeframes[]" value="<?php echo $tf; ?>" class="accent-blue-600">
                                <span class="text-gray-300 text-sm"><?php echo $tf; ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Confidence Slider -->
                    <div>
                        <label class="block text-gray-400 text-sm mb-1">Confidence (%)</label>
                        <input type="range" name="confidence" min="0" max="100" value="50" class="w-full accent-blue-600" id="confidenceSlider">
                        <div class="text-right text-gray-400 text-sm mt-1"><span id="confidenceValue">50</span>%</div>
                    </div>

                    <!-- Key Levels -->
                    <div>
                        <label class="block text-gray-400 text-sm mb-1">Key Levels (HTF PD array / key levels)</label>
                        <textarea name="key_levels" rows="3" class="w-full bg-gray-800 border border-gray-700 rounded-lg p-2 text-white focus:outline-none focus:border-blue-500"></textarea>
                    </div>

                    <!-- Narrative & Notes -->
                    <div>
                        <label class="block text-gray-400 text-sm mb-1">Narrative & Notes (Weekly narrative, expectations, plan)</label>
                        <textarea name="analysis" id="analysis" rows="5" class="w-full bg-gray-800 border border-gray-700 rounded-lg p-2 text-white focus:outline-none focus:border-blue-500" required></textarea>
                    </div>

                    <!-- Chart Images Upload -->
                    <div>
                        <label class="block text-gray-400 text-sm mb-1">Chart Images</label>
                        <div class="border-2 border-dashed border-gray-700 rounded-lg p-6 text-center cursor-pointer hover:border-blue-500 transition" onclick="document.getElementById('chartFile').click()">
                            <i class="fas fa-cloud-upload-alt text-3xl text-blue-500 mb-2"></i>
                            <p class="text-gray-400">Click to upload chart image</p>
                            <p class="text-gray-500 text-xs">PNG, JPG up to 5MB • Multiple images supported</p>
                        </div>
                        <input type="file" id="chartFile" name="chart_image" accept="image/*" style="display: none;" onchange="previewImage(this)">
                        <img id="imagePreview" class="mt-3 max-h-32 rounded-lg hidden" src="#" alt="Preview">
                        <div id="currentChart" class="text-green-500 text-sm mt-2"></div>
                    </div>
                </div>
                <div class="p-5 border-t border-gray-800 flex justify-end gap-3">
                    <button type="button" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-white transition" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-white font-semibold transition" onclick="saveOutlook()">Save Outlook</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Outlook Modal -->
<div class="modal fade outlook-view-modal" id="viewOutlookModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="bg-gray-900 border border-gray-800 rounded-xl shadow-xl">
            <div class="p-5 border-b border-gray-800 flex justify-between items-center">
                <h5 class="text-white text-xl font-semibold">Weekly Outlook</h5>
                <button type="button" class="text-gray-400 hover:text-white" data-bs-dismiss="modal"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-5" id="viewOutlookContent"></div>
        </div>
    </div>
</div>

<!-- Image Preview Modal -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="bg-gray-900 border border-gray-800 rounded-xl shadow-xl">
            <div class="p-5 border-b border-gray-800 flex justify-between items-center">
                <h5 class="text-white text-xl font-semibold">Chart Preview</h5>
                <button type="button" class="text-gray-400 hover:text-white" data-bs-dismiss="modal"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-5 text-center">
                <img id="fullPreviewImage" src="" class="max-w-full max-h-[70vh] rounded-lg">
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let currentOutlookId = 0;

function openOutlookModal() {
    document.getElementById('outlookModalTitle').innerText = 'Create Outlook';
    document.getElementById('outlookId').value = '0';
    document.getElementById('outlookForm').reset();
    document.getElementById('imagePreview').classList.add('hidden');
    document.getElementById('currentChart').innerHTML = '';
    // Set week starting to this Monday
    let today = new Date();
    let day = today.getDay();
    let diff = today.getDate() - day + (day === 0 ? -6 : 1);
    let monday = new Date(today.setDate(diff));
    document.getElementById('week_starting').value = monday.toISOString().slice(0,10);
    // Reset bias
    document.querySelectorAll('[data-bias]').forEach(btn => btn.classList.remove('bg-green-600/20', 'text-green-500', 'bg-red-600/20', 'text-red-500', 'bg-gray-600/20', 'text-white'));
    document.querySelector('[data-bias="Bullish"]').classList.add('bg-green-600/20', 'text-green-500');
    document.getElementById('selectedBias').value = 'Bullish';
    // Reset session focus
    document.querySelectorAll('[data-session]').forEach(btn => btn.classList.remove('bg-blue-600/20', 'text-blue-500'));
    document.querySelector('[data-session="Any"]').classList.add('bg-blue-600/20', 'text-blue-500');
    document.getElementById('sessionFocus').value = 'Any';
    // Reset confidence slider
    document.getElementById('confidenceSlider').value = 50;
    document.getElementById('confidenceValue').innerText = '50';
    $('#outlookModal').modal('show');
}

function editOutlook(id) {
    fetch(`ajax/get_weekly_outlook.php?id=${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                currentOutlookId = id;
                document.getElementById('outlookModalTitle').innerText = 'Edit Outlook';
                document.getElementById('outlookId').value = data.outlook.id;
                document.getElementById('week_starting').value = data.outlook.week_starting;
                document.getElementById('pair').value = data.outlook.pair;
                document.getElementById('analysis').value = data.outlook.analysis;
                // Bias
                document.querySelectorAll('[data-bias]').forEach(btn => btn.classList.remove('bg-green-600/20', 'text-green-500', 'bg-red-600/20', 'text-red-500', 'bg-gray-600/20', 'text-white'));
                let biasBtn = document.querySelector(`[data-bias="${data.outlook.bias}"]`);
                if (biasBtn) {
                    if (data.outlook.bias === 'Bullish') biasBtn.classList.add('bg-green-600/20', 'text-green-500');
                    else if (data.outlook.bias === 'Bearish') biasBtn.classList.add('bg-red-600/20', 'text-red-500');
                    else biasBtn.classList.add('bg-gray-600/20', 'text-white');
                }
                document.getElementById('selectedBias').value = data.outlook.bias;
                // Session focus (if stored, otherwise default)
                if (data.outlook.session_focus) {
                    document.querySelectorAll('[data-session]').forEach(btn => btn.classList.remove('bg-blue-600/20', 'text-blue-500'));
                    let sessionBtn = document.querySelector(`[data-session="${data.outlook.session_focus}"]`);
                    if (sessionBtn) sessionBtn.classList.add('bg-blue-600/20', 'text-blue-500');
                    document.getElementById('sessionFocus').value = data.outlook.session_focus;
                }
                // Confidence (if stored)
                if (data.outlook.confidence) {
                    document.getElementById('confidenceSlider').value = data.outlook.confidence;
                    document.getElementById('confidenceValue').innerText = data.outlook.confidence;
                }
                // Key levels
                if (data.outlook.key_levels) {
                    document.querySelector('textarea[name="key_levels"]').value = data.outlook.key_levels;
                }
                // Timeframes (if stored as JSON or comma separated)
                if (data.outlook.timeframes) {
                    let tfs = data.outlook.timeframes.split(',');
                    document.querySelectorAll('input[name="timeframes[]"]').forEach(cb => {
                        cb.checked = tfs.includes(cb.value);
                    });
                }
                if (data.outlook.chart_image) {
                    document.getElementById('currentChart').innerHTML = `Current: ${data.outlook.chart_image}`;
                }
                document.getElementById('imagePreview').classList.add('hidden');
                $('#outlookModal').modal('show');
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        });
}

function saveOutlook() {
    let form = document.getElementById('outlookForm');
    let formData = new FormData(form);
    // Add session focus and confidence manually (they are not in the form)
    formData.append('session_focus', document.getElementById('sessionFocus').value);
    formData.append('confidence', document.getElementById('confidenceSlider').value);
    // Add key levels
    formData.append('key_levels', document.querySelector('textarea[name="key_levels"]').value);
    // Add timeframes (comma separated)
    let selectedTFs = [];
    document.querySelectorAll('input[name="timeframes[]"]:checked').forEach(cb => selectedTFs.push(cb.value));
    formData.append('timeframes', selectedTFs.join(','));

    Swal.fire({ title: 'Saving...', text: 'Please wait', allowOutsideClick: false, didOpen: () => Swal.showLoading(), background: '#11161f', color: 'white' });
    fetch('ajax/save_weekly_outlook.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            $('#outlookModal').modal('hide');
            Swal.fire({ icon: 'success', title: 'Success!', text: data.message, background: '#11161f', color: 'white', timer: 2000, showConfirmButton: false }).then(() => location.reload());
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    });
}

function deleteOutlook(id) {
    Swal.fire({
        title: 'Delete Outlook?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Delete',
        background: '#11161f',
        color: 'white'
    }).then((result) => {
        if (result.isConfirmed) {
            let formData = new FormData();
            formData.append('id', id);
            fetch('ajax/delete_weekly_outlook.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({ icon: 'success', title: 'Deleted', text: 'Outlook deleted', background: '#11161f', color: 'white', timer: 1500, showConfirmButton: false }).then(() => location.reload());
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
        }
    });
}

function viewOutlook(outlook) {
    let weekStart = new Date(outlook.week_starting);
    let weekEnd = new Date(weekStart);
    weekEnd.setDate(weekEnd.getDate() + 6);
    let dateRange = weekStart.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + ' - ' + weekEnd.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    let html = `
        <div class="mb-6">
            <div class="text-gray-400 text-sm">${dateRange}</div>
            <div class="text-white text-3xl font-bold">${outlook.pair}</div>
            <div class="mt-2 flex flex-wrap gap-2">
                <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold ${outlook.bias === 'Bullish' ? 'bg-green-600/20 text-green-500' : (outlook.bias === 'Bearish' ? 'bg-red-600/20 text-red-500' : 'bg-gray-600/20 text-gray-400')}">${outlook.bias}</span>
                ${outlook.session_focus ? `<span class="inline-block px-3 py-1 rounded-full bg-blue-600/20 text-blue-500 text-sm">Session: ${outlook.session_focus}</span>` : ''}
                ${outlook.confidence ? `<span class="inline-block px-3 py-1 rounded-full bg-gray-600/20 text-gray-400 text-sm">Confidence: ${outlook.confidence}%</span>` : ''}
            </div>
        </div>
        ${outlook.key_levels ? `<div class="bg-gray-800/50 rounded-lg p-4 mb-4"><h4 class="text-blue-500 font-semibold mb-2">Key Levels</h4><p class="text-gray-200 whitespace-pre-line">${outlook.key_levels.replace(/\n/g, '<br>')}</p></div>` : ''}
        <div class="bg-gray-800/50 rounded-lg p-5 text-gray-200 whitespace-pre-line">${outlook.analysis.replace(/\n/g, '<br>')}</div>
    `;
    if (outlook.chart_image) {
        html += `<img src="uploads/${outlook.chart_image}" class="mt-5 w-full rounded-lg cursor-pointer" onclick="previewFullImage('uploads/${outlook.chart_image}')">`;
    }
    document.getElementById('viewOutlookContent').innerHTML = html;
    $('#viewOutlookModal').modal('show');
}

function previewFullImage(src) {
    document.getElementById('fullPreviewImage').src = src;
    $('#imagePreviewModal').modal('show');
}

function selectBias(element, bias) {
    document.querySelectorAll('[data-bias]').forEach(btn => btn.classList.remove('bg-green-600/20', 'text-green-500', 'bg-red-600/20', 'text-red-500', 'bg-gray-600/20', 'text-white'));
    if (bias === 'Bullish') element.classList.add('bg-green-600/20', 'text-green-500');
    else if (bias === 'Bearish') element.classList.add('bg-red-600/20', 'text-red-500');
    else element.classList.add('bg-gray-600/20', 'text-white');
    document.getElementById('selectedBias').value = bias;
}

function selectSessionFocus(element, session) {
    document.querySelectorAll('[data-session]').forEach(btn => btn.classList.remove('bg-blue-600/20', 'text-blue-500'));
    element.classList.add('bg-blue-600/20', 'text-blue-500');
    document.getElementById('sessionFocus').value = session;
}

function previewImage(input) {
    if (input.files && input.files[0]) {
        let reader = new FileReader();
        reader.onload = function(e) {
            let preview = document.getElementById('imagePreview');
            preview.src = e.target.result;
            preview.classList.remove('hidden');
        };
        reader.readAsDataURL(input.files[0]);
        document.getElementById('currentChart').innerHTML = '';
    }
}

function filterOutlooks() {
    let search = document.getElementById('searchInput').value.toLowerCase();
    let cards = document.querySelectorAll('#outlookGrid > div');
    cards.forEach(card => {
        let pair = card.dataset.pair;
        if (pair.includes(search)) card.style.display = 'block';
        else card.style.display = 'none';
    });
}

// Confidence slider display
document.getElementById('confidenceSlider').addEventListener('input', function() {
    document.getElementById('confidenceValue').innerText = this.value;
});
</script>