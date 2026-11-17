import {test,expect} from '@playwright/test';
const base=process.env.LAW_NATIVE_TEST_URL || 'https://127.0.0.1:8447';
const state=process.env.LAW_NATIVE_ADMIN_STATE || '.local/native-admin.json';
test.use({baseURL:base,ignoreHTTPSErrors:true,storageState:state,serviceWorkers:'block'});
// WoltLab fills CSRF fields asynchronously during its native bootstrap.
async function submit(formPage) {
 if (await formPage.locator('[name=lawText]').count()) {
  await expect(formPage.locator('.ck-editor__editable[contenteditable=true]')).toContainText(/\S/);
 }
 await expect(formPage.locator('form [name=t]').last()).not.toHaveValue('NOT_MODIFIED');
 await formPage.getByRole('button',{name:'Absenden',exact:true}).click();
}


async function setDate(page, field, value) {
 await expect(page.locator('#'+field)).toHaveClass(/inputDatePicker/);
 await page.evaluate(({field,value})=>{
  const [year,month,day]=value.split('-').map(Number);
  window.__wcf_bc_datePicker.setDate(field,new Date(year,month-1,day,12));
 },{field,value});
}

test('native WoltLab editor, numbering, stable links, category change, search, bookmarks and conflict protection',async({page,context,browser})=>{
 const failures=[];page.on('pageerror',e=>failures.push(e.message));
 const run=Date.now().toString(36);
 async function create(title,before='') {
  await page.goto('/acp/index.php?law-edit/&categoryId=public');
  await page.locator('[name=title]').fill(title);
  await page.locator('[name=abbreviation]').fill('TEST');
  if(before) await page.locator('[name=before_public]').selectOption(before);
  await page.locator('.ck-editor__editable[contenteditable=true]').fill('Beispieltext zur Prüfung der nativen WoltLab-Erweiterung.');
  await submit(page);
  await expect(page).toHaveURL(/acp\/index.php\?law-list/);
  const href=await page.locator('.table').getByRole('link',{name:title,exact:true}).getAttribute('href');
  return new URL(href,base).searchParams.get('lawKey');
 }
 async function number(id) {
  await page.goto(`/index.php?law/&lawKey=${id}`);
  return Number(await page.locator('dt').filter({hasText:/^Nummer$/}).locator('+ dd').innerText());
 }
 const first=await create(`Test A ${run}`);
 const firstNumber=await number(first);
 const second=await create(`Test B ${run}`);
 expect(await number(second)).toBe(firstNumber+1);
 const inserted=await create(`Test Mitte ${run}`,second);
 expect(await number(inserted)).toBe(firstNumber+1);
 expect(await number(second)).toBe(firstNumber+2);

 await page.getByRole('link',{name:'Bearbeiten',exact:true}).click();
 await expect(page.locator('[name=title]')).toHaveValue(`Test B ${run}`);
 await page.locator('[name=categoryId]').selectOption('civil');
 await expect(page.locator('[name=before_civil]')).toBeVisible();
 await expect(page.locator('[name=before_public]')).not.toBeVisible();
 await submit(page);
 await expect(page).toHaveURL(/acp\/index.php\?law-list/);
 expect(await number(second)).toBeGreaterThanOrEqual(201);
 expect(await number(second)).toBeLessThan(300);

 await page.getByRole('button',{name:'In Merkliste speichern',exact:true}).click();
 await expect(page.getByRole('button',{name:'Aus Merkliste entfernen',exact:true})).toBeVisible();
 await page.reload();
 await expect(page.getByRole('button',{name:'Aus Merkliste entfernen',exact:true})).toBeVisible();
 await page.goto('/index.php?law-library/&view=bookmarks');
 await expect(page.locator('.table tbody')).toContainText(`Test B ${run}`);
 await page.locator('#lawQuery').fill(`Test Mitte ${run}`);
 await page.getByRole('button',{name:'Suchen',exact:true}).click();
 await expect(page).toHaveURL(/law-library/);
 await expect(page.getByText('Keine passenden Gesetze vorhanden.',{exact:true})).toBeVisible();
 await page.goto('/index.php?law-library/');
 await page.locator('#lawQuery').fill(`Test Mitte ${run}`);
 await page.getByRole('button',{name:'Suchen',exact:true}).click();
 await expect(page.locator('.table tbody tr')).toHaveCount(1);
 await expect(page.locator('.table tbody')).toContainText(`Test Mitte ${run}`);

 const concurrent=await context.newPage();
 await page.goto(`/acp/index.php?law-edit/&lawKey=${inserted}`);
 await concurrent.goto(`/acp/index.php?law-edit/&lawKey=${inserted}`);
 await page.locator('[name=title]').fill(`Aktualisiert ${run}`);
 await submit(page);
 await expect(page).toHaveURL(/acp\/index.php\?law-list/);
 await concurrent.locator('[name=title]').fill(`Mein ungespeicherter Titel ${run}`);
 await submit(concurrent);
 await expect(concurrent.locator('.innerError')).toContainText('zwischenzeitlich geändert');
 await expect(concurrent.locator('[name=title]')).toHaveValue(`Mein ungespeicherter Titel ${run}`);
 await concurrent.close();
 await page.goto(`/index.php?law/&lawKey=${inserted}`);
 await expect(page.locator('h1')).toHaveText(`Aktualisiert ${run}`);

 const guest=await browser.newContext({baseURL:base,ignoreHTTPSErrors:true,storageState:{cookies:[],origins:[]}});
 const publicPage=await guest.newPage();
 await publicPage.goto('/index.php?law-library/');
 await expect(publicPage.locator('.table')).toBeVisible();
 await expect(publicPage.getByRole('link',{name:'Gesetz hinzufügen',exact:true})).toHaveCount(0);
 const forbidden=await publicPage.goto(`/index.php?law-edit/&lawKey=${first}`);
 expect(forbidden.status()).toBe(403);
 await guest.close();
 expect(failures).toEqual([]);
});


