# LicitaRadar

Aplicação Laravel 13 + Vue 3 para monitorar licitações de projetos e anteprojetos de engenharia e arquitetura no PNCP, selecionando oportunidades exclusivamente pelo confronto do objeto com certidões, atestados, RRT e habilitação do CNPJ da empresa. Interface baseada nos tokens e no tema Terra Solar da pasta `Design System`, que é somente referência e não foi alterada.

Documentação do projeto: [cronograma de entregas](docs/CRONOGRAMA.md) e [contexto de retomada](docs/CONTEXT.md).

Em Oportunidades, use **Descartar** para retirar um item da seleção normal. Marque o filtro **Descartadas** e aplique os filtros para consultar essa lista e restaurar itens; o histórico e os documentos são preservados. O descarte é compartilhado pelos usuários da empresa e continua válido após novas consultas. Para exportar, marque **Selecionar para PDF** em cada oportunidade ou **Selecionar esta página**, depois clique em **Exportar selecionadas em PDF**. Um único arquivo reúne até 50 itens, com dados do edital, link PNCP e evidências documentais. A seleção acompanha páginas e filtros, mas é limpa ao recarregar a tela ou sair. A exportação exige compatibilidade atual e recusa itens descartados ou sem evidência suficiente.

## Executar no Windows

Requisitos: PHP 8.3+, Composer e Node 22.18+ (ou versão compatível com Vite 8). As dependências instaladas e o banco local estão disponíveis neste ambiente.

```powershell
# Primeiro terminal, na raiz do projeto
./scripts/start.ps1
# Segundo terminal: processa a consulta iniciada pelo painel
./scripts/worker.ps1
# Terceiro terminal: mantém a rotina diária em execução
./scripts/scheduler.ps1
```

Acesse http://127.0.0.1:8000. No primeiro acesso local, crie o administrador com senha de pelo menos 12 caracteres. Não existe senha padrão. A aplicação é para uma única empresa: usuários autenticados compartilham o acervo, as oportunidades e os perfis.

Os scripts ajustam os limites de upload apenas no processo PHP, sem alterar o `php.ini` global. O banco é exclusivamente MySQL e se chama `licitaRadar_bd`. Configure as credenciais em `backend/.env`. Não há resultados fictícios no banco.

### Instalação limpa

```powershell
cd backend
composer install
Copy-Item .env.radar.example .env
../scripts/php.ps1 artisan key:generate
../scripts/php.ps1 artisan radar:database
../scripts/php.ps1 artisan migrate --seed
cd ../frontend
npm ci
npm run build
```

Para desenvolvimento do Vue: `npm run dev` em `frontend`, com Laravel ativo na porta 8000; abra o endereço impresso pelo Vite com o caminho `/app/`.

## Banco e integração

Migrações incluem usuários, sessões, filas, cache, oportunidades (ID PNCP único), perfis e seus resultados, documentos privados com SHA-256, páginas extraídas, requisitos, evidências com revisão identificada e histórico de consultas. Documentos da empresa ficam exclusivamente em `company_documents` (atestados, certificações, RRT e CNPJ); `documents` guarda editais e anexos de oportunidades. Cada tabela possui uma migration de criação com sua estrutura final, sem migrations incrementais add/separate. Alterações futuras devem recriar a definição de criação da tabela. O banco existente teve seu histórico consolidado com preservação dos dados e vínculos. A origem do requisito referencia `documents`; a evidência referencia `company_documents`. Os endpoints `/api/documents/{id}` leem o acervo da empresa, e `/api/tender-documents/{id}` leem os editais, evitando ambiguidade entre IDs iguais nas duas tabelas. Valores monetários usam decimal. Datas seguem `America/Sao_Paulo`.

Configure `DB_CONNECTION=mysql`, `DB_DATABASE=licitaRadar_bd`, `DB_HOST`, `DB_PORT`, `DB_USERNAME` e `DB_PASSWORD` no `.env`, execute `php artisan radar:database` e depois as migrações. Credenciais de produção não estão incluídas. Se o usuário não tiver permissão CREATE, peça ao administrador do MySQL para criar o banco com charset utf8mb4 e collation utf8mb4_unicode_ci.

O cliente usa a API oficial `https://pncp.gov.br/api/consulta/v1/contratacoes/publicacao`, paginada pelas modalidades configuradas, mesmo sem perfis. Na primeira consulta busca sete dias; nas seguintes usa a última consulta bem-sucedida com sobreposição de dois dias. O acervo válido e legível determina a importação e a apresentação das oportunidades, com documento, página e trecho de apoio. Sem acervo utilizável, a consulta não é iniciada. Perfis refinam alertas por UF, modalidade, órgão, valor e prazo; palavras-chave antigas são ignoradas. Os filtros do painel refinam apenas resultados compatíveis. Mudanças no acervo reavaliam os resultados existentes, mas não recuperam publicações antigas fora da janela de coleta.

