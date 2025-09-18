/**
 * Clearance Button Manager
 * Manages the state of action buttons (Approve/Reject) based on:
 * 1. Active clearance term
 * 2. User's signatory assignment
 * 3. Faculty member's clearance application status
 */

class ClearanceButtonManager {
  constructor() {
    this.cache = new Map();
    this.cacheTimeout = 30000; // 30 seconds cache
  }

  /**
   * Check if action buttons should be enabled for a specific user (faculty/student)
   * @param {string} userId - The user's ID (faculty or student)
   * @param {string} clearanceType - The clearance type (College, Senior High School, Faculty)
   * @param {string} userType - The type of user ('faculty' or 'student')
   * @returns {Promise<Object>} Button status information
   */
  async checkButtonStatus(userId, clearanceType = null, userType = "faculty") {
    const cacheKey = `${userId}_${clearanceType || "all"}_${userType}`;

    // Check cache first
    if (this.cache.has(cacheKey)) {
      const cached = this.cache.get(cacheKey);
      if (Date.now() - cached.timestamp < this.cacheTimeout) {
        return cached.data;
      }
    }

    try {
      const url = new URL(
        "../../api/clearance/button_status.php",
        window.location.origin + window.location.pathname
      );

      // Set the appropriate parameter based on user type
      if (userId) {
        if (userType === "student") {
          url.searchParams.set("student_id", userId);
        } else {
          url.searchParams.set("faculty_id", userId);
        }
      }
      if (clearanceType) url.searchParams.set("clearance_type", clearanceType);

      const response = await fetch(url, {
        credentials: "include",
        headers: {
          "Content-Type": "application/json",
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const data = await response.json();

      if (!data.success) {
        throw new Error(data.message || "Failed to check button status");
      }

      // Cache the result
      this.cache.set(cacheKey, {
        data: data.button_status,
        timestamp: Date.now(),
      });

      return data.button_status;
    } catch (error) {
      console.error("Error checking button status:", error);

      // Return default disabled state on error
      return {
        buttons_enabled: false,
        conditions: {
          has_active_term: false,
          user_is_signatory: false,
          faculty_has_applied: false,
        },
        disabled_reasons: ["Error checking status: " + error.message],
      };
    }
  }

  /**
   * Update action buttons for a specific user row (faculty/student)
   * @param {string} userId - The user's ID (faculty or student)
   * @param {string} clearanceType - The clearance type
   * @param {HTMLElement} rowElement - The table row element
   * @param {string} userType - The type of user ('faculty' or 'student')
   */
  async updateButtonsForUser(
    userId,
    clearanceType,
    rowElement,
    userType = "faculty"
  ) {
    const buttonStatus = await this.checkButtonStatus(
      userId,
      clearanceType,
      userType
    );

    // Find action buttons in the row
    const approveBtn = rowElement.querySelector(".approve-btn, .btn-approve");
    const rejectBtn = rowElement.querySelector(".reject-btn, .btn-reject");

    const buttons = [approveBtn, rejectBtn].filter(Boolean);

    if (buttons.length === 0) {
      console.warn(`No action buttons found in row for ${userType}:`, userId);
      return;
    }

    // Update button states
    buttons.forEach((button) => {
      if (buttonStatus.buttons_enabled) {
        button.disabled = false;
        button.classList.remove("disabled");
        button.title =
          "Click to " +
          (button.classList.contains("approve-btn") ? "approve" : "reject") +
          " clearance";
      } else {
        button.disabled = true;
        button.classList.add("disabled");
        button.title = this.getDisabledTooltip(buttonStatus.disabled_reasons);
      }
    });

    // Add visual indicators
    this.addStatusIndicators(rowElement, buttonStatus);
  }

  /**
   * Update all action buttons in the user table (faculty/student)
   * @param {string} clearanceType - The clearance type to check
   * @param {string} userType - The type of user ('faculty' or 'student')
   */
  async updateAllButtons(clearanceType = null, userType = "faculty") {
    const dataAttribute =
      userType === "student" ? "data-student-id" : "data-faculty-id";
    const userRows = document.querySelectorAll(`tr[${dataAttribute}]`);

    if (userRows.length === 0) {
      console.warn(`No ${userType} rows found with ${dataAttribute} attribute`);
      return;
    }

    console.log(
      `Updating buttons for ${userRows.length} ${userType} members...`
    );

    // Process rows in batches to avoid overwhelming the server
    const batchSize = 5;
    for (let i = 0; i < userRows.length; i += batchSize) {
      const batch = Array.from(userRows).slice(i, i + batchSize);

      const promises = batch.map(async (row) => {
        const userId = row.getAttribute(dataAttribute);
        if (userId) {
          await this.updateButtonsForUser(userId, clearanceType, row, userType);
        }
      });

      await Promise.all(promises);

      // Small delay between batches
      if (i + batchSize < userRows.length) {
        await new Promise((resolve) => setTimeout(resolve, 100));
      }
    }

    console.log("Button updates completed");
  }

  /**
   * Get a user-friendly tooltip for disabled buttons
   * @param {Array} disabledReasons - Array of reasons why buttons are disabled
   * @returns {string} Tooltip text
   */
  getDisabledTooltip(disabledReasons) {
    if (!disabledReasons || disabledReasons.length === 0) {
      return "Action buttons are disabled";
    }

    const reasons = disabledReasons.map((reason) => {
      if (reason.includes("No active clearance term")) {
        return "No active clearance term";
      } else if (reason.includes("not assigned as a signatory")) {
        return "You are not assigned as a signatory";
      } else if (reason.includes("has not applied")) {
        return "User has not applied for clearance";
      } else if (reason.includes("No user specified")) {
        return "No user specified";
      }
      return reason;
    });

    return "Disabled: " + reasons.join(", ");
  }

  /**
   * Add visual status indicators to a faculty row
   * @param {HTMLElement} rowElement - The table row element
   * @param {Object} buttonStatus - The button status information
   */
  addStatusIndicators(rowElement, buttonStatus) {
    // Remove existing indicators
    const existingIndicator = rowElement.querySelector(
      ".button-status-indicator"
    );
    if (existingIndicator) {
      existingIndicator.remove();
    }

    // Add new indicator if buttons are disabled
    if (!buttonStatus.buttons_enabled) {
      const indicator = document.createElement("span");
      indicator.className = "button-status-indicator";
      indicator.innerHTML = '<i class="fas fa-info-circle"></i>';
      indicator.title = this.getDisabledTooltip(buttonStatus.disabled_reasons);
      indicator.style.cssText =
        "color: #6c757d; margin-left: 5px; cursor: help;";

      // Find the action buttons cell and add indicator
      const actionCell =
        rowElement.querySelector(".action-buttons").parentElement;
      if (actionCell) {
        actionCell.appendChild(indicator);
      }
    }
  }

  /**
   * Clear the cache
   */
  clearCache() {
    this.cache.clear();
  }

  /**
   * Get current user's signatory status
   * @returns {Promise<Object>} Signatory status information
   */
  async getUserSignatoryStatus() {
    try {
      const response = await fetch(
        "../../api/signatories/check_user_status.php",
        {
          credentials: "include",
          headers: {
            "Content-Type": "application/json",
          },
        }
      );

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const data = await response.json();

      if (!data.success) {
        throw new Error(data.message || "Failed to get signatory status");
      }

      return data.signatory_status;
    } catch (error) {
      console.error("Error getting user signatory status:", error);
      return {
        is_signatory: false,
        assignments: [],
        clearance_types: [],
      };
    }
  }

  /**
   * Get current term status
   * @returns {Promise<Object>} Term status information
   */
  async getTermStatus() {
    try {
      const response = await fetch("../../api/clearance/term_status.php", {
        credentials: "include",
        headers: {
          "Content-Type": "application/json",
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const data = await response.json();

      if (!data.success) {
        throw new Error(data.message || "Failed to get term status");
      }

      return data.term_status;
    } catch (error) {
      console.error("Error getting term status:", error);
      return {
        has_active_term: false,
        active_term: null,
        terms: [],
        academic_year: null,
      };
    }
  }
}

// Create global instance
window.clearanceButtonManager = new ClearanceButtonManager();

// Export for module systems
if (typeof module !== "undefined" && module.exports) {
  module.exports = ClearanceButtonManager;
}
