import assert from 'node:assert/strict';
import {execFileSync, spawn} from 'node:child_process';
import {mkdtempSync, rmSync, writeFileSync} from 'node:fs';
import {createServer} from 'node:net';
import {tmpdir} from 'node:os';
import {join} from 'node:path';
import {setTimeout as sleep} from 'node:timers/promises';
import {chromium} from 'playwright';

const php = process.env.PHP_EXECUTABLE || 'php';
const password = 'Only a local test password 0843';
const directory = mkdtempSync(join(tmpdir(), 'toproco-test-'));
const port = await new Promise((resolve, reject) => {
    const socket = createServer().on('error', reject).listen(0, '127.0.0.1', () => {
        const port = socket.address().port;
        socket.close(() => resolve(port));
    });
});
const url = 'http://127.0.0.1:' + port;
let server;
let browser;

try {
    writeFileSync(join(directory, 'password.hash'), execFileSync(php, [
        '-r', 'echo password_hash(stream_get_contents(STDIN), PASSWORD_DEFAULT);',
    ], {input: password}));
    const router = join(directory, 'router.php');
    // Simulate the host HTTPS flag. Browsers accept Secure cookies on loopback.
    writeFileSync(router, "<?php $_SERVER['HTTPS'] = 'on'; return false;");
    server = spawn(php, ['-d', 'session.save_path=' + directory, '-S', '127.0.0.1:' + port, router], {
        env: {...process.env, TOPROCO_THEME_DIR: directory}, stdio: 'ignore',
    });
    let response;
    for (let attempt = 0; attempt < 30; attempt++) {
        response = await fetch(url + '/vzhled.php?css=1').catch(() => null);
        if (response) break;
        await sleep(100);
    }
    assert.equal(response?.status, 200, 'PHP server did not start');
    const etag = response.headers.get('etag');
    assert.match(await response.text(), /--button: #08654b/);
    assert.equal((await fetch(url + '/vzhled.php?css=1', {headers:{'If-None-Match':etag}})).status, 304);
    assert.equal((await fetch(url + '/vzhled.php', {
        method:'POST', body:new URLSearchParams({action:'save'}),
    })).status, 403);

    browser = await chromium.launch({
        args:['--no-sandbox'], executablePath:process.env.CHROMIUM_EXECUTABLE,
    });
    const page = await browser.newPage({viewport:{width:1200,height:1040}});
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto(url + '/vzhled.php');
    assert.equal(await page.locator('#palette').count(), 0);
    await page.getByLabel('Heslo správce').fill(password);
    await page.getByRole('button', {name:'Přihlásit se'}).click();
    await page.locator('#palette').waitFor();
    const cookie = (await page.context().cookies()).find(value => value.name === 'ToprocoVzhled');
    assert.equal(cookie?.secure, true);
    assert.equal(cookie?.httpOnly, true);
    const previewColor = () => page.locator('#preview').evaluate(element => getComputedStyle(element).backgroundColor);

    for (const [scheme, background] of [
        ['ziva','rgb(251, 248, 240)'], ['modra','rgb(244, 248, 255)'],
        ['tepla','rgb(255, 247, 244)'], ['tmava','rgb(19, 35, 53)'],
    ]) {
        await page.locator('#preset').selectOption(scheme);
        assert.equal(await previewColor(), background);
        assert.match(await page.locator('#contrast').innerText(), /dostatečný kontrast/);
    }
    const text = page.locator('input[name=text]');
    await text.fill('#132335');
    assert.match(await page.locator('#contrast').innerText(), /nízký kontrast/);
    const rejected = page.waitForResponse(response => response.request().method() === 'POST');
    await page.getByRole('button', {name:'Uložit vzhled webu'}).click();
    assert.equal((await rejected).status(), 422);
    assert.match(await page.getByRole('alert').innerText(), /Zvyšte kontrast/);

    await page.locator('#preset').selectOption('tmava');
    await page.locator('input[name=font-body]').fill('18');
    await page.locator('input[name=heading-weight]').fill('700');
    assert.equal(await page.locator('#preset').inputValue(), '');
    await page.getByRole('button', {name:'Uložit vzhled webu'}).click();
    await page.getByText('Vzhled je uložený pro všechny návštěvníky.').waitFor();

    const visitor = await browser.newPage({viewport:{width:390,height:900}});
    await visitor.goto(url);
    assert.equal(await visitor.locator('body').evaluate(el => getComputedStyle(el).backgroundColor), 'rgb(19, 35, 53)');
    assert.equal(await visitor.locator('body').evaluate(el => getComputedStyle(el).fontSize), '18px');
    assert.equal(await visitor.locator('h1').evaluate(el => getComputedStyle(el).fontWeight), '700');
    assert.equal(await visitor.locator('.footer-admin').getAttribute('href'), '/vzhled.php');
    await page.getByRole('button', {name:'Odhlásit se'}).click();
    await page.getByLabel('Heslo správce').waitFor();
    assert.equal(await page.locator('#palette').count(), 0);
    assert.deepEqual(errors, []);
    console.log('PASS appearance: authentication, presets, live preview, contrast, public persistence and sign-out');
} finally {
    await browser?.close();
    server?.kill();
    rmSync(directory, {recursive:true, force:true});
}
