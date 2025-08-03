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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Top Bar -->
    <header class="navbar">
        <div class="container">
            <div class="header-content">
                <div class="header-left">
                    <button class="mobile-menu-toggle" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="logo">
                        <h1>goSTI</h1>
                        <!--<p>Online Clearance System</p>-->
                    </div>
                </div>
                <div class="user-info">
                    <span class="user-name">Admin User</span>
                    <div class="user-dropdown">
                        <button class="dropdown-toggle">▼</button>
                        <div class="dropdown-menu">
                            <a href="../../pages/shared/profile.php">Profile</a>
                            <a href="../../pages/shared/settings.php">Settings</a>
                            <a href="../../pages/auth/logout.php">Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="dashboard-container">
        <!-- Include Sidebar -->
        <?php include '../../includes/components/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
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
                                <div class="signatory-list">
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
                                    <button class="btn btn-sm btn-primary" onclick="addSignatory('student')">
                                        <i class="fas fa-plus"></i> Add New
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="openSignatorySettingsModal('student')">
                                        <i class="fas fa-cog"></i> Settings
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
                                <div class="signatory-list">
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
                                    <button class="btn btn-sm btn-primary" onclick="addSignatory('faculty')">
                                        <i class="fas fa-plus"></i> Add New
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="openSignatorySettingsModal('faculty')">
                                        <i class="fas fa-cog"></i> Settings
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
    </main>

    <!-- Include Modals -->
    <?php include '../../Modals/EditSchoolYearModal.php'; ?>
    <?php include '../../Modals/ClearanceExportModal.php'; ?>
    <?php include '../../Modals/AddSignatoryModal.php'; ?>
    <?php include '../../Modals/AddSchoolYearModal.php'; ?>

    <!-- Scripts -->
    <script src="../../assets/js/alerts.js"></script>
    <script>
        // Clearance Management Functions
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
            showConfirmation(
                'Delete School Year',
                `Are you sure you want to delete school year ${year}? This will reset all clearance data for this year.`,
                'Delete',
                'Cancel',
                () => console.log('Delete School Year clicked'),
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

        // School Year Navigation System
        let currentSchoolYearIndex = 0; // Start with current year (index 0)
        const schoolYears = [
            {
                id: '2024-2025',
                name: '2024-2025',
                status: 'current',
                terms: [
                    { id: 'term1', name: 'Term 1', status: 'active', students: '45/50' },
                    { id: 'term2', name: 'Term 2', status: 'inactive', students: '0/50' }
                ],
                canAddSchoolYear: true
            },
            {
                id: '2023-2024',
                name: '2023-2024',
                status: 'completed',
                terms: [
                    { id: 'term1', name: 'Term 1', status: 'completed', students: '50/50' },
                    { id: 'term2', name: 'Term 2', status: 'completed', students: '48/50' },
                    { id: 'term3', name: 'Term 3', status: 'completed', students: '45/50' }
                ],
                canAddSchoolYear: false
            }
        ];

        function navigateSchoolYear(direction) {
            if (direction === 'prev' && currentSchoolYearIndex < schoolYears.length - 1) {
                // Go to older year (higher index in array)
                currentSchoolYearIndex++;
            } else if (direction === 'next' && currentSchoolYearIndex > 0) {
                // Go to newer year (lower index in array)
                currentSchoolYearIndex--;
            }
            
            updateSchoolYearDisplay();
            updateNavigationButtons();
        }

        function updateSchoolYearDisplay() {
            const currentYear = schoolYears[currentSchoolYearIndex];
            
            // Update navigation display
            document.getElementById('currentYearName').textContent = currentYear.name;
            document.getElementById('currentYearStatus').textContent = `(${currentYear.status === 'current' ? 'Current' : 'Completed'})`;
            document.getElementById('currentYearStatus').className = `year-status ${currentYear.status}`;
            
            // Update year actions
            updateYearActions(currentYear);
            
            // Update terms list
            updateTermsList(currentYear);
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
                            ` : term.status === 'inactive' ? `
                                <button class="btn btn-sm btn-success" onclick="activateTerm('${term.id}')">
                                    <i class="fas fa-play"></i>
                                </button>
                            ` : ''}
                            <button class="btn btn-sm btn-outline-danger" onclick="resetTerm('${term.id}')">
                                <i class="fas fa-undo"></i>
                            </button>
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
            
            // Left arrow (prev) - goes to older years (higher index)
            // Disabled when at the oldest year (highest index)
            prevBtn.disabled = currentSchoolYearIndex === schoolYears.length - 1;
            
            // Right arrow (next) - goes to newer years (lower index)
            // Disabled when at the newest year (lowest index)
            nextBtn.disabled = currentSchoolYearIndex === 0;
        }

        function activateTerm(termId) {
            const currentYear = schoolYears[currentSchoolYearIndex];
            const term = currentYear.terms.find(t => t.id === termId);
            
            if (!term) {
                showToast('Term not found.', 'error');
                return;
            }
            
            showConfirmation(
                'Activate Term',
                `Activate ${termId}? This will start the clearance period.`,
                'Activate',
                'Cancel',
                () => {
                    term.status = 'active';
                    showToast(`${termId} activated successfully!`, 'success');
                    updateTermsList(currentYear);
                },
                'success'
            );
        }

        function deactivateTerm(termId) {
            const currentYear = schoolYears[currentSchoolYearIndex];
            const term = currentYear.terms.find(t => t.id === termId);
            
            if (!term) {
                showToast('Term not found.', 'error');
                return;
            }
            
            showConfirmation(
                'Deactivate Term',
                `Deactivate ${termId}? This will pause the clearance period.`,
                'Deactivate',
                'Cancel',
                () => {
                    term.status = 'inactive';
                    showToast(`${termId} deactivated successfully!`, 'warning');
                    updateTermsList(currentYear);
                },
                'warning'
            );
        }

        function endTerm(termId) {
            const currentYear = schoolYears[currentSchoolYearIndex];
            const term = currentYear.terms.find(t => t.id === termId);
            
            if (!term) {
                showToast('Term not found.', 'error');
                return;
            }
            
            showConfirmation(
                'End Term',
                `End ${termId}? This will conclude the clearance period permanently.`,
                'End Term',
                'Cancel',
                () => {
                    term.status = 'completed';
                    showToast(`${termId} ended successfully!`, 'success');
                    updateTermsList(currentYear);
                },
                'danger'
            );
        }

        function resetTerm(termId) {
            const currentYear = schoolYears[currentSchoolYearIndex];
            const term = currentYear.terms.find(t => t.id === termId);
            
            if (!term) {
                showToast('Term not found.', 'error');
                return;
            }
            
            // Check if term can be reset
            if (term.status === 'active') {
                showToast('Cannot reset active term. Deactivate it first.', 'warning');
                return;
            }
            
            // Create confirmation message with data summary
            const dataSummary = term.students !== '0/0' ? 
                `This will clear ${term.students} student applications and reset the term to inactive status.` :
                'This will reset the term to inactive status.';
            
            showConfirmation(
                'Reset Term',
                `Reset ${termId}? ${dataSummary}`,
                'Reset Term',
                'Cancel',
                () => {
                    // Reset logic
                    term.status = 'inactive';
                    term.students = '0/0';
                    showToast(`${termId} reset successfully!`, 'success');
                    updateTermsList(currentYear);
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
            
            // First, check if modal HTML exists in DOM
            const modal = document.querySelector('.signatory-settings-modal-overlay');
            if (!modal) {
                console.error('Modal HTML not found in DOM. Modal might not be loaded.');
                return;
            }
            
            // Check if the modal function exists
            if (typeof window.openSignatorySettingsModal === 'function') {
                console.log('Using window function');
                window.openSignatorySettingsModal(type);
            } else {
                console.log('Using fallback method');
                // Fallback: directly open the modal
                if (modal) {
                    console.log('Modal found, opening...');
                    // Set the clearance type
                    window.currentClearanceType = type;
                    
                    // Update modal title based on clearance type
                    const modalTitle = type === 'student' ? 'Student Signatory Settings' : 'Faculty Signatory Settings';
                    const titleElement = document.getElementById('signatorySettingsTitle');
                    if (titleElement) {
                        titleElement.textContent = modalTitle;
                    } else {
                        console.error('signatorySettingsTitle element not found in fallback');
                    }
                    
                    // Set the clearance type in the form
                    const clearanceTypeInput = document.getElementById('settingsClearanceType');
                    if (clearanceTypeInput) {
                        clearanceTypeInput.value = type;
                    } else {
                        console.error('settingsClearanceType element not found in fallback');
                    }
                    
                    // Load settings based on type
                    if (type === 'student') {
                        const firstPosition = document.getElementById('requiredFirstPosition');
                        const lastPosition = document.getElementById('requiredLastPosition');
                        if (firstPosition) firstPosition.value = 'Cashier';
                        if (lastPosition) lastPosition.value = 'Registrar';
                    } else {
                        const firstPosition = document.getElementById('requiredFirstPosition');
                        const lastPosition = document.getElementById('requiredLastPosition');
                        if (firstPosition) firstPosition.value = 'Accountant';
                        if (lastPosition) lastPosition.value = 'Registrar';
                    }
                    
                    // Show modal
                    modal.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                    console.log('Modal should now be visible');
                } else {
                    console.error('Signatory settings modal not found');
                }
            }
        }

        function openExportModal() {
            openClearanceExportModal();
        }

        // Initialize accordions as expanded by default
        document.addEventListener('DOMContentLoaded', function() {
            // All accordions start expanded
            const accordions = document.querySelectorAll('.accordion-content');
            accordions.forEach(accordion => {
                accordion.style.display = 'block';
            });
            
            // Test if modal HTML is loaded
            const modal = document.querySelector('.signatory-settings-modal-overlay');
            if (modal) {
                console.log('✅ Modal HTML is loaded in DOM');
            } else {
                console.error('❌ Modal HTML is NOT loaded in DOM');
            }
            
            // Initialize school year display
            updateSchoolYearDisplay();
            updateNavigationButtons();
            
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
            
            if (sidebar) {
                // Toggle active class for mobile overlay
                sidebar.classList.toggle('active');
                
                // Show/hide backdrop
                if (backdrop) {
                    if (sidebar.classList.contains('active')) {
                        backdrop.style.display = 'block';
                    } else {
                        backdrop.style.display = 'none';
                    }
                }
            }
        }
    </script>

    <!-- Include Signatory Settings Modal -->
    <?php include '../../Modals/SignatorySettingsModal.php'; ?>
    
    <!-- Include Alerts Component -->
    <?php include '../../includes/components/alerts.php'; ?>
</body>
</html> 