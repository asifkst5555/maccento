import path from 'node:path';
import { pathToFileURL } from 'node:url';
import { chromium } from 'playwright';

const inputHtml = path.resolve('Maccento_CRM_User_Manual.html');
const outputPdf = path.resolve('Maccento_CRM_User_Manual.pdf');

const preferredChannel = process.env.PLAYWRIGHT_CHANNEL?.trim();
const channelsToTry = [preferredChannel, 'chrome', 'msedge', null]
  .filter((v, i, a) => a.indexOf(v) === i)
  .filter((v) => v !== undefined && v !== '');

let browser;
let lastError;
for (const channel of channelsToTry) {
  try {
    browser = await chromium.launch({
      headless: true,
      ...(channel ? { channel } : {}),
    });
    lastError = undefined;
    break;
  } catch (error) {
    lastError = error;
  }
}

if (!browser) {
  throw lastError ?? new Error('Failed to launch Chromium.');
}

try {
  const page = await browser.newPage();
  await page.goto(pathToFileURL(inputHtml).toString(), { waitUntil: 'networkidle' });
  await page.emulateMedia({ media: 'print' });

  await page.pdf({
    path: outputPdf,
    format: 'A4',
    printBackground: true,
    preferCSSPageSize: true,
  });

  console.log(`Wrote ${outputPdf}`);
} finally {
  await browser.close();
}
