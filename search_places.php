<?php
$mysqli = new mysqli("localhost", "root", "", "health_checkin_db");
$term = $_GET['term'] ?? '';
$suggestions = [];

if ($term) {
    $stmt = $mysqli->prepare("SELECT name FROM places WHERE name LIKE CONCAT('%', ?, '%') LIMIT 10");
    $stmt->bind_param("s", $term);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $suggestions[] = $row['name'];
    }
}
header('Content-Type: application/json');
echo json_encode($suggestions);
?>
