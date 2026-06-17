# PDV Estoque

Sistema web PHP para PDV, controle de estoque, produtos, variantes, etiquetas, serviços, ordens de serviço, relatórios e impressão via QZ Tray.

## Escopo do MVP

- PHP monolítico com Composer, sem API separada.
- SQLite por padrão, com estrutura preparada para migração futura para MySQL via PDO e migrations.
- Recibo gerencial não fiscal no PDV.
- Interface web responsiva para operação em desktop, tablet e celular.

## Requisitos locais

- PHP 8.1 ou superior.
- Extensão `pdo` habilitada.
- Composer.

## Instalação inicial

```bash
composer install
cp .env.example .env
composer setup
php bin/console seed:catalog
php bin/verify-install.php
composer serve
```

Acesse `http://127.0.0.1:8080`. No primeiro acesso, abra `/setup/admin` para criar o administrador inicial. Essa tela só funciona enquanto não houver usuários cadastrados. Depois de logar como admin, use `/health` para ver o diagnóstico operacional protegido.

Para testar o PDV após `php bin/console seed:catalog`, abra `/pos` e use os códigos demo:

- `7891000000010` — Camiseta Demo / Preta M, produto com estoque inicial para validar baixa automática.
- `7891000000027` — Ajuste de Barra Demo, serviço sem baixa física de estoque.

Ao finalizar uma venda, a tela `/sales/{id}` mostra itens, pagamento, troco e movimentos de estoque vinculados para diagnóstico.

## Ordens de serviço

Usuários `admin` ou `caixa` acessam `/service-orders` para acompanhar serviços antes do pagamento final:

- crie a ordem em `/service-orders/create` com cliente, telefone/documento opcionais, descrição, serviços e produtos do catálogo;
- acompanhe o andamento pelos status `Aberta`, `Em execução`, `Pronta`, `Cancelada` e `Fechada`;
- use a página da ordem para ver totais, itens, histórico de status e vínculo com a venda;
- feche a ordem em venda pelo formulário de pagamento da própria ordem.

O fechamento cria uma venda concluída, reaproveita o mesmo fluxo de pagamento do PDV e baixa estoque apenas dos produtos controlados. Serviços permanecem como itens vendidos sem movimento físico de estoque. Se o pagamento for insuficiente ou o estoque de produto não estiver disponível, a ordem continua aberta e a mensagem de validação aparece no detalhe da ordem.

Se preferir criar o administrador pelo console, defina `ADMIN_EMAIL`, `ADMIN_PASSWORD` e opcionalmente `ADMIN_NAME` no ambiente antes de rodar `composer setup`; os valores não são exibidos no console.

## Scripts

- `composer validate` — valida o arquivo Composer.
- `composer test` — executa a suíte de testes.
- `composer setup` — executa migrations e cria o administrador inicial apenas se `ADMIN_EMAIL` e `ADMIN_PASSWORD` estiverem configurados; caso contrário use `/setup/admin`.
- `php bin/console seed:catalog` — cria um produto demo com variante/código de barras e um serviço demo para testar fluxos do PDV.
- `php bin/verify-install.php` — verifica PHP, banco, migrations, storage e logs antes de operar o sistema.
- `composer serve` — sobe o servidor embutido do PHP para desenvolvimento local.

## Hospedagem

O diretório público da hospedagem deve apontar para `public/`. Arquivos como `.env`, `storage/`, `database/`, `src/` e `templates/` não devem ficar expostos como raiz pública.

## Banco de dados

O padrão do MVP é SQLite em `storage/database/pdv.sqlite`. Para manter migração futura para MySQL viável, o domínio deve usar PDO, migrations versionadas e SQL conservador.

## Estoque

A tela protegida `/stock` centraliza o controle operacional de estoque para usuários `admin` ou `estoque`:

- mostra variantes controladas, saldo atual e estoque mínimo;
- destaca itens no estoque mínimo ou abaixo dele;
- registra entrada de compra/reposição com quantidade positiva;
- registra ajustes manuais positivos ou negativos com motivo obrigatório;
- exibe histórico recente de movimentos.

Vendas, reposições e ajustes usam o mesmo ledger `stock_movements`, sempre com quantidade antes/depois, tipo do movimento e motivo/referência. Ajustes que deixariam saldo negativo são bloqueados.

## Impressão

A impressão de recibos e etiquetas é feita pelo navegador com duas camadas:

- Preview protegido no sistema: `/sales/{id}/receipt` para recibo gerencial e `/catalog/{id}/variants/{variantId}/label` para etiqueta.
- Adaptador browser-side em `/assets/print.js`, que tenta usar QZ Tray quando `window.qz` está disponível e mostra status/erro na própria página.

Se QZ Tray não estiver instalado ou disponível, os previews exibem fallback de impressão nativa do navegador. O MVP não emite NFC-e, SAT, SPED ou qualquer documento fiscal legal; o recibo é gerencial não fiscal.
