# LicitaRadar

Aplicação Laravel 13 + Vue 3 para monitorar licitações de projetos e anteprojetos de engenharia e arquitetura no PNCP, selecionando oportunidades exclusivamente pelo confronto do objeto com certidões, atestados, RRT e habilitação do CNPJ da empresa. Interface baseada nos tokens e no tema Terra Solar da pasta `Design System`, que é somente referência e não foi alterada.

Documentação do projeto: [cronograma de entregas](docs/CRONOGRAMA.md) e [contexto de retomada](docs/CONTEXT.md).

Em Oportunidades, use **Descartar** para retirar um item da seleção normal. Marque o filtro **Descartadas** e aplique os filtros para consultar essa lista e restaurar itens somente antes do vencimento. Depois do vencimento, a limpeza exclui o cadastro e seus vínculos no banco, mantendo apenas o identificador PNCP consultado para impedir reimportação. O descarte é compartilhado pelos usuários da empresa. Para exportar, marque **Selecionar para PDF** em cada oportunidade ou **Selecionar esta página**, depois clique em **Exportar selecionadas em PDF**. Um único arquivo reúne até 50 itens, com dados do edital, link PNCP e evidências documentais. A seleção acompanha páginas e filtros, mas é limpa ao recarregar a tela ou sair. A exportação exige prazo aberto e compatibilidade atual, recusando descartadas ou itens sem evidência suficiente.

## Executar no Windows

Requisitos: PHP 8.3+, Composer e Node 22.18+ (ou versão compatível com Vite 8). As dependências instaladas e o banco local estão disponíveis neste ambiente.

```powershell
# Primeiro terminal, na raiz do projeto
./scripts/start.ps1
```

Em outro terminal na raiz, execute `./scripts/frontend.ps1`. Acesse http://127.0.0.1:5173; a API permanece em http://127.0.0.1:8000. No primeiro acesso local, crie o administrador com senha de pelo menos 12 caracteres. Não existe senha padrão. A aplicação é para uma única empresa: usuários autenticados compartilham o acervo, as oportunidades e os perfis.

O servidor local usa Node nativo e quatro processos PHP HTTP, com capacidade separada para leituras e ações manuais. Assim, consultas PNCP longas não ocupam as requisições de inicialização. Não execute `php artisan serve` em paralelo. Sem jobs, filas ou consultas automáticas; detalhes e diagnóstico em [API.md](docs/API.md).

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

Para desenvolvimento do Vue: `npm run dev` em `frontend`, com a API Laravel ativa na porta 8000. Abra http://127.0.0.1:5173. O build é gerado em `frontend/dist` e pode ser conferido com `npm run preview`. Configure `VITE_API_URL` para apontar ao endereço da API.

## Banco e integração

Migrações incluem usuários, sessões, filas, cache, oportunidades (ID PNCP único), perfis e seus resultados, metadados dos PDFs privados com SHA-256 e histórico de consultas. Documentos da empresa ficam exclusivamente em `company_documents`; `documents` guarda editais e anexos. Não há texto de páginas nem tabela de requisitos na base. Cada tabela possui uma migration de criação com sua estrutura final, sem migrations incrementais. Bases existentes são sincronizadas com `php artisan radar:remove-pdf-text`, que remove o texto e os requisitos antigos sem apagar arquivos ou cadastros. Downloads autenticados usam `/api/documents/{id}/download` para o acervo e `/api/tender-documents/{id}/download` para editais. Valores monetários usam decimal e datas seguem `America/Sao_Paulo`.

Configure `DB_CONNECTION=mysql`, `DB_DATABASE=licitaRadar_bd`, `DB_HOST`, `DB_PORT`, `DB_USERNAME` e `DB_PASSWORD` no `.env`, execute `php artisan radar:database` e depois as migrações. Credenciais de produção não estão incluídas. Se o usuário não tiver permissão CREATE, peça ao administrador do MySQL para criar o banco com charset utf8mb4 e collation utf8mb4_unicode_ci.

