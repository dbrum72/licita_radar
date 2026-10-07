# Contexto de retomada — LicitaRadar

Atualizado em: 07/10/2026. Fuso: America/Sao_Paulo.

Leia este documento junto de [CRONOGRAMA.md](CRONOGRAMA.md), [AGENTS.md](AGENTS.md) e `../README.md` ao retomar o projeto. Ele registra as decisões e o estado conhecido; confirme o estado atual do código, banco e processos antes de continuar. Não contém senhas ou outros segredos.

## Objetivo e escopo acordado

LicitaRadar é uma aplicação web Laravel + Vue para encontrar oportunidades de elaboração de projetos e anteprojetos de engenharia e arquitetura em todo o Brasil. Execução de obras não faz parte do interesse inicial da empresa.

Fonte inicial única: PNCP, a partir de https://pncp.gov.br/app/editais?pagina=1. A integração usa APIs oficiais em vez de preencher o site por automação de navegador.

Objetivo atualizado em 07/10/2026: em Oportunidades, a busca deve ser orientada exclusivamente pela documentação da empresa — certidões, atestados, RRT e habilitação do CNPJ — confrontada com o objeto do edital. As capacidades e atividades documentadas devem determinar quais oportunidades são apresentadas e recomendadas, com indicação dos documentos e trechos que sustentam a compatibilidade.

Palavras-chave escolhidas manualmente e perfis genéricos não devem constituir uma base independente de busca. Filtros de modalidade, região, valor, órgão e prazo podem refinar as oportunidades compatíveis com o acervo, sem substituir o confronto documental. A região é atualmente filtrada por UF.

Sem documentação suficiente ou legível para sustentar o confronto, o sistema deve indicar a pendência, sem afirmar compatibilidade. Compatibilidade com o objeto não equivale à habilitação integral: a revisão dos requisitos do edital e da validade das evidências continua necessária.

Estado da implementação em 07/10/2026: a coleta e a seleção em Oportunidades usam exclusivamente o acervo válido e legível da empresa. Perfis definem refinamentos e destinatários dos alertas; suas palavras-chave antigas não determinam a busca. A implementação passou por testes automatizados; homologação com PDFs reais e consulta operacional continua pendente.

O confronto é textual e conservador, com vocabulário explícito de especialidades em `CompanyScopeService`: arquitetura, engenharia, estrutural, elétrica, hidrossanitária, urbanismo, paisagismo, topografia, geotecnia, pavimentação, drenagem, incêndio, climatização e saneamento. Os CNAEs 7111-1/00 e 7112-0/00 também identificam arquitetura e engenharia. Todas as especialidades reconhecidas no objeto precisam de apoio em páginas do acervo. Isso não avalia integralmente quantitativos, complexidade, negativas no texto ou exigências jurídicas; ampliar/calibrar o vocabulário depende dos exemplos reais.

Somente documentos dos quatro tipos acordados com status `ready`, páginas com texto suficiente e validade até o prazo do edital sustentam o confronto. Não é obrigatório possuir um arquivo de cada tipo para a triagem; certidões genéricas sem especialidade não geram compatibilidade. Sem acervo utilizável, a consulta não chama o PNCP e registra orientação no histórico, sem avançar o marco de sucesso. A listagem traz aviso sobre a base documental mesmo quando vazia.

Resultados existentes são reavaliados a cada listagem, painel, detalhe e resumo, incluindo alterações no acervo e vencimentos. A coleta é incremental: somente identificadores PNCP ainda não consultados, com prazo estritamente posterior ao instante da importação e compatibilidade documental, geram oportunidades. Registros existentes não são sobrescritos, reassociados nem importados novamente. A coleta percorre as modalidades configuradas mesmo sem perfis e usa a rota oficial `/contratacoes/proposta` para recebimento de propostas aberto, com limite superior configurável de 365 dias (`proposal_days`). A antiga janela de sete dias de publicação e sobreposição de dois dias não é mais usada.

`pncp_consultations` mantém somente o identificador PNCP e a data da primeira consulta, independentemente do cadastro da oportunidade. Inclui os editais recebidos e avaliados, mesmo se recusados por prazo, dados insuficientes ou incompatibilidade. A gravação do identificador e a eventual importação ocorrem na mesma transação, permitindo nova tentativa em caso de erro de persistência. Alterações posteriores no acervo ou no edital não reimportam identificadores já consultados; atualização de editais conhecidos deixou de fazer parte da sincronização, conforme a regra de coleta incremental acordada.

