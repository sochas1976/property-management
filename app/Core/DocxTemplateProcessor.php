<?php
namespace App\Core;

use ZipArchive;
use DOMDocument;
use DOMXPath;
use DOMElement;
use Exception;

class DocxTemplateProcessor {
    /**
     * Check if a color attribute or highlight is in the red color spectrum.
     */
    public static function isRedColor(?string $color): bool {
        if (!$color) return false;
        $color = strtolower(trim($color));
        if (in_array($color, ['red', 'darkred', 'crimson', 'firebrick', 'salmon', 'indianred'])) {
            return true;
        }
        if (strlen($color) === 6 && ctype_xdigit($color)) {
            $r = hexdec(substr($color, 0, 2));
            $g = hexdec(substr($color, 2, 2));
            $b = hexdec(substr($color, 4, 2));
            return ($r >= 140 && $g <= 110 && $b <= 110);
        }
        return false;
    }

    /**
     * Structured catalog of all selectable fields grouped by data source.
     */
    public static function getAvailableFieldCatalog(): array {
        return [
            'Calendar & Agreement Date' => [
                'calendar.full_date'   => 'Current Calendar Date (e.g. November 12, 2026)',
                'calendar.day'         => 'Current Calendar Day (e.g. 12)',
                'calendar.day_ordinal' => 'Current Calendar Day Ordinal (e.g. 12th)',
                'calendar.month'       => 'Current Calendar Month (e.g. November)',
                'calendar.year'        => 'Current Calendar Year (e.g. 2026)',
                'calendar.year_short'  => 'Current Calendar Year 2-Digit (e.g. 26)',
            ],
            'Application Data (Prospective Applicant)' => [
                'applicant.auto_co_applicant'              => 'Applicant Full Name (Per Line: 1st = Primary, 2nd = Co-Applicant)',
                'applicant.full_names'                     => 'Applicant(s) Full Name (All Combined)',
                'applicant.primary_name'                   => 'Primary Applicant Full Name (Applicant 1)',
                'applicant.co_applicant_name'              => 'Co-Applicant Full Name (Applicant 2)',
                'applicant.email'                          => 'Applicant Email Address',
                'applicant.cell_phone'                     => 'Applicant Cell Phone',
                'applicant.home_phone'                     => 'Applicant Home Phone',
                'applicant.present_address'                => 'Applicant Present Address',
                'applicant.occupants_adults'               => 'Application: Occupants (Adults)',
                'applicant.occupants_children'             => 'Application: Occupants (Children)',
                'applicant.other_occupants'                => 'Application: Other Occupants (Adults & Children)',
                'applicant.tenancy_type'                   => 'Application: Tenancy Type (Fixed / Periodic)',
                'applicant.term_start_date'                => 'Application: Requested Start Date',
                'applicant.term_end_date'                  => 'Application: Requested End Date',
                'applicant.current_employer'               => 'Application: Current Employer',
                'applicant.current_occupation'             => 'Application: Current Occupation',
                'applicant.monthly_income'                 => 'Application: Monthly Income ($)',
                'applicant.emergency_contact_1_name'       => 'Application: Emergency Contact Name',
                'applicant.emergency_contact_1_phone'      => 'Application: Emergency Contact Phone',
                'applicant.emergency_contact_1_relationship' => 'Application: Emergency Contact Relationship',
            ],
            'Property Data' => [
                'property.name'           => 'Property / Landlord Name',
                'property.address_line1'  => 'Property Street Address',
                'property.city'           => 'Property City',
                'property.state'          => 'Property Province / State',
                'property.zip_code'       => 'Property Postal / Zip Code',
                'property.city_state'     => 'Property City, Province/State',
                'property.city_state_zip' => 'Property City, Province/State, Postal Code',
                'property.full_address'   => 'Property Full Address (Street, City, Province/State, Postal)',
            ],
            'Unit & Lease Terms Data' => [
                'unit.unit_number'       => 'Unit Number',
                'unit.floor'             => 'Unit Floor',
                'unit.bedrooms'          => 'Unit Bedrooms',
                'unit.bathrooms'         => 'Unit Bathrooms',
                'unit.square_feet'       => 'Unit Square Feet',
                'unit.parking'           => 'Unit Parking',
                'unit.utilities'         => 'Unit Utilities Included',
                'unit.appliances'        => 'Unit Appliances Included',
                'unit.pets'              => 'Unit Pet Policy',
                'unit.smoking'           => 'Unit Smoking Policy',
                'lease.rent_amount'      => 'Lease Monthly Rent Amount ($)',
                'lease.deposit_held'     => 'Lease Security Deposit ($)',
                'lease.payment_frequency'=> 'Lease Payment Frequency',
                'lease.start_date'       => 'Lease Start Date (Full Date)',
                'lease.start_date_day'   => 'Lease Start Date — Day',
                'lease.start_date_month' => 'Lease Start Date — Month',
                'lease.start_date_year'  => 'Lease Start Date — Year',
                'lease.end_date'         => 'Lease End Date (Full Date)',
                'lease.end_date_day'     => 'Lease End Date — Day',
                'lease.end_date_month'   => 'Lease End Date — Month',
                'lease.end_date_year'    => 'Lease End Date — Year',
                'lease.terms'            => 'Lease Special Provisions / Terms',
            ],
            'Manual / Custom Entry' => [
                'manual'                 => '-- Fill In Manually / Custom Value --',
            ],
        ];
    }

