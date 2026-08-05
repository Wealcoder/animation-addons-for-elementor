import { chromium } from 'playwright';

const url = 'http://elementor-v4-animation-addons.test/aae-toggle-switcher-selftest/';

const browser = await chromium.launch();
const page = await browser.newPage();
await page.goto(url, { waitUntil: 'networkidle' });

async function report(label) {
  const info = await page.evaluate(() => {
    const before = document.querySelector('.aae-ts-label-before');
    const after = document.querySelector('.aae-ts-label-after');
    const cs = (el) => (el ? getComputedStyle(el).backgroundColor : null);
    return {
      beforeClasses: before ? before.className : null,
      afterClasses: after ? after.className : null,
      beforeBg: cs(before),
      afterBg: cs(after),
    };
  });
  console.log(label, JSON.stringify(info, null, 2));
}

await report('INITIAL');

await page.click('.aae-ts-label-after'); // click "Yearly"
await report('AFTER CLICK YEARLY');

// Click somewhere clearly outside the widget entirely
await page.mouse.click(5, 5);
await report('AFTER CLICK OUTSIDE (body corner)');

// Also try clicking on the page background further away
await page.click('body', { position: { x: 400, y: 5 } });
await report('AFTER SECOND CLICK OUTSIDE');

await browser.close();