test('ACP category management renumbers stable laws and rejects collisions, conflicts, unsafe deletion and CSRF',async({page,context,browser})=>{
 const failures=[];page.on('pageerror',e=>failures.push(e.message));
 const run=Date.now().toString(36);
 const title=`Rechtsgebiet ${run}`;
 await page.goto('/acp/index.php?law-category-list/');
 await expect(page.locator('h1')).toHaveText('Gebietssystematik');
 await page.getByRole('link',{name:'Rechtsgebiet hinzufügen',exact:true}).click();
 await page.locator('[name=title]').fill(title);
 await page.locator('[name=description]').fill('Beschreibung für die öffentliche Bibliothek.');
 await page.locator('[name=numberBlock]').fill('50');
 await page.locator('[name=beforeId]').selectOption('public');
 await submit(page);
 await expect(page).toHaveURL(/law-category-list/);
 const row=page.locator('.table tbody tr').filter({hasText:title});
 await expect(row).toContainText('5001–5099');
 await expect(page.locator('.table tbody tr').first()).toContainText(title);
 const href=await row.getByRole('link',{name:title,exact:true}).getAttribute('href');
 const category=new URL(href,base).searchParams.get('categoryId');

 await page.goto(`/acp/index.php?law-edit/&categoryId=${category}`);
 await page.locator('[name=title]').fill(`Gesetz ${run}`);
 await page.locator('.ck-editor__editable[contenteditable=true]').fill('Gesetz im selbst angelegten Rechtsgebiet.');
 await submit(page);
 await expect(page).toHaveURL(/law-list/);
 const lawHref=await page.locator('.table').getByRole('link',{name:`Gesetz ${run}`,exact:true}).getAttribute('href');
 const lawKey=new URL(lawHref,base).searchParams.get('lawKey');
 const publicLaw=`/index.php?law/&lawKey=${lawKey}`;
 await page.goto(publicLaw);
 await expect(page.locator('dt').filter({hasText:/^Nummer$/}).locator('+ dd')).toHaveText('5001');
 await page.goto(`/index.php?law-library/&categoryId=${category}`);
 await expect(page.locator('.contentHeaderDescription')).toHaveText('Beschreibung für die öffentliche Bibliothek.');

 await page.goto(href);
 await page.locator('[name=title]').fill(`Staatsverträge ${run}`);
 await page.locator('[name=numberBlock]').fill('51');
 await page.locator('[name=beforeId]').selectOption('civil');
 await submit(page);
 await expect(page).toHaveURL(/law-category-list/);
 await expect(page.locator('.table')).toContainText(`Staatsverträge ${run}`);
 const names=await page.locator('.table tbody tr').allTextContents();
 const pos=names.findIndex(n=>n.includes(`Staatsverträge ${run}`));
 expect(pos).toBeGreaterThan(names.findIndex(n=>n.includes('Öffentliches Recht')));
 expect(pos).toBeLessThan(names.findIndex(n=>n.includes('Privatrecht')));
 await page.goto(publicLaw);
 await expect(page.locator('dt').filter({hasText:/^Nummer$/}).locator('+ dd')).toHaveText('5101');
 await expect(page.locator('h1')).toHaveText(`Gesetz ${run}`);
 await expect(page.locator('.contentHeaderDescription')).toContainText(`Staatsverträge ${run}`);

 await page.goto(href);
 await page.locator('[name=numberBlock]').fill('1');
 await submit(page);
 await expect(page.locator('.innerError')).toContainText('Nummernkreis ist bereits vergeben');
 await expect(page.locator('[name=title]')).toHaveValue(`Staatsverträge ${run}`);
 await page.goto(href);
 await page.locator('label.green[for=deleteCategory]').click();
 await submit(page);
 await expect(page.locator('.innerError')).toContainText('enthält noch Gesetze');
 await page.goto(publicLaw);
 await expect(page.locator('h1')).toHaveText(`Gesetz ${run}`);

 await page.goto(href);
 const stale=await context.newPage();await stale.goto(href);
 await page.locator('[name=description]').fill('Aktuelle Beschreibung');
 await submit(page);
 await expect(page).toHaveURL(/law-category-list/);
 await stale.locator('[name=description]').fill('Ungespeicherte Beschreibung');
 await submit(stale);
 await expect(stale.locator('.innerError')).toContainText('zwischenzeitlich geändert');
 await expect(stale.locator('[name=description]')).toHaveValue('Ungespeicherte Beschreibung');
 await stale.close();

 await page.goto(lawHref);
 await page.locator('[name=categoryId]').selectOption('public');
 await submit(page);
 await expect(page).toHaveURL(/law-list/);
 await page.goto(href);
 await page.locator('label.green[for=deleteCategory]').click();
 await submit(page);
 await expect(page).toHaveURL(/law-category-list/);
 await expect(page.locator('.table')).not.toContainText(`Staatsverträge ${run}`);
 await page.goto(publicLaw);
 await expect(page.locator('h1')).toHaveText(`Gesetz ${run}`);

 await page.goto('/acp/index.php?law-category-edit/');
 const revision=await page.locator('[name=revision]').inputValue();
 const response=await context.request.post('/acp/index.php?law-category-edit/',{form:{title:`CSRF ${run}`,description:'',numberBlock:'52',beforeId:'',revision}});
 expect(response.status()).toBeLessThan(500);
 await page.goto('/acp/index.php?law-category-list/');
 await expect(page.locator('.table')).not.toContainText(`CSRF ${run}`);
 const guest=await browser.newContext({baseURL:base,ignoreHTTPSErrors:true,storageState:{cookies:[],origins:[]}});
 const guestPage=await guest.newPage();
 await guestPage.goto('/acp/index.php?law-category-edit/');
 await expect(guestPage.locator('[name=numberBlock]')).toHaveCount(0);
 await guest.close();
 expect(failures).toEqual([]);
});

