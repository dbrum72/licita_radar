# Contexto de retomada — LicitaRadar

Atualizado em: 08/10/2026. Fuso: America/Sao_Paulo.

## Seleção de perfis somente em Oportunidades

Seletor removido de Visão geral a pedido do usuário. Somente a view Oportunidades apresenta os checkboxes para escolher perfis. Botões de consulta manual existentes em Visão geral permanecem e usam a seleção corrente do workspace; sem seleção, aplica-se somente o acervo. Regras de interseção e consulta incremental preservadas.

## Recorrência de bloqueio local — 08/10/2026

Vite respondia HTTP 200, mas API na porta 8000 não respondia a `/up`. Processo PHP identificado pelo comando exato e reiniciado. Consulta 2 iniciada às 17:42:06 permanecia running com 1 importação; após interrupção, registro marcado failed e trava liberada com guarda por ID/status/horário e ausência de outra execução. Dados parciais preservados. Saúde e auth/status voltaram a HTTP 200 e login conferido no navegador em `/app/`.

Corrigida espera adicional na inicialização autenticada: booting termina após obter usuário, antes da carga do painel; dashboard e lista de perfis têm timeout de 15 segundos. Teste cobre painel pendente/falha sem esconder a interface autenticada. 21 testes pertinentes e build aprovados. Usuário escolheu concorrência HTTP. Download do Caddy recusado; nenhum Caddy instalado. Implementada entrada HTTP com Node nativo e quatro processos PHP (dois reservados para leituras e dois para escritas), sem jobs ou consultas automáticas. `scripts/start.ps1` e `composer run dev` usam a nova configuração. Duas verificações de transporte aprovadas e teste na API real confirmou leituras HTTP 200 durante requisição lenta. Detalhes em [API.md](API.md). Não foi disparada nova consulta PNCP.

Validação final: 237 testes frontend em 43 arquivos, 2 testes de transporte HTTP, build, formatação e sintaxe do lançador PowerShell aprovados. Não houve alteração de regras/backend PHP nesta entrega; banco foi alterado somente para registrar a consulta interrompida e liberar sua trava, com os guardas descritos acima.

## Paleta vigente — referência visual do usuário

Sidebar corrigida para reproduzir as cores da referência: fundo azul-petróleo, textos/ícones claros, seleção bege com terracota e hover em petróleo escuro. Tokens locais preservam contraste em empresa, navegação e conta. Somente CSS; estrutura e conteúdo mantidos.

Cards atualizados com fundo branco, bordas discretas, cantos de 6px e sombra suave no CSS compartilhado de `AppCard`. Todos os indicadores do painel mantêm fundo branco, com faixas internas azul-petróleo, verde, mostarda e terracota. O primeiro card foi uniformizado a pedido do usuário, removendo o fundo sólido e os textos brancos. Variantes accent/highlight têm destaque próprio. Alteração restrita à apresentação CSS; conteúdo, marcação, espaçamentos e comportamento preservados. Build e formatação verificados.

Aplicada exclusivamente a paleta da imagem fornecida: azul-petróleo `#1e4d55`, terracota `#a6532b`, verde suave `#89aa96`, bege `#e7d6b8`, mostarda `#d8ad58`, fundo cinza-claro `#f2f4f5` e branco. Estados, bordas, sombras e cores de texto foram ajustados à nova família. Tokens e CSS centralizados, HTML do e-mail e prévia tiveram somente valores de cor substituídos. Nenhum componente ou view foi copiado da referência; estrutura, layout, tipografia, espaçamento, lógica e contratos preservados. O identificador CSS `terra-solar` permanece para compatibilidade, mas a referência cromática vigente é esta imagem. Descrições anteriores da paleta marrom/creme são históricas.

## E-mail HTML — 08/10/2026

Resumos manuais usam agora `app/Mail/OpportunityDigest.php`, com views HTML e texto em `resources/views/emails`. Tema Terra Solar, cabeçalho com contagem, perfil, cards de editais com objeto/órgão/local/modalidade, prazo com hora/fuso e valor em reais, link oficial PNCP e botão da interface. Conteúdo externo escapado no HTML. Tabelas e estilos inline; sem imagens remotas ou JavaScript. Regras de destinatários, elegibilidade, deduplicação de notificações e envio exclusivamente manual mantidas. Notificação só é marcada após o envio sem erro.

Validação: 67 testes PHP/392 assertions e Pint aprovados. Prévia fictícia em [previews/email-digest.html](previews/email-digest.html), conferida no navegador em tela ampla e estreita, sem overflow horizontal no DOM. SMTP simulado; não foi enviado e-mail real nem homologada a renderização em Gmail/Outlook nesta entrega.

