<?php
$markdown = "- ![[image1.png]] and ![[image2.png]]";

$content = preg_replace_callback('/(?:^([ \t>*-]*))?!\[\[([^\]]+)\]\]/m', function($matches) {
    $prefix = isset($matches[1]) ? $matches[1] : '';
    $target = $matches[2];
    return "[PREFIX: '$prefix', TARGET: '$target']";
}, $markdown);

echo $content;
