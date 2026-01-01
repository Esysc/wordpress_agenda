<?php
/**
 * Event model for ACS Agenda Manager
 *
 * @package ACSAgendaManager
 */

defined('ABSPATH') || exit;

/**
 * Handles event data and business logic
 */
class ACSAGMA_Event {

    /**
     * Convert a date string to timestamp
     * Supports formats: dd/mm/yy, dd/mm/yyyy, yyyy-mm-dd
     */
    public static function date_to_timestamp(string $date): int {
        $parsed = self::parse_date($date);
        return $parsed['timestamp'] ?? 0;
    }

    /**
     * Get upcoming events (not expired)
     */
    public static function get_upcoming_events(): array {
        $all_events = ACSAGMA_Database::get_events([
            'per_page' => 100,
            'orderby' => 'date',
            'order' => 'ASC',
        ]);

        $now = time();
        $events = [];

        foreach ($all_events as $event) {
            $processed = self::process_event($event, $now);

            if ($processed !== null) {
                $events[] = $processed;
            }
        }

        // Sort by first date
        usort($events, function($a, $b) {
            $date_a = self::date_to_timestamp($a['dates'][0]);
            $date_b = self::date_to_timestamp($b['dates'][0]);
            return $date_a - $date_b;
        });

        return $events;
    }

    /**
     * Process a single event and filter expired dates
     */
    private static function process_event(array $event, int $now): ?array {
        $dates = array_filter(array_map('trim', explode(',', $event['date'])));

        if (empty($dates)) {
            return null;
        }

        $last_date_timestamp = self::date_to_timestamp(end($dates));
        $one_day = 24 * 60 * 60;

        // Event completely expired
        if ($last_date_timestamp + $one_day < $now) {
            return null;
        }

        $candopartial = (int) $event['candopartial'];
        $valid_dates = [];
        $status = null;

        foreach ($dates as $date) {
            $date_timestamp = self::date_to_timestamp($date);

            if ($date_timestamp + $one_day < $now) {
                // Date has passed
                if ($candopartial === 0) {
                    // Event doesn't allow partial attendance - remove entire event
                    return null;
                } elseif ($candopartial === 1) {
                    // Allow partial - skip expired dates
                    continue;
                } elseif ($candopartial === 2) {
                    // Keep event until end - mark as running
                    $status = 'running';
                    $valid_dates[] = [
                        'date' => $date,
                        'expired' => true,
                    ];
                }
            } elseif ($date_timestamp < $now && $date_timestamp + $one_day > $now) {
                // Today
                $status = 'today';
                $valid_dates[] = [
                    'date' => $date,
                    'today' => true,
                ];
            } else {
                // Future date
                $valid_dates[] = [
                    'date' => $date,
                ];
            }
        }

        if (empty($valid_dates)) {
            return null;
        }

        return [
            'id' => (int) $event['id'],
            'categorie' => $event['categorie'],
            'title' => $event['title'],
            'emplacement' => $event['emplacement'],
            'image' => $event['image'],
            'intro' => $event['intro'],
            'link' => $event['link'],
            'dates' => array_column($valid_dates, 'date'),
            'dates_info' => $valid_dates,
            'price' => $event['price'],
            'account' => (int) $event['account'],
            'candopartial' => $candopartial,
            'redirect' => $event['redirect'] ?? '',
            'status' => $status,
        ];
    }

    /**
     * Parse a date string to components
     * Supports formats: dd/mm/yy, dd/mm/yyyy, yyyy-mm-dd
     */
    public static function parse_date(string $date): array {
        $day = 0;
        $month = 0;
        $year = 0;

        // Try dd/mm/yy or dd/mm/yyyy format
        if (strpos($date, '/') !== false) {
            $parts = explode('/', $date);
            if (count($parts) === 3) {
                $day = (int) $parts[0];
                $month = (int) $parts[1];
                $year = (int) $parts[2];
                // Convert 2-digit year to 4-digit
                if ($year < 100) {
                    $year += 2000;
                }
            }
        }
        // Try yyyy-mm-dd format
        elseif (strpos($date, '-') !== false) {
            $parts = explode('-', $date);
            if (count($parts) === 3) {
                $year = (int) $parts[0];
                $month = (int) $parts[1];
                $day = (int) $parts[2];
            }
        }

        // Validate parsed values
        if ($day < 1 || $day > 31 || $month < 1 || $month > 12 || $year < 1900) {
            return [];
        }

        $timestamp = mktime(0, 0, 0, $month, $day, $year);

        return [
            'day' => $day,
            'month' => $month,
            'year' => $year,
            'timestamp' => $timestamp,
            'weekday' => self::get_weekday_name($timestamp),
            'month_name' => self::get_month_name($month),
        ];
    }

    /**
     * Get localized weekday name
     */
    private static function get_weekday_name(int $timestamp): string {
        $weekdays = [
            __('Sunday', 'acs-agenda-manager'),
            __('Monday', 'acs-agenda-manager'),
            __('Tuesday', 'acs-agenda-manager'),
            __('Wednesday', 'acs-agenda-manager'),
            __('Thursday', 'acs-agenda-manager'),
            __('Friday', 'acs-agenda-manager'),
            __('Saturday', 'acs-agenda-manager'),
        ];

        return $weekdays[(int) gmdate('w', $timestamp)];
    }

    /**
     * Get localized month name
     */
    private static function get_month_name(int $month): string {
        $months = [
            1 => __('January', 'acs-agenda-manager'),
            2 => __('February', 'acs-agenda-manager'),
            3 => __('March', 'acs-agenda-manager'),
            4 => __('April', 'acs-agenda-manager'),
            5 => __('May', 'acs-agenda-manager'),
            6 => __('June', 'acs-agenda-manager'),
            7 => __('July', 'acs-agenda-manager'),
            8 => __('August', 'acs-agenda-manager'),
            9 => __('September', 'acs-agenda-manager'),
            10 => __('October', 'acs-agenda-manager'),
            11 => __('November', 'acs-agenda-manager'),
            12 => __('December', 'acs-agenda-manager'),
        ];

        return $months[$month] ?? '';
    }
}
