# S04: Impressao recibos e etiquetas

**Goal:** Entregar preview imprimível de recibo de venda e etiqueta de produto, com adaptador browser-side para QZ Tray e diagnóstico de conexão/erro visível para operação.
**Demo:** After a sale, the cashier can preview and send a receipt to QZ Tray; an admin can print a product label from catalog data, with visible connection status.

## Must-Haves

- Venda concluída oferece link para preview de recibo.
- Preview do recibo mostra código da venda, itens, totais, pagamentos e aviso de recibo gerencial não fiscal.
- Produto ou variante oferece preview de etiqueta com nome, SKU/barcode e preço.
- Páginas de impressão carregam um adaptador QZ Tray com estado visível: aguardando, conectado, indisponível ou erro.
- Usuário consegue acionar impressão via QZ quando disponível ou usar impressão nativa como fallback.
- Testes cobrem guarda de autenticação, conteúdo do recibo, conteúdo da etiqueta e presença dos diagnósticos de impressão.

## Proof Level

- This slice proves: integration

## Integration Closure

Consumes S03 sale, item and payment data plus S02 label/barcode data. Produces print templates, QZ adapter and diagnostic UI consumed by final UAT in S08.

## Verification

- Adds print status panels, QZ availability checks, last error rendering in the browser and explicit fallback to native print for environments without QZ Tray.

## Tasks

- [x] **T01: Adicionar previews protegidos de recibo e etiqueta** `est:2h`
  Add a PrintController and protected routes for receipt and label previews. Receipt route must load sale, items and payments from SalesRepository; label route must load product and variant data from CatalogRepository. Missing sale/product/variant should return contextual 404 pages. Add feature tests for authentication, receipt preview content and label preview content.
  - Files: `src/Controllers/PrintController.php`, `src/Http/Router.php`, `templates/print/receipt.php`, `templates/print/label.php`, `tests/Feature/PrintPreviewTest.php`
  - Verify: composer test

- [x] **T02: Implementar adaptador QZ Tray com fallback nativo** `est:2h`
  Add a browser-side QZ Tray adapter JavaScript and print diagnostics component. The adapter must detect whether `window.qz` exists, show status transitions, expose last error without secrets, and fall back to `window.print()` when QZ is unavailable. Wire buttons on receipt and label preview pages. Add tests/assertions that print pages include QZ diagnostics and fallback controls.
  - Files: `public/assets/print.js`, `templates/layout.php`, `templates/print/receipt.php`, `templates/print/label.php`, `tests/Feature/PrintPreviewTest.php`
  - Verify: composer test

- [x] **T03: Ligar acoes de impressao no fluxo operacional** `est:1h30m`
  Wire receipt links from completed sales and label links from catalog variant rows. Add print-specific responsive CSS for receipt, label and diagnostic panels. Update README with QZ Tray expectations and browser fallback. Ensure visual layout remains readable on desktop and mobile.
  - Files: `templates/sales/show.php`, `templates/catalog/show.php`, `public/assets/app.css`, `README.md`, `tests/Feature/PrintPreviewTest.php`
  - Verify: composer test

- [x] **T04: Executar UAT de impressao e diagnosticos** `est:1h30m`
  Run final verification for S04: full PHPUnit, install verifier, and browser UAT through the real PHP entrypoint. UAT must log in, open an existing sale receipt preview, confirm receipt data and print status controls, open a catalog label preview and confirm barcode/price/status controls with no console errors or failed requests.
  - Files: `tests/Feature/PrintPreviewTest.php`
  - Verify: composer test

## Files Likely Touched

- src/Controllers/PrintController.php
- src/Http/Router.php
- templates/print/receipt.php
- templates/print/label.php
- tests/Feature/PrintPreviewTest.php
- public/assets/print.js
- templates/layout.php
- templates/sales/show.php
- templates/catalog/show.php
- public/assets/app.css
- README.md
