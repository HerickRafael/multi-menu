# Multi Menu

Aplicação modular para gerenciamento de cardápios digitais seguindo as diretrizes de arquitetura em camadas (Controllers → Services → Repositories/Domain).

## Primeiros passos

Depois de clonar o repositório, execute um único comando para preparar o ambiente (macOS, Linux ou WSL):

```bash
make setup
```

O alvo `setup` cuida das seguintes etapas:

1. Instala dependências do macOS via Homebrew (definidas em `Brewfile`).
2. Garante que Docker Desktop ou Colima estejam prontos para uso.
3. Copia `.env.example` para `.env` e gera uma `APP_KEY` automaticamente.
4. Executa `composer install` e `npm install` (quando existir `package.json`).
5. Sobe os containers com `docker compose up -d --build`.
6. Executa migrações (`bin/migrate`) e seeds (`bin/seed`).
7. Configura os hooks do Git via GrumPHP para executar QA antes dos commits.

Após o `make setup` a aplicação estará disponível em [http://localhost:8000](http://localhost:8000).

- O cardápio de demonstração pode ser acessado diretamente em [`http://localhost:8000/wollburger`](http://localhost:8000/wollburger).

O stack também sobe um phpMyAdmin em [http://localhost:8081](http://localhost:8081) (configure `FORWARD_PHPMYADMIN_PORT` para alterar a porta) já apontando para o container MySQL utilizando as credenciais `DB_USERNAME`/`DB_PASSWORD` definidas no `.env` (por padrão `menu`/`secret`).

> **Observação:** em máquinas sem Docker Desktop o `make setup` tentará iniciar o Colima automaticamente. Caso nenhum engine esteja ativo, o comando instruirá a iniciar o serviço manualmente.

## Requisitos manuais (caso precise)

- PHP 8.1+ com extensões `pdo_mysql`, `intl`, `mbstring`, `zip`.
- Composer 2.6+.
- Node.js 18+ (opcional, apenas se houver assets front-end).
- Docker Desktop **ou** Colima + Docker CLI.
- Make.

Todas as dependências para macOS estão listadas no `Brewfile` e são instaladas automaticamente pelo `make setup`.

## Migrações e seeds

- `bin/migrate` cria/atualiza o schema usando os arquivos `database/migrations/*.sql`.
- `bin/seed` aplica os dados iniciais definidos em `database/seeds/*.sql`.

Ambos são executados pelo `make setup`, mas podem ser chamados manualmente via `docker compose run --rm app php bin/migrate` e `docker compose run --rm app php bin/seed`.

## Executando com Docker Desktop

1. Certifique-se de que o Docker esteja rodando.
2. Execute `make setup` (ou `make docker-up` para subir novamente depois de configurado).
3. Use `make logs` para acompanhar os logs em tempo real.
4. Para desligar os serviços utilize `make down`.

## Executando com Colima

O `make setup` já tenta inicializar o Colima automaticamente. Caso queira controlar manualmente:

```bash
colima start --cpu 4 --memory 4 --disk 40
make docker-up
```

Quando terminar o trabalho, desligue o ambiente com `make down` seguido de `colima stop`.

## Executando sem Docker (XAMPP/local)

1. Garanta que um servidor MySQL esteja disponível e atualize o `.env` com as credenciais locais.
2. Execute `composer install` manualmente.
3. Rode as migrações e seeds localmente:

   ```bash
   php bin/migrate
   php bin/seed
   ```

4. Sirva o projeto apontando para `public/`. Você pode usar:

   ```bash
   make xampp
   ```

   O alvo utiliza o servidor embutido do PHP (`php -S 127.0.0.1:8000 -t public`).

> Para ambientes XAMPP/MAMP basta apontar o DocumentRoot para o diretório `public/` e garantir que o PHP CLI utilize as mesmas extensões configuradas no servidor.


## Qualidade de código

- `composer lint` — verifica estilo com PHP-CS-Fixer (PSR-12)
- `composer analyse` — análise estática com PHPStan nível 8
- `composer test` — executa a suíte de testes (PHPUnit)
- `composer quality` — executa todos os comandos acima em sequência

Os hooks configurados via GrumPHP rodam `composer lint`, `composer analyse` e `composer test` automaticamente antes de cada commit.
## Estrutura

```
app/
  Application/Services   # Camada de serviços
  Core                   # Infraestrutura (Router, Controller, Database, Auth)
  Domain/Models          # Regras de negócio e repositórios
  Http/Controllers       # Camada de apresentação
  Support                # Utilidades, Config e helpers
config/                  # Arquivos de configuração centralizados
public/                  # Front controller
routes/                  # Definições de rotas
storage/logs             # Arquivos de log
tests/                   # Testes automatizados
```

## Scripts úteis

- `composer format` — aplica correções automáticas de estilo
- `vendor/bin/php-cs-fixer fix` — execução direta do fixer
- `vendor/bin/phpstan analyse` — análise estática manual
- `vendor/bin/phpunit` — executa os testes com cobertura

## Observabilidade e logs

Os logs são enviados para `storage/logs/app.log` via Monolog. Certifique-se de que o diretório possua permissões de escrita.

## Segurança

- Uso obrigatório de `password_hash()` e `password_verify()`
- Todas as consultas utilizam `PDO` com prepared statements
- Sanitização básica e validações com `respect/validation`

## Documentação adicional

- `.env.example` descreve as variáveis suportadas
- `phpstan.neon` e `.php-cs-fixer.dist.php` definem as regras de qualidade
- `.pre-commit-config.yaml` garante a execução dos validadores antes de cada commit
