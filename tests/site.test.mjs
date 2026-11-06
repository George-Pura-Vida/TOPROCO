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
test('All four product cards link to intended destinations without exposing CRM data',()=>{
  for(const domain of ['muj-dip.jirijanousek.cz','moje-renta.jirijanousek.cz','moje-krypto.jirijanousek.cz','muj-kmen.jirijanousek.cz'])assert.ok(html.includes(domain),domain);
  assert.match(html,/href="https:\/\/muj-kmen\.jirijanousek\.cz\/"[^>]*data-i="partnerApp"/);
  assert.ok(html.includes('Soukromé CRM pro klientskou péči'));
});
test('All responsive artwork is self-hosted',()=>{
  for(const file of ['hero-sunrise','product-dip','product-renta','product-krypto','product-kmen']){
    assert.ok(existsSync(new URL('../assets/'+file+'.svg',import.meta.url)),file);
  }
  assert.ok(html.includes('max-width:620px'));
});
test('Interaction code parses and covers five locales',()=>{
  const scripts=[...html.matchAll(/<script>([\s\S]*?)<\/script>/g)];
  assert.equal(scripts.length,1);
  assert.doesNotThrow(()=>new vm.Script(scripts[0][1]));
  for(const locale of ['cs','sk','en','es','zh'])assert.ok(html.includes('Object.assign(dict.'+locale+','),locale);
  for(const id of ['mobileNavButton','navDrop','lang','projectionChart','chartHorizon','premium','business','white'])assert.ok(ids.includes(id),id);
});
test('Disclaimers and prelaunch restrictions remain',()=>{
  assert.ok(html.includes('noindex,nofollow'));
  assert.ok(html.includes('Nejde o doporučené portfolio'));
  assert.ok(existsSync(new URL('../.htaccess',import.meta.url)));
});

test('Brand uses gold Flower of Life and footer links open in new tabs',()=>{
  assert.equal(html.split('src="/assets/flower-of-life.svg"').length-1,2);
  assert.ok(existsSync(new URL('../assets/flower-of-life.svg',import.meta.url)));
  assert.ok(html.includes('class="footer-top"'));
  assert.ok(html.includes('class="footer-bottom"'));
  const footer=html.split('<footer class="footer"')[1].split('</footer>')[0];
  const links=[...footer.matchAll(/<a[^>]*href="([^"]+)"[^>]*>/g)].filter(x=>x[1]!=='#top');
  assert.equal(links.length,3);
  for(const link of links){assert.match(link[0],/target="_blank"/);assert.match(link[0],/rel="noopener noreferrer"/)}
  const privacy=readFileSync(new URL('../privacy.html',import.meta.url),'utf8');
  assert.ok(privacy.includes('id="storage"'));
});
