<?php
function handlePurchasingTopRequester($conn, $entities) {
    $year = $entities['year'] ?? null;
    $month = $entities['month'] ?? null;

    $where = [];
    if ($year) $where[] = "YEAR(pr_date) = $year";
    if ($month) $where[] = "MONTH(pr_date) = $month";

    $sql = "
        SELECT requester, COUNT(*) AS total_request
        FROM purchase_request_headers
    ";
    if (count($where) > 0) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $sql .= " GROUP BY requester ORDER BY total_request DESC LIMIT 10";

    $result = $conn->query($sql);

    $periodText = "all time";
    if ($year && $month) {
        $monthName = date("F", mktime(0, 0, 0, $month, 1));
        $periodText = "$monthName $year";
    } elseif ($year) {
        $periodText = $year;
    }

    if ($result->num_rows > 0) {
        $response = "📊 Top Purchase Requester in $periodText:\n\n";
        while ($row = $result->fetch_assoc()) {
            $response .= "👤 {$row['requester']} — {$row['total_request']} requests\n";
        }
    } else {
        $response = "❌ No purchase request data found.";
    }

    return $response;
}

