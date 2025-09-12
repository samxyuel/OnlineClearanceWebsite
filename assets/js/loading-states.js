/**
 * Loading States Manager - Online Clearance Website
 * Centralized loading state management system
 */

class LoadingStatesManager {
  constructor(config = {}) {
    this.config = {
      theme: "primary",
      animation: "spin",
      duration: 300,
      timeout: 30000,
      showProgress: true,
      enableSound: false,
      ...config,
    };

    this.activeLoadings = new Set();
    this.timeouts = new Map();

    // Initialize global loading overlay if it doesn't exist
    this.initGlobalOverlay();
  }

  /**
   * Initialize global loading overlay
   */
  initGlobalOverlay() {
    if (!document.getElementById("globalLoadingOverlay")) {
      const overlay = document.createElement("div");
      overlay.id = "globalLoadingOverlay";
      overlay.className = "global-loading-overlay loading-hidden";
      overlay.setAttribute("aria-hidden", "true");
      overlay.innerHTML = `
                <div class="global-loading-content">
                    <div class="loading-spinner ${this.config.animation}"></div>
                    <p class="loading-text">Processing...</p>
                </div>
            `;
      document.body.appendChild(overlay);
    }
  }

  /**
   * Show global loading overlay
   */
  showGlobalLoading(message = "Processing...", theme = null) {
    const overlay = document.getElementById("globalLoadingOverlay");
    if (!overlay) return;

    const themeClass = theme || this.config.theme;
    overlay.className = `global-loading-overlay loading-visible loading-${themeClass}`;
    overlay.setAttribute("aria-hidden", "false");

    const textElement = overlay.querySelector(".loading-text");
    if (textElement) {
      textElement.textContent = message;
    }

    this.activeLoadings.add("global");

    // Auto-hide after timeout
    this.setTimeout("global", () => {
      this.hideGlobalLoading();
    });
  }

  /**
   * Hide global loading overlay
   */
  hideGlobalLoading() {
    const overlay = document.getElementById("globalLoadingOverlay");
    if (!overlay) return;

    overlay.className = "global-loading-overlay loading-hidden";
    overlay.setAttribute("aria-hidden", "true");

    this.activeLoadings.delete("global");
    this.clearTimeout("global");
  }

  /**
   * Show button loading state
   */
  showButtonLoading(buttonId, text = "Loading...", theme = null) {
    const button =
      typeof buttonId === "string"
        ? document.getElementById(buttonId)
        : buttonId;
    if (!button) return;

    const themeClass = theme || this.config.theme;
    button.classList.add("loading", `loading-${themeClass}`);

    // Store original content
    const originalContent = button.innerHTML;
    button.dataset.originalContent = originalContent;

    // Add loading content
    button.innerHTML = `
            <span class="btn-text" style="opacity: 0;">${originalContent}</span>
            <span class="btn-loading">
                <i class="fas fa-spinner loading-spinner ${this.config.animation}"></i>
                ${text}
            </span>
        `;

    this.activeLoadings.add(`button-${buttonId}`);
  }

  /**
   * Hide button loading state
   */
  hideButtonLoading(buttonId) {
    const button =
      typeof buttonId === "string"
        ? document.getElementById(buttonId)
        : buttonId;
    if (!button) return;

    button.classList.remove(
      "loading",
      "loading-primary",
      "loading-secondary",
      "loading-success",
      "loading-warning",
      "loading-danger"
    );

    // Restore original content
    const originalContent = button.dataset.originalContent;
    if (originalContent) {
      button.innerHTML = originalContent;
      delete button.dataset.originalContent;
    }

    this.activeLoadings.delete(`button-${buttonId}`);
  }

