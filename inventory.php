<?php require 'includes/layout.php'; page_start('Inventory', 'inventory'); ?>
<p class="note">Opening balances are from the KD Tanay inventory sheet (October 5, 2026). Confirm physical counts and set each item's unit and low-stock level before relying on alerts. Marking an order served only records its status; use Record stock movement to update inventory.</p>
<div id="warn"></div>
<div class="card"><h2>Record stock movement</h2>
  <form id="mf"><div class="row">
    <label>Ingredient<select id="mi" required></select></label>
    <label>Movement<select id="mt"><option value="received">Received</option><option value="used">Used</option></select></label>
    <label>Quantity<input id="mq" type="number" min="0.001" step="any" required></label>
    <label>Note (optional)<input id="mn" maxlength="100"></label></div>
  <button class="btn">Record movement</button></form></div>
<div class="card"><h2>Add inventory item</h2>
  <form id="af"><div class="row">
    <label>Name<input id="n" required maxlength="40"></label>
    <label>Category<select id="c"><option>Meat</option><option>Produce</option><option>Sides</option><option>Pantry</option><option>Beverage</option><option>Other</option></select></label>
    <label>Unit<input id="u" required maxlength="20" value="unit"></label>
    <label>Opening stock<input id="s" type="number" min="0" step="any" required></label>
    <label>Low-stock level<input id="l" type="number" min="0" step="any" value="0"></label></div>
  <button class="btn">Add item</button></form></div>
<div class="card"><div class="card-heading"><h2>Current stock</h2><button class="btn usage-btn" id="show-daily" type="button">View today's usage</button></div><div class="tw"><table><thead><tr><th>Ingredient</th><th>Category</th><th>Received today</th><th>Used today</th><th>Remaining</th><th>Status</th><th></th></tr></thead><tbody id="t"></tbody></table></div></div>
<div class="card"><h2>Recent stock movements</h2><div class="tw"><table><thead><tr><th>Date</th><th>Ingredient</th><th>Received</th><th>Used</th><th>Count adjustment</th><th>Balance after</th><th>Note</th></tr></thead><tbody id="history"></tbody></table></div></div>
<dialog id="daily-dialog" class="order-dialog" aria-labelledby="daily-title">
  <div class="dialog-header"><div><p class="eyebrow">INVENTORY</p><h2 id="daily-title">Today's usage</h2></div><button class="icon-btn" id="daily-close" type="button" aria-label="Close today's usage">×</button></div>
  <p class="dialog-hint">Usage recorded through manual inventory movements. Orders marked served only save their status and do not change stock. Daily Consumption records are separate.</p>
  <div class="tw"><table><thead><tr><th>Item</th><th>Used today</th></tr></thead><tbody id="daily-usage"></tbody></table></div>
</dialog>
<?php page_end('inventory.js?v=2'); ?>