    /**
     * Get flat map of fieldKey => label.
     */
    public static function getFieldLabel(string $fieldKey): string {
        foreach (self::getAvailableFieldCatalog() as $group => $fields) {
            if (isset($fields[$fieldKey])) {
                return $fields[$fieldKey];
            }
        }
        return ucfirst(str_replace(['.', '_'], ' ', $fieldKey));
    }

    /**
     * Parse date part placeholder specifiers like [AGREEMENT DATE, Day], [START DATE, Month], etc.
     */
    public static function parseDatePartPlaceholder(string $text): ?array {
        $clean = trim($text);
        if (strlen($clean) >= 2 && substr($clean, 0, 1) === '[' && substr($clean, -1) === ']') {
            $clean = substr($clean, 1, -1);
        } elseif (strlen($clean) >= 1 && substr($clean, 0, 1) === '[') {
            $clean = substr($clean, 1);
        }
        if (preg_match('/^(AGREEMENT\s*DATE|START\s*DATE|END\s*DATE|DATE)\s*,\s*([a-z0-9_-]+)$/i', trim($clean), $m)) {
            $rawBase = strtoupper(preg_replace('/\s+/', ' ', trim($m[1])));
            $baseField = 'agreement_date';
            if (strpos($rawBase, 'START') !== false) {
                $baseField = 'start_date';
            } elseif (strpos($rawBase, 'END') !== false) {
                $baseField = 'end_date';
            }
            return [
                'base_raw'   => $rawBase,
                'base_field' => $baseField,
                'part'       => strtolower(trim($m[2]))
            ];
        }
        return null;
    }

    /**
     * Extract specified date part (day, month, year, etc.) from a date string.
     */
    public static function extractDatePart(string $dateStr, string $part, string $fallback = ''): string {
        $dateStr = trim($dateStr);
        $time = strtotime($dateStr);
        if ($time === false) {
            return !empty($fallback) ? $fallback : $dateStr;
        }
        switch (strtolower($part)) {
            case 'day':
                return (string)(int)date('j', $time);
            case 'day2':
            case 'daypadded':
                return date('d', $time);
            case 'ordinal':
            case 'dayordinal':
                return date('jS', $time);
            case 'month':
                return date('F', $time);
            case 'monthshort':
                return date('M', $time);
            case 'monthnum':
                return (string)(int)date('n', $time);
            case 'year':
                return date('Y', $time);
            case 'yearshort':
                return date('y', $time);
            default:
                return !empty($fallback) ? $fallback : $dateStr;
        }
    }

    /**
     * Extract [ ] bracket placeholders (and any red unclosed bracket tokens) from a DOCX file.
     */
    public static function extractPlaceholders(string $docxPath, ?array $leaseContext = null, array $savedMappings = []): array {
        if (!file_exists($docxPath)) {
            throw new Exception("DOCX file does not exist: {$docxPath}");
        }

        $zip = new ZipArchive();
        if ($zip->open($docxPath) !== TRUE) {
            throw new Exception("Unable to open DOCX file as zip archive.");
        }

        $xmlParts = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if (preg_match('#^word/(document|header\d+|footer\d+)\.xml$#', $filename)) {
                $xmlParts[$filename] = $zip->getFromIndex($i);
            }
        }
        $zip->close();

        $rawPlaceholders = [];