Oportunidades descartadas podem ser restauradas somente antes do vencimento. No instante do vencimento ou depois, a limpeza exclui a oportunidade e seus vínculos de banco (descarte, anexos cadastrados, requisitos e associações com perfis), preservando o acervo da empresa e o identificador PNCP consultado. Assim, nem a posterior prorrogação do prazo no PNCP permite reimportação. Arquivos PDF privados em disco não são apagados por essa limpeza de banco. Oportunidades vencidas que não foram descartadas permanecem no banco, mas não aparecem na listagem normal nem podem ser exportadas ou gerar buscas de anexos. Prazo ausente ou inválido impede importação; restauração de registro antigo sem prazo também é bloqueada.

A limpeza roda antes das consultas, ao abrir painel/listagem/detalhe e pelo comando `radar:prune`. O scheduler Laravel a executa a cada minuto quando ativo; a tarefa Windows diária também a executa antes da coleta às 06:00, mesmo se a coleta falhar. Sem execução do scheduler ou acesso à aplicação, a exclusão ocorre na próxima execução disponível. Jobs de anexos de oportunidades já excluídas terminam sem consultar o PNCP.

O sistema deve recomendar oportunidades com base nos requisitos dos editais e nos documentos da empresa, manter histórico e consultar diariamente. Na versão atual, a recomendação é uma triagem e um parecer documental preliminar com revisão humana.

Decisões explícitas do usuário:

- Banco exclusivamente MySQL, nome `licitaRadar_bd`. Não utilizar SQLite na aplicação ou nos testes.
- E-mail de desenvolvimento com Mailpit.
- Apenas recomendar oportunidades nesta etapa.
- Coletar somente editais novos com prazo aberto, sem sobrescrever ou reimportar identificadores consultados. Restaurar descartadas apenas antes do vencimento e excluir seu cadastro do banco após o prazo, preservando o identificador PNCP para deduplicação.
- Orientar a busca de oportunidades exclusivamente por certidões, atestados, RRT e habilitação do CNPJ da empresa, confrontando seu conteúdo com o objeto do edital.
- Análise econômica/mercado/custo-benefício, envio de inscrições/propostas e WhatsApp ficam para o futuro.
- A pasta Design System é referência inicial e não pode ser alterada.
- Documentos da empresa devem ficar em tabela distinta dos editais/anexos.
- Migrations devem criar as tabelas em sua estrutura final, sem `add`, `separate` ou alteração incremental com `Schema::table`. Quando houver mudança estrutural, recriar a definição de criação da tabela, preservando dados existentes.

## Ambiente e organização

- Raiz: `C:\Users\Luciano\code\licitacao`.
- Referência visual, somente leitura: `C:\Users\Luciano\code\Design System`.
- `backend/`: Laravel 13, PHP 8.3+, Sanctum e Smalot PDF Parser.
- `frontend/`: Vue 3, Vite 8 e ícones Lucide.
- `scripts/`: comandos PowerShell para servidor, worker, scheduler e Mailpit.
- `docs/`: instruções em `AGENTS.md`, contexto e cronograma.
- Interface baseada nos tokens e no tema Terra Solar: tons terrosos, creme e verde.
- O build Vue é gerado em `backend/public/app`; Laravel serve a interface e a API na mesma origem.
- Aplicação local: http://127.0.0.1:8000.
- Mailpit: SMTP `127.0.0.1:1025`; interface http://127.0.0.1:8025.
- MySQL local: `127.0.0.1:3306`, usuário configurado `root`. As credenciais estão exclusivamente em `backend/.env`, ignorado pelo Git. Nunca copiar a senha para documentos, testes ou respostas.
- Banco isolado dos testes: `licitaRadar_test_bd`. A suíte pode recriá-lo; nunca apontar testes para `licitaRadar_bd`.

## Funcionalidades implementadas