## Correção do login com e-mail inexistente — 08/10/2026

O erro “This password does not use the Bcrypt algorithm” era causado pelo hash fixo inválido usado para comparar senhas quando o e-mail não existia. Hash da conta cadastrada conferido como Bcrypt válido, sem exibir hashes ou alterar senhas. Login agora gera um hash fictício válido pelo algoritmo configurado, mantém-no no cache e retorna a mesma validação “E-mail ou senha incorretos” para conta inexistente ou senha incorreta. Não emite JWT nesses casos. Teste de regressão cobre a primeira tentativa e reutilização do hash. Nenhuma alteração de credenciais ou dados de usuários.

Validação: 66 testes PHP/380 assertions e Pint aprovados.

## Correção de carregamento inicial — 08/10/2026

No endereço informado pelo usuário, `http://127.0.0.1:5173/app/`, a interface aguardava indefinidamente a inicialização. Servidor PHP na porta 8000 não respondia nem a `/up`; o processo foi identificado e reiniciado, sem reiniciar Vite ou alterar oportunidades/acervo. Após reinício, `/up` e `/api/auth/status` retornaram HTTP 200 e a tela de login foi conferida visualmente no navegador no mesmo endereço. A última consulta, iniciada às 15:47:46, permanecia em andamento e coincidia com a requisição que ocupava o processo antigo. Seu registro foi marcado como interrompido/falha e a trava PNCP liberada após conferir ID, horário e ausência de outra execução ativa. Resultados parciais e identificadores consultados preservados. A etapa interna específica que prolongou a consulta não foi determinada.

Transporte HTTP aceita `timeoutMs`; consultas de estado de autenticação e usuário atual possuem limite de 15 segundos. A expiração mostra mensagem útil e permite concluir a inicialização em vez de permanecer em “Carregando”. Consultas PNCP, anexos e envio manual mantêm seus tempos de processamento. Validação: 21 testes pertinentes de HTTP/token/inicialização/integração e build aprovados; código backend sem alterações nesta correção. Endereço recomendado da interface permanece a raiz `http://127.0.0.1:5173/`, conforme [API.md](API.md).

## Varredura de erros — 08/10/2026

Corrigidos seleção de perfis desatualizada no início da consulta, aplicação implícita de perfis no service, mapeamento de identificadores já conhecidos com dados incompletos, parecer que não revalidava status/página da evidência, link de e-mail para o backend e rascunhos de requisitos herdados do edital anterior. Detalhes em [VARREDURA.md](VARREDURA.md). Operação manual, interseção dos perfis, histórico incremental e tema mantidos; sem migrations ou alteração direta dos dados da aplicação.

Validação: 65 testes PHP/371 assertions, 235 testes frontend em 42 arquivos, sintaxe de 74 arquivos PHP e build aprovados. Formatação PHP/Vue verificada; integrações externas simuladas.

## Decisão vigente — perfis escolhidos em cada consulta

Somente Oportunidades apresenta checkboxes de perfis ativos no componente `ConsultationProfiles`. Nenhum perfil começa marcado; sem seleção, consulta somente pelo acervo. Com seleção, exige correspondência a todos os perfis escolhidos e compatibilidade documental. Uma exclusão ou filtro não atendido em qualquer selecionado impede a importação. Perfis ativos não selecionados não filtram nem recebem associações nessa importação. Esta decisão substitui a aplicação implícita de todos os perfis ativos e a combinação por qualquer perfil descritas no histórico abaixo.

A seleção feita em Oportunidades permanece no workspace em memória e é usada pelos botões de consulta, inclusive em Visão geral. É removida ao sair/expirar a autenticação e reiniciada ao recarregar a interface. Perfis pausados ou excluídos saem da seleção quando a lista é atualizada. A API exige `profile_ids` como array explícito, aceita array vazio e rejeita IDs inexistentes, pausados ou repetidos. A consulta continua manual e incremental; trocar perfis não reavalia identificadores PNCP já consultados nem filtra retroativamente pendentes existentes. A interface informa essa regra. Envio manual de resumos mantém suas regras próprias de perfis ativos e associações existentes.

Validação da seleção: 62 testes PHP/359 assertions, 233 testes frontend em 41 arquivos, build, Pint, Prettier e verificação de diff aprovados. PNCP simulado nos testes; sem chamadas operacionais ou envio SMTP real.

## Decisão vigente — triagem manual e consulta incremental

