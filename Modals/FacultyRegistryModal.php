<?php // Faculty Registry Modal - Add New Faculty ?>
<link rel="stylesheet" href="../../assets/css/modals.css">
<div class="modal-overlay faculty-registration-modal-overlay" id="facultyRegistrationModal" style="display: none;">
  <div class="modal-window">
    <button class="modal-close" onclick="closeFacultyRegistrationModal()">&times;</button>
    <h2 class="modal-title">👨‍🏫 Add New Faculty</h2>
    <div class="modal-supporting-text">Fill out the form below to register a new faculty member.</div>
    <div class="modal-content-area">
      <form id="facultyRegistrationForm" class="modal-form" data-endpoint="../../controllers/addUsers.php">
        <input type="hidden" name="type" value="faculty">
        <div class="form-group">
          <label for="employeeNumber">Employee Number *</label>
          <input type="text" id="employeeNumber" name="employeeNumber" required placeholder="e.g., LCA1234P" maxlength="8">
        </div>

        <div class="form-group">
          <label for="employmentStatus">Employment Status *</label>
          <select id="employmentStatus" name="employmentStatus" required>
            <option value="">Select Employment Status</option>
            <option value="Full Time">Full Time</option>
            <option value="Part Time">Part Time</option>
            <option value="Part Time - Full Load">Part Time - Full Load</option>
          </select>
        </div>
        <div class="form-group">
          <label for="lastName">Last Name *</label>
          <input type="text" id="lastName" name="lastName" required placeholder="Enter last name">
        </div>
        <div class="form-group">
          <label for="firstName">First Name *</label>
          <input type="text" id="firstName" name="firstName" required placeholder="Enter first name">
        </div>
        <div class="form-group">
          <label for="middleName">Middle Name</label>
          <input type="text" id="middleName" name="middleName" placeholder="Enter middle name (optional)">
        </div>
        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" placeholder="Enter email address">
        </div>
        <div class="form-group">
          <label for="contactNumber">Contact Number</label>
          <input type="text" id="contactNumber" name="contactNumber" placeholder="e.g., +63 912 345 6789">
        </div>

        <!-- Primary Department (Required) - matches student registration pattern -->
        <div class="form-group">
          <label for="primaryDepartment">Department *</label>
          <select id="primaryDepartment" name="primaryDepartment" required>
            <option value="">Loading Departments...</option>
          </select>
          <small class="form-help" id="departmentHelpText">Select the primary department for this faculty member</small>
        </div>

        <!-- Multi-Department Assignment Section (Optional) -->
        <div id="additionalDepartmentsSection">
          <div class="form-section-divider">
            <hr>
            <span class="divider-text">Additional Department Assignments (Optional)</span>
          </div>
          <div class="form-group">
            <label>Additional Departments</label>
            <small class="form-help">Assign this faculty member to additional departments beyond their primary</small>
            <div style="display: flex; gap: 8px; align-items: center; margin-top: 8px;">
              <select id="additionalDepartmentSelect" style="flex: 1; padding: 6px;">
                <option value="">Select a department...</option>
              </select>
              <button type="button" class="btn btn-sm btn-outline-primary" onclick="addAdditionalDepartment()">
                <i class="fas fa-plus"></i> Add
              </button>
            </div>
            <div id="departmentsList" style="margin-top: 8px; display: flex; gap: 8px; flex-wrap: wrap;">
              <!-- Additional departments will appear as chips here -->
            </div>
          </div>
        </div>
      </form>
    </div>
    <div class="modal-actions">
      <button class="modal-action-secondary" onclick="closeFacultyRegistrationModal()">Cancel</button>
      <button class="modal-action-primary" onclick="submitFacultyRegistrationForm()">Generate Credentials</button>
    </div>
  </div>
</div>

