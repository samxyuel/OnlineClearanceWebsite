<?php
// Add Scope Signatory Modal (extracted from ClearanceManagement.php)
?>

<div class="modal-overlay" id="addScopeModal" style="display:none;">
    <div class="modal-window">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-user-plus"></i> Add Scope Signatory</h3>
            <button class="modal-close" onclick="closeAddScopeModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-content-area">
            <input type="hidden" id="scopeTypeField" value="student">
            <div class="form-group">
                <label for="scopeSearchInput">Search Staff (name or employee number)</label>
                <input type="text" id="scopeSearchInput" class="form-control" placeholder="e.g., LCA123P or Jane" oninput="debouncedScopeSearch()">
            </div>
            <div class="form-group">
                <div id="scopeSearchResults" class="scope-results-list"></div>
            </div>
            <div class="form-group">
                <label>Selected Staff</label>
                <div id="scopeSelectedChips" class="scope-selected-chips"></div>
                <div class="scope-selected-actions">
                    <label class="checkbox-label" style="display:flex;gap:8px;align-items:center;">
                        <input type="checkbox" id="includeProgramHeadCheckbox">
                        <span>Include Program Head as a signatory (dynamic by department)</span>
                    </label>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="clearScopeSelection()">Clear All</button>
                </div>
            </div>
            <div class="form-group">
                <label>All Staff (excluding Program Heads)</label>
                <div id="scopeAllStaffTableWrap" class="scope-all-staff-wrap">
                    <table class="scope-all-staff-table">
                        <thead>
                            <tr>
                                <th class="sel-col">&nbsp;</th>
                                <th class="name-col">Name</th>
                                <th class="emp-col">Employee No.</th>
                                <th class="desig-col">Designation</th>
                            </tr>
                        </thead>
                        <tbody id="scopeAllStaffTable">
                            <tr class="placeholder"><td colspan="4">Loading…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="modal-actions">
            <button class="modal-action-secondary" onclick="closeAddScopeModal()">Cancel</button>
            <button class="modal-action-primary" onclick="submitAddScope()">Add</button>
        </div>
    </div>
    </div>


