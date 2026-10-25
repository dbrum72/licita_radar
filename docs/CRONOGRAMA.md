# Cronograma de entregas — LicitaRadar

21/10/2026 — Diagnóstico real do 502 documental: retomada da oportunidade 165 reutilizou três lotes salvos e falhou no quarto com referência a documento ausente/fora do conjunto. O contexto dos lotes listava documentos globais; agora cada extração recebe apenas metadados dos documentos do próprio lote. Referências a arquivo ausente, origem inadequada (edital/acervo) ou página inexistente são removidas e tornam as conclusões explicitamente inconclusivas. Avisos de referências inválidas das extrações são propagados deterministicamente à validação final, sem depender de a IA preservá-los. Não se aceita evidência inválida como comprovação. Logging de etapa e regras de formato sem conteúdo documental. Validação: 22 testes documentais aprovados, teste adicional de consolidação aprovado; execução real pós-correção concluída às 18h45: oportunidade 165, 32 documentos, cinco lotes, três reaproveitados e três chamadas nesta tentativa, relatório salvo com 44 exigências. Conferência posterior com chamadas externas bloqueadas retornou cached=true e stale=false; progresso temporário removido após gravação. Relatório apresenta 159 avisos e conclusões inconclusivas por referências não confirmadas; é resultado com pendências, não comprovação de habilitação. Diagnóstico inicial também consumiu uma chamada no quarto lote antes da correção. Pint e diff conferidos.

21/10/2026 — Visão Geral não dispara mais consultas PNCP: removido botão Consultar PNCP do cabeçalho; ação do estado vazio agora navega para Oportunidades, onde ficam os perfis de busca. Orientações atualizadas; atualização do histórico preservada. Validação: cinco testes das telas Visão Geral/Oportunidades aprovados; formatação e diff conferidos.

21/10/2026 — Remoção efetiva da camada HTTP legada: http.js contém somente instância Axios e interceptadores JWT/401 (25 linhas). Removidos helper api, normalização de respostas, status/fields/retryAfter artificiais e API_TIMEOUT. Módulos de domínio consomem response.data diretamente; login/setup armazenam token. Interface lê error.response e usa apiErrorMessage apenas para apresentação (incluindo erros JSON de download). Leituras usam 15s por padrão; ações POST manuais explicitam timeout zero. Validação: build aprovado; 40 arquivos de testes aprovados na suíte e os dois restantes aprovados após atualização das expectativas para Axios nativo (244 testes no total); diff e formatação conferidos.

21/10/2026 — Limpeza do cliente HTTP: removida tradução das opções legadas body/timeoutMs; módulos de domínio usam data/timeout nativos do Axios. Autenticação, respostas e erros centralizados nos interceptadores, inclusive para uso direto da instância. Helper api apenas devolve data e preserva timeout zero para operações manuais longas. Comunicação do frontend com backend exclusivamente por Axios. Validação: 244 testes em 42 arquivos, build Vite, Prettier e diff aprovados. Testes repetidos fora do sandbox após EPERM no cache temporário do Vitest.

Validação do cliente Axios em 21/10/2026: 243 testes frontend/42 arquivos, build Vite, Prettier dos arquivos alterados e diff aprovados; sem chamadas reais aos serviços externos.

21/10/2026 — Cliente HTTP do frontend migrado para Axios a partir de `legalis/frontend/src/api/client.js`, conforme confirmação do usuário. Preservados contratos da API do LicitaRadar, JWT, empresa única, uploads/downloads e timeouts explícitos de leitura versus ações manuais longas. Legalis somente lido.

Validação do teto de chamadas: 22 testes/158 assertions aprovados com IA simulada no MySQL isolado; diff sem erros.

20/10/2026 — Teto padrão de chamadas por análise ampliado de 8 para 16 para comportar o conjunto de 10 chamadas reportado pelo usuário, preservando divisão em lotes e limite finito antes do envio. Sem nova chamada real de IA.

Validação da correção dos anexos PNCP: 91 testes/596 assertions no MySQL isolado, Pint e diff aprovados; listagem e um download reais também conferidos.

20/10/2026 — Corrigida busca de anexos PNCP: novas tentativas limitadas para falhas de conexão, timeout próprio de download, continuidade após falha individual e restauração de arquivo ausente sem duplicar cadastro. Teste real pelo cliente PHP listou 15 anexos e baixou PDF de 1184178 bytes com HTTP 200, sem persistência no banco/storage da aplicação. Homologação de todos os anexos e estabilidade do PNCP permanece pendente.

