// Captures product screenshots of the running app with Playwright.
// Prereq: backend on :8000 and frontend on :4200 must be running.
//   node tools/screenshots.mjs
import { chromium } from 'playwright';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const APP = process.env.APP_URL ?? 'http://localhost:4200';
const API = process.env.API_URL ?? 'http://localhost:8000/api';
const OUT = resolve(dirname(fileURLToPath(import.meta.url)), '../../docs/screenshots');

async function token(email, password) {
  const res = await fetch(`${API}/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ email, password }),
  });
  if (!res.ok) throw new Error(`login ${email} failed: ${res.status}`);
  return (await res.json()).token;
}

const shots = [
  { name: 'home', path: '/' },
  { name: 'reservar', path: '/reservar' },
  { name: 'login', path: '/login' },
  { name: 'contacto', path: '/contacto' },
  { name: 'cuenta', path: '/cuenta', as: ['cliente@laguna.com', 'password'] },
  { name: 'admin-dashboard', path: '/admin', as: ['admin@laguna.com', 'password'] },
  { name: 'admin-reservas', path: '/admin/reservas', as: ['admin@laguna.com', 'password'] },
  { name: 'admin-ajustes', path: '/admin/ajustes', as: ['admin@laguna.com', 'password'] },
];

const browser = await chromium.launch();
try {
  for (const s of shots) {
    const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 }, deviceScaleFactor: 2 });
    if (s.as) {
      const t = await token(...s.as);
      await ctx.addInitScript((tok) => localStorage.setItem('rl_token', tok), t);
    }
    const page = await ctx.newPage();
    await page.goto(`${APP}${s.path}`, { waitUntil: 'networkidle' });
    // Scroll the full page to trigger reveal-on-scroll (IntersectionObserver)
    // sections and lazy content, then return to the top.
    await page.evaluate(async () => {
      const step = window.innerHeight;
      for (let y = 0; y < document.body.scrollHeight; y += step) {
        window.scrollTo(0, y);
        await new Promise((r) => setTimeout(r, 250));
      }
      window.scrollTo(0, 0);
    });
    await page.waitForTimeout(1800); // let images / map tiles / animations settle
    await page.screenshot({ path: `${OUT}/${s.name}.png`, fullPage: true });
    console.log(`✓ ${s.name}.png`);
    await ctx.close();
  }
} finally {
  await browser.close();
}
