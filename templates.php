<?php
$page_title = 'Templates';
require_once 'config.php';
require_once 'auth.php';
if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// Fetch all templates
$templates = $conn->prepare("SELECT * FROM templates WHERE user_id = ? ORDER BY created_at DESC");
$templates->bind_param("i", $user_id);
$templates->execute();
$templates = $templates->get_result();

require_once 'header.php';
?>

<style>
/* Add some custom styles for template cards and modals */
.template-card {
    background: #11161f;
    border: 1px solid #1e293b;
    border-radius: 20px;
    padding: 20px;
    transition: all 0.3s;
    cursor: pointer;
}
.template-card:hover {
    border-color: #3b82f6;
    transform: translateY(-5px);
}
.template-card h3 {
    color: white;
    font-size: 18px;
    margin: 0 0 5px 0;
}
.template-card p {
    color: #64748b;
    font-size: 13px;
    margin: 0 0 15px 0;
}
.template-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid #1e293b;
    padding-top: 15px;
    margin-top: 15px;
}
.scope-badge {
    padding: 4px 10px;
    border-radius: 30px;
    font-size: 11px;
    font-weight: 600;
    background: #1e293b;
    color: #94a3b8;
}
.scope-badge i {
    margin-right: 4px;
}
.rule-count {
    color: #3b82f6;
    font-weight: 600;
}
.delete-btn {
    color: #ef4444;
    background: transparent;
    border: 1px solid #ef4444;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
}
.delete-btn:hover {
    background: #ef4444;
    color: white;
}
</style>

<div class="main-container">
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <div>
            <h1 style="color: white;">Templates</h1>
            <p style="color: #64748b;">Create and manage your trading checklists</p>
        </div>
        <button class="btn-new" onclick="openCreateTemplateModal()">
            <i class="fas fa-plus"></i> New Template
        </button>
    </div>

    <!-- Templates Grid -->
    <div class="row" id="templatesGrid">
        <?php if ($templates->num_rows > 0): ?>
            <?php while ($t = $templates->fetch_assoc()): 
                // Count rules
                $ruleCount = $conn->prepare("SELECT COUNT(*) as cnt FROM template_rules WHERE template_id = ? AND parent_id IS NULL");
                $ruleCount->bind_param("i", $t['id']);
                $ruleCount->execute();
                $rc = $ruleCount->get_result()->fetch_assoc()['cnt'];
            ?>
            <div class="col-md-4 col-sm-6 mb-4">
                <div class="template-card" onclick="editTemplate(<?php echo $t['id']; ?>)">
                    <h3><?php echo htmlspecialchars($t['name']); ?></h3>
                    <p><?php echo htmlspecialchars($t['description'] ?: 'No description'); ?></p>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 10px;">
                        <span class="scope-badge"><i class="fas fa-arrows-alt-h"></i> <?php echo $t['direction_scope']; ?></span>
                        <span class="scope-badge"><i class="fas fa-globe"></i> <?php echo $t['session_scope']; ?></span>
                    </div>
                    <div class="template-footer">
                        <span class="rule-count"><?php echo $rc; ?> rules</span>
                        <button class="delete-btn" onclick="event.stopPropagation(); deleteTemplate(<?php echo $t['id']; ?>, '<?php echo htmlspecialchars($t['name']); ?>')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center" style="padding: 60px 20px; color: #64748b;">
                <i class="fas fa-clipboard-list" style="font-size: 64px; margin-bottom: 20px;"></i>
                <h3 style="color: white;">No templates yet</h3>
                <p>Create your first template to get started</p>
                <button class="btn-new" onclick="openCreateTemplateModal()" style="display: inline-flex; margin-top: 15px;">
                    <i class="fas fa-plus"></i> Create Template
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create/Edit Template Modal (Step 1: Basic Info) -->
<div class="modal fade" id="templateBasicModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="background: #11161f; border: 1px solid #1e293b;">
            <div class="modal-header">
                <h5 class="modal-title" style="color: white;" id="templateModalTitle">Create Template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: invert(1);"></button>
            </div>
            <form id="templateBasicForm">
                <div class="modal-body">
                    <input type="hidden" id="templateId" name="id">
                    <div class="form-group mb-3">
                        <label style="color: #94a3b8;">Template Name *</label>
                        <input type="text" class="form-control" id="templateName" name="name" required style="background: #0f172a; border: 2px solid #1e293b; color: white;">
                    </div>
                    <div class="form-group mb-3">
                        <label style="color: #94a3b8;">Description</label>
                        <textarea class="form-control" id="templateDescription" name="description" rows="2" style="background: #0f172a; border: 2px solid #1e293b; color: white;"></textarea>
                    </div>
                    <div class="form-group mb-3">
                        <label style="color: #94a3b8;">Direction Scope</label>
                        <select class="form-select" id="directionScope" name="direction_scope" style="background: #0f172a; border: 2px solid #1e293b; color: white;">
                            <option value="Both">Both</option>
                            <option value="Bullish">Bullish</option>
                            <option value="Bearish">Bearish</option>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label style="color: #94a3b8;">Session Scope</label>
                        <select class="form-select" id="sessionScope" name="session_scope" style="background: #0f172a; border: 2px solid #1e293b; color: white;">
                            <option value="Both">Both</option>
                            <option value="London">London</option>
                            <option value="New York">New York</option>
                            <option value="Asian">Asian</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background: #3b82f6;">Next: Add Rules</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Rule Editor Modal (Step 2) -->
