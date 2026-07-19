# Princípios arquiteturais

Exceções a estes princípios devem ser justificadas por ADR.

1. **Monólito modular:** manter uma aplicação única com limites explícitos de
   domínio enquanto isso atender à operação e à equipe.
2. **Evolução incremental:** preservar funcionalidades estáveis e evitar
   reescritas totais.
3. **Domínio antes de pastas:** responsabilidades e contratos são definidos
   antes de namespaces, menus ou diretórios.
4. **Fonte única de verdade:** cada informação possui proprietário e origem
   canônica; cópias são projeções reconstruíveis.
5. **Pessoa não é conta:** identidade, autenticação, vínculo e lotação são
   conceitos diferentes.
6. **Negar por padrão:** ausência de política autorizadora resulta em negação.
7. **Autorização por objeto:** ACL de página não concede acesso automático a
   qualquer registro.
8. **Segurança e privacidade por padrão:** mínimo privilégio, arquivos privados,
   finalidade, retenção e exclusão verificáveis.
9. **Histórico para fatos relevantes:** estados mutáveis não substituem trilha
   de movimentações, decisões e aprovações.
10. **Eventos versionados:** fatos publicados possuem contrato, produtor,
    consumidores, idempotência e correlação.
11. **Eventos não substituem transações:** alterações atômicas são concluídas
    localmente; publicação ocorre após commit, preferencialmente por outbox.
12. **Integrações por contrato:** um domínio não depende de detalhes internos de
    outro.
13. **Expand/Contract:** migrations estruturais expandem, migram consumidores e
    somente depois removem legado.
14. **Compatibilidade retroativa:** preservar slugs, IDs e contratos sempre que
    possível durante a migração.
15. **Testes proporcionais ao risco:** identidade, autorização, LGPD, cálculos,
    integrações e estados exigem testes automatizados.
16. **Observabilidade faz parte da entrega:** fluxos críticos possuem logs,
    métricas, correlação e recuperação.
17. **Mercado como referência:** funcionalidades de Senior, Gupy, Sólides,
    Factorial, SAP ou outras soluções só entram quando gerarem valor local.
18. **Documentação sem duplicidade:** manual, funcional, técnico e ADR possuem
    responsabilidades distintas e referências canônicas.
19. **Deploy não apaga dados de runtime:** o pipeline envia código; nunca
    remove nem sobrescreve uploads, anexos, `.env`, cache de produção ou
    storage privado. Exclusões FTP e `.gitignore` são controles obrigatórios
    (ver `docs/DEPLOY_PRODUCAO.md`).
20. **Banco fora do deploy automático:** migrations em produção são manuais,
    com backup e plano de rollback; o workflow FTP não executa Phinx.

## Restrições atuais reconhecidas

- aplicação PHP com PSR-4 `App\` e `Routes\`;
- roteamento, menu e ACL dependentes de `adms_pages` e cadastros relacionados;
- banco MySQL e migrations Phinx;
- `adms_users` mistura conta, pessoa, vínculo e posição;
- testes automatizados insuficientes;
- documentos históricos podem divergir do código;
- mudanças de namespace possuem alto risco e baixo valor imediato.

Essas restrições orientam a migração, mas não são consideradas arquitetura-alvo.
