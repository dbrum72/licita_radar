# Documentação do LicitaRadar

Atualizado em 08/10/2026, fuso America/Sao_Paulo.

## Estado atual

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
| [API.md](API.md) | Execução independente, JWT, CORS e implantação. |
| [FRONTEND.md](FRONTEND.md) | Views, componentes, tema, testes e interações. |
| [COMPARACAO_LEGALIS.md](COMPARACAO_LEGALIS.md) | Práticas adotadas da aplicação irmã. |
| [REVISAO.md](REVISAO.md) | Revisão MVC e remoção de código obsoleto. |
| [VARREDURA.md](VARREDURA.md) | Falhas corrigidas e validação da varredura de erros. |
| [Prévia de e-mail](previews/email-digest.html) | Resumo HTML Terra Solar com dados fictícios; envio manual. |
| [AGENTS.md](AGENTS.md) | Regras de migrations e manutenção documental. |

Últimas validações registradas: backend com 65 testes e 371 assertions; frontend com 235 testes em 42 arquivos, sintaxe de 74 arquivos PHP, build e formatação aprovados na varredura de erros. Homologação operacional com PNCP, SMTP e PDFs reais permanece pendente; a conferência visual mais recente cobriu o login.

Seções históricas não substituem as decisões atuais. Para iniciar os servidores, seguir [API.md](API.md), sem executar instruções antigas de workers ou scheduler.