20/10/2026 — Falha de transporte após 48,11 segundos com limite de 600 durante consolidação: separado diagnóstico de timeout e erro de conexão, com estatísticas cURL seguras. Lotes validados passam a ser preservados no storage privado para retomada manual, invalidados por mudanças de contexto/PDF/configuração e removidos após salvar relatório final. 22 testes/158 assertions, Pint e diff aprovados. Teste real de conectividade sem chave retornou 401; geração com IA segue sem homologação nesta alteração.

20/10/2026 — Ajustados tempo e lotes da análise: timeout padrão de 600 segundos por chamada; novo orçamento independente de 100 mil caracteres para lotes de extração, mantendo consolidação de 500 mil e máximo de chamadas. Medição local confirma conjuntos extensos antes enviados em chamada única. Adicionado diagnóstico seguro de duração do transporte. 20 testes/136 assertions no MySQL isolado com IA simulada, Pint e diff aprovados; geração real ainda não homologada.

Validação da correção de citações: 19 testes/124 assertions no MySQL isolado, Pint e diff aprovados; provedor simulado.

20/10/2026 — Validação de citações por IA: diferenças tipográficas normalizadas; trechos não encontrados removidos das evidências e identificados em avisos, com resumo/escopo/exigências marcados como inconclusivos para revisão. Documento fora do conjunto e página inexistente continuam bloqueando a resposta. Sem nova chamada real ou consumo de créditos nesta correção.

Validação das respostas incompletas da IA: 17 testes/115 assertions no MySQL isolado e Pint aprovados, com provedor simulado.

20/10/2026 — Respostas incompletas da IA passam a distinguir limite de saída e filtro de conteúdo, com log seguro de motivo e tokens. Teto padrão ampliado para 32768 tokens, compartilhado entre raciocínio e relatório. Preservação do relatório e ausência de repetição automática mantidas. Motivo da tentativa original não recuperável pelos logs anteriores; sem nova chamada real nesta alteração.

20/10/2026 — Diagnóstico de novas falhas de análise: OpenAI retornou 429, antes convertido em 502 genérico. Tratamento distingue cota/faturamento de limite temporário e orienta a ação correspondente, com diagnóstico seguro no log. 15 testes/103 assertions no MySQL isolado e Pint aprovados; sem chamadas reais de IA. Homologação depende da disponibilidade dos limites/cota do provedor.

## Análise por IA: limite de execução PHP — 20/10/2026

Corrigido encerramento fatal aos 30 segundos durante análise documental por IA, que chegava ao navegador como erro 500 sem CORS. A operação recebe orçamento finito baseado no timeout e máximo de chamadas configurados, com margem para PDFs e armazenamento; limite anterior restaurado ao terminar. Regressão cobre timeout do provedor com resposta 504 e CORS, sem repetição, liberação da trava e ausência de relatório parcial. Validação: 13 testes e 93 assertions aprovados no MySQL isolado; Pint e diff conferidos. Sem chamadas reais de IA ou alterações nos dados da aplicação. Detalhes em CONTEXT.md.

## Consolidação das decisões vigentes — 14/10/2026

Documentação consolidada, com correção do seletor em FRONTEND.md e das referências à execução diária no planejamento. Validação desta atualização: 69 testes PHP/412 assertions no MySQL isolado, 237 testes frontend em 43 arquivos, 2 testes de transporte HTTP, build Vite, Prettier, Pint e git diff --check aprovados. PHP, Vitest e transporte foram repetidos com permissão após restrições do sandbox. Nenhuma mudança de código de aplicação ou migration nesta atualização; sem chamadas PNCP ou SMTP reais, nem nova revisão visual.

Estado de retomada: Laravel API com JWT e serviços por domínio; Vue independente; empresa única; consultas, anexos e resumos exclusivamente manuais. A API local usa Node e quatro processos PHP com capacidade separada para leituras e escritas. Somente Oportunidades exibe a seleção de perfis: todos os selecionados devem ser atendidos; sem seleção, aplica-se apenas o acervo. Identificadores PNCP já consultados não são reavaliados. O dia do prazo permanece aberto integralmente em America/Sao_Paulo, preservando o horário original. Migrations de criação por tabela, MySQL e preservação dos dados são obrigatórios. PNCP, SMTP e PDFs reais continuam pendentes de homologação.

