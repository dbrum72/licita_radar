# Revisão de código — 08/10/2026

Este documento registra a revisão MVC inicial. As descrições de sessão/CSRF e estrutura da interface abaixo são históricas: a arquitetura vigente usa backend API separado e JWT Bearer, conforme [API.md](API.md), e componentes reais do Legalis com tema preservado, conforme [FRONTEND.md](FRONTEND.md). Índice atualizado em [README.md](README.md).

## Estrutura

O backend segue MVC: Models representam entidades/relações, Controllers tratam HTTP e Views entregam a interface e o PDF. As regras de negócio ficam em Services e as validações complexas em Http/Requests. A API permanece na origem da interface, com sessão, autenticação, CSRF e limitadores existentes.

Controllers: Auth, Dashboard, Opportunity, Document, Requirement, SearchProfile, Consultation, TenderAttachment, Digest e Spa. Services extraídos: DashboardService, OpportunityQueryService, OpportunityAnalysisService, DocumentStorageService, RequirementService e SearchProfileService. OpportunityLifecycleService concentra descarte/restauração; OpportunityPdfService valida a seleção antes de renderizar.

No Vue, App.vue contém a estrutura da aplicação. Views representam painel, oportunidades, acervo, perfis e detalhe. Components representam os modais de perfil, leitura de PDF e revisão. Composables separam ações/estado de autenticação, perfis, oportunidades, documentos, requisitos e controle assíncrono da requisição. useLicitaRadar coordena a navegação e useWorkspace fornece o contexto compartilhado. Services tratam HTTP/CSRF, formatação, download e atualização após ações.

## Código removido

- RadarController e lógica de negócio em closures das rotas web.
- Classes de jobs PNCP e scripts de rotina diária, instalação de agendamento, scheduler e worker.
- Método PncpClient.publications, substituído pela consulta de propostas abertas.
- Rota /api/user de exemplo com token, página welcome, assets/Vite/package.json do segundo frontend Laravel e README genérico do backend.
- Teste unitário que apenas verificava true e imports sem uso.
- frontend/src/api.js, substituído por services/http.js.

Os 130 registros pendentes/falhos dos dois jobs PNCP foram removidos de jobs/failed_jobs. Acervo, oportunidades e vínculos de domínio foram preservados. As tabelas de infraestrutura e o histórico de migrations Laravel foram mantidos; a configuração de fila usa o driver null. Não foi necessário alterar a estrutura do banco nesta revisão.

## Problemas corrigidos

- A seleção de documentos recarrega o acervo no início da listagem/painel, evitando usar dados desatualizados quando services são reutilizados.
- Candidatos de evidência respeitam a validade até o prazo da oportunidade.
- Upload manual compartilha a trava com busca/exclusão de anexos e remove arquivos armazenados quando a criação do registro falha.
- Falha de armazenamento de download é registrada; PDFs corrompidos ou com extração incompleta deixam a busca como parcial.
- Datas de validade sem horário são exibidas sem deslocamento de um dia por UTC.
- Falha ao atualizar a tela após uma ação não encobre o erro original da consulta.
- Respostas HTTP sem JSON produzem uma mensagem útil, preservando o status HTTP.
- Leitura de acervo e anexos distingue IDs iguais ao excluir um anexo.
- Navegação é bloqueada durante uma ação e a seleção de PDF é limpa ao sair da listagem.
- Resumos agora possuem endpoint e botão próprios; erro SMTP não marca resultados pendentes como enviados.
- Composer usa o único frontend Vue e não inicia listeners de fila.

## Limites

Consultas e envios são iniciados pelo usuário e executados na requisição HTTP. Consultas extensas podem ultrapassar limites do navegador/proxy/servidor; a tela orienta conferir o histórico antes de repetir quando a resposta não é válida. A comparação documental permanece textual e preliminar. Os testes simulam PNCP e SMTP; não constituem homologação com serviços externos e PDFs reais.

## Validação da revisão

47 testes de backend aprovados, com 261 assertions no MySQL isolado licitaRadar_test_bd; 13 testes de frontend aprovados, incluindo renderização Vue, ações manuais e transporte HTTP/CSRF; build Vue aprovado e formatação PHP aplicada. PNCP e SMTP simulados nos testes. O banco da aplicação foi acessado somente para retirar os jobs obsoletos de PNCP.

