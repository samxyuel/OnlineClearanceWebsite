<?php
// Authentication temporarily disabled for interface development
// TODO: Re-enable authentication when login system is implemented

$adminName = 'Admin User'; // Temporary admin name for testing
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management - goSTI Online Clearance</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/alerts.css">
    <link rel="stylesheet" href="../../assets/css/modals.css">
    <link rel="stylesheet" href="../../assets/css/activity-tracker.css">
    <link rel="stylesheet" href="../../assets/fontawesome/css/all.min.css">
    <style>
        /* Responsive visibility for tab pills vs dropdown (mobile-first) */
        .tab-banner-wrapper .tab-nav { display: none; }
        .tab-banner-wrapper .tab-nav-mobile { display: block; }
        @media (min-width: 769px) {
            .tab-banner-wrapper .tab-nav { display: flex; }
            .tab-banner-wrapper .tab-nav-mobile { display: none; }
        }

        /* Inline styles moved to styles.css for design consistency */
    </style>
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
                            <h2><i class="fas fa-users-cog"></i> Staff Management</h2>
                            <p>Manage administrative personnel and signatories for the clearance system</p>
                        </div>

                        


            <!-- Statistics Dashboard -->
            <div class="stats-dashboard">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                                                        <div class="stat-content">
                                        <h3>26</h3>
                                        <p>Total Staff</p>
                                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon active">
                        <i class="fas fa-user-check"></i>
                    </div>
                                                        <div class="stat-content">
                                        <h3>22</h3>
                                        <p>Active Staff</p>
                                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                                                        <div class="stat-content">
                                        <h3>10</h3>
                                        <p>Essential Staff</p>
                                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-unlock"></i>
                    </div>
                    <div class="stat-content">
                        <h3>16</h3>
                        <p>Optional Staff</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Section -->
            <div class="quick-actions-section">
                <div class="action-buttons">
                    <button class="btn btn-primary add-staff-btn" onclick="openStaffRegistrationModal()">
                        <i class="fas fa-plus"></i> Register Staff
                    </button>
                    <!-- Staff Import disabled - not implemented in bulk import system -->
                    <!-- <button class="btn btn-secondary import-btn" onclick="openStaffImportModal()">
                        <i class="fas fa-file-import"></i> Import Staff
                    </button> -->
                    <button class="btn btn-secondary export-btn" onclick="openStaffExportModal()">
                        <i class="fas fa-file-export"></i> Export Staff
                    </button>
                </div>
            </div>

            <!-- Search and Filters Section -->
            <div class="search-filters-section">
                <div class="search-box">
                    <i class="fas fa-search" style="pointer-events: none;"></i>
                    <input type="text" id="searchInput" placeholder="Search by name or employee ID..." onkeydown="if(event.key==='Enter') applyFilters();">
                </div>
                
                <div class="filter-dropdowns">
                                                        <select id="positionFilter" class="filter-select">
                                        <option value="">All Positions</option>
                                        <option value="Guidance">Guidance</option>
                                        <option value="Disciplinary Officer">Disciplinary Officer</option>
                                        <option value="Clinic">Clinic</option>
                                        <option value="Librarian">Librarian</option>
                                        <option value="Alumni Placement Officer">Alumni Placement Officer</option>
                                        <option value="Student's Affairs Officer">Student's Affairs Officer</option>
                                        <option value="Registrar">Registrar</option>
                                        <option value="Cashier">Cashier</option>
                                        <option value="Program Head">Program Head</option>
                                        <option value="PAMO">PAMO</option>
                                        <option value="MIS/IT">MIS/IT</option>
                                        <option value="Petty Cash Custodian">Petty Cash Custodian</option>
                                        <option value="Building Administrator">Building Administrator</option>
                                        <option value="Accountant">Accountant</option>
                                        <option value="Academic Head">Academic Head</option>
                                        <option value="School Administrator">School Administrator</option>
                                        <option value="HR">HR</option>
                                    </select>
                    
                </div>
                
                <div class="filter-actions">
                    <button class="apply-filters-btn" onclick="applyFilters()">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <button class="clear-filters-btn" onclick="clearFilters()">
                        <i class="fas fa-times"></i> Clear Filters
                    </button>
                </div>
            </div>


            <!-- Tabs (consistent with FacultyManagement) -->
            <div class="tab-banner-wrapper" style="margin-bottom: 12px;">
                <div class="tab-nav" id="staffTabNav">
                    <button class="tab-pill active" data-tab="ph" onclick="switchStaffTab(this)"><i class="fas fa-user-tie"></i> Program Heads</button>
                    <button class="tab-pill" data-tab="sa" onclick="switchStaffTab(this)"><i class="fas fa-user-shield"></i> School Administrator</button>
                    <button class="tab-pill" data-tab="regular" onclick="switchStaffTab(this)"><i class="fas fa-users"></i> Regular Staff</button>
                </div>
                <div class="tab-nav-mobile" id="staffTabSelectWrapper">
                    <select id="staffTabSelect" class="tab-select" onchange="handleStaffTabSelectChange(this)">
                        <option value="ph" selected>Program Heads</option>
                        <option value="sa">School Administrator</option>
                        <option value="regular">Regular Staff</option>
                    </select>
                </div>
            </div>

            <!-- Staff Cards Container -->
            <div class="staff-cards-container">
                <div class="staff-section">
                    <h3><i class="fas fa-user-tie"></i> Program Heads</h3>
                    <div class="staff-cards" id="phStaffCards">
                        <!-- Program Head cards will be populated here -->
                    </div>
                </div>

                <div class="staff-section">
                    <h3><i class="fas fa-user-shield"></i> School Administrator</h3>
                    <div class="staff-cards" id="saStaffCards">
                        <!-- School Administrator card(s) will be populated here -->
                    </div>
                </div>

                <div class="staff-section">
                    <h3><i class="fas fa-users"></i> Regular Staff</h3>
                    <div class="staff-cards" id="regularStaffCards">
                        <!-- Regular staff cards will be populated here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div class="pagination-container">
            <div class="pagination-info">
                <span>Showing <span id="startEntry">1</span>-<span id="endEntry">8</span> of <span id="totalEntries">26</span> entries</span>
            </div>
            <div class="pagination-controls">
                <button class="pagination-btn" onclick="previousPage()" id="prevBtn">
                    <i class="fas fa-chevron-left"></i> Previous
                </button>
                <div class="page-numbers" id="pageNumbers">
                    <button class="page-number active">1</button>
                    <button class="page-number">2</button>
                    <button class="page-number">3</button>
                </div>
                <button class="pagination-btn" onclick="nextPage()" id="nextBtn">
                    Next <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
    
    <!-- RIGHT SIDE: Activity Tracker -->
    <div class="dashboard-sidebar">
        <?php include '../../includes/components/activity-tracker.php'; ?>
    </div>
