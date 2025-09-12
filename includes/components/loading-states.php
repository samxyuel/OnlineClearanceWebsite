<?php
/**
 * Loading States Component - Online Clearance Website
 * Reusable loading state templates and configuration
 */

// Loading States Configuration
$loadingConfig = [
    'theme' => 'primary',
    'animation' => 'spin',
    'duration' => 300,
    'timeout' => 30000,
    'showProgress' => true,
    'enableSound' => false,
    'autoHide' => true,
    'debug' => false
];

// Override config if provided
if (isset($loadingConfigOverride)) {
    $loadingConfig = array_merge($loadingConfig, $loadingConfigOverride);
}
?>

<!-- Loading States HTML Templates -->
<div id="loadingStatesTemplates" style="display: none;">
    
    <!-- Global Loading Overlay Template -->
    <div id="globalLoadingTemplate" class="global-loading-overlay loading-hidden" aria-hidden="true">
        <div class="global-loading-content">
            <div class="loading-spinner <?php echo $loadingConfig['animation']; ?>"></div>
            <p class="loading-text">Processing...</p>
        </div>
    </div>

    <!-- Button Loading Template -->
    <div id="buttonLoadingTemplate" class="btn-loading">
        <i class="fas fa-spinner loading-spinner <?php echo $loadingConfig['animation']; ?>"></i>
        <span class="loading-text">Loading...</span>
    </div>

    <!-- Table Loading Template -->
    <div id="tableLoadingTemplate" class="table-loading-overlay">
        <div class="table-loading-content">
            <div class="loading-spinner <?php echo $loadingConfig['animation']; ?>"></div>
            <div class="loading-message">Loading data...</div>
        </div>
    </div>

    <!-- Term Loading Template -->
    <div id="termLoadingTemplate" class="term-loading-overlay">
        <div class="term-loading-content">
            <div class="loading-spinner <?php echo $loadingConfig['animation']; ?>"></div>
            <div class="loading-message">Processing term...</div>
        </div>
    </div>

    <!-- Progress Bar Template -->
    <div id="progressTemplate" class="progress-container">
        <div class="progress-bar" style="width: 0%"></div>
        <div class="progress-text">0% - Processing...</div>
    </div>

    <!-- Toast Loading Template -->
    <div id="toastLoadingTemplate" class="toast loading">
        <div class="toast-content">
            <div class="loading-spinner <?php echo $loadingConfig['animation']; ?>"></div>
            <div class="toast-message">Processing...</div>
        </div>
    </div>

</div>

<!-- Loading States Configuration Script -->
<script>
// Loading States Configuration
window.LoadingStatesConfig = <?php echo json_encode($loadingConfig); ?>;

// Debug mode
if (window.LoadingStatesConfig.debug) {
    console.log('Loading States initialized with config:', window.LoadingStatesConfig);
}

// Auto-initialize if LoadingStatesManager is available
document.addEventListener('DOMContentLoaded', function() {
    if (typeof LoadingStatesManager !== 'undefined') {
        window.LoadingStates = new LoadingStatesManager(window.LoadingStatesConfig);
        
        if (window.LoadingStatesConfig.debug) {
            console.log('LoadingStatesManager initialized');
        }
    }
});
</script>

<!-- Loading States CSS (if not already included) -->
<?php if (!isset($loadingStatesCSSIncluded)): ?>
<style>
/* Inline critical loading states CSS for immediate availability */
.loading-hidden { display: none !important; }
.loading-visible { display: flex !important; }
.loading-inline { display: inline-flex !important; align-items: center; gap: 0.5rem; }
.loading-block { display: block !important; }

.global-loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    backdrop-filter: blur(2px);
}

.global-loading-content {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    text-align: center;
    min-width: 200px;
}

.loading-spinner {
    font-size: 2rem;
    color: var(--primary-color, #007bff);
    margin-bottom: 1rem;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.btn.loading {
    pointer-events: none;
    opacity: 0.7;
    position: relative;
}

.btn.loading .btn-text {
    opacity: 0;
}

.btn.loading .btn-loading {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
</style>
<?php 
$loadingStatesCSSIncluded = true;
endif; ?>

<!-- Loading States Utility Functions -->
<script>
// Utility functions for common loading patterns
window.LoadingUtils = {
    
    // Wrap async function with loading states
    async withLoading(asyncFunction, options = {}) {
        const {
            buttonId = null,
            globalMessage = null,
            tableId = null,
            termId = null,
            signatoryType = null,
            onStart = null,
            onEnd = null,
            onError = null
        } = options;

        try {
            // Show appropriate loading state
            if (buttonId) {
                showButtonLoading(buttonId, options.buttonText || 'Loading...');
            } else if (globalMessage) {
                showGlobalLoading(globalMessage);
            } else if (tableId) {
                showTableLoading(tableId, options.tableMessage || 'Loading data...');
            } else if (termId) {
                showTermLoading(termId, options.termMessage || 'Processing...');
            } else if (signatoryType) {
                showSignatoryLoading(signatoryType, options.signatoryMessage || 'Loading signatories...');
            }

            // Call onStart callback
            if (onStart) onStart();

            // Execute the async function
            const result = await asyncFunction();

            // Call onEnd callback
            if (onEnd) onEnd(result);

            return result;

        } catch (error) {
            // Call onError callback
            if (onError) onError(error);
            throw error;
        } finally {
            // Hide loading states
            if (buttonId) {
                hideButtonLoading(buttonId);
            } else if (globalMessage) {
                hideGlobalLoading();
            } else if (tableId) {
                hideTableLoading(tableId);
            } else if (termId) {
                hideTermLoading(termId);
            } else if (signatoryType) {
                hideSignatoryLoading(signatoryType);
            }
        }
    },

    // Create loading state for specific elements
    createLoadingState(element, type = 'default', message = 'Loading...') {
        if (!element) return;

        const loadingClass = `loading-${type}`;
        element.classList.add('loading', loadingClass);
        
        if (message) {
            element.dataset.loadingMessage = message;
        }
    },

    // Remove loading state from specific elements
    removeLoadingState(element) {
        if (!element) return;

        element.classList.remove('loading', 'loading-primary', 'loading-secondary', 'loading-success', 'loading-warning', 'loading-danger');
        delete element.dataset.loadingMessage;
    },

    // Check if element is in loading state
    isLoading(element) {
        return element && element.classList.contains('loading');
    }
};

// Auto-hide loading states on errors
window.addEventListener('error', function() {
    if (window.LoadingStates && window.LoadingStates.isLoading()) {
        console.warn('Error occurred while loading, hiding all loading states');
        window.LoadingStates.hideAllLoading();
    }
});

// Auto-hide loading states on page unload
window.addEventListener('beforeunload', function() {
    if (window.LoadingStates) {
        window.LoadingStates.hideAllLoading();
    }
});
</script>