Os registros antigos são históricos: não autorizam retomar jobs, workers, scheduler, autenticação por sessão/CSRF ou vencimento pelo instante exato. Análise econômica, propostas e WhatsApp permanecem fora do MVP. Detalhes operacionais em [API.md](API.md), arquitetura em [BACKEND.md](BACKEND.md) e [FRONTEND.md](FRONTEND.md).

## Arquitetura do backend — 13/10/2026

Aplicados padrões do backend Legalis: serviços por domínio, colaboradores por injeção com propriedades readonly, fluxos de autenticação/registro em serviços específicos, validações em FormRequests, controllers com métodos REST e respostas tipadas, relações Eloquent tipadas e comandos em Console/Commands. Referência somente lida. URLs e contratos do frontend, JWT, empresa única, operação manual e vencimento por data preservados. Sem migrations ou alterações diretas dos dados da aplicação. 69 testes PHP/412 assertions e Pint aprovados; integrações externas simuladas. Detalhes e estrutura vigente em [BACKEND.md](BACKEND.md).


## Regra vigente de vencimento — 13/10/2026

Oportunidades permanecem disponíveis durante toda a data do prazo em America/Sao_Paulo, independentemente do horário informado. Somente datas anteriores ao dia atual são consideradas vencidas. Regra aplicada à importação, listagem, painel, PDF, resumos, parecer, salvar/restaurar, anexos e limpeza de descartadas. Horário original preservado no banco. O filtro de dias mínimos dos perfis também usa dias de calendário. Substitui as comparações históricas pelo instante exato do prazo. Não houve migrations, consultas PNCP reais ou alteração direta dos dados. Validação: 69 testes PHP/412 assertions aprovados no MySQL isolado, Pint e verificação de diff aprovados; regressões cobrem hoje às 23:59:59, virada do dia, listagem, painel, PDF, salvar/restaurar, limpeza e importação com fuso explícito.


08/10/2026 — Seletor de perfis removido da Visão geral e mantido somente em Oportunidades. Consulta manual, seleção em memória, interseção dos perfis e compatibilidade com acervo preservadas.

08/10/2026 — Recuperada nova interrupção da API local; consulta 2 interrompida com resultados preservados. Inicialização autenticada deixa de aguardar painel, e leituras de painel/perfis recebem timeout de 15s. Usuário escolheu servidor HTTP concorrente; implementado Node nativo + quatro processos PHP locais, separando capacidade de leitura e escrita. Sem instalação adicional, jobs ou consultas automáticas. Login conferido, 21 testes frontend, 2 testes do transporte e build aprovados. API real respondeu durante requisição lenta; comandos de execução atualizados.

Validação final desta correção: suíte frontend completa com 237 testes em 43 arquivos aprovada, além dos 2 testes de transporte, build, formatação e sintaxe PowerShell.

08/10/2026 — Corrigidas cores da sidebar: fundo azul-petróleo, textos claros e seleção bege/terracota conforme referência, preservando conteúdo e estrutura.

08/10/2026 — Primeiro card da visão geral uniformizado aos demais: fundo branco, borda neutra e sombra suave; azul-petróleo somente na faixa lateral, ícone e valor. Conteúdo preservado.

08/10/2026 — Aplicados estilos da referência aos cards via CSS compartilhado: superfícies brancas, bordas discretas, cantos suaves, sombras e destaques cromáticos nos indicadores do painel. Conteúdo e estrutura preservados; build e formatação verificados.

08/10/2026 — Aplicada paleta da referência visual do usuário, restrita a cores: azul-petróleo, terracota, verde suave, bege e neutros claros. Valores de cor alterados em tokens/CSS e no e-mail/prévia; nenhum componente/view copiado, nenhum layout ou comportamento alterado. Comparação automática dos arquivos ignorando cores confirmou preservação do restante do conteúdo.

08/10/2026 — Melhorada apresentação dos resumos por e-mail: HTML Terra Solar com cards, datas/valores, links PNCP e botão da aplicação; alternativa textual mantida. Envio manual e regras de notificação preservados. 67 testes PHP/392 assertions e Pint aprovados; prévia fictícia conferida no navegador, incluindo largura estreita. Sem SMTP real. Ver [previews/email-digest.html](previews/email-digest.html).

