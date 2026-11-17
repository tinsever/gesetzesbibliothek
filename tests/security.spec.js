import {test,expect} from '@playwright/test';
const password=process.env.LAW_SECURITY_PASSWORD;
test.skip(!password,'Requires dedicated local security fixtures; see the security audit report.');
async function ready(page) {
 await expect(page.locator('form [name=t]').last()).not.toHaveValue('NOT_MODIFIED');
}
async function login(page,user,acp=false) {
 await page.goto(`${acp?'/acp':''}/index.php?login/`);
 await page.locator('[name=username]').fill(user);
 await page.locator('[name=password]').fill(password);
 await ready(page);
 await page.locator('form [type=submit]').click();
 await expect(page).not.toHaveURL(/\?login\//);
}

test('Reader cannot edit, spoof bookmark ownership, or mutate bookmarks without CSRF',async({page,context})=>{
 await login(page,'AuditReader');
 await page.goto('/index.php?law/&lawKey=beispiel-1');
 await expect(page.locator('h1')).toContainText('Verfassung');
 await expect(page.getByRole('link',{name:'Bearbeiten',exact:true})).toHaveCount(0);
 for (const url of ['/index.php?law-edit/&lawKey=beispiel-1','/acp/index.php?law-edit/&lawKey=beispiel-1','/acp/index.php?law-category-edit/']) {
  const r=await context.request.get(url);
  expect(await r.text()).not.toContain('name="revision"');
 }
 await ready(page);
 const token=await page.locator('form [name=t]').last().inputValue();
 const invalid=await context.request.post('/index.php?law-bookmark/',{form:{lawKey:'beispiel-1',saved:'1'}});
 expect(invalid.status()).toBe(403);
 await page.reload();
 await expect(page.getByRole('button',{name:'In Merkliste speichern',exact:true})).toBeVisible();
 await context.request.post('/index.php?law-bookmark/',{form:{lawKey:'beispiel-1',saved:'1',userID:'1',t:token}});
 await page.reload();
 await expect(page.getByRole('button',{name:'Aus Merkliste entfernen',exact:true})).toBeVisible();
 const admin=await context.browser().newContext({baseURL:'https://127.0.0.1:8447',ignoreHTTPSErrors:true,storageState:'.local/native-admin.json'});
 const p=await admin.newPage();await p.goto('/index.php?law/&lawKey=beispiel-1');
 await expect(p.getByRole('button',{name:'In Merkliste speichern',exact:true})).toBeVisible();
 await admin.close();
});

test('ACP access alone does not authorize law management, including direct POST requests',async({page,context})=>{
 await login(page,'AuditAcpLimited',true);
 for (const url of ['/acp/index.php?law-list/','/acp/index.php?law-category-list/','/acp/index.php?law-edit/&lawKey=beispiel-1','/acp/index.php?law-category-edit/']) {
  const get=await context.request.get(url);expect(get.status()).toBe(403);
  const post=await context.request.post(url,{form:{title:'Unauthorized',revision:'0',categoryId:'public',numberBlock:'70'}});
  expect(post.status()).toBe(403);
 }
});

test('A logged-in account without read permission cannot see library, direct law, search or bookmarks',async({page,context})=>{
 await login(page,'AuditBlocked');
 for (const url of ['/index.php?law-library/','/index.php?law/&lawKey=beispiel-1','/index.php?law-library/&query=Verfassung','/index.php?law-library/&view=bookmarks']) {
  const r=await context.request.get(url);expect(r.status()).toBe(403);
  expect(await r.text()).not.toContain('Fiktiver Beispieltext');
 }
 const r=await context.request.post('/index.php?law-bookmark/',{form:{lawKey:'beispiel-1',saved:'1'}});
 expect(r.status()).toBe(403);
});

test('Server rejects bad input, stale writes, missing CSRF and sanitizes crafted rich text',async({browser})=>{
 const context=await browser.newContext({baseURL:'https://127.0.0.1:8447',ignoreHTTPSErrors:true,storageState:'.local/native-admin.json'});
 const page=await context.newPage();
 await page.goto('/acp/index.php?law-edit/&lawKey=beispiel-1');await ready(page);
 const form=await page.locator('form').evaluate(e=>Object.fromEntries(new FormData(e)));
 for (const override of [{categoryId:'missing'}, {status:'forged'}, {date:'2026-02-30'}, {before_public:'missing'}, {revision:'0'}, {t:''}, {'t[]':'bad'}, {'lawText[]':'bad'}, {'date[]':'bad'}, {date:'2026-09-09\u0000'}]) {
  const r=await context.request.post(page.url(),{form:{...form,...override,title:'MUST NOT SAVE'}});
  expect(r.status()).toBeLessThan(500);
  const law=await context.request.get('/index.php?law/&lawKey=beispiel-1');
  expect((await law.text()).includes('MUST NOT SAVE'),JSON.stringify(override)).toBe(false);
 }
 const withoutRevision={...form,title:'MUST NOT SAVE'};delete withoutRevision.revision;
 const missing=await context.request.post(page.url(),{form:withoutRevision});
 expect(missing.status()).toBeGreaterThanOrEqual(400);expect(missing.status()).toBeLessThan(500);
 const xssTitle='<img src=x onerror="window.__lawXss=1">';
 const payload='<p>Security check</p><script>window.__lawXss=1</script><img src="x" onerror="window.__lawXss=1"><a href="javascript:window.__lawXss=1">Link</a><svg onload="window.__lawXss=1"></svg>';
 const response=await context.request.post(page.url(),{form:{...form,title:xssTitle,lawText:payload}});
 expect(response.status()).toBe(200);
 await page.goto('/index.php?law/&lawKey=beispiel-1');
 await expect(page.locator('h1')).toHaveText(xssTitle);
 await expect(page.locator('article')).toContainText('Security check');
 await expect(page.locator('article script,article [onerror],article [onload],article a[href^="javascript:"]')).toHaveCount(0);
 expect(await page.evaluate(()=>window.__lawXss)).toBeUndefined();
 await context.close();
});
