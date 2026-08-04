const marked = require('marked');
const katex = require('katex');

const blockMath = {
  name: 'blockMath',
  level: 'inline', // Matches anywhere inline
  start(src) { return src.indexOf('$$'); },
  tokenizer(src, tokens) {
    const match = /^\$\$([^]+?)\$\$/.exec(src);
    if (match) {
      return {
        type: 'blockMath',
        raw: match[0],
        text: match[1]
      };
    }
  },
  renderer(token) {
    return '<div class="math math-block">' + katex.renderToString(token.text, { displayMode: true, throwOnError: false }) + '</div>';
  }
};

const inlineMath = {
  name: 'inlineMath',
  level: 'inline',
  start(src) { return src.indexOf('$'); },
  tokenizer(src, tokens) {
    const match = /^\$([^$\n]+?)\$/.exec(src);
    if (match) {
      return {
        type: 'inlineMath',
        raw: match[0],
        text: match[1]
      };
    }
  },
  renderer(token) {
    return '<span class="math math-inline">' + katex.renderToString(token.text, { displayMode: false, throwOnError: false }) + '</span>';
  }
};

marked.use({ extensions: [blockMath, inlineMath] });

const md = `- Dependent Relationship
  $$
  P(A \cup B) = P(A) + P(B)
  $$`;

console.log(marked.parse(md));
