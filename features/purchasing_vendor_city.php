<?php
function handlePurchasingVendorCity($conn, $entities) {
    $city = $entities['city'] ?? null;

    if ($city) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) AS total FROM vendor
            WHERE TRIM(SUBSTRING_INDEX(address, ',', -1)) LIKE ?
        ");
        $likeCity = "%{$city}%";
        $stmt->bind_param("s", $likeCity);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $total = $row['total'] ?? 0;
        $response = "📍 Vendor count in $city: $total vendors.";
        $stmt->close();
    } else {
        $sql = "
            SELECT TRIM(SUBSTRING_INDEX(address, ',', -1)) AS city, COUNT(*) AS total
            FROM vendor
            GROUP BY city
            ORDER BY total DESC
        ";
        $result = $conn->query($sql);
        $response = "📊 Vendor per city:\n\n";
        while ($row = $result->fetch_assoc()) {
            $cityName = $row['city'] ?: 'Unknown';
            $response .= "• $cityName: {$row['total']} vendors\n";
        }
    }
    
    return $response;
}

