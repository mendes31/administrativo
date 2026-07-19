# Testes automatizados

## Execução

```bash
composer install
php vendor/bin/phpunit --configuration phpunit.xml.dist
```

## Segurança da suíte inicial

Os testes iniciais não carregam `.env`, não conectam ao banco e não alteram
sessões ou dados de produção. Eles cobrem regras puras e caracterizam contratos
atuais de login, sessão, ACL e Portal pela estrutura versionada do código.

## Suítes

- `Unit`: validação de login, URL de retorno, CSRF e níveis de acesso;
- `Characterization`: contratos estruturais de login, persistência de sessão,
  roteamento/ACL e vínculo do Portal ao colaborador autenticado.

## Evolução

Testes que exigirem banco devem usar ambiente isolado, schema próprio de teste e
fixtures determinísticas. É proibido apontar a suíte para a base de produção.