O usuário confirmou manter a compatibilidade com o acervo e acumular oportunidades ainda não avaliadas entre consultas, substituindo sua escolha anterior de mostrar somente o último lote. A listagem padrão mostra pendentes (não salvas nem descartadas); “Salvas” e “Descartadas” mostram as respectivas decisões. Salvar remove um eventual descarte; descartar remove a marcação de salvo; remover dos salvos ou restaurar devolve à triagem. Salvar requer prazo aberto. Os filtros Salvas/Descartadas são mutuamente exclusivos na interface.

Na importação, oportunidades precisam ter prazo aberto e compatibilidade documental. Havendo perfis ativos, precisam atender a pelo menos um deles; cada perfil é independente, inclusive suas exclusões. Sem perfis ativos, a busca permanece baseada no acervo. Essa regra substitui a descrição anterior de que perfis apenas refinavam associações/resumos. Alterações de perfis não reassociam dados históricos.

O registro `pncp_consultations` impede reimportação por identificador, inclusive de resultados rejeitados, salvos, descartados ou excluídos após vencimento. A API PNCP pode devolver itens conhecidos; o backend os ignora e cadastra somente inéditos. Pendentes anteriores permanecem enquanto tiverem prazo aberto e compatibilidade atual com o acervo. Nenhuma migration ou alteração direta dos dados existentes foi necessária.

Validação desta entrega: 59 testes PHP, 344 assertions; 229 testes frontend em 39 arquivos; build aprovado. PNCP simulado, sem consulta operacional ou envio SMTP real.

## Última atualização documental — 08/10/2026

Índice e resumo vigente em [README.md](README.md). Esclarecida a interação do marcador das oportunidades: laranja preenchido significa salva; clicar alterna a marcação e o filtro “Salvas” recupera os itens marcados. A marcação é persistida pela API autenticada e independe da seleção para PDF e do descarte. Esta atualização altera somente documentação; resultados de testes abaixo são registros das respectivas entregas, sem nova execução.

## Componentes HTML Legalis — substituição implementada

Por confirmação do usuário, páginas/layout/navegação agora usam componentes reais de UI/formulários do Legalis no lugar de button/input/select/textarea/label nativos. Importados AppButton/Icon/Card/Table, AppInput/Select/Textarea/Checkbox/FileUpload e suas camadas de props/fields/controls/contexto; confirmação de exclusão usa AppConfirmDialog adaptado. Cards compõem painéis e oportunidades; tabela apresenta histórico. Tokens Terra Solar preservados, CSS dos componentes adaptado ao tema do LicitaRadar. Seleção múltipla mantém IDs numéricos, upload limita um PDF de 20 MB e número conserva o modificador number. Testes do Legalis importados e fluxos existentes migrados. Detalhes em [FRONTEND.md](FRONTEND.md). Esta entrega complementa a organização de pastas anterior: agora também substitui o HTML dos controles. Backend/JWT, operação manual, migrations e dados preservados.

Validação final: 228 testes em 39 arquivos, build Vite, formatação e diff aprovados. Login conferido visualmente no navegador; áreas privadas cobertas por testes de componentes/fluxo. Sem PNCP/SMTP reais.

## Arquitetura de views/components e testes — referência Legalis

Frontend reorganizado por domínio em `views/<domínio>/*Page.vue`, com componentes específicos em `views/<domínio>/components`. Layout autenticado em `layouts/DefaultLayout.vue`; componentes compartilhados separados em layout, navigation, public, ui e documents. App.vue contém somente composição de carregamento/login/área privada. Detalhe de oportunidade dividido em parecer, anexos e requisitos; diálogos de perfil e revisão/exclusão ficam no domínio. Leitor de PDF é compartilhado.

Testes movidos de src para frontend/tests, espelhando a estrutura e usando Vitest/jsdom/Vue Test Utils com alias @ e fixtures reativas. Integração SSR mantida em ambiente Node. Legalis somente lido. Backend/API JWT, navegação de estado, operação manual, tema e dados preservados. Guia: [FRONTEND.md](FRONTEND.md). Esta estrutura substitui referências históricas a views e modais em diretórios planos.

Validação da arquitetura frontend: 52 testes em 27 arquivos, build Vite e Prettier aprovados; caminhos antigos removidos, nenhum teste em src e CSS compilado preservado. Backend não alterado nesta etapa.

## Decisão vigente — API separada e JWT

