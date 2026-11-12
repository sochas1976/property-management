<?php
namespace App\Core;

use DateTime;
use Exception;

/**
 * AppDate — Centralized application date helper with Development Test Mode time-travel simulation.
 */
class AppDate {
    /**
     * Get the current effective date as a DateTime object (at midnight).
     */
    public static function today(): DateTime {
        return new DateTime(self::todayString());
    }

    /**
     * Get the current effective date string in 'Y-m-d' format.
     * When Test Mode is active and a simulated date is set, returns the simulated date.
     * Otherwise returns real-time system date.
     */
    public static function todayString(): string {
        if (defined('TEST_MODE_AVAILABLE') && TEST_MODE_AVAILABLE) {
            $isTestActive = ($_COOKIE['pp_test_mode_active'] ?? '') === 'true';
            $simulated = $_COOKIE['pp_test_simulated_date'] ?? null;
            if ($isTestActive && !empty($simulated)) {
                $simulated = trim((string)$simulated);
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $simulated)) {
                    return $simulated;
                }
            }
        }
        return date('Y-m-d');
    }

    /**
     * Check if the application is currently operating with a simulated date.
     */
    public static function isSimulated(): bool {
        if (defined('TEST_MODE_AVAILABLE') && TEST_MODE_AVAILABLE) {
            $isTestActive = ($_COOKIE['pp_test_mode_active'] ?? '') === 'true';
            $simulated = $_COOKIE['pp_test_simulated_date'] ?? null;
            return $isTestActive && !empty($simulated) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$simulated);
        }
        return false;
    }

    /**
     * Calculate days remaining from the current effective date to an end date.
     * Returns:
     *   > 0  for future dates (days remaining)
     *   0    if the end date is today
     *   < 0  for past dates (days overdue/passed)
     *   null if end date is invalid
     */
    public static function daysUntil(?string $endDateStr): ?int {
        if (empty($endDateStr)) {
            return null;
        }
        try {
            $today = self::today();
            $endDate = new DateTime($endDateStr);
            $diff = $today->diff($endDate);
            return $diff->invert ? -$diff->days : $diff->days;
        } catch (Exception $e) {
            return null;
        }
    }
}
