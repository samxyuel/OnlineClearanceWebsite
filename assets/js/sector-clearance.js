/**
 * Sector-Based Clearance System JavaScript
 * Handles sector-specific clearance period management
 */

// Global variables
let currentAcademicYear = null;
let currentSemester = null;
let sectorPeriods = {
  College: null,
  "Senior High School": null,
  Faculty: null,
};

// API Configuration - using global API_BASE from main page

/**
 * Initialize sector-based clearance system
 */
async function initializeSectorClearance() {
  try {
    console.log("🚀 Initializing sector-based clearance system...");

    // Load current academic year and terms
    await loadCurrentAcademicContext();

    // Load sector periods
    await loadSectorPeriods();

    // Load signatories for each sector
    await loadAllSectorSignatories();

    console.log("✅ Sector clearance system initialized successfully");
  } catch (error) {
    console.error("❌ Failed to initialize sector clearance system:", error);
    showToast("Failed to initialize clearance system", "error");
  }
}

/**
 * Load current academic year and terms
 */
async function loadCurrentAcademicContext() {
  try {
    const response = await fetch(`${API_BASE}/term_status.php`);
    const data = await response.json();

    if (data.success) {
      currentAcademicYear = data.term_status.academic_year;
      currentSemester = data.term_status.terms?.[0]; // Get current semester

      // Update UI
      updateAcademicYearDisplay();
    }
  } catch (error) {
    console.error("Error loading academic context:", error);
    throw error;
  }
}

/**
 * Load sector-specific periods
 */
async function loadSectorPeriods() {
  try {
    const response = await fetch(`${API_BASE}/sector-periods.php`);
    const data = await response.json();

    if (data.success) {
      // Update sector periods
      sectorPeriods = {
        College: null,
        "Senior High School": null,
        Faculty: null,
      };

      // Map periods by sector
      if (data.periods) {
        data.periods.forEach((period) => {
          sectorPeriods[period.sector] = period;
        });
      }

      // Update UI
      updateSectorPeriodsUI();
    }
  } catch (error) {
    console.error("Error loading sector periods:", error);
    throw error;
  }
}

/**
 * Update sector periods UI
 */
function updateSectorPeriodsUI() {
  const sectors = ["College", "Senior High School", "Faculty"];

  sectors.forEach((sector) => {
    const period = sectorPeriods[sector];
    const sectorKey = sector.toLowerCase().replace(" ", "-");

    // Update status badge
    const statusBadge = document.getElementById(`${sectorKey}-status-badge`);
    if (statusBadge) {
      const status = period?.status || "Not Started";
      statusBadge.textContent = status;
      statusBadge.className = `status-badge ${status
        .toLowerCase()
        .replace(" ", "-")}`;
    }

    // Update period details
    if (period) {
      updatePeriodDetails(sectorKey, period);
      updatePeriodActions(sectorKey, period.status);
    } else {
      updatePeriodDetails(sectorKey, null);
      updatePeriodActions(sectorKey, "Not Started");
    }
  });
}

/**
 * Update period details for a sector
 */
function updatePeriodDetails(sectorKey, period) {
  const elements = {
    startDate: document.getElementById(`${sectorKey}-start-date`),
    endDate: document.getElementById(`${sectorKey}-end-date`),
    applications: document.getElementById(`${sectorKey}-applications`),
    completed: document.getElementById(`${sectorKey}-completed`),
  };

  if (period) {
    elements.startDate.textContent = formatDate(period.start_date);
    elements.endDate.textContent = formatDate(period.end_date);
    elements.applications.textContent = period.total_forms || 0;
    elements.completed.textContent = period.completed_forms || 0;
  } else {
    elements.startDate.textContent = "-";
    elements.endDate.textContent = "-";
    elements.applications.textContent = "0";
    elements.completed.textContent = "0";
  }
}

/**
 * Update period action buttons
 */
function updatePeriodActions(sectorKey, status) {
  const startBtn = document.getElementById(`${sectorKey}-start-btn`);
  const pauseBtn = document.getElementById(`${sectorKey}-pause-btn`);
  const closeBtn = document.getElementById(`${sectorKey}-close-btn`);

  // Hide all buttons first
  [startBtn, pauseBtn, closeBtn].forEach((btn) => {
    if (btn) btn.style.display = "none";
  });

  // Show appropriate button based on status
  switch (status) {
    case "Not Started":
      if (startBtn) startBtn.style.display = "inline-block";
      break;
    case "Ongoing":
      if (pauseBtn) pauseBtn.style.display = "inline-block";
      if (closeBtn) closeBtn.style.display = "inline-block";
      break;
    case "Paused":
      if (startBtn) {
        startBtn.innerHTML = '<i class="fas fa-play"></i> Resume';
        startBtn.style.display = "inline-block";
      }
      if (closeBtn) closeBtn.style.display = "inline-block";
      break;
    case "Closed":
      // No buttons for closed periods
      break;
  }
}