O cliente usa a API oficial `https://pncp.gov.br/api/consulta/v1/contratacoes/proposta`, que consulta contratações com recebimento de propostas aberto, paginada pelas modalidades configuradas, mesmo sem perfis. O limite superior da consulta é de 365 dias futuros, configurável em `radar.proposal_days`. Cada prazo é novamente conferido antes de importar; datas ausentes, inválidas ou encerradas são recusadas. A coleta acrescenta somente editais ainda não consultados e compatíveis com o acervo. `pncp_consultations` guarda identificadores e datas da primeira consulta, inclusive dos recebidos mas recusados; registros já consultados não são sobrescritos nem incluídos novamente, mesmo após exclusão ou prorrogação. A migration preenche esse controle com as oportunidades já existentes sem alterar seus dados. Mudanças no acervo reavaliam a apresentação das oportunidades existentes, mas não reimportam IDs consultados. Perfis refinam alertas por UF, modalidade, órgão, valor e prazo; palavras-chave opcionais refinam o objeto do edital.

A limpeza de descartadas vencidas ocorre ao consultar pelo navegador e ao abrir painel/listagem/detalhe. O acervo e os identificadores consultados são preservados. A listagem e a exportação mostram apenas prazos abertos.

O confronto usa vocabulário explícito de especialidades e reconhecimento inicial dos CNAEs de arquitetura e engenharia. Todas as especialidades reconhecidas no objeto precisam de evidência legível nos PDFs da empresa, com validade até o prazo do edital. Certidões genéricas, documentos vencidos ou dependentes de OCR não sustentam compatibilidade. O confronto é textual e preliminar: não comprova quantitativos, complexidade ou habilitação integral e precisa ser calibrado com PDFs reais. Painel, detalhe e resumos por e-mail também reavaliam o acervo atual.

A triagem textual do objeto classifica escopo como aderente, revisão ou inclui obra. É uma heurística conservadora, não uma interpretação integral do edital. Lotes separados e termos ambíguos precisam de revisão.

## PDFs e compatibilidade documental

PDFs de até 20 MB ficam no storage privado. Anexos são baixados somente pelo botão Buscar anexos no PNCP e reunidos em `storage/app/private/documents/opportunities/{id}/`; uploads manuais usam a mesma pasta. A busca usa identificadores oficiais, deduplica por conteúdo e sinaliza anexos não PDF. O nome do arquivo abre o PDF original em outra aba mediante requisição autenticada, sem token na URL. Excluir anexo remove somente esse PDF e seu registro.

Não há leitor de páginas, extração automática de requisitos ou texto dos PDFs persistido na base. O acervo é lido diretamente dos arquivos em memória para confrontar especialidades com o objeto. Trechos de compatibilidade são calculados para a resposta, sem gravação. Não há OCR automático: PDFs sem texto legível não sustentam compatibilidade. A revisão integral do edital cabe ao usuário.

## Operação manual pelo navegador

Todas as consultas ao PNCP são iniciadas pelo botão Consultar PNCP no LicitaRadar. A API oficial é chamada pelo servidor na mesma requisição HTTP; a tela aguarda o resultado e atualiza o histórico e as oportunidades ao terminar. Falhas preservam resultados parciais e permitem nova tentativa manual. Não há fila, worker, consultas programadas ou envios automáticos de e-mail. Anexos continuam sendo buscados pelo botão no detalhe da oportunidade.

A tarefa Windows diária foi desativada. Os scripts de automação e as classes de jobs PNCP foram removidos. Registros pendentes e falhos desses jobs foram retirados do banco. A limpeza de descartadas vencidas ocorre durante ações no navegador, sem execução periódica. Na tela Perfis de busca, o botão Enviar resumos por e-mail envia os resultados novos aos perfis ativos com destinatário cadastrado. O envio ocorre na requisição, sem fila, e revalida o acervo e os filtros atuais. SMTP indisponível mantém os resultados não entregues pendentes. Em desenvolvimento, inicie ./scripts/mailpit.ps1 e leia as mensagens em http://127.0.0.1:8025.

