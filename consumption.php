<?php require 'includes/layout.php'; page_start('Meat Consumption', 'consumption'); ?>
<div id="msg"></div>
<div class="card"><h2>Daily record</h2>
<form id="f" novalidate>
  <div class="row">
    <label>Business date<input type="date" name="business_date" required></label>
    <label>Customers served<input type="number" name="customers" min="0" step="1" required></label>
    <label>Pork (kg)<input type="number" name="pork_kg" min="0" step="0.01"></label>
    <label>Beef (kg)<input type="number" name="beef_kg" min="0" step="0.01"></label>
    <label>Chicken (kg)<input type="number" name="chicken_kg" min="0" step="0.01"></label>
    <label>Other meat (kg)<input type="number" name="other_kg" min="0" step="0.01"></label>
  </div>
  <div class="row">
    <label>Buffet revenue (₱)<input type="number" name="revenue" min="0" step="0.01"></label>
    <label>Meat cost (₱)<input type="number" name="meat_cost" min="0" step="0.01"></label>
    <label>Other expenses (₱)<input type="number" name="other_expenses" min="0" step="0.01"></label>
  </div>
  <div class="preview" id="pv"></div>
  <button class="btn" id="save">Save record</button> <button type="button" class="btn ghost" id="cancel" hidden>Cancel edit</button>
</form></div>
<div class="card"><h2>Seven-day trend (avg meat per customer)</h2><div class="bars" id="chart"></div></div>
<div class="card"><h2>Saved records &amp; historical averages</h2>
  <div class="filters">
    <label>From<input type="date" id="from"></label><label>To<input type="date" id="to"></label>
    <button class="btn" id="apply" type="button">Filter</button><button class="btn ghost" id="clear" type="button">Clear</button>
  </div>
  <div id="hist"></div>
  <div class="tw"><table><thead><tr><th>Date</th><th>Customers</th><th>Total meat</th><th>Avg / customer</th><th>Revenue</th><th>Meat cost</th><th>Net profit</th><th>Margin</th><th></th></tr></thead>
  <tbody id="rows"></tbody></table></div>
</div>
<?php page_end('consumption.js'); ?>
