<!-- Template Basic Info Modal -->
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

<!-- Rule Editor Modal -->
<div class="modal fade" id="ruleEditorModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl">
        <div class="modal-content" style="background: #11161f; border: 1px solid #1e293b;">
        <div class="modal-header">
    <h5 class="modal-title" style="color: white;" id="ruleEditorTitle">
        Edit Rules
        <span id="ruleCount" style="color: #3b82f6; margin-left: 10px; font-size: 14px;">0</span>
    </h5>
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
                <button type="button" class="btn btn-success" onclick="saveFullTemplate()">Save Template</button>
            </div>
        </div>
    </div>
</div>

<!-- Manage Templates Modal -->
<div class="modal fade" id="manageTemplatesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="background: #11161f; border: 1px solid #1e293b;">
            <div class="modal-header">
                <h5 class="modal-title" style="color: white;">Manage Templates</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: invert(1);"></button>
            </div>
            <div class="modal-body">
                <div id="templatesListContainer">
                    <!-- Templates will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="openCreateTemplateModal()">Create New Template</button>
            </div>
        </div>
    </div>
</div>

<!-- Templates for rule builder -->
<template id="ruleGroupTemplate">
    <div class="rule-group card mb-3" style="background: #0f172a; border: 1px solid #1e293b;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="card-title" style="color: white;">Rule Group</h6>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRuleGroup(this)"><i class="fas fa-times"></i></button>
            </div>
            <div class="mb-2">
                <input type="text" class="form-control rule-label" placeholder="Rule label..." style="background: #1e293b; color: white;">
            </div>
            <div class="row g-2 mb-2">
                <div class="col-md-4">
                    <select class="form-select rule-type" style="background: #1e293b; color: white;">
                        <option value="HTF">HTF</option>
                        <option value="LTF">LTF</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <select class="form-select rule-direction" style="background: #1e293b; color: white;">
                        <option value="Both">Both</option>
                        <option value="Bullish">Bullish</option>
                        <option value="Bearish">Bearish</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <select class="form-select rule-session" style="background: #1e293b; color: white;">
                        <option value="Both">Both</option>
                        <option value="London">London</option>
                        <option value="New York">New York</option>
                        <option value="Asian">Asian</option>
                    </select>
                </div>
            </div>
            <div class="form-check mb-2">
                <input class="form-check-input required-check" type="checkbox" id="requiredCheck">
                <label class="form-check-label" style="color: #94a3b8;">Required</label>
            </div>
            <div class="child-rules mt-3">
                <!-- For either/or, child rules go here -->
            </div>
            <button type="button" class="btn btn-sm btn-outline-info add-child-rule" onclick="addChildRule(this)">Add Child Rule</button>
        </div>
    </div>
</template>

<template id="childRuleTemplate">
    <div class="child-rule d-flex align-items-center gap-2 mb-2">
        <input type="text" class="form-control form-control-sm child-label" placeholder="Child rule label" style="background: #1e293b; color: white;">
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeChildRule(this)"><i class="fas fa-minus"></i></button>
    </div>
</template>