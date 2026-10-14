# Migrations

- Manter uma migration de criação por tabela, contendo sua estrutura final.
- Não criar migrations incrementais `add`, `separate` nem usar `Schema::table` para alterar tabelas.
- Quando a estrutura mudar, recriar a definição de criação da tabela correspondente.
- Preservar os dados existentes ao sincronizar o banco com migrations reorganizadas.

# Documentação de retomada

- Ao retomar o projeto, consultar `docs/CONTEXT.md` e `docs/CRONOGRAMA.md`.
- Atualizar esses arquivos quando houver mudanças relevantes de escopo, implementação, validação ou pendências.
- Não registrar credenciais na documentação.
