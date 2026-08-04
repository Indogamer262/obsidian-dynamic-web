<?php
require_once 'config.php';

$page = isset($_GET['page']) ? $_GET['page'] : '';

if (empty($page)) {
    if (!empty($defaultPage)) {
        header("Location: ?page=" . urlencode($defaultPage));
        exit;
    } else {
        header("Location: explorer.php");
        exit;
    }
}

// Ensure safe path handling
$page = str_replace(['../', '..\\'], '', $page);
$targetFile = $vaultDir . '/' . $page . '.md';
$currentDir = dirname($targetFile);
if ($currentDir === '.' || $currentDir === $vaultDir) {
    $currentDir = $vaultDir;
}

if (!file_exists($targetFile)) {
    $markdownContent = "# File Not Found\n\nThe requested file `{$page}` could not be found.";
} else {
    $markdownContent = file_get_contents($targetFile);
}

// Function to resolve Obsidian Link (Shortest Path Algorithm)
function resolve_obsidian_link($target, $currentDir, $vaultDir) {
    // 1. Try exact match from current dir
    $directPath = $currentDir . '/' . ltrim($target, '/');
    if (is_file($directPath)) return $directPath;
    if (is_file($directPath . '.md')) return $directPath . '.md';

    // 2. Try exact match from vault dir
    $vaultDirect = $vaultDir . '/' . ltrim($target, '/');
    if (is_file($vaultDirect)) return $vaultDirect;
    if (is_file($vaultDirect . '.md')) return $vaultDirect . '.md';

    // 3. Use shortest-path recursive search as fallback
    $basename = basename($target);
    $ext = pathinfo($basename, PATHINFO_EXTENSION);
    $isMd = ($ext === '' || strtolower($ext) === 'md');

    // Convert current dir to array of paths to check, starting from deepest
    $pathsToCheck = [];
    $curr = trim($currentDir, '/\\');
    while ($curr !== '') {
        $pathsToCheck[] = $curr;
        if ($curr === $vaultDir) break;
        $curr = dirname($curr);
        if ($curr === '.') break;
    }
    if (!in_array($vaultDir, $pathsToCheck)) {
        $pathsToCheck[] = $vaultDir;
    }
    
    $checkedDirs = [];
    
    foreach ($pathsToCheck as $dir) {
        $found = search_file_recursive($basename, $dir, $checkedDirs, $isMd);
        if ($found) return $found;
        $checkedDirs[] = $dir;
    }
    
    return null; // Not found
}

function search_file_recursive($target, $dir, $skipDirs, $isMd) {
    if (!is_dir($dir)) return null;
    
    foreach ($skipDirs as $skip) {
        if ($skip === $dir) return null;
    }
    
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

// Process Markdown

// 1. Embeds: ![[file]]
$markdownContent = preg_replace_callback('/!\[\[([^\]]+)\]\]/', function($matches) use ($currentDir, $vaultDir) {
    $content = $matches[1];
    $content = str_replace('\|', '|', $content); // Unescape \|
    $parts = explode('|', $content, 2);
    $target = trim($parts[0]);
    $alias = isset($parts[1]) ? trim($parts[1]) : '';
    
    $resolved = resolve_obsidian_link($target, $currentDir, $vaultDir);
    if ($resolved) {
        $ext = strtolower(pathinfo($resolved, PATHINFO_EXTENSION));
        $webPath = str_replace('\\', '/', $resolved);
        
        // Handle width/height from alias
        $style = '';
        if (is_numeric($alias)) {
            $style = 'width: ' . htmlspecialchars($alias) . 'px;';
        } elseif (preg_match('/^(\d+)x(\d+)$/', $alias, $dimMatches)) {
            $style = 'width: ' . $dimMatches[1] . 'px; height: ' . $dimMatches[2] . 'px;';
        }

        if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'])) {
            return '<img src="' . htmlspecialchars($webPath) . '" alt="' . htmlspecialchars($target) . '" class="obsidian-embed image-embed" style="' . $style . '">';
        } elseif ($ext === 'pdf') {
            return '<iframe src="' . htmlspecialchars($webPath) . '" class="obsidian-embed pdf-embed" style="width: 100%; height: 600px; ' . $style . '"></iframe>';
        } else {
            return '<a href="' . htmlspecialchars($webPath) . '" class="obsidian-embed file-embed" download>' . htmlspecialchars($target) . '</a>';
        }
    }
    
    $unresolvedAlias = $alias !== '' ? '&#124;' . htmlspecialchars($alias) : '';
    return '<span class="is-unresolved">![[' . htmlspecialchars($target) . $unresolvedAlias . ']]</span>';
}, $markdownContent);

