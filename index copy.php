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
        // Determine if this is the current day
        $isToday = false;
        if (preg_match('/(\d{1,2})\s*(juni|juli|augustus|september|oktober|november|december)/iu', $dag, $matches)) {
            $dayNum = $matches[1];
            $monthStr = strtolower($matches[2]);
            $monthMap = [
                'januari' => 1, 'februari' => 2, 'maart' => 3, 'april' => 4, 'mei' => 5, 'juni' => 6, 'juli' => 7,
                'augustus' => 8, 'september' => 9, 'oktober' => 10, 'november' => 11, 'december' => 12
            ];
            $month = $monthMap[$monthStr] ?? null;
            $year = (int)date('Y');
            if ($month && checkdate($month, $dayNum, $year)) {
                $isToday = (date('n') == $month && date('j') == $dayNum);
            }
        }
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
            foreach ($acts as &$act) {
                for ($col = 0;; $col++) {
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
        $gridCols = ['50px'];
        $totalCols = 1;
        foreach ($podiums as $podium) {
            $colCount = $maxSubcolumns[$podium] ?: 1;
            $isBroeikast = (mb_strtolower(trim($podium)) === 'de broeikast');
            for ($i = 0; $i < $colCount; $i++) {
                $gridCols[] = $isBroeikast ? '0.7fr' : '1fr';
            }
            $totalCols += $colCount;
        }
        $output .= "<div class='grid-container cols-$totalCols' style='grid-template-columns:" . implode(' ', $gridCols) . ";'>";
        // Header row
        $output .= "<div class='grid-item time-slot tijd-header sticky-tijd' style='grid-column: 1 / 2;'>Tijd</div>";
        $colStart = 2;
        foreach ($podiums as $podium) {
            $colspan = $maxSubcolumns[$podium] ?: 1;
            $output .= "<div class='grid-item sticky-header' style='grid-column: $colStart / " . ($colStart + $colspan) . "; text-align:center;'><strong>$podium</strong></div>";
            $colStart += $colspan;
        }
        // Time slots
        for ($hour = $startHour; $hour <= $endHour; $hour++) {
            for ($minute = 0; $minute < 60; $minute += $timeInterval) {
                $timeLabel = sprintf("%02d:%02d", $hour, $minute);
                $currentTime = strtotime($timeLabel);
                $now = time();
                $isCurrentSlot = $isToday && $currentTime <= $now && $now < $currentTime + $timeInterval * 60 && date('Y-m-d', $currentTime) === date('Y-m-d');
                $timeSlotClass = 'grid-item time-slot' . ($isCurrentSlot ? ' current-time-slot' : '');
                $output .= "<div class='$timeSlotClass' style='grid-column: 1 / 2;'>$timeLabel</div>";
                foreach ($podiums as $podium) {
                    $subcols = $maxSubcolumns[$podium] ?: 1;
                    for ($subcol = 0; $subcol < $subcols; $subcol++) {
                        $found = false;
                        foreach ($podiumActs[$podium] as $actIdx => $act) {
                            if ($act['subcol'] === $subcol && $act['start'] === $currentTime && !$act['rendered']) {
                                // Remove time and trailing spaces/tabs from act title for display
                                $actName = preg_replace('/\s*\d{1,2}:\d{2}\s*-\s*\d{1,2}:\d{2}.*/', '', $act['title']);
                                $actName = rtrim($actName);
                                $rowspan = ($act['end'] - $act['start']) / ($timeInterval * 60);
                                if ($rowspan < 1) $rowspan = 1;
                                $output .= "<div class='grid-item active-slot' style='grid-row: span $rowspan;'><span>" . htmlspecialchars($actName) . "</span></div>";
                                $podiumActs[$podium][$actIdx]['rendered'] = true;
                                $found = true;
                                break;
                            }
                        }
                        if (!$found) {
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
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta http-equiv="refresh" content="300">
</head>
<body>

<img src="header_2025-01.svg" alt="Kaderock Header" style="width:100%;max-width:900px;display:block;margin:32px auto 16px auto;">

<?php
echo generateBlokjesContent($data);
?>

</body>
<script>
// Auto-refresh every 5 minutes for PWA (if not already handled by meta)
setTimeout(function() {
    window.location.reload();
}, 300000); // 300,000 ms = 5 minutes

// Scroll to the current time slot for the current day only
window.addEventListener('DOMContentLoaded', function() {
    function pad(n) { return n < 10 ? '0' + n : n; }
    // Build Dutch month names for matching
    const months = ['januari','februari','maart','april','mei','juni','juli','augustus','september','oktober','november','december'];
    const now = new Date();
    const day = now.getDate();
    const month = months[now.getMonth()];
    // Find the <h2> for today (e.g., 'Zaterdag 7 juni ...')
    const h2s = document.querySelectorAll('h2');
    let todayHeader = null;
    for (let h2 of h2s) {
        if (h2.textContent.match(new RegExp('\\b' + day + '\\b.*' + month, 'i'))) {
            todayHeader = h2;
            break;
        }
    }
    if (!todayHeader) return; // No schedule for today
    // Find the next .grid-container after the header
    let grid = todayHeader.nextElementSibling;
    while (grid && !grid.classList.contains('grid-container')) {
        grid = grid.nextElementSibling;
    }
    if (!grid) return;
    // Now scroll to the current time slot in this grid only
    const hour = pad(now.getHours());
    const minute = pad(Math.floor(now.getMinutes() / 5) * 5);
    const slots = grid.querySelectorAll('.grid-item.time-slot');
    let found = false;
    for (let slot of slots) {
        if (slot.textContent.trim() === `${hour}:${minute}`) {
            slot.scrollIntoView({behavior: 'smooth', block: 'center'});
            found = true;
            break;
        }
    }
    if (!found && slots.length > 0) {
        slots[0].scrollIntoView({behavior: 'smooth', block: 'center'});
    }
});
</script>
</html>