- Primeiro acesso local para criar usuário, autenticação por sessão, CSRF e restrição dos endpoints privados.
- Painel com contagens, consultas recentes e vencimentos documentais.
- Perfis de busca ativos/pausados, filtros, paginação e oportunidades salvas.
- Descarte reversível até o vencimento, compartilhado pela empresa, com usuário/data. Descartadas vencidas são excluídas do banco, mantendo apenas o identificador PNCP para bloquear reimportação. Descartadas não entram nos indicadores, resumos ou novas buscas de anexos.
- Seleção de até 50 oportunidades para um PDF único, mantida ao trocar páginas/filtros durante o uso da tela e limpa ao recarregar ou sair. O PDF contém objeto, órgão, local, modalidade, valor, prazo, publicação, link PNCP e evidências por documento/página. Compatibilidade e descarte são revalidados no servidor antes da exportação; seleção inválida é recusada integralmente.
- Cliente PNCP paginado por modalidade, com intervalo entre requisições e espera progressiva para HTTP 429/5xx.
- Importação incremental de editais novos e abertos, com controle independente de identificadores consultados e histórico de execução.
- Triagem do objeto: `relevant`, `review` ou `excluded`, com motivo registrado.
- Confronto entre objeto e especialidades documentadas no acervo, com documento, página e trecho de apoio na listagem e no detalhe. Painel e resumos usam a compatibilidade atual.
- Busca dos anexos oficiais por identificador PNCP, sem seguir URLs arbitrárias ou redirecionamentos externos.
- Upload privado de PDFs de até 20 MB, SHA-256, extração de texto por página e identificação de páginas que precisam de OCR.
- Extração de trechos candidatos a requisitos; sugestão de páginas de documentos da empresa por palavras em comum.
- Revisão dos requisitos com fundamentação, documento/página de evidência, usuário e data. Validade da evidência é conferida até o prazo da oportunidade.
- Parecer documental preliminar. Sem documentos/requisitos suficientes, a conclusão permanece pendente.
- Resumos por e-mail para perfis com endereço cadastrado; marcação dos resultados notificados após envio SMTP.
- Rotina diária às 06:00, horário de Brasília.

Tipos do acervo: `attestation` (atestado), `certificate` (certificação/certidão), `rrt` (Registro de Responsabilidade Técnica - RRT) e `cnpj` (CNPJ). `tender` identifica editais/anexos das oportunidades.

## Banco e relações importantes

Tabelas de domínio: `search_profiles`, `opportunities`, `profile_opportunity`, `documents`, `company_documents`, `requirements`, `sync_runs`, `opportunity_dismissals`, `pncp_consultations`. Existem também tabelas Laravel de usuários, sessões, cache, filas e tokens.

- `documents`: editais e anexos vinculados às oportunidades.
- `company_documents`: acervo da empresa, sem vínculo com uma oportunidade específica.
- `requirements.source_document_id` referencia `documents`.
- `requirements.evidence_document_id` referencia `company_documents`.
- `opportunity_dismissals`: um registro por oportunidade descartada, com usuário/data. Restauração remove apenas esse registro de decisão.
- `pncp_consultations`: identificador PNCP único e primeira consulta, sem chave estrangeira para oportunidades; permanece após a exclusão de descartadas vencidas. A migration preenche os identificadores das oportunidades preexistentes sem alterar seus dados.
- Modelos: `Document`, `CompanyDocument`, `Opportunity`, `Requirement`, `SearchProfile`, `SyncRun`.
- `CompanyDocument` herda as propriedades comuns de `Document`, mas usa explicitamente a tabela `company_documents`.
- IDs iguais nas duas tabelas são possíveis. `/api/documents/{id}` lê documentos da empresa; `/api/tender-documents/{id}` lê os editais. A interface distingue essas rotas.

Migrations de domínio atuais, na ordem de criação:

1. `2026_10_06_000001_create_search_profiles_table.php`
2. `2026_10_06_000002_create_opportunities_table.php`
3. `2026_10_06_000003_create_profile_opportunity_table.php`
4. `2026_10_06_000004_create_documents_table.php`
5. `2026_10_06_000005_create_company_documents_table.php`
6. `2026_10_06_000006_create_requirements_table.php`
7. `2026_10_06_000007_create_sync_runs_table.php`
8. `2026_10_07_000001_create_opportunity_dismissals_table.php`
9. `2026_10_07_000002_create_pncp_consultations_table.php`

