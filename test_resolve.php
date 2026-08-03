<?php
$vaultDir = 'vault';
$page = 'index';
$pagePath = $vaultDir . '/' . $page;
$currentDir = dirname($pagePath);
$targets = ['Semester 3/Semester 3', 'Semester 4/Semester 4', 'Semester 5/Semester 5'];

foreach ($targets as $target) {
    $directPath = $currentDir . '/' . $target;
    echo "Target: $target -> exists? " . (file_exists($directPath . '.md') ? 'yes' : 'no') . "\n";
}
