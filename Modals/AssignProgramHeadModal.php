<?php
// Assign/Change Program Head Modal (generic, used from Course Management)
?>

<div id="assignPHModal" class="modal-overlay" style="display:none;">
    <div class="modal-window" style="max-width:520px;">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-user-tie"></i> Assign / Change Program Head</h3>
            <button class="modal-close" onclick="closeAssignPHModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-content-area">
            <div class="form-group">
                <label>Sector</label>
                <input type="text" id="assignPHSector" class="form-control" readonly>
            </div>
            <div class="form-group">
                <label>Department</label>
                <input type="text" id="assignPHDepartmentName" class="form-control" readonly>
                <input type="hidden" id="assignPHDepartmentId">
            </div>
            <div class="form-group">
                <label for="assignPHUserSearch">Select Staff User</label>
                <input type="text" id="assignPHUserSearch" class="form-control" list="assignPHUserOptions" placeholder="Type name or username to search...">
                <datalist id="assignPHUserOptions"></datalist>
                <small class="form-help">Search pulls from users; the designation will be set to Program Head on assign.</small>
                <input type="hidden" id="assignPHUserId">
            </div>
            <div class="form-group">
                <label class="checkbox-label" style="display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" id="assignPHTransferToggle" checked>
                    <span>Transfer existing Program Head if occupied</span>
                </label>
            </div>
        </div>
        <div class="modal-actions">
            <button class="modal-action-secondary" onclick="closeAssignPHModal()">Cancel</button>
            <button class="modal-action-primary" onclick="submitAssignPH()">Assign</button>
        </div>
    </div>
    <script>
        // Simple user search with suggestions (uses api/users/read.php?search=...)
        (function(){
            const input = document.getElementById('assignPHUserSearch');
            const options = document.getElementById('assignPHUserOptions');
            const userIdField = document.getElementById('assignPHUserId');
            if (!input) return;
            let timer = null;
            input.addEventListener('input', function(){
                userIdField.value = '';
                const q = input.value.trim();
                if (timer) clearTimeout(timer);
                timer = setTimeout(async () => {
                    if (!q) { options.innerHTML = ''; return; }
                    try{
                        const r = await fetch('../../api/users/read.php?limit=10&search=' + encodeURIComponent(q), { credentials:'include' });
                        const data = await r.json();
                        options.innerHTML = '';
                        const arr = data.users || [];
                        arr.forEach(u => {
                            const opt = document.createElement('option');
                            opt.value = `${u.username} — ${u.first_name} ${u.last_name}`;
                            opt.setAttribute('data-user-id', u.user_id);
                            options.appendChild(opt);
                        });
                    }catch(e){ /* ignore */ }
                }, 200);
            });
            input.addEventListener('change', function(){
                const val = input.value;
                const match = Array.from(options.children).find(o => o.value === val);
                userIdField.value = match ? match.getAttribute('data-user-id') : '';
            });
        })();

        window.openAssignPHModal = async function(sectorName, departmentDisplayName, departmentCode){
            const sectorInput = document.getElementById('assignPHSector');
            const deptNameInput = document.getElementById('assignPHDepartmentName');
            const deptIdInput = document.getElementById('assignPHDepartmentId');
            const modal = document.getElementById('assignPHModal');
            if (!sectorInput || !deptNameInput || !deptIdInput || !modal) return;

            // Map card code to DB department name if needed
            const mapNames = {
                'ICT': 'Information & Communication Technology',
                'BSA': 'Business, Arts, & Science',
                'THM': 'Tourism & Hospitality Management',
                'ACADEMIC': 'Academic Track',
                'TVL': 'Technological-Vocational Livelihood',
                'HOME_ECON': 'Home Economics',
                'GENERAL_EDUCATION': 'General Education'
            };
            const dbDeptName = mapNames[departmentCode] || departmentDisplayName;

            sectorInput.value = sectorName;
            deptNameInput.value = dbDeptName;
            deptIdInput.value = '';

            // Resolve department_id via API by sector + name
            try{
                const url = `../../api/departments/list.php?sector=${encodeURIComponent(sectorName)}&q=${encodeURIComponent(dbDeptName)}&limit=50`;
                const r = await fetch(url, { credentials:'include' });
                const data = await r.json();
                if (data && data.success && (data.departments||[]).length > 0) {
                    // choose exact match if present, else first
                    const exact = data.departments.find(d => d.department_name.toLowerCase() === dbDeptName.toLowerCase());
                    const chosen = exact || data.departments[0];
                    deptIdInput.value = chosen.department_id;
                }
            }catch(e){ /* ignore */ }

            modal.style.display = 'flex';
            document.body.classList.add('modal-open');
        };
        window.closeAssignPHModal = function(){
            const modal = document.getElementById('assignPHModal');
            if (modal) modal.style.display = 'none';
            document.body.classList.remove('modal-open');
        };
        window.submitAssignPH = async function(){
            const deptId = document.getElementById('assignPHDepartmentId').value;
            const userId = document.getElementById('assignPHUserId').value;
            const transfer = document.getElementById('assignPHTransferToggle').checked;
            if (!deptId) { alert('Department not resolved.'); return; }
            if (!userId) { alert('Please pick a staff user from suggestions.'); return; }
            try{
                const payload = { user_id: parseInt(userId,10), designation: 'Program Head', department_id: parseInt(deptId,10), staff_category: 'Program Head', transfer: !!transfer };
                const r = await fetch('../../api/signatories/assign.php', { method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include', body: JSON.stringify(payload) });
                const data = await r.json();
                if (data && data.success) {
                    if (typeof showToastNotification === 'function') showToastNotification('Program Head assigned successfully', 'success');
                    closeAssignPHModal();
                } else {
                    alert((data && data.message) ? data.message : 'Assignment failed');
                }
            }catch(e){ alert('Assignment failed'); }
        };
    </script>
</div>

