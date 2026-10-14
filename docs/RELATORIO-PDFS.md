# Relatório de alterações — PDFs e anexos

Data: 14/10/2026, America/Sao_Paulo.

## Comportamento entregue

- Retirados “Ler páginas” dos anexos e “Ler texto” do acervo, junto com o diálogo que exibia texto extraído.
- Nomes dos arquivos viraram links para abrir o PDF original em outra aba. O arquivo é obtido do storage privado pelo endpoint autenticado; JWT permanece no cabeçalho, sem exposição em URL ou publicação do storage. A aba é aberta no clique e recebe o PDF após o download, com tratamento para popup bloqueado, falha de acesso e arquivo ausente.
- Busca PNCP e uploads de editais apenas armazenam PDFs; mantêm pastas por oportunidade e deduplicação por SHA-256. Botão de upload renomeado para “Adicionar PDF”; removidos avisos de extração/OCR dos anexos. Novos títulos PNCP não recebem extensões `.pdf` duplicadas.
- Status documental passou a `stored` (“PDF armazenado”). Não é uma afirmação de legibilidade ou habilitação.
- Leitura do acervo ocorre diretamente dos arquivos por `PdfTextReader`, em memória. As páginas são reaproveitadas apenas durante a operação de comparação; não são gravadas na base. Seleção de oportunidades, filtros, resumo e exportação continuam usando compatibilidade com o acervo atual.
- Área de requisitos técnicos, cadastro, revisão e parecer baseado nesses requisitos removidos por confirmação do usuário. Compatibilidade com o acervo permanece visível. Indicador de requisitos pendentes removido do painel.

## Limpeza da base local

Aplicado `php artisan radar:remove-pdf-text`, comando idempotente de manutenção. As migrations de criação foram atualizadas; não foi criada migration incremental nem recriada a base.

| Dado | Antes | Depois |
| --- | ---: | ---: |
| Oportunidades | 188 | 188 |
| Registros de anexos | 19 | 19 |
| Documentos da empresa | 14 | 14 |
| Requisitos | 274 | Tabela removida |
| Coluna `documents.pages` | Existente | Removida |
| Coluna `company_documents.pages` | Existente | Removida |
| PDFs de anexos encontrados | 7 | 7 |
| PDFs do acervo encontrados | 14 | 14 |

Comparação de hashes antes/depois confirmou preservação dos identificadores das oportunidades, IDs/caminhos/SHA-256 dos documentos e bytes de todos os arquivos encontrados. Nenhum PDF foi movido, apagado ou modificado nesta limpeza. Removido também o registro da migration antiga de requisitos. Status de documentos normalizados e avisos antigos de falha na extração eliminados.

Pendência constatada antes da alteração: 12 registros de anexos já estavam sem seu arquivo no storage. Foram preservados; a abertura retorna 404 com “Arquivo PDF não encontrado no storage.” É necessário fornecer novamente esses PDFs. Uma busca repetida mantém a deduplicação por conteúdo dos registros existentes.

## Código obsoleto removido

- Backend: `DocumentService` (extração persistida, classificador de requisitos e candidatos), `RequirementService`, `RequirementController`, requests de cadastro/revisão, model `Requirement`, relação Eloquent de requisitos e migration da tabela antiga.
- API: rotas de requisitos e leitores JSON `GET /api/documents/{id}` e `GET /api/tender-documents/{id}`. Rotas de download e exclusão permanecem.
- Frontend: `DocumentReaderDialog`, `OpportunityRequirements`, `RequirementReviewDialog`, `useRequirements`, API de requisitos, estado `reading`, ação `readDocument`, estilos do leitor/requisitos e rótulos antigos de revisão/extração.
- Testes exclusivos das funcionalidades removidas eliminados; testes dos fluxos preservados atualizados para usar PDFs reais no storage de testes, sem gravar texto na base.
- Biblioteca de parsing de PDFs preservada porque é necessária à leitura do acervo em memória.
- Autoload otimizado do Composer regenerado após a exclusão das classes antigas; scripts temporários de auditoria removidos.

## Validação

A suíte PHP completa passou com 67 testes e 415 assertions no MySQL isolado. Depois do tratamento de arquivo ausente, os 2 testes de schema/limpeza foram repetidos e passaram com 22 assertions. Incluem leitura do arquivo atual em memória, mudança da compatibilidade quando o PDF muda, agrupamento, deduplicação, upload, exclusão, acesso autenticado e limpeza idempotente do schema sem perda de arquivos/cadastros. Frontend: 235 testes aprovados em 40 arquivos, incluindo abertura em outra aba, falha de acesso e popup bloqueado. Build Vite, Prettier, Pint e `git diff --check` aprovados. Varredura final confirmou ausência das classes/ações removidas no código de aplicação.

PNCP e e-mail foram simulados nos testes; nenhum download operacional ou envio SMTP foi disparado. A conferência da base local usou leitura das contagens e hashes, sem expor conteúdo dos PDFs ou credenciais.
