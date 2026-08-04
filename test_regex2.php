<?php
$markdown = "
> [!note]-
> > ![[Prepare Kuis.md]]
Here is an inline ![[image.png]] embed.
- ![[list_embed.md]]
";

$content = preg_replace_callback('/(?:^([ \t>*-]*))?!\[\[([^\]]+)\]\]/m', function($matches) {
    $prefix = isset($matches[1]) ? $matches[1] : '';
    $target = $matches[2];
    return "[PREFIX: '$prefix', TARGET: '$target']";
}, $markdown);

echo $content;
