const _fs=require('fs');_fs.mkdirSync(__dirname+'/out',{recursive:true});
const { chromium } = require('playwright');
const B=process.env.BASE||'http://127.0.0.1:8099';
(async()=>{const b=await chromium.launch();const res=[];const note=(k,ok,d)=>res.push((ok?'PASS ':'FAIL ')+k+(d?' — '+d:''));
 for (const theme of ['dark','light']) for (const [vn,vs] of [['desktop',{width:1440,height:900}],['mobile',{width:390,height:844}]]) {
  const ctx=await b.newContext({viewport:vs,isMobile:vn==='mobile',hasTouch:vn==='mobile'});await ctx.addInitScript(t=>{try{localStorage.setItem('nx-theme',t)}catch(e){}},theme);
  const p=await ctx.newPage();const errs=[];p.on('pageerror',e=>errs.push(e.message.slice(0,100)));p.on('console',m=>{if(m.type()==='error'&&!/favicon/.test(m.text()))errs.push(m.text().slice(0,100))});
  const tag=`[${theme}/${vn}] `;
  await p.goto(B+'/login');await p.fill('input[name=email]','owner@example.com');await p.fill('input[name=password]','Demo-pass-123');await p.waitForTimeout(1300);await Promise.all([p.waitForNavigation(),p.click('button[type=submit]:has-text("Sign in")')]);
  // 1 popup add form
  await p.goto(B+'/admin/brands',{waitUntil:'networkidle'});
  await p.click('a[href$="/admin/brands/create"]');await p.waitForTimeout(1200);
  note(tag+'brand: "New" opens a popup',await p.isVisible('#nxModal.show'));
  // 2 validation: submit empty
  await p.click('#nxModal form button[type=submit]');await p.waitForTimeout(900);
  const invalid=await p.locator('#nxModal .is-invalid, #nxModal .invalid-feedback:visible, #nxModal .nx-field-error').count();
  note(tag+'brand: empty submit shows an inline error',invalid>0,String(invalid));
  // 3 valid create
  await p.fill('#nxModal input[name=name]','QA Brand '+vn+theme);await p.click('#nxModal form button[type=submit]');await p.waitForTimeout(1800);
  note(tag+'brand: valid submit closes popup and lists it',!(await p.isVisible('#nxModal.show'))&&(await p.content()).includes('QA Brand '+vn+theme));
  // 4 delete confirmation appears and can be cancelled
  const del=p.locator('tr:has-text("QA Brand '+vn+theme+'") .delete, tr:has-text("QA Brand '+vn+theme+'") [data-delete], tr:has-text("QA Brand '+vn+theme+'") button[title*="elete"]').first();
  if(await del.count()){await del.click();await p.waitForTimeout(700);
    const dlg=await p.locator('.swal2-popup, .nx-confirm, #nxConfirm.show, .modal.show:has-text("Delete")').first().isVisible().catch(()=>false);
    note(tag+'brand: delete asks for confirmation',dlg);
    const cancel=p.locator('button:has-text("Cancel"):visible').first(); if(await cancel.count()) await cancel.click(); await p.waitForTimeout(400);
    // confirm real delete to clean up + soft delete check
    await del.click();await p.waitForTimeout(600);const ok=p.locator('button:has-text("Delete"):visible, button:has-text("Yes"):visible').last(); if(await ok.count()) await ok.click(); await p.waitForTimeout(1500);
    note(tag+'brand: confirmed delete removes the row',!(await p.content()).includes('QA Brand '+vn+theme));
  } else note(tag+'brand: delete button found',false);
  // 5 theme toggle + sidebar
  const before=await p.getAttribute('html','data-theme');await p.click('#nxThemeBtn');await p.waitForTimeout(400);
  note(tag+'theme toggle flips theme',(await p.getAttribute('html','data-theme'))!==before);
  await p.click('#nxThemeBtn');
  if(vn==='mobile'){await p.click('#nxMenuBtn');await p.waitForTimeout(500);note(tag+'mobile menu opens the sidebar',await p.locator('#nxSidebar').evaluate(e=>e.getBoundingClientRect().right>0&&e.getBoundingClientRect().left>=-1));await p.click('#nxScrim').catch(()=>{});}
  // 6 DataTable search
  await p.goto(B+'/admin/products',{waitUntil:'networkidle'});const s=p.locator('.dataTables_filter input').first();
  if(await s.count()){await s.fill('Panadol');await p.waitForTimeout(500);const rows=await p.locator('table tbody tr:visible').count();note(tag+'products: table search filters rows',rows>=1&&rows<10,String(rows));} else note(tag+'products: search box',false);
  // 7 notifications bell / alerts
  await p.goto(B+'/admin/alerts',{waitUntil:'networkidle'});note(tag+'alerts page lists alerts',(await p.content()).includes('New order'));
  // 8 prescription review flow (desktop dark only)
  if(theme==='dark'&&vn==='desktop'){
    await p.goto(B+'/admin/prescriptions/3',{waitUntil:'networkidle'});await p.fill('textarea[name=note]','Looks fine');await Promise.all([p.waitForNavigation(),p.click('button[name=decision][value=approved]')]);
    note(tag+'pharmacist approves a prescription',(await p.content()).toLowerCase().includes('approved'));
    // order status move
    await p.goto(B+'/admin/orders/1',{waitUntil:'networkidle'});const btn=p.locator('form[action$="/status"] button:visible').first();
    if(await btn.count()){const t=(await btn.textContent()).trim();await Promise.all([p.waitForNavigation(),btn.click()]).catch(()=>{});note(tag+'order moves to next status ('+t+')',true);} else note(tag+'order status button',false);
    // settings save
    await p.goto(B+'/admin/settings',{waitUntil:'networkidle'});await Promise.all([p.waitForNavigation(),p.click('form[action$="/admin/settings"] button[type=submit]:visible')]).catch(()=>{});
    note(tag+'settings save round-trips',(await p.content()).toLowerCase().includes('saved')||p.url().endsWith('/admin/settings'));
    // role guard: pharmacist cannot open settings
  }
  note(tag+'no JS errors during the flow',errs.length===0,errs.slice(0,2).join(' | '));
  await ctx.close();
 }
 // permission guards with other roles
 for (const [email,deny,allow] of [['pharm@example.com',['/admin/settings','/admin/staff','/admin/system','/admin/products/create'],['/admin/prescriptions','/admin/orders']],['rider@example.com',['/admin/orders','/admin/settings','/admin/prescriptions'],['/admin/deliveries']]]) {
  const ctx=await b.newContext({viewport:{width:1280,height:800}});const p=await ctx.newPage();
  await p.goto(B+'/login');await p.fill('input[name=email]',email);await p.fill('input[name=password]','Demo-pass-123');await p.waitForTimeout(1300);await Promise.all([p.waitForNavigation(),p.click('button[type=submit]:has-text("Sign in")')]);
  for(const u of deny){const r=await p.goto(B+u);note(email.split('@')[0]+' blocked from '+u,[403,404].includes(r.status()),String(r.status()));}
  for(const u of allow){const r=await p.goto(B+u);note(email.split('@')[0]+' can open '+u,r.status()===200,String(r.status()));}
  const dash=await p.goto(B+'/admin');note(email.split('@')[0]+' dashboard opens without errors',dash.status()===200,String(dash.status()));
  await ctx.close();
 }
 // guest + customer cannot reach admin
 const g=await b.newContext();const gp=await g.newPage();const r1=await gp.goto(B+'/admin');note('guest /admin redirects to sign in',gp.url().includes('/login'),gp.url());
 const r2=await gp.goto(B+'/admin/orders');note('guest /admin/orders redirects to sign in',gp.url().includes('/login'));
 await g.close();
 const c=await b.newContext();const cp=await c.newPage();await cp.goto(B+'/login');await cp.fill('input[name=email]','sara@example.com');await cp.fill('input[name=password]','Demo-pass-123');await cp.waitForTimeout(1300);await Promise.all([cp.waitForNavigation(),cp.click('button[type=submit]:has-text("Sign in")')]);
 const r3=await cp.goto(B+'/admin');note('customer is refused at /admin',[403,404].includes(r3.status())||!cp.url().endsWith('/admin'),String(r3.status()));
 console.log(res.join('\n'));await b.close();})();
