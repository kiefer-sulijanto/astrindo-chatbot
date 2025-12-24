<?php
function handleServiceSummary($conn, $entities) {
    $year = $entities['year'] ?? null;
    $month = $entities['month'] ?? null;
    $department = $entities['department'] ?? null;
    $status = $entities['status'] ?? null;
    $solveType = $entities['solve_type'] ?? null;

    $conditions = [];
    if ($year) $conditions[] = "YEAR(do_date) = $year";
    if ($month) $conditions[] = "MONTH(do_date) = $month";
    if ($department) $conditions[] = "department = '" . $conn->real_escape_string($department) . "'";
    if ($status) $conditions[] = "status = '" . $conn->real_escape_string($status) . "'";
    if ($solveType) $conditions[] = "solve_type = '" . $conn->real_escape_string($solveType) . "'";

    $sql = "SELECT do_no, do_date, request_for, request_type, department, request_by, status, solve_type FROM service";
    if (count($conditions) > 0) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    $sql .= " ORDER BY do_date DESC";

    $result = $conn->query($sql);
    if (!$result || $result->num_rows === 0) {
        return "❌ No service records found for the given filters.";
    }

    $response = "🛠️ Service Summary:\n\n";
    while ($row = $result->fetch_assoc()) {
        $response .= "🔹 Service Number: {$row['do_no']}\n ";
        $response .= "📆 Service Date: {$row['do_date']}\n";
        $response .= "🙋‍♂️ Request For: {$row['request_for']}\n";
        $response .= "🔧 Request Type: {$row['request_type']}\n";
        $response .= "• Department: {$row['department']}\n";
        $response .= "• Request By: {$row['request_by']}\n";
        $response .= "• Solve Type: {$row['solve_type']}\n";
        $response .= "• Status: {$row['status']}\n\n";
    }

    return $response;
}
