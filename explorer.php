<?php
require_once 'config.php';

$dir = isset($_GET['dir']) ? $_GET['dir'] : '';
// Secure the directory path to prevent directory traversal
$dir = trim($dir, '/\\');
$dir = str_replace(['../', '..\\'], '', $dir);

$currentPath = $vaultDir . ($dir !== '' ? '/' . $dir : '');
$realBase = realpath($vaultDir);
$realCurrent = realpath($currentPath);

// Verify that the requested path is within the vault directory
if ($realCurrent === false || strpos($realCurrent, $realBase) !== 0 || !is_dir($realCurrent)) {
    $currentPath = $vaultDir;
    $dir = '';
}

$files = scandir($currentPath);
$foldersList = [];
$filesList = [];

foreach ($files as $file) {
    if ($file === '.') continue;
    if ($file === '..' && $dir === '') continue; // Don't show '..' at vault root

    $fullPath = $currentPath . '/' . $file;
    $relPath = $dir !== '' ? $dir . '/' . $file : $file;

    if (is_dir($fullPath)) {
        if ($file === '..') {
            // Parent directory link
            $parentDir = dirname($dir);
            if ($parentDir === '.') $parentDir = '';
            $foldersList[] = [
                'name' => '..',
                'link' => '?dir=' . urlencode($parentDir),
                'icon' => '📁'
            ];
        } else {
            $foldersList[] = [
                'name' => $file,
                'link' => '?dir=' . urlencode($relPath),
                'icon' => '📁'
            ];
        }
    } else {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if (strtolower($ext) === 'md') {
            $pageParam = substr($relPath, 0, -3); // remove .md
            $filesList[] = [
                'name' => $file,
                'link' => 'index.php?page=' . urlencode($pageParam),
                'icon' => '📝'
            ];
        } else {
            $filesList[] = [
                'name' => $file,
                'link' => $fullPath, // Raw link to vault
                'icon' => '📄'
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vault Explorer</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        a { color: #569cd6; text-decoration: none; }
        a:hover { text-decoration: underline; }
        ul { list-style: none; padding: 0; }
        li { padding: 8px 0; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #333; }
        li:last-child { border-bottom: none; }
        .container { max-width: 800px; margin: 0 auto; background: #252526; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
        h1 { font-size: 24px; margin-top: 0; border-bottom: 1px solid #3c3c3c; padding-bottom: 10px; }
        .path { color: #9cdcfe; font-weight: normal; font-size: 18px;}
    </style>
</head>
<body>
    <div class="container">
        <h1>Vault Explorer <span class="path">/<?= htmlspecialchars($dir) ?></span></h1>
        <ul>
            <?php foreach ($foldersList as $item): ?>
                <li><span><?= $item['icon'] ?></span> <a href="<?= htmlspecialchars($item['link']) ?>"><?= htmlspecialchars($item['name']) ?></a></li>
            <?php endforeach; ?>
            <?php foreach ($filesList as $item): ?>
                <li><span><?= $item['icon'] ?></span> <a href="<?= htmlspecialchars($item['link']) ?>"><?= htmlspecialchars($item['name']) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
</body>
</html>
