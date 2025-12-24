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

    // ✅ Use HTML divider so it's always straight regardless of font
    function generateSeparatorHTML() {
    return "
        <hr style='
            border: none;
            border-top: 2px solid #bdbdbd;
            margin: 16px 0;
        '>
    ";
}


    // Promo cost column fix
    $promoCostExpr = "COALESCE(real_total_cost, revision_total_cost, estimated_total_cost, additional_total_cost, 0)";

    if ($year && !$month) {
        $sql = "
            SELECT DATE_FORMAT(activity_date, '%Y-%m') AS month, mkt_code, sign_of_applicant,
                COALESCE(real_total_cost, estimated_total_cost, 0) AS total_cost, status
            FROM marketing_activity_headers
            WHERE YEAR(activity_date) = '{$year}'
            UNION ALL
            SELECT DATE_FORMAT(promo_date, '%Y-%m') AS month, mkt_code, sign_of_applicant,
                {$promoCostExpr} AS total_cost, status
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
                {$promoCostExpr} AS total_cost, status
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
                {$promoCostExpr} AS total_cost, status
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
        $data[$monthKey]['total_cost'] += (float)$row['total_cost'];
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

    // ✅ Build output as HTML (so divider renders nicely)
    $response = "📊 <strong>Marketing Total Activities</strong><br><br>";

    foreach ($data as $monthKey => $summary) {

        $response .= "📅 <strong>Period:</strong> {$monthKey}<br>";
        $response .= "🔢 <strong>Marketing Codes:</strong> " . implode(', ', array_keys($summary['marketing_codes'])) . "<br>";
        $response .= "📊 <strong>Total Activities:</strong> {$summary['total_activities']}<br>";
        $response .= "💰 <strong>Total Cost:</strong> Rp " . number_format($summary['total_cost'], 0, ',', '.') . "<br><br>";

        $response .= "👤 <strong>Marketing Specialists:</strong><br>";
        foreach ($summary['specialists'] as $specName => $activityCount) {
            $response .= "&nbsp;&nbsp;- {$specName}: {$activityCount} activities<br>";
        }

        $response .= "<br>📈 <strong>Status Summary:</strong><br>";
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
            $safeStatus = htmlspecialchars((string)$status, ENT_QUOTES, 'UTF-8');
            $response .= "&nbsp;&nbsp;- {$emoji} {$safeStatus}: {$count}<br>";
        }

        $response .= generateSeparatorHTML();

        $grandTotalActivities += $summary['total_activities'];
        $grandTotalCost += $summary['total_cost'];
    }

    $response .= "<br>📌 <strong>Summary:</strong><br>";

    if ($summaryType === 'cost') {
        $response .= "Total Cost: Rp " . number_format($grandTotalCost, 0, ',', '.') . "<br>";
    } elseif ($summaryType === 'activity') {
        $response .= "Total Activities: {$grandTotalActivities}<br>";
    } else {
        $response .= "Total Activities: {$grandTotalActivities}<br>";
        $response .= "Total Cost: Rp " . number_format($grandTotalCost, 0, ',', '.') . "<br>";
    }

    return $response;
}
?>