        foreach ($xmlParts as $partName => $xmlContent) {
            $dom = new DOMDocument();
            @$dom->loadXML($xmlContent);
            $xpath = new DOMXPath($dom);
            $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

            $paragraphs = $xpath->query('//w:p', $dom);
            foreach ($paragraphs as $p) {
                $runs = $xpath->query('.//w:r', $p);
                $pFullText = '';
                foreach ($runs as $r) {
                    $tList = $xpath->query('.//w:t', $r);
                    foreach ($tList as $tNode) {
                        $pFullText .= $tNode->nodeValue;
                    }
                }

                if (trim($pFullText) === '') {
                    continue;
                }

                // 1. Extract all standard [ ... ] bracketed placeholders in the paragraph
                if (preg_match_all('/\[[^\]]+\]/', $pFullText, $matches) && !empty($matches[0])) {
                    foreach ($matches[0] as $tok) {
                        $trimmed = trim($tok);
                        if ($trimmed !== '') {
                            $rawPlaceholders[] = [
                                'part'          => $partName,
                                'original_text' => $trimmed,
                                'context'       => self::formatContextSnippet($pFullText, $trimmed),
                            ];
                        }
                    }
                } elseif (strpos($pFullText, '[') !== false) {
                    // 2. Catch any unclosed '[' placeholder (e.g. "[Other Occupants" without closing bracket)
                    if (preg_match_all('/\[[^\r\n\[]+/', $pFullText, $m2) && !empty($m2[0])) {
                        foreach ($m2[0] as $tok) {
                            $trimmed = trim($tok);
                            if ($trimmed !== '') {
                                $rawPlaceholders[] = [
                                    'part'          => $partName,
                                    'original_text' => $trimmed,
                                    'context'       => self::formatContextSnippet($pFullText, $trimmed),
                                ];
                            }
                        }
                    }
                }
            }
        }

        // Deduplicate placeholders (case-insensitive) and count occurrences
        $deduped = [];
        foreach ($rawPlaceholders as $ph) {
            $normKey = strtolower(trim($ph['original_text']));
            if (!isset($deduped[$normKey])) {
                $deduped[$normKey] = [
                    'key'           => 'ph_' . md5($normKey),
                    'part'          => $ph['part'],
                    'original_text' => $ph['original_text'],
                    'context'       => $ph['context'],
                    'count'         => 1,
                ];
            } else {
                $deduped[$normKey]['count']++;
            }
        }

        // Attach mapping info and resolved value for each unique placeholder
        $result = [];
        foreach ($deduped as $normKey => $ph) {
            $origText = $ph['original_text'];
            $savedEntry = self::findSavedMappingForPlaceholder($origText, $savedMappings);

            $suggestedField = self::detectFieldMapping($origText, $ph['context'], $ph['count']);
            $mappedField = !empty($savedEntry['field']) ? $savedEntry['field'] : $suggestedField;
            $manualValue = isset($savedEntry['manual_value']) ? (string)$savedEntry['manual_value'] : '';

            $resolved = self::resolveMappedValue($mappedField, $manualValue, $leaseContext, 0, $ph['count']);

            $ph['suggested_field'] = $suggestedField;
            $ph['mapped_field']    = $mappedField;
            $ph['field_label']     = self::getFieldLabel($mappedField);
            $ph['manual_value']    = $manualValue;
            $ph['default_value']   = $resolved['value'];
            $ph['is_missing']      = $resolved['is_missing'];

            $result[] = $ph;
        }

