const _fs=require('fs');_fs.mkdirSync(__dirname+'/out',{recursive:true});
const { chromium } = require('playwright'); const fs=require('fs');
const B=process.env.BASE||'http://127.0.0.1:8099';
const ORDER=process.argv[2], GORDER=process.argv[3];
const pages=[
 ['home','/'],['shop','/shop'],['shop-search','/shop?q=paracetamol'],['shop-empty','/shop?q=zzzzqqq'],['shop-deals','/shop?sort=discount'],['shop-filter','/shop?in_stock=1&price_max=500'],
 ['category','/category/pain-fever'],['product','/product/panadol-500mg-20-tablets'],['product-variable','/product/brufen-400mg'],['product-rx','/product/amoxicillin-500mg-12-capsules'],['quick','/product/panadol-500mg-20-tablets/quick'],
 ['blog','/blog'],['blog-tag','/blog?tag=Wellness'],['blog-empty','/blog?q=zzzz'],['article','/blog/staying-hydrated-in-the-heat'],['contact','/contact'],['upload-rx','/upload-prescription'],['cms','/page/about-us'],['track','/track'],['cart-empty','/cart'],
 ['login','/login'],['register','/register'],['forgot','/forgot-password'],['order-guest',GORDER],['invoice-guest',GORDER+'/invoice'],['notfound','/nope-404'],
];
const authPages=[['account','/account'],['order-mine',ORDER],['checkout','/checkout']];
const cartPages=[['cart','/cart'],['checkout-guest','/checkout']];
(async()=>{
 const b=await chromium.launch(); const out=[]; const axeSrc=fs.readFileSync(require.resolve('axe-core/axe.min.js'),'utf8');
 const configs=[];
 for (const theme of ['dark','light']) for (const vp of [['desktop',{width:1440,height:900}],['tablet',{width:820,height:1100}],['mobile',{width:390,height:844}]]) configs.push({theme,vp});
 const only=parseInt(process.argv[4]);
 for (const {theme,vp} of configs.filter((_,i)=>only===i||isNaN(only))) {
  const [vn,vs]=vp;
  const mk=async()=>{const ctx=await b.newContext({viewport:vs,isMobile:vn==='mobile',hasTouch:vn!=='desktop',deviceScaleFactor:1,bypassCSP:true});await ctx.addInitScript(t=>{try{localStorage.setItem('nx-theme',t);localStorage.setItem('fx-cookie','essential')}catch(e){}},theme);return ctx;};
  const run=async(ctx,list,label)=>{
   const p=await ctx.newPage(); const errs=[],fails=[];
   p.on('pageerror',e=>errs.push('pageerror: '+e.message.slice(0,140)));p.on('console',m=>{if(m.type()==='error'&&!/favicon|Failed to load resource.*(404|403)/.test(m.text()))errs.push('console: '+m.text().slice(0,140))});
   p.on('response',r=>{const u=r.url();if(u.startsWith(B)&&r.status()>=400&&!/nope-404|zzzz/.test(u))fails.push(r.status()+' '+u.replace(B,''))});
   p.on('requestfailed',r=>{if(r.url().startsWith(B))fails.push('failed '+r.url().replace(B,''))});
   for (const [name,url] of list) {
    errs.length=0;fails.length=0;
    let st=0; try{const r=await p.goto(url.startsWith('http')?url:B+url,{waitUntil:'networkidle',timeout:30000});st=r.status();}catch(e){out.push({name,theme,vn,label,error:'goto '+e.message.slice(0,100)});continue;}
    await p.evaluate(t=>{document.documentElement.setAttribute('data-theme',t);document.documentElement.setAttribute('data-bs-theme',t);const c=[...document.querySelectorAll('button')].find(x=>/Accept all/.test(x.textContent));c&&c.click();},theme);
    await p.evaluate(async()=>{for(let y=0;y<document.body.scrollHeight;y+=500){window.scrollTo(0,y);await new Promise(r=>setTimeout(r,60));}window.scrollTo(0,0);});
    await p.waitForTimeout(500);
    const m=await p.evaluate(()=>{const w=document.documentElement.clientWidth;
      const overflow=[...document.querySelectorAll('body *')].filter(e=>{const r=e.getBoundingClientRect();const cs=getComputedStyle(e);return r.width>0&&r.right>w+1&&cs.position!=='fixed'&&cs.visibility!=='hidden'&&!e.closest('.mk-rail,.fx-recent-row,.fx-trend,.mk-cats-row,.fx-cap,.fx-caps,.mk-drawer,.offcanvas,.fx-cmd,.mk-mega-panel,[hidden],.nx-toasts,.sf-suggest,.mk-wz-steps,.table-responsive,.sf-thumbs')}).slice(0,3).map(e=>e.tagName.toLowerCase()+'.'+String(e.className).split(' ')[0]);
      const imgs=[...document.images].filter(i=>i.complete&&i.naturalWidth===0&&i.src&&getComputedStyle(i).display!=='none').map(i=>i.src.slice(-50));
      const noname=[...document.querySelectorAll('button,a[href],input:not([type=hidden]),select,textarea')].filter(e=>{const cs=getComputedStyle(e);if(cs.display==='none'||cs.visibility==='hidden'||e.closest('[hidden],.visually-hidden,[aria-hidden=true]'))return false;const n=(e.getAttribute('aria-label')||e.textContent||e.getAttribute('title')||e.getAttribute('placeholder')||(e.id&&document.querySelector('label[for="'+e.id+'"]'))||(e.closest('label'))||e.value||'').toString().trim();return !n&&e.type!=='submit'&&e.type!=='file'}).slice(0,3).map(e=>e.outerHTML.slice(0,80));
      const small=innerWidth<500?[...document.querySelectorAll('a[href],button,input:not([type=hidden]),select')].filter(e=>{const r=e.getBoundingClientRect();const cs=getComputedStyle(e);return r.width>0&&cs.visibility!=='hidden'&&(r.height<30||r.width<30)&&!e.closest('[hidden],.fx-trend,.sf-crumbs,.mk-crumbs,.mk-legal,.mk-fcol,.pagination,.visually-hidden,p,li,small')&&!e.matches('input[type=checkbox],input[type=radio]')}).length:0;
      return {h1:document.querySelectorAll('h1').length,overflow,imgs,noname,small,title:document.title};});
    let axe=[];try{await p.evaluate(axeSrc);axe=await p.evaluate(async()=>{const r=await axe.run(document,{runOnly:['wcag2a','wcag2aa','best-practice'],rules:{'region':{enabled:false},'landmark-one-main':{enabled:false},'page-has-heading-one':{enabled:false}}});return r.violations.map(v=>({id:v.id,impact:v.impact,n:v.nodes.length,ex:v.nodes[0].target.join(' ').slice(0,70),msg:(v.nodes[0].any[0]||v.nodes[0].all[0]||v.nodes[0].none[0]||{}).message?.slice(0,90)}));});}catch(e){axe=[{id:'axe-failed',impact:'x',n:0,ex:e.message.slice(0,60)}];}
    out.push({name,theme,vn,label,status:st,errs:[...new Set(errs)].slice(0,3),fails:[...new Set(fails)].slice(0,4),...m,axe});
   }
   await p.close();
  };
  // guest
  let ctx=await mk(); await run(ctx,pages,'guest');
  // guest with cart
  const cp=await ctx.newPage();await cp.goto(B+'/product/panadol-500mg-20-tablets',{waitUntil:'networkidle'});await cp.evaluate(()=>{const c=[...document.querySelectorAll('button')].find(x=>/Accept all/.test(x.textContent));c&&c.click();});await cp.click('[data-buy-btn]');await cp.waitForTimeout(700);await cp.close();
  await run(ctx,cartPages,'guest+cart'); await ctx.close();
  // customer
  ctx=await mk(); const lp=await ctx.newPage(); await lp.goto(B+'/login',{waitUntil:'networkidle'}); await lp.fill('input[name=email]','sara@example.com'); await lp.fill('input[name=password]','Demo-pass-123'); await lp.waitForTimeout(1300); await Promise.all([lp.waitForNavigation(),lp.click('button[type=submit]:has-text("Sign in")')]).catch(()=>{}); await lp.close();
  const cp2=await ctx.newPage();await cp2.goto(B+'/product/panadol-500mg-20-tablets',{waitUntil:'networkidle'});await cp2.click('[data-buy-btn]');await cp2.waitForTimeout(600);await cp2.close();
  await run(ctx,authPages,'customer'); await ctx.close();
  console.error('done',theme,vn);
 }
 if(only>=0&&only<6){fs.writeFileSync(__dirname+'/out/report-'+only+'.json',JSON.stringify(out,null,1));console.error('written',out.length);await b.close();return;}
 // RTL / Roman-Urdu spot checks (dark+light, desktop+mobile)
 for (const loc of ['ur','ur_Latn']) for (const theme of ['dark','light']) for (const [vn,vs] of [['desktop',{width:1440,height:900}],['mobile',{width:390,height:844}]]) {
  const ctx=await b.newContext({viewport:vs,isMobile:vn==='mobile',bypassCSP:true});await ctx.addInitScript(t=>{try{localStorage.setItem('nx-theme',t);localStorage.setItem('fx-cookie','essential')}catch(e){}},theme);
  const p=await ctx.newPage();await p.goto(B+'/lang/'+loc,{waitUntil:'networkidle'});
  for (const [name,url] of [['home','/'],['shop','/shop'],['product','/product/panadol-500mg-20-tablets'],['blog','/blog'],['contact','/contact'],['upload-rx','/upload-prescription'],['login','/login']]) {
   await p.goto(B+url,{waitUntil:'networkidle'});await p.evaluate(t=>{document.documentElement.setAttribute('data-theme',t);const c=[...document.querySelectorAll('button')].find(x=>/Accept all|سب قبول/.test(x.textContent));c&&c.click();},theme);await p.waitForTimeout(400);
   const m=await p.evaluate(()=>{const w=document.documentElement.clientWidth;return {dir:document.documentElement.dir,sw:document.documentElement.scrollWidth,w,untranslated:(document.body.innerText.match(/\b(Add to cart|Upload prescription|Health blog|Contact us|Shop by need)\b/g)||[]).length}});
   out.push({name:'L:'+loc+':'+name,theme,vn,label:'locale',...m,overflowPx:m.sw-m.w});
  }
  await ctx.close();
 }
 fs.writeFileSync(__dirname+'/out/report-L.json',JSON.stringify(out,null,1));console.error('written',out.length);
 await b.close();})();