Backend Laravel exclusivo para API em `routes/api.php`, autenticada por JWT Bearer, sem sessão/CSRF. Vue possui servidor independente (porta 5173), API configurável por `VITE_API_URL` e build em `frontend/dist`. Removidos SpaController, rotas web, endpoint CSRF e dependência/configuração Sanctum. Composer não instala/compila o frontend. Downloads privados usam requisição com Authorization.

JWT dura 60 minutos por padrão, valida assinatura HS256, emissor/destinatário e validade; registro de tokens no cache permite revogação ao sair. Limpar cache invalida tokens. Chave separada opcional `JWT_SECRET` ou derivação contextual de APP_KEY. Frontend usa sessionStorage por aba, sem cookies; limpa token/dados privados em 401/logout. CORS autoriza origens explícitas por `FRONTEND_URL`. Todas as operações PNCP e e-mails continuam manuais na interface. Sem alterações de migrations ou dados de domínio. Instruções: [API.md](API.md).

Esta decisão substitui as referências históricas abaixo a sessão/CSRF, entrega de SPA pelo Laravel e caminho `/app/`.

Validação final da separação/JWT: 56 testes PHP, 313 assertions, com Bearer real em todos os testes de domínio; 25 testes frontend; build independente, formatação, Composer e diff aprovados. Testadas revogação, expiração, assinatura/algoritmo/destinatário inválidos, usuário excluído, CORS e downloads. Sem chamadas operacionais PNCP/SMTP.

## Comparação com Legalis — estado vigente

Aplicadas práticas da aplicação irmã: integração PNCP por contrato injetado com transporte e mapper separados; módulos de API por domínio no frontend; validação estrita de UF, datas e intervalo de valores; limites independentes por ação com tempo de espera; diálogos acessíveis e confirmação explícita de exclusão; limpeza dos dados privados após logout/401; formatação Prettier e testes DOM. Constantes, utilitários e configuração possuem pastas próprias. Nenhuma alteração no Legalis, nas migrations ou nos dados do LicitaRadar. Mantida a operação exclusivamente manual pelo navegador, inclusive resumos por e-mail.

Validação: 51 testes PHP, 280 assertions; 22 testes de frontend; build, Pint, Prettier e verificação de diff aprovados. PNCP/SMTP reais permanecem sem homologação nesta entrega. Referências e detalhes: [COMPARACAO_LEGALIS.md](COMPARACAO_LEGALIS.md).

## Estado vigente após a revisão MVC — 08/10/2026

Resumos por e-mail foram mantidos por decisão do usuário, exclusivamente pelo botão Enviar resumos por e-mail em Perfis de busca. POST /api/digest executa o envio síncrono com autenticação JWT Bearer e limitação de frequência. Consultar PNCP e buscar anexos não enviam e-mails.

RadarController foi substituído por controllers de autenticação, painel, oportunidades, documentos, requisitos, perfis, consultas, anexos, resumos e interface. FormRequests validam as entradas. Services isolam consultas, parecer, exportação, armazenamento, revisão, perfis e ciclo de vida. O frontend está dividido em views, componentes, composables e services; App.vue contém a estrutura principal e a composição das telas.

Foram removidos scripts de automação, classes de jobs PNCP, método de coleta por publicação, rota/token de exemplo, segunda configuração Vite do backend, página de boas-vindas e teste unitário de exemplo. Foram retirados 130 registros de jobs PNCP pendentes/falhos no MySQL da aplicação, sem alterar acervo, oportunidades ou relações de domínio. Configurações e tabelas de infraestrutura Laravel permanecem; filas estão desativadas por configuração. Não houve mudança estrutural do banco nesta revisão.

Detalhes e validação: [REVISAO.md](REVISAO.md). As seções anteriores à revisão, mantidas abaixo como histórico, não devem ser usadas para retomar workers, scheduler, scripts removidos ou o antigo controller. A seleção de oportunidades e os resumos continuam sendo triagem documental preliminar; PNCP, SMTP e PDFs reais ainda precisam de homologação operacional.


## Decisão vigente — operação manual no navegador — 08/10/2026

Esta decisão substitui todos os objetivos e instruções anteriores sobre automação, rotina diária, scheduler, workers e consultas por comandos. Todas as consultas são iniciadas por ações do usuário na interface do LicitaRadar. O servidor usa a API oficial do PNCP na mesma requisição HTTP, sem despachar jobs. A tela aguarda a conclusão e atualiza os dados; falhas preservam resultados parciais. Buscar anexos permanece uma ação manual no detalhe. Não há consultas agendadas nem e-mails disparados ao consultar.

