/**
 * Base Path Utility
 * Returns the base path based on protocol:
 * - HTTP (localhost): includes /OnlineClearanceWebsite/
 * - HTTPS (production): excludes /OnlineClearanceWebsite/
 */
function getBasePath() {
  const protocol = window.location.protocol;
  // If HTTPS (production), return empty string (root deployment)
  // If HTTP (localhost), return /OnlineClearanceWebsite/
  return protocol === "https:" ? "" : "/OnlineClearanceWebsite";
}

/**
 * Helper function to build API URLs
 * @param {string} endpoint - API endpoint path (e.g., 'api/auth/login.php')
 * @returns {string} Full API URL with base path
 */
function getApiUrl(endpoint) {
  const basePath = getBasePath();
  const cleanEndpoint = endpoint.startsWith("/") ? endpoint : "/" + endpoint;
  return basePath + cleanEndpoint;
}
