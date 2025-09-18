/**
 * Grace Period Manager
 * Handles grace period countdown timers and UI updates
 */

class GracePeriodManager {
  constructor() {
    this.activeTimers = new Map();
    this.callbacks = new Map();
    this.updateInterval = 1000; // Update every second
  }

  /**
   * Start a grace period countdown
   * @param {string} periodId - Unique identifier for the period
   * @param {number} remainingSeconds - Seconds remaining in grace period
   * @param {function} onUpdate - Callback for countdown updates
   * @param {function} onComplete - Callback when grace period ends
   */
  startGracePeriod(periodId, remainingSeconds, onUpdate, onComplete) {
    // Clear any existing timer for this period
    this.stopGracePeriod(periodId);

    if (remainingSeconds <= 0) {
      if (onComplete) onComplete();
      return;
    }

    // Store callbacks
    this.callbacks.set(periodId, { onUpdate, onComplete });

    // Start countdown
    const timer = setInterval(() => {
      remainingSeconds--;

      // Update UI
      if (onUpdate) {
        onUpdate(remainingSeconds, this.formatTime(remainingSeconds));
      }

      // Check if grace period ended
      if (remainingSeconds <= 0) {
        this.stopGracePeriod(periodId);
        if (onComplete) onComplete();
      }
    }, this.updateInterval);

    this.activeTimers.set(periodId, timer);

    // Initial update
    if (onUpdate) {
      onUpdate(remainingSeconds, this.formatTime(remainingSeconds));
    }
  }

  /**
   * Stop a grace period countdown
   * @param {string} periodId - Unique identifier for the period
   */
  stopGracePeriod(periodId) {
    const timer = this.activeTimers.get(periodId);
    if (timer) {
      clearInterval(timer);
      this.activeTimers.delete(periodId);
    }
    this.callbacks.delete(periodId);
  }

  /**
   * Stop all active grace periods
   */
  stopAllGracePeriods() {
    for (const [periodId, timer] of this.activeTimers) {
      clearInterval(timer);
    }
    this.activeTimers.clear();
    this.callbacks.clear();
  }

  /**
   * Format seconds into readable time string
   * @param {number} seconds - Seconds to format
   * @returns {string} Formatted time string
   */
  formatTime(seconds) {
    if (seconds <= 0) return "00:00";

    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;

    return `${minutes.toString().padStart(2, "0")}:${remainingSeconds
      .toString()
      .padStart(2, "0")}`;
  }

  /**
   * Check if a grace period is active
   * @param {string} periodId - Unique identifier for the period
   * @returns {boolean} True if grace period is active
   */
  isGracePeriodActive(periodId) {
    return this.activeTimers.has(periodId);
  }

  /**
   * Get active grace period count
   * @returns {number} Number of active grace periods
   */
  getActiveCount() {
    return this.activeTimers.size;
  }
}

/**
 * Grace Period UI Manager
 * Handles UI updates for grace periods
 */
class GracePeriodUIManager {
  constructor() {
    this.gracePeriodManager = new GracePeriodManager();
    this.gracePeriodElements = new Map();
  }

  /**
   * Initialize grace period UI for a clearance period
   * @param {string} periodId - Unique identifier for the period
   * @param {Object} gracePeriodData - Grace period data from API
   */
  initializeGracePeriod(periodId, gracePeriodData) {
    if (!gracePeriodData || !gracePeriodData.is_active) {
      this.hideGracePeriodUI(periodId);
      return;
    }

    const remainingSeconds = gracePeriodData.remaining_seconds;

    // Create or update grace period UI
    this.createGracePeriodUI(periodId, remainingSeconds);

    // Start countdown
    this.gracePeriodManager.startGracePeriod(
      periodId,
      remainingSeconds,
      (seconds, formattedTime) =>
        this.updateGracePeriodUI(periodId, seconds, formattedTime),
      () => this.onGracePeriodComplete(periodId)
    );
  }

  /**
   * Create grace period UI elements
   * @param {string} periodId - Unique identifier for the period
   * @param {number} remainingSeconds - Seconds remaining
   */
  createGracePeriodUI(periodId, remainingSeconds) {
    // Find or create grace period container
    let container = document.getElementById(`grace-period-${periodId}`);
    if (!container) {
      container = document.createElement("div");
      container.id = `grace-period-${periodId}`;
      container.className = "grace-period-container";

      // Insert at the top of the clearance content
      const clearanceContent =
        document.querySelector(".clearance-content") || document.body;
      clearanceContent.insertBefore(container, clearanceContent.firstChild);
    }

    container.innerHTML = `
            <div class="grace-period-banner">
                <div class="grace-period-content">
                    <div class="grace-period-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="grace-period-text">
                        <div class="grace-period-title">Grace Period Active</div>
                        <div class="grace-period-description">
                            Clearance period is transitioning. Applications will be enabled in:
                        </div>
                    </div>
                    <div class="grace-period-countdown">
                        <div class="countdown-timer" id="countdown-${periodId}">
                            ${this.gracePeriodManager.formatTime(
                              remainingSeconds
                            )}
                        </div>
                    </div>
                </div>
                <div class="grace-period-progress">
                    <div class="progress-bar" id="progress-${periodId}"></div>
                </div>
            </div>
        `;

    // Store reference
    this.gracePeriodElements.set(periodId, {
      container,
      countdown: document.getElementById(`countdown-${periodId}`),
      progress: document.getElementById(`progress-${periodId}`),
    });

    // Show container
    container.style.display = "block";

    // Add CSS if not already added
    this.addGracePeriodCSS();
  }

