/**
 * Activity Tracker JavaScript
 * Handles real-time updates, filtering, settings, and interactions
 */

class ActivityTracker {
  constructor() {
    // Prevent multiple instances
    if (window.activityTrackerInstance) {
      console.log(
        "ActivityTracker already exists, returning existing instance"
      );
      return window.activityTrackerInstance;
    }

    console.log("ActivityTracker constructor called");

    this.container = document.querySelector(".activity-tracker");
    this.activityList = this.container?.querySelector(".activity-list");
    this.filterPanel = this.container?.querySelector(".activity-filter");
    this.settingsPanel = this.container?.querySelector(".activity-settings");
    this.statsSummary = this.container?.querySelector(
      ".activity-stats-summary"
    );
    this.refreshBtn = this.container?.querySelector(".activity-refresh-btn");
    this.filterBtn = this.container?.querySelector(".activity-filter-btn");
    this.settingsBtn = this.container?.querySelector(".activity-settings-btn");
    this.filterForm = this.container?.querySelector("#activity-filter-form");
    this.settingsForm = this.container?.querySelector(
      "#activity-settings-form"
    );

    // Mobile toggle elements
    this.toggleBtn = document.querySelector("#activity-tracker-toggle");
    this.toggleIcon = document.querySelector("#toggle-icon");
    this.dashboardSidebar = document.querySelector(".dashboard-sidebar");
    this.backdrop = document.querySelector("#activity-tracker-backdrop");

    // Ensure elements are found - retry if needed
    if (!this.dashboardSidebar) {
      console.warn(
        "dashboardSidebar not found initially, will retry during toggle"
      );
    }
    if (!this.toggleBtn) {
      console.error(
        "Toggle button not found - mobile functionality will not work"
      );
    }
    if (!this.backdrop) {
      console.warn("Backdrop not found - mobile overlay will not work");
    }

    console.log("Mobile elements found:", {
      toggleBtn: !!this.toggleBtn,
      toggleIcon: !!this.toggleIcon,
      dashboardSidebar: !!this.dashboardSidebar,
      backdrop: !!this.backdrop,
    });

    // Detach toggle button from sidebar to keep it visible when sidebar is hidden (mobile)
    if (
      this.toggleBtn &&
      this.toggleBtn.parentElement &&
      this.toggleBtn.parentElement.classList.contains("dashboard-sidebar")
    ) {
      console.log("Moving toggle button to document.body");
      document.body.appendChild(this.toggleBtn);
    }
    // Detach backdrop from sidebar so it can be controlled independently
    if (
      this.backdrop &&
      this.backdrop.parentElement &&
      this.backdrop.parentElement.classList.contains("dashboard-sidebar")
    ) {
      console.log("Moving backdrop to document.body");
      document.body.appendChild(this.backdrop);
    }
    this.isMobile = window.innerWidth <= 992;
    this.isVisible = !this.isMobile; // Initial state based on desktop/mobile

    console.log("Initial state:", {
      isMobile: this.isMobile,
      isVisible: this.isVisible,
      windowWidth: window.innerWidth,
      toggleBtn: !!this.toggleBtn,
      dashboardSidebar: !!this.dashboardSidebar,
      backdrop: !!this.backdrop,
    });

    this.currentFilters = {
      type: "",
      priority: "",
      user: "",
      dateRange: "",
    };

    this.currentSettings = {
      autoRefresh: true,
      refreshInterval: 30,
      maxActivities: 10,
      showPriority: true,
      showUserInfo: true,
      showTimestamps: true,
    };

    this.refreshInterval = null;
    this.isLoading = false;

    // Store instance globally to prevent multiple instances
    window.activityTrackerInstance = this;
    console.log("ActivityTracker instance stored globally:", this);

    // Add global test method for debugging
    window.testActivityTrackerToggle = () => {
      if (this.testToggle) {
        this.testToggle();
      } else {
        console.log("testToggle method not available");
      }
    };

    this.init();
  }

  init() {
    console.log("ActivityTracker init called for instance:", this);

    if (!this.container) {
      console.error("Container not found, cannot initialize");
      return;
    }

    console.log("Binding events...");
    this.bindEvents();

    console.log("Loading settings...");
    this.loadSettings();

    console.log("Starting auto refresh...");
    this.startAutoRefresh();

    console.log("Loading activities...");
    this.loadActivities();

    console.log("Handling resize...");
    this.handleResize();

    console.log("Loading mobile state...");
    this.loadMobileState(); // Load mobile state on initialization

    console.log("Adding touch support...");
    this.addTouchSupport(); // Add touch support on initialization

    // Ensure toggle button is visible on mobile
    if (this.isMobile && this.toggleBtn) {
      console.log("Ensuring toggle button is visible on mobile");
      this.toggleBtn.style.display = "flex";
      // Force the display property to ensure it's visible
      this.toggleBtn.style.setProperty("display", "flex", "important");
      this.updateToggleButton();
    }

    console.log("ActivityTracker initialization complete");
  }