As migrations antigas `create_radar_tables`, `add_attachment_status` e `separate_company_documents` foram removidas e o histórico do banco foi consolidado. O script pontual de reconciliação foi removido após execução. Há backup dos registros antigos do histórico em `backend/storage/app/private/migration-history-*.json`. Na última verificação, todas as migrations estavam aplicadas e `artisan migrate` informou que não havia pendências. Não executar `migrate:fresh` no banco da aplicação para resolver conflitos de histórico.

## Arquivos centrais

- `backend/app/Http/Controllers/RadarController.php`: autenticação, painel, perfis, documentos, oportunidades e revisão.
- `backend/app/Services/PncpClient.php`: HTTP e mapeamento PNCP.
- `backend/app/Services/SyncService.php`: coleta, filtros, persistência e filas de anexos.
- `backend/app/Services/ScopeClassifier.php`: heurísticas de escopo e filtros de perfil.
- `backend/app/Services/CompanyScopeService.php`: confronto documental do objeto, evidências e seleção baseada no acervo atual.
- `backend/app/Services/OpportunityLifecycleService.php`: exclusão transacional de descartadas vencidas e preservação dos identificadores consultados.
- `backend/app/Services/OpportunityPdfService.php` e `backend/resources/views/pdf/opportunities.blade.php`: relatório PDF com Dompdf, acesso autenticado, recursos remotos desativados e sem armazenamento público dos arquivos exportados.
- `backend/app/Services/DocumentService.php`: extração e evidências candidatas.
- `backend/app/Services/DigestService.php`: resumos por e-mail.
- `backend/app/Jobs/SyncPncp.php` e `FetchTenderDocuments.php`: processamento assíncrono.
- `backend/routes/web.php`: API com sessão/CSRF e entrega da interface.
- `backend/routes/console.php`: comandos operacionais e agendamento Laravel.
- `backend/config/radar.php`: modalidades, janela inicial, horário e intervalo de chamadas.
- `frontend/src/App.vue`: telas e fluxos da interface.
- `frontend/src/api.js`: requisições com sessão e CSRF.

## Execução e validação

Na raiz, use terminais separados conforme a necessidade:

```powershell
./scripts/start.ps1
./scripts/worker.ps1
./scripts/mailpit.ps1
```

O servidor usa diretamente o servidor PHP com router Laravel. Isso evita a perda da configuração local de PHP observada anteriormente com um subprocesso de `artisan serve`.

No Windows já foi registrada a tarefa `LicitaRadar - Consulta diaria PNCP`, às 06:00. Ela executa `scripts/daily.ps1`, consulta o PNCP e processa a fila. Exige computador ligado e sessão iniciada; Mailpit deve estar ativo para capturar os resumos. `scripts/install-schedule.ps1` registra essa tarefa. `scripts/scheduler.ps1` é uma alternativa manual; não manter ambos os agendadores ativos simultaneamente.

Dentro de `backend`:

```powershell
../scripts/php.ps1 artisan radar:status
../scripts/php.ps1 artisan radar:prune
../scripts/php.ps1 artisan migrate:status
../scripts/php.ps1 artisan radar:sync
../scripts/php.ps1 artisan radar:attachments
../scripts/php.ps1 artisan radar:digest
../scripts/php.ps1 artisan radar:mail-test
../scripts/php.ps1 artisan test
```

O comando `radar:mail-test` só aceita Mailpit local na porta 1025. Uma mensagem de teste foi enviada e conferida na API do Mailpit. O resumo real requer endereço no perfil.

Dentro de `frontend`: `npm run build`. Validação em 07/10/2026 da busca orientada pelo acervo: **21 testes aprovados, 78 assertions**, no MySQL isolado de testes; build Vue aprovado. Casos cobertos: ausência de acervo, validade até o prazo, OCR, certidões genéricas, especialidades incompatíveis, coleta sem perfis, palavras-chave antigas ignoradas, refinamento de alertas e atualização incompatível do objeto preservando histórico/salvos. Nenhuma migration ou dado do banco da aplicação foi alterado. O build pode exigir permissão para criar subprocessos no ambiente restrito do Codex; não confundir esse bloqueio com erro da aplicação.