/**
 * Start sector clearance period
 */
async function startSectorPeriod(sector) {
  try {
    if (!currentAcademicYear || !currentSemester) {
      showToast("Academic year or semester not loaded", "error");
      return;
    }

    const card = document.getElementById(
      `${sector.toLowerCase().replace(" ", "-")}-sector-card`
    );
    if (card) card.classList.add("loading");

    const response = await fetch(`${API_BASE}/sector-periods.php`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        academic_year_id: currentAcademicYear.academic_year_id,
        semester_id: currentSemester.semester_id,
        sector: sector,
        action: "start",
        start_date: new Date().toISOString().split("T")[0],
      }),
    });

    const data = await response.json();

    if (data.success) {
      showToast(`${sector} clearance period started successfully`, "success");
      await loadSectorPeriods();
    } else {
      showToast(data.message || "Failed to start clearance period", "error");
    }
  } catch (error) {
    console.error("Error starting sector period:", error);
    showToast("Failed to start clearance period", "error");
  } finally {
    const card = document.getElementById(
      `${sector.toLowerCase().replace(" ", "-")}-sector-card`
    );
    if (card) card.classList.remove("loading");
  }
}

/**
 * Pause sector clearance period
 */
async function pauseSectorPeriod(sector) {
  try {
    const period = sectorPeriods[sector];
    if (!period) {
      showToast("No active period found for this sector", "error");
      return;
    }

    const card = document.getElementById(
      `${sector.toLowerCase().replace(" ", "-")}-sector-card`
    );
    if (card) card.classList.add("loading");

    const response = await fetch(`${API_BASE}/sector-periods.php`, {
      method: "PUT",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        period_id: period.period_id,
        action: "pause",
      }),
    });

    const data = await response.json();

    if (data.success) {
      showToast(`${sector} clearance period paused successfully`, "success");
      await loadSectorPeriods();
    } else {
      showToast(data.message || "Failed to pause clearance period", "error");
    }
  } catch (error) {
    console.error("Error pausing sector period:", error);
    showToast("Failed to pause clearance period", "error");
  } finally {
    const card = document.getElementById(
      `${sector.toLowerCase().replace(" ", "-")}-sector-card`
    );
    if (card) card.classList.remove("loading");
  }
}

/**
 * Close sector clearance period
 */
async function closeSectorPeriod(sector) {
  try {
    const period = sectorPeriods[sector];
    if (!period) {
      showToast("No active period found for this sector", "error");
      return;
    }

    // Show confirmation dialog
    const confirmed = confirm(
      `Are you sure you want to close the ${sector} clearance period? This action cannot be undone and will reject all pending applications.`
    );
    if (!confirmed) return;

    const card = document.getElementById(
      `${sector.toLowerCase().replace(" ", "-")}-sector-card`
    );
    if (card) card.classList.add("loading");

    const response = await fetch(`${API_BASE}/sector-periods.php`, {
      method: "PUT",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        period_id: period.period_id,
        action: "close",
      }),
    });

    const data = await response.json();

    if (data.success) {
      showToast(`${sector} clearance period closed successfully`, "success");
      await loadSectorPeriods();
    } else {
      showToast(data.message || "Failed to close clearance period", "error");
    }
  } catch (error) {
    console.error("Error closing sector period:", error);
    showToast("Failed to close clearance period", "error");
  } finally {
    const card = document.getElementById(
      `${sector.toLowerCase().replace(" ", "-")}-sector-card`
    );
    if (card) card.classList.remove("loading");
  }
}

/**
 * Load signatories for all sectors
 */
async function loadAllSectorSignatories() {
  const sectors = ["College", "Senior High School", "Faculty"];

  for (const sector of sectors) {
    try {
      await loadSectorSignatories(sector);
    } catch (error) {
      console.error(`Error loading signatories for ${sector}:`, error);
    }
  }
}

/**
 * Load signatories for a specific sector
 */
async function loadSectorSignatories(sector) {
  try {
    const sectorKey = sector.toLowerCase().replace(" ", "-");
    const listElement = document.getElementById(`${sectorKey}SignatoryList`);

    if (!listElement) return;

    listElement.innerHTML =
      '<div class="loading-text">Loading signatories...</div>';

    // Get signatories from the existing API
    const response = await fetch(
      `${API_BASE}/signatories/list.php?sector=${encodeURIComponent(sector)}`
    );
    const data = await response.json();

    if (data.success && data.signatories) {
      renderSignatories(listElement, data.signatories);
    } else {
      listElement.innerHTML =
        '<div class="loading-text">No signatories assigned</div>';
    }
  } catch (error) {
    console.error(`Error loading signatories for ${sector}:`, error);
    const sectorKey = sector.toLowerCase().replace(" ", "-");
    const listElement = document.getElementById(`${sectorKey}SignatoryList`);
    if (listElement) {
      listElement.innerHTML =
        '<div class="loading-text">Error loading signatories</div>';
    }
  }
}

