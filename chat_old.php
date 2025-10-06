<?php

// # Error Reporting and Headers
// error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
// ini_set('display_errors', 0);
// header("Content-Type: application/json");
// session_start();
// include("db.php");

// # OpenAI GPT-4o API Call
// $openaiKey = getenv('OPENAI_API_KEY');

// if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
//     http_response_code(405);
//     echo json_encode(["status" => "error", "message" => "Method Not Allowed"]);
//     exit;
// }

// $data = json_decode(file_get_contents("php://input"), true);
// $userMessage = $data['message'] ?? '';

// if (empty($userMessage)) {
//     echo json_encode(["status" => "error", "message" => "Please enter a message."]);
//     exit;
// }

// # SECTION 1 : MARKETING
// # Feature 1 : Tracking Total Marketing Activities and Costs by Marketing Specialist (Universal)

// # *stripos not effective enough, its only for short time because its manual (later use NLU)
// if (
//     stripos($userMessage, 'total activity') !== false || 
//     stripos($userMessage, 'total activities') !== false || 
//     stripos($userMessage, 'activity summary') !== false ||
//     stripos($userMessage, 'activity') !== false ||
//     stripos($userMessage, 'marketing activity') !== false ||
//     stripos($userMessage, 'marketing activities') !== false 
// ) {

//     function formatNameFromEmail($email) {
//         if (!$email) return '-';
//         $namePart = explode('@', $email)[0];
//         $namePart = str_replace('.', ' ', $namePart);
//         $namePart = ucwords($namePart);
//         return $namePart;
//     }

//     $monthMap = [
//         'january'=>'01','february'=>'02','march'=>'03','april'=>'04','may'=>'05',
//         'june'=>'06','july'=>'07','august'=>'08','september'=>'09','october'=>'10','november'=>'11','december'=>'12'
//     ];

//     $requestedMonth = null;

//     if (preg_match('/(20\d{2})[-\/](0[1-9]|1[0-2])/', $userMessage, $matches)) {
//         $requestedMonth = $matches[1] . "-" . $matches[2];
//     } elseif (preg_match('/(january|february|march|april|may|june|july|august|september|october|november|december)\s+(20\d{2})/i', $userMessage, $matches)) {
//         $monthWord = strtolower($matches[1]);
//         $year = $matches[2];
//         $monthNum = $monthMap[$monthWord];
//         $requestedMonth = $year . "-" . $monthNum;
//     } elseif (preg_match('/(20\d{2})/', $userMessage, $matches)) {
//         $requestedYear = $matches[1];

//         $sql = "
//             SELECT 
//                 DATE_FORMAT(activity_date, '%Y-%m') AS month,
//                 mkt_code,
//                 sign_of_applicant,
//                 COALESCE(real_total_cost, estimated_total_cost, 0) AS total_cost,
//                 status
//             FROM marketing_activity_headers
//             WHERE DATE_FORMAT(activity_date, '%Y') = '$requestedYear'
//             UNION ALL
//             SELECT 
//                 DATE_FORMAT(promo_date, '%Y-%m') AS month,
//                 mkt_code,
//                 sign_of_applicant,
//                 COALESCE(real_budget, budget, 0) AS total_cost,
//                 status
//             FROM marketing_promo_headers
//             WHERE DATE_FORMAT(promo_date, '%Y') = '$requestedYear'
//         ";

//         $result = $conn->query($sql);
//         $data = [];

//         while ($row = $result->fetch_assoc()) {
//             $month = $row['month'];
//             $mktCode = $row['mkt_code'];
//             $name = formatNameFromEmail($row['sign_of_applicant']);
//             $status = $row['status'] ?? 'UNKNOWN';

//             if (!isset($data[$month])) {
//                 $data[$month] = [
//                     'total_activities' => 0,
//                     'total_cost' => 0,
//                     'marketing_codes' => [],
//                     'specialists' => [],
//                     'status_summary' => []
//                 ];
//             }

//             $data[$month]['total_activities'] += 1;
//             $data[$month]['total_cost'] += $row['total_cost'];
//             $data[$month]['marketing_codes'][$mktCode] = true;

//             if (!isset($data[$month]['specialists'][$name])) {
//                 $data[$month]['specialists'][$name] = 0;
//             }
//             $data[$month]['specialists'][$name] += 1;

//             if (!isset($data[$month]['status_summary'][$status])) {
//                 $data[$month]['status_summary'][$status] = 0;
//             }
//             $data[$month]['status_summary'][$status] += 1;
//         }

//         $response = "📊 Total Activities for Year: {$requestedYear}\n\n";

//         $separator = str_repeat("─", 47);

