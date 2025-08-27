<?php
function handleHrTopRequesterAtk($conn, $entities) {
    $year = $entities['year'] ?? null;
    $month = $entities['month'] ?? null;

    $where = "WHERE category = 'STASIONARY' AND status = 'SUCCESS'";
    if ($year) $where .= " AND YEAR(do_date) = $year";
    if ($month) $where .= " AND MONTH(do_date) = $month";

    $monthNames = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];

    $title = "📊 Top Requester ATK";
    if ($year && $month) {
        $monthName = $monthNames[(int)$month] ?? $month;
        $title .= " $monthName $year";
    } elseif ($year) {
        $title .= " $year";
    }

    $sql = "
        SELECT created_by, COUNT(*) AS total
        FROM transaksi_h_hrd
        $where
        GROUP BY created_by
        ORDER BY total DESC
    ";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $response = "$title:\n\n";
        while ($row = $result->fetch_assoc()) {
            $response .= "👤 {$row['created_by']} — {$row['total']} requests\n";
        }
    } else {
        $response = "❌ No ATK request data found.";
    }

    return $response;
}