A tarefa Windows “LicitaRadar - Consulta diaria PNCP” foi desativada e conferida como Disabled. Os scripts de rotina diária, instalação de agendamento, scheduler e worker e as classes dos jobs antigos foram removidos. Comandos radar:sync e radar:prune foram removidos; a limpeza ocorre nas ações do navegador. Acervo, oportunidades e histórico foram preservados. Resumos por e-mail mantidos exclusivamente por botão manual em Perfis de busca; nenhuma consulta os dispara.

Os registros anteriores abaixo descrevem o histórico e não autorizam retomar automação ou workers. Os critérios de aceite atuais exigem operação manual ponta a ponta, consulta síncrona, ausência de despacho à fila e ausência de tarefas periódicas. As demais regras de confronto documental, deduplicação, prazo, exclusão e exportação permanecem vigentes.


Leia este documento junto de [CRONOGRAMA.md](CRONOGRAMA.md), [AGENTS.md](AGENTS.md) e `../README.md` ao retomar o projeto. Ele registra as decisões e o estado conhecido; confirme o estado atual do código, banco e processos antes de continuar. Não contém senhas ou outros segredos.

## Objetivo e escopo acordado

LicitaRadar é uma aplicação web Laravel + Vue para encontrar oportunidades de elaboração de projetos e anteprojetos de engenharia e arquitetura em todo o Brasil. Execução de obras não faz parte do interesse inicial da empresa.

Fonte inicial única: PNCP, a partir de https://pncp.gov.br/app/editais?pagina=1. A integração usa APIs oficiais em vez de preencher o site por automação de navegador.

Objetivo atualizado em 07/10/2026: em Oportunidades, a busca deve ser orientada exclusivamente pela documentação da empresa — certidões, atestados, RRT e habilitação do CNPJ — confrontada com o objeto do edital. As capacidades e atividades documentadas devem determinar quais oportunidades são apresentadas e recomendadas, com indicação dos documentos e trechos que sustentam a compatibilidade.

Palavras-chave opcionais nos perfis refinam as oportunidades compatíveis com o acervo; campo vazio representa busca genérica. Filtros de modalidade, região, valor, órgão e prazo podem refinar as oportunidades compatíveis com o acervo, sem substituir o confronto documental. A região é atualmente filtrada por UF.

Sem documentação suficiente ou legível para sustentar o confronto, o sistema deve indicar a pendência, sem afirmar compatibilidade. Compatibilidade com o objeto não equivale à habilitação integral: a revisão dos requisitos do edital e da validade das evidências continua necessária.

Estado da implementação em 07/10/2026: a coleta e a seleção em Oportunidades usam exclusivamente o acervo válido e legível da empresa. Perfis definem refinamentos e destinatários dos alertas; palavras-chave opcionais refinam o objeto do edital. A implementação passou por testes automatizados; homologação com PDFs reais e consulta operacional continua pendente.

O confronto é textual e conservador, com vocabulário explícito de especialidades em `CompanyScopeService`: arquitetura, engenharia, estrutural, elétrica, hidrossanitária, urbanismo, paisagismo, topografia, geotecnia, pavimentação, drenagem, incêndio, climatização e saneamento. Os CNAEs 7111-1/00 e 7112-0/00 também identificam arquitetura e engenharia. Todas as especialidades reconhecidas no objeto precisam de apoio em páginas do acervo. Isso não avalia integralmente quantitativos, complexidade, negativas no texto ou exigências jurídicas; ampliar/calibrar o vocabulário depende dos exemplos reais.

Somente documentos dos quatro tipos acordados com status `ready`, páginas com texto suficiente e validade até o prazo do edital sustentam o confronto. Não é obrigatório possuir um arquivo de cada tipo para a triagem; certidões genéricas sem especialidade não geram compatibilidade. Sem acervo utilizável, a consulta não chama o PNCP e registra orientação no histórico, sem avançar o marco de sucesso. A listagem traz aviso sobre a base documental mesmo quando vazia.

Resultados existentes são reavaliados a cada listagem, painel, detalhe e resumo, incluindo alterações no acervo e vencimentos. A coleta é incremental: somente identificadores PNCP ainda não consultados, com prazo estritamente posterior ao instante da importação e compatibilidade documental, geram oportunidades. Registros existentes não são sobrescritos, reassociados nem importados novamente. A coleta percorre as modalidades configuradas mesmo sem perfis e usa a rota oficial `/contratacoes/proposta` para recebimento de propostas aberto, com limite superior configurável de 365 dias (`proposal_days`). A antiga janela de sete dias de publicação e sobreposição de dois dias não é mais usada.