<div class="modal fade" id="ruleEditorModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl">
        <div class="modal-content" style="background: #11161f; border: 1px solid #1e293b;">
            <div class="modal-header">
                <h5 class="modal-title" style="color: white;" id="ruleEditorTitle">Edit Rules</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: invert(1);"></button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <div id="ruleList" class="mb-3">
                    <!-- Rules will be dynamically added here -->
                </div>
                <button type="button" class="btn btn-outline-primary" onclick="addRuleGroup()">
                    <i class="fas fa-plus"></i> Add Rule Group
                </button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="backToBasic()">Back</button>
                <button type="button" class="btn btn-success" onclick="saveTemplate()">Save Template</button>
            </div>
        </div>
    </div>
</div>

<!-- Template for a rule group (used by JavaScript) -->
<template id="ruleGroupTemplate">
    <div class="rule-group card mb-3" style="background: #0f172a; border: 1px solid #1e293b;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="card-title" style="color: white;">Rule Group</h6>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRuleGroup(this)"><i class="fas fa-times"></i></button>
            </div>
            <div class="row g-2 mb-2">
                <div class="col-md-4">
                    <select class="form-select rule-type" style="background: #1e293b; color: white;">
                        <option value="HTF">HTF</option>
                        <option value="LTF">LTF</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <select class="form-select group-type" style="background: #1e293b; color: white;">
                        <option value="single">Single Rule</option>
                        <option value="either_or">Either/Or</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="form-check">
                        <input class="form-check-input required-check" type="checkbox" id="requiredCheck">
                        <label class="form-check-label" style="color: #94a3b8;">Required</label>
                    </div>
                </div>
            </div>
            <div class="mb-2">
                <input type="text" class="form-control rule-label-bullish" placeholder="Label for Bullish (optional)" style="background: #1e293b; color: white;">
            </div>
            <div class="mb-2">
                <input type="text" class="form-control rule-label-bearish" placeholder="Label for Bearish (optional)" style="background: #1e293b; color: white;">
            </div>
            <div class="mb-2">
                <input type="text" class="form-control rule-label-fallback" placeholder="Default label (required)" style="background: #1e293b; color: white;">
            </div>
            <div class="child-rules mt-3">
                <!-- For either/or, child rules go here -->
            </div>
            <button type="button" class="btn btn-sm btn-outline-info add-child-rule" onclick="addChildRule(this)" style="display: none;">Add Child Rule</button>
        </div>
    </div>
</template>

<!-- Template for a child rule -->
<template id="childRuleTemplate">
    <div class="child-rule d-flex align-items-center gap-2 mb-2">
        <input type="text" class="form-control form-control-sm child-label" placeholder="Child rule label" style="background: #1e293b; color: white;">
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeChildRule(this)"><i class="fas fa-minus"></i></button>
    </div>
</template>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let currentTemplateId = null;
let rulesData = []; // will hold rules for editing

function openCreateTemplateModal() {
    document.getElementById('templateModalTitle').innerText = 'Create Template';
    document.getElementById('templateId').value = '';
    document.getElementById('templateBasicForm').reset();
    new bootstrap.Modal(document.getElementById('templateBasicModal')).show();
}

