# Comparação com Legalis — 08/10/2026

Atualização de frontend: implementações, props e testes de componentes de UI/formulários do Legalis foram importados e adaptados ao tema do LicitaRadar. Views, componentes locais/compartilhados e testes espelhados estão descritos em [FRONTEND.md](FRONTEND.md).

Atualização posterior: por decisão do usuário, a autenticação por sessão/CSRF descrita nesta comparação foi substituída por JWT Bearer, com backend API e frontend independentes. Consulte [API.md](API.md) para a arquitetura vigente.

Foram examinadas a arquitetura, as integrações, a validação HTTP, o cliente de API, os composables, os diálogos e a configuração de testes/formatação da aplicação irmã `../../legalis`. Legalis foi usada somente como referência e não recebeu alterações.

## Práticas aplicadas

| Referência em Legalis | Aplicação no LicitaRadar |
| --- | --- |
| `backend/app/Integrations/Djen/Contracts/DjenClient.php`, `HttpDjenClient.php` e bindings no `AppServiceProvider` | Integração PNCP em `app/Integrations/Pncp`, com contrato injetado, transporte HTTP e mapper separados. Respostas malformadas são recusadas antes da importação. |
| FormRequests, incluindo `LegalPublicationIndexRequest.php`, e controllers por domínio | Validação de UF, datas estritas e intervalo de valores; opções de UF centralizadas. Mantida a organização MVC existente, com regras nos services e entradas nos requests. Paginação preserva filtros. |
| Limitadores nomeados no `AppServiceProvider` | Contadores separados para consulta PNCP, envio manual de resumos, login e configuração inicial. Consultar não consome o limite de enviar e-mail. Resposta inclui tempo de espera. |
| `frontend/src/api/client.js` e módulos como `folder-documents.js` | Módulos de API por domínio, usando transporte HTTP/CSRF compartilhado. Erros preservam status, campos inválidos e espera. Dados privados são limpos no logout e em HTTP 401. |
| `useRetryCountdown.js` | Contagem de espera independente por ação, com descarte do timer ao desmontar. O término da contagem não dispara requisições. |
| `AppDialog`, `AppConfirmDialog` e `useDeleteConfirmation.js` | Diálogo compartilhado com título acessível, controle de Tab/Escape, retorno de foco e fechamento bloqueado durante processamento. Exclusão de anexo exige confirmação explícita e conserva o diálogo em caso de falha. |
| `.prettierrc.json`, Vitest e Vue Test Utils | Formatação consistente, constantes/utilitários/configuração separados e testes DOM de interação dos modais. |

## Adequação ao escopo

LicitaRadar continua sendo uma aplicação de uma empresa, autenticada por sessão e CSRF. Não foram importadas regras financeiras, autenticação JWT, multiempresa, jobs ou agendamentos do Legalis. Consultas, anexos e resumos por e-mail continuam exclusivamente por botões no navegador, executados na mesma requisição HTTP. Não houve alteração de migrations ou dos dados da aplicação nesta comparação.

O transporte PNCP mantém tratamento de falhas e espera entre chamadas dentro da requisição manual. A contagem de espera da interface apenas informa quando o usuário pode tentar novamente.

## Validação

- Backend: 51 testes, 280 assertions, em MySQL isolado.
- Frontend: 22 testes em cinco arquivos, incluindo foco, teclado, exclusão, sessão e limites por ação.
- Build Vite, `npm run format:check`, Pint e `git diff --check` aprovados.
- PNCP e e-mail simulados nos testes; consulta externa e envio SMTP reais não foram executados.

Comandos de desenvolvimento: `../scripts/php.ps1 artisan test` em `backend`; `npm test`, `npm run build` e `npm run format:check` em `frontend`. `npm run format` aplica a formatação manualmente.