  /**
   * Show table loading state
   */
  showTableLoading(tableId, message = "Loading data...") {
    const table =
      typeof tableId === "string" ? document.getElementById(tableId) : tableId;
    if (!table) return;

    const container =
      table.closest(".data-table-container") || table.parentElement;
    container.classList.add("loading");

    // Create loading overlay
    let overlay = container.querySelector(".table-loading-overlay");
    if (!overlay) {
      overlay = document.createElement("div");
      overlay.className = "table-loading-overlay";
      overlay.innerHTML = `
                <div class="table-loading-content">
                    <div class="loading-spinner ${this.config.animation}"></div>
                    <div>${message}</div>
                </div>
            `;
      container.appendChild(overlay);
    }

    this.activeLoadings.add(`table-${tableId}`);
  }

  /**
   * Hide table loading state
   */
  hideTableLoading(tableId) {
    const table =
      typeof tableId === "string" ? document.getElementById(tableId) : tableId;
    if (!table) return;

    const container =
      table.closest(".data-table-container") || table.parentElement;
    container.classList.remove("loading");

    const overlay = container.querySelector(".table-loading-overlay");
    if (overlay) {
      overlay.remove();
    }

    this.activeLoadings.delete(`table-${tableId}`);
  }

  /**
   * Show term item loading state
   */
  showTermLoading(termId, message = "Processing...") {
    const termElement =
      document.querySelector(`[data-term-id="${termId}"]`) ||
      document.querySelector(`.term-item:has([onclick*="${termId}"])`);
    if (!termElement) return;

    termElement.classList.add("loading");
    termElement.dataset.loadingMessage = message;

    this.activeLoadings.add(`term-${termId}`);
  }

  /**
   * Hide term item loading state
   */
  hideTermLoading(termId) {
    const termElement =
      document.querySelector(`[data-term-id="${termId}"]`) ||
      document.querySelector(`.term-item:has([onclick*="${termId}"])`);
    if (!termElement) return;

    termElement.classList.remove("loading");
    delete termElement.dataset.loadingMessage;

    this.activeLoadings.delete(`term-${termId}`);
  }

  /**
   * Show terms list loading state
   */
  showTermsListLoading(message = "Updating terms...") {
    const termsList = document.querySelector(".terms-list");
    if (!termsList) return;

    termsList.classList.add("loading");
    termsList.dataset.loadingMessage = message;

    this.activeLoadings.add("terms-list");
  }

  /**
   * Hide terms list loading state
   */
  hideTermsListLoading() {
    const termsList = document.querySelector(".terms-list");
    if (!termsList) return;

    termsList.classList.remove("loading");
    delete termsList.dataset.loadingMessage;

    this.activeLoadings.delete("terms-list");
  }

  /**
   * Show signatory loading state
   */
  showSignatoryLoading(type, message = "Loading signatories...") {
    const signatoryCard = document
      .querySelector(`#${type}SignatoryList`)
      ?.closest(".signatory-card");
    if (!signatoryCard) return;

    signatoryCard.classList.add("loading");
    signatoryCard.dataset.loadingMessage = message;

    this.activeLoadings.add(`signatory-${type}`);
  }

  /**
   * Hide signatory loading state
   */
  hideSignatoryLoading(type) {
    const signatoryCard = document
      .querySelector(`#${type}SignatoryList`)
      ?.closest(".signatory-card");
    if (!signatoryCard) return;

    signatoryCard.classList.remove("loading");
    delete signatoryCard.dataset.loadingMessage;

    this.activeLoadings.delete(`signatory-${type}`);
  }

  /**
   * Show progress bar
   */
  showProgress(message, percentage = 0, containerId = null) {
    const container = containerId
      ? document.getElementById(containerId)
      : document.body;
    if (!container) return;

    let progressContainer = container.querySelector(".progress-container");
    if (!progressContainer) {
      progressContainer = document.createElement("div");
      progressContainer.className = "progress-container";
      progressContainer.innerHTML = `
                <div class="progress-bar" style="width: 0%"></div>
                <div class="progress-text">0%</div>
            `;
      container.appendChild(progressContainer);
    }

    const progressBar = progressContainer.querySelector(".progress-bar");
    const progressText = progressContainer.querySelector(".progress-text");

    if (progressBar) {
      progressBar.style.width = `${percentage}%`;
    }
    if (progressText) {
      progressText.textContent = `${percentage}% - ${message}`;
    }

    this.activeLoadings.add("progress");
  }