08/10/2026 — Corrigido erro Bcrypt no login com e-mail inexistente: hash fictício válido gerado pelo algoritmo configurado, com cache; resposta HTTP 422 de credenciais incorretas sem emissão de JWT. Senhas e cadastros existentes preservados. Teste de regressão acrescentado.

Validação: 66 testes PHP/380 assertions e Pint aprovados.

08/10/2026 — Restaurado carregamento da interface: processo PHP ocupado por consulta longa reiniciado; saúde e estado de autenticação HTTP 200; login conferido visualmente em `/app/`. Consulta interrompida registrada como falha e trava liberada, preservando resultados parciais. Adicionado limite de 15 segundos à inicialização/autenticação de estado, com erro informativo se a API não responder. 21 testes frontend pertinentes e build aprovados; sem novas chamadas PNCP/SMTP nesta correção.

08/10/2026 — Varredura e correção de erros na seleção de perfis, deduplicação antes do mapper, revalidação de evidências, endereço da interface em e-mails e limpeza dos formulários ao trocar de edital. Testes de regressão adicionados. Relatório em [VARREDURA.md](VARREDURA.md).

Aceite: 65 testes PHP/371 assertions, 235 testes frontend em 42 arquivos, sintaxe de 74 arquivos PHP, build e formatação aprovados. Sem PNCP/SMTP reais ou alterações diretas do banco da aplicação.

08/10/2026 — Correção solicitada: a consulta exige atender a TODOS os perfis selecionados (interseção dos critérios). Uma exclusão em qualquer selecionado impede a importação. Sem seleção, mantém busca pelo acervo. Substitui a combinação por qualquer perfil registrada nas entregas anteriores; deduplicação global e triagem manual preservadas.

Validação da correção: suíte PHP completa com 62 testes/359 assertions e 16 testes frontend pertinentes em três arquivos aprovados. PNCP simulado nos testes.

08/10/2026 — Seleção explícita de perfis por checkboxes no Painel e em Oportunidades, mantendo componentes Legalis e tema. Seleção compartilhada entre telas, inicialmente vazia; sem seleção, busca pelo acervo; com vários perfis, correspondência a qualquer selecionado. API exige IDs explícitos e valida existência, atividade e duplicidade. Deduplicação PNCP global, pendentes e triagem preservadas; sem migrations.

Aceite da seleção: 62 testes PHP/359 assertions e 233 testes frontend em 41 arquivos; build, Pint, Prettier e diff aprovados. Sem PNCP/SMTP reais.

08/10/2026 — Triagem manual incremental implementada: pendentes acumuladas entre consultas, salvas e descartadas separadas por filtros; decisões mutuamente exclusivas e reversíveis antes do vencimento. Mantidos confronto com acervo e deduplicação por identificador PNCP. Perfis ativos passam a refinar a entrada de novas oportunidades por correspondência a qualquer perfil; sem ativos, mantém busca pelo acervo. Sem migrations ou alteração direta dos dados existentes. Validação: 59 testes PHP/344 assertions, 229 testes frontend em 39 arquivos e build aprovados.

08/10/2026 — Documentação consolidada com índice em [README.md](README.md), referências à arquitetura vigente e explicação do marcador de oportunidades salvas. Descrição de autenticação do envio manual atualizada para JWT Bearer. Atualização exclusivamente documental; sem nova execução de testes ou alterações de código/dados.

08/10/2026 — Substituição do HTML nativo de controles por componentes Legalis, mantendo o tema: UI, formulários, cards, tabela do histórico e confirmação. Implementações/props e nove arquivos de testes importados; adicionada cobertura das adaptações e da fronteira entre páginas e controles. Tokens e regras de negócio preservados. Ver [FRONTEND.md](FRONTEND.md).

Aceite técnico: 228 testes em 39 arquivos, build e formatação aprovados; login conferido no navegador. Backend/JWT, migrations e dados não alterados nesta etapa.

08/10/2026 — Arquitetura de frontend baseada no Legalis: páginas por domínio, componentes locais e compartilhados, layout independente, App.vue de composição e testes espelhados em frontend/tests. Parecer, anexos e requisitos separados em componentes próprios. API JWT e ações exclusivamente manuais preservadas. Detalhes em [FRONTEND.md](FRONTEND.md).