test('manual amendment dates, linked history, corrections and concurrent history edits', async({page,context})=>{
 const run=Date.now().toString(36);
 const failures=[];page.on('pageerror',e=>failures.push(e.message));
 async function create(title) {
  await page.goto('/acp/index.php?law-edit/&categoryId=public');
  await page.locator('[name=title]').fill(title);
  await setDate(page,'updated','2025-01-01');
  await page.locator('.ck-editor__editable[contenteditable=true]').fill('§ 1 Unveränderter Gesetzestext mit Ä, Ö und Ü.');
  await submit(page);
  await expect(page).toHaveURL(/law-list/);
  return new URL(await page.locator('.table').getByRole('link',{name:title,exact:true}).getAttribute('href'),base).searchParams.get('lawKey');
 }
 const source=await create(`Änderungsgesetz ${run}`);
 const target=await create(`Stammgesetz ${run}`);
 const edit=`/acp/index.php?law-edit/&lawKey=${target}`;
 const publicLaw=`/index.php?law/&lawKey=${target}`;
 await page.goto(edit);
 await setDate(page,'updated','2025-07-01');
 await page.locator('[name=amendingLawKey]').selectOption(source);
 await submit(page);
 await expect(page).toHaveURL(/law-list/);
 await page.goto(publicLaw);
 await expect(page.locator('dt').filter({hasText:/^Zuletzt geändert$/}).locator('+ dd')).toContainText('01.07.2025');
 await expect(page.locator('dt').filter({hasText:/^Zuletzt geändert$/}).locator('+ dd a')).toHaveAttribute('href',new RegExp(source));
 await expect(page.locator('#amendmentHistory tbody tr')).toHaveCount(1);
 await page.locator('#amendmentHistory a').filter({hasText:`Änderungsgesetz ${run}`}).click();
 await expect(page.locator('h1')).toHaveText(`Änderungsgesetz ${run}`);
 await page.goto(edit);
 await submit(page);
 await expect(page).toHaveURL(/law-list/);
 await page.goto(edit);
 await expect(page.locator('[name=updated]')).toHaveValue('2025-07-01');
 await expect(page.locator('#amendmentHistory tbody tr')).toHaveCount(1);
 await page.getByRole('link',{name:'Änderung hinzufügen',exact:true}).click();
 await setDate(page,'date','2024-02-29');
 await page.locator('[name=amendingLawKey]').selectOption(source);
 await submit(page);
 await expect(page).toHaveURL(/law-edit/);
 await expect(page.locator('#amendmentHistory tbody tr')).toHaveCount(2);
 await expect(page.locator('[name=updated]')).toHaveValue('2025-07-01');
 await page.locator('#amendmentHistory tbody tr').last().getByRole('link',{name:'Änderung bearbeiten',exact:true}).click();
 const amendEdit=page.url();
 const stale=await context.newPage();await stale.goto(amendEdit);
 await setDate(page,'date','2024-03-01');
 await submit(page);await expect(page).toHaveURL(/law-edit/);
 await setDate(stale,'date','2024-04-01');
 await submit(stale);
 await expect(stale.locator('.innerError')).toContainText('zwischenzeitlich geändert');
 await expect(stale.locator('[name=date]')).toHaveValue('2024-04-01');
 await stale.close();
 await setDate(page,'updated','2023-01-01');
 await submit(page);
 await expect(page.locator('.innerError')).toContainText('vor einem Eintrag');
 await page.goto(amendEdit);
 await page.locator('label.green[for=deleteAmendment]').click();
 await submit(page);await expect(page).toHaveURL(/law-edit/);
 await expect(page.locator('#amendmentHistory tbody tr')).toHaveCount(1);
 // A metadata-only history edit never sends the rich text through the editor.
 await page.goto(publicLaw);
 await expect(page.locator('article')).toContainText('§ 1 Unveränderter Gesetzestext mit Ä, Ö und Ü.');
 const guest=await context.browser().newContext({baseURL:base,ignoreHTTPSErrors:true,storageState:{cookies:[],origins:[]}});
 const guestPage=await guest.newPage();await guestPage.goto(publicLaw);
 await expect(guestPage.locator('#amendmentHistory tbody tr')).toHaveCount(1);
 await expect(guestPage.getByRole('link',{name:'Änderung bearbeiten',exact:true})).toHaveCount(0);
 await guestPage.goto(amendEdit);
 await expect(guestPage.locator('[name=amendingLawKey]')).toHaveCount(0);
 await guest.close();
 expect(failures).toEqual([]);
});

