const _fs=require('fs');_fs.mkdirSync(__dirname+'/out',{recursive:true});
const { chromium } = require('playwright'); const fs=require('fs');
const B=process.env.BASE||'http://127.0.0.1:8099'; const ID=JSON.parse(fs.readFileSync(''+__dirname+'/ids.json','utf8'));
const pages=[['dashboard','/admin'],['activity','/admin/activity'],['alerts','/admin/alerts'],['articles','/admin/articles'],['article-new','/admin/articles/create'],['article-edit',`/admin/articles/${ID.article}/edit`],
['attributes','/admin/attributes'],['attribute-new','/admin/attributes/create'],['attribute-edit',`/admin/attributes/${ID.attr}/edit`],['backups','/admin/backups'],
['brands','/admin/brands'],['brand-new','/admin/brands/create'],['brand-edit',`/admin/brands/${ID.brand}/edit`],['categories','/admin/categories'],['category-new','/admin/categories/create'],['category-edit',`/admin/categories/${ID.category}/edit`],
['coupons','/admin/coupons'],['coupon-new','/admin/coupons/create'],['coupon-edit',`/admin/coupons/${ID.coupon}/edit`],['customers','/admin/customers'],['customer-new','/admin/customers/create'],['customer-edit',`/admin/customers/${ID.customer}/edit`],
['deliveries','/admin/deliveries'],['slots','/admin/delivery-slots'],['slot-new','/admin/delivery-slots/create'],['slot-edit',`/admin/delivery-slots/${ID.slot}/edit`],['emails','/admin/emails'],
['home-sections','/admin/home-sections'],['section-new','/admin/home-sections/create?type=reviews'],['section-edit',`/admin/home-sections/${ID.section}/edit`],['inventory','/admin/inventory'],['inventory-adjust','/admin/inventory/adjust'],['license','/admin/license'],
['menu-items','/admin/menu-items'],['menu-new','/admin/menu-items/create'],['menu-edit',`/admin/menu-items/${ID.menu}/edit`],['messages','/admin/messages'],['orders','/admin/orders'],['order',`/admin/orders/${ID.order}`],['order-rx',`/admin/orders/${ID.rx_order}`],
['pages','/admin/pages'],['page-new','/admin/pages/create'],['page-edit',`/admin/pages/${ID.page}/edit`],['rx-requests','/admin/prescription-requests'],['prescriptions','/admin/prescriptions'],['prescription',`/admin/prescriptions/${ID.rx_order}`],
['products','/admin/products'],['product-new','/admin/products/create'],['product-edit',`/admin/products/${ID.product}/edit`],['reports','/admin/reports'],
...['sales','products','customers','inventory','finance','operations','marketing'].map(t=>['report-'+t,'/admin/reports/'+t]),
['reviews','/admin/reviews'],['review-edit',`/admin/reviews/${ID.review}/edit`],['roles','/admin/roles'],['role-new','/admin/roles/create'],['role-edit',`/admin/roles/${ID.role}/edit`],['security','/admin/security'],['settings','/admin/settings'],
['shipping','/admin/shipping'],['shipping-new','/admin/shipping/create'],['shipping-edit',`/admin/shipping/${ID.shipping}/edit`],['staff','/admin/staff'],['staff-new','/admin/staff/create'],['staff-edit',`/admin/staff/${ID.staff}/edit`],
['storefront','/admin/storefront'],['system','/admin/system'],['texts','/admin/texts'],['theme','/admin/theme'],['trash','/admin/trash'],['log-viewer','/admin/log-viewer']];
const only=parseInt(process.argv[2]);
(async()=>{
 const b=await chromium.launch(); const out=[]; const axeSrc=fs.readFileSync(require.resolve('axe-core/axe.min.js'),'utf8');
 const configs=[]; for (const theme of ['dark','light']) for (const vp of [['desktop',{width:1440,height:900}],['tablet',{width:820,height:1100}],['mobile',{width:390,height:844}]]) configs.push({theme,vp});
 const {theme,vp}=configs[only]; const [vn,vs]=vp;
 const ctx=await b.newContext({viewport:vs,isMobile:vn==='mobile',hasTouch:vn!=='desktop',bypassCSP:true});
 await ctx.addInitScript(t=>{try{localStorage.setItem('nx-theme',t)}catch(e){}},theme);
 const p=await ctx.newPage();
 await p.goto(B+'/login',{waitUntil:'networkidle'}); await p.fill('input[name=email]','owner@example.com'); await p.fill('input[name=password]','Demo-pass-123'); await p.waitForTimeout(1300);
 await Promise.all([p.waitForNavigation(),p.click('button[type=submit]:has-text("Sign in")')]);
 const errs=[],fails=[];
 p.on('pageerror',e=>errs.push('pageerror: '+e.message.slice(0,140)));p.on('console',m=>{if(m.type()==='error'&&!/favicon/.test(m.text()))errs.push('console: '+m.text().slice(0,140))});
 p.on('response',r=>{const u=r.url();if(u.startsWith(B)&&r.status()>=400)fails.push(r.status()+' '+u.replace(B,'').slice(0,80))});
 p.on('requestfailed',r=>{if(r.url().startsWith(B))fails.push('failed '+r.url().replace(B,'').slice(0,80))});
 for (const [name,url] of pages) {
  errs.length=0;fails.length=0; let st=0;
  try{const r=await p.goto(B+url,{waitUntil:'networkidle',timeout:40000});st=r.status();}catch(e){out.push({name,theme,vn,error:'goto '+e.message.slice(0,100)});continue;}
  await p.evaluate(t=>{document.documentElement.setAttribute('data-theme',t);document.documentElement.setAttribute('data-bs-theme',t);},theme);
  await p.waitForTimeout(700);
  const m=await p.evaluate(()=>{const w=document.documentElement.clientWidth;
   const overflow=[...document.querySelectorAll('body *')].filter(e=>{const r=e.getBoundingClientRect();const cs=getComputedStyle(e);return r.width>0&&r.right>w+1&&cs.position!=='fixed'&&cs.visibility!=='hidden'&&!e.closest('.dataTables_wrapper,.table-responsive,.nx-sidebar,.modal,[hidden],.nx-toasts,.dropdown-menu,.dtr-details,.select2-container,.nx-scrim,.nx-tabs,.nav-tabs,.nav-pills,#nxTopbar')}).slice(0,3).map(e=>e.tagName.toLowerCase()+'.'+String(e.className).split(' ')[0]);
   const pageScroll=document.documentElement.scrollWidth-w;
   return {h1:document.querySelectorAll('h1,.nx-page-title').length,overflow,pageScroll,title:document.title,imgs:[...document.images].filter(i=>i.complete&&i.naturalWidth===0&&i.src).length};});
  let axe=[]; try{await p.evaluate(axeSrc);axe=await p.evaluate(async()=>{const r=await axe.run(document,{runOnly:['wcag2a','wcag2aa','best-practice'],rules:{'region':{enabled:false},'landmark-one-main':{enabled:false},'page-has-heading-one':{enabled:false}}});return r.violations.map(v=>({id:v.id,impact:v.impact,n:v.nodes.length,ex:v.nodes[0].target.join(' ').slice(0,70),msg:((v.nodes[0].any[0]||v.nodes[0].all[0]||v.nodes[0].none[0]||{}).message||'').slice(0,90)}));});}catch(e){axe=[{id:'axe-failed',impact:'x',n:0,ex:e.message.slice(0,50)}];}
  out.push({name,theme,vn,status:st,errs:[...new Set(errs)].slice(0,3),fails:[...new Set(fails)].slice(0,3),...m,axe});
 }
 fs.writeFileSync(__dirname+'/out/admin-'+only+'.json',JSON.stringify(out,null,1));console.error('written',out.length);
 await b.close();})();