`pncp_consultations` mantém somente o identificador PNCP e a data da primeira consulta, independentemente do cadastro da oportunidade. Inclui os editais recebidos e avaliados, mesmo se recusados por prazo, dados insuficientes ou incompatibilidade. A gravação do identificador e a eventual importação ocorrem na mesma transação, permitindo nova tentativa em caso de erro de persistência. Alterações posteriores no acervo ou no edital não reimportam identificadores já consultados; atualização de editais conhecidos deixou de fazer parte da sincronização, conforme a regra de coleta incremental acordada.

Oportunidades descartadas podem ser restauradas somente antes do vencimento. No instante do vencimento ou depois, a limpeza exclui a oportunidade e seus vínculos de banco (descarte, anexos cadastrados, requisitos e associações com perfis), preservando o acervo da empresa e o identificador PNCP consultado. Assim, nem a posterior prorrogação do prazo no PNCP permite reimportação. Arquivos PDF privados em disco não são apagados por essa limpeza de banco. Oportunidades vencidas que não foram descartadas permanecem no banco, mas não aparecem na listagem normal nem podem ser exportadas ou gerar buscas de anexos. Prazo ausente ou inválido impede importação; restauração de registro antigo sem prazo também é bloqueada.

A limpeza roda antes das consultas, ao abrir painel/listagem/detalhe e pelo comando `radar:prune`. O scheduler Laravel a executa a cada minuto quando ativo; a tarefa Windows diária também a executa antes da coleta às 06:00, mesmo se a coleta falhar. Sem execução do scheduler ou acesso à aplicação, a exclusão ocorre na próxima execução disponível. Todos os jobs antigos de anexos terminam sem consultar o PNCP, inclusive os de oportunidades excluídas.

O sistema deve recomendar oportunidades com base nos requisitos dos editais e nos documentos da empresa, manter histórico e consultar diariamente. Na versão atual, a recomendação é uma triagem e um parecer documental preliminar com revisão humana.

Decisões explícitas do usuário:

- Banco exclusivamente MySQL, nome `licitaRadar_bd`. Não utilizar SQLite na aplicação ou nos testes.
- E-mail de desenvolvimento com Mailpit.
- Apenas recomendar oportunidades nesta etapa.
- Perfis aceitam palavras-chave opcionais; vazio significa busca genérica entre oportunidades compatíveis com o acervo.
- Buscar anexos apenas sob solicitação pelo botão, diretamente na requisição, sem worker nem disparo automático na coleta.
- Permitir excluir anexos e seus requisitos de origem; extrair somente candidatos a requisitos técnicos de novos PDFs.
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
- Perfis ativos/pausados com palavras-chave opcionais, filtros, paginação e oportunidades salvas. Termos separados por vírgula correspondem a qualquer termo no objeto, sem distinção de caixa ou acentos; campo vazio representa busca genérica baseada no acervo.
- Descarte reversível até o vencimento, compartilhado pela empresa, com usuário/data. Descartadas vencidas são excluídas do banco, mantendo apenas o identificador PNCP para bloquear reimportação. Descartadas não entram nos indicadores, resumos ou novas buscas de anexos.
- Seleção de até 50 oportunidades para um PDF único, mantida ao trocar páginas/filtros durante o uso da tela e limpa ao recarregar ou sair. O PDF contém objeto, órgão, local, modalidade, valor, prazo, publicação, link PNCP e evidências por documento/página. Compatibilidade e descarte são revalidados no servidor antes da exportação; seleção inválida é recusada integralmente.
- Cliente PNCP paginado por modalidade, com intervalo entre requisições e espera progressiva para HTTP 429/5xx.
- Importação incremental de editais novos e abertos, com controle independente de identificadores consultados e histórico de execução.
- Triagem do objeto: `relevant`, `review` ou `excluded`, com motivo registrado.
- Confronto entre objeto e especialidades documentadas no acervo, com documento, página e trecho de apoio na listagem e no detalhe. Painel e resumos usam a compatibilidade atual.
- Busca dos anexos oficiais somente pelo botão, com download e extração na mesma requisição; sem worker, URLs arbitrárias ou redirecionamentos externos. A tela atualiza os documentos e requisitos ao concluir.
- Exclusão autenticada de anexos com confirmação, removendo arquivo privado e requisitos de origem, preservando oportunidade e acervo da empresa.
- Upload privado de PDFs de até 20 MB, SHA-256, extração de texto por página e identificação de páginas que precisam de OCR.
- Extração de novos PDFs limitada a candidatos a requisitos de qualificação técnica explícita; sugestão de páginas do acervo por palavras em comum. Exigências genéricas de certidão/habilitação não bastam. Requisitos antigos não são apagados retroativamente.
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
- `backend/app/Services/SyncService.php`: coleta, filtros e persistência de oportunidades, sem disparar anexos.
- `backend/app/Services/ScopeClassifier.php`: heurísticas de escopo e filtros de perfil.
- `backend/app/Services/CompanyScopeService.php`: confronto documental do objeto, evidências e seleção baseada no acervo atual.
- `backend/app/Services/OpportunityLifecycleService.php`: exclusão transacional de descartadas vencidas e preservação dos identificadores consultados.
- `backend/app/Services/OpportunityPdfService.php` e `backend/resources/views/pdf/opportunities.blade.php`: relatório PDF com Dompdf, acesso autenticado, recursos remotos desativados e sem armazenamento público dos arquivos exportados.
- `backend/app/Services/DocumentService.php`: extração e evidências candidatas.
- `backend/app/Services/DigestService.php`: resumos por e-mail.
- `backend/app/Services/TenderAttachmentService.php`: busca e extração dos anexos sob solicitação.
- `backend/app/Jobs/SyncPncp.php`: consulta assíncrona de oportunidades.
- `backend/app/Jobs/FetchTenderDocuments.php`: compatibilidade com jobs antigos; encerra sem downloads.
- `backend/routes/web.php`: API com sessão/CSRF e entrega da interface.
- `backend/routes/console.php`: comandos operacionais e agendamento Laravel.
- `backend/config/radar.php`: modalidades, janela inicial, horário e intervalo de chamadas.
- `frontend/src/App.vue`: telas e fluxos da interface.
- `frontend/src/api.js`: requisições com sessão e CSRF.

