const marked = require('marked');
const md = `> [!note]-
> <span class="markdown-embed" style="display:block">
# Hello
This is **markdown**.
</span>`;
console.log(marked.parse(md));