// 3. Wikilinks: [[file]] or [[file|Alias]]
$markdownContent = preg_replace_callback('/\[\[([^\]]+)\]\]/', function($matches) use ($currentDir, $vaultDir) {
    $content = $matches[1];
    $content = str_replace('\|', '|', $content); // Unescape \|
    $parts = explode('|', $content, 2);
    $target = trim($parts[0]);
    $alias = isset($parts[1]) ? trim($parts[1]) : $target;
    
    $resolved = resolve_obsidian_link($target, $currentDir, $vaultDir);
    if ($resolved) {
        $ext = strtolower(pathinfo($resolved, PATHINFO_EXTENSION));
        if ($ext === 'md') {
            $relPath = substr($resolved, strlen($vaultDir) + 1, -3); // remove vaultDir/ and .md
            $relPath = str_replace('\\', '/', $relPath);
            return '<a href="?page=' . urlencode($relPath) . '" class="internal-link">' . htmlspecialchars($alias) . '</a>';
        } else {
            $webPath = str_replace('\\', '/', $resolved);
            return '<a href="' . htmlspecialchars($webPath) . '" class="internal-link" target="_blank">' . htmlspecialchars($alias) . '</a>';
        }
    }
    return '<a class="internal-link is-unresolved">' . htmlspecialchars($alias) . '</a>';
}, $markdownContent);

