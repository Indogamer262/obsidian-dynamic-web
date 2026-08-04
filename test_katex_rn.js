const marked = require('marked');
const markedKatex = require('marked-katex-extension');

marked.use(markedKatex({ throwOnError: false }));

const md = "$$\r\nP(A \\cup B)\r\n$$";
console.log("With \\r\\n:");
console.log(marked.parse(md));

const md2 = "$$\nP(A \\cup B)\n$$";
console.log("With \\n:");
console.log(marked.parse(md2));
