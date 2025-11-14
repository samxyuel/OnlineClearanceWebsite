/**
 * Universal Modal Handler (Refined)
 * Works alongside existing modal close functions, provides fallback for missing ones
 *
 * Features:
 * - Automatically handles X (close) buttons
 * - Intelligently detects Cancel buttons
 * - Respects existing close functions
 * - Provides fallback for modals without close handlers
 * - Handles Escape key
 * - Handles clicking outside modal (on overlay)
 * - Works with dynamically added modals
 */

(function () {
  "use strict";

  /**
   * Check if a button is meant to close/cancel a modal
   * @param {HTMLElement} button - The button element to check
   * @returns {boolean} - True if button is a close/cancel button
   */
  function isCloseButton(button) {
    if (!button) return false;

    const text = (button.textContent || "").trim().toLowerCase();
    const hasCloseText =
      text === "cancel" ||
      text === "close" ||
      text.includes("cancel") ||
      text.includes("close");

    // Check for explicit data attribute
    if (button.dataset.modalAction === "close") return true;
    if (button.dataset.modalAction === "cancel") return false; // Explicitly not a close

    // Check onclick for close function patterns
    const onclick = button.getAttribute("onclick") || "";
    const isCloseFunction = /close.*Modal|closeModal/i.test(onclick);

    // Must be in modal-actions AND have close text OR close function
    const inModalActions = button.closest(".modal-actions");

    return inModalActions && (hasCloseText || isCloseFunction);
  }

  /**
   * Find the parent modal from any element
   * @param {HTMLElement} element - Element to search from
   * @returns {HTMLElement|null} - The parent modal or null
   */
  function findParentModal(element) {
    if (!element) return null;

    let current = element;
    while (current && current !== document.body) {
      if (
        current.classList.contains("modal-overlay") ||
        current.classList.contains("modal") ||
        (current.id && current.id.includes("Modal"))
      ) {
        return current;
      }
      current = current.parentElement;
    }
    return null;
  }

  /**
   * Close modal with fallback to existing close functions
   * @param {HTMLElement} modal - The modal element to close
   */
  function closeModal(modal) {
    if (!modal) return;

    // First, try to trigger existing close function if it exists
    const modalId = modal.id;
    if (modalId) {
      // Try common close function patterns
      // Pattern 1: closeEditFacultyModal -> close + EditFacultyModal
      const closeFuncName =
        "close" + modalId.charAt(0).toUpperCase() + modalId.slice(1);
      if (typeof window[closeFuncName] === "function") {
        try {
          window[closeFuncName]();
          return; // Let existing function handle it
        } catch (e) {
          console.warn("Error calling existing close function:", e);
        }
      }

      // Pattern 2: closeEditFacultyModal -> close + EditFacultyModal (camelCase)
      const camelCaseName = modalId.replace(/-([a-z])/g, (g) =>
        g[1].toUpperCase()
      );
      const closeFunc =
        "close" +
        camelCaseName.charAt(0).toUpperCase() +
        camelCaseName.slice(1);
      if (typeof window[closeFunc] === "function") {
        try {
          window[closeFunc]();
          return;
        } catch (e) {
          console.warn("Error calling existing close function:", e);
        }
      }

      // Pattern 3: Try to find any close function that matches modal ID
      const modalName = modalId.replace(/Modal$/, "").replace(/-/g, "");
      const possibleNames = [
        "close" + modalName.charAt(0).toUpperCase() + modalName.slice(1),
        "close" + modalName,
        "close" + modalId,
      ];

      for (const funcName of possibleNames) {
        if (typeof window[funcName] === "function") {
          try {
            window[funcName]();
            return;
          } catch (e) {
            console.warn("Error calling existing close function:", e);
          }
        }
      }
    }

    // Fallback: close manually
    modal.style.display = "none";
    document.body.style.overflow = "";
    document.body.classList.remove("modal-open");
    modal.classList.remove("active");

    // Trigger custom event for modals that need cleanup
    const closeEvent = new CustomEvent("modal:close", {
      detail: { modal: modal },
      bubbles: true,
    });
    modal.dispatchEvent(closeEvent);
  }

  /**
   * Initialize modal event handlers
   */
  function initModalHandlers() {
    // Handle X (close) buttons - always close
    document.addEventListener("click", function (e) {
      const closeBtn = e.target.closest(".modal-close");
      if (closeBtn) {
        e.preventDefault();
        e.stopPropagation();

        const modal = findParentModal(closeBtn);
        if (modal) {
          // Check if button has onclick - if so, let it run first
          const onclick = closeBtn.getAttribute("onclick");
          if (onclick && onclick.includes("close")) {
            // Let existing handler run, but ensure modal closes
            setTimeout(() => {
              if (
                modal.style.display !== "none" &&
                modal.style.display !== ""
              ) {
                closeModal(modal);
              }
            }, 50);
          } else {
            closeModal(modal);
          }
        }
      }
    });

    // Handle Cancel buttons - only if they're actually cancel buttons
    document.addEventListener("click", function (e) {
      const button = e.target.closest("button");
      if (!button) return;

      // Only process if it's a cancel/close button
      if (!isCloseButton(button)) return;

      // Don't interfere if button has explicit onclick that's not a close function
      const onclick = button.getAttribute("onclick");
      if (onclick && !onclick.includes("close") && !onclick.includes("Close")) {
        return; // Button has other purpose (e.g., resetPassword, sendEmail)
      }

      e.preventDefault();
      e.stopPropagation();

      const modal = findParentModal(button);
      if (modal) {
        // If onclick exists and calls a close function, let it run
        if (
          onclick &&
          (onclick.includes("close") || onclick.includes("Close"))
        ) {
          setTimeout(() => {
            if (modal.style.display !== "none" && modal.style.display !== "") {
              closeModal(modal);
            }
          }, 50);
        } else {
          closeModal(modal);
        }
      }
    });

    // Handle Escape key - close topmost modal
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" || e.keyCode === 27) {
        // Find all visible modals
        const allModals = document.querySelectorAll(
          '.modal-overlay, .modal, [id*="Modal"]'
        );

        const visibleModals = Array.from(allModals).filter((modal) => {
          const style = window.getComputedStyle(modal);
          return style.display !== "none" && style.display !== "";
        });

        if (visibleModals.length > 0) {
          // Close the topmost modal (last in DOM order, typically the most recent)
          const topModal = visibleModals[visibleModals.length - 1];
          closeModal(topModal);
        }
      }
    });

    // Handle clicking outside modal (on overlay)
    document.addEventListener("click", function (e) {
      const target = e.target;

      // Check if click is directly on modal overlay
      if (
        target.classList.contains("modal-overlay") ||
        target.classList.contains("modal")
      ) {
        // Only close if clicking directly on overlay, not on modal content
        if (target === e.target) {
          closeModal(target);
        }
      }
    });
  }

  // Initialize when DOM is ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initModalHandlers);
  } else {
    initModalHandlers();
  }

  // Handle dynamically added modals (MutationObserver)
  const observer = new MutationObserver(function (mutations) {
    mutations.forEach(function (mutation) {
      mutation.addedNodes.forEach(function (node) {
        if (node.nodeType === 1) {
          // Element node
          // Check if added node is a modal or contains modals
          if (
            node.classList &&
            (node.classList.contains("modal-overlay") ||
              node.classList.contains("modal"))
          ) {
            // Modal was dynamically added - event delegation should handle it
            // But we can ensure it's properly initialized if needed
          }
        }
      });
    });
  });

  // Start observing for dynamically added modals
  observer.observe(document.body, {
    childList: true,
    subtree: true,
  });

  // Export utility functions for manual modal closing if needed
  window.closeAnyModal = function (modalElement) {
    if (typeof modalElement === "string") {
      // If string provided, treat as ID selector
      modalElement =
        document.getElementById(modalElement) ||
        document.querySelector(modalElement);
    }
    closeModal(modalElement);
  };

  // Export function to close all modals
  window.closeAllModals = function () {
    const allModals = document.querySelectorAll(
      '.modal-overlay, .modal, [id*="Modal"]'
    );
    allModals.forEach((modal) => {
      const style = window.getComputedStyle(modal);
      if (style.display !== "none" && style.display !== "") {
        closeModal(modal);
      }
    });
  };

  console.log("✅ Universal Modal Handler initialized (refined version)");
})();
