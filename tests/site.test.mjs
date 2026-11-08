import test from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync, existsSync} from 'node:fs';
import vm from 'node:vm';
const html=readFileSync(new URL('../index.html',import.meta.url),'utf8');
const ids=[...html.matchAll(/\bid="([^"]+)"/g)].map(m=>m[1]);
test('HTML includes semantic structure and unique IDs',()=>{
  for(const token of ['<html lang="cs"','<main id="content"','</main>','<footer','</html>'])assert.ok(html.includes(token),token);
  assert.equal(new Set(ids).size,ids.length);
});
test('All five product cards link to intended destinations without exposing CRM data',()=>{
  for(const domain of ['muj-dip.jirijanousek.cz','moje-renta.jirijanousek.cz','moje-krypto.jirijanousek.cz','muj-kmen.jirijanousek.cz','muj-obchod.jirijanousek.cz'])assert.ok(html.includes(domain),domain);
  assert.match(html,/href="https:\/\/muj-kmen\.jirijanousek\.cz\/"[^>]*data-i="partnerApp"/);
  assert.ok(html.includes('Soukromé CRM pro klientskou péči'));
  assert.match(html,/href="https:\/\/muj-obchod\.jirijanousek\.cz\/"[^>]*data-i="openShop"/);
  assert.equal([...html.matchAll(/<article class="product /g)].length,5);
});
test('All responsive artwork is self-hosted',()=>{
  const photos=[...new Set(html.match(/assets\/[a-z0-9-]+\.webp/g))];
  assert.equal(photos.length,8);
  for(const file of photos)assert.ok(existsSync(new URL('../'+file,import.meta.url)),file);
  const styles=readFileSync(new URL('../toproco.css',import.meta.url),'utf8');
  assert.ok(styles.includes('max-width: 620px'));
});
test('Interaction code parses and covers five locales',()=>{
  const scripts=[...html.matchAll(/<script>([\s\S]*?)<\/script>/g)];
  assert.equal(scripts.length,1);
  assert.doesNotThrow(()=>new vm.Script(scripts[0][1]));
  for(const locale of ['cs','sk','en','es','zh'])assert.ok(html.includes('Object.assign(dict.'+locale+','),locale);
  for(const id of ['mobileNavButton','navDrop','lang','projectionChart','chartHorizon','premium','business','white'])assert.ok(ids.includes(id),id);
});
test('The homepage is indexable and exposes the canonical URL and sitemap',()=>{
  const home='https://toproco.jirijanousek.cz/';
  assert.ok(html.includes('<meta name="robots" content="index, follow'));
  assert.ok(html.includes('<link rel="canonical" href="'+home+'">'));
  assert.ok(existsSync(new URL('../assets/toproco-sdileni.jpg',import.meta.url)));
  const sitemap=readFileSync(new URL('../sitemap.xml',import.meta.url),'utf8');
  assert.ok(sitemap.includes('<loc>'+home+'</loc>'));
  const robots=readFileSync(new URL('../robots.txt',import.meta.url),'utf8');
  assert.ok(robots.includes('Sitemap: '+home+'sitemap.xml'));
  const jsonLd=html.match(/<script type="application\/ld\+json">([\s\S]*?)<\/script>/)[1];
  assert.equal(JSON.parse(jsonLd)['@graph'][0].url,home);
});

test('Disclaimers and access restrictions remain',()=>{
  assert.ok(html.includes('Nejde o doporučené portfolio'));
  assert.ok(existsSync(new URL('../.htaccess',import.meta.url)));
});

test('Brand uses gold Flower of Life and footer links open in new tabs',()=>{
  assert.equal(html.split('src="/assets/flower-of-life.svg"').length-1,2);
  assert.ok(existsSync(new URL('../assets/flower-of-life.svg',import.meta.url)));
  assert.ok(html.includes('class="footer-top"'));
  assert.ok(html.includes('class="footer-bottom"'));
  const footer=html.split('<footer class="footer"')[1].split('</footer>')[0];
  const links=[...footer.matchAll(/<a[^>]*href="([^"]+)"[^>]*>/g)].filter(x=>!['#top','/vzhled.php'].includes(x[1]));
  assert.equal(links.length,3);
  assert.match(footer, /href="\/vzhled\.php" rel="nofollow"/);
  for(const link of links){assert.match(link[0],/target="_blank"/);assert.match(link[0],/rel="noopener noreferrer"/)}
  const privacy=readFileSync(new URL('../privacy.html',import.meta.url),'utf8');
  assert.ok(privacy.includes('id="storage"'));
});
