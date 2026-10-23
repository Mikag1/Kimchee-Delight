USE samgyutrack;

CREATE TABLE IF NOT EXISTS inventory_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  category VARCHAR(30) NOT NULL DEFAULT 'Other',
  unit VARCHAR(20) NOT NULL DEFAULT 'unit',
  quantity DECIMAL(12,3) NOT NULL DEFAULT 0,
  low_stock DECIMAL(12,3) NOT NULL DEFAULT 0,
  is_sample TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY inventory_items_name_unique (name)
);

CREATE TABLE IF NOT EXISTS inventory_movements (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  item_id INT UNSIGNED NOT NULL,
  movement_type ENUM('opening','received','used','count') NOT NULL,
  added DECIMAL(12,3) NOT NULL DEFAULT 0,
  used DECIMAL(12,3) NOT NULL DEFAULT 0,
  adjustment DECIMAL(12,3) NOT NULL DEFAULT 0,
  balance_after DECIMAL(12,3) NOT NULL,
  note VARCHAR(255) NOT NULL DEFAULT '',
  movement_date DATE NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT inventory_movements_item_fk FOREIGN KEY (item_id) REFERENCES inventory_items(id),
  KEY inventory_movements_date_idx (movement_date, id),
  KEY inventory_movements_item_idx (item_id, id)
);

CREATE TABLE IF NOT EXISTS orders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  table_number TINYINT UNSIGNED NOT NULL,
  items TEXT NOT NULL,
  order_type ENUM('Order','Refill') NOT NULL DEFAULT 'Order',
  status ENUM('Pending','Preparing','Served') NOT NULL DEFAULT 'Pending',
  checked_items TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY orders_status_created_idx (status, created_at)
);

INSERT IGNORE INTO inventory_items (name, category, unit, quantity, low_stock, is_sample) VALUES
('JOWLS','Meat','unit',0,0,1),
('KASIM','Meat','unit',0,0,1),
('MIXED MEAT','Meat','unit',6.9,0,1),
('BEEF','Meat','unit',0.473,0,1),
('CHICKEN','Meat','unit',2.944,0,1),
('FILLET','Meat','unit',1.005,0,1),
('RICE','Sides','unit',15,0,1),
('KIMCHI','Sides','unit',0.5,0,1),
('HOTDOG','Sides','unit',1,0,1),
('LETTUCE','Produce','unit',0,0,1),
('POTATO','Produce','unit',10,0,1),
('BANANA','Produce','unit',0,0,1),
('CUCUMBER','Produce','unit',2,0,1),
('TTEOKBOKKI','Sides','unit',0,0,1),
('FISHCAKE','Sides','unit',1,0,1),
('CORN','Produce','unit',5,0,1),
('JUICE','Beverage','unit',5,0,1),
('MILK','Beverage','unit',6.5,0,1),
('CHEESE','Sides','unit',21,0,1),
('RAMEN','Sides','unit',41,0,1),
('SOY SAUCE','Pantry','unit',2,0,1),
('OIL','Pantry','unit',1,0,1),
('SUGAR','Pantry','unit',1,0,1),
('ENOKI','Produce','unit',0,0,1),
('SOJU','Beverage','unit',0,0,1),
('COKE 1.5','Beverage','unit',8,0,1),
('COKE MISMO','Beverage','unit',21,0,1);
