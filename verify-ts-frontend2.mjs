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
      beforeSelected: before ? before.classList.contains('e--selected') : null,
      afterSelected: after ? after.classList.contains('e--selected') : null,
      beforeBg: cs(before),
      afterBg: cs(after),
    };
  });
  console.log(label, JSON.stringify(info));
}

await report('INITIAL (settled)');

await page.click('.aae-ts-label-after'); // click "Yearly"
await page.waitForTimeout(400); // let the 200ms transition fully settle
await report('SETTLED AFTER CLICK YEARLY');

await page.mouse.click(5, 5); // click far outside the widget
await page.waitForTimeout(400);
await report('SETTLED AFTER CLICK OUTSIDE #1');

await page.mouse.click(600, 5);
await page.waitForTimeout(400);
await report('SETTLED AFTER CLICK OUTSIDE #2');

await browser.close();