//         if (count($data) > 0) {
//             foreach ($data as $month => $summary) {
//                 $marketingCodes = implode(', ', array_keys($summary['marketing_codes']));
//                 $response .= $separator . "\n";
//                 $response .= "📌 Month: {$month}\n";
//                 $response .= "🔢 Marketing Code(s): {$marketingCodes}\n";
//                 $response .= "📊 Total Activities: {$summary['total_activities']}\n";
//                 $response .= "💰 Total Cost: Rp " . number_format($summary['total_cost'], 0, ',', '.') . "\n\n";

//         $response .= "👤 Marketing Specialists:\n";
//         foreach ($summary['specialists'] as $specName => $activityCount) {
//             $response .= "   - {$specName}: {$activityCount} activities\n";
//         }

//         $response .= "\n📈 Status Summary:\n";
//         foreach ($summary['status_summary'] as $status => $count) {
//             $emoji = match(strtoupper($status)) {
//                 'DRAFT' => '📝',
//                 'AWAITING' => '⏳',
//                 'AWAITING REALIZATION' => '📝',
//                 'REALIZATION APPROVAL' => '📝',
//                 'AWAITING APPROVAL RECAP' => '📝',
//                 'AWAITING APPROVAL REVISE' => '📝',
//                 'AWAITING CLAIM' => '📝',
//                 'COMPLETED' => '✅',
//                 'NOT ACHIEVED' => '🚫',
//                 'ONGOING' => '🔄',
//                 'APPROVAL ADDITIONAL COST' => '📎',
//                 default => '❔'
//             };
//             $response .= "   - {$emoji} {$status}: {$count}\n";
//         }
//         $response .= "\n";

//             }
//         } else {
//             $response = "No activity data found for {$requestedYear}.";
//         }

//         echo json_encode(["status" => "success", "message" => $response]);
//         exit;

//     } else {
//         $latestMonthSql = "
//             SELECT MAX(month) AS latest_month FROM (
//                 SELECT DATE_FORMAT(activity_date, '%Y-%m') AS month FROM marketing_activity_headers
//                 UNION
//                 SELECT DATE_FORMAT(promo_date, '%Y-%m') AS month FROM marketing_promo_headers
//             ) all_months
//         ";
//         $resultMonth = $conn->query($latestMonthSql);
//         $rowMonth = $resultMonth->fetch_assoc();
//         $requestedMonth = $rowMonth['latest_month'];
//     }

//     $sql = "
//         SELECT 
//             DATE_FORMAT(activity_date, '%Y-%m') AS month,
//             mkt_code,
//             sign_of_applicant,
//             COALESCE(real_total_cost, estimated_total_cost, 0) AS total_cost,
//             status
//         FROM marketing_activity_headers
//         WHERE DATE_FORMAT(activity_date, '%Y-%m') = '$requestedMonth'
//         UNION ALL
//         SELECT 
//             DATE_FORMAT(promo_date, '%Y-%m') AS month,
//             mkt_code,
//             sign_of_applicant,
//             COALESCE(real_budget, budget, 0) AS total_cost,
//             status
//         FROM marketing_promo_headers
//         WHERE DATE_FORMAT(promo_date, '%Y-%m') = '$requestedMonth'
//     ";

//     $result = $conn->query($sql);
//     $data = [
//         'total_activities' => 0,
//         'total_cost' => 0,
//         'marketing_codes' => [],
//         'specialists' => [],
//         'status_summary' => []
//     ];

//     while ($row = $result->fetch_assoc()) {
//         $mktCode = $row['mkt_code'];
//         $name = formatNameFromEmail($row['sign_of_applicant']);
//         $status = $row['status'] ?? 'UNKNOWN';

//         $data['total_activities'] += 1;
//         $data['total_cost'] += $row['total_cost'];
//         $data['marketing_codes'][$mktCode] = true;

//         if (!isset($data['specialists'][$name])) {
//             $data['specialists'][$name] = 0;
//         }
//         $data['specialists'][$name] += 1;

//         if (!isset($data['status_summary'][$status])) {
//             $data['status_summary'][$status] = 0;
//         }
//         $data['status_summary'][$status] += 1;
//     }

//     $response = "📊 Total Activities for Month: {$requestedMonth}\n\n";
// if ($data['total_activities'] > 0) {
//     $marketingCodes = implode(', ', array_keys($data['marketing_codes']));
//     $response .= "🔢 Marketing Code(s): {$marketingCodes}\n";
//     $response .= "📊 Total Activities: {$data['total_activities']}\n";
//     $response .= "💰 Total Cost: Rp " . number_format($data['total_cost'], 0, ',', '.') . "\n\n";

//     $response .= "👤 Marketing Specialists:\n";
//     foreach ($data['specialists'] as $specName => $activityCount) {
//         $response .= "   - {$specName}: {$activityCount} activities\n";
//     }

