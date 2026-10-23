<?php require 'includes/layout.php'; $table = max(1, min(99, (int)($_GET['table'] ?? 1))); ?>
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Order · Table <?= $table ?></title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css?v=5"></head>
<body class="cust">
<main class="order-page">
  <header class="order-header"><a class="order-brand" href="order.php" aria-label="Kimchee Delight home"><span class="brand-mark">K</span><span>Kimchee<span class="brand-light"> Delight</span></span></a><span class="table-pill"><span></span> Table <?= $table ?></span></header>
  <section class="order-hero">
    <div class="hero-copy"><p class="eyebrow">A LITTLE TASTE OF KOREA</p><h1>Gather. Grill.<br><span>Enjoy every bite.</span></h1><p>Choose your favorite flavors, then grill fresh cuts right at your table.</p><a href="#menu" class="hero-link">Explore the menu <span aria-hidden="true">↓</span></a></div>
    <div class="hero-photo"><img src="https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=1200&q=85" alt="Korean barbecue grilling at the table"><span class="photo-caption">Made for sharing</span></div>
  </section>
  <div id="done" class="msg ok order-success" role="status" hidden>Order sent to the kitchen! Your server will be with you shortly.</div>
  <section class="menu-section" id="menu">
    <div class="section-heading"><div><p class="eyebrow">THE MAIN EVENT</p><h2>Choose your grill</h2></div><span class="section-note">Pick a flavor for each cut · grill it your way</span></div>
    <div id="meats" class="meat-grid"></div>
  </section>
  <section class="order-lower">
    <div class="order-panel"><p class="eyebrow">A LITTLE SOMETHING ON THE SIDE</p><h2>Side dishes</h2><div id="sides" class="side-grid"><p class="menu-loading">Loading sides from inventory…</p></div></div>
    <div class="order-panel"><p class="eyebrow">SOMETHING TO SIP</p><h2>Drinks</h2><div id="drinks" class="side-grid"><p class="menu-loading">Loading drinks from inventory…</p></div></div>
  </section>
  <section class="refill-section"><div><p class="eyebrow">NEED A TOP-UP?</p><h2>Request a refill</h2><p>Tap anything you'd like more of.</p></div><div class="chips" id="refills"></div></section>
