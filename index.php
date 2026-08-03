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
    $ext = pathinfo($target, PATHINFO_EXTENSION);
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
        $found = search_file_recursive($target, $dir, $checkedDirs, $isMd);
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
// 1. Callouts (Line by Line processing for multi-line blockquotes)
$lines = explode("\n", $markdownContent);
$inCallout = false;
$calloutCollapsible = false;
$newLines = [];

foreach ($lines as $line) {
    if (preg_match('/^>\s*\[!([a-zA-Z0-9-]+)\]([\+\-]?)(.*)$/', $line, $matches)) {
        if ($inCallout) {
            $newLines[] = $calloutCollapsible ? "</div></details></div>\n" : "</div></div>\n";
        }
        $inCallout = true;
        $type = strtolower($matches[1]);
        $collapse = $matches[2];
        $title = trim($matches[3]);
        if (empty($title)) $title = ucfirst($type);
        
        $calloutCollapsible = ($collapse === '+' || $collapse === '-');
        $isOpen = ($collapse === '+');
        
        $html = '<div class="callout callout-'. htmlspecialchars($type) .'" data-callout="'.htmlspecialchars($type).'">';
        if ($calloutCollapsible) {
            $html .= '<details class="callout-collapsible" ' . ($isOpen ? 'open' : '') . '>';
            $html .= '<summary class="callout-title"><div class="callout-title-inner">' . htmlspecialchars($title) . '</div></summary>';
            $html .= '<div class="callout-content">';
        } else {
            $html .= '<div class="callout-title"><div class="callout-title-inner">' . htmlspecialchars($title) . '</div></div>';
            $html .= '<div class="callout-content">';
        }
        $newLines[] = $html;
    } elseif ($inCallout && preg_match('/^>(.*)$/', $line, $matches)) {
        $newLines[] = $matches[1];
    } else {
        if ($inCallout) {
            $newLines[] = $calloutCollapsible ? "</div></details></div>\n" : "</div></div>\n";
            $inCallout = false;
        }
        $newLines[] = $line;
    }
}
if ($inCallout) {
    $newLines[] = $calloutCollapsible ? "</div></details></div>\n" : "</div></div>\n";
}
$markdownContent = implode("\n", $newLines);

// 2. Embeds: ![[file]]
$markdownContent = preg_replace_callback('/!\[\[([^\]]+)\]\]/', function($matches) use ($currentDir, $vaultDir) {
    $target = $matches[1];
    $resolved = resolve_obsidian_link($target, $currentDir, $vaultDir);
    if ($resolved) {
        $ext = strtolower(pathinfo($resolved, PATHINFO_EXTENSION));
        $webPath = str_replace('\\', '/', $resolved);
        
        if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'])) {
            return '<img src="' . htmlspecialchars($webPath) . '" alt="' . htmlspecialchars($target) . '" class="obsidian-embed image-embed">';
        } elseif ($ext === 'pdf') {
            return '<iframe src="' . htmlspecialchars($webPath) . '" class="obsidian-embed pdf-embed" width="100%" height="600px"></iframe>';
        } else {
            return '<a href="' . htmlspecialchars($webPath) . '" class="obsidian-embed file-embed" download>' . htmlspecialchars($target) . '</a>';
        }
    }
    return '<span class="is-unresolved">![[' . htmlspecialchars($target) . ']]</span>';
}, $markdownContent);

// 3. Wikilinks: [[file]] or [[file|Alias]]
$markdownContent = preg_replace_callback('/\[\[([^\]]+)\]\]/', function($matches) use ($currentDir, $vaultDir) {
    $content = $matches[1];
    $parts = explode('|', $content, 2);
    $target = $parts[0];
    $alias = isset($parts[1]) ? $parts[1] : $target;
    
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
    
    <style>
        body { 
            margin: 0; padding: 20px; font-family: var(--font-text); 
            background-color: var(--background-primary, #1e1e1e); 
            color: var(--text-normal, #dcddde); 
        }
        .markdown-rendered { max-width: 800px; margin: 0 auto; line-height: 1.6; }
        .is-unresolved { color: var(--text-muted); opacity: 0.6; }
        a.internal-link { text-decoration: none; color: var(--text-accent); }
        a.internal-link:hover { text-decoration: underline; }
        
        /* Fallback styling if not provided by app.css */
        .callout { border: 1px solid var(--background-modifier-border); border-radius: 4px; padding: 12px; margin: 1em 0; background-color: var(--background-secondary); }
        .callout-title { font-weight: bold; margin-bottom: 8px; }
        details.callout-collapsible summary { cursor: pointer; font-weight: bold; }
        details.callout-collapsible > .callout-content { margin-top: 8px; }
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
