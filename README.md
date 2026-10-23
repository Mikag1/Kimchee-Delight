# SamgyuTrack (school prototype)

## Run with XAMPP
1. Install XAMPP, open the Control Panel, start **Apache** and **MySQL**.
2. Copy the `samgyutrack` folder into `C:\xampp\htdocs\`.
3. Go to http://localhost/phpmyadmin → **Import** tab → choose `database/samgyutrack.sql` → **Import**.
   (It creates the `samgyutrack` database, the `daily_consumption` table and 7 sample rows.)
4. Import `database/inventory_orders.sql` into phpMyAdmin too. It adds shared order/inventory tables and
   sample inventory rows. For an existing installation, import this additional file only; do not re-import
   `samgyutrack.sql` over existing daily records.
5. If your MySQL has a password, edit `config/database.php`.
6. Open http://localhost/samgyutrack/ . Internet is needed once for the Lucide icon CDN.

## What really works (PHP + MySQL)
Dashboard cards/chart and the Consumption page: add, edit, delete (with confirm), filter by date,
historical averages, 7-day trend. Orders are shared through MySQL; Inventory stock and movements are shared
through MySQL as well. Served orders save their status only—they do not deduct inventory. Inventory sample
balances and daily-consumption sample rows are marked as samples; daily summaries prefer recorded rows.

## Simulated (browser localStorage, no database)
Queue.

The sample inventory import includes 27 item names and balances from the KD Tanay spreadsheet, with units
left as "unit" and each row marked Sample. Enter a physical count with "Set count" to replace a sample balance
with a recorded count. Existing orders and inventory movements previously saved in browser storage are not
automatically copied into MySQL; retain them separately before switching if they contain data you need.
Daily Consumption records and Inventory movements remain separate; do not record the same usage in both.
There is no staff login yet. Restrict access to a trusted local network and do not expose this prototype to the
public internet.

## Formulas (includes/calc.php)
- Total meat = pork + beef + chicken + other
- Avg meat / customer = total meat ÷ customers (shown as "—" if customers = 0)
- Avg meat cost / customer = meat cost ÷ customers
- Avg revenue / customer = revenue ÷ customers
- Net profit = revenue − meat cost − other expenses
- Margin % = net profit ÷ revenue × 100 (shown as "—" if revenue = 0)
- Dashboard "last 7 days" = last 7 recorded days. Filtered/historical average = total meat ÷ total customers over the range (not an average of daily averages).
