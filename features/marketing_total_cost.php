<?php
function handleMarketingTotalCost($conn, $entities, $summaryType = 'both') {
    $year = $entities['year'] ?? null;
    $month = $entities['month'] ?? null;

    function formatNameFromEmail($email) {
        if (!$email) return '-';
        $namePart = explode('@', $email)[0];
        $namePart = str_replace('.', ' ', $namePart);
        return ucwords($namePart);
    }

    function generateSeparatorFixed($length = 48) {
        return str_repeat("─", $length);
    }

    if ($year && !$month) {
        $sql = "
            SELECT DATE_FORMAT(activity_date, '%Y-%m') AS month, mkt_code, sign_of_applicant,
                COALESCE(real_total_cost, estimated_total_cost, 0) AS total_cost, status
            FROM marketing_activity_headers
            WHERE YEAR(activity_date) = '{$year}'
            UNION ALL
            SELECT DATE_FORMAT(promo_date, '%Y-%m') AS month, mkt_code, sign_of_applicant,
                COALESCE(real_budget, budget, 0) AS total_cost, status
            FROM marketing_promo_headers
            WHERE YEAR(promo_date) = '{$year}'
        ";
    } elseif ($year && $month) {
        $requestedMonth = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);
        $sql = "
            SELECT DATE_FORMAT(activity_date, '%Y-%m') AS month, mkt_code, sign_of_applicant,
                COALESCE(real_total_cost, estimated_total_cost, 0) AS total_cost, status
            FROM marketing_activity_headers
            WHERE DATE_FORMAT(activity_date, '%Y-%m') = '$requestedMonth'
            UNION ALL
            SELECT DATE_FORMAT(promo_date, '%Y-%m') AS month, mkt_code, sign_of_applicant,
                COALESCE(real_budget, budget, 0) AS total_cost, status
            FROM marketing_promo_headers
            WHERE DATE_FORMAT(promo_date, '%Y-%m') = '$requestedMonth'
        ";
    } else {
        $resultMonth = $conn->query("SELECT MAX(DATE_FORMAT(activity_date, '%Y-%m')) AS latest_month FROM marketing_activity_headers");
        $rowMonth = $resultMonth->fetch_assoc();
        $requestedMonth = $rowMonth['latest_month'];

        $sql = "
            SELECT DATE_FORMAT(activity_date, '%Y-%m') AS month, mkt_code, sign_of_applicant,
                COALESCE(real_total_cost, estimated_total_cost, 0) AS total_cost, status
            FROM marketing_activity_headers
            WHERE DATE_FORMAT(activity_date, '%Y-%m') = '$requestedMonth'
            UNION ALL
            SELECT DATE_FORMAT(promo_date, '%Y-%m') AS month, mkt_code, sign_of_applicant,
                COALESCE(real_budget, budget, 0) AS total_cost, status
            FROM marketing_promo_headers
            WHERE DATE_FORMAT(promo_date, '%Y-%m') = '$requestedMonth'
        ";
    }

    $result = $conn->query($sql);
    $data = [];

    $grandTotalActivities = 0;
    $grandTotalCost = 0;

    while ($row = $result->fetch_assoc()) {
        $monthKey = $row['month'];
        $mktCode = $row['mkt_code'];
        $name = formatNameFromEmail($row['sign_of_applicant']);
        $status = $row['status'] ?? 'UNKNOWN';

        if (!isset($data[$monthKey])) {
            $data[$monthKey] = [
                'total_activities' => 0,
                'total_cost' => 0,
                'marketing_codes' => [],
                'specialists' => [],
                'status_summary' => []
            ];
        }

        $data[$monthKey]['total_activities'] += 1;
        $data[$monthKey]['total_cost'] += $row['total_cost'];
        $data[$monthKey]['marketing_codes'][$mktCode] = true;

        if (!isset($data[$monthKey]['specialists'][$name])) {
            $data[$monthKey]['specialists'][$name] = 0;
        }
        $data[$monthKey]['specialists'][$name] += 1;

        if (!isset($data[$monthKey]['status_summary'][$status])) {
            $data[$monthKey]['status_summary'][$status] = 0;
        }
        $data[$monthKey]['status_summary'][$status] += 1;
    }

    if (empty($data)) {
        return "No activity data found for this period.";
    }

    $response = "📊 Marketing Total Activities:\n\n";

    foreach ($data as $monthKey => $summary) {
        $block = "";

        $block .= "📅 Period: {$monthKey}\n";
        $block .= "🔢 Marketing Codes: " . implode(', ', array_keys($summary['marketing_codes'])) . "\n";
        $block .= "📊 Total Activities: {$summary['total_activities']}\n";
        $block .= "💰 Total Cost: Rp " . number_format($summary['total_cost'], 0, ',', '.') . "\n\n";

        $block .= "👤 Marketing Specialists:\n";
        foreach ($summary['specialists'] as $specName => $activityCount) {
            $block .= "   - {$specName}: {$activityCount} activities\n";
        }

        $block .= "\n📈 Status Summary:\n";
        foreach ($summary['status_summary'] as $status => $count) {
            $emoji = match(strtoupper($status)) {
                'DRAFT' => '📝',
                'AWAITING' => '⏳',
                'COMPLETED' => '✅',
                'NOT ACHIEVED' => '🚫',
                'ONGOING' => '🔄',
                'CANCELED' => '❔',
                'APPROVAL ADDITIONAL COST' => '📎',
                default => '❔'
            };
            $block .= "   - {$emoji} {$status}: {$count}\n";
        }

        $response .= $block;
        $response .= generateSeparatorFixed() . "\n";

        $grandTotalActivities += $summary['total_activities'];
        $grandTotalCost += $summary['total_cost'];
    }

    $response .= "\n📌 Summary:\n";

    if ($summaryType == 'cost') {
        $response .= "Total Cost: Rp " . number_format($grandTotalCost, 0, ',', '.') . "\n";
    } elseif ($summaryType == 'activity') {
        $response .= "Total Activities: {$grandTotalActivities}\n";
    } else {
        $response .= "Total Activities: {$grandTotalActivities}\n";
        $response .= "Total Cost: Rp " . number_format($grandTotalCost, 0, ',', '.') . "\n";
    }

    return $response;
}
?>