Em produção use HTTPS, `APP_ENV=production`, `APP_DEBUG=false`, backup do banco e armazenamento privado. Se necessário crie usuários via `php artisan radar:user email@empresa.com`. A configuração inicial via web só aceita acesso de loopback enquanto não há usuários.

## Validação

```powershell
cd backend
../scripts/php.ps1 artisan radar:database --testing
../scripts/php.ps1 artisan test
cd ../frontend
npm test
npm run format:check
npm run build
```

Os testes usam exclusivamente o banco MySQL isolado `licitaRadar_test_bd`, que pode ser recriado pela suíte. Nunca aponte os testes para o banco da aplicação. Eles cobrem acesso privado, triagem de escopo, leitura do acervo em memória, ausência de texto persistido, limpeza dos dados legados, paginação/deduplicação de PNCP, preservação de salvos, falhas externas e validação de perfis. A consulta real depende da disponibilidade e limites do PNCP. Há espaçamento entre chamadas e espera progressiva para HTTP 429/5xx; a falha preserva resultados parciais sem avançar o marco da última consulta bem-sucedida. SMTP e PDFs reais ainda precisam de validação com os dados da empresa.

Documentação oficial: https://pncp.gov.br/api/consulta/swagger-ui/index.html

## Organização do código

- `backend/app/Models`: entidades e relações Eloquent.
- `backend/app/Http/Controllers`: controllers por área, responsáveis pelas respostas HTTP.
- `backend/app/Http/Requests`: validação das entradas.
- `backend/app/Integrations/Pncp`: contrato, transporte HTTP e mapeamento da API externa.
- `backend/app/Services`: coleta PNCP, consultas, compatibilidade, exportação, PDFs, perfis, descarte e resumos.
- `backend/routes/api.php`: declaração das rotas autenticadas com JWT Bearer, sem regras de negócio em closures.
- `frontend/src/views/<domínio>`: páginas `*Page.vue` e componentes exclusivos em `components`.
- `frontend/src/layouts`: composição da área autenticada.
- `frontend/src/components`: componentes compartilhados de layout, navegação, autenticação, interface e acesso aos PDFs.
- `frontend/tests`: testes espelhando views/components/layouts, testes de services/composables e integração.
- `frontend/src/composables`: estado reativo e ações por domínio; useLicitaRadar coordena a navegação e useWorkspace compartilha o contexto.
- `frontend/src/api`: chamadas por domínio, compartilhando transporte HTTP/JWT.
- `frontend/src/services`: transporte HTTP/JWT, download e atualização após ações.
- `frontend/src/constants`, `utils` e `config`: opções compartilhadas, formatação e navegação.

Práticas adaptadas da aplicação irmã Legalis e evidências de validação: [comparação técnica](docs/COMPARACAO_LEGALIS.md). Para aplicar a formatação da interface, execute `npm run format` em `frontend`.

A interface e o build Vite existem somente em `frontend`; Laravel entrega exclusivamente a API; o build `frontend/dist` é servido de forma independente. Configurações e tabelas históricas de infraestrutura do Laravel foram preservadas. A conexão de fila está desativada (`null`). Não houve alteração estrutural do banco nesta revisão. Consulte `docs/REVISAO.md` para os problemas corrigidos e a validação.

Autenticação, configuração CORS e implantação separada: [API.md](docs/API.md). JWT substitui sessão e CSRF, com validade de 60 minutos e revogação ao sair.

Estrutura de páginas, componentes e testes adaptada do Legalis: [FRONTEND.md](docs/FRONTEND.md).

As páginas usam os componentes de UI e formulários importados do Legalis, com estilos adaptados aos tokens do tema LicitaRadar. Botões, campos, seletores, checkboxes, upload, cards e tabela de histórico são encapsulados nos componentes compartilhados.
