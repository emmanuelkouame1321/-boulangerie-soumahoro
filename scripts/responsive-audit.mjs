/**
 * Audit responsive des écrans (cahier §89, ADR-033).
 *
 * Mesure, pour chaque écran et chaque largeur de référence
 * (1440 / 1280 / 1024 / 768 / 390), le défilement horizontal du document
 * et liste les éléments qui dépassent de la fenêtre sans être contenus
 * par un conteneur défilant. Sortie non nulle si un dépassement est
 * détecté : la commande peut servir de garde-fou avant livraison.
 *
 * Aucune dépendance : Chrome est piloté par le protocole DevTools (CDP)
 * via le WebSocket natif de Node (Node 22+).
 *
 * Usage :
 *   SESSION_COOKIE_VALUE=<valeur du cookie de session> \
 *   node scripts/responsive-audit.mjs [--urls fichier.json] [--widths 1440,390]
 *                                  [--base http://127.0.0.1:8000] [--json rapport.json]
 *
 * Prérequis :
 *   - l'application servie (composer dev) et un compte connecté ;
 *   - la valeur du cookie de session relevée dans le navigateur
 *     (F12 → Application → Cookies) et passée dans SESSION_COOKIE_VALUE ;
 *   - Chrome installé (CHEMINS_CHROME ci-dessous, sinon variable CHROME_PATH).
 *
 * La liste d'écrans par défaut est celle de l'audit Phase 10 : écrans
 * d'administration, formulaires de création/édition et rapports. Les URLs
 * contenant un identifiant supposent un jeu de données de démonstration.
 */

import { readFileSync, writeFileSync, existsSync } from 'node:fs';
import { spawn } from 'node:child_process';

const CHROME_CANDIDATES = [
  'C:/Program Files/Google/Chrome/Application/chrome.exe',
  'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
  '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
  '/usr/bin/google-chrome',
  '/usr/bin/chromium-browser',
  '/usr/bin/chromium',
];

const DEFAULT_WIDTHS = [1440, 1280, 1024, 768, 390];

const DEFAULT_URLS = [
  '/dashboard',
  '/inventory',
  '/inventory/movements',
  '/inventory/transfers',
  '/inventory/transfers/create',
  '/products',
  '/products/create',
  '/products/1/edit',
  '/production',
  '/production/create',
  '/production/1',
  '/production/recipes',
  '/production/recipes/create',
  '/production/recipes/1/edit',
  '/purchasing/orders',
  '/purchasing/orders/create',
  '/purchasing/orders/1',
  '/sales',
  '/sales/create',
  '/sales/1',
  '/sales/closings',
  '/sales/closings/create',
  '/losses',
  '/losses/create',
  '/losses/1',
  '/customers',
  '/customers/create',
  '/wholesalers',
  '/wholesalers/create',
  '/wholesalers/1/edit',
  '/rebates',
  '/employees',
  '/employees/create',
  '/employees/1',
  '/payroll',
  '/expenses',
  '/expenses/create',
  '/expenses/1/edit',
  '/suppliers',
  '/suppliers/create',
  '/suppliers/1/edit',
  '/caisse',
  '/notifications',
  '/exports',
  '/reports',
  '/reports/sales',
  '/reports/stock',
  '/reports/production',
  '/reports/losses',
  '/reports/purchases',
  '/reports/wholesalers',
  '/reports/expenses',
  '/reports/payroll',
  '/reports/sales/print',
  '/settings/users',
  '/settings/users/create',
  '/settings/users/1/edit',
  '/settings/roles',
  '/settings/roles/create',
  '/settings/bakeries',
  '/settings/bakeries/create',
  '/settings/bakeries/1/edit',
  '/settings/units',
  '/settings/units/create',
  '/settings/audit',
  '/settings/notifications',
  '/settings/security',
];

const args = process.argv.slice(2);

const option = (name, fallback) => {
  const index = args.indexOf(name);

  return index === -1 ? fallback : args[index + 1];
};

