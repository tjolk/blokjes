<?php
// Lees de JSON-cache
$json = file_get_contents("cache.json");
$data = json_decode($json, true);

function generateBlokjesContent($data) {
    $startHour = 10;
    $endHour = 22;
    $timeInterval = 5;
    $output = "";
    foreach ($data as $dag => $podia) {
        $output .= "<h2>$dag</h2>";
        $podiums = array_keys($podia);
        // Preprocess: for each podium, build a list of acts with start/end and assign subcolumns
        $podiumActs = [];
        $maxSubcolumns = [];
        foreach ($podiums as $podium) {
            $acts = [];
            foreach ($podia[$podium] as $optreden) {
                // Parse time (do not skip Riccardo Marogna)
                if (preg_match('/(\d{1,2}:\d{2})\s*-\s*(\d{1,2}:\d{2})/', $optreden, $matches)) {
                    $start = strtotime($matches[1]);
                    $end = strtotime($matches[2]);
                    $acts[] = [
                        'title' => $optreden,
                        'start' => $start,
                        'end' => $end,
                        'assigned' => false,
                        'subcol' => null
                    ];
                }
            }
            // Assign subcolumns for overlaps
            $columns = [];
            foreach ($acts as $i => &$act) {
                for ($col = 0; ; $col++) {
                    $overlap = false;
                    foreach ($columns[$col] ?? [] as $other) {
                        if (!($act['end'] <= $other['start'] || $act['start'] >= $other['end'])) {
                            $overlap = true;
                            break;
                        }
                    }
                    if (!$overlap) {
                        $act['subcol'] = $col;
                        $columns[$col][] = $act;
                        break;
                    }
                }
            }
            unset($act);
            $podiumActs[$podium] = $acts;
            $maxSubcolumns[$podium] = count($columns);
        }
        // Calculate total grid columns
        $totalCols = 1; // time
        foreach ($podiums as $podium) {
            $totalCols += $maxSubcolumns[$podium] ?: 1;
        }
        // Build grid-template-columns style
        $gridCols = ['100px'];
        foreach ($podiums as $podium) {
            for ($i = 0; $i < ($maxSubcolumns[$podium] ?: 1); $i++) {
                $gridCols[] = '1fr';
            }
        }
        $output .= "<div class='grid-container' style='display:grid;grid-template-columns:" . implode(' ', $gridCols) . ";'>";
        // Header row
        $output .= "<div class='grid-item time-slot' style='grid-column: 1 / 2;'>Tijd / Podium</div>";
        $colStart = 2;
        foreach ($podiums as $podium) {
            $colspan = $maxSubcolumns[$podium] ?: 1;
            $output .= "<div class='grid-item' style='grid-column: $colStart / " . ($colStart + $colspan) . "; text-align:center;'><strong>$podium</strong></div>";
            $colStart += $colspan;
        }
        // Time slots
        for ($hour = $startHour; $hour <= $endHour; $hour++) {
            for ($minute = 0; $minute < 60; $minute += $timeInterval) {
                $timeLabel = sprintf("%02d:%02d", $hour, $minute);
                $currentTime = strtotime($timeLabel);
                $output .= "<div class='grid-item time-slot' style='grid-column: 1 / 2;'>$timeLabel</div>";
                foreach ($podiums as $podium) {
                    $subcols = $maxSubcolumns[$podium] ?: 1;
                    $found = false;
                    foreach ($podiumActs[$podium] as $actIdx => $act) {
                        if ($act['subcol'] !== null && $act['start'] === $currentTime && empty($act['rendered'])) {
                            $rowspan = ($act['end'] - $act['start']) / ($timeInterval * 60);
                            $output .= "<div class='grid-item active-slot' style='grid-row: span $rowspan;'>" . htmlspecialchars($act['title']) . "</div>";
                            $podiumActs[$podium][$actIdx]['rendered'] = true;
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $output .= "<div class='grid-item'></div>";
                    }
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
