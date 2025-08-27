<?php
function handlePurchasingTotalRequest($conn, $entities) {
    $year = $entities['year'] ?? null;
    $month = $entities['month'] ?? null;

    if ($year && $month) {
        $sql = "
            SELECT COUNT(*) AS total
            FROM purchase_request_headers
            WHERE YEAR(pr_date) = $year AND MONTH(pr_date) = $month
        ";
        $label = "$month/$year";
    } elseif ($year) {
        $sql = "
            SELECT COUNT(*) AS total
            FROM purchase_request_headers
            WHERE YEAR(pr_date) = $year
        ";
        $label = "$year";
    } else {
        $sql = "
            SELECT COUNT(*) AS total
            FROM purchase_request_headers
        ";
        $label = "All time";
    }

    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $total = $row['total'] ?? 0;

    return "📊 Total Purchase Requests for $label: $total request(s)";
}

