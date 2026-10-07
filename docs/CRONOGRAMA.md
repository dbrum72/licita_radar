# Cronograma de entregas — LicitaRadar

Atualizado em: 07/10/2026. Fuso: America/Sao_Paulo.

Este cronograma organiza a sequência de entregas e seus critérios de aceite. Datas e durações ainda não foram acordadas; não representam compromissos de prazo. As etapas futuras são planejamento, não autorização para implementá-las neste momento.

## Etapas

| Etapa | Entrega | Situação | Dependência | Critério de aceite |
| --- | --- | --- | --- | --- |
| 1 | Fundação da aplicação | Implementada e validada localmente | Nenhuma | Laravel + Vue funcionando, MySQL `licitaRadar_bd`, autenticação, identidade baseada no Design System, migrations de criação por tabela e testes passando. |
| 2 | Radar PNCP orientado pela documentação da empresa, filtros e automação | Seleção documental implementada e validada por testes; homologação operacional e com PDFs reais pendente | Etapa 1, acervo da empresa e disponibilidade do PNCP | Busca baseada exclusivamente em certidões, atestados, RRT e habilitação do CNPJ, confrontados com o objeto do edital; compatibilidade fundamentada em documentos/trechos; pendência explícita quando faltar evidência; consulta paginada completa, sem duplicatas, filtros de refinamento, processamento da fila e confirmação da execução diária às 06:00. |
| 3 | Acervo da empresa e análise documental inicial | Implementado; homologação com PDFs reais pendente | Etapas 1–2 e exemplos documentais | Upload dos tipos acordados, tabelas separadas, extração por página, evidências rastreáveis, revisão dos requisitos e vencimentos conferidos com casos reais. |
| 4 | Estabilização e piloto de uso | Planejada; próxima entrega de consolidação | Homologação das etapas 2–3 | Rodadas completas de consulta e anexos, erros diagnosticáveis, testes dos resumos no Mailpit e revisão do fluxo pelo usuário. |
| 5 | Ampliar a interpretação documental | Backlog proposto, escopo a definir | PDFs representativos e resultados do piloto | Definir necessidade de OCR e análise de requisitos complexos; validar extração e comparação contra revisão humana, sem aprovar habilitação apenas por palavras em comum. |
| 6 | Operação em produção | Planejada; ambiente a definir | Piloto aprovado e infraestrutura definida | Hospedagem, SMTP real, HTTPS, backups e execução diária independente do computador de desenvolvimento, com monitoramento de falhas. |
| 7 | Viabilidade econômica e de mercado | Adiada pelo usuário | Dados de custos, capacidade, margem e critérios de decisão | Parecer econômico fundamentado, com premissas e distinção entre estimativas e dados conhecidos. |
| 8 | Preparação/envio de inscrições e propostas | Adiada pelo usuário | Regras dos portais, credenciais e aprovação do novo escopo | Definir fluxo de preparação, revisão e autorização do envio antes de implementar ações externas. |
| 9 | Alertas por WhatsApp | Adiada pelo usuário | Escolha do provedor e aprovação do novo escopo | Integração configurada e alertas validados com destinatários autorizados. |

## Próxima entrega: consolidação do MVP

Implementado em 07/10/2026: busca e seleção orientadas exclusivamente pelo acervo, com especialidades e evidências por página. Perfis agora refinam alertas; palavras-chave manuais não determinam a coleta. Próxima prioridade: homologar a qualidade do confronto textual com PDFs e editais reais.

Critérios de aceite do novo objetivo:

- Confrontar o objeto do edital com as capacidades e atividades presentes nas certidões, atestados, RRT e habilitação do CNPJ cadastrados.
- Fundamentar a compatibilidade com referência aos documentos e trechos utilizados, mantendo a revisão dos requisitos para o parecer documental.
- Validar casos compatíveis, incompatíveis e com documentação ausente ou insuficiente, sem recomendar oportunidades por palavras-chave genéricas independentes do acervo.
- Reavaliar a compatibilidade das oportunidades quando a documentação da empresa mudar.
- Permitir descarte reversível até o vencimento, exclusão de descartadas vencidas e bloqueio persistente de reimportação por identificador PNCP; coletar somente editais novos com propostas abertas. Exportar até 50 selecionadas abertas em PDF único com dados e evidências. Implementado e validado por testes; homologação operacional e revisão do fluxo pelo usuário ainda pendentes.

1. Diagnosticar a falha da última importação PNCP e validar uma rodada completa. A última consulta terminou com falha, preservando resultados parciais; importação parcial não constitui aceite operacional.
2. Conferir o processamento dos anexos pendentes e falhos e os formatos não PDF.
3. Validar os PDFs de certificações, atestados, RRT e CNPJ da empresa. O usuário informou que fornecerá exemplos; a homologação desses exemplos ainda não foi concluída.
4. Validar uma oportunidade do início ao fim: edital, exigência, evidência da empresa, revisão e parecer preliminar.
5. Testar o resumo de oportunidades no Mailpit e uma execução da tarefa diária. Já foi validada a captura de uma mensagem de teste, mas isso não substitui o teste do resumo completo.

## Limites da entrega atual

- A busca é baseada no acervo; o confronto usa vocabulário explícito de especialidades e exige apoio documental para todas as especialidades reconhecidas no objeto. Não realiza interpretação semântica integral ou comprovação de quantitativos. CNAEs de arquitetura e engenharia têm reconhecimento inicial; outras atividades precisam de calibração.
- A triagem do objeto e as sugestões de evidência usam heurísticas textuais.
- O parecer exige revisão humana e não garante habilitação jurídica ou técnica.
- PDFs digitalizados podem exigir OCR; OCR automático não está implementado.
- O desenvolvimento usa Mailpit, sem entrega de e-mails externos.
- A tarefa Windows exige computador ligado e sessão iniciada.
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