//     $response .= "\n📈 Status Summary:\n";
//     foreach ($data['status_summary'] as $status => $count) {
//         $emoji = match(strtoupper($status)) {
//             'DRAFT' => '📝',
//             'AWAITING' => '⏳',
//             'AWAITING REALIZATION' => '📝',
//             'REALIZATION APPROVAL' => '📝',
//             'AWAITING APPROVAL RECAP' => '📝',
//             'AWAITING APPROVAL REVISE' => '📝',
//             'AWAITING CLAIM' => '📝',
//             'COMPLETED' => '✅',
//             'NOT ACHIEVED' => '🚫',
//             'ONGOING' => '🔄',
//             'CANCELED' => '❔',
//             'APPROVAL ADDITIONAL COST' => '📎',
//             default => '❔'
//         };
//         $response .= "   - {$emoji} {$status}: {$count}\n";
//     }
// } else {
//     $response = "No activity data found for {$requestedMonth}.";
// }


//     echo json_encode(["status" => "success", "message" => $response]);
//     exit;
// }


// # Feature 2 : Calculate Total Cost Per Marketing Specialist (email and name normalization)
// # *stripos not effective enough, its only for short time because its manual (later use NLU)
// if (
//     stripos($userMessage, 'cost by') !== false || 
//     stripos($userMessage, 'total cost for') !== false || 
//     stripos($userMessage, 'marketing cost for') !== false ||
//     stripos($userMessage, 'total biaya') !== false ||
//     stripos($userMessage, 'pembayaran') !== false ||
//     stripos($userMessage, 'total pembayaran') !== false ||
//     stripos($userMessage, 'biaya marketing') !== false 
// ) {

//     function extractInput($text) {
//         if (preg_match('/(?:cost by|total cost for|marketing cost for|total biaya|pembayaran|total|biaya marketing)\s+(.+?)(?:\sin\s|$)/i', $text, $matches)) {
//             return trim($matches[1]);
//         }
//         return null;
//     }
//     $specialistInput = extractInput($userMessage);
//     if (!$specialistInput) {
//         echo json_encode(["message" => "Cannot detect the specialist name or email."]);
//         exit;
//     }

//     if (strpos($specialistInput, '@') !== false) {
//         $specialistEmail = strtolower($specialistInput);
//         $specialistName = ''; 
//         $nameLike = '%';
//     } else {
//         $specialistEmail = strtolower(str_replace(' ', '.', $specialistInput)) . '@astrindo.co.id';
//         $specialistName = $specialistInput;
//         $nameLike = "%" . $specialistName . "%";
//     }
//     $monthMap = [
//         'january'=>'01','february'=>'02','march'=>'03','april'=>'04','may'=>'05',
//         'june'=>'06','july'=>'07','august'=>'08','september'=>'09','october'=>'10','november'=>'11','december'=>'12'
//     ];

//     $requestedMonthYear = null;
//     $requestedYear = null;

//     if (preg_match('/(20\d{2})[-\/](0[1-9]|1[0-2])/', $userMessage, $matches)) {
//         $requestedMonthYear = $matches[1] . "-" . $matches[2];
//     } elseif (preg_match('/(january|february|march|april|may|june|july|august|september|october|november|december)\s+(20\d{2})/i', $userMessage, $matches)) {
//         $monthWord = strtolower($matches[1]);
//         $year = $matches[2];
//         $monthNum = $monthMap[$monthWord];
//         $requestedMonthYear = $year . "-" . $monthNum;
//     } elseif (preg_match('/(20\d{2})/', $userMessage, $matches)) {
//         $requestedYear = $matches[1];
//     }

//     $periodCondition = '';
//     $periodValue = '';

//     if ($requestedMonthYear) {
//         $periodCondition = "%Y-%m";
//         $periodValue = $requestedMonthYear;
//     } elseif ($requestedYear) {
//         $periodCondition = "%Y";
//         $periodValue = $requestedYear;
//     } else {
//         // fallback to latest month
//         $latestMonthSql = "
//             SELECT MAX(month) AS latest_month FROM (
//                 SELECT DATE_FORMAT(activity_date, '%Y-%m') AS month FROM marketing_activity_headers
//                 UNION
//                 SELECT DATE_FORMAT(promo_date, '%Y-%m') AS month FROM marketing_promo_headers
//             ) all_months
//         ";
//         $resultMonth = $conn->query($latestMonthSql);
//         $rowMonth = $resultMonth->fetch_assoc();
//         $periodCondition = "%Y-%m";
//         $periodValue = $rowMonth['latest_month'];
//     }

//     $totalCost = 0;
//     $totalActivities = 0;
//     $mktCodes = [];

