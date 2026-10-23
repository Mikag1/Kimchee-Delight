<?php require 'includes/layout.php'; page_start('Orders (Staff)', 'orders'); ?>
<p class="note">Orders are shared with customer devices and staff through the database. Marking an order served saves its status only; record stock usage separately in Inventory.</p>
<div id="order-message"></div>
<div class="card"><h2>Customer QR ordering</h2>
  <p>Each table would get its own QR code pointing to <code>order.php?table=N</code>. Try it:</p>
  <label style="max-width:160px">Table number<input id="tn" type="number" min="1" max="30" value="5"></label><br>
  <a class="btn" id="open" target="_blank" href="order.php?table=5" style="display:inline-block;text-decoration:none">Open customer view</a></div>
<div class="card"><h2>Order queue</h2><div class="tw"><table><thead><tr><th>Table</th><th>Items</th><th>Type</th><th>Submitted</th><th>Status</th><th></th></tr></thead><tbody id="o"></tbody></table></div></div>
<dialog id="order-dialog" class="order-dialog" aria-labelledby="dialog-title">
  <div class="dialog-header"><div><p class="eyebrow">TABLE <span id="dialog-table"></span> · <span id="dialog-type"></span></p><h2 id="dialog-title">Order checklist</h2></div><button class="icon-btn" id="dialog-close" type="button" aria-label="Close checklist">×</button></div>
  <p class="dialog-hint">Check off each item as you prepare it. Your progress is saved so you can come back to this list.</p>
  <div id="dialog-items" class="checklist"></div>
  <div class="dialog-footer"><span id="dialog-progress" class="dialog-progress"></span><button class="btn" id="dialog-done" type="button">Done</button></div>
</dialog>
<?php page_end('orders.js?v=10'); ?>
