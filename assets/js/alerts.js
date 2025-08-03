// Alert System JavaScript
class AlertSystem {
  constructor() {
    this.confirmedAction = null;
    this.toastContainer = null;
    this.init();
  }

  init() {
    // Create toast container if it doesn't exist
    if (!document.getElementById("toastContainer")) {
      this.toastContainer = document.createElement("div");
      this.toastContainer.id = "toastContainer";
      this.toastContainer.className = "toast-container";
      document.body.appendChild(this.toastContainer);
    } else {
      this.toastContainer = document.getElementById("toastContainer");
    }
  }

  // Show confirmation modal
  showConfirmation(
    title,
    message,
    confirmText,
    cancelText,
    onConfirm,
    type = "info"
  ) {
    const modal = document.getElementById("confirmationModal");
    const header = document.getElementById("alertHeader");
    const icon = document.getElementById("alertIcon");
    const titleEl = document.getElementById("alertTitle");
    const messageEl = document.getElementById("alertMessage");
    const confirmBtn = document.getElementById("confirmBtn");
    const cancelBtn = document.getElementById("cancelBtn");

    // Set content
    titleEl.textContent = title;
    messageEl.textContent = message;
    confirmBtn.textContent = confirmText;
    cancelBtn.textContent = cancelText;

    // Set styling based on type
    header.className = `alert-modal-header alert-${type}`;
    icon.className = `fas fa-${this.getIconForType(type)}`;

    // Set button styling
    confirmBtn.className = `btn btn-${
      type === "danger" ? "danger" : "primary"
    }`;

    // Store callback
    this.confirmedAction = onConfirm;

    // Show modal
    modal.style.display = "flex";
    setTimeout(() => modal.classList.add("active"), 10);
  }

  // Close confirmation modal
  closeConfirmation() {
    const modal = document.getElementById("confirmationModal");
    if (modal) {
      modal.classList.remove("active");
      setTimeout(() => (modal.style.display = "none"), 300);
    }
  }

  // Execute confirmed action
  executeConfirmedAction() {
    if (this.confirmedAction) {
      this.confirmedAction();
    }
    this.closeConfirmation();
  }

  // Show toast notification
  showToast(message, type = "info", duration = 3000) {
    const toast = document.createElement("div");
    toast.className = `toast-notification toast-${type}`;

    const iconMap = {
      success: "check-circle",
      error: "exclamation-circle",
      warning: "exclamation-triangle",
      info: "info-circle",
    };

    toast.innerHTML = `
            <i class="fas fa-${iconMap[type]}"></i>
            <span>${message}</span>
            <button class="toast-close" onclick="this.parentElement.remove()">×</button>
        `;

    this.toastContainer.appendChild(toast);

    // Show toast
    setTimeout(() => toast.classList.add("active"), 10);

    // Auto remove
    setTimeout(() => {
      if (toast.parentElement) {
        toast.classList.remove("active");
        setTimeout(() => toast.remove(), 300);
      }
    }, duration);
  }

  // Get icon for alert type
  getIconForType(type) {
    const iconMap = {
      danger: "exclamation-triangle",
      warning: "exclamation-circle",
      info: "info-circle",
    };
    return iconMap[type] || "info-circle";
  }
}

// Initialize alert system
const alertSystem = new AlertSystem();

// Global functions for backward compatibility
window.showConfirmationModal = (
  title,
  message,
  confirmText,
  cancelText,
  onConfirm,
  type
) => {
  alertSystem.showConfirmation(
    title,
    message,
    confirmText,
    cancelText,
    onConfirm,
    type
  );
};

window.closeConfirmationModal = () => {
  alertSystem.closeConfirmation();
};

window.executeConfirmedAction = () => {
  alertSystem.executeConfirmedAction();
};

window.showToastNotification = (message, type, duration) => {
  alertSystem.showToast(message, type, duration);
};

// Additional convenience functions
window.showSuccessToast = (message, duration) => {
  alertSystem.showToast(message, "success", duration);
};

window.showErrorToast = (message, duration) => {
  alertSystem.showToast(message, "error", duration);
};

window.showWarningToast = (message, duration) => {
  alertSystem.showToast(message, "warning", duration);
};

window.showInfoToast = (message, duration) => {
  alertSystem.showToast(message, "info", duration);
};

// Backward compatibility aliases
window.showConfirmation = (
  title,
  message,
  confirmText,
  cancelText,
  onConfirm,
  type
) => {
  alertSystem.showConfirmation(
    title,
    message,
    confirmText,
    cancelText,
    onConfirm,
    type
  );
};

window.showToast = (message, type, duration) => {
  alertSystem.showToast(message, type, duration);
};
