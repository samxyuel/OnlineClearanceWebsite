<?php
// Online Clearance Website - Admin Clearance Management
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Demo session data for testing
$_SESSION['user_id'] = 3;
$_SESSION['role_id'] = 1; // Admin role
$_SESSION['first_name'] = 'Admin';
$_SESSION['last_name'] = 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clearance Management - Online Clearance System</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/modals.css">
    <link rel="stylesheet" href="../../assets/css/alerts.css">
    <link rel="stylesheet" href="../../assets/css/activity-tracker.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <?php include '../../includes/components/header.php'; ?>

    <!-- Main Content Area -->
    <main class="dashboard-container">
        <!-- Include Sidebar -->
        <?php include '../../includes/components/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="dashboard-layout">
                <!-- LEFT SIDE: Main Content -->
                <div class="dashboard-main">
                    <div class="content-wrapper">
                        <!-- Page Header -->
                        <div class="page-header">
                            <h2><i class="fas fa-clipboard-check"></i> Clearance Management</h2>
                            <p>Manage clearance periods, signatories, and monitor clearance statistics</p>
                        </div>

                <!-- Mixed Accordion + Card Design -->
                <div class="clearance-management-mixed">
                    <!-- School Years & Terms Card -->
                    <div class="management-card school-years-card">
                        <div class="card-header">
                            <h3><i class="fas fa-calendar-alt"></i> School Years & Terms</h3>
                            <div class="card-actions">
                                <button class="btn btn-sm btn-primary" onclick="showAddSchoolYearModal()">
                                    <i class="fas fa-plus"></i> Add Year
                                </button>
                            </div>
                        </div>
                        <div class="card-content">
                            <!-- School Year Navigation -->
                            <div class="school-year-navigation">
                                <button class="nav-arrow" id="prevYearBtn" onclick="navigateSchoolYear('prev')">
                                    <i class="fa-solid fa-caret-left"></i>
                                </button>
                                <div class="current-year-display">
                                    <span id="currentYearName">2024-2025</span>
                                    <span id="currentYearStatus" class="year-status current">(Current)</span>
                                    <div class="year-actions">
                                        <button class="btn btn-sm btn-outline-primary" onclick="editSchoolYear('2024-2025')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteSchoolYear('2024-2025')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <button class="nav-arrow" id="nextYearBtn" onclick="navigateSchoolYear('next')">
                                    <i class="fa-solid fa-caret-right"></i>
                                </button>
                            </div>
                            
                            <div class="terms-list">
                                <div class="term-item active">
                                    <div class="term-info">
                                        <span class="term-name">Term 1</span>
                                        <span class="term-status active">ACTIVE</span>
                                    </div>
                                    <div class="term-actions">
                                        <button class="btn btn-sm btn-warning" onclick="deactivateTerm('term1')">
                                            <i class="fas fa-pause"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="endTerm('term1')">
                                            <i class="fa-solid fa-clipboard-check"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteTerm('term1')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="term-item inactive">
                                    <div class="term-info">
                                        <span class="term-name">Term 2</span>
                                        <span class="term-status inactive">INACTIVE</span>
                                    </div>
                                    <div class="term-actions">
                                        <button class="btn btn-sm btn-success" onclick="activateTerm('term2')">
                                            <i class="fas fa-play"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="resetTerm('term2')">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Statistics Card (Compact) -->
                    <div class="management-card quick-stats-card">
                        <div class="card-header">
                            <h3><i class="fas fa-chart-bar"></i> Quick Statistics</h3>
                        </div>
                        <div class="card-content">
                            <div class="compact-stats">
                                <div class="stat-line">
                                    <span class="stat-label">Students:</span>
                                    <span class="stat-value">45</span>
                                    <span class="stat-separator">|</span>
                                    <span class="stat-label">Faculty:</span>
                                    <span class="stat-value">12</span>
                                </div>
                                <div class="stat-line">
                                    <span class="stat-label">Applied:</span>
                                    <span class="stat-value">32</span>
                                    <span class="stat-separator">|</span>
                                    <span class="stat-label">Completed:</span>
                                    <span class="stat-value">28</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Student Clearance Signatories Accordion -->
                    <div class="accordion-section">
                        <div class="accordion-header" onclick="toggleAccordion('student-signatories')">
                            <h3><i class="fas fa-user-graduate"></i> Student Clearance Signatories</h3>
                            <span class="accordion-icon">▼</span>
                        </div>
                        <div class="accordion-content" id="student-signatories">
                            <div class="signatory-card">
                                <div class="signatory-list" id="studentSignatoryList">
                                    <div class="signatory-item required-first">
                                        <span class="signatory-name">Cashier</span>
                                        <span class="signatory-requirement">(Required First)</span>
                                    </div>
                                    <div class="signatory-item optional">
                                        <span class="signatory-name">Program Head</span>
                                        <button class="remove-signatory" onclick="removeSignatory('student', 'Program Head')">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div class="signatory-item optional">
                                        <span class="signatory-name">Library</span>
                                        <button class="remove-signatory" onclick="removeSignatory('student', 'Library')">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div class="signatory-item optional">
                                        <span class="signatory-name">Clinic</span>
                                        <button class="remove-signatory" onclick="removeSignatory('student', 'Clinic')">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div class="signatory-item optional">
                                        <span class="signatory-name">Guidance</span>
                                        <button class="remove-signatory" onclick="removeSignatory('student', 'Guidance')">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div class="signatory-item required-last">
                                        <span class="signatory-name">Registrar</span>
                                        <span class="signatory-requirement">(Required Last)</span>
                                    </div>
                                </div>
                                <div class="signatory-actions">
                                    <button class="btn btn-sm btn-primary" onclick="openAddScopeModal('student')">
                                        <i class="fas fa-plus"></i> Add New
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="openSignatorySettingsModal('student')">
                                        <i class="fas fa-cog"></i> Settings
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="clearAllSignatories('student')">
                                        <i class="fas fa-trash"></i> Clear All
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Faculty Clearance Signatories Accordion -->
                    <div class="accordion-section">
                        <div class="accordion-header" onclick="toggleAccordion('faculty-signatories')">
                            <h3><i class="fas fa-chalkboard-teacher"></i> Faculty Clearance Signatories</h3>
                            <span class="accordion-icon">▼</span>
                        </div>
                        <div class="accordion-content" id="faculty-signatories">
                            <div class="signatory-card">
                                <div class="signatory-list" id="facultySignatoryList">
                                    <div class="signatory-item required-first">
                                        <span class="signatory-name">Accountant</span>
                                        <span class="signatory-requirement">(Required First)</span>
                                    </div>
                                    <div class="signatory-item optional">
                                        <span class="signatory-name">Program Head</span>
                                        <button class="remove-signatory" onclick="removeSignatory('faculty', 'Program Head')">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div class="signatory-item required-last">
                                        <span class="signatory-name">Registrar</span>
                                        <span class="signatory-requirement">(Required Last)</span>
                                    </div>
                                </div>
                                <div class="signatory-actions">
                                    <button class="btn btn-sm btn-primary" onclick="openAddScopeModal('faculty')">
                                        <i class="fas fa-plus"></i> Add New
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="openSignatorySettingsModal('faculty')">
                                        <i class="fas fa-cog"></i> Settings
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="clearAllSignatories('faculty')">
                                        <i class="fas fa-trash"></i> Clear All
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Export Button -->
                    <div class="export-section">
                        <button class="btn btn-primary export-btn" onclick="openExportModal()">
                            <i class="fas fa-file-export"></i> Export Clearance Data
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- RIGHT SIDE: Activity Tracker -->
        <div class="dashboard-sidebar">
            <?php include '../../includes/components/activity-tracker.php'; ?>
        </div>
    </div>
    </main>

    <!-- Include Modals -->
    <?php include '../../Modals/EditSchoolYearModal.php'; ?>
    <?php include '../../Modals/ClearanceExportModal.php'; ?>
    <?php include '../../Modals/AddSignatoryModal.php'; ?>
    <?php include '../../Modals/AddSchoolYearModal.php'; ?>

    <!-- Add Scope Signatory Modal (externalized) -->
    <?php include '../../Modals/AddScopeSignatoryModal.php'; ?>

    <!-- Scripts -->
    <script src="../../assets/js/activity-tracker.js"></script>
    
    <!-- Include Audit Functions -->
    <?php include '../../includes/functions/audit_functions.php'; ?>
    
    <script>
        // Clearance Management Functions
        async function fetchJSON(url, options = {}) {
            const res = await fetch(url, { credentials: 'include', ...options });
            const data = await res.json().catch(()=>({}));
            if (!res.ok || data.success === false) { throw new Error(data.message || 'Request failed'); }
            return data;
        }

        async function loadScopeSignatories(type){
            const listEl = document.getElementById(type==='student' ? 'studentSignatoryList' : 'facultySignatoryList');
            if (!listEl) return;
            
            try {
                // Fetch signatories and settings in parallel
                const [signatoriesData, settingsData] = await Promise.all([
                    fetchJSON(`../../api/signatories/list.php?limit=200&clearance_type=${encodeURIComponent(type)}`),
                    fetchJSON(`../../api/signatories/scope_settings.php?clearance_type=${encodeURIComponent(type)}`)
                ]);
                
                const items = signatoriesData.signatories || [];
                const settings = settingsData.settings || {};
                
                if (items.length === 0) {
                    listEl.innerHTML = '<div style="color:#6c757d;padding:6px 0;">No signatories assigned to this scope yet</div>';
                    return;
                }
                
                // Enhanced render with required signatory styling
                const html = items.map(it => {
                    let itemClass = 'signatory-item optional';
                    let requirementText = '';
                    
                    // Check if this signatory is Required First
                    if (settings.required_first_enabled && settings.required_first_designation_id) {
                        const isRequiredFirst = it.designation_id === settings.required_first_designation_id;
                        if (isRequiredFirst) {
                            itemClass = 'signatory-item required-first';
                            requirementText = '<span class="signatory-requirement">(Required First)</span>';
                        }
                    }
                    
                    // Check if this signatory is Required Last
                    if (settings.required_last_enabled && settings.required_last_designation_id) {
                        const isRequiredLast = it.designation_id === settings.required_last_designation_id;
                        if (isRequiredLast) {
                            itemClass = 'signatory-item required-last';
                            requirementText = '<span class="signatory-requirement">(Required Last)</span>';
                        }
                    }
                    
                    return `
                        <div class="${itemClass}">
                            <span class="signatory-name">${it.designation_name} — ${[it.first_name, it.last_name].filter(Boolean).join(' ')}</span>
                            ${requirementText}
                            <button class="remove-signatory" onclick="removeScope('${type}', ${it.user_id}, '${it.designation_name.replace(/'/g, "\'")}')">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    `;
                }).join('');
                
                listEl.innerHTML = html;
                
            } catch (error) {
                console.error('Error loading scope signatories:', error);
                listEl.innerHTML = '<div style="color:#dc3545;padding:6px 0;">Error loading signatories. Please try again.</div>';
            }
        }

        async function removeScope(type, userId, designation){
            try {
                console.log(`🔧 Attempting to remove signatory: ${designation} (User ID: ${userId}) from ${type} scope`);
                
                // First, check if this signatory is currently required
                const settingsData = await fetchJSON(`/OnlineClearanceWebsite/api/signatories/scope_settings.php?clearance_type=${encodeURIComponent(type)}`);
                const settings = settingsData.settings || {};
                
                // Check if trying to remove Required First signatory
                if (settings.required_first_enabled && settings.required_first_designation_id) {
                    const signatoryData = await fetchJSON(`/OnlineClearanceWebsite/api/signatories/list.php?limit=200&clearance_type=${encodeURIComponent(type)}`);
                    const signatory = signatoryData.signatories?.find(s => s.user_id === userId);
                    
                    if (signatory && signatory.designation_id === settings.required_first_designation_id) {
                        showToast('This signatory is currently set as Required First. Please disable this feature in Settings before removing the signatory.', 'warning');
                        return;
                    }
                }
                
                // Check if trying to remove Required Last signatory
                if (settings.required_last_enabled && settings.required_last_designation_id) {
                    const signatoryData = await fetchJSON(`/OnlineClearanceWebsite/api/signatories/list.php?limit=200&clearance_type=${encodeURIComponent(type)}`);
                    const signatory = signatoryData.signatories?.find(s => s.user_id === userId);
                    
                    if (signatory && signatory.designation_id === settings.required_last_designation_id) {
                        showToast('This signatory is currently set as Required Last. Please disable this feature in Settings before removing the signatory.', 'warning');
                        return;
                    }
                }
                
                console.log(`🔧 Proceeding with removal of non-required signatory: ${designation}`);
                
                // If not required, proceed with removal
                const response = await fetchJSON(`/OnlineClearanceWebsite/api/signatories/unassign.php`,{
                    method:'POST', 
                    headers:{'Content-Type':'application/json'}, 
                    credentials:'include',
                    body: JSON.stringify({ user_id:userId, designation:designation, clearance_type:type })
                });
                
                console.log(`🔧 Removal successful, response:`, response);
                showToast('Removed scope signatory', 'success');
                
                // Refresh the signatory list
                await loadScopeSignatories(type);
                
            } catch (error) {
                console.error('Error removing scope signatory:', error);
                showToast('Failed to remove signatory: ' + (error.message || 'Unknown error'), 'error');
            }
        }

        // Clear all signatories for a specific scope
        async function clearAllSignatories(type) {
            try {
                console.log(`🔧 Attempting to clear all signatories from ${type} scope`);
                
                // Check if there are any required signatories
                const settingsData = await fetchJSON(`/OnlineClearanceWebsite/api/signatories/scope_settings.php?clearance_type=${encodeURIComponent(type)}`);
                const settings = settingsData.settings || {};
                
                let warningMessage = '';
                if (settings.required_first_enabled || settings.required_last_enabled) {
                    warningMessage = '\n\nNote: Some signatories are currently set as required. They will also be removed.';
                }
                
                // Show confirmation dialog
                const scopeName = type === 'student' ? 'Student' : 'Faculty';
                const confirmed = confirm(`Are you sure you want to remove ALL signatories from ${scopeName} clearance? This action cannot be undone.${warningMessage}`);
                
                if (!confirmed) {
                    return;
                }
                
                // Fetch current signatories to get their IDs
                const signatoriesData = await fetchJSON(`/OnlineClearanceWebsite/api/signatories/list.php?limit=200&clearance_type=${encodeURIComponent(type)}`);
                const signatories = signatoriesData.signatories || [];
                
                if (signatories.length === 0) {
                    showToast('No signatories to remove', 'info');
                    return;
                }
                
                console.log(`🔧 Found ${signatories.length} signatories to remove`);
                
                // Remove all signatories
                let successCount = 0;
                let errorCount = 0;
                
                for (const signatory of signatories) {
                    try {
                        await fetchJSON(`/OnlineClearanceWebsite/api/signatories/unassign.php`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            credentials: 'include',
                            body: JSON.stringify({
                                user_id: signatory.user_id,
                                designation: signatory.designation_name,
                                clearance_type: type
                            })
                        });
                        successCount++;
                    } catch (error) {
                        console.error(`Error removing signatory ${signatory.designation_name}:`, error);
                        errorCount++;
                    }
                }
                
                // Show results
                if (successCount > 0 && errorCount === 0) {
                    showToast(`Successfully removed all ${successCount} signatories`, 'success');
                } else if (successCount > 0 && errorCount > 0) {
                    showToast(`Removed ${successCount} signatories, ${errorCount} failed`, 'warning');
                } else {
                    showToast('Failed to remove any signatories', 'error');
                }
                
                // Refresh the signatory list
                await loadScopeSignatories(type);
                
            } catch (error) {
                console.error('Error clearing all signatories:', error);
                showToast('Failed to clear signatories: ' + (error.message || 'Unknown error'), 'error');
            }
        }

        let scopeSearchTimer = null;
        window.scopeSelectedIds = new Set();
        window.scopeSelectedLabels = new Map();
        async function openAddScopeModal(type){
            document.getElementById('scopeTypeField').value = type;
            document.getElementById('scopeSearchInput').value = '';
            document.getElementById('scopeSearchResults').innerHTML = '';
            renderScopeSelectedChips();
            // load include PH toggle
            try{
                const data = await fetchJSON(`../../api/signatories/scope_settings.php?clearance_type=${encodeURIComponent(type)}`);
                const on = !!(data.settings && (data.settings.include_program_head==1 || data.settings.include_program_head===true));
                const cb = document.getElementById('includeProgramHeadCheckbox');
                if (cb) cb.checked = on;
            }catch(e){ /* ignore */ }
            const modal = document.getElementById('addScopeModal');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            const addBtn = modal.querySelector('.modal-action-primary');
            if (addBtn){ addBtn.disabled = isPeriodLocked(); }
            // load all staff table (excluding PH)
            try{
                const data = await fetchJSON('../../api/staff/list.php?limit=200&exclude_program_head=1');
                const tb = document.getElementById('scopeAllStaffTable');
                if (tb){
                    const rows = (data.staff||[]).map(s => {
                        const uid = s.user_id;
                        const label = `${(s.first_name||'').trim()} ${(s.last_name||'').trim()} • ${(s.employee_number||s.username||'')}`.trim();
                        const checked = window.scopeSelectedIds.has(uid) ? 'checked' : '';
                        return `
                        <tr data-user-id=\"${uid}\"> 
                            <td style=\"padding:8px 10px;border-top:1px solid #eef2f6;text-align:center;\"><input type=\"checkbox\" ${checked} onchange=\"toggleScopeUser(${uid}, '${label.replace(/'/g, "\'")}')\"></td>
                            <td style=\"padding:8px 10px;border-top:1px solid #eef2f6;\">${[s.first_name||'', s.last_name||''].join(' ').trim()}</td>
                            <td style=\"padding:8px 10px;border-top:1px solid #eef2f6;\">${s.employee_number||s.username||''}</td>
                            <td style=\"padding:8px 10px;border-top:1px solid #eef2f6;\">${s.designation_name||''}</td>
                        </tr>`;
                    }).join('');
                    tb.innerHTML = rows || '<tr><td style="padding:10px 10px;color:#6c757d;" colspan="4">No staff found</td></tr>';
                }
            }catch(e){
                const tb = document.getElementById('scopeAllStaffTable');
                if (tb){ tb.innerHTML = '<tr><td style="padding:10px 10px;color:#dc3545;" colspan="3">Failed to load staff</td></tr>'; }
            }
        }
        function closeAddScopeModal(){
            const modal = document.getElementById('addScopeModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            // clear selection marker
            const sel = document.querySelector('#scopeSearchResults .selected');
            if (sel) sel.classList.remove('selected');
            document.getElementById('scopeSearchResults').dataset.selectedUserId = '';
        }

        function debouncedScopeSearch(){
            if (scopeSearchTimer) clearTimeout(scopeSearchTimer);
            scopeSearchTimer = setTimeout(runScopeSearch, 300);
        }
        async function runScopeSearch(){
            const q = document.getElementById('scopeSearchInput').value.trim();
            const box = document.getElementById('scopeSearchResults');
            if (!q){ box.innerHTML = ''; return; }
            try{
                const data = await fetchJSON(`../../api/staff/list.php?limit=20&exclude_program_head=1&search=${encodeURIComponent(q)}`);
                const users = data.staff || [];
                if (!users.length){ box.innerHTML='<div style="color:#6c757d;">No results</div>'; return; }
                box.innerHTML = users.map(u => {
                    const uid = u.user_id;
                    const emp = (u.employee_number||u.username||'');
                    const label = `${(u.first_name||'').trim()} ${(u.last_name||'').trim()} • ${emp}`.trim();
                    const checked = window.scopeSelectedIds.has(uid) ? 'checked' : '';
                    const desig = u.designation_name || '';
                    return `
                    <div class=\"result-row\" data-user-id=\"${uid}\" style=\"display:grid;grid-template-columns:28px 1fr;gap:10px;padding:8px 10px;border-bottom:1px solid #eef2f6;align-items:center;\">
                        <div style=\"display:flex;justify-content:center;\"><input type=\"checkbox\" ${checked} onchange=\"toggleScopeUser(${uid}, '${label.replace(/'/g, "\'")}')\"></div>
                        <div style=\"display:flex;flex-direction:column;\">
                            <div style=\"display:flex;align-items:center;gap:8px;\">
                                <div style=\"font-weight:600;color:#2f3a4b;\">${(u.first_name||'')} ${(u.last_name||'')}</div>
                                <div style=\"color:#6c757d;\">${emp}</div>
                            </div>
                            <div style=\"color:#708090;font-size:12px;\">${desig}</div>
                        </div>
                    </div>`;
                }).join('');
            }catch(e){ box.innerHTML='<div style="color:#dc3545;">Search failed</div>'; }
        }
        function toggleScopeUser(userId, label){
            if (window.scopeSelectedIds.has(userId)){
                window.scopeSelectedIds.delete(userId);
                window.scopeSelectedLabels.delete(userId);
            } else {
                window.scopeSelectedIds.add(userId);
                window.scopeSelectedLabels.set(userId, label);
            }
            renderScopeSelectedChips();
        }
        function renderScopeSelectedChips(){
            const wrap = document.getElementById('scopeSelectedChips');
            if (!wrap) return;
            const items = Array.from(window.scopeSelectedIds);
            wrap.innerHTML = items.map(id => {
                const text = window.scopeSelectedLabels.get(id) || `User ${id}`;
                return `<span class=\"chip\" style=\"display:inline-flex;align-items:center;gap:6px;background:#eef3f8;border:1px solid #d7dee7;border-radius:16px;padding:4px 10px;\">${text}<button type=\"button\" aria-label=\"remove\" onclick=\"removeScopeSelected(${id})\" style=\"border:none;background:transparent;cursor:pointer;color:#6b7785;\">×</button></span>`;
            }).join('');
        }
        function removeScopeSelected(userId){
            window.scopeSelectedIds.delete(userId);
            window.scopeSelectedLabels.delete(userId);
            renderScopeSelectedChips();
            // also uncheck in results if present
            const el = document.querySelector(`#scopeSearchResults [data-user-id=\"${userId}\"] input[type=checkbox]`);
            if (el) el.checked = false;
        }
        function clearScopeSelection(){
            window.scopeSelectedIds.clear();
            window.scopeSelectedLabels.clear();
            renderScopeSelectedChips();
            document.querySelectorAll('#scopeSearchResults input[type=checkbox]').forEach(cb => cb.checked = false);
        }
        async function submitAddScope(){
            const type = document.getElementById('scopeTypeField').value;
            const includePH = !!document.getElementById('includeProgramHeadCheckbox')?.checked;
            const ids = Array.from(window.scopeSelectedIds);
            if (!ids.length && includePH === undefined){ showToast('Select at least one staff or toggle Program Head','warning'); return; }
            // Save scope setting first
            try{
                await fetchJSON('../../api/signatories/scope_settings.php',{
                    method:'PUT', headers:{'Content-Type':'application/json'}, credentials:'include',
                    body: JSON.stringify({ clearance_type:type, include_program_head: includePH })
                });
            }catch(e){ /* surface but continue adds */ showToast('Saved PH setting with warnings','warning'); }
            // Add staff in parallel (limit fanout)
            let ok = 0, fail = 0;
            for (const uid of ids){
                try{
                    await fetchJSON('../../api/signatories/assign.php',{
                        method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include',
                        body: JSON.stringify({ user_id:uid, clearance_type:type })
                    });
                    ok++;
                }catch(e){ fail++; }
            }
            closeAddScopeModal();
            if (ok && !fail) showToast(`Added ${ok} signator${ok===1?'y':'ies'}`,'success');
            else if (ok && fail) showToast(`Added ${ok}, skipped ${fail}`,'warning');
            else if (!ok && fail) showToast('No signatories added','error');
            loadScopeSignatories(type);
        }

        async function addScope(type, userId, designation){
            try{
                await fetchJSON('../../api/signatories/assign.php',{
                    method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include',
                    body: JSON.stringify({ user_id:userId, designation:designation, clearance_type:type })
                });
                showToast('Scope signatory added', 'success');
                loadScopeSignatories(type);
            }catch(e){ showToast(e.message,'error'); }
        }
        function toggleAccordion(sectionId) {
            const content = document.getElementById(sectionId);
            const header = content.previousElementSibling;
            const icon = header.querySelector('.accordion-icon');
            
            if (content.style.display === 'none' || content.style.display === '') {
                content.style.display = 'block';
                icon.textContent = '▼';
                header.classList.add('active');
            } else {
                content.style.display = 'none';
                icon.textContent = '▶';
                header.classList.remove('active');
            }
        }

        function editSchoolYear(year) {
            showEditSchoolYearModal(year);
        }

        function deleteSchoolYear(year) {
            const currentYear = schoolYears[currentSchoolYearIndex];
            const ayId = currentYear?.academicYearId;
            showConfirmation(
                'Delete School Year',
                `Delete school year ${currentYear?.name || year}? This requires: both terms Ended and no applications.`,
                'Delete',
                'Cancel',
                async () => {
                    try {
                        if (!ayId) { showToast('School Year not loaded', 'error'); return; }
                        await fetchJSON(`${API_BASE}/years.php?id=${encodeURIComponent(ayId)}`, { method: 'DELETE', headers: { 'Content-Type': 'application/json' } });
                        showToast('School year deleted', 'success');
                        try {
                            await loadCurrentYearAndTerms();
                            updateSchoolYearDisplay();
                        } catch (e) {
                            window.location.reload();
                        }
                    } catch (e) {
                        console.error(e);
                        showToast(e.message || 'Failed to delete school year', 'error');
                    }
                },
                'warning'
            );
        }

        function removeSignatory(type, position) {
            showConfirmation(
                'Remove Signatory',
                `Are you sure you want to remove ${position} from ${type} clearance signatories?`,
                'Remove',
                'Cancel',
                () => {
                    // Find and remove the signatory item
                    const signatoryItems = document.querySelectorAll(`.signatory-item.optional[data-position="${position}"]`);
                    let removed = false;
                    
                    signatoryItems.forEach(item => {
                        const section = item.closest('.accordion-content');
                        const isCorrectSection = (type === 'student' && section.id === 'student-signatories') || 
                                               (type === 'faculty' && section.id === 'faculty-signatories');
                        
                        if (isCorrectSection) {
                            item.remove();
                            removed = true;
                        }
                    });
                    
                    if (removed) {
                        showToast(`Removed ${position} from ${type} clearance signatories.`, 'success');
                    } else {
                        showToast(`${position} not found in ${type} clearance signatories.`, 'warning');
                    }
                },
                'warning'
            );
        }

        // School Year Navigation System (backend-driven)
        let currentSchoolYearIndex = 0; // single current year for now
        let schoolYears = [];
        const API_BASE = '../../api/clearance';

        function mapPeriodStatusToTermStatus(periodStatus) {
            if (periodStatus === 'active') return 'active';
            if (periodStatus === 'ended') return 'completed';
            if (periodStatus === 'deactivated') return 'deactivated';
            return 'inactive';
        }

        async function fetchJSON(url, options = {}) {
            const res = await fetch(url, { credentials: 'include', ...options });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || data.success === false) {
                throw new Error(data.message || 'Request failed');
            }
            return data;
        }

        async function loadCurrentYearAndTerms() {
            const ctx = await fetchJSON(`${API_BASE}/context.php`);
            if (!ctx.academic_year) { schoolYears = []; return; }

            const ayId = ctx.academic_year.academic_year_id;
            const term1SemId = ctx.terms.find(t => t.semester_name === '1st')?.semester_id || null;
            const term2SemId = ctx.terms.find(t => t.semester_name === '2nd')?.semester_id || null;

            const periodsResp = await fetchJSON(`${API_BASE}/periods.php`);
            const periods = periodsResp.periods || [];
            function findPeriodForSemester(semId) {
                return periods.find(p => p.academic_year_id === ayId && p.semester_id === semId) || null;
            }
            const p1 = term1SemId ? findPeriodForSemester(term1SemId) : null;
            const p2 = term2SemId ? findPeriodForSemester(term2SemId) : null;

            const yearObj = {
                id: ctx.academic_year.year,
                name: ctx.academic_year.year,
                status: 'current',
                terms: [
                    { id: 'term1', name: 'Term 1', status: mapPeriodStatusToTermStatus(p1?.status), periodId: p1?.period_id || null, semesterId: term1SemId, students: '0/0' },
                    { id: 'term2', name: 'Term 2', status: mapPeriodStatusToTermStatus(p2?.status), periodId: p2?.period_id || null, semesterId: term2SemId, students: '0/0' }
                ],
                canAddSchoolYear: true,
                academicYearId: ayId
            };

            schoolYears = [yearObj];
            currentSchoolYearIndex = 0;
        }
        function isPeriodLocked(){
            const cy = schoolYears[currentSchoolYearIndex];
            if (!cy) return false;
            return (cy.terms||[]).some(t => t.status === 'active' || t.status === 'deactivated');
        }

        function updateLockUI(){
            const locked = isPeriodLocked();
            // Disable Add New buttons in both accordions
            document.querySelectorAll('.signatory-actions .btn').forEach(btn => {
                if (btn && /Add New/i.test(btn.textContent)) {
                    btn.disabled = locked;
                }
            });
            // Disable remove icons
            document.querySelectorAll('.remove-signatory').forEach(btn => {
                btn.disabled = locked;
                btn.style.pointerEvents = locked ? 'none' : 'auto';
                btn.style.opacity = locked ? '0.5' : '1';
            });
            // Insert or remove lock note
            ['student-signatories','faculty-signatories'].forEach(id => {
                const container = document.getElementById(id);
                if (!container) return;
                let note = container.querySelector('.lock-note');
                if (locked) {
                    if (!note) {
                        note = document.createElement('div');
                        note.className = 'lock-note';
                        note.style.color = '#6c757d';
                        note.style.fontSize = '12px';
                        note.style.margin = '6px 0';
                        note.innerText = 'Changes locked during active/paused period';
                        const card = container.querySelector('.signatory-card');
                        if (card) card.insertBefore(note, card.firstChild);
                    }
                } else if (note) {
                    note.remove();
                }
            });
        }

        function navigateSchoolYear(direction) {
            // prev/next disabled for now
            updateSchoolYearDisplay();
            updateNavigationButtons();
        }

        function updateSchoolYearDisplay() {
            const currentYear = schoolYears[currentSchoolYearIndex];

            // Guard: if data not yet loaded
            if (!currentYear) {
                const nameEl = document.getElementById('currentYearName');
                const statusEl = document.getElementById('currentYearStatus');
                const termsList = document.querySelector('.terms-list');
                if (nameEl) nameEl.textContent = 'No current year';
                if (statusEl) {
                    statusEl.textContent = '(None)';
                    statusEl.className = 'year-status';
                }
                if (termsList) {
                    termsList.innerHTML = `
                        <div class="term-item inactive">
                            <div class="term-info">
                                <span class="term-name">No terms</span>
                                <span class="term-status inactive">INACTIVE</span>
                            </div>
                        </div>
                    `;
                }
                return;
            }

            // Update navigation display
            document.getElementById('currentYearName').textContent = currentYear.name;
            document.getElementById('currentYearStatus').textContent = `(${currentYear.status === 'current' ? 'Current' : 'Completed'})`;
            document.getElementById('currentYearStatus').className = `year-status ${currentYear.status}`;

            // Update year actions
            updateYearActions(currentYear);

            // Update terms list
            updateTermsList(currentYear);
            // Update lock UI after status refresh
            try { updateLockUI(); } catch (e) {}
        }

        function updateYearActions(schoolYear) {
            const yearActions = document.querySelector('.year-actions');
            
            if (schoolYear.status === 'current') {
                // Current year - full functionality
                yearActions.innerHTML = `
                    <button class="btn btn-sm btn-outline-primary" onclick="editSchoolYear('${schoolYear.id}')">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteSchoolYear('${schoolYear.id}')">
                        <i class="fas fa-trash"></i>
                    </button>
                `;
            } else {
                // Completed year - read-only
                yearActions.innerHTML = `
                    <button class="btn btn-sm btn-outline-primary" onclick="viewSchoolYear('${schoolYear.id}')">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-success" onclick="exportSchoolYear('${schoolYear.id}')">
                        <i class="fas fa-download"></i>
                    </button>
                `;
            }
        }

        function updateTermsList(schoolYear) {
            const termsList = document.querySelector('.terms-list');
            termsList.innerHTML = '';
            
            schoolYear.terms.forEach(term => {
                const termItem = document.createElement('div');
                termItem.className = `term-item ${term.status}`;
                
                if (schoolYear.status === 'current') {
                    // Current year - full functionality
                    termItem.innerHTML = `
                        <div class="term-info">
                            <span class="term-name">${term.name}</span>
                            <span class="term-status ${term.status}">${term.status.toUpperCase()}</span>
                        </div>
                        <div class="term-actions">
                            ${term.status === 'active' ? `
                                <button class="btn btn-sm btn-warning" onclick="deactivateTerm('${term.id}')">
                                    <i class="fas fa-pause"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="endTerm('${term.id}')">
                                    <i class="fa-solid fa-clipboard-check"></i>
                                </button>
                            ` : term.status === 'deactivated' ? `
                                <button class="btn btn-sm btn-success" onclick="activateTerm('${term.id}')">
                                    <i class="fas fa-play"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="endTerm('${term.id}')">
                                    <i class="fa-solid fa-clipboard-check"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="resetTerm('${term.id}')">
                                    <i class="fas fa-undo"></i>
                                </button>
                            ` : term.status === 'inactive' ? `
                                <button class="btn btn-sm btn-success" onclick="activateTerm('${term.id}')">
                                    <i class="fas fa-play"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" title="End (skip)" onclick="endTerm('${term.id}')">
                                    <i class="fa-solid fa-clipboard-check"></i>
                                </button>
                            ` : ''}
                        </div>
                    `;
                } else {
                    // Completed year - read-only
                    termItem.innerHTML = `
                        <div class="term-info">
                            <span class="term-name">${term.name}</span>
                            <span class="term-status ${term.status}">${term.status.toUpperCase()}</span>
                        </div>
                        <div class="term-actions">
                            <button class="btn btn-sm btn-outline-primary" onclick="viewTerm('${term.id}')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" onclick="exportTerm('${term.id}')">
                                <i class="fas fa-download"></i>
                            </button>
                        </div>
                    `;
                }
                
                termsList.appendChild(termItem);
            });
        }

        function updateNavigationButtons() {
            const prevBtn = document.getElementById('prevYearBtn');
            const nextBtn = document.getElementById('nextYearBtn');
            prevBtn.disabled = true; nextBtn.disabled = true;
        }

        async function activateTerm(termId) {
            const currentYear = schoolYears[currentSchoolYearIndex];
            if (!currentYear) { showToast('Data not loaded yet.', 'warning'); return; }
            const term = currentYear.terms.find(t => t.id === termId);
            if (!term) { showToast('Term not found.', 'error'); return; }

            showConfirmation(
                'Activate Term',
                `Activate ${term.name}? This will start the clearance period.`,
                'Activate',
                'Cancel',
                async () => {
                    try {
                        // Preflight validation before any write
                        const pre = await fetchJSON(`${API_BASE}/preflight.php?academic_year_id=${encodeURIComponent(currentYear.academicYearId)}&semester_id=${encodeURIComponent(term.semesterId)}`);
                        if (!pre.ok) {
                            const issues = (pre.issues || []).map(i => `• ${i.message}`).join('\n');
                            showToast(issues || 'Activation blocked by validation checks.', 'warning');
                            return;
                        }
                        if (term.periodId) {
                            await fetchJSON(`${API_BASE}/periods.php`, { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ period_id: term.periodId, action: 'activate' }) });
                        } else {
                            const today = new Date().toISOString().slice(0,10);
                            await fetchJSON(`${API_BASE}/periods.php`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ academic_year_id: currentYear.academicYearId, semester_id: term.semesterId, start_date: today, is_active: true }) });
                        }
                        showToast(`${term.name} activated successfully!`, 'success');
                        await loadCurrentYearAndTerms();
                        updateSchoolYearDisplay();
                    } catch (e) { console.error(e); showToast(e.message || 'Failed to activate term', 'error'); }
                },
                'success'
            );
        }

        async function deactivateTerm(termId) {
            const currentYear = schoolYears[currentSchoolYearIndex];
            if (!currentYear) { showToast('Data not loaded yet.', 'warning'); return; }
            const term = currentYear.terms.find(t => t.id === termId);
            if (!term) { showToast('Term not found.', 'error'); return; }

            showConfirmation(
                'Deactivate Term',
                `Deactivate ${term.name}? This will pause the clearance period.`,
                'Deactivate',
                'Cancel',
                async () => {
                    try {
                        if (!term.periodId) { showToast('No period exists for this term.', 'warning'); return; }
                        await fetchJSON(`${API_BASE}/periods.php`, { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ period_id: term.periodId, action: 'deactivate' }) });
                        showToast(`${term.name} deactivated successfully!`, 'warning');
                        await loadCurrentYearAndTerms();
                        updateSchoolYearDisplay();
                    } catch (e) { console.error(e); showToast(e.message || 'Failed to deactivate term', 'error'); }
                },
                'warning'
            );
        }

        async function endTerm(termId) {
            const currentYear = schoolYears[currentSchoolYearIndex];
            if (!currentYear) { showToast('Data not loaded yet.', 'warning'); return; }
            const term = currentYear.terms.find(t => t.id === termId);
            if (!term) { showToast('Term not found.', 'error'); return; }

            showConfirmation(
                'End Term',
                `End ${term.name}? This will conclude the clearance period permanently.`,
                'End Term',
                'Cancel',
                async () => {
                    try {
                        if (!term.periodId) {
                            // End (skip): create an inactive period, then end it
                            const today = new Date().toISOString().slice(0,10);
                            const createRes = await fetchJSON(`${API_BASE}/periods.php`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ academic_year_id: currentYear.academicYearId, semester_id: term.semesterId, start_date: today, is_active: false }) });
                            const newPid = createRes.period_id;
                            await fetchJSON(`${API_BASE}/periods.php`, { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ period_id: newPid, action: 'end' }) });
                        } else {
                            await fetchJSON(`${API_BASE}/periods.php`, { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ period_id: term.periodId, action: 'end' }) });
                        }
                        showToast(`${term.name} ended successfully!`, 'success');
                        await loadCurrentYearAndTerms();
                        updateSchoolYearDisplay();
                    } catch (e) { console.error(e); showToast(e.message || 'Failed to end term', 'error'); }
                },
                'danger'
            );
        }

        async function resetTerm(termId) {
            const currentYear = schoolYears[currentSchoolYearIndex];
            if (!currentYear) { showToast('Data not loaded yet.', 'warning'); return; }
            const term = currentYear.terms.find(t => t.id === termId);
            if (!term) { showToast('Term not found.', 'error'); return; }
            if (term.status === 'active') { showToast('Cannot reset an active term. Deactivate it first.', 'warning'); return; }
            if (term.status === 'completed') { showToast('Cannot reset an ended term.', 'info'); return; }
            if (term.status === 'inactive') { showToast('Cannot reset. No period exists yet for this term.', 'info'); return; }
            if (term.status !== 'deactivated') { showToast('Reset is allowed only for deactivated terms.', 'warning'); return; }

            const dataSummary = 'This will revert all clearance progress to Unapplied for this paused term.';

            showConfirmation(
                'Reset Term',
                `Reset ${term.name}? ${dataSummary}`,
                'Reset Term',
                'Cancel',
                async () => {
                    try {
                        if (!term.periodId) { showToast('No period exists for this term.', 'warning'); return; }
                        await fetchJSON(`${API_BASE}/reset_by_period.php`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ period_id: term.periodId }) });
                        showToast(`${term.name} reset successfully!`, 'success');
                        await loadCurrentYearAndTerms();
                        updateSchoolYearDisplay();
                    } catch (e) { console.error(e); showToast(e.message || 'Failed to reset term', 'error'); }
                },
                'warning'
            );
        }

        function deleteTerm(termId) {
            showConfirmation(
                'Delete Term',
                `Are you sure you want to delete ${termId}? This action cannot be undone.`,
                'Delete',
                'Cancel',
                () => {
                    showToast(`${termId} deleted successfully!`, 'success');
                    // Implementation for deleting term
                },
                'danger'
            );
        }

        function viewTerm(termId) {
            showToast(`Viewing ${termId} data...`, 'info');
        }

        function exportTerm(termId) {
            showToast(`Exporting ${termId} data...`, 'info');
        }

        function viewSchoolYear(yearId) {
            showToast(`Viewing ${yearId} data...`, 'info');
        }

        function exportSchoolYear(yearId) {
            showToast(`Exporting ${yearId} data...`, 'info');
        }

        function addSignatory(type) {
            showAddSignatoryModal(type);
        }

        function openSignatorySettingsModal(type) {
            console.log('Opening signatory settings modal for:', type);
            
            // Check if the modal function exists
            if (typeof window.openSignatorySettingsModal === 'function') {
                console.log('Using window function from modal');
                window.openSignatorySettingsModal(type);
            } else {
                console.error('Modal function not found. Please ensure SignatorySettingsModal.php is properly loaded.');
            }
        }

        function openExportModal() {
            openClearanceExportModal();
        }

        // Initialize accordions as expanded by default
        document.addEventListener('DOMContentLoaded', async function() {
            // All accordions start expanded
            const accordions = document.querySelectorAll('.accordion-content');
            accordions.forEach(accordion => {
                accordion.style.display = 'block';
            });
            
            // Initialize Activity Tracker (only if not already initialized)
            if (typeof ActivityTracker !== 'undefined' && !window.activityTrackerInstance) {
                window.activityTrackerInstance = new ActivityTracker();
                console.log('Activity Tracker initialized');
            }
            
            // Test if modal HTML is loaded
            const modal = document.querySelector('.signatory-settings-modal-overlay');
            if (modal) {
                console.log('✅ Modal HTML is loaded in DOM');
            } else {
                console.error('❌ Modal HTML is NOT loaded in DOM');
            }
            
            // Load data then render UI
            try {
                await loadCurrentYearAndTerms();
            } catch (e) {
                console.error('Failed to load academic context:', e);
            }
            updateSchoolYearDisplay();
            updateNavigationButtons();
            try { updateLockUI(); } catch (e) {}
            
            // Ensure sidebar backdrop functionality
            const backdrop = document.getElementById('sidebar-backdrop');
            if (backdrop) {
                backdrop.addEventListener('click', function() {
                    const sidebar = document.querySelector('.sidebar');
                    if (sidebar) {
                        sidebar.classList.remove('active');
                        this.style.display = 'none';
                    }
                });
            }
            
            // Close sidebar on window resize
            window.addEventListener('resize', function() {
                const sidebar = document.querySelector('.sidebar');
                const backdrop = document.getElementById('sidebar-backdrop');
                
                if (window.innerWidth > 768) {
                    if (sidebar) sidebar.classList.remove('active');
                    if (backdrop) backdrop.style.display = 'none';
                }
            });
        });

        // Sidebar toggle function
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            const mainContent = document.querySelector('.main-content');
            
            if (sidebar) {
                if (window.innerWidth <= 768) {
                    // Mobile: toggle sidebar overlay with backdrop
                    sidebar.classList.toggle('active');
                    
                    // Show/hide backdrop only on mobile
                    if (backdrop) {
                        if (sidebar.classList.contains('active')) {
                            backdrop.style.display = 'block';
                        } else {
                            backdrop.style.display = 'none';
                        }
                    }
                } else {
                    // Desktop: toggle sidebar collapsed state without backdrop
                    sidebar.classList.toggle('collapsed');
                    if (mainContent) {
                        mainContent.classList.toggle('full-width');
                    }
                    
                    // Ensure backdrop is hidden on desktop
                    if (backdrop) {
                        backdrop.style.display = 'none';
                    }
                }
            }
        }

        // Initial load of scope lists
        document.addEventListener('DOMContentLoaded', function(){
            loadScopeSignatories('student').catch(()=>{});
            loadScopeSignatories('faculty').catch(()=>{});
        });
    </script>

    <!-- Include Signatory Settings Modal -->
    <?php include '../../Modals/SignatorySettingsModal.php'; ?>
    
    <!-- Include Alerts Component -->
    <?php include '../../includes/components/alerts.php'; ?>
    
    <!-- Include Alerts JavaScript -->
    <script src="../../assets/js/alerts.js"></script>
</body>
</html> 