  /**
   * Update grace period UI
   * @param {string} periodId - Unique identifier for the period
   * @param {number} seconds - Seconds remaining
   * @param {string} formattedTime - Formatted time string
   */
  updateGracePeriodUI(periodId, seconds, formattedTime) {
    const elements = this.gracePeriodElements.get(periodId);
    if (!elements) return;

    // Update countdown
    if (elements.countdown) {
      elements.countdown.textContent = formattedTime;
    }

    // Update progress bar
    if (elements.progress) {
      const gracePeriodData = this.getGracePeriodData(periodId);
      if (gracePeriodData) {
        const totalSeconds = gracePeriodData.duration_minutes * 60;
        const progress = ((totalSeconds - seconds) / totalSeconds) * 100;
        elements.progress.style.width = `${Math.min(
          100,
          Math.max(0, progress)
        )}%`;
      }
    }

    // Add urgency styling for last 30 seconds
    if (seconds <= 30) {
      elements.container.classList.add("urgent");
    }
  }

  /**
   * Handle grace period completion
   * @param {string} periodId - Unique identifier for the period
   */
  onGracePeriodComplete(periodId) {
    const elements = this.gracePeriodElements.get(periodId);
    if (elements) {
      // Show completion message briefly
      elements.container.innerHTML = `
                <div class="grace-period-banner completed">
                    <div class="grace-period-content">
                        <div class="grace-period-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="grace-period-text">
                            <div class="grace-period-title">Grace Period Complete</div>
                            <div class="grace-period-description">
                                Clearance period is now active. You can apply to signatories.
                            </div>
                        </div>
                    </div>
                </div>
            `;

      // Hide after 3 seconds
      setTimeout(() => {
        this.hideGracePeriodUI(periodId);
        // Refresh clearance data
        this.refreshClearanceData(periodId);
      }, 3000);
    }
  }

  /**
   * Hide grace period UI
   * @param {string} periodId - Unique identifier for the period
   */
  hideGracePeriodUI(periodId) {
    const elements = this.gracePeriodElements.get(periodId);
    if (elements && elements.container) {
      elements.container.style.display = "none";
    }
    this.gracePeriodElements.delete(periodId);
  }

  /**
   * Add grace period CSS styles
   */
  addGracePeriodCSS() {
    if (document.getElementById("grace-period-styles")) return;

    const style = document.createElement("style");
    style.id = "grace-period-styles";
    style.textContent = `
            .grace-period-container {
                margin-bottom: 20px;
                animation: slideDown 0.3s ease-out;
            }

            .grace-period-banner {
                background: linear-gradient(135deg, #ffc107, #ff9800);
                color: #fff;
                border-radius: 8px;
                padding: 16px;
                box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
                position: relative;
                overflow: hidden;
            }

            .grace-period-banner.urgent {
                background: linear-gradient(135deg, #f44336, #d32f2f);
                animation: pulse 1s infinite;
            }

            .grace-period-banner.completed {
                background: linear-gradient(135deg, #4caf50, #388e3c);
            }

            .grace-period-content {
                display: flex;
                align-items: center;
                gap: 16px;
                position: relative;
                z-index: 2;
            }

            .grace-period-icon {
                font-size: 24px;
                opacity: 0.9;
            }

            .grace-period-text {
                flex: 1;
            }

            .grace-period-title {
                font-weight: 600;
                font-size: 16px;
                margin-bottom: 4px;
            }

            .grace-period-description {
                font-size: 14px;
                opacity: 0.9;
            }

            .grace-period-countdown {
                text-align: center;
            }

            .countdown-timer {
                font-family: 'Courier New', monospace;
                font-size: 24px;
                font-weight: bold;
                background: rgba(255, 255, 255, 0.2);
                padding: 8px 16px;
                border-radius: 6px;
                min-width: 80px;
            }

            .grace-period-progress {
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                height: 3px;
                background: rgba(255, 255, 255, 0.2);
            }

            .progress-bar {
                height: 100%;
                background: rgba(255, 255, 255, 0.8);
                transition: width 1s linear;
                width: 0%;
            }

            @keyframes slideDown {
                from {
                    opacity: 0;
                    transform: translateY(-20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            @keyframes pulse {
                0%, 100% {
                    transform: scale(1);
                }
                50% {
                    transform: scale(1.02);
                }
            }

            /* Responsive design */
            @media (max-width: 768px) {
                .grace-period-content {
                    flex-direction: column;
                    text-align: center;
                    gap: 12px;
                }

                .grace-period-countdown {
                    order: -1;
                }

                .countdown-timer {
                    font-size: 20px;
                }
            }
        `;
    document.head.appendChild(style);
  }

  /**
   * Get grace period data (placeholder - should be implemented based on your data structure)
   * @param {string} periodId - Unique identifier for the period
   * @returns {Object|null} Grace period data
   */
  getGracePeriodData(periodId) {
    // This should return the grace period data for the given period
    // For now, return a default structure
    return {
      duration_minutes: 5,
    };
  }

  /**
   * Refresh clearance data after grace period
   * @param {string} periodId - Unique identifier for the period
   */
  refreshClearanceData(periodId) {
    // Trigger a refresh of the clearance data
    if (typeof window.refreshClearanceData === "function") {
      window.refreshClearanceData();
    } else if (typeof window.loadClearanceData === "function") {
      window.loadClearanceData();
    }
  }

  /**
   * Cleanup all grace period UI
   */
  cleanup() {
    this.gracePeriodManager.stopAllGracePeriods();
    this.gracePeriodElements.clear();
  }
}

// Global instance
window.gracePeriodUIManager = new GracePeriodUIManager();

// Export for module systems
if (typeof module !== "undefined" && module.exports) {
  module.exports = { GracePeriodManager, GracePeriodUIManager };
}
