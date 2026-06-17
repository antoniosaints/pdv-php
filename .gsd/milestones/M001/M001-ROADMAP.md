# M001: MVP operacional de PDV e estoque

**Vision:** Ship a first usable, easy-to-host PHP PDV for small stores: install quickly, manage products and variants, sell through a responsive PDV, update stock automatically, print receipts/labels and expose actionable business summaries.

## Success Criteria

- A clean install can run the PHP app with SQLite using Composer and documented setup steps.
- Protected users can manage products, variants, services and barcodes.
- The PDV can complete a sale with barcode input, payment recording and automatic stock decrement.
- Receipts and labels can be previewed and sent through the QZ Tray integration path with diagnostics.
- Stock replenishment, service orders, sales reports, profit summary, monthly projection and dashboard tips exist at MVP depth.
- Backup/export and operational diagnostics are available for a small hosted deployment.

## Slices

- [x] **S01: S01** `risk:high` `depends:[]`
  > After this: A clean checkout can install dependencies, create the SQLite schema, log in as admin and show a protected dashboard shell with database and health diagnostics.

- [x] **S02: S02** `risk:high` `depends:[]`
  > After this: An authenticated admin can create products, variants, services, barcodes, costs, prices, stock minimums and label data, then search them responsively.

- [x] **S03: S03** `risk:high` `depends:[]`
  > After this: A cashier can scan or type a barcode, add items to cart, apply quantity/discount, choose payment, finalize a sale and see stock decrease automatically.

- [x] **S04: S04** `risk:high` `depends:[]`
  > After this: After a sale, the cashier can preview and send a receipt to QZ Tray; an admin can print a product label from catalog data, with visible connection status.

- [x] **S05: S05** `risk:medium` `depends:[]`
  > After this: The stock screen shows low-stock products, lets the user record replenishment entries or adjustments, and updates balances and movement history.

- [x] **S06: S06** `risk:medium` `depends:[]`
  > After this: A user can create a customer service order, add services/products, move it through statuses and optionally close it into a sale/payment flow.

- [ ] **S07: Relatorios dashboard e dicas** `risk:medium` `depends:[S03,S05,S06]`
  > After this: The dashboard shows sales total, gross profit, low-stock alerts, open orders, monthly projection and simple improvement tips based on real stored data.

- [ ] **S08: Fechamento operacional e UAT** `risk:medium` `depends:[S04,S05,S07]`
  > After this: From a clean install, the app passes an end-to-end browser UAT covering login, catalog, sale, stock change, receipt preview, reports, backup export and diagnostics.

## Boundary Map

### S01 → S02

Produces:
- Composer project structure with PHP app entrypoint, router, layout, PDO connection, migration runner, auth/session middleware, base tables, test command and health page.

Consumes:
- nothing (first slice)

### S02 → S03

Produces:
- Product, variant, barcode and inventory tables plus forms, validation and query services for lookup by barcode or name.

Consumes:
- S01 database, routing, auth, layout and test patterns.

### S03 → S04

Produces:
- Completed sale records with line items, payments, stock movement records and receipt data shape.

Consumes:
- S02 product lookup and stock balances.

### S03 → S05

Produces:
- Stock movement history and current balances after sales.

Consumes:
- S02 product catalog and S03 sales decrement logic.

### S03 → S07

Produces:
- Sales, payment, cost and stock movement facts usable by reports.

Consumes:
- S03 completed sale data.

### S04 → S08

Produces:
- Browser-side QZ Tray adapter, print templates and diagnostic print status for final UAT.

Consumes:
- S03 receipt data and S02 label data.

### S07 → S08

Produces:
- Dashboard and report pages that final integration can verify against seeded sales data.

Consumes:
- S03 sale data, S05 stock status and S06 service/order data.
