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
            text-align: center;
            padding: 10px;
            border: 1px solid black;
            font-weight: bold;
            grid-row: span var(--span, 1);
        }
        .time-slot {
            font-weight: bold;
            text-align: center;
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
                $activeArtist = "";
                $duration = 1;

                if (isset($podia[$podium])) {
                    foreach ($podia[$podium] as $optreden) {
                        preg_match('/\b(\d{1,2}:\d{2})\b.*?-\s*(\d{1,2}:\d{2})\b/', $optreden, $matches);
                        $startTime = isset($matches[1]) ? strtotime($matches[1]) : null;
                        $endTime = isset($matches[2]) ? strtotime($matches[2]) : null;
                        $currentTime = strtotime($timeLabel);

                        if ($startTime && $endTime && $currentTime == $startTime) {
                            $isActive = true;
                            $activeArtist = $optreden;
                            $duration = ($endTime - $startTime) / (60 * $timeInterval);
                        }
                    }
                }

                if ($isActive && $activeArtist !== "") {
                    echo "<div class='active-slot' style='--span: $duration;'>$activeArtist</div>";
                } else {
                    echo "<div class='grid-item'></div>";
                }
            }
        }
    }

    echo "</div><br>";
}

echo "</body></html>";
?>