//     /* ========== FOR marketing_activity_headers ========== */
//     if ($periodCondition == "%Y-%m") {
//         $stmt = $conn->prepare("SELECT mkt_code, COALESCE(real_total_cost, estimated_total_cost, 0) AS total_cost 
//             FROM marketing_activity_headers 
//             LEFT JOIN users ON marketing_activity_headers.sign_of_applicant = users.email
//             WHERE (marketing_activity_headers.sign_of_applicant = ?  OR 
//   LOWER(REPLACE(users.name, ' ', '')) LIKE LOWER(REPLACE(?, ' ', ''))) 
//             AND DATE_FORMAT(activity_date, '%Y-%m') = ?");
//         $stmt->bind_param("sss", $specialistEmail, $nameLike, $periodValue);
//     } else {
//         $stmt = $conn->prepare("SELECT mkt_code, COALESCE(real_total_cost, estimated_total_cost, 0) AS total_cost 
//             FROM marketing_activity_headers 
//             LEFT JOIN users ON marketing_activity_headers.sign_of_applicant = users.email
//             WHERE (marketing_activity_headers.sign_of_applicant = ?  OR 
//   LOWER(REPLACE(users.name, ' ', '')) LIKE LOWER(REPLACE(?, ' ', '')))
//             AND DATE_FORMAT(activity_date, '%Y') = ?");
//         $stmt->bind_param("sss", $specialistEmail, $nameLike, $periodValue);
//     }
    
//     $stmt->execute();
//     $result = $stmt->get_result();

//     while ($row = $result->fetch_assoc()) {
//         $totalCost += $row['total_cost'];
//         $totalActivities++;
//         if (!empty($row['mkt_code'])) {
//             $mktCodes[] = $row['mkt_code'];
//         }
//     }
//     $stmt->close();

//     /* ========== FOR marketing_promo_headers ========== */
//     if ($periodCondition == "%Y-%m") {
//         $stmt2 = $conn->prepare("SELECT mkt_code, COALESCE(real_budget, budget, 0) AS total_cost 
//             FROM marketing_promo_headers 
//             LEFT JOIN users ON marketing_promo_headers.sign_of_applicant = users.email
//             WHERE (marketing_promo_headers.sign_of_applicant = ?  OR 
//   LOWER(REPLACE(users.name, ' ', '')) LIKE LOWER(REPLACE(?, ' ', '')))
//             AND DATE_FORMAT(promo_date, '%Y-%m') = ?");
//         $stmt2->bind_param("sss", $specialistEmail, $nameLike, $periodValue);
//     } else {
//         $stmt2 = $conn->prepare("SELECT mkt_code, COALESCE(real_budget, budget, 0) AS total_cost 
//             FROM marketing_promo_headers 
//             LEFT JOIN users ON marketing_promo_headers.sign_of_applicant = users.email
//             WHERE (marketing_promo_headers.sign_of_applicant = ?  OR 
//   LOWER(REPLACE(users.name, ' ', '')) LIKE LOWER(REPLACE(?, ' ', '')))
//             AND DATE_FORMAT(promo_date, '%Y') = ?");
//         $stmt2->bind_param("sss", $specialistEmail, $nameLike, $periodValue);
//     }
    
//     $stmt2->execute();
//     $result2 = $stmt2->get_result();

//     while ($row2 = $result2->fetch_assoc()) {
//         $totalCost += $row2['total_cost'];
//         $totalActivities++;
//         if (!empty($row2['mkt_code'])) {
//             $mktCodes[] = $row2['mkt_code'];
//         }
//     }
//     $stmt2->close();

//     /* ========== Final Output ========== */
//     if ($requestedMonthYear) {
//         $periodText = date("F Y", strtotime($requestedMonthYear . "-01"));
//     } elseif ($requestedYear) {
//         $periodText = $requestedYear;
//     } else {
//         $periodText = date("F Y", strtotime($periodValue . "-01"));
//     }

//     if ($totalActivities == 0) {
//         $response = "❌ No marketing cost data found for {$specialistEmail} in {$periodText}.";
//     } else {
//         $response = "📊 Total Marketing Cost for {$specialistEmail} ({$periodText})\n\n";
//         $response .= "💰 Total Cost: Rp " . number_format($totalCost, 0, ',', '.') . "\n";
//         $response .= "🔢 Total Activities: {$totalActivities}\n";
//         $response .= "📄 Marketing Codes: " . (!empty($mktCodes) ? implode(', ', $mktCodes) : '-') . "\n";
//     }

//     echo json_encode(["status" => "success", "message" => $response]);
//     exit;
// }

// #Feature 3 : Inventory Marketing (Search Stock)
// # *stripos not effective enough, its only for short time because its manual (later use NLU)

// if (
//     stripos($userMessage, 'inventory') !== false || 
//     stripos($userMessage, 'stock') !== false || 
//     stripos($userMessage, 'marketing stock') !== false ||
//     stripos($userMessage, 'stok') !== false ||
//     stripos($userMessage, 'sisa stok') !== false ||
//     stripos($userMessage, 'total sisa stock') !== false ||
//     stripos($userMessage, 'total stock') !== false ||
//     stripos($userMessage, 'inventory stock') !== false  
// ) {

