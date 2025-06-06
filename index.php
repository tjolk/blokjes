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
                if (preg_match('/(\d{1,2}:\d{2})\s*-\s*(\d{1,2}:\d{2})/', $optreden, $matches)) {
                    $start = strtotime($matches[1]);
                    $end = strtotime($matches[2]);
                    $acts[] = [
                        'title' => $optreden,
                        'start' => $start,
                        'end' => $end,
                        'subcol' => null,
                        'rendered' => false
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
        // Build grid-template-columns
        $gridCols = ['100px'];
        $totalCols = 1;
        foreach ($podiums as $podium) {
            $colCount = $maxSubcolumns[$podium] ?: 1;
            $totalCols += $colCount;
            for ($i = 0; $i < $colCount; $i++) {
                $gridCols[] = '1fr';
            }
        }
        $output .= "<div class='grid-container cols-$totalCols' style='display:grid;grid-template-columns:" . implode(' ', $gridCols) . ";'>";
        // Header row
        $output .= "<div class='grid-item time-slot tijd-header sticky-tijd' style='grid-column: 1 / 2;'>Tijd</div>";
        $colStart = 2;
        foreach ($podiums as $podium) {
            $colspan = $maxSubcolumns[$podium] ?: 1;
            $output .= "<div class='grid-item sticky-header' style='grid-column: $colStart / " . ($colStart + $colspan) . "; text-align:center;'><strong>$podium</strong></div>";
            $colStart += $colspan;
        }
        // Time slots
        $rowCount = (($endHour - $startHour + 1) * (60 / $timeInterval));
        for ($hour = $startHour; $hour <= $endHour; $hour++) {
            for ($minute = 0; $minute < 60; $minute += $timeInterval) {
                $timeLabel = sprintf("%02d:%02d", $hour, $minute);
                $currentTime = strtotime($timeLabel);
                $output .= "<div class='grid-item time-slot' style='grid-column: 1 / 2;'>$timeLabel</div>";
                // For each podium
                foreach ($podiums as $podium) {
                    $subcols = $maxSubcolumns[$podium] ?: 1;
                    for ($subcol = 0; $subcol < $subcols; $subcol++) {
                        // Only render act if it starts at this time in this subcol
                        $found = false;
                        foreach ($podiumActs[$podium] as $actIdx => $act) {
                            if ($act['subcol'] === $subcol && $act['start'] === $currentTime && !$act['rendered']) {
                                $rowspan = ($act['end'] - $act['start']) / ($timeInterval * 60);
                                $output .= "<div class='grid-item active-slot' style='grid-row: span $rowspan;'>" . htmlspecialchars($act['title']) . "</div>";
                                $podiumActs[$podium][$actIdx]['rendered'] = true;
                                $found = true;
                                break;
                            }
                        }
                        // Fill empty cell if no act starts here and not covered by a rowspan
                        if (!$found) {
                            // Check if a previous act is spanning this cell
                            $spanned = false;
                            foreach ($podiumActs[$podium] as $act) {
                                if ($act['subcol'] === $subcol && $act['start'] < $currentTime && $act['end'] > $currentTime) {
                                    $spanned = true;
                                    break;
                                }
                            }
                            if (!$spanned) {
                                $output .= "<div class='grid-item'></div>";
                            }
                        }
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
<script>
// Scroll to the current time slot on page load
window.addEventListener('DOMContentLoaded', function() {
    function pad(n) { return n < 10 ? '0' + n : n; }
    const now = new Date();
    const hour = pad(now.getHours());
    const minute = pad(Math.floor(now.getMinutes() / 5) * 5); // round down to nearest 5
    const selector = `.grid-item.time-slot`;
    const slots = document.querySelectorAll(selector);
    let found = false;
    for (let slot of slots) {
        if (slot.textContent.trim() === `${hour}:${minute}`) {
            slot.scrollIntoView({behavior: 'smooth', block: 'center'});
            found = true;
            break;
        }
    }
    // If not found, scroll to first slot
    if (!found && slots.length > 0) {
        slots[0].scrollIntoView({behavior: 'smooth', block: 'center'});
    }
});
</script>
</html>