        return $result;
    }

    /**
     * Find a saved mapping entry for a placeholder token (case-insensitive / bracket-normalized).
     */
    public static function findSavedMappingForPlaceholder(string $token, array $savedMappings): ?array {
        if (empty($savedMappings)) {
            return null;
        }
        if (isset($savedMappings[$token]) && is_array($savedMappings[$token])) {
            return $savedMappings[$token];
        }
        $cleanToken = trim($token, " \t\n\r\0\x0B[]");
        foreach ($savedMappings as $k => $v) {
            if (!is_array($v)) continue;
            if (strcasecmp(trim($k), trim($token)) === 0) {
                return $v;
            }
            $cleanK = trim($k, " \t\n\r\0\x0B[]");
            if ($cleanK !== '' && strcasecmp($cleanK, $cleanToken) === 0) {
                return $v;
            }
        }
        return null;
    }

    /**
     * Smart heuristic detection mapping a [ ] placeholder to our field catalog key.
     */
    public static function detectFieldMapping(string $text, string $context = '', int $occurrenceCount = 1): string {
        $t = strtolower(trim($text));

        // 1. Check for date-part specifiers like [AGREEMENT DATE, Day], [START DATE, Month], etc.
        $datePart = self::parseDatePartPlaceholder($t);
        if ($datePart !== null) {
            $base = $datePart['base_field'];
            $part = $datePart['part'];
            if ($base === 'agreement_date') {
                if ($part === 'day') return 'calendar.day';
                if ($part === 'ordinal' || $part === 'dayordinal') return 'calendar.day_ordinal';
                if ($part === 'month') return 'calendar.month';
                if ($part === 'year') return 'calendar.year';
                if ($part === 'yearshort') return 'calendar.year_short';
                return 'calendar.full_date';
            } elseif ($base === 'start_date') {
                if ($part === 'day') return 'lease.start_date_day';
                if ($part === 'month') return 'lease.start_date_month';
                if ($part === 'year') return 'lease.start_date_year';
                return 'lease.start_date';
            } elseif ($base === 'end_date') {
                if ($part === 'day') return 'lease.end_date_day';
                if ($part === 'month') return 'lease.end_date_month';
                if ($part === 'year') return 'lease.end_date_year';
                return 'lease.end_date';
            }
        }

        // 2. Direct checks on placeholder name
        if (preg_match('/other\s*occupant/i', $t)) return 'applicant.other_occupants';
        if (preg_match('/adult/i', $t)) return 'applicant.occupants_adults';
        if (preg_match('/child/i', $t)) return 'applicant.occupants_children';
        if (preg_match('/co-?applicant|second\s*tenant|(?:tenant|applicant|resident).*?\b2\b/i', $t)) return 'applicant.co_applicant_name';
        if (preg_match('/primary\s*tenant|(?:tenant|applicant|resident).*?\b1\b/i', $t)) return 'applicant.primary_name';
        if (preg_match('/tenant|resident|lessee|applicant/i', $t)) {
            return ($occurrenceCount > 1) ? 'applicant.auto_co_applicant' : 'applicant.full_names';
        }
        if (preg_match('/landlord|lessor|owner|company/i', $t)) return 'property.name';
        if (preg_match('/security|deposit/i', $t)) return 'lease.deposit_held';
        if (preg_match('/rent/i', $t)) return 'lease.rent_amount';
        if (preg_match('/unit|suite|apt/i', $t)) return 'unit.unit_number';

        // Granular city / state / zip checks
        $hasCity = (bool)preg_match('/city|municipality|town/i', $t);
        $hasState = (bool)preg_match('/state|province|prov/i', $t);
        $hasZip = (bool)preg_match('/zip|postal/i', $t);

        if ($hasCity && $hasState && $hasZip) return 'property.city_state_zip';
        if ($hasCity && $hasState) return 'property.city_state';
        if ($hasCity && !$hasState && !$hasZip) return 'property.city';
        if ($hasState && !$hasCity && !$hasZip) return 'property.state';
        if ($hasZip && !$hasCity && !$hasState) return 'property.zip_code';

        if (preg_match('/address|street|premises/i', $t)) return 'property.address_line1';
        if (preg_match('/start|begin|commenc/i', $t)) return 'lease.start_date';
        if (preg_match('/end|terminat|expir/i', $t)) return 'lease.end_date';
        if (preg_match('/frequency|payment period/i', $t)) return 'lease.payment_frequency';
        if (preg_match('/agreement date|signing date|today|current date/i', $t)) return 'calendar.full_date';
        if (preg_match('/email/i', $t)) return 'applicant.email';
        if (preg_match('/phone|tel/i', $t)) return 'applicant.cell_phone';
        if (preg_match('/parking/i', $t)) return 'unit.parking';
        if (preg_match('/utilit/i', $t)) return 'unit.utilities';
        if (preg_match('/appliance/i', $t)) return 'unit.appliances';

        return 'manual';
    }

    /**
     * Resolve a catalog field key against a Lease + Application + Unit + Property context.
     * Returns ['value' => string, 'is_missing' => bool]
     */
    public static function resolveMappedValue(
        string $fieldKey,
        string $manualValue = '',
        ?array $lease = null,
        int $occurrenceIndex = 0,
        int $totalOccurrences = 1
    ): array {
        $now = class_exists('\App\Core\AppDate') ? \App\Core\AppDate::today() : new \DateTime();
        $applicants = $lease['applicants'] ?? [];
        $app1 = $applicants[0] ?? null;
        $app2 = $applicants[1] ?? null;

        $val = '';

        switch ($fieldKey) {
            // Calendar & Agreement Date
            case 'calendar.full_date':
            case 'today_date':
                $val = $now->format('F j, Y');
                break;
            case 'calendar.day':
                $val = (string)(int)$now->format('j');
                break;
            case 'calendar.day_ordinal':
                $val = $now->format('jS');
                break;
            case 'calendar.month':
                $val = $now->format('F');
                break;
            case 'calendar.year':
                $val = $now->format('Y');
                break;
            case 'calendar.year_short':
                $val = $now->format('y');
                break;

            // Application Data
            case 'applicant.auto_co_applicant':
                if ($totalOccurrences > 1 && !empty($applicants)) {
                    $val = isset($applicants[$occurrenceIndex]['full_name'])
                        ? trim((string)$applicants[$occurrenceIndex]['full_name'])
                        : '';
                } else {
                    $val = !empty($lease['tenant_name']) ? trim((string)$lease['tenant_name']) : ($app1['full_name'] ?? '');
                }
                break;
            case 'applicant.full_names':
            case 'tenant_name':
                $val = !empty($lease['tenant_name']) ? trim((string)$lease['tenant_name']) : ($app1['full_name'] ?? '');
                break;
            case 'applicant.primary_name':
                $val = !empty($app1['full_name']) ? trim((string)$app1['full_name']) : ($lease['tenant_name'] ?? '');
                break;
            case 'applicant.co_applicant_name':
                $val = !empty($app2['full_name']) ? trim((string)$app2['full_name']) : '';
                break;
            case 'applicant.email':
                $val = !empty($app1['email']) ? $app1['email'] : ($lease['tenant_email'] ?? '');
                break;
            case 'applicant.cell_phone':
                $val = !empty($app1['cell_phone']) ? $app1['cell_phone'] : ($lease['tenant_phone'] ?? '');
                break;
            case 'applicant.home_phone':
                $val = !empty($app1['home_phone']) ? $app1['home_phone'] : ($lease['tenant_phone'] ?? '');
                break;
            case 'applicant.present_address':
                $val = $app1['present_address'] ?? '';
                break;
            case 'applicant.occupants_adults':
                $val = $app1['occupants_adults'] ?? '';
                break;
            case 'applicant.occupants_children':
                $val = $app1['occupants_children'] ?? '';
                break;
            case 'applicant.other_occupants':
            case 'other_occupants':
                $parts = array_filter([
                    trim((string)($app1['occupants_adults'] ?? '')),
                    trim((string)($app1['occupants_children'] ?? ''))
                ]);
                $val = !empty($parts) ? implode(', ', $parts) : '';
                break;
            case 'applicant.tenancy_type':
                $val = !empty($app1['tenancy_type']) ? ucfirst($app1['tenancy_type']) : '';
                break;
            case 'applicant.term_start_date':
                $val = !empty($app1['term_start_date']) ? $app1['term_start_date'] : ($lease['start_date'] ?? '');
                break;
            case 'applicant.term_end_date':
                $val = !empty($app1['term_end_date']) ? $app1['term_end_date'] : ($lease['end_date'] ?? '');
                break;
            case 'applicant.current_employer':
                $val = $app1['current_employer'] ?? '';
                break;
            case 'applicant.current_occupation':
                $val = $app1['current_occupation'] ?? '';
                break;
            case 'applicant.monthly_income':
                $val = !empty($app1['monthly_income']) ? number_format((float)$app1['monthly_income'], 2) : '';
                break;
            case 'applicant.emergency_contact_1_name':
                $val = $app1['emergency_contact_1_name'] ?? '';
                break;
            case 'applicant.emergency_contact_1_phone':
                $val = $app1['emergency_contact_1_phone'] ?? '';
                break;
            case 'applicant.emergency_contact_1_relationship':
                $val = $app1['emergency_contact_1_relationship'] ?? '';
                break;

            // Property Data
            case 'property.name':
            case 'landlord_name':
                $val = $lease['property_name'] ?? '';
                break;
            case 'property.address_line1':
            case 'property_address':
                $val = $lease['address_line1'] ?? '';
                break;
            case 'property.city':
            case 'property_city':
                $val = $lease['city'] ?? '';
                break;
            case 'property.state':
            case 'property_state':
                $val = $lease['state'] ?? '';
                break;
            case 'property.zip_code':
            case 'property_zip':
                $val = $lease['zip_code'] ?? '';
                break;
            case 'property.city_state':
            case 'property_city_state':
                $parts = array_filter([$lease['city'] ?? '', $lease['state'] ?? '']);
                $val = implode(', ', $parts);
                break;
            case 'property.city_state_zip':
            case 'property_city_state_zip':
                $parts = array_filter([$lease['city'] ?? '', $lease['state'] ?? '', $lease['zip_code'] ?? '']);
                $val = implode(', ', $parts);
                break;
            case 'property.full_address':
                $parts = array_filter([$lease['address_line1'] ?? '', $lease['city'] ?? '', $lease['state'] ?? '', $lease['zip_code'] ?? '']);
                $val = implode(', ', $parts);
                break;

            // Unit & Lease Terms Data
            case 'unit.unit_number':
            case 'unit_number':
                $val = $lease['unit_number'] ?? '';
                break;
            case 'unit.floor':
                $val = isset($lease['floor']) ? (string)$lease['floor'] : '';
                break;
            case 'unit.bedrooms':
                $val = isset($lease['bedrooms']) ? (string)$lease['bedrooms'] : '';
                break;
            case 'unit.bathrooms':
                $val = isset($lease['bathrooms']) ? (string)$lease['bathrooms'] : '';
                break;
            case 'unit.square_feet':
                $val = !empty($lease['square_feet']) ? (string)$lease['square_feet'] : '';
                break;
            case 'unit.parking':
                $val = $lease['parking'] ?? '';
                break;
            case 'unit.utilities':
                $val = $lease['utilities'] ?? '';
                break;
            case 'unit.appliances':
                $val = $lease['appliances'] ?? '';
                break;
            case 'unit.pets':
                $val = $lease['pets'] ?? '';
                break;
            case 'unit.smoking':
                $val = $lease['smoking'] ?? '';
                break;
            case 'lease.rent_amount':
            case 'rent_amount':
                $val = isset($lease['rent_amount']) && $lease['rent_amount'] !== '' ? number_format((float)$lease['rent_amount'], 2) : '';
                break;
            case 'lease.deposit_held':
            case 'deposit_held':
                $val = isset($lease['deposit_held']) && $lease['deposit_held'] !== '' ? number_format((float)$lease['deposit_held'], 2) : '';
                break;
            case 'lease.payment_frequency':
            case 'payment_frequency':
                $val = !empty($lease['payment_frequency']) ? ucfirst($lease['payment_frequency']) : '';
                break;
            case 'lease.start_date':
            case 'start_date':
                $val = !empty($lease['start_date']) ? $lease['start_date'] : ($app1['term_start_date'] ?? '');
                break;
            case 'lease.start_date_day':
                $src = !empty($lease['start_date']) ? $lease['start_date'] : ($app1['term_start_date'] ?? '');
                $val = $src !== '' ? self::extractDatePart($src, 'day') : '';
                break;
            case 'lease.start_date_month':
                $src = !empty($lease['start_date']) ? $lease['start_date'] : ($app1['term_start_date'] ?? '');
                $val = $src !== '' ? self::extractDatePart($src, 'month') : '';
                break;
            case 'lease.start_date_year':
                $src = !empty($lease['start_date']) ? $lease['start_date'] : ($app1['term_start_date'] ?? '');
                $val = $src !== '' ? self::extractDatePart($src, 'year') : '';
                break;
            case 'lease.end_date':
            case 'end_date':
                $val = !empty($lease['end_date']) ? $lease['end_date'] : ($app1['term_end_date'] ?? '');
                break;
            case 'lease.end_date_day':
                $src = !empty($lease['end_date']) ? $lease['end_date'] : ($app1['term_end_date'] ?? '');
                $val = $src !== '' ? self::extractDatePart($src, 'day') : '';
                break;
            case 'lease.end_date_month':
                $src = !empty($lease['end_date']) ? $lease['end_date'] : ($app1['term_end_date'] ?? '');
                $val = $src !== '' ? self::extractDatePart($src, 'month') : '';
                break;
            case 'lease.end_date_year':
                $src = !empty($lease['end_date']) ? $lease['end_date'] : ($app1['term_end_date'] ?? '');
                $val = $src !== '' ? self::extractDatePart($src, 'year') : '';
                break;
            case 'lease.terms':
                $val = $lease['terms'] ?? '';
                break;

            case 'manual':
            default:
                $val = '';
                break;
        }

        $val = trim((string)$val);

        // If a manual override value exists, it overrides the data from the Source Field
        if (trim($manualValue) !== '') {
            $val = trim($manualValue);
        } elseif ($fieldKey === 'applicant.auto_co_applicant' && $occurrenceIndex > 0 && !empty($applicants) && $occurrenceIndex >= count($applicants)) {
            // If field is 'applicant.auto_co_applicant' and occurrenceIndex >= count($applicants) where count >= 1,
            // an empty 2nd tenant line is intentional and not "missing"
            return ['value' => '', 'is_missing' => false];
        }

        return [
            'value'      => $val,
            'is_missing' => ($val === '')
        ];
    }

    /**
     * Format a clean snippet of surrounding context.
     */
    protected static function formatContextSnippet(string $fullText, string $matchText, int $padding = 45): string {
        $pos = mb_stripos($fullText, $matchText);
        if ($pos === false) {
            return mb_substr($fullText, 0, 110);
        }
        $start = max(0, $pos - $padding);
        $len = mb_strlen($matchText) + ($padding * 2);
        $snippet = mb_substr($fullText, $start, $len);
        if ($start > 0) $snippet = '...' . $snippet;
        if (($start + $len) < mb_strlen($fullText)) $snippet .= '...';
        return $snippet;
    }

    /**
     * Build default mapping dictionary for a template file.
     */
    public static function buildDefaultMappings(string $docxPath, array $existingMappings = []): array {
        $placeholders = self::extractPlaceholders($docxPath, null, $existingMappings);
        $mappings = [];
        foreach ($placeholders as $ph) {
            $orig = $ph['original_text'];
            $mappings[$orig] = [
                'field'        => $ph['mapped_field'],
                'manual_value' => $ph['manual_value']
            ];
        }
        return $mappings;
    }

    /**
     * Generate a populated DOCX document using either direct replacements or template mappings + lease context.
     */
    public static function generateDocument(
        string $templatePath,
        array $replacements,
        string $outputPath,
        bool $removeRedColor = true,
        ?array $leaseContext = null,
        array $savedMappings = []
    ): bool {
        if (!copy($templatePath, $outputPath)) {
            throw new Exception("Failed to copy template to output path: {$outputPath}");
        }

        // First, extract placeholders from the template so we know all tokens and their occurrence counts
        $extracted = self::extractPlaceholders($templatePath, $leaseContext, $savedMappings);
        $tokenMeta = [];
        foreach ($extracted as $ph) {
            $norm = strtolower(trim($ph['original_text']));
            $tokenMeta[$norm] = $ph;
        }

        // Sort tokens longest-first so longer placeholders are replaced before shorter substrings
        $sortedTokens = array_values($extracted);
        usort($sortedTokens, function ($a, $b) {
            return strlen($b['original_text']) - strlen($a['original_text']);
        });

        $occurrenceCounters = [];

        $zip = new ZipArchive();
        if ($zip->open($outputPath) !== TRUE) {
            throw new Exception("Unable to open output DOCX archive.");
        }

        $xmlParts = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if (preg_match('#^word/(document|header\d+|footer\d+)\.xml$#', $filename)) {
                $xmlParts[$filename] = $zip->getFromIndex($i);
            }
        }

        foreach ($xmlParts as $partName => $xmlContent) {
            $dom = new DOMDocument();
            @$dom->loadXML($xmlContent);
            $xpath = new DOMXPath($dom);
            $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

            $paragraphs = $xpath->query('//w:p', $dom);
            foreach ($paragraphs as $p) {
                // Replace each known token across runs in this paragraph
                foreach ($sortedTokens as $ph) {
                    $token = $ph['original_text'];
                    $norm = strtolower(trim($token));
                    if (!isset($occurrenceCounters[$norm])) {
                        $occurrenceCounters[$norm] = 0;
                    }

                    self::replaceTokenInParagraph(
                        $xpath,
                        $p,
                        $token,
                        $ph,
                        $replacements,
                        $leaseContext,
                        $occurrenceCounters[$norm],
                        $ph['count']
                    );
                }

                // If removeRedColor is enabled, strip red font styling on runs in the document
                if ($removeRedColor) {
                    $pColorNode = $xpath->query('.//w:pPr/w:rPr/w:color', $p)->item(0);
                    if ($pColorNode && self::isRedColor($pColorNode->getAttribute('w:val'))) {
                        $pColorNode->parentNode->removeChild($pColorNode);
                    }
                    $runs = $xpath->query('.//w:r', $p);
                    foreach ($runs as $r) {
                        $cNode = $xpath->query('.//w:rPr/w:color', $r)->item(0);
                        if ($cNode && self::isRedColor($cNode->getAttribute('w:val'))) {
                            $cNode->parentNode->removeChild($cNode);
                        }
                        $hNode = $xpath->query('.//w:rPr/w:highlight', $r)->item(0);
                        if ($hNode && self::isRedColor($hNode->getAttribute('w:val'))) {
                            $hNode->parentNode->removeChild($hNode);
                        }
                    }
                }
            }

            $zip->addFromString($partName, $dom->saveXML());
        }

        $zip->close();
        return true;
    }

    /**
     * Replace all occurrences of $token across <w:t> runs in a paragraph <w:p>.
     */
    protected static function replaceTokenInParagraph(
        DOMXPath $xpath,
        DOMElement $p,
        string $token,
        array $phMeta,
        array $replacements,
        ?array $leaseContext,
        int &$occurrenceCounter,
        int $totalOccurrences
    ): void {
        $safety = 0;
        while ($safety++ < 30) {
            $runs = $xpath->query('.//w:r', $p);
            if ($runs->length === 0) {
                break;
            }

            $tNodes = [];
            $fullText = '';
            foreach ($runs as $r) {
                $tList = $xpath->query('.//w:t', $r);
                foreach ($tList as $tNode) {
                    $val = $tNode->textContent;
                    $start = mb_strlen($fullText, 'UTF-8');
                    $len = mb_strlen($val, 'UTF-8');
                    $tNodes[] = [
                        'run'   => $r,
                        'tNode' => $tNode,
                        'text'  => $val,
                        'start' => $start,
                        'end'   => $start + $len
                    ];
                    $fullText .= $val;
                }
            }

            $pos = mb_stripos($fullText, $token, 0, 'UTF-8');
            if ($pos === false) {
                break;
            }

            // Determine replacement value for this occurrence
            $replacementValue = self::resolveReplacementForOccurrence(
                $token,
                $phMeta,
                $replacements,
                $leaseContext,
                $occurrenceCounter,
                $totalOccurrences
            );
            $occurrenceCounter++;

            if ($replacementValue === null) {
                break;
            }

            $matchStart = $pos;
            $matchEnd = $pos + mb_strlen($token, 'UTF-8');

            $overlappingIndices = [];
            foreach ($tNodes as $idx => $info) {
                if ($info['end'] > $matchStart && $info['start'] < $matchEnd) {
                    $overlappingIndices[] = $idx;
                }
            }

            if (empty($overlappingIndices)) {
                break;
            }

            $firstIdx = $overlappingIndices[0];
            $lastIdx = $overlappingIndices[count($overlappingIndices) - 1];

            if ($firstIdx === $lastIdx) {
                $info = $tNodes[$firstIdx];
                $relStart = $matchStart - $info['start'];
                $relEnd = $matchEnd - $info['start'];
                $prefix = mb_substr($info['text'], 0, $relStart, 'UTF-8');
                $suffix = mb_substr($info['text'], $relEnd, null, 'UTF-8');
                $info['tNode']->textContent = $prefix . $replacementValue . $suffix;
                $info['tNode']->setAttribute('xml:space', 'preserve');
            } else {
                $firstInfo = $tNodes[$firstIdx];
                $relStart = $matchStart - $firstInfo['start'];
                $prefix = mb_substr($firstInfo['text'], 0, $relStart, 'UTF-8');
                $firstInfo['tNode']->textContent = $prefix . $replacementValue;
                $firstInfo['tNode']->setAttribute('xml:space', 'preserve');

                for ($i = $firstIdx + 1; $i < $lastIdx; $i++) {
                    $tNodes[$i]['tNode']->textContent = '';
                }

                $lastInfo = $tNodes[$lastIdx];
                $relEnd = $matchEnd - $lastInfo['start'];
                $suffix = mb_substr($lastInfo['text'], $relEnd, null, 'UTF-8');
                $lastInfo['tNode']->textContent = $suffix;
                $lastInfo['tNode']->setAttribute('xml:space', 'preserve');
            }
        }
    }

    /**
     * Resolve the replacement string for a specific occurrence of a placeholder token.
     */
    protected static function resolveReplacementForOccurrence(
        string $token,
        array $phMeta,
        array $replacements,
        ?array $leaseContext,
        int $occurrenceIndex,
        int $totalOccurrences
    ): ?string {
        // 1. If mapped to applicant.auto_co_applicant and we have multiple occurrences + leaseContext,
        // resolve per-occurrence unless an explicit single override was typed by the user
        $mappedField = $phMeta['mapped_field'] ?? '';
        if ($mappedField === 'applicant.auto_co_applicant' && $totalOccurrences > 1 && $leaseContext !== null) {
            $res = self::resolveMappedValue($mappedField, $phMeta['manual_value'] ?? '', $leaseContext, $occurrenceIndex, $totalOccurrences);
            return $res['value'];
        }

        // 2. Check explicit replacements array passed from form/caller
        $direct = self::lookupInReplacementsArray($token, $replacements);
        if ($direct !== null && trim($direct) !== '') {
            return $direct;
        }

        // 3. Resolve from mapped field & leaseContext
        if ($mappedField !== '') {
            $res = self::resolveMappedValue($mappedField, $phMeta['manual_value'] ?? '', $leaseContext, $occurrenceIndex, $totalOccurrences);
            if ($res['value'] !== '' || !$res['is_missing']) {
                return $res['value'];
            }
        }

        return $direct;
    }

    protected static function lookupInReplacementsArray(string $token, array $replacements): ?string {
        $token = trim($token);
        if (isset($replacements[$token])) {
            return (string)$replacements[$token];
        }
        $tokenNoBrackets = trim($token, " \t\n\r\0\x0B[]");
        foreach ($replacements as $origKey => $val) {
            $cleanKey = trim((string)$origKey);
            if ($cleanKey === $token || strcasecmp($cleanKey, $token) === 0) {
                return (string)$val;
            }
            $keyNoBrackets = trim($cleanKey, " \t\n\r\0\x0B[]");
            if ($keyNoBrackets !== '' && strcasecmp($keyNoBrackets, $tokenNoBrackets) === 0) {
                return (string)$val;
            }
        }
        return null;
    }
}