//     // Optional: extract keyword if user search specific code or name
//     $searchKeyword = null;
//     if (preg_match('/(?:inventory|stock|marketing stock|stok|sisa stok|total sisa stock|total stock|inventory stock)\s+(.*)/i', $userMessage, $matches)) {
//         $searchKeyword = trim($matches[1]);
//     }

//     // Build SQL Query with JOIN to offices table
//     $sql = "
//         SELECT sm.code_barang, im.name_item, im.code_brand, sm.qty, sm.code_transaksi_terakhir, sm.updated_at, sm.code_cabang, o.address
//         FROM stock_marketing sm
//         JOIN item_marketing im ON sm.code_barang = im.code_item
//         LEFT JOIN offices o ON sm.code_cabang = o.id
//     ";

//     if ($searchKeyword) {
//         $sql .= " WHERE sm.code_barang LIKE '%$searchKeyword%' OR im.name_item LIKE '%$searchKeyword%' ";
//     }

//     $sql .= " ORDER BY im.code_brand ASC, sm.code_barang ASC";

//     $result = $conn->query($sql);
    
//     if ($result->num_rows > 0) {
//         $groupedData = [];

//         // Grouping by brand
//         while ($row = $result->fetch_assoc()) {
//             $brand = $row['code_brand'];
//             if (!isset($groupedData[$brand])) {
//                 $groupedData[$brand] = [];
//             }
//             $groupedData[$brand][] = $row;
//         }

//         $response = "📦 Inventory Marketing Stock Grouped by Brand:\n\n";
//         foreach ($groupedData as $brand => $items) {
//             $response .= "🔖 Brand: {$brand}\n\n";
//             foreach ($items as $item) {
//                 $response .= "🆔 Code: {$item['code_barang']}\n";  
//                 $response .= "📄 Name: {$item['name_item']}\n";
//                 $response .= "📦 Qty: {$item['qty']}\n";
//                 $response .= "🌆 Branch Address: {$item['address']}\n"; 
//                 $response .= "📑 Last Transaction: {$item['code_transaksi_terakhir']}\n";
//                 $response .= "🕑 Last Update: {$item['updated_at']}\n";
//                 $response .= "──────────────────────────────\n";
//             }
//             $response .= "\n";
//         }
//     } else {
//         $response = "No inventory data found.";
//     }

//     echo json_encode(["status" => "success", "message" => $response]);
//     exit;
// }


// #Section 2 : Purchasing
// #Feature 1: Tracking the most requested purchase by Requester + Details
// if (
//     stripos($userMessage, 'most request purchase') !== false ||
//     stripos($userMessage, 'paling banyak request purchase') !== false ||
//     stripos($userMessage, 'most purchase request') !== false ||
//     stripos($userMessage, 'paling banyak purchase request') !== false  
// ) {
//     $limit = 10;
//     if (preg_match('/top\s+(\d+)/i', $userMessage, $matches)) {
//         $limit = intval($matches[1]);
//     }

//     // Extract Year and Month
//     $monthNames = [
//         'january' => '01','february' => '02','march' => '03','april' => '04',
//         'may' => '05','june' => '06','july' => '07','august' => '08',
//         'september' => '09','october' => '10','november' => '11','december' => '12'
//     ];

//     $month = null;
//     $year = null;

//     // Priority 1: extract Month + Year
//     if (preg_match('/(' . implode('|', array_keys($monthNames)) . ')\s+(20\d{2})/i', $userMessage, $matches)) {
//         $monthText = strtolower($matches[1]);
//         $year = $matches[2];
//         $month = $monthNames[$monthText];
//     } else {
//         // Priority 2: extract Year only
//         if (preg_match('/\b(20\d{2})\b/', $userMessage, $matches)) {
//             $year = $matches[1];
//         }

//         // Priority 3: extract Month only (just for safety)
//         foreach ($monthNames as $monthText => $monthNumber) {
//             if (stripos($userMessage, $monthText) !== false) {
//                 $month = $monthNumber;
//                 break;
//             }
//         }
//     }

//     // Build SQL
//     $sql = "
//         SELECT
//             IFNULL(requester, '-') AS requester,
//             COUNT(*) AS total_requests,
//             MAX(pr_no) AS latest_pr_no,
//             MAX(pr_date) AS latest_pr_date,
//             MAX(purpose) AS latest_purpose,
//             MAX(destination) AS latest_destination,
//             MAX(vendor_id) AS latest_vendor_id,
//             MAX(created_at) AS latest_created_at
//         FROM purchase_request_headers
//     ";

//     // Apply WHERE condition if year/month detected
//     $where = [];

//     if ($year) {
//         $where[] = "YEAR(pr_date) = $year";
//     }
//     if ($month) {
//         $where[] = "MONTH(pr_date) = $month";
//     }

//     if (count($where) > 0) {
//         $sql .= " WHERE " . implode(" AND ", $where);
//     }

