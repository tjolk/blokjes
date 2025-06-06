<?php
// Lees de JSON-cache
$json = file_get_contents("cache.json");
$data = json_decode($json, true);

// Controleer of de JSON correct is geladen
if (!$data) {
    die("Fout bij het laden van cache.json");
}

// Print JSON als een leesbare tabel
echo "<pre>";
print_r($data);
echo "</pre>";
?>