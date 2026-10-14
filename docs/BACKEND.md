# Arquitetura do backend — referência Legalis

Atualizado em 14/10/2026, America/Sao_Paulo.

## Referências examinadas

O backend de `../../legalis/backend` foi consultado para comparar árvore de pastas, controllers, FormRequests, models, serviços, integrações, providers, bootstrap, comandos e testes. Exemplos usados: AuthController e Requests/Auth, Registration/RegisterOrganization, Publications/ImportDjenPublications, OrganizationMembers/UpdateOrganizationMemberRole, FolderDocumentController, Folder, Djen/Contracts/DjenClient, AppServiceProvider e Console/Commands/SyncDjenPublications. Legalis permanece somente leitura.

## Estrutura implementada

```text
backend/app/
  Console/Commands/       CreateRadarDatabase, CreateRadarUser, RadarStatus, RemoveStoredPdfText
  Http/Controllers/      controllers por recurso, métodos REST e respostas tipadas
  Http/Requests/
    Auth/                LoginRequest, SetupRequest
    *.php                validação de perfis, documentos e oportunidades
  Integrations/Pncp/
    Contracts/           PncpClient
    HttpPncpClient.php    transporte e paginação
    PncpOpportunityMapper.php
  Models/                entidades, casts e relações Eloquent tipadas
  Mail/                  OpportunityDigest
  Providers/             bindings, autenticação e limitadores
  Services/
    Auth/                AuthenticateUser, JwtService
    Registration/        RegisterAdministrator
    CompanyDocuments/    CompanyScopeService
    Consultations/       SyncService
    Dashboard/           DashboardService
    Digests/             DigestService
    Documents/           PdfTextReader, DocumentStorageService, TenderAttachmentService
    Opportunities/       consulta, compatibilidade, ciclo de vida, PDF e ScopeClassifier
    SearchProfiles/      SearchProfileService
  Support/               RadarOptions
```

Controllers recebem entradas validadas e produzem respostas HTTP. Login e configuração inicial delegam os fluxos a serviços específicos, seguindo a separação usada no Legalis. SetupRequest conserva a restrição de loopback e ausência de usuários; o serviço revalida a ausência de usuários dentro da trava. Bookmark e descarte também usam FormRequests.

Serviços declaram colaboradores no construtor, com propriedades readonly, em vez de procurar serviços pelo container dentro dos métodos. Integração PNCP conserva contrato e binding no provider, transporte separado do mapper. Classes concretas são resolvidas pelo container do Laravel.

Os controllers de oportunidades, documentos e perfis usam métodos REST quando aplicável. Downloads e exportação mantêm respostas próprias. Em 14/10/2026, leitores JSON de páginas e rotas de requisitos foram removidos. PDFs são armazenados sem extração persistida; `CompanyScopeService` usa `PdfTextReader` para ler o acervo em memória. `radar:remove-pdf-text` sincroniza o schema existente com as migrations de criação revisadas, preservando arquivos e cadastros. Relatório em [RELATORIO-PDFS.md](RELATORIO-PDFS.md).

## Regras do LicitaRadar

A organização segue os padrões do Legalis adaptados ao domínio de licitações. Permanecem empresa única, JWT Bearer existente, operações exclusivamente manuais e síncronas, MySQL e migrations de criação por tabela. Multiempresa, permissões de escritórios, jobs e scheduler do Legalis não fazem parte deste escopo. A regra de vencimento usa a data em Brasília e mantém hoje aberto durante todo o dia.

Na reorganização de 13/10/2026 não houve mudanças de schema/dados ou frontend. A remoção do texto e dos requisitos em 14/10/2026 alterou as migrations de criação e sincronizou a base local, conforme RELATORIO-PDFS.md. Não houve chamadas PNCP ou envio SMTP reais nesta alteração.

## Validação

69 testes PHP e 412 assertions aprovados no MySQL isolado `licitaRadar_test_bd`; Pint aprovado. Autoload regenerado. A suíte cobre JWT, acesso privado, perfis, importação, documentos, evidências, triagem, PDF e vencimento por data. Integrações externas são simuladas; homologação operacional permanece pendente.
