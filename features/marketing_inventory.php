<?php
function handleMarketingInventory($conn, $entities) {
    $keyword = $entities['keyword'] ?? null;
    $brand = $entities['brand'] ?? null;

    $sql = "
        SELECT sm.code_barang, im.name_item, im.code_brand, sm.qty, sm.code_transaksi_terakhir, sm.updated_at, sm.code_cabang, o.address
        FROM stock_marketing sm
        JOIN item_marketing im ON sm.code_barang = im.code_item
        LEFT JOIN offices o ON sm.code_cabang = o.id
        WHERE 1=1
    
    ";

    $params = [];
    $types = '';

    if ($keyword) {
        $sql .= " AND (sm.code_barang LIKE ? OR im.name_item LIKE ?)";
        $params[] = "%{$keyword}%";
        $params[] = "%{$keyword}%";
        $types .= "ss";
    }

    if ($brand) {
        $sql .= " AND im.code_brand = ?";
        $params[] = strtoupper($brand);
        $types .= "s";
    }

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $response = "📦 Inventory Marketing:\n\n";
        while ($row = $result->fetch_assoc()) {
            $response .= "📌 Brand: {$row['code_brand']}\n";
            $response .= "🆔 Code: {$row['code_barang']} - {$row['name_item']}\n";
            $response .= "📦 Qty: {$row['qty']}\n";
            $response .= "📍 Branch: {$row['address']}\n";
            $response .= "🕒 Updated: {$row['updated_at']}\n";
            $response .= "───────────────\n";
        }
    } else {
        $response = "❌ Tidak ada data inventory marketing ditemukan.";
    }

    return $response;
}
