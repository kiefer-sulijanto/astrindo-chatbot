<?php
function handleMarketingSpecialistCost($conn, $entities) {

    $specialist = isset($entities['specialist']) ? $entities['specialist'] : null;
    $year       = isset($entities['year']) ? $entities['year'] : null;
    $month      = isset($entities['month']) ? $entities['month'] : null;

    if (!$specialist) {
        return "⚠️ Specialist not specified.";
    }


    // Step 1: Lookup actual email from DB based on name fragment
    $likeName = '%' . strtolower($specialist) . '%';
    $stmtEmail = $conn->prepare("
        SELECT DISTINCT sign_of_applicant
        FROM marketing_activity_headers
        WHERE LOWER(sign_of_applicant) LIKE ?
        LIMIT 1
    ");
    $stmtEmail->bind_param("s", $likeName);
    $stmtEmail->execute();
    $resultEmail = $stmtEmail->get_result();
    $rowEmail = $resultEmail->fetch_assoc();
    $stmtEmail->close();

    if (!$rowEmail) {
        return "❌ Tidak ditemukan email marketing dengan nama \"$specialist\".";
    }

    $email = $rowEmail['sign_of_applicant'];

    // Step 2: Handle period condition
    $periodCondition = '';
    $periodValue = '';

    if ($year && $month) {
        $periodCondition = "%Y-%m";
        $periodValue = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT);
    } elseif ($year) {
        $periodCondition = "%Y";
        $periodValue = "$year";
    } else {
        $periodCondition = "%Y-%m";
        $resultMonth = $conn->query("SELECT MAX(DATE_FORMAT(activity_date, '%Y-%m')) AS latest_month FROM marketing_activity_headers");
        $rowMonth = $resultMonth->fetch_assoc();
        $periodValue = $rowMonth['latest_month'];
    }

    $totalCost = 0;
    $totalActivities = 0;
    $used = 'none';

    file_put_contents("debug_used_cost.txt", "🔍 Start Debug for $email | Period: $periodValue\n");

    // Step 3: Query marketing activities
    $stmt = $conn->prepare("
        SELECT real_total_cost, estimated_total_cost
        FROM marketing_activity_headers
        WHERE sign_of_applicant = ? AND DATE_FORMAT(activity_date, ?) = ?
    ");

    $stmt->bind_param("sss", $email, $periodCondition, $periodValue);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        if (!is_null($row['real_total_cost'])) {
            $totalCost += $row['real_total_cost'];
            $used = 'real';
        } elseif (!is_null($row['estimated_total_cost'])) {
            $totalCost += $row['estimated_total_cost'];
            $used = 'estimated';
        } else {
            $used = 'none';
        }

        file_put_contents("debug_used_cost.txt", "Marketing Activity | $email | $periodValue | Used: $used | Amount: " . ($row['real_total_cost'] ?? $row['estimated_total_cost'] ?? 0) . "\n", FILE_APPEND);
        $totalActivities++;
    }

    $stmt->close();

    if ($totalActivities === 0) {
        file_put_contents("debug_used_cost.txt", "❌ No data found for $email | Period: $periodValue\n", FILE_APPEND);
    }

    $response = "📊 Total Marketing Cost for $specialist (Period: $periodValue)\n";
    $response .= "💰 Total Cost: Rp " . number_format($totalCost, 0, ',', '.') . "\n";
    $response .= "📝 Total Activities: $totalActivities";

    return $response;
}
?>