Aceite técnico: 52 testes frontend em 27 arquivos, build e formatação aprovados. Classes e CSS compilado preservados; backend, migrations e dados não alterados nesta etapa.

08/10/2026 — Nova arquitetura: backend exclusivo API JWT, frontend Vue independente, build em `frontend/dist`, endereços/CORS configuráveis. Removida autenticação de sessão/CSRF e entrega de SPA pelo Laravel. Consultas e e-mails continuam manuais pelo navegador. Documentação operacional: [API.md](API.md). Referências antigas a sessão e build no backend são históricas.

Aceite técnico da separação/JWT: 56 testes PHP/313 assertions com Bearer real, 25 testes frontend, build independente, formatação e Composer aprovados. Sem alterações de migrations ou dados de domínio. Homologação operacional PNCP/SMTP permanece pendente.

Comparação com Legalis concluída em 08/10/2026: integração por contrato, APIs por domínio, validação estrita, limites independentes, espera informativa sem requisição automática, modais acessíveis, confirmação de exclusão e limpeza de estado privado implementados. Validação: 51 testes PHP/280 assertions, 22 testes frontend, build e formatação aprovados. Operação manual preservada; homologação PNCP/SMTP real permanece pendente. Veja [COMPARACAO_LEGALIS.md](COMPARACAO_LEGALIS.md).

Atualizado em: 08/10/2026. Fuso: America/Sao_Paulo.

## Estado vigente após a revisão MVC — 08/10/2026

Resumos por e-mail foram mantidos por decisão do usuário, exclusivamente pelo botão Enviar resumos por e-mail em Perfis de busca. POST /api/digest executa o envio síncrono com autenticação JWT Bearer e limitação de frequência. Consultar PNCP e buscar anexos não enviam e-mails.

RadarController foi substituído por controllers de autenticação, painel, oportunidades, documentos, requisitos, perfis, consultas, anexos, resumos e interface. FormRequests validam as entradas. Services isolam consultas, parecer, exportação, armazenamento, revisão, perfis e ciclo de vida. O frontend está dividido em views, componentes, composables e services; App.vue contém a estrutura principal e a composição das telas.

Foram removidos scripts de automação, classes de jobs PNCP, método de coleta por publicação, rota/token de exemplo, segunda configuração Vite do backend, página de boas-vindas e teste unitário de exemplo. Foram retirados 130 registros de jobs PNCP pendentes/falhos no MySQL da aplicação, sem alterar acervo, oportunidades ou relações de domínio. Configurações e tabelas de infraestrutura Laravel permanecem; filas estão desativadas por configuração. Não houve mudança estrutural do banco nesta revisão.

Detalhes e validação: [REVISAO.md](REVISAO.md). As seções anteriores à revisão, mantidas abaixo como histórico, não devem ser usadas para retomar workers, scheduler, scripts removidos ou o antigo controller. A seleção de oportunidades e os resumos continuam sendo triagem documental preliminar; PNCP, SMTP e PDFs reais ainda precisam de homologação operacional.


## Decisão vigente — operação manual no navegador — 08/10/2026

Esta decisão substitui todos os objetivos e instruções anteriores sobre automação, rotina diária, scheduler, workers e consultas por comandos. Todas as consultas são iniciadas por ações do usuário na interface do LicitaRadar. O servidor usa a API oficial do PNCP na mesma requisição HTTP, sem despachar jobs. A tela aguarda a conclusão e atualiza os dados; falhas preservam resultados parciais. Buscar anexos permanece uma ação manual no detalhe. Não há consultas agendadas nem e-mails disparados ao consultar.

A tarefa Windows “LicitaRadar - Consulta diaria PNCP” foi desativada e conferida como Disabled. Os scripts de rotina diária, instalação de agendamento, scheduler e worker e as classes dos jobs antigos foram removidos. Comandos radar:sync e radar:prune foram removidos; a limpeza ocorre nas ações do navegador. Acervo, oportunidades e histórico foram preservados. Resumos por e-mail mantidos exclusivamente por botão manual em Perfis de busca; nenhuma consulta os dispara.

Os registros anteriores abaixo descrevem o histórico e não autorizam retomar automação ou workers. Os critérios de aceite atuais exigem operação manual ponta a ponta, consulta síncrona, ausência de despacho à fila e ausência de tarefas periódicas. As demais regras de confronto documental, deduplicação, prazo, exclusão e exportação permanecem vigentes.


