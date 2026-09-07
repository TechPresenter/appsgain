<?xml version="1.0" encoding="UTF-8"?>
<!--
  Appsgain Technologies — XML sitemap stylesheet.
  Browsers apply this to sitemap.xml so the file is readable by a person;
  crawlers ignore it and read the raw XML underneath.
-->
<xsl:stylesheet version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:s="http://www.sitemaps.org/schemas/sitemap/0.9"
  xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">

<xsl:output method="html" encoding="UTF-8" indent="yes"/>

<xsl:template match="/">
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>XML Sitemap — Appsgain Technologies</title>
  <style>
    :root{
      --violet:#6A00FF; --magenta:#D000A8; --ink:#0B1026;
      --bg:#f6f7fb; --card:#fff; --line:#e5e7f0; --gray:#5b6178;
    }
    @media (prefers-color-scheme:dark){
      :root{ --bg:#0B1026; --card:#141a33; --line:#26304f; --ink:#eef1f8; --gray:#9aa3c0; }
    }
    *{box-sizing:border-box}
    body{margin:0;padding:28px 18px;background:var(--bg);color:var(--ink);
         font:15px/1.55 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif}
    .wrap{max-width:1080px;margin:0 auto}
    h1{margin:0 0 6px;font-size:26px;letter-spacing:-.02em}
    .brand{background:linear-gradient(135deg,var(--violet),var(--magenta));
           -webkit-background-clip:text;background-clip:text;color:transparent}
    .sub{color:var(--gray);margin:0 0 22px;font-size:14px}
    .count{display:inline-block;background:var(--violet);color:#fff;border-radius:999px;
           padding:3px 12px;font-size:13px;font-weight:600;margin-left:8px}
    .card{background:var(--card);border:1px solid var(--line);border-radius:14px;overflow:hidden}
    .scroll{overflow-x:auto}
    table{width:100%;border-collapse:collapse;font-size:14px;min-width:640px}
    th{text-align:left;padding:12px 16px;background:linear-gradient(135deg,var(--violet),var(--magenta));
       color:#fff;font-weight:600;font-size:12.5px;text-transform:uppercase;letter-spacing:.04em}
    td{padding:11px 16px;border-top:1px solid var(--line);vertical-align:top}
    tr:hover td{background:rgba(106,0,255,.05)}
    a{color:var(--violet);text-decoration:none;word-break:break-all}
    a:hover{text-decoration:underline}
    .num{color:var(--gray);width:52px;font-variant-numeric:tabular-nums}
    .meta{color:var(--gray);white-space:nowrap;font-variant-numeric:tabular-nums}
    .bar{display:inline-block;height:6px;border-radius:3px;
         background:linear-gradient(90deg,var(--violet),var(--magenta));vertical-align:middle;margin-right:7px}
    footer{color:var(--gray);font-size:12.5px;margin-top:18px;text-align:center}
  </style>
</head>
<body>
  <div class="wrap">
    <h1><span class="brand">XML Sitemap</span><span class="count"><xsl:value-of select="count(s:urlset/s:url)"/> URLs</span></h1>
    <p class="sub">This file tells search engines which pages to crawl. It is generated from the live database — no manual editing needed.</p>

    <div class="card"><div class="scroll">
      <table>
        <tr><th class="num">#</th><th>URL</th><th>Updated</th><th>Frequency</th><th>Priority</th></tr>
        <xsl:for-each select="s:urlset/s:url">
          <tr>
            <td class="num"><xsl:value-of select="position()"/></td>
            <td><a href="{s:loc}"><xsl:value-of select="s:loc"/></a></td>
            <td class="meta"><xsl:value-of select="s:lastmod"/></td>
            <td class="meta"><xsl:value-of select="s:changefreq"/></td>
            <td class="meta">
              <span class="bar" style="width:{round(s:priority * 34)}px"></span>
              <xsl:value-of select="s:priority"/>
            </td>
          </tr>
        </xsl:for-each>
      </table>
    </div></div>

    <footer>Appsgain Technologies Private Limited — sitemap.xml</footer>
  </div>
</body>
</html>
</xsl:template>
</xsl:stylesheet>