test('configurable library name updates headings and menus and escapes markup', async({page})=>{
 await page.goto('/acp/index.php?index/');
 const options=page.locator('a[href*="option/"]').filter({hasText:/^Gesetzesbibliothek$/});
 const url=await options.first().getAttribute('href');
 await page.goto(url);
 const field=page.locator('[name="values[law_library_name]"]');
 const original=await field.inputValue();
 async function saveName(name) {
  await page.goto(url);
  await field.fill(name);
  await expect(page.locator('form [name=t]').last()).not.toHaveValue('NOT_MODIFIED');
  await page.locator('[name=__submit]').click();
  await expect(page.locator('.success')).toBeVisible();
 }
 try {
  for (const name of ['EulLex', 'EulLex & <img src=x onerror=alert(1)>']) {
   await saveName(name);
   await page.goto('/index.php?law-library/');
   await expect(page.locator('h1')).toHaveText(name);
   await expect(page).toHaveTitle(new RegExp(name.replace(/[.*+?^${}()|[\]\\]/g,'\\$&')));
   await expect(page.locator('[data-identifier="de.eulenthal.wcf.lawLibrary.Library"] .boxMenuLinkTitle')).toHaveText(name);
   await expect(page.locator('h1 img')).toHaveCount(0);
   await page.goto('/acp/index.php?law-list/');
   await expect(page.locator('.acpPageSubMenuCategoryList.active')).toContainText(name);
  }
 } finally {
  await saveName(original);
 }
 await page.goto('/index.php?law-library/');
 await expect(page.locator('h1')).toHaveText(original || 'Gesetzesbibliothek');
});
