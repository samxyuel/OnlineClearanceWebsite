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
        current.classList.contains("alert-modal-overlay") ||
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
  // Flag to prevent recursive calls - track by modal ID
  const closingModals = new Set();

  function closeModal(modal) {
    if (!modal) return;

    const modalId = modal.id || modal.className || "unknown";

    // Prevent recursive calls for the same modal
    if (closingModals.has(modalId)) {
      console.warn(
        "[ModalHandler] closeModal called recursively for:",
        modalId
      );
      return;
    }

    closingModals.add(modalId);

    try {
      // Directly close the modal without calling close functions
      // This prevents recursive loops
      console.log(
        "[ModalHandler] closeModal() - Directly closing modal:",
        modalId
      );

      // Force close immediately
      modal.style.display = "none";
      document.body.style.overflow = "";
      document.body.classList.remove("modal-open");
      modal.classList.remove("active");

      // Reset flag immediately after closing
      closingModals.delete(modalId);

      console.log("[ModalHandler] Modal closed successfully:", modalId);
    } catch (error) {
      console.error("[ModalHandler] Error closing modal:", error);
      closingModals.delete(modalId);
    }
  }

  // Legacy function for backward compatibility - but don't call close functions recursively
  function closeModalLegacy(modal) {
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
      // Don't interfere with buttons that open modals
      const button = e.target.closest("button");
      if (button && !button.classList.contains("modal-close")) {
        const onclick = button.getAttribute("onclick") || "";
        const buttonClasses = button.className || "";

        // Comprehensive check for modal-opening buttons
        const opensModal =
          // Pattern: open*Modal, *Modal, Modal*
          onclick.match(/open\w*Modal|Modal\w*Open/i) ||
          // Contains both "open" and "Modal"
          (onclick.includes("open") && onclick.includes("Modal")) ||
          // Starts with "open" and includes "Modal"
          (onclick.startsWith("open") && onclick.includes("Modal")) ||
          // Specific known functions
          onclick.includes("triggerExportModal") ||
          onclick.includes("triggerImportModal") ||
          onclick.includes("openAddStudentModal") ||
          onclick.includes("openAddFacultyModal") ||
          onclick.includes("openBulkSelectionModal") ||
          onclick.includes("openCollegeBatchUpdateModal") ||
          onclick.includes("openSeniorHighBatchUpdateModal") ||
          onclick.includes("openFacultyBatchUpdateModal") ||
          onclick.includes("openSignatoryOverrideModal") ||
          onclick.includes("openRejectionRemarksModal") ||
          onclick.includes("openRejectionModal") ||
          // Button classes that open modals
          buttonClasses.includes("export-btn") ||
          buttonClasses.includes("import-btn") ||
          buttonClasses.includes("bulk-selection-filters-btn") ||
          buttonClasses.includes("add-student-btn") ||
          buttonClasses.includes("add-faculty-btn") ||
          // Data attribute for explicit marking
          button.dataset.opensModal === "true";

        if (opensModal) {
          return; // Let the button's onclick handler run
        }
      }

      const closeBtn = e.target.closest(".modal-close");
      if (closeBtn) {
        console.log("[ModalHandler] X button clicked on modal:", closeBtn);
        const modal = findParentModal(closeBtn);
        if (modal) {
          console.log(
            "[ModalHandler] Found parent modal:",
            modal.id || modal.className
          );
          // Check if button has onclick - if so, execute it
          const onclick = closeBtn.getAttribute("onclick");
          console.log("[ModalHandler] X button clicked, onclick:", onclick);

          if (
            onclick &&
            (onclick.includes("close") || onclick.includes("Close"))
          ) {
            // Prevent default to stop browser from executing onclick twice
            e.preventDefault();
            e.stopPropagation();

            // Execute the onclick handler directly using Function constructor
            // This works regardless of where the function is defined
            try {
              console.log("[ModalHandler] Executing onclick handler:", onclick);

              // Use Function constructor to execute the onclick in the global scope
              // This will work even if the function is defined later
              const executeOnclick = new Function(onclick);
              executeOnclick();

              console.log(
                "[ModalHandler] Successfully executed onclick handler"
              );
              return; // Function handles closing, don't continue
            } catch (error) {
              console.error(
                "[ModalHandler] Error executing close button onclick:",
                error
              );
              console.error(
                "[ModalHandler] Error details:",
                error.message,
                error.stack
              );

              // If direct execution fails, try to extract and call the function
              try {
                const funcMatch = onclick.match(/(?:window\.)?(\w+)\s*\(/);
                if (funcMatch) {
                  const funcName = funcMatch[1];
                  console.log(
                    "[ModalHandler] Trying to call function directly:",
                    funcName
                  );

                  if (typeof window[funcName] === "function") {
                    window[funcName]();
                    return;
                  }
                }
              } catch (extractError) {
                console.error(
                  "[ModalHandler] Failed to extract and call function:",
                  extractError
                );
              }

              // Fall through to default close behavior
            }
          } else {
            // No onclick or not a close function - prevent default and close directly
            e.preventDefault();
            e.stopPropagation();
          }

          // Fallback: close the modal directly if no onclick or onclick failed
          console.log("[ModalHandler] Using default close behavior");
          closeModal(modal);
        } else {
          console.warn(
            "[ModalHandler] Could not find parent modal for close button"
          );
        }
      }
    });

    // Handle Cancel buttons - only if they're actually cancel buttons
    document.addEventListener("click", function (e) {
      const button = e.target.closest("button");
      if (!button) return;

      // Only process if it's a cancel/close button
      if (!isCloseButton(button)) return;

      console.log(
        "[ModalHandler] Cancel/Close button clicked:",
        button.textContent || button.className
      );
      const modal = findParentModal(button);
      if (modal) {
        console.log(
          "[ModalHandler] Found parent modal for Cancel button:",
          modal.id || modal.className
        );
        // Don't interfere if button has explicit onclick that's not a close function
        const onclick = button.getAttribute("onclick");
        console.log("[ModalHandler] Cancel button onclick:", onclick);
        if (
          onclick &&
          !onclick.includes("close") &&
          !onclick.includes("Close")
        ) {
          console.log("[ModalHandler] Button has non-close onclick, ignoring");
          return; // Button has other purpose (e.g., resetPassword, sendEmail)
        }

        // ALWAYS prevent default and stop propagation to prevent double execution
        e.preventDefault();
        e.stopPropagation();

        // If onclick exists and calls a close function, execute it
        if (
          onclick &&
          (onclick.includes("close") || onclick.includes("Close"))
        ) {
          // Execute the onclick handler directly
          try {
            console.log(
              "[ModalHandler] Executing cancel button onclick handler:",
              onclick
            );
            // Handle conditional patterns like "window.closeModal && window.closeModal()"
            if (onclick.includes("&&")) {
              // Extract function name from conditional pattern
              // Pattern: "window.closeEligibleForGraduationModal && window.closeEligibleForGraduationModal()"
              const conditionalMatch = onclick.match(
                /window\.(\w+)\s*&&\s*window\.\1\s*\(/
              );
              if (
                conditionalMatch &&
                typeof window[conditionalMatch[1]] === "function"
              ) {
                console.log(
                  "[ModalHandler] Calling conditional function from cancel button:",
                  conditionalMatch[1]
                );
                window[conditionalMatch[1]]();
                return;
              }

              // Try alternative pattern: "window.closeModal && window.closeModal()"
              const altMatch = onclick.match(/window\.(\w+)\s*&&/);
              if (altMatch && typeof window[altMatch[1]] === "function") {
                console.log(
                  "[ModalHandler] Calling conditional function from cancel button (alt pattern):",
                  altMatch[1]
                );
                window[altMatch[1]]();
                return;
              }
            }

            // Extract function name from onclick (e.g., "closeExportModal()" -> "closeExportModal")
            // Also handles "window.closeExportModal()" pattern
            const funcMatch = onclick.match(/(?:window\.)?(\w+)\s*\(/);
            if (funcMatch) {
              const funcName = funcMatch[1];
              console.log(
                "[ModalHandler] Extracted function name from cancel button:",
                funcName
              );

              // Try window scope first, then global scope
              let closeFunc = null;
              if (typeof window[funcName] === "function") {
                closeFunc = window[funcName];
                console.log(
                  "[ModalHandler] Function found on window:",
                  funcName
                );
              } else {
                // Try to get from global scope
                try {
                  closeFunc = eval(funcName);
                  if (typeof closeFunc === "function") {
                    console.log(
                      "[ModalHandler] Function found in global scope:",
                      funcName
                    );
                  }
                } catch (e) {
                  // Ignore eval errors
                }
              }

              console.log(
                "[ModalHandler] Function exists check - window[funcName]:",
                typeof window[funcName],
                "closeFunc:",
                typeof closeFunc
              );

              if (closeFunc && typeof closeFunc === "function") {
                console.log(
                  "[ModalHandler] Calling function from cancel button onclick:",
                  funcName
                );
                try {
                  closeFunc();
                  return;
                } catch (callError) {
                  console.error(
                    "[ModalHandler] Error calling cancel button function:",
                    funcName,
                    callError
                  );
                  // Fall through to default close
                }
              } else {
                console.warn(
                  "[ModalHandler] Function",
                  funcName,
                  "is not defined or not a function. Trying direct execution..."
                );

                // Last resort: try to execute the onclick directly as a string
                try {
                  const funcCall = new Function(onclick);
                  funcCall();
                  console.log(
                    "[ModalHandler] Successfully executed cancel button onclick as function"
                  );
                  return;
                } catch (execError) {
                  console.error(
                    "[ModalHandler] Failed to execute cancel button onclick directly:",
                    execError
                  );
                }
              }
            } else {
              console.warn(
                "[ModalHandler] Could not extract function name from cancel button onclick:",
                onclick
              );
            }

            // If we can't execute the function, fall through to default close
            console.log(
              "[ModalHandler] Could not execute cancel button onclick function, using default close"
            );
          } catch (error) {
            console.error(
              "[ModalHandler] Error executing cancel button onclick:",
              error
            );
            // Fall through to default close behavior
          }
        }
        // No onclick handler or error - we handle it directly
        console.log(
          "[ModalHandler] No onclick handler for cancel button, using default close behavior"
        );
        closeModal(modal);
      } else {
        console.warn(
          "[ModalHandler] Could not find parent modal for cancel button"
        );
      }
    });

    // Handle Escape key - close topmost modal
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" || e.keyCode === 27) {
        // Find all visible modals
        const allModals = document.querySelectorAll(
          '.modal-overlay, .modal, .alert-modal-overlay, [id*="Modal"]'
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

      // Don't interfere with buttons or interactive elements
      if (
        target.tagName === "BUTTON" ||
        target.tagName === "A" ||
        target.closest("button") ||
        target.closest("a") ||
        target.closest(".modal-window") ||
        target.closest(".modal-content")
      ) {
        return; // Don't close if clicking on buttons or modal content
      }

      // Check if click is directly on modal overlay
      if (
        target.classList.contains("modal-overlay") ||
        target.classList.contains("modal") ||
        target.classList.contains("alert-modal-overlay")
      ) {
        // Only close if clicking directly on overlay, not on modal content
        // Also check that the modal is actually visible
        const style = window.getComputedStyle(target);
        if (style.display !== "none" && style.display !== "") {
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

  /**
   * Open modal function - globally available
   * @param {string|HTMLElement} modalIdOrElement - Modal ID or element
   */
  window.openModal = function (modalIdOrElement) {
    try {
      console.log("[ModalHandler] openModal() called with:", modalIdOrElement);
      let modal;
      if (typeof modalIdOrElement === "string") {
        console.log(
          "[ModalHandler] Searching for modal by ID:",
          modalIdOrElement
        );
        // First try exact ID match
        modal = document.getElementById(modalIdOrElement);
        console.log(
          "[ModalHandler] Try 1 - getElementById:",
          modal ? "✅ Found" : "❌ Not found"
        );

        if (!modal) {
          // Try with Modal suffix if not found (e.g., "export" -> "exportModal")
          modal = document.getElementById(modalIdOrElement + "Modal");
          console.log(
            "[ModalHandler] Try 2 - getElementById with Modal suffix:",
            modal ? "✅ Found" : "❌ Not found"
          );
        }
        if (!modal) {
          // Try querySelector as fallback (for class selectors, etc.)
          modal = document.querySelector(modalIdOrElement);
          console.log(
            "[ModalHandler] Try 3 - querySelector:",
            modal ? "✅ Found" : "❌ Not found"
          );
        }
        if (!modal) {
          // Try finding by class if ID contains "Modal" (e.g., "exportModal" -> ".export-modal-overlay")
          const classSelector =
            modalIdOrElement.replace(/([A-Z])/g, "-$1").toLowerCase() +
            "-overlay";
          console.log("[ModalHandler] Try 4 - class selector:", classSelector);
          modal = document.querySelector("." + classSelector);
          console.log(
            "[ModalHandler] Try 4 result:",
            modal ? "✅ Found" : "❌ Not found"
          );
        }
        if (!modal) {
          // Last resort: try finding any element with the ID as a class
          modal = document.querySelector("." + modalIdOrElement);
          console.log(
            "[ModalHandler] Try 5 - class name:",
            modal ? "✅ Found" : "❌ Not found"
          );
        }
      } else {
        modal = modalIdOrElement;
        console.log("[ModalHandler] Using provided element:", modal);
      }

      if (!modal) {
        console.error(
          "[ModalHandler] ❌ Modal not found after all attempts:",
          modalIdOrElement
        );
        if (typeof showToastNotification === "function") {
          showToastNotification(
            "Modal not found. Please refresh the page.",
            "error"
          );
        }
        return;
      }

      console.log(
        "[ModalHandler] ✅ Modal found:",
        modal.id || modal.className
      );

      // Prevent opening if already open (avoid conflicts)
      const currentStyle = window.getComputedStyle(modal);
      console.log(
        "[ModalHandler] Modal current display:",
        currentStyle.display
      );
      if (currentStyle.display !== "none" && currentStyle.display !== "") {
        console.warn("[ModalHandler] Modal is already open:", modalIdOrElement);
        return;
      }

      console.log("[ModalHandler] Opening modal...");

      // Show modal using requestAnimationFrame to prevent blocking
      requestAnimationFrame(() => {
        console.log("[ModalHandler] Setting modal display to flex");
        modal.style.display = "flex";
        document.body.style.overflow = "hidden";
        document.body.classList.add("modal-open");

        // Add active class for animation
        requestAnimationFrame(() => {
          console.log("[ModalHandler] Adding active class to modal");
          modal.classList.add("active");

          // Verify modal is visible
          setTimeout(() => {
            const finalDisplay = window.getComputedStyle(modal).display;
            console.log("[ModalHandler] Modal final display:", finalDisplay);
            if (finalDisplay === "flex") {
              console.log("[ModalHandler] ✅ Modal successfully opened");
            } else {
              console.error(
                "[ModalHandler] ❌ Modal display is not flex:",
                finalDisplay
              );
            }
          }, 50);
        });

        // Focus on first focusable element (non-blocking)
        setTimeout(() => {
          try {
            const firstInput = modal.querySelector(
              'input, textarea, select, button:not(.modal-close), [tabindex]:not([tabindex="-1"])'
            );
            if (firstInput && typeof firstInput.focus === "function") {
              firstInput.focus();
            }
          } catch (focusError) {
            console.warn("Error focusing modal input:", focusError);
          }
        }, 100);

        // Trigger custom event for modals that need initialization
        try {
          const openEvent = new CustomEvent("modal:open", {
            detail: { modal: modal },
            bubbles: true,
          });
          modal.dispatchEvent(openEvent);
        } catch (eventError) {
          console.warn("Error dispatching modal:open event:", eventError);
        }
      });
    } catch (error) {
      console.error("Error opening modal:", error);
    }
  };

  // Export closeModal function globally
  window.closeModal = function (modalIdOrElement) {
    console.log(
      "[ModalHandler] window.closeModal() called with:",
      modalIdOrElement
    );
    let modal;
    if (typeof modalIdOrElement === "string") {
      console.log(
        "[ModalHandler] Searching for modal by string:",
        modalIdOrElement
      );
      // First try exact ID match
      modal = document.getElementById(modalIdOrElement);
      console.log(
        "[ModalHandler] Try 1 - getElementById:",
        modal ? "✅ Found" : "❌ Not found"
      );

      if (!modal) {
        // Try with Modal suffix if not found (e.g., "export" -> "exportModal")
        modal = document.getElementById(modalIdOrElement + "Modal");
        console.log(
          "[ModalHandler] Try 2 - getElementById with Modal suffix:",
          modal ? "✅ Found" : "❌ Not found"
        );
      }
      if (!modal) {
        // Try querySelector as fallback (for class selectors, etc.)
        modal = document.querySelector(modalIdOrElement);
        console.log(
          "[ModalHandler] Try 3 - querySelector:",
          modal ? "✅ Found" : "❌ Not found"
        );
      }
      if (!modal) {
        // Try finding by class if ID contains "Modal" (e.g., "exportModal" -> ".export-modal-overlay")
        const classSelector =
          modalIdOrElement.replace(/([A-Z])/g, "-$1").toLowerCase() +
          "-overlay";
        console.log("[ModalHandler] Try 4 - class selector:", classSelector);
        modal = document.querySelector("." + classSelector);
        console.log(
          "[ModalHandler] Try 4 result:",
          modal ? "✅ Found" : "❌ Not found"
        );
      }
      if (!modal) {
        // Last resort: try finding any element with the ID as a class
        modal = document.querySelector("." + modalIdOrElement);
        console.log(
          "[ModalHandler] Try 5 - class name:",
          modal ? "✅ Found" : "❌ Not found"
        );
      }
    } else {
      modal = modalIdOrElement;
      console.log("[ModalHandler] Using provided element:", modal);
    }

    if (!modal) {
      console.error(
        "[ModalHandler] ❌ Modal not found after all attempts:",
        modalIdOrElement
      );
      if (typeof showToastNotification === "function") {
        showToastNotification(
          "Modal not found. Please refresh the page.",
          "error"
        );
      }
      return;
    }

    console.log(
      "[ModalHandler] ✅ Modal found, closing:",
      modal.id || modal.className
    );
    closeModal(modal);
  };

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
      '.modal-overlay, .modal, .alert-modal-overlay, [id*="Modal"]'
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