O confronto usa vocabulário explícito de especialidades e reconhecimento inicial dos CNAEs de arquitetura e engenharia. Todas as especialidades reconhecidas no objeto precisam de evidência em documentos `ready`, com validade até o prazo do edital. Certidões genéricas, documentos vencidos ou dependentes de OCR não sustentam compatibilidade. O confronto é textual e preliminar: não comprova quantitativos, complexidade ou habilitação integral e precisa ser calibrado com PDFs reais. Painel, detalhe e resumos por e-mail também reavaliam o acervo atual.

A triagem textual do objeto classifica escopo como aderente, revisão ou inclui obra. É uma heurística conservadora, não uma interpretação integral do edital. Lotes separados e termos ambíguos precisam de revisão.

## Documentos e parecer

PDFs textuais de até 20 MB são extraídos por página. O radar enfileira a busca dos anexos oficiais para oportunidades aderentes. O worker baixa PDFs usando identificadores PNCP, impede redirecionamentos externos e sinaliza anexos em outros formatos. No detalhe da oportunidade é possível solicitar nova busca ou adicionar PDFs manualmente. O sistema identifica trechos candidatos a exigências e sugere páginas de atestados/certificações por palavras em comum. A aprovação de um requisito é manual, exige fundamentação, documento da empresa e página existente; documentos vencidos até o prazo da licitação são recusados.

Não há OCR automático nesta versão. PDFs digitalizados, páginas vazias ou extração parcial são sinalizados como `needs_ocr`; nunca resultam automaticamente em habilitação confirmada. Revise integralmente os anexos e cadastre requisitos que a extração tenha omitido. A conclusão “Requisitos cadastrados revisados” descreve somente os itens revisados, não garante habilitação. A interpretação precisa ser calibrada com os PDFs reais que serão fornecidos.

Análise econômica, envio de propostas/inscrição e WhatsApp estão fora desta etapa, conforme o escopo acordado.

## Automação e e-mail

Rotina Laravel agendada às 06:00 (Brasília). No Windows, `./scripts/install-schedule.ps1` instala a tarefa `LicitaRadar - Consulta diaria PNCP`: consulta o PNCP e processa os PDFs sem precisar manter terminais abertos. A tarefa usa o usuário atual e requer sessão iniciada; o computador precisa estar ligado. Não use a tarefa Windows e `scheduler.ps1` simultaneamente. A opção StartWhenAvailable executa uma rodada perdida ao voltar a ficar disponível. Em servidor Linux, configure cron a cada minuto para `php artisan schedule:run`; use um supervisor para o worker `php artisan queue:work --timeout=21000 --tries=1`.

Para executar imediatamente: `../scripts/php.ps1 artisan radar:sync` dentro de `backend`. Para conferir contagens e o histórico mais recente: `../scripts/php.ps1 artisan radar:status`. Para reenfileirar anexos pendentes/falhos: `../scripts/php.ps1 artisan radar:attachments`, seguido do worker. E-mails só são enviados para endereços cadastrados em perfis ativos, com oportunidades abertas classificadas como aderentes. O desenvolvimento usa Mailpit local: `MAIL_MAILER=smtp`, `MAIL_HOST=127.0.0.1`, `MAIL_PORT=1025`, usuário/senha `null`, esquema `smtp` e remetente `radar@licitaradar.test`. Inicie com `./scripts/mailpit.ps1` na raiz e abra http://127.0.0.1:8025 para ler as mensagens capturadas. Dentro de `backend`, `../scripts/php.ps1 artisan radar:mail-test` envia uma mensagem de verificação somente ao Mailpit local. Cadastre qualquer endereço de desenvolvimento no perfil para testar o resumo de oportunidades. O Mailpit precisa estar ativo durante a consulta diária para capturar o resumo; caso indisponível, os resultados permanecem sem marca de notificação e `radar:digest` permite nova tentativa. Em produção configure um SMTP real. O driver `log` pode ser usado para desativar envios e não marca resultados como entregues. Resultados já notificados não são repetidos normalmente. Em caso de interrupção entre a entrega SMTP e a gravação no banco, pode haver repetição; o SMTP não oferece transação conjunta com o banco.

Em produção use HTTPS, `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, backup do banco e armazenamento privado. Se necessário crie usuários via `php artisan radar:user email@empresa.com`. A configuração inicial via web só aceita acesso de loopback enquanto não há usuários.

## Validação

```powershell
cd backend
../scripts/php.ps1 artisan radar:database --testing
../scripts/php.ps1 artisan test
cd ../frontend
npm run build
```

Os testes usam exclusivamente o banco MySQL isolado `licitaRadar_test_bd`, que pode ser recriado pela suíte. Nunca aponte os testes para o banco da aplicação. Eles cobrem acesso privado, triagem de escopo, prazo de validade de evidências, parecer pendente, paginação/deduplicação de PNCP, preservação de salvos, falhas externas e validação de perfis. A consulta real depende da disponibilidade e limites do PNCP. Há espaçamento entre chamadas e espera progressiva para HTTP 429/5xx; a falha preserva resultados parciais sem avançar o marco da última consulta bem-sucedida. SMTP e PDFs reais ainda precisam de validação com os dados da empresa.

Documentação oficial: https://pncp.gov.br/api/consulta/swagger-ui/index.html