Este cronograma organiza a sequência de entregas e seus critérios de aceite. Datas e durações ainda não foram acordadas; não representam compromissos de prazo. As etapas futuras são planejamento, não autorização para implementá-las neste momento.

## Etapas

| Etapa | Entrega | Situação | Dependência | Critério de aceite |
| --- | --- | --- | --- | --- |
| 1 | Fundação da aplicação | Implementada e validada localmente | Nenhuma | Laravel + Vue funcionando, MySQL `licitaRadar_bd`, autenticação, identidade baseada no Design System, migrations de criação por tabela e testes passando. |
| 2 | Radar PNCP orientado pela documentação da empresa e filtros, com consulta manual | Seleção documental implementada e validada por testes; homologação operacional e com PDFs reais pendente | Etapa 1, acervo da empresa e disponibilidade do PNCP | Busca baseada exclusivamente em certidões, atestados, RRT e habilitação do CNPJ, confrontados com o objeto do edital; compatibilidade fundamentada em documentos/trechos; pendência explícita quando faltar evidência; consulta paginada completa, sem duplicatas, filtros de refinamento, consulta concluída na mesma requisição do navegador, sem fila, worker ou execução agendada. |
| 3 | Acervo da empresa e análise documental inicial | Implementado; homologação com PDFs reais pendente | Etapas 1–2 e exemplos documentais | Upload dos tipos acordados, tabelas separadas, extração por página limitada a candidatos técnicos, busca de anexos sob solicitação sem worker, exclusão de anexos e requisitos de origem, evidências rastreáveis e revisão com casos reais. |
| 4 | Estabilização e piloto de uso | Planejada; próxima entrega de consolidação | Homologação das etapas 2–3 | Rodadas completas de consulta e anexos, erros diagnosticáveis, testes dos resumos no Mailpit e revisão do fluxo pelo usuário. |
| 5 | Ampliar a interpretação documental | Backlog proposto, escopo a definir | PDFs representativos e resultados do piloto | Definir necessidade de OCR e análise de requisitos complexos; validar extração e comparação contra revisão humana, sem aprovar habilitação apenas por palavras em comum. |
| 6 | Operação em produção | Planejada; ambiente a definir | Piloto aprovado e infraestrutura definida | Hospedagem, SMTP real, HTTPS, backups e monitoramento de falhas, preservando a operação manual vigente. Automação depende de nova definição explícita de escopo. |
| 7 | Viabilidade econômica e de mercado | Adiada pelo usuário | Dados de custos, capacidade, margem e critérios de decisão | Parecer econômico fundamentado, com premissas e distinção entre estimativas e dados conhecidos. |
| 8 | Preparação/envio de inscrições e propostas | Adiada pelo usuário | Regras dos portais, credenciais e aprovação do novo escopo | Definir fluxo de preparação, revisão e autorização do envio antes de implementar ações externas. |
| 9 | Alertas por WhatsApp | Adiada pelo usuário | Escolha do provedor e aprovação do novo escopo | Integração configurada e alertas validados com destinatários autorizados. |

## Estado atual das últimas alterações

Palavras-chave opcionais nos perfis, busca direta de anexos pelo botão, exclusão de PDFs/requisitos de origem e extração limitada a candidatos técnicos estão implementadas. Última validação: **39 testes aprovados, 211 assertions e build Vue aprovado**, com PNCP simulado e MySQL isolado. Não houve novas migrations. Homologação operacional e com PDFs reais permanece pendente.

## Próxima entrega: consolidação do MVP

14/10/2026 — escopo revisado pelo usuário: PDFs originais no storage, abertura em outra aba, sem texto de páginas na base e sem fluxo de requisitos. Acervo lido em memória para seleção de oportunidades. Removidos 274 requisitos da base local; homologação passa a conferir download/abertura dos PDFs e confronto do objeto com o acervo. Critérios históricos de extração/revisão de requisitos abaixo foram substituídos por esta decisão. Detalhes em [RELATORIO-PDFS.md](RELATORIO-PDFS.md).

14/10/2026: novos anexos PNCP e uploads manuais agrupados por oportunidade em `storage/app/private/documents/opportunities/{id}/`. Repetições reutilizam a pasta e preservam a deduplicação. Arquivos anteriores permanecem acessíveis no caminho registrado. A homologação com anexos reais continua pendente.

