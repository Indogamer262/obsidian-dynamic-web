const { marked } = require('marked');

const rawMd = `---
title: Midnight Home
---

| <img src="vault/pexels-kelly-1179532-2688664.jpg" alt="pexels-kelly-1179532-2688664.jpg" class="obsidian-embed image-embed" style="width: 240px;"> | <span style="font-size:26px;font-weight:bold;">Midnight</span><br><span style="font-weight: normal;">Maranatha Infinite Discoveries at Night</span><br><br><div style="width:400px;"><b>Midnight</b> <span style="font-weight: normal;">adalah situs pembelajaran Teknik Informatika yang dibuat oleh 2472008. Situs ini gratis dan dapat diakses oleh semua orang!</span></div> |
| ------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
<div style="border:1px solid white;margin:10px 0 10px 0;padding:8px;border-radius:4px;">Home/</div>

---
<div class="callout" data-callout="info"><div class="callout-title"><div class="callout-icon"></div><div class="callout-title-inner">Selamat datang di Midnight v2</div></div><div class="callout-content">
Ini adalah tampilan terbaru dari Midnight, lebih ringan, lebih efisien!
Midnight kali ini berbasis \`Obsidian\`
</div></div>
# Materi Belajar
<span class="nav-card"><a href="?page=Semester+3%2FSemester+3" class="internal-link">🖿 Semester 3</a></span>
`;

console.log(marked.parse(rawMd, { breaks: true, gfm: true }));
