<?php
$url = "https://www.kaderock.com"; // Zorg ervoor dat de URL correct is
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$html = curl_exec($ch);
curl_close($ch);

$dom = new DOMDocument();
@$dom->loadHTML($html);
$xpath = new DOMXPath($dom);

$nodes = $xpath->query("//*[contains(@class, 'dagHeader') or contains(@class, 'podiumHeader text-white') or contains(@class, 'd-table-cell text-white bg-main pt-2 pe-4 pb-2 ps-4')]");

$data = [];
$activeDag = null;
$activePodium = null;

foreach ($nodes as $node) {
    $class = $node->getAttribute("class");
    $text = trim($node->nodeValue);

    if (strpos($class, "dagHeader") !== false) {
        // Nieuwe dag -> Reset actieve podium
        $activeDag = $text;
        $data[$activeDag] = [];
        $activePodium = null;

    } elseif (strpos($class, "podiumHeader text-white") !== false) {
        // Normaliseer de podiumnaam naar kleine letters
        $activePodium = strtolower($text);
        $data[$activeDag][$activePodium] = [];

    } elseif (strpos($class, "d-table-cell text-white bg-main pt-2 pe-4 pb-2 ps-4") !== false && $activeDag && $activePodium) {
        // Artiest koppelen aan actieve dag en podium
        $data[$activeDag][$activePodium][] = $text;
    }
}

// Opslaan in JSON-cache
file_put_contents("cache.json", json_encode($data, JSON_PRETTY_PRINT));

echo "Gegevens opgeslagen in cache.json";
?>