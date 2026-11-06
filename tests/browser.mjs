import assert from 'node:assert/strict';
import {chromium} from 'playwright';
import {resolve} from 'node:path';
const browser=await chromium.launch({headless:true,args:['--no-sandbox']});
const url='file://'+resolve('index.html');
try{
 for(const width of [320,390,768,1024,1440]){
  const context=await browser.newContext({viewport:{width,height:900}});
  const page=await context.newPage();
  const errors=[];
  page.on('pageerror',e=>errors.push(e.message));
  await page.goto(url,{waitUntil:'load'});
  const cards=await page.locator('.product').all();
  assert.equal(cards.length,4,'Expected four visual product cards at '+width);
  for(const card of cards){const r=await card.boundingBox();assert.ok(r.x>=-2&&r.x+r.width<=width+2,'Overflowing product card at '+width+': '+JSON.stringify(r))}
  assert.equal(await page.locator('#projA').getAttribute('d')===null,false,'Chart path not rendered at '+width);
  assert.deepEqual(errors,[],'JavaScript errors at '+width);
  if(width<1000){
    await page.locator('#mobileNavButton').click();
    assert.equal(await page.locator('#mobileNavButton').getAttribute('aria-expanded'),'true');
    assert.equal(await page.locator('#navDrop').isVisible(),true);
    await page.locator('#navDrop a[href="#produkty"]').click();
    assert.equal(await page.locator('#mobileNavButton').getAttribute('aria-expanded'),'false');
  }
  if(width===390){
    await page.locator('#lang').selectOption('en');
    assert.match(await page.locator('.hero h1').innerText(),/Together we create/);
    await page.locator('#chartHorizon').selectOption('30');
    const final=await page.locator('#chartFinal').innerText();assert.match(final,/Kč/);
    await page.screenshot({path:'toproco-mobile.png',fullPage:true});
  }
  if(width===1440)await page.screenshot({path:'toproco-desktop.png',fullPage:true});
  await context.close();
  console.log('PASS '+width+'px: cards, chart, JS and navigation');
 }
}finally{await browser.close()}
