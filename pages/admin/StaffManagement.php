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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                    <span class="user-name"><?php echo htmlspecialchars($adminName); ?></span>
                    <div class="user-dropdown">
                        <button class="dropdown-toggle">▼</button>
                        <div class="dropdown-menu">
                            <a href="../../pages/shared/profile.php">Profile</a>
                            <a href="../../pages/shared/settings.php">Settings</a>
                            <a href="#" onclick="alert('Logout functionality will be implemented later')">Logout</a>
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
                    <button class="btn btn-secondary import-btn" onclick="openStaffImportModal()">
                        <i class="fas fa-file-import"></i> Import Staff
                    </button>
                    <button class="btn btn-secondary export-btn" onclick="openStaffExportModal()">
                        <i class="fas fa-file-export"></i> Export Staff
                    </button>
                </div>
            </div>

            <!-- Search and Filters Section -->
            <div class="search-filters-section">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search by name or employee ID...">
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
                    <select id="statusFilter" class="filter-select">
                        <option value="">All Status</option>
                        <option value="essential">Essential</option>
                        <option value="optional">Optional</option>
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

            <!-- Staff Cards Container -->
            <div class="staff-cards-container">
                <div class="staff-section">
                    <h3><i class="fas fa-shield-alt"></i> Essential Staff</h3>
                    <div class="staff-cards" id="essentialStaffCards">
                        <!-- Essential staff cards will be populated here -->
                    </div>
                </div>

                <div class="staff-section">
                    <h3><i class="fas fa-user-plus"></i> Optional Staff</h3>
                    <div class="staff-cards" id="optionalStaffCards">
                        <!-- Optional staff cards will be populated here -->
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
    </main>

    <!-- Include Modals -->
    <?php 
    include '../../Modals/StaffRegistryModal.php';
    include '../../Modals/EditStaffModal.php';
    include '../../Modals/StaffImportModal.php';
    include '../../Modals/StaffExportModal.php';
    ?>

    <!-- Include Alert System -->
    <?php include '../../includes/components/alerts.php'; ?>

    <script src="../../assets/js/alerts.js"></script>
    <script>
        // Sample staff data
        const staffData = [
            {
                id: 'LCA123P',
                name: 'John Smith',
                position: 'Registrar',
                status: 'essential',
                email: 'john.smith@gosti.edu.ph',
                contact: '+63 912 345 6789',
                department: 'Administration'
            },
            {
                id: 'LCA124P',
                name: 'Maria Garcia',
                position: 'Cashier',
                status: 'optional',
                email: 'maria.garcia@gosti.edu.ph',
                contact: '+63 912 345 6790',
                department: 'Finance'
            },
            {
                id: 'LCA125P',
                name: 'David Lee',
                position: 'Program Head',
                status: 'essential',
                email: 'david.lee@gosti.edu.ph',
                contact: '+63 912 345 6791',
                department: 'MIT'
            },
            {
                id: 'LCA126P',
                name: 'Sarah Chen',
                position: 'School Administrator',
                status: 'essential',
                email: 'sarah.chen@gosti.edu.ph',
                contact: '+63 912 345 6792',
                department: 'Administration'
            },
                                    {
                            id: 'LCA127P',
                            name: 'Mike Wilson',
                            position: 'Guidance',
                            status: 'essential',
                            email: 'mike.wilson@gosti.edu.ph',
                            contact: '+63 912 345 6793',
                            department: 'Student Services'
                        },
            {
                id: 'LCA128P',
                name: 'Lisa Brown',
                position: 'Accountant',
                status: 'optional',
                email: 'lisa.brown@gosti.edu.ph',
                contact: '+63 912 345 6794',
                department: 'Finance'
            },
            {
                id: 'LCA129P',
                name: 'Tom Davis',
                position: 'Librarian',
                status: 'optional',
                email: 'tom.davis@gosti.edu.ph',
                contact: '+63 912 345 6795',
                department: 'Library'
            },
                                    {
                            id: 'LCA130P',
                            name: 'Amy Johnson',
                            position: 'MIS/IT',
                            status: 'optional',
                            email: 'amy.johnson@gosti.edu.ph',
                            contact: '+63 912 345 6796',
                            department: 'IT'
                        },
                        {
                            id: 'LCA131P',
                            name: 'Robert Chen',
                            position: 'Disciplinary Officer',
                            status: 'essential',
                            email: 'robert.chen@gosti.edu.ph',
                            contact: '+63 912 345 6797',
                            department: 'Student Services'
                        },
                        {
                            id: 'LCA132P',
                            name: 'Maria Santos',
                            position: 'Clinic',
                            status: 'essential',
                            email: 'maria.santos@gosti.edu.ph',
                            contact: '+63 912 345 6798',
                            department: 'Health Services'
                        }
        ];

        let currentPage = 1;
        const cardsPerPage = 8;
        let filteredData = [...staffData];

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            renderStaffCards();
            updatePagination();
        });

        // Render staff cards
        function renderStaffCards() {
            const essentialContainer = document.getElementById('essentialStaffCards');
            const optionalContainer = document.getElementById('optionalStaffCards');
            
            essentialContainer.innerHTML = '';
            optionalContainer.innerHTML = '';

            const startIndex = (currentPage - 1) * cardsPerPage;
            const endIndex = startIndex + cardsPerPage;
            const pageData = filteredData.slice(startIndex, endIndex);

            pageData.forEach(staff => {
                const card = createStaffCard(staff);
                if (staff.status === 'essential') {
                    essentialContainer.appendChild(card);
                } else {
                    optionalContainer.appendChild(card);
                }
            });

            updatePaginationInfo();
        }

        // Create staff card
        function createStaffCard(staff) {
            const card = document.createElement('div');
            card.className = 'staff-card';
            card.setAttribute('data-staff-id', staff.id);
            
            const statusIcon = staff.status === 'essential' ? 
                '<i class="fa-solid fa-lock"></i>' : 
                '<i class="fa-solid fa-unlock"></i>';
            const statusClass = staff.status === 'essential' ? 'essential' : 'optional';
            
            card.innerHTML = `
                <div class="staff-card-header ${statusClass}">
                    <span class="status-indicator">${statusIcon}</span>
                    <span class="employee-id">${staff.id}</span>
                </div>
                <div class="staff-card-body">
                    <h4 class="staff-name">${staff.name}</h4>
                    <p class="staff-position">${staff.position}</p>
                    <p class="staff-department">${staff.department}</p>
                </div>
                <div class="staff-card-actions">
                    <button class="btn btn-sm btn-outline-primary" onclick="openEditStaffModal('${staff.id}')">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    ${staff.status === 'optional' ? 
                        `<button class="btn btn-sm btn-outline-danger" onclick="deleteStaff('${staff.id}')">
                            <i class="fas fa-trash"></i> Delete
                        </button>` : 
                        '<span class="essential-note">Essential - Cannot Delete</span>'
                    }
                </div>
            `;
            
            return card;
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            filteredData = staffData.filter(staff => 
                staff.name.toLowerCase().includes(searchTerm) ||
                staff.id.toLowerCase().includes(searchTerm) ||
                staff.position.toLowerCase().includes(searchTerm)
            );
            currentPage = 1;
            renderStaffCards();
            updatePagination();
        });

        // Filter functionality
        function applyFilters() {
            const positionFilter = document.getElementById('positionFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            
            filteredData = staffData.filter(staff => {
                const positionMatch = !positionFilter || staff.position === positionFilter;
                const statusMatch = !statusFilter || staff.status === statusFilter;
                return positionMatch && statusMatch;
            });
            
            currentPage = 1;
            renderStaffCards();
            updatePagination();
        }

        function clearFilters() {
            document.getElementById('positionFilter').value = '';
            document.getElementById('statusFilter').value = '';
            document.getElementById('searchInput').value = '';
            filteredData = [...staffData];
            currentPage = 1;
            renderStaffCards();
            updatePagination();
        }

        // Pagination functions
        function updatePagination() {
            const totalPages = Math.ceil(filteredData.length / cardsPerPage);
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
            const startEntry = (currentPage - 1) * cardsPerPage + 1;
            const endEntry = Math.min(currentPage * cardsPerPage, filteredData.length);
            
            document.getElementById('startEntry').textContent = startEntry;
            document.getElementById('endEntry').textContent = endEntry;
            document.getElementById('totalEntries').textContent = filteredData.length;
        }

        // Staff actions
        function deleteStaff(staffId) {
            showConfirmationModal(
                'Delete Staff Member',
                `Are you sure you want to delete staff member ${staffId}? This action cannot be undone.`,
                'Delete',
                'Cancel',
                () => {
                    // Remove from data
                    const index = staffData.findIndex(staff => staff.id === staffId);
                    if (index > -1) {
                        staffData.splice(index, 1);
                        filteredData = filteredData.filter(staff => staff.id !== staffId);
                        renderStaffCards();
                        updatePagination();
                        showToastNotification('Staff member deleted successfully!', 'success');
                    }
                },
                'danger'
            );
        }

        // Modal functions
        function openStaffRegistrationModal() {
            const modal = document.querySelector('.staff-registration-modal-overlay');
            if (modal) {
                modal.style.display = 'flex';
                document.body.classList.add('modal-open');
            }
        }

        function openEditStaffModal(staffId) {
            const staff = staffData.find(s => s.id === staffId);
            if (staff) {
                // Populate edit modal with staff data
                const modal = document.querySelector('.edit-staff-modal-overlay');
                if (modal) {
                    // Set form values
                    document.getElementById('editEmployeeId').value = staff.id;
                    document.getElementById('editStaffName').value = staff.name;
                    document.getElementById('editStaffEmail').value = staff.email;
                    document.getElementById('editStaffContact').value = staff.contact;
                    
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
                    
                    modal.style.display = 'flex';
                    document.body.classList.add('modal-open');
                }
            }
        }

        function openStaffImportModal() {
            const modal = document.querySelector('.staff-import-modal-overlay');
            if (modal) {
                modal.style.display = 'flex';
                document.body.classList.add('modal-open');
            }
        }

        function openStaffExportModal() {
            const modal = document.querySelector('.staff-export-modal-overlay');
            if (modal) {
                modal.style.display = 'flex';
                document.body.classList.add('modal-open');
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
    </script>
</body>
</html> 