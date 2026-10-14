# Documentação do LicitaRadar

Atualizado em 14/10/2026, fuso America/Sao_Paulo.

## Estado atual

- Consolidação de 14/10/2026: decisões recentes de CONTEXT.md e CRONOGRAMA.md prevalecem sobre descrições históricas. O vencimento considera a data em America/Sao_Paulo: o dia do prazo permanece aberto integralmente, preservando o horário original no banco.
- Backend organizado por domínio conforme BACKEND.md, com serviços de autenticação/registro, injeção readonly, FormRequests e comandos em Console/Commands. Empresa única e contratos públicos preservados.
- Execução local da API por Node nativo e quatro processos PHP, com capacidade separada para leituras e escritas; interface Vue independente. Seguir API.md para iniciar e diagnosticar os servidores.
- Consultas PNCP, busca de anexos e envio de resumos por e-mail são iniciados manualmente na interface. O backend usa a API PNCP na requisição HTTP, sem jobs, workers ou agendamento.
- Backend Laravel exclusivo para API, separado do frontend Vue, com autenticação JWT Bearer. Downloads privados também exigem token.
- Controllers, Models, FormRequests e Services organizam o backend; views por domínio, componentes compartilhados, composables, services e módulos de API organizam o frontend.
- Controles, cards e tabela usam componentes importados do Legalis e adaptados ao tema Terra Solar do LicitaRadar, com os respectivos testes.
- Perfis ativos são selecionáveis por checkboxes somente em Oportunidades. A importação exige correspondência a todos os escolhidos e compatibilidade com o acervo; sem seleção, usa somente o acervo. Uma exclusão em qualquer perfil selecionado impede a importação.
- O marcador laranja preenchido indica uma oportunidade salva. Um clique alterna essa marcação; o filtro “Salvas” recupera as oportunidades marcadas.
- A tela principal acumula oportunidades ainda não avaliadas entre consultas; salvas e descartadas ficam em filtros separados. A consulta ignora identificadores PNCP já registrados e acrescenta somente inéditos.

## Guia de leitura

| Documento | Conteúdo |
| --- | --- |
| [CONTEXT.md](CONTEXT.md) | Decisões, estado para retomada e histórico. |
| [CRONOGRAMA.md](CRONOGRAMA.md) | Entregas, validações e pendências. |
| [BACKEND.md](BACKEND.md) | Arquitetura do backend baseada no Legalis, serviços por domínio e responsabilidades. |
| [API.md](API.md) | Execução independente, JWT, CORS e implantação. |
| [FRONTEND.md](FRONTEND.md) | Views, componentes, tema, testes e interações. |
| [COMPARACAO_LEGALIS.md](COMPARACAO_LEGALIS.md) | Práticas adotadas da aplicação irmã. |
| [REVISAO.md](REVISAO.md) | Revisão MVC e remoção de código obsoleto. |
| [VARREDURA.md](VARREDURA.md) | Falhas corrigidas e validação da varredura de erros. |
| [Prévia de e-mail](previews/email-digest.html) | Resumo HTML Terra Solar com dados fictícios; envio manual. |
| [AGENTS.md](AGENTS.md) | Regras de migrations e manutenção documental. |

Validação executada em 14/10/2026: backend com 69 testes e 412 assertions no MySQL isolado; frontend com 237 testes em 43 arquivos; 2 testes do transporte HTTP; build Vite, Prettier, Pint e verificação de diff aprovados. As tentativas iniciais de PHP, Vitest e transporte encontraram restrições do sandbox; as respectivas execuções com permissão passaram. PNCP e SMTP simulados; homologação operacional com serviços externos e PDFs reais permanece pendente. Não houve nova conferência visual nesta atualização.

Seções históricas não substituem as decisões atuais. Para iniciar os servidores, seguir [API.md](API.md), sem executar instruções antigas de workers ou scheduler.