Validação posterior em 07/10/2026 de descarte e exportação: **26 testes aprovados, 116 assertions**, build Vue aprovado e revisão visual de todas as quatro páginas de um PDF de teste com objetos/trechos longos, acentos e valor ausente. Cobertura inclui autenticação, descarte repetido/restauração, preservação em nova sincronização, indicadores/resumos, PDF com somente as selecionadas e validação de seleções vazias, duplicadas, inexistentes, descartadas ou sem compatibilidade atual. Dompdf 3.1.6 instalado via Composer. A nova migration de criação de `opportunity_dismissals` foi aplicada no banco local; contagens e hashes dos registros das sete tabelas de domínio anteriores foram comparados antes/depois e permaneceram idênticos. Nenhuma migration incremental ou alteração de tabelas existentes foi utilizada.

Validação posterior em 07/10/2026 das regras de vencimento e coleta incremental: **34 testes aprovados, 153 assertions**, build Vue aprovado. Testes cobrem o limite exato do vencimento, exclusão em cascata preservando acervo/identificador, execução repetida da limpeza, jobs de registros excluídos, prorrogação no PNCP sem reimportação, coleta de propostas abertas paginada, rejeição de prazo ausente/inválido/encerrado e preenchimento dos identificadores existentes sem alteração de oportunidades. Migration de criação de `pncp_consultations` aplicada no banco local. Limpeza inicial executada: nenhuma descartada vencida encontrada naquele instante. Consulta real completa pela nova rota ainda precisa de homologação; especificação oficial de parâmetros conferida em `https://pncp.gov.br/api/consulta/v3/api-docs`.

Prazos com fuso explícito são normalizados para `America/Sao_Paulo` preservando o instante, com teste específico. Conferência local após a migration: 891 oportunidades, 891 identificadores registrados e nenhuma oportunidade sem identificador no controle de consultas. Esses números são snapshot de 07/10/2026 e não métricas permanentes.

## Estado observado em 06/10/2026

Snapshot obtido por `radar:status` ao preparar esta documentação:

- 519 oportunidades no MySQL.
- 13 editais/anexos em `documents`.
- 3 documentos da empresa em `company_documents`.
- Nenhum documento da empresa restante em `documents`.
- 122 jobs na fila.
- Busca de anexos: 486 pendentes, 23 falhas, 1 em execução, 6 prontas e 3 parciais.
- Consulta #1 terminou com **falha** às 17:59:33, preservando 519 resultados parciais.

Esses números são um snapshot, não métricas permanentes. Confirmar novamente antes de reportar progresso. Processos iniciados durante o chat podem ter encerrado; portas, workers, Mailpit e fila precisam ser verificados na retomada.

## Limitações e próximo trabalho

Prioridade após implementação em 07/10/2026: homologar a seleção documental com o acervo real da empresa e objetos reais do PNCP, ajustando o vocabulário e identificando falsos positivos/negativos. Reiniciar workers existentes para carregar o código atualizado antes da próxima rodada operacional. A validação automatizada não substitui essa homologação.

1. Diagnosticar o erro da última consulta nos logs privados e concluir a homologação de uma rodada PNCP. Já houve HTTP 429 e bloqueio de rede do ambiente restrito em tentativas anteriores; não atribuir a falha atual a uma causa sem ler o log correspondente.
2. Conferir anexos pendentes/falhos e worker em execução. Reiniciar workers após alterações relevantes de código.
3. Validar PDFs reais da empresa e um fluxo completo de evidência e revisão. Os exemplos prometidos ainda não foram homologados.
4. A coleta consulta propostas abertas com horizonte configurável de 365 dias. Identificadores consultados não são reavaliados para importação nem têm seus objetos/prazos atualizados pela sincronização. Novos perfis recebem associações apenas de novas importações. Homologar a rota de propostas abertas e monitorar os limites do PNCP.
5. Não há OCR automático nem interpretação semântica abrangente. Palavras em comum não comprovam quantitativos, especialidades, registros profissionais ou habilitação integral.
6. Homologar o resumo real no Mailpit e confirmar uma execução diária. Captura de mensagem de teste e registro da tarefa já foram feitos.
7. Produção, SMTP real e etapas futuras dependem de definição posterior do usuário.

## Como manter este contexto

Ao concluir alterações relevantes, atualizar decisões, pendências e resultados de validação neste arquivo e a situação de entrega no cronograma. Não registrar credenciais, não marcar etapas como homologadas apenas porque o código existe e não tratar snapshots antigos como estado atual.