//     $sql .= " GROUP BY requester ORDER BY total_requests DESC LIMIT {$limit}";

//     $result = $conn->query($sql);

//     if ($result->num_rows > 0) {
//         $filterText = "";
//         if ($year && $month) {
//             $monthText = ucfirst(array_search($month, $monthNames));
//             $filterText = "in $monthText $year";
//         } elseif ($year) {
//             $filterText = "in $year";
//         }

//         $response = "📊 Top $limit Requester Purchase {$filterText}:\n\n";
//         $rank = 1;
//         while($row = $result->fetch_assoc()) {
//             $prDate = (!empty($row['latest_pr_date'])) ? date("d M Y", strtotime($row['latest_pr_date'])) : "-";
//             $createdDate = (!empty($row['latest_created_at'])) ? date("d M Y H:i", strtotime($row['latest_created_at'])) : "-";
//             $purpose = (!empty($row['latest_purpose'])) ? $row['latest_purpose'] : "-";
//             $destination = (!empty($row['latest_destination'])) ? $row['latest_destination'] : "-";
//             $vendorId = (!empty($row['latest_vendor_id'])) ? $row['latest_vendor_id'] : "-";

//             $response .= "{$rank}. 👤 {$row['requester']} — {$row['total_requests']} request(s)\n";
//             $response .= "📄 Latest PR: {$row['latest_pr_no']} on {$prDate}\n";
//             $response .= "🎯 Purpose: {$purpose}\n";
//             $response .= "🌎 Destination: {$destination}\n";
//             $response .= "🏷️ Vendor ID: {$vendorId}\n";
//             $response .= "🕑 Created: {$createdDate}\n\n";
//             $rank++;
//         }
//     } else {
//         $response = "❌ No purchase request data found for this period.";
//     }

//     echo json_encode(["status" => "success", "message" => $response]);
//     exit;
// }


// #Feature 2: Total Request Purchase (Smart Simplified Version)
// if (
//     stripos($userMessage, 'total request purchase') !== false || 
//     stripos($userMessage, 'jumlah request purchase') !== false ||
//     stripos($userMessage, 'jumlah purchase request') !== false ||
//     stripos($userMessage, 'total purchase request') !== false
// ) {
//     // --- Extract Year & Month ---
//     $monthNames = [
//         'january' => '01','february' => '02','march' => '03','april' => '04',
//         'may' => '05','june' => '06','july' => '07','august' => '08',
//         'september' => '09','october' => '10','november' => '11','december' => '12'
//     ];

//     $month = null;
//     $year = null;

//     // First priority: Month + Year pattern
//     if (preg_match('/(' . implode('|', array_keys($monthNames)) . ')\s+(20\d{2})/i', $userMessage, $matches)) {
//         $monthText = strtolower($matches[1]);
//         $year = $matches[2];
//         $month = $monthNames[$monthText];
//     } else {
//         // Fallback: Year only
//         if (preg_match('/\b(20\d{2})\b/', $userMessage, $matches)) {
//             $year = $matches[1];
//         }
//         // Fallback: detect month (even without year)
//         foreach ($monthNames as $monthText => $monthNumber) {
//             if (stripos($userMessage, $monthText) !== false) {
//                 $month = $monthNumber;
//                 break;
//             }
//         }
//     }

//     $isPerYear = stripos($userMessage, 'per year') !== false;

//     // --- CASE 1: Month + Year ---
//     if ($year && $month) {
//         $sql = "
//             SELECT COUNT(*) AS total_request
//             FROM purchase_request_headers
//             WHERE YEAR(pr_date) = $year AND MONTH(pr_date) = $month
//         ";
//         $result = $conn->query($sql);
//         $row = $result->fetch_assoc();
//         $monthText = ucfirst(array_search($month, $monthNames));
//         $total = $row['total_request'] ?? 0;
//         $response = "📊 Total Request Purchase for $monthText $year: {$total} requests";
//         echo json_encode(["status" => "success", "message" => $response]);
//         exit;
//     }

//     // --- CASE 2: Year only ---
//     if ($year) {
//         $sql = "
//             SELECT COUNT(*) AS total_request
//             FROM purchase_request_headers
//             WHERE YEAR(pr_date) = $year
//         ";
//         $result = $conn->query($sql);
//         $row = $result->fetch_assoc();
//         $total = $row['total_request'] ?? 0;
//         $response = "📊 Total Request Purchase for $year: {$total} requests";
//         echo json_encode(["status" => "success", "message" => $response]);
//         exit;
//     }

//     // --- CASE 3: per year summary ---
//     if ($isPerYear) {
//         $sql = "
//             SELECT YEAR(pr_date) AS year, COUNT(*) AS total_request
//             FROM purchase_request_headers
//             GROUP BY year
//             ORDER BY year DESC
//         ";
//         $result = $conn->query($sql);
//         $response = "📊 Total Request Purchase per Year:\n\n";
//         $chartData = [];