  bindEvents() {
    // Refresh button
    if (this.refreshBtn) {
      this.refreshBtn.addEventListener("click", () => this.refreshActivities());
    }

    // Filter toggle
    if (this.filterBtn) {
      this.filterBtn.addEventListener("click", () => this.toggleFilterPanel());
    }

    // Settings toggle
    if (this.settingsBtn) {
      this.settingsBtn.addEventListener("click", () =>
        this.toggleSettingsPanel()
      );
    }

    // Filter form submission
    if (this.filterForm) {
      this.filterForm.addEventListener("submit", (e) => {
        e.preventDefault();
        this.applyFilters();
      });
    }

    // Settings form submission
    if (this.settingsForm) {
      this.settingsForm.addEventListener("submit", (e) => {
        e.preventDefault();
        this.saveSettings();
      });
    }

    // Reset filter button
    const resetFilterBtn = this.container?.querySelector(".reset-filter-btn");
    if (resetFilterBtn) {
      resetFilterBtn.addEventListener("click", () => this.resetFilters());
    }

    // Reset settings button
    const resetSettingsBtn = this.container?.querySelector(
      ".reset-settings-btn"
    );
    if (resetSettingsBtn) {
      resetSettingsBtn.addEventListener("click", () => this.resetSettings());
    }

    // Mobile toggle button
    if (this.toggleBtn) {
      console.log("Binding click event to toggle button");
      this.toggleBtn.addEventListener("click", () => {
        console.log("Toggle button clicked");
        this.toggleMobileView();
      });

      // Test if the button is actually clickable
      console.log("Toggle button properties:", {
        display: this.toggleBtn.style.display,
        computedDisplay: window.getComputedStyle(this.toggleBtn).display,
        isVisible: this.toggleBtn.offsetParent !== null,
        zIndex: this.toggleBtn.style.zIndex,
        position: this.toggleBtn.style.position,
      });
    } else {
      console.error("Toggle button not found for event binding");
    }

    // Backdrop click to close sidebar
    if (this.backdrop) {
      console.log("Binding click event to backdrop");
      this.backdrop.addEventListener("click", () => {
        console.log("Backdrop clicked");
        if (this.isVisible) {
          this.toggleMobileView();
        }
      });
    } else {
      console.warn(
        "Backdrop not found for event binding - mobile overlay will not work"
      );
    }

    // Window resize handler (only bind once)
    if (!this.resizeHandlerBound) {
      window.addEventListener("resize", () => this.handleResize());
      this.resizeHandlerBound = true;
    }

    // Bind activity item events
    this.bindActivityItemEvents();

    // Bind modal events
    this.bindModalEvents();

    // Filter chips
    const filterChips = document.querySelectorAll(".filter-chip");
    filterChips.forEach((chip) => {
      chip.addEventListener("click", () => {
        // Remove active class from all chips
        filterChips.forEach((c) => c.classList.remove("active"));
        // Add active class to clicked chip
        chip.classList.add("active");

        const filterType = chip.getAttribute("data-filter");
        console.log("Filter chip clicked:", filterType);
        this.filterActivities(filterType);
      });
    });

    // Quick action buttons
    const refreshBtn = document.querySelector(
      ".quick-action-btn[title*='Refresh']"
    );
    if (refreshBtn) {
      refreshBtn.addEventListener("click", () => {
        console.log("Refresh button clicked");
        this.refreshActivities();
      });
    }

    const exportBtn = document.querySelector(
      ".quick-action-btn[title*='Export']"
    );
    if (exportBtn) {
      exportBtn.addEventListener("click", () => {
        console.log("Export button clicked");
        this.exportActivities();
      });
    }
  }

  bindActivityItemEvents() {
    if (!this.activityList) return;

    // Use event delegation for dynamic content
    this.activityList.addEventListener("click", (e) => {
      const activityItem = e.target.closest(".activity-item");
      if (!activityItem) return;

      const detailsBtn = e.target.closest(".activity-details-btn");
      if (detailsBtn) {
        e.preventDefault();
        e.stopPropagation();
        this.showActivityDetails(activityItem.dataset.activityId);
      } else {
        // Click on activity item itself
        this.showActivityDetails(activityItem.dataset.activityId);
      }
    });
  }

