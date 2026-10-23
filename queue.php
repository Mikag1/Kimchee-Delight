<?php require 'includes/layout.php'; page_start('Queue', 'queue'); ?>
<p class="note">Prototype module — uses sample data saved in your browser only (no database). Each group gets two hours at the table, starting when seated.</p>
<div class="two">
  <div class="card"><h2>Add to queue</h2>
    <form id="qf"><div class="row"><label>Customer / group name<input id="qname" required maxlength="40"></label>
    <label>People<input id="qsize" type="number" min="1" max="20" value="2" required></label></div>
    <button class="btn">Add to queue</button></form></div>
  <div class="card"><h2>Table availability</h2><div class="tables" id="tables"></div></div>
</div>
<div class="card"><h2>Queue</h2><div class="tw"><table><thead><tr><th>No.</th><th>Name</th><th>People</th><th>Table</th><th>Status</th><th>Wait / dining time</th><th></th></tr></thead><tbody id="q"></tbody></table></div></div>
<?php page_end('queue.js?v=2'); ?>
