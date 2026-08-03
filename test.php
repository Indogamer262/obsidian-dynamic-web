<?php
$vaultDir = 'vault';
function search_file_recursive($target, $dir, $skipDirs, $isMd) {
    if (!is_dir($dir)) return null;
    $files = scandir($dir);
    $subDirs = [];
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $fullPath = $dir . '/' . $file;
        if (is_dir($fullPath)) {
            if (!in_array($fullPath, $skipDirs)) {
                $subDirs[] = $fullPath;
            }
        } else {
            if ($isMd) {
                if ($file === $target . '.md' || $file === $target) return $fullPath;
            } else {
                if ($file === $target) return $fullPath;
            }
        }
    }
    foreach ($subDirs as $subDir) {
        $found = search_file_recursive($target, $subDir, [], $isMd);
        if ($found) return $found;
    }
    return null;
}
echo search_file_recursive('Buku Panduan S1 Teknik Informatika v2024', 'vault', [], true);