</main>
<div class="sticky order-sticky"><div class="order-total"><span class="total-label">YOUR TABLE</span><strong><span id="item-count">0</span> <span id="item-label">items</span> selected</strong></div><button class="btn" id="send">Send to kitchen <span aria-hidden="true">→</span></button></div>
<script src="assets/js/app.js"></script>
<script>
const MEATS=[
  {name:'Pork',desc:'Thin-sliced pork belly, ready for the grill',image:'premium_photo-1725899526027-d70de4e01ee8'},
  {name:'Beef',desc:'Thin-sliced beef for Korean BBQ',image:'premium_photo-1661412657617-d6eab13c563e'},
  {name:'Chicken',desc:'Thin-sliced chicken, ready for the grill',image:'premium_photo-1725467478749-ee1b4e92acc9'}
], REFILLS=['Lettuce','Water','Meat refill'], PHOTO_BY_ITEM={
  'CHEESE':'premium_photo-1775581773993-d49ec225d27b',
  'FISHCAKE':'premium_photo-1700746098900-ba202a05618e',
  'HOTDOG':'premium_photo-1664478260612-7c3c685d0998',
  'KIMCHI':'premium_photo-1700947160393-78ab82cc71f4',
  'RAMEN':'premium_photo-1694708455249-992010f9db32',
  'RICE':'premium_photo-1675814316651-3ce3c6409922',
  'TTEOKBOKKI':'premium_photo-1664360227360-16628277f9f0',
  'COKE 1.5':'photo-1648569883125-d01072540b4c',
  'COKE MISMO':'photo-1648569883125-d01072540b4c',
  'JUICE':'photo-1640213505284-21352ee0d76b',
  'MILK':'photo-1550583724-b2692b85b150',
  'SOJU':'photo-1567143768137-1251ad4b85c3'
}, LABELS={CHEESE:'Cheese',FISHCAKE:'Fishcake',HOTDOG:'Hotdog',KIMCHI:'Kimchi',RAMEN:'Ramen',RICE:'Rice',TTEOKBOKKI:'Tteokbokki','COKE 1.5':'Coke 1.5L','COKE MISMO':'Coke Mismo',JUICE:'Juice',MILK:'Milk',SOJU:'Soju'};
const qty={}, flavors={}, refill=new Set(), sides={}, drinks={};
const imageUrl = (id, width) => `https://${id.startsWith('premium_photo-')?'plus':'images'}.unsplash.com/${id}?auto=format&fit=crop&w=${width}&q=80`;
$('#meats').innerHTML=MEATS.map((m,i)=>`<article class="meat-card"><div class="food-photo"><img src="${imageUrl(m.image,640)}" alt="${esc(m.name)}" loading="lazy"><span class="food-number">0${i+1}</span></div><div class="meat-info"><div class="meat-top"><div><h3>${esc(m.name)}</h3><p>${esc(m.desc)}</p></div><div class="qty"><button type="button" aria-label="Remove ${esc(m.name)}" data-m="${esc(m.name)}" data-d="-1">−</button><b id="q-${i}" aria-live="polite">0</b><button type="button" aria-label="Add ${esc(m.name)}" data-m="${esc(m.name)}" data-d="1">+</button></div></div><label class="flavor-control">Flavor<select aria-label="Flavor for ${esc(m.name)}" data-flavor="${esc(m.name)}"><option>Plain</option><option>Garlic</option><option>Spicy gochujang</option></select></label></div></article>`).join('');
function inventoryLabel(name){return LABELS[name]||name.toLowerCase().replace(/\b\w/g,letter=>letter.toUpperCase());}
function renderInventoryCards(items,container,group,counts){
  container.innerHTML=items.map((item,i)=>`<article class="side-card">${PHOTO_BY_ITEM[item.name]?`<img src="${imageUrl(PHOTO_BY_ITEM[item.name],400)}" alt="${esc(inventoryLabel(item.name))}" loading="lazy">`:'<div class="side-photo-placeholder" aria-hidden="true"></div>'}<span>${esc(inventoryLabel(item.name))}</span><div class="side-qty"><button type="button" aria-label="Remove ${esc(inventoryLabel(item.name))}" data-group="${group}" data-index="${i}" data-d="-1" disabled>−</button><b id="${group}-q-${i}" aria-live="polite">0</b><button type="button" aria-label="Add ${esc(inventoryLabel(item.name))}" data-group="${group}" data-index="${i}" data-d="1">+</button></div></article>`).join('')||'<p class="menu-empty">No items in this inventory category yet.</p>';
  items.forEach(item=>{counts[item.name]=0;});
}
const inventoryItems={sides:[],drinks:[]};
fetch('api/inventory.php').then(response=>{if(!response.ok)throw new Error('Inventory menu could not be loaded.');return response.json();}).then(result=>{
  if(!Array.isArray(result.items))throw new Error('Inventory returned an invalid menu.');
  inventoryItems.sides=result.items.filter(item=>item.cat==='Sides');
  inventoryItems.drinks=result.items.filter(item=>item.cat==='Beverage');
  renderInventoryCards(inventoryItems.sides,$('#sides'),'side',sides);
  renderInventoryCards(inventoryItems.drinks,$('#drinks'),'drink',drinks);
}).catch(error=>{
  $('#sides').innerHTML=`<p class="menu-error">${esc(error.message)} Refresh the page to try again.</p>`;
  $('#drinks').innerHTML=`<p class="menu-error">${esc(error.message)} Refresh the page to try again.</p>`;
});
$('#refills').innerHTML=REFILLS.map(r=>`<button data-r="${esc(r)}">${esc(r)}</button>`).join('');
function updateCount(){const count=Object.values(qty).reduce((sum,n)=>sum+n,0)+Object.values(sides).reduce((sum,n)=>sum+n,0)+Object.values(drinks).reduce((sum,n)=>sum+n,0)+refill.size;$('#item-count').textContent=count;$('#item-label').textContent=count===1?'item':'items';}
$('#meats').onclick=e=>{const m=e.target.dataset.m;if(!m)return;qty[m]=Math.max(0,Math.min(10,(qty[m]||0)+ +e.target.dataset.d));$('#q-'+MEATS.findIndex(item=>item.name===m)).textContent=qty[m];updateCount();};
$('#meats').onchange=e=>{const m=e.target.dataset.flavor;if(m)flavors[m]=e.target.value;};
function changeInventoryQuantity(event,group,items,counts){
  const button=event.target.closest('button[data-group="'+group+'"]');if(!button)return;
  const item=items[Number(button.dataset.index)];if(!item)return;
  counts[item.name]=Math.max(0,Math.min(10,(counts[item.name]||0)+Number(button.dataset.d)));
  $('#'+group+'-q-'+button.dataset.index).textContent=counts[item.name];
  const card=button.closest('.side-card');
  card.querySelector('[data-d="-1"]').disabled=counts[item.name]===0;
  card.querySelector('[data-d="1"]').disabled=counts[item.name]===10;
  updateCount();
}
$('#sides').onclick=e=>changeInventoryQuantity(e,'side',inventoryItems.sides,sides);
$('#drinks').onclick=e=>changeInventoryQuantity(e,'drink',inventoryItems.drinks,drinks);
$('#refills').onclick=e=>{const r=e.target.dataset.r;if(!r)return;refill.has(r)?refill.delete(r):refill.add(r);e.target.classList.toggle('selected',refill.has(r));e.target.setAttribute('aria-pressed',refill.has(r));updateCount();};
$('#send').onclick=async()=>{
  const meats=MEATS.filter(m=>qty[m.name]).map(m=>`${m.name} ×${qty[m.name]} (${flavors[m.name] || 'Plain'})`);
  const orderItems=[...meats,...inventoryItems.sides.filter(s=>sides[s.name]).map(s=>`${s.name} ×${sides[s.name]}`),...inventoryItems.drinks.filter(d=>drinks[d.name]).map(d=>`${d.name} ×${drinks[d.name]}`)];
  if(!orderItems.length&&!refill.size)return alert('Pick at least one item or refill.');
  const submitted=[];
  if(orderItems.length)submitted.push({table:<?= $table ?>,items:orderItems.join(', '),type:'Order'});
  if(refill.size)submitted.push({table:<?= $table ?>,items:[...refill].join(', ')+' refill',type:'Refill'});
  const send=$('#send');
  send.disabled=true;
  try{
    const response=await fetch('api/orders.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({orders:submitted})});
    const result=await response.json().catch(()=>({errors:['The server returned an unexpected response.']}));
    if(!response.ok)throw new Error([].concat(result.errors||'Order could not be sent.').join(' '));
    $('#done').classList.remove('err');$('#done').classList.add('ok');
    $('#done').textContent='Order sent to the kitchen! Your server will be with you shortly.';
    $('#done').hidden=false;scrollTo(0,0);
  }catch(error){
    $('#done').classList.remove('ok');$('#done').classList.add('err');
    $('#done').textContent=error.message;$('#done').hidden=false;scrollTo(0,0);
    send.disabled=false;
    return;
  }
  Object.keys(qty).forEach(k=>qty[k]=0);Object.keys(flavors).forEach(k=>flavors[k]='Plain');document.querySelectorAll('[id^=q-]').forEach(n=>n.textContent=0);document.querySelectorAll('#meats select').forEach(select=>select.value='Plain');
  refill.clear();inventoryItems.sides.forEach(item=>sides[item.name]=0);inventoryItems.drinks.forEach(item=>drinks[item.name]=0);document.querySelectorAll('#sides b[id^="side-q-"],#drinks b[id^="drink-q-"]').forEach(counter=>counter.textContent='0');document.querySelectorAll('#sides [data-d="-1"],#drinks [data-d="-1"]').forEach(button=>button.disabled=true);document.querySelectorAll('#sides [data-d="1"],#drinks [data-d="1"]').forEach(button=>button.disabled=false);document.querySelectorAll('#refills button').forEach(b=>{b.classList.remove('selected');b.setAttribute('aria-pressed','false');});updateCount();
  send.disabled=false;
};
</script></body></html>