const baseUrl = option('--base', process.env.AUDIT_BASE_URL ?? 'http://127.0.0.1:8000').replace(/\/$/, '');
const chromePath = process.env.CHROME_PATH ?? CHROME_CANDIDATES.find((candidate) => existsSync(candidate));
const widths = option('--widths', DEFAULT_WIDTHS.join(',')).split(',').map((value) => parseInt(value.trim(), 10)).filter((value) => Number.isFinite(value));
const reportFile = option('--json', null);
const urlsFile = option('--urls', null);
const cookieValue = process.env.SESSION_COOKIE_VALUE;
const cookieName = process.env.SESSION_COOKIE_NAME ?? 'boulangerie-soumahoro-session';
const urls = urlsFile ? JSON.parse(readFileSync(urlsFile, 'utf8')) : DEFAULT_URLS;

if (!chromePath) {
  console.error('Chrome introuvable : définir CHROME_PATH.');
  process.exit(1);
}

if (!cookieValue) {
  console.error('SESSION_COOKIE_VALUE manquant : relever le cookie de session de la navigateur connecté.');
  process.exit(1);
}

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

const tempDir = process.env.TEMP ?? process.env.TMPDIR ?? '/tmp';

const port = 9223 + (process.pid % 500);

const chrome = spawn(chromePath, [
  '--headless=new',
  `--remote-debugging-port=${port}`,
  '--no-first-run',
  '--no-default-browser-check',
  '--disable-gpu',
  '--user-data-dir=' + tempDir + '/chrome-responsive-audit',
  'about:blank',
], { stdio: 'ignore' });

process.on('exit', () => chrome.kill());

let wsUrl = null;

for (let attempt = 0; attempt < 60 && wsUrl === null; attempt++) {
  try {
    const targets = await (await fetch(`http://127.0.0.1:${port}/json/list`)).json();
    wsUrl = targets.find((target) => target.type === 'page')?.webSocketDebuggerUrl ?? null;
  } catch {
    await sleep(500);
  }
}

if (wsUrl === null) {
  console.error('Chrome injoignable par CDP.');
  process.exit(1);
}

const socket = new WebSocket(wsUrl);
let messageId = 0;
const pending = new Map();
const listeners = new Map();

socket.addEventListener('message', (event) => {
  const message = JSON.parse(event.data);

  if (message.id !== undefined && pending.has(message.id)) {
    const { resolve, reject } = pending.get(message.id);
    pending.delete(message.id);
    message.error ? reject(new Error(JSON.stringify(message.error))) : resolve(message.result);
    return;
  }

  (listeners.get(message.method) ?? []).forEach((handler) => handler(message.params));
});

const send = (method, params = {}) => new Promise((resolve, reject) => {
  const id = ++messageId;
  pending.set(id, { resolve, reject });
  socket.send(JSON.stringify({ id, method, params }));
});

const once = (method) => new Promise((resolve) => {
  const handler = (params) => {
    listeners.set(method, (listeners.get(method) ?? []).filter((item) => item !== handler));
    resolve(params);
  };

  listeners.set(method, [...(listeners.get(method) ?? []), handler]);
});

/**
 * Mesure la page : défilement du document et éléments qui dépassent la
 * fenêtre sans être contenus par un ancêtre défilant (tableau à
 * défilement horizontal : le défilement est alors voulu).
 */