function editTemplate(id) {
    // Fetch template data and open rule editor
    fetch(`ajax/get_template.php?id=${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                currentTemplateId = id;
                document.getElementById('templateId').value = id;
                document.getElementById('templateName').value = data.template.name;
                document.getElementById('templateDescription').value = data.template.description;
                document.getElementById('directionScope').value = data.template.direction_scope;
                document.getElementById('sessionScope').value = data.template.session_scope;
                rulesData = data.rules;
                showRuleEditor();
            }
        });
}

document.getElementById('templateBasicForm').addEventListener('submit', function(e) {
    e.preventDefault();
    // Save basic info first (or just store and open rule editor)
    let formData = new FormData(this);
    fetch('ajax/save_template_basic.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            currentTemplateId = data.template_id;
            document.getElementById('templateId').value = data.template_id;
            // Clear rule list and show editor
            showRuleEditor();
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    });
});

function showRuleEditor() {
    // Hide basic modal, show rule editor modal
    bootstrap.Modal.getInstance(document.getElementById('templateBasicModal')).hide();
    let ruleModal = new bootstrap.Modal(document.getElementById('ruleEditorModal'));
    ruleModal.show();
    // Load rules if editing
    renderRuleList();
}

function backToBasic() {
    bootstrap.Modal.getInstance(document.getElementById('ruleEditorModal')).hide();
    new bootstrap.Modal(document.getElementById('templateBasicModal')).show();
}

function renderRuleList() {
    let container = document.getElementById('ruleList');
    container.innerHTML = '';
    if (rulesData.length === 0) {
        // Add one empty rule group by default
        addRuleGroup();
    } else {
        rulesData.forEach(rule => {
            // Create group element and populate
            let groupEl = createRuleGroupElement(rule);
            container.appendChild(groupEl);
        });
    }
}

function createRuleGroupElement(rule = null) {
    let template = document.getElementById('ruleGroupTemplate').content.cloneNode(true);
    let groupDiv = template.querySelector('.rule-group');
    
    if (rule) {
        groupDiv.querySelector('.rule-type').value = rule.rule_type;
        groupDiv.querySelector('.group-type').value = rule.group_type;
        groupDiv.querySelector('.required-check').checked = rule.required == 1;
        groupDiv.querySelector('.rule-label-bullish').value = rule.label_bullish || '';
        groupDiv.querySelector('.rule-label-bearish').value = rule.label_bearish || '';
        groupDiv.querySelector('.rule-label-fallback').value = rule.label;
        
        // Add child rules if any
        if (rule.children && rule.children.length) {
            let childContainer = groupDiv.querySelector('.child-rules');
            rule.children.forEach(child => {
                let childEl = createChildRuleElement(child);
                childContainer.appendChild(childEl);
            });
        }
    }
    
    // Show/hide add child button based on group type
    let groupSelect = groupDiv.querySelector('.group-type');
    let addChildBtn = groupDiv.querySelector('.add-child-rule');
    groupSelect.addEventListener('change', function() {
        if (this.value === 'either_or') {
            addChildBtn.style.display = 'inline-block';
        } else {
            addChildBtn.style.display = 'none';
            // Clear child rules
            groupDiv.querySelector('.child-rules').innerHTML = '';
        }
    });
    if (groupSelect.value === 'either_or') {
        addChildBtn.style.display = 'inline-block';
    }
    
    return groupDiv;
}

function createChildRuleElement(child = null) {
    let template = document.getElementById('childRuleTemplate').content.cloneNode(true);
    let childDiv = template.querySelector('.child-rule');
    if (child) {
        childDiv.querySelector('.child-label').value = child.label;
    }
    return childDiv;
}

function addRuleGroup() {
    let container = document.getElementById('ruleList');
    let groupEl = createRuleGroupElement();
    container.appendChild(groupEl);
}

function removeRuleGroup(btn) {
    btn.closest('.rule-group').remove();
}

function addChildRule(btn) {
    let container = btn.closest('.rule-group').querySelector('.child-rules');
    let childEl = createChildRuleElement();
    container.appendChild(childEl);
}

function removeChildRule(btn) {
    btn.closest('.child-rule').remove();
}

function saveTemplate() {
    // Collect rules from UI
    let groups = [];
    document.querySelectorAll('#ruleList .rule-group').forEach(group => {
        let ruleType = group.querySelector('.rule-type').value;
        let groupType = group.querySelector('.group-type').value;
        let required = group.querySelector('.required-check').checked ? 1 : 0;
        let labelBullish = group.querySelector('.rule-label-bullish').value;
        let labelBearish = group.querySelector('.rule-label-bearish').value;
        let labelFallback = group.querySelector('.rule-label-fallback').value;
        
        if (!labelFallback) {
            Swal.fire('Error', 'Default label is required for each rule group', 'warning');
            throw 'Validation failed';
        }
        
        let children = [];
        group.querySelectorAll('.child-rules .child-rule').forEach(child => {
            let childLabel = child.querySelector('.child-label').value;
            if (childLabel) {
                children.push({ label: childLabel });
            }
        });
        
        groups.push({
            rule_type: ruleType,
            group_type: groupType,
            required: required,
            label_bullish: labelBullish,
            label_bearish: labelBearish,
            label: labelFallback,
            children: children
        });
    });
    
    let data = {
        template_id: currentTemplateId,
        name: document.getElementById('templateName').value,
        description: document.getElementById('templateDescription').value,
        direction_scope: document.getElementById('directionScope').value,
        session_scope: document.getElementById('sessionScope').value,
        rules: groups
    };
    
    fetch('ajax/save_template.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            Swal.fire('Success', 'Template saved', 'success').then(() => {
                location.reload();
            });
        } else {
            Swal.fire('Error', res.message, 'error');
        }
    });
}

function deleteTemplate(id, name) {
    Swal.fire({
        title: 'Delete Template?',
        text: `Are you sure you want to delete "${name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Delete',
        background: '#11161f',
        color: 'white'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`ajax/delete_template.php?id=${id}`)
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        location.reload();
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                });
        }
    });
}
</script>

<?php require_once 'footer.php'; ?>