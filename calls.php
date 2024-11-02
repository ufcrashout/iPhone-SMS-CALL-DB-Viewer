<?php
// calls.php
session_start();
require 'db.php';

if (!isset($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit;
}

$db = getCallDbConnection();

// Cache contact names for optimized lookup
$contactCache = [];
function getCachedContactName($phone_number) {
    global $contactCache;
    if (isset($contactCache[$phone_number])) {
        return $contactCache[$phone_number];
    }
    $name = getContactName($phone_number);
    $contactCache[$phone_number] = $name ? "$name ($phone_number)" : $phone_number;
    return $contactCache[$phone_number];
}

// Pagination settings
$records_per_page = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Filter parameters
$phone_number = $_GET['phone_number'] ?? '';
$call_type = $_GET['call_type'] ?? '';
$date = $_GET['date'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'ZDATE';
$order = $_GET['order'] ?? 'DESC';

// Query for statistics if a phone number is selected
$stats = [];
if ($phone_number) {
    $stats_query = "
        SELECT 
            COUNT(*) AS total_calls,
            SUM(CASE WHEN ZCALLTYPE = 1 THEN ZDURATION ELSE 0 END) AS total_outgoing_duration,
            SUM(CASE WHEN ZCALLTYPE = 2 THEN ZDURATION ELSE 0 END) AS total_incoming_duration,
            COUNT(CASE WHEN ZCALLTYPE = 1 THEN 1 END) AS outgoing_calls,
            COUNT(CASE WHEN ZCALLTYPE = 2 THEN 1 END) AS incoming_calls
        FROM ZCALLRECORD
        WHERE ZADDRESS = :phone_number
    ";
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->execute([':phone_number' => $phone_number]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
}

// Main query to retrieve call records with filters and pagination
$query = "
    SELECT ZADDRESS AS phone_number, ZCALLTYPE AS call_type,
           (ZDATE + strftime('%s', '2001-01-01 00:00:00')) AS call_date,
           ZDURATION AS duration, ZNAME AS name, ZSERVICE_PROVIDER AS service_provider,
           ZISO_COUNTRY_CODE AS country_code
    FROM ZCALLRECORD
    WHERE 1=1
";

$params = [];
if ($phone_number) {
    $query .= " AND ZADDRESS = :phone_number";
    $params[':phone_number'] = $phone_number;
}
if ($call_type) {
    $query .= " AND ZCALLTYPE = :call_type";
    $params[':call_type'] = $call_type;
}
if ($date) {
    $query .= " AND DATE(ZDATE + strftime('%s', '2001-01-01 00:00:00')) = :date";
    $params[':date'] = $date;
}

// Count total records for pagination
$count_query = "SELECT COUNT(*) FROM ($query)";
$count_stmt = $db->prepare($count_query);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_records = $count_stmt->fetchColumn();

// Add sorting and pagination to the main query
$query .= " ORDER BY $sort_by $order LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

// Bind parameters for filters
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$calls = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to format call types
function formatCallType($call_type) {
    $types = [
        1 => 'Phone Call',
        2 => 'FaceTime',
        3 => 'Facebook Audio',
        4 => 'Facebook Video',
    ];
    return $types[$call_type] ?? 'Unknown';
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Call History</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; cursor: pointer; }
        .filter { margin-top: 20px; }
        nav { margin-bottom: 20px; }
        .pagination { margin-top: 20px; }
        .stats { margin-top: 20px; font-weight: bold; }
    </style>
    <script>
        function sortTable(column) {
            const url = new URL(window.location.href);
            const currentOrder = url.searchParams.get("order") || "ASC";
            url.searchParams.set("sort_by", column);
            url.searchParams.set("order", currentOrder === "ASC" ? "DESC" : "ASC");
            window.location.href = url.toString();
        }
    </script>
</head>
<body>

<nav>
    <a href="index.php">Messages</a> | <a href="calls.php">Call History</a>
</nav>

<h1>Call History</h1>

<!-- Display stats if a specific number is selected -->
<?php if ($phone_number && $stats): ?>
    <div class="stats">
        <h3>Statistics for <?= htmlspecialchars(getCachedContactName($phone_number)) ?></h3>
        <p>Total Calls: <?= $stats['total_calls'] ?></p>
        <p>Outgoing Calls: <?= $stats['outgoing_calls'] ?>, Duration: <?= $stats['total_outgoing_duration'] ?> seconds</p>
        <p>Incoming Calls: <?= $stats['incoming_calls'] ?>, Duration: <?= $stats['total_incoming_duration'] ?> seconds</p>
    </div>
<?php endif; ?>

<div class="filter">
    <h3>Filter Calls</h3>
    <form method="GET" action="calls.php">
        <label for="phone_number">Phone Number:</label>
        <input type="text" name="phone_number" id="phone_number" value="<?= htmlspecialchars($phone_number) ?>">
        
        <label for="call_type">Call Type:</label>
        <select name="call_type" id="call_type">
            <option value="">All</option>
            <option value="1" <?= $call_type == '1' ? 'selected' : '' ?>>Phone Call</option>
            <option value="2" <?= $call_type == '2' ? 'selected' : '' ?>>FaceTime</option>
            <option value="3" <?= $call_type == '3' ? 'selected' : '' ?>>Facebook Audio</option>
            <option value="4" <?= $call_type == '4' ? 'selected' : '' ?>>Facebook Video</option>
        </select>
        
        <label for="date">Date:</label>
        <input type="date" name="date" id="date" value="<?= htmlspecialchars($date) ?>">
        
        <button type="submit">Filter</button>
    </form>
</div>

<h2>Recent Calls</h2>
<table>
    <thead>
        <tr>
            <th onclick="sortTable('ZADDRESS')">Contact</th>
            <th onclick="sortTable('ZCALLTYPE')">Type</th>
            <th onclick="sortTable('ZDATE')">Date</th>
            <th>Time</th>
            <th onclick="sortTable('ZDURATION')">Duration (seconds)</th>
            <th>Name</th>
            <th>Service Provider</th>
            <th>Country Code</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($calls as $call): ?>
            <?php
                $displayName = getCachedContactName($call['phone_number']);
            ?>
            <tr>
                <td>
                    <a href="calls.php?phone_number=<?= urlencode($call['phone_number']) ?>">
                        <?= htmlspecialchars($displayName) ?>
                    </a>
                </td>
                <td><?= formatCallType($call['call_type']) ?></td>
                <td><?= date('Y-m-d', $call['call_date']) ?></td>
                <td><?= date('H:i:s', $call['call_date']) ?></td>
                <td><?= htmlspecialchars($call['duration']) ?></td>
                <td><?= htmlspecialchars($call['name']) ?></td>
                <td><?= htmlspecialchars($call['service_provider']) ?></td>
                <td><?= htmlspecialchars($call['country_code']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Pagination Controls -->
<div class="pagination">
    <?php 
    // Build query string without 'page' parameter
    $query_string = http_build_query(array_diff_key($_GET, ['page' => ''])); 
    ?>
    
    <?php if ($page > 1): ?>
        <a href="?<?= $query_string ?>&page=<?= $page - 1 ?>">Previous</a>
    <?php endif; ?>
    
    <?php if ($page * $records_per_page < $total_records): ?>
        <a href="?<?= $query_string ?>&page=<?= $page + 1 ?>">Next</a>
    <?php endif; ?>
</div>
</body>
</html>