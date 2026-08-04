<?php
$markdown = "
> [!note]-
> > ![[Prepare Kuis.md]]
";

$content = preg_replace_callback('/^([ \t>*-]*)(!\[\[([^\]]+)\]\])/m', function($matches) {
    $prefix = $matches[1];
    $target = $matches[3];
    
    $embedContent = "Line 1\n\n# Line 2\nLine 3";
    
    // Prepend prefix to all lines of embedContent
    $lines = explode("\n", $embedContent);
    $processedLines = array_map(function($line) use ($prefix) {
        return $prefix . $line;
    }, $lines);
    
    return implode("\n", $processedLines);
}, $markdown);

echo $content;
