# Multi Menu

Aplicação modular para gerenciamento de cardápios digitais seguindo as diretrizes de arquitetura em camadas (Controllers → Services → Repositories/Domain).

## Requisitos

- PHP 8.1+
- Composer
- Extensões PDO e Mbstring habilitadas
- MySQL/MariaDB para recursos que dependem de banco

## Configuração rápida

```bash
composer install
cp .env.example .env
php -S localhost:8000 -t public
```

As variáveis de ambiente podem ser ajustadas no arquivo `.env`.

## Qualidade de código

- `composer lint` — verifica estilo com PHP-CS-Fixer (PSR-12)
- `composer analyse` — análise estática com PHPStan nível 8
- `composer test` — executa a suíte de testes (PHPUnit)
- `composer quality` — executa todos os comandos acima em sequência

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
