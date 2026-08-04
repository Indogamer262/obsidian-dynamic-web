const marked = require('marked');
const md = `> [!note]-
> 
> <div class="markdown-embed">
> 
> # Hello
> This is **markdown**.
> 
> </div>
> 
`;
console.log(marked.parse(md));