const MEASURE = `
(() => {
  const doc = document.documentElement;
  // clientWidth : la largeur de mise en page, indépendante de la largeur
  // du contenu. window.innerWidth serait masqué en émulation mobile :
  // Chrome élargit alors le viewport visuel au lieu de signaler le
  // débordement.
  const vw = doc.clientWidth || window.innerWidth;

  const scrollable = (el) => {
    let node = el.parentElement;

    while (node && node !== doc) {
      const overflowX = getComputedStyle(node).overflowX;

      if (overflowX === 'auto' || overflowX === 'scroll' || overflowX === 'hidden') {
        return true;
      }

      node = node.parentElement;
    }

    return false;
  };

  const describe = (el) => {
    const id = el.id ? '#' + el.id : '';
    const classes = (el.className || '').toString().trim().split(/\\s+/).filter((c) => c).slice(0, 3).join('.');
    return el.tagName.toLowerCase() + id + (classes ? '.' + classes : '');
  };

  const offenders = [];

  for (const el of document.querySelectorAll('body *')) {
    const rect = el.getBoundingClientRect();

    if (rect.width === 0 || rect.height === 0) continue;

    const right = rect.right;

    if (right <= vw + 1 || scrollable(el)) continue;

    offenders.push({
      selector: describe(el),
      right: Math.round(right),
      width: Math.round(rect.width),
      text: (el.textContent || '').trim().replace(/\\s+/g, ' ').slice(0, 48),
    });
  }

  // Un parent débordant produit des enfants débordants : on ne garde que
  // les éléments les plus externes.
  const minimal = offenders.filter((candidate) => !offenders.some((other) =>
    other !== candidate
    && other.right >= candidate.right - 1
    && other.width >= candidate.width
    && other.text.startsWith(candidate.text)
  ));

  return {
    vw,
    overflow: Math.max(doc.scrollWidth, document.body.scrollWidth) - vw,
    offenders: minimal.slice(0, 8),
    path: location.pathname,
  };
})()
`;

await new Promise((resolve) => socket.addEventListener('open', resolve));

await send('Page.enable');
await send('Runtime.enable');
await send('Network.enable');

try {
  await send('Network.setCookie', {
    name: cookieName,
    value: cookieValue,
    domain: new URL(baseUrl).hostname,
    path: '/',
    httpOnly: true,
  });
} catch (error) {
  console.error(`Cookie non posé (${error.message}) : la mesure risque de se faire sur la page de connexion.`);
}

const report = [];
const errors = [];

for (const url of urls) {
  for (const width of widths) {
    await send('Emulation.setDeviceMetricsOverride', {
      width,
      height: 900,
      deviceScaleFactor: 1,
      mobile: width <= 768,
    });

    const loaded = once('Page.loadEventFired');

    try {
      await send('Page.navigate', { url: `${baseUrl}${url}` });
      await loaded;
    } catch (error) {
      console.log(`ERREUR  ${String(width).padStart(4)}px ${url} — ${error.message}`);
      errors.push(url);
      continue;
    }

    await sleep(400);

    const { result } = await send('Runtime.evaluate', { expression: MEASURE, returnByValue: true });
    const entry = { url, ...result.value };

    report.push(entry);

    if (entry.path !== url) {
      entry.authError = entry.path === '/login';
    }

    console.log(
      `${entry.overflow > 1 ? 'DÉBORDE' : 'ok    '} ${String(width).padStart(4)}px `
      + `${String(entry.overflow).padStart(5)}px  ${url}`
      + (entry.offenders.length ? `\n         ↳ ${entry.offenders.map((o) => `${o.selector} (${o.width}px, +${o.right - entry.vw})`).join('\n         ↳ ')}` : '')
      + (entry.authError ? '  [redirigé : session invalide]' : ''),
    );
  }
}

const failures = report.filter((entry) => entry.overflow > 1);
const authErrors = report.filter((entry) => entry.authError);

if (reportFile) {
  writeFileSync(reportFile, JSON.stringify(report, null, 2));
}

console.log(`\n${report.length} mesures — ${failures.length} dépassement(s)${authErrors.length ? `, ${authErrors.length} redirection(s) d'authentification` : ''}${errors.length ? `, ${errors.length} erreur(s) de navigation` : ''}`);

socket.close();
chrome.kill();
process.exit(failures.length > 0 || authErrors.length > 0 || errors.length > 0 ? 1 : 0);
