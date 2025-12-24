<?php

function handleFinanceTopSender($conn, $entities) {
    $year = $entities['year'] ?? null;
    $month = $entities['month'] ?? null;

    $where = "WHERE status = 'SUCCESS'";
    if ($year) $where .= " AND YEAR(do_date) = $year";
    if ($month) $where .= " AND MONTH(do_date) = $month";

    $monthNames = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];

    $title = "📊 Top Requester Finance Items";
    if ($year && $month) {
        $monthName = $monthNames[(int)$month] ?? $month;
        $title .= " $monthName $year";
    } elseif ($year) {
        $title .= " $year";
    }

    $sql = "
        SELECT sender, COUNT(*) AS total
        FROM transaksi_finance_h
        $where
        GROUP BY sender
        ORDER BY total DESC
    ";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $response = "$title:\n\n";
        while ($row = $result->fetch_assoc()) {
            $response .= "👤 {$row['sender']} — {$row['total']} requests\n";
        }
    } else {
        $response = "❌ No Sender request data found.";
    }

    return $response;
}
