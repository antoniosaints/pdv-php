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

## Impressão

A impressão de recibos e etiquetas será feita no navegador via QZ Tray. O MVP não emite NFC-e, SAT, SPED ou qualquer documento fiscal legal.
