<?php
// Lees de JSON-cache
$json = file_get_contents("cache.json");
$data = json_decode($json, true);

function generateBlokjesContent($data) {
    $startHour = 10; // Begin van de dag
    $endHour = 22; // Eindtijd
    $timeInterval = 5; // 5 minuten per blok
    $output = "";
    foreach ($data as $dag => $podia) {
        $output .= "<h2>$dag</h2>";
        $podiums = array_keys($podia);
        $output .= "<div class='grid-container'>";
        $output .= "<div class='grid-item time-slot'>Tijd / Podium</div>";
        foreach ($podiums as $podium) {
            $output .= "<div class='grid-item'><strong>$podium</strong></div>";
        }
        for ($hour = $startHour; $hour <= $endHour; $hour++) {
            for ($minute = 0; $minute < 60; $minute += $timeInterval) {
                $timeLabel = sprintf("%02d:%02d", $hour, $minute);
                $output .= "<div class='grid-item time-slot'>$timeLabel</div>";
                foreach ($podiums as $podium) {
                    $slotContent = "";
                    $isActive = false;
                    if (isset($podia[$podium])) {
                        foreach ($podia[$podium] as $optreden) {
                            preg_match('/\b(\d{1,2}:\d{2})\b.*?-\s*(\d{1,2}:\d{2})\b/', $optreden, $matches);
                            $startTime = isset($matches[1]) ? strtotime($matches[1]) : null;
                            $endTime = isset($matches[2]) ? strtotime($matches[2]) : null;
                            $currentTime = strtotime($timeLabel);
                            if ($startTime && $endTime && $currentTime >= $startTime && $currentTime < $endTime) {
                                $isActive = true;
                                $slotContent = "<div>$optreden</div>";
                            }
                        }
                    }
                    $class = $isActive ? "grid-item active-slot" : "grid-item";
                    $output .= "<div class='$class'>$slotContent</div>";
                }
            }
        }
        $output .= "</div><br>";
    }
    return $output;
}

?><!DOCTYPE html>
<html lang='nl'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Blokkenschema Kaderock</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<h1>Blokkenschema Kaderock</h1>

<?php
echo generateBlokjesContent($data);
?>

</body>
</html>
