<?php

/*
 * Admin panel templates (XAseco panels/Admin*.xml), merged into config('panels.admin') by mergeConfigFrom, a shallow array_merge:
 * a template name the app defines under 'admin' in config/panels.php replaces that whole template (the rest stay).
 * Keys are the template names without the "Admin" prefix. [x, y, width, height] positions in ManiaLink units.
 */

// [x, y, width, height] for Rewind, Pause, Play, Refresh, Green, Red, Buddy
$small  = [[0.8, -0.8, 2, 2.1], [2.9, -0.8, 2, 2.1], [5.0, -0.8, 2, 2.1], [7.1, -0.8, 2, 2.1], [9.0, -0.2, 2.4, 3.3], [10.8, -0.2, 2.4, 3.3], [12.9, -0.3, 2.4, 3]];
$wide   = [[1, -0.6, 3, 3], [5, -0.6, 3, 3], [9, -0.6, 3, 3], [13, -0.6, 3, 3.1], [17, -0.2, 3.5, 4], [21, -0.2, 3.5, 4], [25.2, -0.2, 3.5, 4]];
$narrow = [[1, -0.6, 3, 3], [4, -0.6, 3, 3], [7, -0.6, 3, 3], [10, -0.6, 3, 3.1], [13, -0.1, 3.5, 4], [16, -0.1, 3.5, 4], [19.2, -0.2, 3.2, 3.5]];
$with   = fn (array $set, int $i, array $button) => array_replace($set, [$i => $button]);

// frame 'x y [z]', background size, buttons, background substyle
$admin = fn (string $frame, array $bg, array $buttons, string $style = 'Bgs1InRace') => ['frame' => $frame, 'bg' => $bg, 'buttons' => $buttons, 'bg_style' => $style];

return [
    'BelowChat'        => $admin('-33.5 -45.6 0', [21.8, 2.5], [[1, -0.4, 1.7, 1.7], [4, -0.4, 1.7, 1.7], [7, -0.4, 1.7, 1.7], [10, -0.4, 1.7, 1.8], [13, 0.1, 2.1, 2.8], [16, 0.1, 2.1, 2.8], [19.1, -0.2, 1.8, 2.1]]),
    'AboveChat'        => $admin('-56 -29.2 0', [16, 3.5], $small),
    'AboveCPList'      => $admin('48.1 -28.5 0', [16, 3.5], $small),
    'BottomCenter'     => $admin('-7.9 -34.9 0', [16, 3.5], $small),
    'AboveSpeed2'      => $admin('48.6 -35 5', [16, 3.5], $with($small, 6, [12.8, -0.3, 2.4, 3])),
    'TopCenter'        => $admin('-12.1 44.2 0', [16, 3.5], array_replace($small, [0 => [0.8, -0.8, 2, 2], 1 => [2.9, -0.8, 2, 2], 2 => [5.0, -0.8, 2, 2], 6 => [13.0, -0.3, 2.4, 3]])),
    'AboveChatWide'    => $admin('-56 -28.5 0', [30, 4.2], $wide),
    'AboveCPListWide'  => $admin('34.1 -27.8 0', [30, 4.2], $wide),
    'BottomCenterWide' => $admin('-11.4 -34.2 0', [23, 4.2], $narrow),
    'TopCenterWide'    => $admin('-12.1 44.2 0', [23, 4.2], $with($narrow, 6, [19.2, -0.2, 3.2, 3.5])),
    'AboveSpeed'       => $admin('54.4 -33.5 0', [10.4, 6.5], [[0.8, -0.8, 2, 2.1], [2.9, -0.8, 2, 2.1], [5.0, -0.8, 2, 2.1], [7.1, -0.8, 2, 2.1], [1.0, -2.9, 2.5, 3.4], [3.5, -2.9, 2.5, 3.4], [6.6, -3.0, 2.3, 3]]),
    'CallVote'         => $admin('-64.8 37.1 0', [14.1, 3.5], [[1.1, -0.8, 1.7, 1.8], [3.0, -0.8, 1.7, 1.8], [4.9, -0.8, 1.7, 1.8], [6.7, -0.8, 1.7, 1.8], [8.5, -0.4, 1.9, 2.6], [10.1, -0.4, 1.9, 2.6], [11.9, -0.4, 1.8, 2.4]]),
    'CallVoteBlur'     => $admin('-64.8 37.1 0', [14.1, 3.5], [[1.1, -0.8, 1.7, 1.8], [3.0, -0.8, 1.7, 1.8], [4.9, -0.8, 1.7, 1.8], [6.7, -0.8, 1.7, 1.8], [8.5, -0.4, 1.9, 2.6], [10.1, -0.4, 1.9, 2.6], [11.9, -0.4, 1.8, 2.4]], 'Bgs1'),
    'LeftEdge'         => $admin('-64.8 5.5 0', [4.6, 29.6], [[0.9, -1, 3, 3], [1.0, -5, 3, 3], [1.0, -9, 3, 3], [1.0, -13, 3, 3.1], [0.8, -16, 3.4, 5], [0.8, -20, 3.4, 5], [1.0, -25, 3.2, 4]]),
    'RightEdge'        => $admin('60.1 5.5 0', [4.6, 29.6], [[0.6, -1, 3, 3], [0.7, -5, 3, 3], [0.7, -9, 3, 3], [0.6, -13, 3, 3.1], [0.5, -16, 3.4, 5], [0.5, -20, 3.4, 5], [0.6, -25, 3.2, 4]]),
];