/**
 * Render signatories in the list
 */
function renderSignatories(container, signatories) {
  if (!signatories || signatories.length === 0) {
    container.innerHTML =
      '<div class="loading-text">No signatories assigned</div>';
    return;
  }

  const html = signatories
    .map((signatory) => {
      const requirementClass = signatory.is_required_first
        ? "required-first"
        : signatory.is_required_last
        ? "required-last"
        : "optional";
      const requirementText = signatory.is_required_first
        ? "(Required First)"
        : signatory.is_required_last
        ? "(Required Last)"
        : "";

      return `
            <div class="signatory-item ${requirementClass}">
                <span class="signatory-name">${
                  signatory.designation_name
                }</span>
                ${
                  requirementText
                    ? `<span class="signatory-requirement">${requirementText}</span>`
                    : ""
                }
                <button class="remove-signatory" onclick="removeSignatory('${
                  signatory.signatory_id
                }')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
    })
    .join("");

  container.innerHTML = html;
}

/**
 * Update academic year display
 */
function updateAcademicYearDisplay() {
  if (currentAcademicYear) {
    const yearElement = document.getElementById("currentYearName");
    if (yearElement) {
      yearElement.textContent = currentAcademicYear.school_year;
    }
  }
}

// Note: View Past Clearances Modal functions are now in ViewPastClearancesModal.php

/**
 * Utility function to format dates
 */
function formatDate(dateString) {
  if (!dateString) return "-";

  const date = new Date(dateString);
  return date.toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
}

/**
 * Remove signatory (placeholder function)
 */
function removeSignatory(signatoryId) {
  if (confirm("Are you sure you want to remove this signatory?")) {
    showToast("Signatory removed successfully", "success");
    // Implement actual removal logic
  }
}

/**
 * Refresh statistics
 */
async function refreshStatistics() {
  try {
    const card = document.getElementById("statistics-card");
    if (card) card.classList.add("loading");

    // This would typically call a statistics API
    // For now, just show a success message
    setTimeout(() => {
      showToast("Statistics refreshed successfully", "success");
      if (card) card.classList.remove("loading");
    }, 1500);
  } catch (error) {
    console.error("Error refreshing statistics:", error);
    showToast("Failed to refresh statistics", "error");
    const card = document.getElementById("statistics-card");
    if (card) card.classList.remove("loading");
  }
}

/**
 * Update statistics display
 */
function updateStatisticsDisplay(stats) {
  if (!stats) return;

  // Update main statistics
  const elements = {
    totalStudents: document.getElementById("total-students"),
    totalFaculty: document.getElementById("total-faculty"),
    totalApplied: document.getElementById("total-applied"),
    totalCompleted: document.getElementById("total-completed"),
  };

  if (elements.totalStudents)
    elements.totalStudents.textContent = stats.totalStudents || 0;
  if (elements.totalFaculty)
    elements.totalFaculty.textContent = stats.totalFaculty || 0;
  if (elements.totalApplied)
    elements.totalApplied.textContent = stats.totalApplied || 0;
  if (elements.totalCompleted)
    elements.totalCompleted.textContent = stats.totalCompleted || 0;

  // Update sector breakdown
  updateSectorBreakdown(stats.sectorBreakdown);
}

/**
 * Update sector breakdown
 */
function updateSectorBreakdown(breakdown) {
  const container = document.getElementById("statistics-breakdown");
  if (!container || !breakdown) return;

  const html = breakdown
    .map(
      (sector) => `
    <div class="breakdown-item">
      <div class="breakdown-label">
        <i class="fas fa-${getSectorIcon(sector.sector)}"></i> ${sector.sector}
      </div>
      <div class="breakdown-stats">
        <span class="breakdown-stat">${
          sector.sector === "Faculty" ? "Faculty" : "Students"
        }: <strong>${sector.total || 0}</strong></span>
        <span class="breakdown-stat">Applied: <strong>${
          sector.applied || 0
        }</strong></span>
        <span class="breakdown-stat">Completed: <strong>${
          sector.completed || 0
        }</strong></span>
      </div>
    </div>
  `
    )
    .join("");

  container.innerHTML = html;
}

/**
 * Get sector icon
 */
function getSectorIcon(sector) {
  const icons = {
    College: "university",
    "Senior High School": "graduation-cap",
    Faculty: "chalkboard-teacher",
  };
  return icons[sector] || "users";
}

// Initialize when DOM is loaded
document.addEventListener("DOMContentLoaded", function () {
  initializeSectorClearance();
});

// Export functions for global access
window.startSectorPeriod = startSectorPeriod;
window.pauseSectorPeriod = pauseSectorPeriod;
window.closeSectorPeriod = closeSectorPeriod;
window.removeSignatory = removeSignatory;
window.refreshStatistics = refreshStatistics;
window.updateStatisticsDisplay = updateStatisticsDisplay;
// Note: View Past Clearances Modal functions are exported in ViewPastClearancesModal.php
