<?php
/**
 * Student Validation Helper Functions
 * Provides validation utilities for student year levels based on sector
 */

/**
 * Validates if a year level is valid for a given sector
 * 
 * @param string $yearLevel The year level to validate
 * @param string $sector The sector ('College' or 'Senior High School')
 * @return bool True if valid, false otherwise
 */
function isValidYearLevelForSector($yearLevel, $sector) {
    if ($sector === 'Senior High School') {
        return in_array($yearLevel, ['1st Year', '2nd Year']);
    } elseif ($sector === 'College') {
        return in_array($yearLevel, ['1st Year', '2nd Year', '3rd Year', '4th Year']);
    }
    return false; // Unknown sector
}

/**
 * Gets the maximum year level for a sector
 * 
 * @param string $sector The sector ('College' or 'Senior High School')
 * @return string|null Maximum year level or null if invalid sector
 */
function getMaxYearLevelForSector($sector) {
    if ($sector === 'Senior High School') {
        return '2nd Year';
    } elseif ($sector === 'College') {
        return '4th Year';
    }
    return null;
}

/**
 * Gets the valid year levels for a sector
 * 
 * @param string $sector The sector ('College' or 'Senior High School')
 * @return array Array of valid year levels
 */
function getValidYearLevelsForSector($sector) {
    if ($sector === 'Senior High School') {
        return ['1st Year', '2nd Year'];
    } elseif ($sector === 'College') {
        return ['1st Year', '2nd Year', '3rd Year', '4th Year'];
    }
    return [];
}