Implementado em 07/10/2026: busca e seleção orientadas exclusivamente pelo acervo, com especialidades e evidências por página. Perfis refinam alertas e resultados por palavras-chave opcionais; campo vazio representa busca genérica entre oportunidades compatíveis com o acervo. Próxima prioridade: homologar a qualidade do confronto textual com PDFs e editais reais.

Critérios de aceite do novo objetivo:

- Confrontar o objeto do edital com as capacidades e atividades presentes nas certidões, atestados, RRT e habilitação do CNPJ cadastrados.
- Fundamentar a compatibilidade com referência aos documentos e trechos utilizados, mantendo a revisão dos requisitos para o parecer documental.
- Validar casos compatíveis, incompatíveis e com documentação ausente ou insuficiente, sem recomendar oportunidades por palavras-chave genéricas independentes do acervo.
- Reavaliar a compatibilidade das oportunidades quando a documentação da empresa mudar.
- Permitir descarte reversível até o vencimento, exclusão de descartadas vencidas e bloqueio persistente de reimportação por identificador PNCP; coletar somente editais novos com propostas abertas. Exportar até 50 selecionadas abertas em PDF único com dados e evidências. Implementado e validado por testes; homologação operacional e revisão do fluxo pelo usuário ainda pendentes.

1. Diagnosticar a falha da última importação PNCP e validar uma rodada completa. A última consulta terminou com falha, preservando resultados parciais; importação parcial não constitui aceite operacional.
2. Homologar a busca de anexos pelo botão, sem worker, incluindo falhas, novas tentativas, formatos não PDF e exclusão com remoção dos requisitos de origem.
3. Validar os PDFs de certificações, atestados, RRT e CNPJ da empresa. O usuário informou que fornecerá exemplos; a homologação desses exemplos ainda não foi concluída.
4. Validar uma oportunidade do início ao fim: edital, extração apenas de candidatos técnicos, evidência da empresa, revisão e parecer preliminar. Requisitos antigos exigem nova importação do anexo para aplicar o classificador atual.
5. Homologar o resumo de oportunidades no Mailpit pelo botão manual em Perfis de busca. A captura de mensagem de teste não substitui o resumo completo. A tarefa diária permanece desativada.

## Limites da entrega atual

- A busca é baseada no acervo; o confronto usa vocabulário explícito de especialidades e exige apoio documental para todas as especialidades reconhecidas no objeto. Não realiza interpretação semântica integral ou comprovação de quantitativos. CNAEs de arquitetura e engenharia têm reconhecimento inicial; outras atividades precisam de calibração.
- A triagem, a seleção de candidatos técnicos e as sugestões de evidência usam heurísticas textuais e precisam de revisão humana.
- Todas as consultas e buscas de anexos ocorrem por ação manual no navegador, sem worker ou agendamento.
- O parecer exige revisão humana e não garante habilitação jurídica ou técnica.
- PDFs digitalizados podem exigir OCR; OCR automático não está implementado.
- O desenvolvimento usa Mailpit, sem entrega de e-mails externos.
- A tarefa Windows diária está desativada; não há execução programada.
- Análise econômica, inscrições/propostas e WhatsApp permanecem fora do MVP atual.

## Histórico de entregas

| Data | Marco |
| --- | --- |
| 06/10/2026 | Implementação inicial em Laravel + Vue, integração PNCP, painel, perfis, documentos, requisitos e automação. |
| 06/10/2026 | Configuração exclusiva de MySQL e validação em banco de testes separado. |
| 06/10/2026 | Mailpit configurado e mensagem de teste capturada. |
| 06/10/2026 | Tipos de documento RRT e CNPJ acrescentados. |
| 06/10/2026 | Acervo da empresa movido para `company_documents`, preservando documentos e evidências. |
| 06/10/2026 | Migrations reorganizadas em arquivos de criação por tabela; histórico do banco consolidado sem apagar dados. |
| 06/10/2026 | Documentação de cronograma e contexto de retomada criada. |
| 07/10/2026 | `AGENTS.md` movido para `docs`; objetivo atualizado para busca exclusivamente baseada no acervo da empresa, confrontado com o objeto do edital. Implementação e homologação pendentes. |
| 07/10/2026 | Implementada seleção documental na coleta, painel, oportunidades, detalhe e resumos; evidências por documento/página; perfis restritos a refinamentos e alertas. 21 testes aprovados, 78 assertions; build Vue aprovado. Dados existentes preservados, sem mudanças de migrations. Homologação real pendente. |
| 07/10/2026 | Descarte reversível e exportação de oportunidades selecionadas em PDF implementados. 26 testes aprovados, 116 assertions; build Vue e revisão visual do PDF aprovados. Migration de criação de `opportunity_dismissals` aplicada com preservação dos registros e vínculos das tabelas existentes. |
| 07/10/2026 | Restauração limitada ao prazo aberto; limpeza de descartadas vencidas e controle independente de identificadores PNCP para impedir reimportação. Coleta alterada para propostas abertas e importação incremental sem sobrescrever editais consultados. 34 testes aprovados, 153 assertions; build Vue aprovado; migration de criação de `pncp_consultations` aplicada e preenchida com IDs existentes. Limpeza inicial sem descartadas vencidas. |

