<?php
function handleHrTopItemAtk($conn, $entities) {
    $year = $entities['year'] ?? null;
    $month = $entities['month'] ?? null;

    $sql = "
        SELECT code_item, SUM(CAST(qty AS UNSIGNED)) AS total_qty
        FROM transaksi_d_hrd
        WHERE 1=1
    ";
    if ($year) $sql .= " AND YEAR(created_at) = $year";
    if ($month) $sql .= " AND MONTH(created_at) = $month";
    $sql .= " GROUP BY code_item ORDER BY total_qty DESC";

    $result = $conn->query($sql);

    // Get readable month name if needed
    $monthName = $month ? date("F", mktime(0, 0, 0, $month, 10)) : null;

    // Title with dynamic date label
    if ($month && $year) {
        $title = "📦 Most Requested ATK Items $monthName $year:\n\n";
    } elseif ($year) {
        $title = "📦 Most Requested ATK Items in $year:\n\n";
    } else {
        $title = "📦 Most Requested ATK Items:\n\n";
    }

    if ($result->num_rows > 0) {
        $response = $title;
        while ($row = $result->fetch_assoc()) {
            $response .= "• {$row['code_item']}: {$row['total_qty']} pcs\n";
        }
    } else {
        $response = "❌ No data found for ATK items.";
    }

    return $response;
}