## Última validação consolidada — 07/10/2026

**39 testes aprovados, 211 assertions no MySQL isolado e build Vue aprovado.** Inclui palavras-chave opcionais, limites independentes de anexos, busca síncrona sem fila, falha e nova tentativa, neutralização dos jobs antigos, exclusão de arquivo/requisitos e preservação do acervo. Nenhuma migration necessária para essas últimas mudanças. PNCP foi simulado nos testes; o fluxo operacional completo e a precisão com editais reais ainda precisam de homologação. As contagens abaixo são snapshots históricos.

## Execução e validação

Na raiz, use terminais separados conforme a necessidade:

```powershell
./scripts/start.ps1
./scripts/worker.ps1
./scripts/mailpit.ps1
```

O worker é necessário somente para tarefas que continuam assíncronas, como a consulta de oportunidades iniciada pelo painel. Buscar e excluir anexos não depende dele. O comando `radar:attachments` foi removido.

O servidor usa diretamente o servidor PHP com router Laravel. Isso evita a perda da configuração local de PHP observada anteriormente com um subprocesso de `artisan serve`.

No Windows já foi registrada a tarefa `LicitaRadar - Consulta diaria PNCP`, às 06:00. Ela executa `scripts/daily.ps1`, consulta o PNCP e processa a fila. Exige computador ligado e sessão iniciada; Mailpit deve estar ativo para capturar os resumos. `scripts/install-schedule.ps1` registra essa tarefa. `scripts/scheduler.ps1` é uma alternativa manual; não manter ambos os agendadores ativos simultaneamente.

Dentro de `backend`:

