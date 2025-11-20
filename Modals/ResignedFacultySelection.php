<?php
// Resigned Faculty Selection Modal
?>
<link rel="stylesheet" href="../../assets/css/modals.css">

<div class="modal-overlay resigned-faculty-modal-overlay" id="resignedFacultySelectionModal">
  <div class="modal-window resigned-faculty-modal-window">
    <button class="modal-close" onclick="window.closeResignedFacultySelectionModal && window.closeResignedFacultySelectionModal()">&times;</button>

    <h2 class="modal-title"><i class="fas fa-user-cog"></i> Manage Resigned Faculty</h2>
    <div class="modal-supporting-text">
      Choose faculty members who should appear in the Resigned Faculty list. Use the filters or search to narrow down the roster, then confirm to update the dashboard.
    </div>

    <div class="modal-content-area resigned-faculty-modal-content">
      <div class="faculty-selection-filters">
        <div class="filter-group">
          <label for="resignedSelectionDepartment">Department</label>
          <select id="resignedSelectionDepartment">
            <option value="">All Departments</option>
          </select>
        </div>
        <div class="filter-group">
          <label for="resignedSelectionEmployment">Employment Status</label>
          <select id="resignedSelectionEmployment">
            <option value="">All Employment Status</option>
          </select>
        </div>
        <div class="filter-group">
          <label for="resignedSelectionAccount">Account Status</label>
          <select id="resignedSelectionAccount">
            <option value="">All Account Status</option>
          </select>
        </div>
        <div class="filter-group filter-group-search">
          <label for="resignedSelectionSearch">Search</label>
          <div class="filter-search-input">
            <i class="fas fa-search"></i>
            <input type="text" id="resignedSelectionSearch" placeholder="Search by name or employee number">
          </div>
        </div>
      </div>

      <div class="faculty-roster-list">
        <div class="faculty-roster-header">
          <div class="faculty-roster-controls">
            <label class="checkbox-container" for="resignedSelectionSelectAll">
              <input type="checkbox" id="resignedSelectionSelectAll">
              <span class="checkmark"></span>
              Select All
            </label>
            <span class="faculty-roster-selected-count" id="resignedSelectionSelectedCount">0 selected</span>
          </div>
          <div class="faculty-roster-stats" id="resignedSelectionStats">Loading faculty roster...</div>
        </div>
        <div class="faculty-roster-container" id="resignedFacultySelectionContainer">
          <div class="loading-spinner">
            <div class="spinner"></div>
            <p>Loading faculty roster...</p>
          </div>
        </div>
      </div>

      <div class="selected-faculty-summary" id="resignedSelectionSummary">
        <div class="summary-header">
          <h4><i class="fas fa-check-circle"></i> Selected Faculty</h4>
          <span class="summary-count" id="resignedSelectionSummaryCount">0 selected</span>
        </div>
        <div class="selected-list" id="resignedSelectionSummaryList">
          <p class="no-selection">No faculty members selected.</p>
        </div>
      </div>
    </div>

    <div class="modal-actions">
      <button class="modal-action-secondary" onclick="window.closeResignedFacultySelectionModal && window.closeResignedFacultySelectionModal()">Cancel</button>
      <button class="modal-action-primary" id="confirmResignedFacultySelectionBtn" onclick="window.confirmResignedFacultySelection && window.confirmResignedFacultySelection()">
        Confirm Selection
      </button>
    </div>
  </div>
</div>