</div>
</div>
</main>

    <!-- Include Modals -->
    <?php 
    include '../../Modals/StaffRegistryModal.php';
    include '../../Modals/EditStaffModal.php';
    // Staff Import disabled - not implemented in bulk import system
    // include '../../Modals/StaffImportModal.php';
    include '../../Modals/StaffExportModal.php';
    include '../../Modals/GeneratedCredentialsModal.php';
    ?>

    <!-- Include Alert System -->
    <?php include '../../includes/components/alerts.php'; ?>

    <script src="../../assets/js/alerts.js"></script>
    
    <!-- Include Activity Tracker JavaScript -->
    <script src="../../assets/js/activity-tracker.js"></script>
    
    <script>
        // Live staff data – start empty; cards appear only after real registration or fetch
        const staffData = [];

        let currentPage = 1;
        const cardsPerPage = 8;
        let filteredData = [...staffData];


        // Initialize the page
        document.addEventListener('DOMContentLoaded', async function() {
            try { await loadStaffFromApi(); } catch (e) { console.warn('loadStaffFromApi failed', e); }
            renderStaffCards();
            updatePagination();
        });

        async function loadStaffFromApi(){
            const res = await fetch('../../api/staff/list.php?limit=500', { credentials: 'include' });
            const data = await res.json();
            if (!res.ok || !data || data.success !== true) { throw new Error(data && data.message || 'Failed to load staff'); }
            const rows = data.staff || [];
            const map = new Map();
            rows.forEach(r => {
                const emp = r.employee_number || r.username;
                if (!emp) return;
                const key = String(emp);
                const fullName = [r.first_name, r.last_name].filter(Boolean).join(' ');
                if (!map.has(key)) {
                    map.set(key, {
                        id: key,
                        user_id: r.user_id, // Add user_id to the staff object
                        name: fullName || '—',
                        position: r.designation_name || '',
                        staff_category: r.staff_category || '',
                        department: '', // Legacy field for compatibility
                        departments: r.departments || [], // New structured departments array
                        sectors: r.sectors || [], // New sectors array
                        email: r.email || '',
                        contact: r.contact_number || '',
                        status: r.is_active ? 'active' : 'inactive',
                        employment_status: r.employment_status || '',
                        is_also_faculty: r.is_also_faculty || false,
                        faculty_employment_status: r.faculty_employment_status || ''
                    });
                }
            });
            // Reset and load
            staffData.length = 0;
            map.forEach(v => staffData.push(v));
            filteredData.length = 0;
            Array.prototype.push.apply(filteredData, staffData);
            currentPage = 1;
        }

        // Listen for successful staff registration to add a card live
        document.addEventListener('staff-added', function(e){
            const d = e.detail || {};
            const fullName = (d.name && d.name.trim().length) ? d.name : [d.first_name, d.middle_name, d.last_name].filter(Boolean).join(' ').replace(/\s+/g,' ').trim();
            const newStaff = {
                id: d.employee_id || d.employeeId || '',
                name: fullName || 'New Staff',
                position: d.designation || d.position || '',
                department: Array.isArray(d.departments) && d.departments.length ? d.departments.join(', ') : ''
            };
            if (!newStaff.id) return;
            staffData.push(newStaff);
            filteredData = [...staffData];
            currentPage = 1;
            renderStaffCards();
            updatePagination();
            showToastNotification('Staff registered. Card added.', 'success');
        });

        // Render staff cards
        function renderStaffCards() {
            const phContainer = document.getElementById('phStaffCards');
            const saContainer = document.getElementById('saStaffCards');
            const regularContainer = document.getElementById('regularStaffCards');
            
            phContainer.innerHTML = '';
            saContainer.innerHTML = '';
            regularContainer.innerHTML = '';

            const tabData = getTabFilteredData();
            const startIndex = (currentPage - 1) * cardsPerPage;
            const endIndex = startIndex + cardsPerPage;
            const pageData = tabData.slice(startIndex, endIndex);

            const currentTab = window.currentStaffTab || 'ph';

            pageData.forEach(staff => {
                const card = createStaffCard(staff);
                const positionLower = (staff.position || '').toLowerCase();
                if (currentTab === 'ph' && positionLower === 'program head') {
                    phContainer.appendChild(card);
                } else if (currentTab === 'sa' && positionLower === 'school administrator') {
                    saContainer.appendChild(card);
                } else if (currentTab === 'regular' && positionLower !== 'program head' && positionLower !== 'school administrator') {
                    regularContainer.appendChild(card);
                }
            });

            updatePaginationInfo();
            toggleStaffSectionsVisibility();
        }

        function getTabFilteredData(){
            const tab = window.currentStaffTab || 'ph';
            return filteredData.filter(staff => {
                const pos = (staff.position || '').toLowerCase();
                if (tab === 'ph') return pos === 'program head';
                if (tab === 'sa') return pos === 'school administrator';
                return pos !== 'program head' && pos !== 'school administrator';
            });
        }

        // Create staff card
        function createStaffCard(staff) {
            const card = document.createElement('div');
            card.className = 'staff-card';
            card.setAttribute('data-staff-id', staff.id);
            
            const positionLower = (staff.position || '').toLowerCase();
            const isSpecial = (positionLower === 'program head' || positionLower === 'school administrator');
            const headerIcon = isSpecial ? '<i class="fas fa-user-shield"></i>' : '<i class="fas fa-id-badge"></i>';
            const headerClass = isSpecial ? 'special' : 'regular';
            
            // Create sector badge for Program Heads
            let sectorBadge = '';
            if (positionLower === 'program head' && staff.sectors && staff.sectors.length > 0) {
                const sectorNames = staff.sectors.map(s => s.name).join(', ');
                sectorBadge = `<div class="sector-badge"><i class="fas fa-building"></i> ${sectorNames}</div>`;
            }
            
            card.innerHTML = `
                <div class="staff-card-header ${headerClass}">
                    <span class="status-indicator">${headerIcon}</span>
                    <span class="employee-id">${staff.id}</span>
                </div>
                <div class="staff-card-body">
                    <h4 class="staff-name">${staff.name}</h4>
                    <p class="staff-position">${staff.position}</p>
                    ${sectorBadge}
                    <p class="staff-department">${staff.department || ''}</p>
                </div>
                <div class="staff-card-actions">
                    <button class="btn btn-sm btn-outline-primary" onclick="openEditStaffModal('${staff.id}')">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteStaff('${staff.id}')">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            `;
            
            // Add department chips for Program Heads
            if (positionLower === 'program head') {
                const chips = document.createElement('div');
                chips.className = 'ph-dept-chips';
                chips.style.marginTop = '8px';
                
                if (staff.departments && staff.departments.length > 0) {
                    // Use new structured departments array
                    const deptChips = staff.departments.map(dept => {
                        const primaryClass = dept.is_primary ? 'primary' : '';
                        return `<span class="chip ${primaryClass}" title="${dept.is_primary ? 'Primary Department' : 'Secondary Department'}">${dept.name}</span>`;
                    }).join(' ');
                    chips.innerHTML = deptChips;
                } else if (staff.department) {
                    // Fallback to legacy department field
                    chips.innerHTML = `<span class="chip">${staff.department}</span>`;
                }
                
                const body = card.querySelector('.staff-card-body');
                if (body && chips.innerHTML) body.appendChild(chips);
            }

            return card;
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            filteredData = staffData.filter(staff => {
                return (
                    staff.name.toLowerCase().includes(searchTerm) ||
                    staff.id.toLowerCase().includes(searchTerm) ||
                    (staff.position||'').toLowerCase().includes(searchTerm)
                );
            });
            currentPage = 1;
            renderStaffCards();
            updatePagination();
        });

        // Filter functionality
        function applyFilters() {
            const positionFilter = document.getElementById('positionFilter').value;
            
            filteredData = staffData.filter(staff => {
                const positionMatch = !positionFilter || staff.position === positionFilter;
                return positionMatch;
            });
            
            currentPage = 1;
            renderStaffCards();
            updatePagination();
        }

        function clearFilters() {
            document.getElementById('positionFilter').value = '';
            document.getElementById('searchInput').value = '';
            filteredData = [...staffData];
            currentPage = 1;
            renderStaffCards();
            updatePagination();
        }

        // Pagination functions
        function updatePagination() {
            const totalPages = Math.ceil(getTabFilteredData().length / cardsPerPage);
            const pageNumbers = document.getElementById('pageNumbers');
            
            pageNumbers.innerHTML = '';
            
            for (let i = 1; i <= totalPages; i++) {
                const pageBtn = document.createElement('button');
                pageBtn.className = `page-number ${i === currentPage ? 'active' : ''}`;
                pageBtn.textContent = i;
                pageBtn.onclick = () => goToPage(i);
                pageNumbers.appendChild(pageBtn);
            }
            
            document.getElementById('prevBtn').disabled = currentPage === 1;
            document.getElementById('nextBtn').disabled = currentPage === totalPages;
        }

        function goToPage(page) {
            currentPage = page;
            renderStaffCards();
            updatePagination();
        }

        function previousPage() {
            if (currentPage > 1) {
                currentPage--;
                renderStaffCards();
                updatePagination();
            }
        }

        function nextPage() {
            const totalPages = Math.ceil(filteredData.length / cardsPerPage);
            if (currentPage < totalPages) {
                currentPage++;
                renderStaffCards();
                updatePagination();
            }
        }

        function updatePaginationInfo() {
            const tabDataLen = getTabFilteredData().length;
            const startEntry = tabDataLen === 0 ? 0 : (currentPage - 1) * cardsPerPage + 1;
            const endEntry = Math.min(currentPage * cardsPerPage, tabDataLen);
            
            document.getElementById('startEntry').textContent = startEntry;
            document.getElementById('endEntry').textContent = endEntry;
            document.getElementById('totalEntries').textContent = tabDataLen;
        }

        // Tabs logic (consistent with FacultyManagement)
        window.currentStaffTab = 'ph';

        function switchStaffTab(btn){
            const newTab = btn.getAttribute('data-tab');
            const currentTab = window.currentStaffTab || 'ph';
            if (newTab === currentTab) return;
            performStaffTabSwitch(btn, newTab);
        }

        function performStaffTabSwitch(btn, newTab){
            document.querySelectorAll('#staffTabNav .tab-pill').forEach(p=>p.classList.remove('active'));
            btn.classList.add('active');
            window.currentStaffTab = newTab;
            currentPage = 1;
            renderStaffCards();
            updatePagination();
            toggleStaffSectionsVisibility();
        }

        function handleStaffTabSelectChange(sel){
            const newTab = sel.value;
            const currentTab = window.currentStaffTab || 'ph';
            if (newTab === currentTab) return;
            document.querySelectorAll('#staffTabNav .tab-pill').forEach(b=>{
                b.classList.toggle('active', b.getAttribute('data-tab') === newTab);
            });
            window.currentStaffTab = newTab;
            currentPage = 1;
            renderStaffCards();
            updatePagination();
            toggleStaffSectionsVisibility();
        }

        function toggleStaffSectionsVisibility(){
            const phSection = document.getElementById('phStaffCards')?.parentElement;
            const saSection = document.getElementById('saStaffCards')?.parentElement;
            const regularSection = document.getElementById('regularStaffCards')?.parentElement;
            const tab = window.currentStaffTab || 'ph';
            if (phSection) phSection.style.display = (tab === 'ph') ? '' : 'none';
            if (saSection) saSection.style.display = (tab === 'sa') ? '' : 'none';
            if (regularSection) regularSection.style.display = (tab === 'regular') ? '' : 'none';
        }

        // Staff actions
        async function deleteStaff(staffId) {
            showConfirmationModal(
                'Delete Staff Member',
                `Are you sure you want to delete staff member ${staffId}? This action cannot be undone.`,
                'Delete',
                'Cancel',
                async () => {
                    try {
                        // First attempt: fail if PH has assignments (to show prompt)
                        let r = await fetch('../../api/signatories/delete_staff.php', {
                            method:'POST',
                            headers:{'Content-Type':'application/json'},
                            credentials:'include',
                            body: JSON.stringify({ employee_id: staffId })
                        });
                        let res = await r.json();
                        if (r.status === 409 && res && Array.isArray(res.departments)) {
                            // Program Head assigned to departments – prompt to unassign then delete
                            const depCount = res.departments.length;
                            showConfirmationModal(
                                'Unassign Program Head',
                                `This Program Head is assigned to ${depCount} department(s). Unassign and proceed with deletion?`,
                                'Unassign and Delete',
                                'Cancel',
                                async () => {
                                    try {
                                        const r2 = await fetch('../../api/signatories/delete_staff.php', {
                                            method:'POST',
                                            headers:{'Content-Type':'application/json'},
                                            credentials:'include',
                                            body: JSON.stringify({ employee_id: staffId, ph_resolution: 'unassign' })
                                        });
                                        const res2 = await r2.json();
                                        if (!r2.ok || !res2.success) {
                                            throw new Error(res2.message || 'Delete failed');
                                        }
                                        // Remove from UI lists
                                        const index = staffData.findIndex(staff => staff.id === staffId);
                                        if (index > -1) staffData.splice(index, 1);
                                        filteredData = filteredData.filter(staff => staff.id !== staffId);
                                        renderStaffCards();
                                        updatePagination();
                                        showToastNotification('Staff member deleted (PH unassigned).', 'success');
                                    } catch (e) {
                                        showToastNotification(e.message || 'Delete failed', 'error');
                                    }
                                },
                                'warning'
                            );
                            return;
                        }
                        if (!r.ok || !res.success) {
                            throw new Error(res.message || 'Delete failed');
                        }
                        // Success – remove from UI lists
                        const index = staffData.findIndex(staff => staff.id === staffId);
                        if (index > -1) staffData.splice(index, 1);
                        filteredData = filteredData.filter(staff => staff.id !== staffId);
                        renderStaffCards();
                        updatePagination();
                        showToastNotification('Staff member deleted successfully!', 'success');
                    } catch (err) {
                        showToastNotification(err.message || 'Delete failed', 'error');
                    }
                },
                'danger'
            );
        }

        // Modal functions
        function openStaffRegistrationModal() {
            try {
                const modal = document.querySelector('.staff-registration-modal-overlay');
                if (!modal) {
                    if (typeof showToastNotification === 'function') {
                        showToastNotification('Staff registration modal not found. Please refresh the page.', 'error');
                    }
                    return;
                }

                // Use window.openModal if available, otherwise fallback
                if (typeof window.openModal === 'function') {
                    window.openModal(modal);
                } else {
                    // Fallback to direct manipulation
                    modal.style.display = 'flex';
                    document.body.classList.add('modal-open');
                    requestAnimationFrame(() => {
                        modal.classList.add('active');
                    });
                }
            } catch (error) {
                if (typeof showToastNotification === 'function') {
                    showToastNotification('Unable to open staff registration modal. Please try again.', 'error');
                }
            }
        }

        async function openEditStaffModal(staffId) {
            try {
                const staff = staffData.find(s => s.id === staffId);
                if (!staff) {
                    if (typeof showToastNotification === 'function') {
                        showToastNotification('Staff member not found.', 'error');
                    }
                    return;
                }

                // Populate edit modal with staff data
                const modal = document.querySelector('.edit-staff-modal-overlay');
                if (!modal) {
                    if (typeof showToastNotification === 'function') {
                        showToastNotification('Edit staff modal not found. Please refresh the page.', 'error');
                    }
                    return;
                }

                // Use window.openModal if available, otherwise fallback
                if (typeof window.openModal === 'function') {
                    window.openModal(modal);
                } else {
                    // Fallback to direct manipulation
                    modal.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                    document.body.classList.add('modal-open');
                    requestAnimationFrame(() => {
                        modal.classList.add('active');
                    });
                }

                if (staff) {
                    // Set form values
                    document.getElementById('editStaffForm').dataset.userId = staff.user_id;
                    document.getElementById('editEmployeeId').value = staff.id;
                    
                    // Parse name into separate fields
                    const nameParts = (staff.name || '').split(' ');
                    const firstName = nameParts[0] || '';
                    const lastName = nameParts[nameParts.length - 1] || '';
                    const middleName = nameParts.slice(1, -1).join(' ') || '';
                    
                    document.getElementById('editFirstName').value = firstName;
                    document.getElementById('editLastName').value = lastName;
                    document.getElementById('editMiddleName').value = middleName;
                    document.getElementById('editStaffEmail').value = staff.email || '';
                    document.getElementById('editStaffContact').value = staff.contact || ''; 
                    // staff.status is account status (active/inactive), the essential/optional dropdown was removed.
                    
                    // Handle position logic - check if it's a standard position
                    const standardPositions = [
                        'Guidance', 'Disciplinary Officer', 'Clinic', 'Librarian',
                        'Alumni Placement Officer', 'Student\'s Affairs Officer', 'Registrar',
                        'Cashier', 'Program Head', 'PAMO', 'MIS/IT', 'Petty Cash Custodian',
                        'Building Administrator', 'Accountant', 'Academic Head', 'School Administrator', 'HR'
                    ];
                    
                    if (standardPositions.includes(staff.position)) {
                        document.getElementById('editStaffPosition').value = staff.position;
                        document.getElementById('editCustomPosition').value = '';
                    } else {
                        document.getElementById('editStaffPosition').value = '';
                        document.getElementById('editCustomPosition').value = staff.position;
                    }
                    
                    // Handle faculty section
                    // A Program Head is always a faculty member.
                    const isProgramHead = (staff.position || '').toLowerCase() === 'program head';
                    const isAlsoFaculty = staff.is_also_faculty || isProgramHead;

                    document.getElementById('editIsAlsoFaculty').checked = isAlsoFaculty;
                    if (isAlsoFaculty) {
                        document.getElementById('editFacultyEmploymentStatus').value = staff.employment_status || '';
                        document.getElementById('editFacultyEmployeeNumber').value = staff.id;
                    }
                    
                    // Trigger form updates
                    if (typeof toggleEditFacultySection === 'function') {
                        toggleEditFacultySection();
                    }
                    if (typeof toggleEditProgramHeadAssignment === 'function') {
                        toggleEditProgramHeadAssignment();
                    }

                    // If it's a Program Head, populate department assignments
                    if (isProgramHead && typeof loadExistingAssignments === 'function') {
                        loadExistingAssignments(staff);
                    }
                    
                    // Dynamically populate the Program Head sector dropdown
                    const sectorSelect = document.getElementById('editProgramHeadCategory');
                    if (sectorSelect) {
                        sectorSelect.innerHTML = '<option value="">Loading sectors...</option>';
                        try {
                            const response = await fetch('../../api/sectors/list.php', { credentials: 'include' });
                            const data = await response.json();
                            if (data.success && data.sectors) {
                                sectorSelect.innerHTML = '<option value="">Select Sector</option>';
                                data.sectors.forEach(sector => {
                                    const option = new Option(sector.sector_name, sector.sector_name);
                                    sectorSelect.add(option);
                                });
                            }
                        } catch (error) {
                            console.error('Failed to load sectors for edit modal:', error);
                            sectorSelect.innerHTML = '<option value="">Error loading sectors</option>';
                        }
                    }

                    modal.style.display = 'flex';
                    document.body.classList.add('modal-open');
                }
            } catch (error) {
                console.error('[StaffManagement] Error opening edit staff modal:', error);
                if (typeof showToastNotification === 'function') {
                    showToastNotification('Unable to open edit staff modal. Please try again.', 'error');
                }
            }
        }

        // Staff Import disabled - not implemented in bulk import system
        // function openStaffImportModal() {
        //     const modal = document.querySelector('.staff-import-modal-overlay');
        //     if (modal) {
        //         modal.style.display = 'flex';
        //         document.body.classList.add('modal-open');
        //     }
        // }

        function openStaffExportModal() {
            try {
                const modal = document.querySelector('.staff-export-modal-overlay');
                if (!modal) {
                    if (typeof showToastNotification === 'function') {
                        showToastNotification('Staff export modal not found. Please refresh the page.', 'error');
                    }
                    return;
                }

                // Use window.openModal if available, otherwise fallback
                if (typeof window.openModal === 'function') {
                    window.openModal(modal);
                } else {
                    // Fallback to direct manipulation
                    modal.style.display = 'flex';
                    document.body.classList.add('modal-open');
                    requestAnimationFrame(() => {
                        modal.classList.add('active');
                    });
                }
            } catch (error) {
                if (typeof showToastNotification === 'function') {
                    showToastNotification('Unable to open staff export modal. Please try again.', 'error');
                }
            }
        }

        // Toggle sidebar
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            const backdrop = document.getElementById('sidebar-backdrop');
            
            // Check if we're on mobile (screen width <= 768px)
            if (window.innerWidth <= 768) {
                // Mobile behavior - use 'active' class
                if (sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                    if (backdrop) backdrop.style.display = 'none';
                } else {
                    sidebar.classList.add('active');
                    if (backdrop) backdrop.style.display = 'block';
                }
            } else {
                // Desktop behavior - use 'collapsed' class
                if (sidebar.classList.contains('collapsed')) {
                    sidebar.classList.remove('collapsed');
                    mainContent.classList.remove('expanded');
                } else {
                    sidebar.classList.add('collapsed');
                    mainContent.classList.add('expanded');
                }
            }
        }
        
        // Initialize Activity Tracker when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof ActivityTracker !== 'undefined' && !window.activityTrackerInstance) {
                window.activityTrackerInstance = new ActivityTracker();
                console.log('Activity Tracker initialized');
            }
        });

    </script>
    
    <!-- Include Audit Functions -->
    <?php include '../../includes/functions/audit_functions.php'; ?>
</body>
</html> 