Atualizar a situação das etapas com evidências de aceite, não apenas com a existência do código. Consulte [CONTEXT.md](CONTEXT.md) para retomar o trabalho.

Atualização em 07/10/2026: campo opcional de palavras-chave nos perfis implementado. Busca por qualquer termo no objeto do edital, ignorando caixa e acentos; vazio significa busca genérica com base no acervo. Persistência, limpeza do campo e aplicação nos resumos validadas. Suíte MySQL isolada: 35 testes aprovados, 166 assertions; build Vue aprovado. Sem alterações de migrations ou dados da aplicação.

Correção em 07/10/2026: botão Buscar anexos no PNCP tinha limite compartilhado entre endpoints e oportunidades. Implementado limitador nomeado por usuário/oportunidade com mensagem em português e tempo de espera. Teste de regressão cobre ações distintas, três oportunidades, bloqueio de repetição e liberação após cinco minutos. Suíte MySQL isolada: 36 testes aprovados, 175 assertions.

07/10/2026: diagnosticada fila de anexos sem worker ativo. Interface de acompanhamento e atualização manual implementada; 3 testes de importação/extração aprovados, 13 assertions, build Vue aprovado. Registro histórico: esse fluxo foi substituído pela busca direta sob solicitação; anexos não dependem mais do worker.

07/10/2026 — Decisão vigente: anexos somente sob solicitação. Botão baixa e extrai diretamente sem worker; removidos disparos na sincronização e comando radar:attachments. Jobs antigos não consultam PNCP. Interface mostra processamento e atualiza documentos/requisitos ao concluir. 37 testes aprovados, 188 assertions no MySQL isolado; build Vue aprovado. Testes incluem busca via endpoint sem fila, deduplicação, falha e nova tentativa, ausência de disparo na coleta e neutralização de jobs antigos. Homologação de download real continua pendente.

07/10/2026: exclusão de anexos e de seus requisitos de origem implementada; extração de novos PDFs restrita a candidatos de qualificação técnica explícita. Preservação do acervo e da oportunidade validada. 39 testes aprovados, 211 assertions; build Vue aprovado. Homologação do classificador com editais reais permanece pendente.

08/10/2026: campo de palavras-chave a ignorar implementado nos perfis, com precedência sobre inclusão, comparação sem caixa/acentos e reavaliação nos resumos. Migration de criação atualizada e MySQL local sincronizado preservando o perfil existente. Suíte completa aprovada (40 testes, 225 assertions); cobertura adicional de resumos aprovada em RadarTest (18 testes, 92 assertions). Build Vue aprovado. Homologação operacional pendente.

Validação da operação manual em 08/10/2026: 42 testes aprovados, 240 assertions no MySQL isolado e build Vue aprovado. Testes comprovam consulta pelo endpoint concluída na mesma requisição, erros retornados ao navegador, nenhuma fila/e-mail disparado, jobs antigos inertes e ausência de eventos no scheduler. PNCP simulado; rodada operacional real ainda não homologada. Comandos de envio de e-mail pelo terminal também removidos, em conformidade com operação exclusivamente pelo navegador.

Validação da revisão MVC em 08/10/2026: 47 testes aprovados, 261 assertions no MySQL isolado; 13 testes de frontend aprovados; build Vue aprovado e formatação PHP aplicada. Sem rodada real de PNCP ou envio SMTP nesta revisão.
