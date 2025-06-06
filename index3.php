<?php
// Lees de JSON-cache
$json = file_get_contents("cache.json");
$data = json_decode($json, true);

echo "<!DOCTYPE html>
<html lang='nl'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Blokkenschema Kaderock</title>
    <style>
        body {
            background-color: black;
            color: white;
            font-family: Arial, sans-serif;
        }
        .grid-container {
            display: grid;
            grid-template-columns: 100px repeat(3, 1fr);
            gap: 2px;
            border: 1px solid white;
            padding: 10px;
            margin-bottom: 40px;
        }
        .grid-item {
            background-color: black;
            color: white;
            text-align: center;
            padding: 10px;
            border: 1px solid white;
        }
        .active-slot {
            background-color: white;
            color: black;
        }
        .time-slot {
            font-weight: bold;
            text-align: center;
        }

        /* Mobiele weergave */
        @media (max-width: 768px) {
            .grid-container {
                display: grid;
                grid-template-columns: 100px repeat(3, minmax(150px, 1fr));
                overflow-x: auto;
                width: 100%;
            }

            .grid-item {
                min-width: 100px;
                word-wrap: break-word;
            }

            h1, h2 {
                text-align: center;
            }
        }
    </style>
</head>
<body>

<h1>Blokkenschema Kaderock</h1>";

$startHour = 10; // Begin van de dag
$endHour = 22; // Eindtijd
$timeInterval = 5; // 5 minuten per blok

foreach ($data as $dag => $podia) {
    echo "<h2>$dag</h2>";
    
    // Haal de 3 podiums op
    $podiums = array_keys($podia);

    // Genereer tabel-header
    echo "<div class='grid-container'>";
    echo "<div class='grid-item time-slot'>Tijd / Podium</div>";

    foreach ($podiums as $podium) {
        echo "<div class='grid-item'><strong>$podium</strong></div>";
    }

    for ($hour = $startHour; $hour <= $endHour; $hour++) {
        for ($minute = 0; $minute < 60; $minute += $timeInterval) {
            $timeLabel = sprintf("%02d:%02d", $hour, $minute);
            echo "<div class='grid-item time-slot'>$timeLabel</div>";

            foreach ($podiums as $podium) {
                $slotContent = "";
                $isActive = false;

                if (isset($podia[$podium])) {
                    foreach ($podia[$podium] as $optreden) {
                        preg_match('/(\d{1,2}:\d{2}).*?-\s*(\d{1,2}:\d{2})/', $optreden, $matches);
                        $startTime = isset($matches[1]) ? strtotime($matches[1]) : null;
                        $endTime = isset($matches[2]) ? strtotime($matches[2]) : null;
                        $currentTime = strtotime($timeLabel);

                        // Controleer of het huidige tijdslot binnen de tijd van een optreden valt
                        if ($startTime && $endTime && $currentTime >= $startTime && $currentTime < $endTime) {
                            $isActive = true;
                            $slotContent = "<div>$optreden</div>";
                        }
                    }
                }

                // Pas achtergrondkleur aan op basis van actieve tijdslots
                $class = $isActive ? "grid-item active-slot" : "grid-item";
                echo "<div class='$class'>$slotContent</div>";
            }
        }
    }

    echo "</div><br>";
}

echo "</body></html>";
?>