  bindModalEvents() {
    // Close modal when clicking outside
    document.addEventListener("click", (e) => {
      if (e.target.classList.contains("modal")) {
        this.closeModal(e.target);
      }
    });

    // Close modal with close button
    document.addEventListener("click", (e) => {
      if (e.target.classList.contains("modal-close")) {
        const modal = e.target.closest(".modal");
        if (modal) {
          this.closeModal(modal);
        }
      }
    });

    // Close modal with Escape key
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") {
        const openModal = document.querySelector(
          '.modal:not([style*="display: none"])'
        );
        if (openModal) {
          this.closeModal(openModal);
        }
      }
    });
  }

  toggleFilterPanel() {
    if (!this.filterPanel || !this.filterBtn) return;

    const isVisible = this.filterPanel.style.display !== "none";

    if (isVisible) {
      this.filterPanel.style.display = "none";
      this.filterBtn.classList.remove("active");
    } else {
      this.filterPanel.style.display = "block";
      this.filterBtn.classList.add("active");

      // Hide settings panel if open
      if (this.settingsPanel) {
        this.settingsPanel.style.display = "none";
        this.settingsBtn?.classList.remove("active");
      }
    }
  }

  toggleSettingsPanel() {
    if (!this.settingsPanel || !this.settingsBtn) return;

    const isVisible = this.settingsPanel.style.display !== "none";

    if (isVisible) {
      this.settingsPanel.style.display = "none";
      this.settingsBtn.classList.remove("active");
    } else {
      this.settingsPanel.style.display = "block";
      this.settingsBtn.classList.add("active");

      // Hide filter panel if open
      if (this.filterPanel) {
        this.filterPanel.style.display = "none";
        this.filterBtn?.classList.remove("active");
      }
    }
  }

  async loadActivities() {
    if (this.isLoading) return;

    this.setLoading(true);

    try {
      // TEMPORARILY DISABLED: API call for interface configuration
      // TODO: Replace with actual API call when database is ready
      // const response = await fetch("includes/functions/audit_functions.php", {
      //   method: "POST",
      //   headers: {
      //     "Content-Type": "application/x-www-form-urlencoded",
      //   },
      //   body: new URLSearchParams({
      //     action: "get_activities",
      //     filters: JSON.stringify(this.currentFilters),
      //     limit: this.currentSettings.maxActivities,
      //   }),
      // });

      // if (!response.ok) {
      //   throw new Error("Failed to load activities");
      // }

      // const data = await response.json();

      // if (data.success) {
      //   this.renderActivities(data.activities);
      //   this.updateStats(data.stats);
      // } else {
      //   console.error("Error loading activities:", data.message);
      //   this.showError("Failed to load activities");
      // }

      // TEMPORARY: Use demo data for interface configuration
      this.showDemoData();
    } catch (error) {
      console.error("Error loading activities:", error);
      this.showError("Network error while loading activities");
    } finally {
      this.setLoading(false);
    }
  }

  async refreshActivities() {
    if (this.refreshBtn) {
      const icon = this.refreshBtn.querySelector("i");
      if (icon) {
        icon.classList.add("fa-spin");
      }
    }

    await this.loadActivities();

    if (this.refreshBtn) {
      const icon = this.refreshBtn.querySelector("i");
      if (icon) {
        icon.classList.remove("fa-spin");
      }
    }
  }

  // TEMPORARY: Demo data for interface configuration
  showDemoData() {
    const demoActivities = [
      {
        id: 1,
        type: "login",
        title: "User Login",
        description: "Admin user logged into the system",
        user: "John Admin",
        user_id: 1,
        priority: "low",
        timestamp: new Date(Date.now() - 5 * 60 * 1000).toISOString(),
      },
      {
        id: 2,
        type: "create",
        title: "New Student Added",
        description: "Student account created for Maria Santos",
        user: "Admin User",
        user_id: 1,
        priority: "medium",
        timestamp: new Date(Date.now() - 15 * 60 * 1000).toISOString(),
      },
      {
        id: 3,
        type: "update",
        title: "Faculty Record Updated",
        description: "Faculty information updated for Dr. Smith",
        user: "System Admin",
        user_id: 2,
        priority: "medium",
        timestamp: new Date(Date.now() - 30 * 60 * 1000).toISOString(),
      },
      {
        id: 4,
        type: "export",
        title: "Data Export",
        description: "Student records exported to CSV format",
        user: "Admin User",
        user_id: 1,
        priority: "low",
        timestamp: new Date(Date.now() - 60 * 60 * 1000).toISOString(),
      },
      {
        id: 5,
        type: "clearance",
        title: "Clearance Approved",
        description: "Student clearance request approved",
        user: "Faculty Head",
        user_id: 3,
        priority: "high",
        timestamp: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString(),
      },
    ];

    const demoStats = {
      total_activities: 5,
      high_priority: 1,
      medium_priority: 2,
      low_priority: 2,
    };

    this.renderActivities(demoActivities);
    this.updateStats(demoStats);
  }

  renderActivities(activities) {
    if (!this.activityList) return;

    if (!activities || activities.length === 0) {
      this.activityList.innerHTML = `
                <div class="no-activities">
                    <i class="fas fa-inbox"></i>
                    <p>No activities to display</p>
                </div>
            `;
      return;
    }

    const activitiesHTML = activities
      .map((activity) => this.renderActivityItem(activity))
      .join("");
    this.activityList.innerHTML = activitiesHTML;

    // Update activity count in footer
    const activityCount = this.container?.querySelector(".activity-count");
    if (activityCount) {
      activityCount.textContent = activities.length;
    }
  }

  renderActivityItem(activity) {
    const priorityClass = `priority-${activity.priority.toLowerCase()}`;
    const priorityText =
      activity.priority.charAt(0).toUpperCase() + activity.priority.slice(1);
    const iconClass = this.getActivityIcon(activity.type);
    const relativeTime = this.getRelativeTime(activity.timestamp);

    return `
            <div class="activity-item ${priorityClass}" data-activity-id="${
      activity.id
    }">
                <div class="activity-icon">
                    <i class="${iconClass}"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-header">
                        <h4 class="activity-title">${this.escapeHtml(
                          activity.title
                        )}</h4>
                        <span class="activity-priority-badge ${priorityClass}">${priorityText}</span>
                    </div>
                    <p class="activity-description">${this.escapeHtml(
                      activity.description
                    )}</p>
                    <div class="activity-meta">
                        <span class="activity-user">
                            <i class="fas fa-user"></i>
                            ${this.escapeHtml(activity.user_name)}
                        </span>
                        <span class="activity-time">
                            <i class="fas fa-clock"></i>
                            ${relativeTime}
                        </span>
                    </div>
                </div>
                <div class="activity-actions">
                    <button class="activity-details-btn" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
        `;
  }

  getActivityIcon(type) {
    const iconMap = {
      login: "fas fa-sign-in-alt",
      logout: "fas fa-sign-out-alt",
      create: "fas fa-plus",
      update: "fas fa-edit",
      delete: "fas fa-trash",
      export: "fas fa-download",
      import: "fas fa-upload",
      approve: "fas fa-check",
      reject: "fas fa-times",
      clearance: "fas fa-clipboard-check",
      default: "fas fa-info-circle",
    };

    return iconMap[type.toLowerCase()] || iconMap.default;
  }

  getRelativeTime(timestamp) {
    const now = new Date();
    const activityTime = new Date(timestamp);
    const diffMs = now - activityTime;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return "Just now";
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;

    return activityTime.toLocaleDateString();
  }

  updateStats(stats) {
    if (!this.statsSummary) return;

    const statItems = this.statsSummary.querySelectorAll(".stat-item");

    statItems.forEach((item, index) => {
      const statNumber = item.querySelector(".stat-number");
      const statLabel = item.querySelector(".stat-label");

      if (statNumber && statLabel) {
        switch (index) {
          case 0: // Total
            statNumber.textContent = stats.total || 0;
            statNumber.className = "stat-number";
            break;
          case 1: // High Priority
            statNumber.textContent = stats.high || 0;
            statNumber.className = "stat-number priority-high";
            break;
          case 2: // Medium Priority
            statNumber.textContent = stats.medium || 0;
            statNumber.className = "stat-number priority-medium";
            break;
          case 3: // Low Priority
            statNumber.textContent = stats.low || 0;
            statNumber.className = "stat-number priority-low";
            break;
        }
      }
    });
  }

  applyFilters() {
    if (!this.filterForm) return;

    const formData = new FormData(this.filterForm);

    this.currentFilters = {
      type: formData.get("activity_type") || "",
      priority: formData.get("priority") || "",
      user: formData.get("user") || "",
      dateRange: formData.get("date_range") || "",
    };

    this.loadActivities();
    this.toggleFilterPanel();
  }

  resetFilters() {
    if (!this.filterForm) return;

    this.filterForm.reset();
    this.currentFilters = {
      type: "",
      priority: "",
      user: "",
      dateRange: "",
    };

    this.loadActivities();
  }

  saveSettings() {
    if (!this.settingsForm) return;

    const formData = new FormData(this.settingsForm);

    this.currentSettings = {
      autoRefresh: formData.get("auto_refresh") === "on",
      refreshInterval: parseInt(formData.get("refresh_interval")) || 30,
      maxActivities: parseInt(formData.get("max_activities")) || 10,
      showPriority: formData.get("show_priority") === "on",
      showUserInfo: formData.get("show_user_info") === "on",
      showTimestamps: formData.get("show_timestamps") === "on",
    };

    localStorage.setItem(
      "activityTrackerSettings",
      JSON.stringify(this.currentSettings)
    );

    this.applySettings();
    this.toggleSettingsPanel();

    this.showSuccess("Settings saved successfully");
  }

  resetSettings() {
    this.currentSettings = {
      autoRefresh: true,
      refreshInterval: 30,
      maxActivities: 10,
      showPriority: true,
      showUserInfo: true,
      showTimestamps: true,
    };

    localStorage.removeItem("activityTrackerSettings");
    this.applySettings();
    this.showSuccess("Settings reset to default");
  }

  loadSettings() {
    const savedSettings = localStorage.getItem("activityTrackerSettings");
    if (savedSettings) {
      try {
        this.currentSettings = {
          ...this.currentSettings,
          ...JSON.parse(savedSettings),
        };
      } catch (error) {
        console.error("Error loading settings:", error);
      }
    }

    this.applySettings();
  }

  applySettings() {
    // Update form values
    if (this.settingsForm) {
      const autoRefreshCheckbox = this.settingsForm.querySelector(
        'input[name="auto_refresh"]'
      );
      const refreshIntervalSelect = this.settingsForm.querySelector(
        'select[name="refresh_interval"]'
      );
      const maxActivitiesSelect = this.settingsForm.querySelector(
        'select[name="max_activities"]'
      );
      const showPriorityCheckbox = this.settingsForm.querySelector(
        'input[name="show_priority"]'
      );
      const showUserInfoCheckbox = this.settingsForm.querySelector(
        'input[name="show_user_info"]'
      );
      const showTimestampsCheckbox = this.settingsForm.querySelector(
        'input[name="show_timestamps"]'
      );

      if (autoRefreshCheckbox)
        autoRefreshCheckbox.checked = this.currentSettings.autoRefresh;
      if (refreshIntervalSelect)
        refreshIntervalSelect.value = this.currentSettings.refreshInterval;
      if (maxActivitiesSelect)
        maxActivitiesSelect.value = this.currentSettings.maxActivities;
      if (showPriorityCheckbox)
        showPriorityCheckbox.checked = this.currentSettings.showPriority;
      if (showUserInfoCheckbox)
        showUserInfoCheckbox.checked = this.currentSettings.showUserInfo;
      if (showTimestampsCheckbox)
        showTimestampsCheckbox.checked = this.currentSettings.showTimestamps;
    }

    // Apply visual settings
    this.applyVisualSettings();

    // Restart auto-refresh with new interval
    this.startAutoRefresh();
  }

  applyVisualSettings() {
    if (!this.container) return;

    // Show/hide priority badges
    const priorityBadges = this.container.querySelectorAll(
      ".activity-priority-badge"
    );
    priorityBadges.forEach((badge) => {
      badge.style.display = this.currentSettings.showPriority
        ? "inline-block"
        : "none";
    });

    // Show/hide user info
    const userInfo = this.container.querySelectorAll(".activity-user");
    userInfo.forEach((info) => {
      info.style.display = this.currentSettings.showUserInfo ? "flex" : "none";
    });

    // Show/hide timestamps
    const timestamps = this.container.querySelectorAll(".activity-time");
    timestamps.forEach((timestamp) => {
      timestamp.style.display = this.currentSettings.showTimestamps
        ? "flex"
        : "none";
    });
  }

  startAutoRefresh() {
    if (this.refreshInterval) {
      clearInterval(this.refreshInterval);
    }

    if (this.currentSettings.autoRefresh) {
      this.refreshInterval = setInterval(() => {
        this.loadActivities();
      }, this.currentSettings.refreshInterval * 1000);
    }
  }

  setLoading(loading) {
    this.isLoading = loading;

    if (loading) {
      this.container?.classList.add("loading");
    } else {
      this.container?.classList.remove("loading");
    }
  }

  async showActivityDetails(activityId) {
    try {
      // TEMPORARILY DISABLED: API call for interface configuration
      // TODO: Replace with actual API call when database is ready
      // const response = await fetch("includes/functions/audit_functions.php", {
      //   method: "POST",
      //   headers: {
      //     "Content-Type": "application/x-www-form-urlencoded",
      //   },
      //   body: new URLSearchParams({
      //     action: "get_activity_details",
      //     activity_id: activityId,
      //   }),
      // });

      // if (!response.ok) {
      //   throw new Error("Failed to load activity details");
      // }

      // const data = await response.json();

      // if (data.success) {
      //   this.renderActivityModal(data.activity);
      // } else {
      //   this.showError("Failed to load activity details");
      // }

      // TEMPORARY: Use demo data for interface configuration
      this.showDemoActivityDetails(activityId);
    } catch (error) {
      console.error("Error loading activity details:", error);
      this.showError("Network error while loading activity details");
    }
  }

  // TEMPORARY: Demo activity details for interface configuration
  showDemoActivityDetails(activityId) {
    const demoActivities = {
      1: {
        id: 1,
        type: "login",
        title: "User Login",
        description: "Admin user logged into the system",
        priority: "low",
        user_name: "John Admin",
        user_role: "Administrator",
        ip_address: "192.168.1.100",
        user_agent:
          "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
        timestamp: new Date(Date.now() - 5 * 60 * 1000).toISOString(),
      },
      2: {
        id: 2,
        type: "create",
        title: "New Student Added",
        description: "Student account created for Maria Santos",
        priority: "medium",
        user_name: "Admin User",
        user_role: "Administrator",
        ip_address: "192.168.1.101",
        user_agent:
          "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
        timestamp: new Date(Date.now() - 15 * 60 * 1000).toISOString(),
      },
      3: {
        id: 3,
        type: "update",
        title: "Faculty Record Updated",
        description: "Faculty information updated for Dr. Smith",
        priority: "medium",
        user_name: "System Admin",
        user_role: "System Administrator",
        ip_address: "192.168.1.102",
        user_agent:
          "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
        timestamp: new Date(Date.now() - 30 * 60 * 1000).toISOString(),
      },
      4: {
        id: 4,
        type: "export",
        title: "Data Export",
        description: "Student records exported to CSV format",
        priority: "low",
        user_name: "Admin User",
        user_role: "Administrator",
        ip_address: "192.168.1.103",
        user_agent:
          "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
        timestamp: new Date(Date.now() - 60 * 60 * 1000).toISOString(),
      },
      5: {
        id: 5,
        type: "clearance",
        title: "Clearance Approved",
        description: "Student clearance request approved",
        priority: "high",
        user_name: "Faculty Head",
        user_role: "Faculty Head",
        ip_address: "192.168.1.104",
        user_agent:
          "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
        timestamp: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString(),
      },
    };

    const activity = demoActivities[activityId];
    if (activity) {
      this.renderActivityModal(activity);
    } else {
      this.showError("Activity not found");
    }
  }

  renderActivityModal(activity) {
    const modal = document.createElement("div");
    modal.className = "modal";
    modal.style.display = "block";

    const priorityClass = `priority-${activity.priority.toLowerCase()}`;
    const priorityText =
      activity.priority.charAt(0).toUpperCase() + activity.priority.slice(1);

    modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3><i class="fas fa-info-circle"></i> Activity Details</h3>
                    <button class="modal-close">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="activity-details-content">
                        <div class="detail-row">
                            <label>Title:</label>
                            <span>${this.escapeHtml(activity.title)}</span>
                        </div>
                        <div class="detail-row">
                            <label>Description:</label>
                            <span>${this.escapeHtml(
                              activity.description
                            )}</span>
                        </div>
                        <div class="detail-row">
                            <label>Type:</label>
                            <span>${this.escapeHtml(activity.type)}</span>
                        </div>
                        <div class="detail-row">
                            <label>Priority:</label>
                            <span class="priority-badge ${priorityClass}">${priorityText}</span>
                        </div>
                        <div class="detail-row">
                            <label>User:</label>
                            <span>${this.escapeHtml(activity.user_name)}</span>
                        </div>
                        <div class="detail-row">
                            <label>Role:</label>
                            <span>${this.escapeHtml(activity.user_role)}</span>
                        </div>
                        <div class="detail-row">
                            <label>IP Address:</label>
                            <span>${this.escapeHtml(
                              activity.ip_address || "N/A"
                            )}</span>
                        </div>
                        <div class="detail-row">
                            <label>User Agent:</label>
                            <span>${this.escapeHtml(
                              activity.user_agent || "N/A"
                            )}</span>
                        </div>
                        <div class="detail-row">
                            <label>Timestamp:</label>
                            <span>${new Date(
                              activity.timestamp
                            ).toLocaleString()}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;

    document.body.appendChild(modal);
  }

  closeModal(modal) {
    modal.style.display = "none";
    setTimeout(() => {
      if (modal.parentNode) {
        modal.parentNode.removeChild(modal);
      }
    }, 300);
  }

  showSuccess(message) {
    // You can integrate this with your existing alert system
    console.log("Success:", message);
  }

  showError(message) {
    // You can integrate this with your existing alert system
    console.error("Error:", message);
  }

  escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }

  destroy() {
    if (this.refreshInterval) {
      clearInterval(this.refreshInterval);
    }

    // Remove event listeners
    this.container?.removeEventListener("click", this.handleClick);
  }

  handleResize() {
    console.log("handleResize called, current width:", window.innerWidth);

    const wasMobile = this.isMobile;
    this.isMobile = window.innerWidth <= 992;

    console.log("Resize state change:", {
      wasMobile,
      isMobile: this.isMobile,
      windowWidth: window.innerWidth,
    });

    // If switching between mobile and desktop
    if (wasMobile !== this.isMobile) {
      console.log("Switching between mobile and desktop");

      if (this.isMobile) {
        // Switching to mobile - hide sidebar by default
        console.log("Switching to mobile, hiding sidebar");
        this.dashboardSidebar.classList.remove("show");
        if (this.backdrop) {
          this.backdrop.classList.remove("active");
        }
        this.isVisible = false;
        if (this.toggleBtn) {
          this.toggleBtn.style.display = "flex"; // Show toggle button on mobile
          this.toggleBtn.style.setProperty("display", "flex", "important"); // Force visibility
        }
        this.updateToggleButton();
      } else {
        // Switching to desktop - show sidebar
        console.log("Switching to desktop, showing sidebar");
        this.dashboardSidebar.classList.remove("show"); // Ensure it's not 'show' from mobile state
        if (this.backdrop) {
          this.backdrop.classList.remove("active"); // Hide backdrop
        }
        // Don't change isVisible state when switching to desktop - let it maintain its current state
        if (this.toggleBtn) {
          this.toggleBtn.style.display = "none"; // Hide toggle button on desktop
        }
        this.updateToggleButton();
      }
    }
  }

  toggleMobileView() {
    console.log("toggleMobileView called, current state:", {
      isVisible: this.isVisible,
      dashboardSidebar: !!this.dashboardSidebar,
      backdrop: !!this.backdrop,
    });

    // Re-find the dashboard sidebar element in case it wasn't found during initialization
    if (!this.dashboardSidebar) {
      this.dashboardSidebar = document.querySelector(".dashboard-sidebar");
      console.log("Re-finding dashboardSidebar:", !!this.dashboardSidebar);
    }

    if (!this.dashboardSidebar) {
      console.error("dashboardSidebar not found - cannot toggle mobile view");
      return;
    }

    this.isVisible = !this.isVisible;
    console.log("Toggling to:", this.isVisible);

    if (this.isVisible) {
      this.dashboardSidebar.classList.add("show");
      if (this.backdrop) {
        this.backdrop.classList.add("active");
      }
      if (this.toggleBtn) {
        this.toggleBtn.style.display = "none"; // Hide toggle button when sidebar is open
      }
      console.log("Sidebar shown, backdrop active, toggle button hidden");
    } else {
      this.dashboardSidebar.classList.remove("show");
      if (this.backdrop) {
        this.backdrop.classList.remove("active");
      }
      if (this.toggleBtn) {
        this.toggleBtn.style.display = "flex"; // Show toggle button when sidebar is closed
        this.toggleBtn.style.setProperty("display", "flex", "important"); // Force visibility
      }
      console.log("Sidebar hidden, backdrop inactive, toggle button shown");
    }

    this.updateToggleButton();
    this.saveMobileState();
  }

  updateToggleButton() {
    console.log("updateToggleButton called, isVisible:", this.isVisible);

    if (!this.toggleBtn || !this.toggleIcon) {
      console.error("Toggle button or icon not found");
      return;
    }

    if (this.isVisible) {
      this.toggleBtn.classList.add("showing");
      this.toggleIcon.className = "fas fa-times";
      this.toggleBtn.title = "Hide Activity Tracker";
      console.log("Toggle button updated to show state");
    } else {
      this.toggleBtn.classList.remove("showing");
      this.toggleIcon.className = "fas fa-chart-line";
      this.toggleBtn.title = "Show Activity Tracker";
      console.log("Toggle button updated to hide state");
    }
  }

  // Add touch gesture support for mobile
  addTouchSupport() {
    if (!this.toggleBtn || !this.isMobile || this.touchSupportAdded) return;

    let startY = 0;
    let startX = 0;
    let isDragging = false;

    this.toggleBtn.addEventListener("touchstart", (e) => {
      startY = e.touches[0].clientY;
      startX = e.touches[0].clientX;
      isDragging = false;
    });

    this.toggleBtn.addEventListener("touchmove", (e) => {
      if (!isDragging) {
        const deltaY = Math.abs(e.touches[0].clientY - startY);
        const deltaX = Math.abs(e.touches[0].clientX - startX);

        if (deltaY > 10 || deltaX > 10) {
          isDragging = true;
        }
      }
    });

    this.toggleBtn.addEventListener("touchend", (e) => {
      if (!isDragging) {
        this.toggleMobileView();
      }
    });

    this.touchSupportAdded = true;
  }

  // Save mobile state to localStorage
  saveMobileState() {
    if (this.isMobile) {
      localStorage.setItem("activityTrackerVisible", this.isVisible.toString());
    }
  }

  // Test method to verify toggle functionality
  testToggle() {
    console.log("Testing toggle functionality...");
    console.log("Current state:", {
      isMobile: this.isMobile,
      isVisible: this.isVisible,
      toggleBtn: !!this.toggleBtn,
      dashboardSidebar: !!this.dashboardSidebar,
      backdrop: !!this.backdrop,
    });

    if (this.isMobile && this.toggleBtn) {
      console.log("Toggle button found, testing click...");
      this.toggleMobileView();
    } else {
      console.log("Cannot test toggle - missing elements or not mobile");
    }
  }

  // Load mobile state from localStorage
  loadMobileState() {
    if (this.isMobile) {
      const savedState = localStorage.getItem("activityTrackerVisible");
      if (savedState !== null) {
        this.isVisible = savedState === "true";
        if (this.isVisible && this.dashboardSidebar) {
          this.dashboardSidebar.classList.add("show");
        }
        this.updateToggleButton();
      }
    }
  }

  // Filter activities by type
  filterActivities(filterType) {
    console.log("Filtering activities by:", filterType);

    const activityItems = document.querySelectorAll(".activity-item");

    activityItems.forEach((item) => {
      const activityType = item.getAttribute("data-type") || "all";

      if (filterType === "all" || activityType === filterType) {
        item.style.display = "flex";
      } else {
        item.style.display = "none";
      }
    });

    // Update activity count
    this.updateActivityCount();
  }

  // Refresh activities
  refreshActivities() {
    console.log("Refreshing activities...");

    // Show loading state
    const refreshBtn = document.querySelector(
      ".quick-action-btn[title*='Refresh']"
    );
    if (refreshBtn) {
      const originalText = refreshBtn.innerHTML;
      refreshBtn.innerHTML =
        '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
      refreshBtn.disabled = true;

      // Simulate refresh delay
      setTimeout(() => {
        refreshBtn.innerHTML = originalText;
        refreshBtn.disabled = false;

        // Update last updated time
        const statusElement = document.querySelector(".activity-status small");
        if (statusElement) {
          statusElement.textContent = `Last updated: ${new Date().toLocaleString(
            "en-US",
            {
              month: "short",
              day: "numeric",
              hour: "numeric",
              minute: "2-digit",
              hour12: true,
            }
          )}`;
        }

        console.log("Activities refreshed");
      }, 1500);
    }
  }

  // Export activities
  exportActivities() {
    console.log("Exporting activities...");

    // Show loading state
    const exportBtn = document.querySelector(
      ".quick-action-btn[title*='Export']"
    );
    if (exportBtn) {
      const originalText = exportBtn.innerHTML;
      exportBtn.innerHTML =
        '<i class="fas fa-spinner fa-spin"></i> Exporting...';
      exportBtn.disabled = true;

      // Simulate export delay
      setTimeout(() => {
        exportBtn.innerHTML = originalText;
        exportBtn.disabled = false;

        // Show success message (you can replace this with actual export logic)
        console.log("Activities exported successfully");

        // You can add a toast notification here
        if (typeof showAlert !== "undefined") {
          showAlert("Activities exported successfully!", "success");
        }
      }, 2000);
    }
  }

  // Update activity count
  updateActivityCount() {
    const visibleActivities = document.querySelectorAll(
      '.activity-item[style*="flex"]'
    ).length;
    const totalActivities = document.querySelectorAll(".activity-item").length;

    // Update quick stats
    const totalStat = document.querySelector(
      ".quick-stat-item:first-child .quick-stat-number"
    );
    if (totalStat) {
      totalStat.textContent = visibleActivities;
    }

    console.log(
      `Showing ${visibleActivities} of ${totalActivities} activities`
    );
  }
}

// Initialize activity tracker when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  new ActivityTracker();
});

// Export for use in other modules
window.ActivityTracker = ActivityTracker;
