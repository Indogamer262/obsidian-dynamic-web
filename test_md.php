<?php
$markdownContent = file_get_contents('vault/index.md');
// Process embeds
$markdownContent = preg_replace_callback('/!\[\[([^\]]+)\]\]/', function($matches) {
    $content = $matches[1];
    $content = str_replace('\|', '&#124;', $content); 
    $parts = explode('|', $content, 2);
    $target = str_replace('&#124;', '|', trim($parts[0]));
    $alias = isset($parts[1]) ? str_replace('&#124;', '|', trim($parts[1])) : '';
    return '<img src="test" alt="' . $target . ' - ' . $alias . '">';
}, $markdownContent);
echo $markdownContent;
