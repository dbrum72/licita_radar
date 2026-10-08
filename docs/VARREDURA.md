# Varredura de erros — 08/10/2026

Revisados os fluxos de API/JWT, consulta PNCP, seleção de perfis, triagem, documentos, evidências e composição do frontend. Mantidos operação manual, tema, interseção dos perfis selecionados e histórico incremental global. Nenhuma migration ou alteração direta no banco da aplicação.

## Correções

| Falha encontrada | Correção e cobertura |
| --- | --- |
| Um perfil selecionado pausado/excluído entre a validação HTTP e o início do service podia ser ignorado, ampliando a consulta. O service também mantinha aplicação implícita de todos os ativos quando chamado sem seleção. | Service usa seleção vazia por padrão e confere os IDs selecionados antes de iniciar a consulta. Seleção desatualizada retorna erro de validação sem consultar PNCP ou criar histórico. Teste de regressão específico. |
| Um identificador já consultado com dados posteriormente incompletos podia falhar no mapper e interromper a consulta. | Identificadores conhecidos são ignorados antes do mapeamento, mantendo a proteção transacional contra duplicatas. Teste inclui um item conhecido incompleto seguido de uma oportunidade nova válida. |
| Evidência com extração que passou a falhar ou com página removida podia conservar o parecer de requisitos revisados. | Parecer revalida status e existência da página, além da validade. Revisão protege contra evidência inexistente e exige validade até o maior instante entre prazo e data atual. Teste conserva acervo compatível separado para verificar especificamente a evidência do requisito. |
| O resumo por e-mail apontava para APP_URL, agora endereço do backend API. | Link passa a usar a primeira origem configurada em FRONTEND_URL/CORS. Teste verifica o endereço da interface no corpo do resumo. |
| Rascunho de requisito e revisão podiam persistir ao trocar de edital. | Composable limpa os dados quando muda o identificador da oportunidade ou ao sair do detalhe. Atualização do mesmo edital preserva o rascunho. Dois testes de regressão. |

## Limites

Validação final: 65 testes PHP/371 assertions, 235 testes frontend em 42 arquivos, sintaxe de 74 arquivos PHP, build Vite, Pint e Prettier aprovados. Verificação de diff sem erros de whitespace.

As verificações automatizadas usam PNCP e SMTP simulados. Homologação operacional com APIs externas e PDFs reais continua pendente. A varredura e os testes não garantem ausência de todos os defeitos possíveis; registram as falhas corrigidas e a cobertura executada.