//         while ($row = $result->fetch_assoc()) {
//             $response .= "📅 {$row['year']} : {$row['total_request']} requests\n";
//             $chartData[] = [
//                 'year' => $row['year'],
//                 'total' => (int)$row['total_request']
//             ];
//         }

//         echo json_encode(["status" => "success", "message" => $response, "chart" => $chartData]);
//         exit;
//     }

//     // --- CASE 4: fallback last 12 months ---
//     $sql = "
//         SELECT DATE_FORMAT(pr_date, '%Y-%m') AS request_month, COUNT(*) AS total_request
//         FROM purchase_request_headers
//         WHERE pr_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
//         GROUP BY request_month
//         ORDER BY request_month ASC
//     ";
//     $result = $conn->query($sql);
//     $response = "📊 Total Request Purchase per Month (Last 12 Months):\n\n";
//     $chartData = [];

//     while ($row = $result->fetch_assoc()) {
//         $response .= "🗓️ {$row['request_month']} : {$row['total_request']} requests\n";
//         $chartData[] = [
//             'month' => $row['request_month'],
//             'total' => (int)$row['total_request']
//         ];
//     }

//     echo json_encode(["status" => "success", "message" => $response, "chart" => $chartData]);
//     exit;
// }
//     // ✅ Generic fallback for general purchase request keyword
//     if (
//         stripos($userMessage, 'purchase request') !== false || 
//         stripos($userMessage, 'request purchase') !== false
//     ) {
//         $response = "Please specify what you want to know:\n\n".
//                     "🟢 Type 'total request purchase per month' to see monthly summary.\n".
//                     "🟢 Type 'total request purchase per year' (or any year) to see yearly data.\n".
//                     "🟢 Type 'most request purchase' to see most requester summary.\n\n".
//                     "I'll be happy to help!";

//         echo json_encode(["status" => "success", "message" => $response]);
//         exit;
//     }

// #Feature 3 : Count Vendor by City
//     if (
//         stripos($userMessage, 'jumlah vendor per city') !== false || 
//         stripos($userMessage, 'jumlah vendor berdasarkan kota') !== false ||
//         preg_match('/jumlah vendor di\s+(.+)/i', $userMessage, $matches)
//     ) {
//         // Check if city is specified
//         if (isset($matches[1])) {
//             $cityInput = trim($matches[1]);
    
//             // Query to count vendor in specific city
//             $sql = "
//                 SELECT 
//                     COUNT(*) AS jumlah_vendor
//                 FROM vendor
//                 WHERE TRIM(SUBSTRING_INDEX(address, ',', -1)) LIKE '%$cityInput%'
//             ";
    
//             $result = $conn->query($sql);
            
//             if ($result->num_rows > 0) {
//                 $row = $result->fetch_assoc();
//                 $jumlah = $row['jumlah_vendor'];
//                 $response = "📍 Jumlah vendor di $cityInput: $jumlah vendor.";
//             } else {
//                 $response = "Data vendor di $cityInput tidak ditemukan.";
//                                     }
//         } else {
//             // Normal query for all cities
//             $sql = "
//                 SELECT 
//                     TRIM(SUBSTRING_INDEX(address, ',', -1)) AS city,
//                     COUNT(*) AS jumlah_vendor 
//                 FROM vendor
//                 GROUP BY city
//                 ORDER BY jumlah_vendor DESC
//             ";
    
//             $result = $conn->query($sql);
            
//             if ($result->num_rows > 0) {
//                 $response = "📊 Jumlah Vendor Berdasarkan Kota:\n\n";
//                 while($row = $result->fetch_assoc()) {
//                     $city = $row['city'] ?: 'Unknown';
//                     $jumlah = $row['jumlah_vendor'];
//                     $response .= "• $city : $jumlah vendor\n";
//                 }
//             } else {
//                 $response = "Data vendor tidak ditemukan.";
//             }
//         }
    
//         echo json_encode(["message" => $response]);
//         exit;
//     }

// #Section 3 : HR
// #Feature 1 : Track who's the most request ATK
// if (
//     stripos($userMessage, 'paling banyak request ATK') !== false ||
//     stripos($userMessage, 'top request ATK') !== false
// ) {
//     // --- Extract Year & Month dari pesan user ---
//     $monthNames = [
//         'january' => '01','february' => '02','march' => '03','april' => '04',
//         'may' => '05','june' => '06','july' => '07','august' => '08',
//         'september' => '09','october' => '10','november' => '11','december' => '12'
//     ];

//     $month = null;
//     $year = null;

//     // Cek jika ada format bulan dan tahun
//     if (preg_match('/(' . implode('|', array_keys($monthNames)) . ')\s+(\d{4})/i', $userMessage, $matches)) {
//         $month = $monthNames[strtolower($matches[1])];
//         $year = $matches[2];
//     } elseif (preg_match('/\b(20\d{2})\b/', $userMessage, $matches)) {
//         $year = $matches[1];
//     }