  /**
   * Hide progress bar
   */
  hideProgress(containerId = null) {
    const container = containerId
      ? document.getElementById(containerId)
      : document.body;
    if (!container) return;

    const progressContainer = container.querySelector(".progress-container");
    if (progressContainer) {
      progressContainer.remove();
    }

    this.activeLoadings.delete("progress");
  }

  /**
   * Show toast loading
   */
  showToastLoading(message, persistent = false) {
    // This would integrate with your existing toast system
    if (typeof showToast === "function") {
      showToast(message, "info", persistent);
    }
  }

  /**
   * Hide all loading states
   */
  hideAllLoading() {
    this.activeLoadings.forEach((loadingId) => {
      if (loadingId === "global") {
        this.hideGlobalLoading();
      } else if (loadingId.startsWith("button-")) {
        const buttonId = loadingId.replace("button-", "");
        this.hideButtonLoading(buttonId);
      } else if (loadingId.startsWith("table-")) {
        const tableId = loadingId.replace("table-", "");
        this.hideTableLoading(tableId);
      } else if (loadingId.startsWith("term-")) {
        const termId = loadingId.replace("term-", "");
        this.hideTermLoading(termId);
      } else if (loadingId === "terms-list") {
        this.hideTermsListLoading();
      } else if (loadingId.startsWith("signatory-")) {
        const type = loadingId.replace("signatory-", "");
        this.hideSignatoryLoading(type);
      } else if (loadingId === "progress") {
        this.hideProgress();
      }
    });
  }

  /**
   * Set timeout for loading state
   */
  setTimeout(id, callback, delay = null) {
    const timeoutDelay = delay || this.config.timeout;
    const timeoutId = setTimeout(() => {
      callback();
      this.timeouts.delete(id);
    }, timeoutDelay);

    this.timeouts.set(id, timeoutId);
  }

  /**
   * Clear timeout
   */
  clearTimeout(id) {
    const timeoutId = this.timeouts.get(id);
    if (timeoutId) {
      clearTimeout(timeoutId);
      this.timeouts.delete(id);
    }
  }

  /**
   * Check if any loading states are active
   */
  isLoading() {
    return this.activeLoadings.size > 0;
  }

  /**
   * Get active loading states
   */
  getActiveLoadings() {
    return Array.from(this.activeLoadings);
  }
}

// Create global instance
window.LoadingStates = new LoadingStatesManager();

// Utility functions for easy access
window.showGlobalLoading = (message, theme) =>
  window.LoadingStates.showGlobalLoading(message, theme);
window.hideGlobalLoading = () => window.LoadingStates.hideGlobalLoading();
window.showButtonLoading = (buttonId, text, theme) =>
  window.LoadingStates.showButtonLoading(buttonId, text, theme);
window.hideButtonLoading = (buttonId) =>
  window.LoadingStates.hideButtonLoading(buttonId);
window.showTableLoading = (tableId, message) =>
  window.LoadingStates.showTableLoading(tableId, message);
window.hideTableLoading = (tableId) =>
  window.LoadingStates.hideTableLoading(tableId);
window.showTermsListLoading = (message) =>
  window.LoadingStates.showTermsListLoading(message);
window.hideTermsListLoading = () => window.LoadingStates.hideTermsListLoading();
window.showSignatoryLoading = (type, message) =>
  window.LoadingStates.showSignatoryLoading(type, message);
window.hideSignatoryLoading = (type) =>
  window.LoadingStates.hideSignatoryLoading(type);
window.showProgress = (message, percentage, containerId) =>
  window.LoadingStates.showProgress(message, percentage, containerId);
window.hideProgress = (containerId) =>
  window.LoadingStates.hideProgress(containerId);

// Auto-hide loading states on page unload
window.addEventListener("beforeunload", () => {
  window.LoadingStates.hideAllLoading();
});

// Export for module systems
if (typeof module !== "undefined" && module.exports) {
  module.exports = LoadingStatesManager;
}