```powershell
../scripts/php.ps1 artisan radar:status
../scripts/php.ps1 artisan radar:prune
../scripts/php.ps1 artisan migrate:status
../scripts/php.ps1 artisan radar:sync
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
2. Homologar download sob solicitação, falhas e novas tentativas, exclusão de anexos e qualidade da extração técnica com editais reais. Não iniciar worker para buscar anexos.
3. Validar PDFs reais da empresa e um fluxo completo de evidência e revisão. Os exemplos prometidos ainda não foram homologados.
4. A coleta consulta propostas abertas com horizonte configurável de 365 dias. Identificadores consultados não são reavaliados para importação nem têm seus objetos/prazos atualizados pela sincronização. Novos perfis recebem associações apenas de novas importações. Homologar a rota de propostas abertas e monitorar os limites do PNCP.
5. Não há OCR automático nem interpretação semântica abrangente. Palavras em comum não comprovam quantitativos, especialidades, registros profissionais ou habilitação integral.
6. Homologar o resumo real no Mailpit e confirmar uma execução diária. Captura de mensagem de teste e registro da tarefa já foram feitos.
7. Produção, SMTP real e etapas futuras dependem de definição posterior do usuário.

## Como manter este contexto

Ao concluir alterações relevantes, atualizar decisões, pendências e resultados de validação neste arquivo e a situação de entrega no cronograma. Não registrar credenciais, não marcar etapas como homologadas apenas porque o código existe e não tratar snapshots antigos como estado atual.

## Atualização de perfis — 07/10/2026

A pedido do usuário, os perfis possuem campo opcional de palavras-chave separado por vírgulas. Qualquer termo pode corresponder ao objeto, ignorando caixa e acentos. Campo vazio representa busca genérica entre oportunidades compatíveis com o acervo. As palavras-chave são persistidas e aplicadas às associações de novas importações e aos resumos, mantendo os demais filtros e a deduplicação. Nenhuma migration necessária. Build Vue aprovado; validação da suíte registrada no cronograma.

## Decisão vigente: anexos somente sob solicitação — 07/10/2026

Esta decisão substitui as descrições anteriores de fila/automação de anexos: o botão executa download e extração diretamente na requisição HTTP e atualiza o detalhe ao concluir. SyncService não dispara anexos. O comando radar:attachments foi removido. Jobs FetchTenderDocuments antigos permanecem compatíveis com a fila, mas encerram sem consultar o PNCP. Não é necessário worker para anexos; consulta de oportunidades mantém seu fluxo existente. Há trava por oportunidade para evitar downloads concorrentes, limite por usuário/oportunidade e erro HTTP 502 quando a busca falha. Documentos existentes são preservados e deduplicados. Sem migrations.
Validação da busca sob solicitação: 37 testes aprovados, 188 assertions; build Vue aprovado. PNCP simulado nos testes; download operacional real não executado.

## Anexos e requisitos técnicos — 07/10/2026

Anexos podem ser excluídos pelo botão Excluir anexo, com confirmação da remoção dos requisitos originados nele. Endpoint autenticado DELETE /api/tender-documents/{document}, trava compartilhada com a busca, remoção do arquivo privado e cascade de requisitos; oportunidade e company_documents preservados. A extração de novos PDFs seleciona somente candidatos com referência técnica explícita (capacidade, acervo, responsável, registro profissional, atestados de serviços/projetos e certificações técnicas). Certidão/habilitação/qualificação genéricas não bastam; cláusulas fiscais, trabalhistas, jurídicas e financeiras são excluídas sem indício técnico explícito. Continua sendo heurística textual com revisão humana; requisitos anteriormente cadastrados não são apagados retroativamente. Para extrair novamente um anexo antigo com a regra atual, exclua-o e envie/busque o PDF novamente. 39 testes aprovados, 211 assertions; build Vue aprovado. Sem migrations.

## Palavras-chave de exclusão — 08/10/2026

Perfis possuem `excluded_keywords`, exibido como “Palavras-chave a ignorar, separadas por vírgula”. Qualquer termo encontrado no objeto, ignorando caixa e acentos, impede a associação da nova oportunidade ao perfil e seu envio nos resumos. A exclusão tem prioridade sobre palavras-chave de inclusão; vazio não exclui nada. Resumos reavaliam também associações existentes. A listagem geral continua orientada pelo acervo, sem aplicar filtros de um perfil específico; os filtros do perfil não eliminam o cadastro global de oportunidades.

Atualizada a migration de criação de `search_profiles` com coluna JSON nullable, sem migration incremental. Banco local sincronizado por script pontual removido após execução; o perfil preexistente foi conferido integralmente e preservado. Suíte completa: 40 testes, 225 assertions aprovados; após ampliar a cobertura dos resumos, RadarTest passou com 18 testes e 92 assertions. Build Vue aprovado. Homologação operacional permanece pendente.

Validação da operação manual em 08/10/2026: 42 testes aprovados, 240 assertions no MySQL isolado e build Vue aprovado. Testes comprovam consulta pelo endpoint concluída na mesma requisição, erros retornados ao navegador, nenhuma fila/e-mail disparado, jobs antigos inertes e ausência de eventos no scheduler. PNCP simulado; rodada operacional real ainda não homologada. Comandos de envio de e-mail pelo terminal também removidos, em conformidade com operação exclusivamente pelo navegador.

Validação da revisão MVC em 08/10/2026: 47 testes aprovados, 261 assertions no MySQL isolado; 13 testes de frontend aprovados; build Vue aprovado e formatação PHP aplicada. Sem rodada real de PNCP ou envio SMTP nesta revisão.
