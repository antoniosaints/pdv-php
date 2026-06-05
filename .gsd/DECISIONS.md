# Decisions Register

<!-- Append-only. Never edit or remove existing rows.
     To reverse a decision, add a new row that supersedes it.
     Read this file at the start of any planning or research phase. -->

| # | When | Scope | Decision | Choice | Rationale | Revisable? | Made By |
|---|------|-------|----------|--------|-----------|------------|---------|
| D001 | M001 planning | architecture | Arquitetura inicial do sistema PDV | Aplicação web monolítica em PHP com Composer, server-rendered e pequenos módulos JavaScript quando necessário, sem API separada no MVP. | O usuário pediu PHP para facilitar hospedagem e evitar backend separado; um monólito reduz operação, implantação e complexidade para shared hosting ou VPS simples. | Yes | human |
| D002 | M001 planning | architecture | Estratégia de banco de dados | SQLite como banco padrão via PDO, migrations e camada de acesso compatível com MySQL. | SQLite reduz fricção de instalação no MVP, enquanto PDO, migrations e tipos conservadores preservam caminho de migração para MySQL quando a instalação crescer. | Yes | human |
| D003 | M001 planning | product-scope | Escopo fiscal e impressão do PDV | Impressão inicial por QZ Tray para recibos gerenciais e etiquetas, sem emissão fiscal NFC-e/SAT no MVP. | O usuário confirmou recibo gerencial no MVP. QZ Tray atende impressão local a partir do navegador, enquanto emissão fiscal exige regras, certificados e homologação que devem ser planejados separadamente. | Yes | collaborative |
| D004 | M001/S01 planning | architecture | Framework PHP inicial | Usar um microkernel próprio com front controller, nikic/fast-route para roteamento e vlucas/phpdotenv para configuração, evitando Laravel/Symfony no MVP. | O alvo é instalação simples em hospedagem PHP comum. Um microkernel pequeno reduz dependências e exige menos configuração, mas ainda usa bibliotecas Composer maduras para roteamento e ambiente. | Yes | agent |
| D005 | M001/S01/T03 | security | Fluxo de criação do primeiro administrador | Criar o primeiro administrador por uma tela `/setup/admin` disponível somente enquanto não existir nenhum usuário, em vez de seedar uma senha padrão. | Credenciais padrão são perigosas em PDV hospedado. A tela de setup mantém a instalação simples e fecha automaticamente após criar o primeiro usuário. | Yes | agent |
| D006 | M001/S01/T04 | security-observability | Exposição do health check | Manter `/health` protegido por autenticação, com script CLI `bin/verify-install.php` para verificação antes do primeiro login. | O health check revela detalhes operacionais como driver, paths graváveis e migrations. Isso ajuda o operador e agentes, mas não deve ficar público em hospedagem de loja. | Yes | agent |