//     // Build query
//     $sql = "
//         SELECT created_by, COUNT(*) AS total_request
//         FROM transaksi_h_hrd
//         WHERE category = 'STASIONARY'
//           AND status = 'SUCCESS'
//     ";

//     if ($year && $month) {
//         $sql .= " AND YEAR(do_date) = '$year' AND MONTH(do_date) = '$month' ";
//     } elseif ($year) {
//         $sql .= " AND YEAR(do_date) = '$year' ";
//     }

//     $sql .= "
//         GROUP BY created_by
//         ORDER BY total_request DESC
//     ";

//     $result = $conn->query($sql);

//     if ($result->num_rows > 0) {
//         $response = "📊 Top Requester ATK";
//         if ($year && $month) {
//             $response .= " untuk " . ucfirst($matches[1]) . " $year";
//         } elseif ($year) {
//             $response .= " untuk tahun $year";
//         }
//         $response .= ":\n\n";

//         while ($row = $result->fetch_assoc()) {
//             $user = $row['created_by'] ?: '-';
//             $total = $row['total_request'];
//             $response .= "• $user : $total request\n";
//         }
//     } else {
//         $response = "Tidak ada data request ATK ditemukan.";
//     }

//     echo json_encode(["message" => $response]);
//     exit;
// }

// #Feature 2 : Track which ATK item is most requested
// if (
//     stripos($userMessage, 'atk paling banyak direquest') !== false || 
//     stripos($userMessage, 'atk sering direquest') !== false || 
//     stripos($userMessage, 'atk yang sering direquest') !== false
// ) {
//     $monthNames = [
//         'january' => 1,'february' => 2,'march' => 3,'april' => 4,
//         'may' => 5,'june' => 6,'july' => 7,'august' => 8,
//         'september' => 9,'october' => 10,'november' => 11,'december' => 12
//     ];

//     $year = null;
//     $month = null;

//     if (preg_match('/\b(20\d{2})\b/', $userMessage, $matches)) {
//         $year = $matches[1];
//     }

//     foreach ($monthNames as $name => $num) {
//         if (stripos($userMessage, $name) !== false) {
//             $month = $num;
//             break;
//         }
//     }

//     // Fix query
//     $sql = "
//         SELECT code_item, SUM(CAST(qty AS UNSIGNED)) AS total_qty
//         FROM transaksi_d_hrd
//         WHERE 1=1
//     ";

//     if ($year) {
//         $sql .= " AND YEAR(created_at) = {$year}";
//     }
//     if ($month) {
//         $sql .= " AND MONTH(created_at) = {$month}";
//     }

//     $sql .= " GROUP BY code_item ORDER BY total_qty DESC";

//     $result = $conn->query($sql);

//     if ($result && $result->num_rows > 0) {
//         $response = "📦 ATK yang paling sering direquest:\n\n";
//         while($row = $result->fetch_assoc()) {
//             $response .= "- {$row['code_item']} : {$row['total_qty']} pcs\n";
//         }
//     } else {
//         $response = "Data tidak ditemukan untuk periode tersebut.";
//     }

//     echo json_encode(["message" => $response]);
//     exit;
// }

    
// #Normal Chatbot Flow with OpenAI
// if (!isset($_SESSION['chat_history'])) {
//     $_SESSION['chat_history'] = [];
// }
// $_SESSION['chat_history'][] = ["role" => "user", "content" => $userMessage];

// $messages = [
//     ["role" => "system", "content" => "You are a helpful assistant for Astrindo Digital Approval AI. Answer user queries based on digital approval data."]
// ];
// $messages = array_merge($messages, $_SESSION['chat_history']);

// $payload = [
//     "model" => "gpt-4o",
//     "messages" => $messages,
//     "temperature" => 0.4
// ];

// $ch = curl_init($apiUrl);
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_POST, true);
// curl_setopt($ch, CURLOPT_HTTPHEADER, [
//     "Content-Type: application/json",
//     "Authorization: Bearer $apiKey"
// ]);
// curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
// $response = curl_exec($ch);

// if ($response === false) {
//     echo json_encode(["status" => "error", "message" => curl_error($ch)]);
//     curl_close($ch);
//     exit;
// }

// $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
// curl_close($ch);

// if ($httpCode === 200) {
//     $result = json_decode($response, true);
//     $reply = $result['choices'][0]['message']['content'] ?? "No reply generated.";
//     $_SESSION['chat_history'][] = ["role" => "assistant", "content" => $reply];
//     echo json_encode(["status" => "success", "message" => $reply]);
// } else {
//     echo json_encode(["status" => "error", "message" => "OpenAI API Error", "response" => $response]);
// }
?>