<script>
  // Store additional departments for faculty registration
  window.additionalDepartments = [];
  
  // Store loaded departments data for reuse
  window.facultyDepartmentsData = [];
  
  // Check if we're in Program Head context (restricted mode)
  // DEPARTMENT_IDS is set by Program Head pages, undefined for Admin pages
  function isRestrictedMode() {
    return typeof DEPARTMENT_IDS !== 'undefined' 
           && Array.isArray(DEPARTMENT_IDS) 
           && DEPARTMENT_IDS.length > 0;
  }
  
  // Get the restricted department IDs (Program Head's assigned departments)
  function getRestrictedDepartmentIds() {
    return isRestrictedMode() ? DEPARTMENT_IDS : [];
  }

  // Populate primary department dropdown with cross-sector support
  async function populatePrimaryDepartmentDropdown() {
    const sel = document.getElementById('primaryDepartment');
    const helpText = document.getElementById('departmentHelpText');
    const additionalSection = document.getElementById('additionalDepartmentsSection');
    if (!sel) return;
    
    try {
      // Fetch Faculty sector departments
      const response = await fetch('../../api/departments/list.php?sector=Faculty&limit=500', { 
        credentials: 'include' 
      });
      const data = await response.json();
      
      if (!data || data.success !== true) {
        sel.innerHTML = '<option value="">Error loading departments</option>';
        return;
      }
      
      // Store for reuse
      window.facultyDepartmentsData = data.departments || [];
      
      // Check if restricted mode (Program Head)
      const restrictedIds = getRestrictedDepartmentIds();
      const isRestricted = restrictedIds.length > 0;
      
      // Filter departments based on mode
      let departmentsToShow = window.facultyDepartmentsData;
      
      if (isRestricted) {
        // Program Head mode: only show their assigned departments (Faculty sector versions)
        departmentsToShow = window.facultyDepartmentsData.filter(dept => {
          // Check if any sector's department_id is in the restricted list
          return dept.sectors && dept.sectors.some(s => restrictedIds.includes(s.department_id));
        });
      }
      
      // Build the dropdown
      sel.innerHTML = '';
      
      if (departmentsToShow.length === 0) {
        sel.innerHTML = '<option value="">No departments available</option>';
        if (helpText) helpText.textContent = 'No Faculty sector departments found for your assignments.';
        return;
      }
      
      // For Program Head with single department: auto-select and make readonly
      if (isRestricted && departmentsToShow.length === 1) {
        const dept = departmentsToShow[0];
        const facultySector = dept.sectors.find(s => s.sector_name === 'Faculty');
        const deptId = facultySector ? facultySector.department_id : (dept.sectors[0]?.department_id || '');
        
        const option = document.createElement('option');
        option.value = deptId;
        option.textContent = dept.department_name + (dept.department_code ? ` (${dept.department_code})` : '');
        option.selected = true;
        sel.appendChild(option);
        
        // Make readonly
        sel.disabled = true;
        sel.style.backgroundColor = '#e9ecef';
        sel.style.cursor = 'not-allowed';
        
        // Update help text
        if (helpText) {
          helpText.innerHTML = '<i class="fas fa-lock" style="margin-right: 4px;"></i> Faculty will be registered under your assigned department.';
          helpText.style.color = '#6c757d';
        }
        
        // Hide additional departments section for single-department Program Heads
        if (additionalSection) {
          additionalSection.style.display = 'none';
        }
        
        return;
      }
      
      // Multiple departments available - show dropdown
      sel.disabled = false;
      sel.style.backgroundColor = '';
      sel.style.cursor = '';
      
      // Show additional departments section
      if (additionalSection) {
        additionalSection.style.display = 'block';
      }
      
      // Add placeholder
      const placeholder = document.createElement('option');
      placeholder.value = '';
      placeholder.textContent = 'Select primary department...';
      sel.appendChild(placeholder);
      
      if (isRestricted) {
        // Program Head mode: simple list (no grouping needed)
        departmentsToShow.forEach(dept => {
          const facultySector = dept.sectors.find(s => s.sector_name === 'Faculty');
          const deptId = facultySector ? facultySector.department_id : (dept.sectors[0]?.department_id || '');
          
          const option = document.createElement('option');
          option.value = deptId;
          option.textContent = dept.department_name + (dept.department_code ? ` (${dept.department_code})` : '');
          sel.appendChild(option);
        });
        
        if (helpText) {
          helpText.textContent = 'Select from your assigned departments.';
        }
      } else {
        // Admin mode: group by cross-sector vs faculty-only with indicators
        const crossSectorDepts = departmentsToShow.filter(d => d.is_shared);
        const facultyOnlyDepts = departmentsToShow.filter(d => !d.is_shared);
        
        // Cross-Sector Departments group
        if (crossSectorDepts.length > 0) {
          const crossGroup = document.createElement('optgroup');
          crossGroup.label = '── Cross-Sector Departments ──';
          
          crossSectorDepts.forEach(dept => {
            const facultySector = dept.sectors.find(s => s.sector_name === 'Faculty');
            const deptId = facultySector ? facultySector.department_id : (dept.sectors[0]?.department_id || '');
            
            // Build shared sectors text (excluding Faculty)
            const sharedWith = dept.sectors
              .filter(s => s.sector_name !== 'Faculty')
              .map(s => s.sector_name)
              .join(', ');
            
            const option = document.createElement('option');
            option.value = deptId;
            let text = dept.department_name;
            if (dept.department_code) text += ` (${dept.department_code})`;
            if (sharedWith) text += ` [Shared with: ${sharedWith}]`;
            option.textContent = text;
            crossGroup.appendChild(option);
          });
          
          sel.appendChild(crossGroup);
        }
        
        // Faculty-Only Departments group
        if (facultyOnlyDepts.length > 0) {
          const facultyGroup = document.createElement('optgroup');
          facultyGroup.label = '── Faculty-Only Departments ──';
          
          facultyOnlyDepts.forEach(dept => {
            const facultySector = dept.sectors.find(s => s.sector_name === 'Faculty');
            const deptId = facultySector ? facultySector.department_id : (dept.sectors[0]?.department_id || '');
            
            const option = document.createElement('option');
            option.value = deptId;
            let text = dept.department_name;
            if (dept.department_code) text += ` (${dept.department_code})`;
            option.textContent = text;
            facultyGroup.appendChild(option);
          });
          
          sel.appendChild(facultyGroup);
        }
        
        if (helpText) {
          helpText.textContent = 'Select the primary department for this faculty member. Cross-sector departments are shared with student sectors.';
        }
      }
      
      // Also populate additional departments dropdown
      populateAdditionalDepartmentSelect();
      
    } catch (error) {
      console.error('[FacultyRegistryModal] Error loading departments:', error);
      sel.innerHTML = '<option value="">Error loading departments</option>';
    }
  }

  // Add additional department
  window.addAdditionalDepartment = function() {
    const sel = document.getElementById('additionalDepartmentSelect');
    const primarySel = document.getElementById('primaryDepartment');
    if (!sel) return;
    const val = sel.value;
    const text = sel.options[sel.selectedIndex] ? sel.options[sel.selectedIndex].text : '';

    if (!val) {
      showToastNotification('Please select a department', 'error');
      return;
    }

    const deptId = parseInt(val, 10);
    if (isNaN(deptId)) {
      showToastNotification('Invalid department selected', 'error');
      return;
    }
    
    // Prevent selecting the primary department as additional
    const primaryDeptId = parseInt(primarySel?.value, 10);
    if (deptId === primaryDeptId) {
      showToastNotification('This is already the primary department', 'warning');
      return;
    }

    // Prevent duplicates
    if (window.additionalDepartments.some(d => d.department_id === deptId)) {
      showToastNotification('This department is already added', 'warning');
      return;
    }

    window.additionalDepartments.push({ department_id: deptId, department_name: text });
    sel.value = '';
    renderAdditionalDepartments();
  };

  // Remove additional department
  window.removeAdditionalDepartment = function(departmentName) {
    window.additionalDepartments = window.additionalDepartments.filter(d => (d.department_name || '') !== departmentName);
    renderAdditionalDepartments();
  };

  // Render additional departments as chips
  function renderAdditionalDepartments() {
    const container = document.getElementById('departmentsList');
    if (!container) return;

    container.innerHTML = window.additionalDepartments.map(d => `
      <span class="chip" style="padding: 6px 12px; background: #e9ecef; border-radius: 20px; font-size: 14px; display: flex; align-items: center; gap: 8px;">
        ${d.department_name}
        <button type="button" onclick="removeAdditionalDepartment('${d.department_name.replace(/'/g, "\\'")}')" style="background: none; border: none; cursor: pointer; color: #dc3545; font-size: 16px; padding: 0;">
          ×
        </button>
      </span>
    `).join('');
  }

  // Populate additional department select (uses stored data)
  function populateAdditionalDepartmentSelect() {
    const sel = document.getElementById('additionalDepartmentSelect');
    if (!sel) return;
    
    // If we already have data, use it
    if (window.facultyDepartmentsData && window.facultyDepartmentsData.length > 0) {
      sel.innerHTML = '';
      const ph = document.createElement('option');
      ph.value = '';
      ph.text = 'Select a department...';
      sel.appendChild(ph);
      
      // Get restricted IDs for Program Head mode
      const restrictedIds = getRestrictedDepartmentIds();
      const isRestricted = restrictedIds.length > 0;
      
      let departmentsToShow = window.facultyDepartmentsData;
      if (isRestricted) {
        departmentsToShow = window.facultyDepartmentsData.filter(dept => {
          return dept.sectors && dept.sectors.some(s => restrictedIds.includes(s.department_id));
        });
      }
      
      departmentsToShow.forEach(dept => {
        const facultySector = dept.sectors.find(s => s.sector_name === 'Faculty');
        const deptId = facultySector ? facultySector.department_id : (dept.sectors[0]?.department_id || '');
        
        const o = document.createElement('option');
        o.value = deptId;
        let text = dept.department_name;
        if (dept.department_code) text += ` (${dept.department_code})`;
        if (dept.is_shared && !isRestricted) text += ' [Shared]';
        o.text = text;
        sel.appendChild(o);
      });
      sel.selectedIndex = 0;
      return;
    }
    
    // Fallback: fetch from API
    const url = '../../api/departments/list.php?sector=Faculty&limit=500';
    fetch(url, { credentials: 'include' })
      .then(r => r.json())
      .then(data => {
        if (!data || data.success !== true) return;
        sel.innerHTML = '';
        const ph = document.createElement('option');
        ph.value = '';
        ph.text = 'Select a department...';
        sel.appendChild(ph);
        (data.departments || []).forEach(dept => {
          const facultySector = dept.sectors.find(s => s.sector_name === 'Faculty');
          const deptId = facultySector ? facultySector.department_id : (dept.sectors[0]?.department_id || '');
          
          const o = document.createElement('option');
          o.value = deptId;
          let text = dept.department_name;
          if (dept.department_code) text += ` (${dept.department_code})`;
          if (dept.is_shared) text += ' [Shared]';
          o.text = text;
          sel.appendChild(o);
        });
        sel.selectedIndex = 0;
      })
      .catch(() => {});
  }

  // Form validation and submission
  function validateFacultyForm() {
    const requiredFields = ['employeeNumber', 'employmentStatus', 'lastName', 'firstName'];
    let isValid = true;
    
    requiredFields.forEach(fieldId => {
      const field = document.getElementById(fieldId);
      const value = field.value.trim();
      
      if (!value) {
        showFieldError(fieldId, 'This field is required');
        isValid = false;
      } else {
        showFieldSuccess(fieldId);
      }
    });
    
    // Primary department validation
    const primaryDept = document.getElementById('primaryDepartment');
    if (primaryDept && !primaryDept.value) {
      showFieldError('primaryDepartment', 'Please select a department');
      isValid = false;
    } else if (primaryDept && primaryDept.value) {
      showFieldSuccess('primaryDepartment');
    }
    
    // Email validation
    const email = document.getElementById('email').value.trim();
    if (email && !isValidEmail(email)) {
      showFieldError('email', 'Please enter a valid email address');
      isValid = false;
    }
    
    // Employee number validation
    const employeeNumber = document.getElementById('employeeNumber').value.trim();
    if (employeeNumber && !/^LCA\d{4}[A-Z]$/.test(employeeNumber)) {
      showFieldError('employeeNumber', 'Employee number should be in format: LCA + 4 digits + 1 letter (e.g., LCA1234P)');
      isValid = false;
    }
    
    return isValid;
  }
  
  function showFieldError(fieldId, message) {
    const field = document.getElementById(fieldId);
    const errorDiv = field.parentNode.querySelector('.field-error') || document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.textContent = message;
    errorDiv.style.color = '#dc3545';
    errorDiv.style.fontSize = '0.85rem';
    errorDiv.style.marginTop = '4px';
    
    if (!field.parentNode.querySelector('.field-error')) {
      field.parentNode.appendChild(errorDiv);
    }
    
    field.style.borderColor = '#dc3545';
  }
  
  function showFieldSuccess(fieldId) {
    const field = document.getElementById(fieldId);
    const errorDiv = field.parentNode.querySelector('.field-error');
    if (errorDiv) {
      errorDiv.remove();
    }
    field.style.borderColor = '#28a745';
  }
  
  function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }
  
  // Make functions globally accessible
  window.openFacultyRegistrationModal = function() {
    console.log('[FacultyRegistryModal] ===== openFacultyRegistrationModal called =====');
    
    try {
      const modal = document.getElementById('facultyRegistrationModal');
      console.log('[FacultyRegistryModal] Modal element search result:', modal);
      
      if (!modal) {
        console.error('[FacultyRegistryModal] ❌ Modal element not found!');
        if (typeof showToastNotification === 'function') {
          showToastNotification('Faculty registration modal not found. Please refresh the page.', 'error');
        }
        return;
      }
      
      console.log('[FacultyRegistryModal] ✅ Modal element found');
      console.log('[FacultyRegistryModal] Modal current display:', window.getComputedStyle(modal).display);
      console.log('[FacultyRegistryModal] Modal current opacity:', window.getComputedStyle(modal).opacity);
      console.log('[FacultyRegistryModal] Modal inline style:', modal.style.display);

      // Use window.openModal if available, otherwise fallback
      if (typeof window.openModal === 'function') {
        console.log('[FacultyRegistryModal] Using window.openModal()');
        window.openModal('facultyRegistrationModal');
        
        setTimeout(() => {
          const finalDisplay = window.getComputedStyle(modal).display;
          const finalOpacity = window.getComputedStyle(modal).opacity;
          console.log('[FacultyRegistryModal] After openModal - display:', finalDisplay, 'opacity:', finalOpacity);
          if (finalDisplay === 'none' || finalDisplay === '') {
            console.error('[FacultyRegistryModal] ❌ Modal still hidden!');
          }
        }, 100);
      } else {
        console.log('[FacultyRegistryModal] window.openModal not available, using fallback');
        // Fallback to direct manipulation
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        document.body.classList.add('modal-open');
        requestAnimationFrame(() => {
          modal.classList.add('active');
          console.log('[FacultyRegistryModal] Fallback: Added active class');
        });
      }

      // Reset state
      window.additionalDepartments = [];
      window.facultyDepartmentsData = [];
      const deptList = document.getElementById('departmentsList');
      if (deptList) deptList.innerHTML = '';
      
      // Reset primary department dropdown
      const primaryDept = document.getElementById('primaryDepartment');
      if (primaryDept) {
        primaryDept.disabled = false;
        primaryDept.style.backgroundColor = '';
        primaryDept.style.cursor = '';
      }
      
      // Show additional departments section (may be hidden for single-dept Program Heads)
      const additionalSection = document.getElementById('additionalDepartmentsSection');
      if (additionalSection) {
        additionalSection.style.display = 'block';
      }
      
      // Populate departments (primary and additional)
      populatePrimaryDepartmentDropdown();
    } catch (error) {
      console.error('[FacultyRegistryModal] ❌ Error:', error);
      console.error('[FacultyRegistryModal] Error stack:', error.stack);
      if (typeof showToastNotification === 'function') {
        showToastNotification('Unable to open faculty registration modal. Please try again.', 'error');
      }
    }
  };
  
  window.closeFacultyRegistrationModal = function() {
    console.log('[FacultyRegistryModal] closeFacultyRegistrationModal() called');
    try {
      const modal = document.getElementById('facultyRegistrationModal');
      if (!modal) {
        console.warn('[FacultyRegistryModal] Modal not found');
        return;
      }
      console.log('[FacultyRegistryModal] Closing modal:', modal.id);

      // Use window.closeModal if available, otherwise fallback
      if (typeof window.closeModal === 'function') {
        window.closeModal('facultyRegistrationModal');
      } else {
        // Fallback to direct manipulation
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        document.body.classList.remove('modal-open');
        modal.classList.remove('active');
      }
      
      // Reset form
      const form = document.getElementById('facultyRegistrationForm');
      if (form) form.reset();
      window.additionalDepartments = [];
      window.facultyDepartmentsData = [];
      const deptList = document.getElementById('departmentsList');
      if (deptList) deptList.innerHTML = '';
      
      // Reset primary department dropdown
      const primaryDept = document.getElementById('primaryDepartment');
      if (primaryDept) {
        primaryDept.innerHTML = '<option value="">Loading Departments...</option>';
        primaryDept.disabled = false;
        primaryDept.style.backgroundColor = '';
        primaryDept.style.cursor = '';
      }
      
      // Reset help text
      const helpText = document.getElementById('departmentHelpText');
      if (helpText) {
        helpText.textContent = 'Select the primary department for this faculty member';
        helpText.style.color = '';
      }
      
      // Show additional departments section
      const additionalSection = document.getElementById('additionalDepartmentsSection');
      if (additionalSection) {
        additionalSection.style.display = 'block';
      }
      
      // Clear error messages
      const errorDivs = modal.querySelectorAll('.field-error');
      errorDivs.forEach(div => div.remove());
      
      // Reset field borders
      const fields = modal.querySelectorAll('input, select');
      fields.forEach(field => {
        field.style.borderColor = '';
      });
    } catch (error) {
      // Silent error handling
    }
  };
  
  function submitFacultyRegistrationForm() {
    if (!validateFacultyForm()) {
      showToastNotification('Please correct the errors in the form', 'error');
      return;
    }

    // Generate credentials locally first
    const form = document.getElementById('facultyRegistrationForm');
    const empId = form.employeeNumber.value.trim();
    const lastName = form.lastName.value.trim().replace(/\s+/g, '');
    const username = empId; // Use employee number as username
    const password = `${lastName}${empId}`; // e.g., TiborLCA5030P

    // Prepare the data for the modal and the final submission
    const credentialData = { username, password };

    // The callback function that will be executed when "Confirm & Save" is clicked
    const confirmCallback = () => {
      // Pass the generated credentials along with the form data
      confirmFacultyCreation(credentialData);
    };

    // Open the unified credentials modal
    openGeneratedCredentialsModal('newAccount', credentialData, confirmCallback);
  }

  function closeCredentialModal() {
    closeGeneratedCredentialsModal();
  }

  function copyCredentials() {
    const u = document.getElementById('generatedUsername').value;
    const p = document.getElementById('generatedPassword').value;
    const txt = `Username: ${u}\nPassword: ${p}`;
    navigator.clipboard?.writeText(txt).then(()=>{
        showToastNotification('Credentials copied','success');
    }).catch(()=>{
        // fallback
        const temp=document.createElement('textarea');
        temp.value=txt;document.body.appendChild(temp);temp.select();document.execCommand('copy');document.body.removeChild(temp);
        showToastNotification('Credentials copied','success');
    });
  }

  function confirmFacultyCreation(credentialData) {
    const form = document.getElementById('facultyRegistrationForm');
    
    // Get selected primary department
    const primaryDeptSelect = document.getElementById('primaryDepartment');
    const selectedDepartmentId = primaryDeptSelect ? parseInt(primaryDeptSelect.value, 10) : null;
    
    if (!selectedDepartmentId || isNaN(selectedDepartmentId)) {
      showToastNotification('Please select a department', 'error');
      return;
    }
    
    const data = {
      employee_number: form.employeeNumber.value.trim(),
      employment_status: form.employmentStatus.value,
      first_name: form.firstName.value.trim(),
      last_name: form.lastName.value.trim(),
      middle_name: form.middleName.value.trim() || null,
      email: form.email.value.trim() || null,
      contact_number: form.contactNumber.value.trim() || null,
      username: credentialData.username,
      password: credentialData.password,
      department_id: selectedDepartmentId // Use selected department
    };

    // Add additional departments if any (excluding primary to avoid duplicates)
    if (window.additionalDepartments && window.additionalDepartments.length > 0) {
      const additionalIds = window.additionalDepartments
        .map(d => d.department_id)
        .filter(id => id !== selectedDepartmentId); // Exclude primary
      if (additionalIds.length > 0) {
        data['assignedDepartments'] = additionalIds;
      }
    }

    const confirmBtn = document.getElementById('credentialModalConfirmBtn');
    if(confirmBtn) confirmBtn.disabled = true;

    fetch('../../api/users/create_faculty.php', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    })
    .then(r=>r.json())
    .then(res=>{
        if(res.success){
            showToastNotification('Faculty registered successfully!','success');
            closeGeneratedCredentialsModal();
            closeFacultyRegistrationModal();
            // notify parent page
            document.dispatchEvent(new CustomEvent('faculty-added',{detail:{employee_number:data.employee_number}}));
        } else {
            showToastNotification(res.message||'Error registering faculty','error');
            if(confirmBtn) confirmBtn.disabled = false;
        }
    })
    .catch(err=>{
        console.error(err);
        showToastNotification('Network error','error');
        if(confirmBtn) confirmBtn.disabled = false;
    });
  }
  
  // Add event listeners
  document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('facultyRegistrationForm');
    const fields = form.querySelectorAll('input, select');
    
    fields.forEach(field => {
      field.addEventListener('blur', function() {
        const fieldId = this.id;
        const value = this.value.trim();
        
        if (value) {
          showFieldSuccess(fieldId);
        }
      });
    });
  });
</script> 