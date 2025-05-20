<?php
// Database credentials (update before uploading to GoDaddy)
$host = 'localhost';
$db = 'shizload';
$user = 'DZ49';
$pass = 'Tiremaster1992!';

// Connect to MySQL
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch HTML from BitInfoCharts
$url = 'https://bitinfocharts.com/top-100-richest-bitcoin-addresses.html';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$html = curl_exec($ch);
curl_close($ch);
if ($html === false) {
    die("Failed to fetch BitInfoCharts");
}

// Parse HTML with regex (simplified)
preg_match_all('/<td><a href="\/bitcoin\/address\/([13][a-km-zA-HJ-NP-Z1-9]{25,34})">.*?<td.*?>([\d,.]+)\s*BTC/m', $html, $matches, PREG_SET_ORDER);

// Prepare SQL statement
$stmt = $conn->prepare("INSERT INTO wallets (address, balance, last_updated) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE balance = ?, last_updated = NOW()");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

// Insert wallets
foreach ($matches as $match) {
    $address = $match[1];
    $balance = floatval(str_replace(',', '', $match[2]));
    $stmt->bind_param("sdd", $address, $balance, $balance);
    if (!$stmt->execute()) {
        echo "Error inserting $address: " . $stmt->error . "\n";
    }
}

echo "Fetched and stored " . count($matches) . " wallets\n";
$stmt->close();
$conn->close();
?>