// Dynamically load CSS snippets
$cssFiles = [];
if (is_dir('css-snippets')) {
    $files = scandir('css-snippets');
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'css') {
            $cssFiles[] = 'css-snippets/' . $file;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(basename($page)) ?></title>
    
    <!-- Obsidian Base CSS -->
    <link rel="stylesheet" href="app.css">
    
    <!-- Snippets CSS -->
    <?php foreach ($cssFiles as $css): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($css) ?>">
    <?php endforeach; ?>

    <!-- Highlight.js CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        html, body {
            margin: 0; padding: 0; font-family: var(--font-text); 
            background-color: var(--background-primary, #1e1e1e); 
            color: var(--text-normal, #dcddde); 
            overflow-y: auto !important; /* Allow scrolling */
            height: 100% !important;
            min-height: 100vh !important;
            box-sizing: border-box;
        }
        .markdown-rendered { 
            max-width: 800px; /* Revert to 800px as user preferred */
            margin: 0 auto; 
            padding: 20px;
            line-height: 1.6; 
            box-sizing: border-box;
        }
        
        /* Table Overrides */
        .markdown-rendered table {
            width: 100%;
            display: table !important;
            overflow: visible !important;
        }
        .markdown-rendered tbody > tr > td, 
        .markdown-rendered thead > tr > th {
            white-space: normal !important;
            word-break: normal !important;
            overflow: visible !important;
            height: auto !important;
            padding: 12px !important;
        }
        .is-unresolved { color: var(--text-muted); opacity: 0.6; }
        a.internal-link { text-decoration: none; color: var(--text-accent); }
        a.internal-link:hover { text-decoration: underline; }
        
        /* Fallback styling if not provided by app.css */
        img.obsidian-embed { max-width: 100%; border-radius: 4px; }
    </style>
</head>
<body class="theme-dark">
    <div class="markdown-rendered" id="content"></div>

    <textarea id="raw-markdown" style="display:none;"><?= htmlspecialchars($markdownContent) ?></textarea>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/mermaid/dist/mermaid.min.js"></script>
    
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            // Init Mermaid
            mermaid.initialize({ startOnLoad: false, theme: 'dark' });

            const rawMd = document.getElementById('raw-markdown').value;
            
            // Custom renderer for Mermaid in marked.js
            const renderer = new marked.Renderer();
            const originalCode = renderer.code.bind(renderer);
            renderer.code = function(code, language, isEscaped) {
                if (language === 'mermaid') {
                    return '<div class="mermaid">' + code + '</div>';
                }
                return originalCode(code, language, isEscaped);
            };
            
            marked.use({ renderer });
            
            // Allow marked to process HTML tags (so <span>[[halaman-lain]]</span> works seamlessly)
            const html = marked.parse(rawMd, { breaks: true, gfm: true });
            document.getElementById('content').innerHTML = html;

            // Transform Blockquotes into Obsidian Callouts
            document.querySelectorAll('#content blockquote').forEach(bq => {
                const firstP = bq.querySelector('p');
                if (!firstP) return;
                
                const htmlContent = firstP.innerHTML;
                const match = htmlContent.match(/^\s*\[!([a-zA-Z0-9-]+)\]([\+\-]?)\s*(.*?)(?:<br>|\n|$)/i);
                
                if (match) {
                    const type = match[1].toLowerCase();
                    const fold = match[2];
                    const titleText = match[3].trim() || type.charAt(0).toUpperCase() + type.slice(1);
                    
                    const isCollapsible = fold === '+' || fold === '-';
                    const isOpen = fold === '+';
                    
                    const calloutWrapper = document.createElement(isCollapsible ? 'details' : 'div');
                    calloutWrapper.className = 'callout' + (isCollapsible ? ' callout-collapsible' : '');
                    calloutWrapper.setAttribute('data-callout', type);
                    if (isOpen) calloutWrapper.setAttribute('open', '');
                    
                    const titleEl = document.createElement(isCollapsible ? 'summary' : 'div');
                    titleEl.className = 'callout-title';
                    
                    if (isCollapsible) {
                        const foldIcon = document.createElement('i');
                        foldIcon.className = 'callout-fold';
                        foldIcon.setAttribute('data-lucide', 'chevron-right');
                        titleEl.appendChild(foldIcon);
                    }
                    
                    const iconEl = document.createElement('div');
                    iconEl.className = 'callout-icon';
                    const iconI = document.createElement('i');
                    const iconMap = { info: 'info', note: 'pencil', tip: 'flame', success: 'check-circle', question: 'help-circle', warning: 'alert-triangle', error: 'zap', bug: 'bug', example: 'list', quote: 'quote' };
                    iconI.setAttribute('data-lucide', iconMap[type] || 'pencil');
                    iconEl.appendChild(iconI);
                    titleEl.appendChild(iconEl);
                    
                    const titleInnerEl = document.createElement('div');
                    titleInnerEl.className = 'callout-title-inner';
                    titleInnerEl.innerHTML = titleText;
                    titleEl.appendChild(titleInnerEl);
                    
                    const contentEl = document.createElement('div');
                    contentEl.className = 'callout-content';
                    firstP.innerHTML = htmlContent.substring(match[0].length);
                    while (bq.firstChild) { contentEl.appendChild(bq.firstChild); }
                    
                    calloutWrapper.appendChild(titleEl);
                    calloutWrapper.appendChild(contentEl);
                    bq.parentNode.replaceChild(calloutWrapper, bq);
                }
            });

            // Initialize Lucide Icons
            lucide.createIcons();

            // Highlight syntax
            hljs.highlightAll();

            // Render Mermaid diagrams
            setTimeout(() => {
                mermaid.run({
                    querySelector: '.mermaid'
                });
            }, 100);
        });
    </script>
</body>
</html>
