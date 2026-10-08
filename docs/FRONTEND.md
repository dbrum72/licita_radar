# Arquitetura do frontend — referência Legalis

## Cores vigentes

Por solicitação do usuário, substituída somente a paleta com base na imagem do portal fornecida: azul-petróleo `#1e4d55`, terracota `#a6532b`, verde `#89aa96`, bege `#e7d6b8`, mostarda `#d8ad58` e fundo `#f2f4f5`. Componentes, views, layout e tipografia existentes foram preservados. As referências abaixo a Terra Solar descrevem a estrutura de tema mantida; suas cores anteriores foram substituídas. O e-mail de resumo acompanha a paleta.

## Seleção dos perfis da consulta

`components/profiles/ConsultationProfiles.vue` compõe AppCard e AppCheckbox para selecionar vários perfis ativos por IDs numéricos. É compartilhado pelo Painel e pela lista de Oportunidades. `useConsultationProfiles` isola a seleção, inicialmente vazia, e remove IDs de perfis pausados/excluídos ao atualizar as opções. `useOpportunities` envia uma cópia da seleção no corpo da consulta; `useLicitaRadar` carrega as opções e limpa a escolha ao encerrar a sessão.

Sem perfis marcados, aplica somente o acervo. A seleção vale para a próxima consulta e não altera a lista histórica de pendentes. Identificadores PNCP já consultados não são reavaliados ao trocar os perfis, conforme aviso na interface. Checkboxes ficam bloqueadas durante ações em andamento. A escolha permanece ao navegar entre telas, mas não é persistida após recarregar a aplicação.

Com vários perfis selecionados, cada oportunidade deve atender a TODOS: os filtros são combinados por interseção. Uma exclusão em qualquer selecionado impede a importação. A interface informa essa regra.

## Marcador de oportunidades salvas

Na triagem manual vigente, a lista padrão acumula pendentes de consultas anteriores. Salvar retira o item dessa lista e o disponibiliza em “Salvas”; remover a marcação o devolve às pendentes. Descartar remove a marcação de salvo e leva a “Descartadas”; restaurar devolve à triagem. Os dois filtros são mutuamente exclusivos. Após decidir, a lista e o painel são atualizados e o item sai da seleção para PDF. Os resultados seguem sujeitos ao prazo aberto e, fora de Descartadas, à compatibilidade atual com o acervo.

O marcador no canto superior direito de cada oportunidade alterna entre “Salvar oportunidade” e “Remover dos salvos”. Preenchido em laranja indica `bookmarked: true`; sem preenchimento indica que não está salva. O clique chama `toggleBookmark` em `useOpportunities`, persiste o valor pela API e atualiza o card após a resposta. O filtro “Salvas” restringe a listagem às oportunidades marcadas. Essa ação é independente de “Selecionar para PDF” e “Descartar”.

## Componentes HTML importados — decisão vigente

Após confirmação do usuário, a aplicação passou a substituir controles e estruturas HTML das páginas pelos componentes do Legalis, mantendo o tema do LicitaRadar. Foram importadas implementações e props de AppButton, AppIcon, AppCard, AppTable, AppInput, AppSelect, AppTextarea, AppCheckbox e AppFileUpload, junto de BaseField, AppField, controles internos, contexto de campo e utilitário pick. AppConfirmDialog usa o contrato de props/eventos do Legalis e compõe o diálogo acessível já adaptado.

Todos os botões e campos de páginas, layout e navegação usam esses componentes. Cards substituem painéis, indicadores e oportunidades; AppTable apresenta o histórico de consultas. Upload continua manual e autenticado, com seleção de um PDF, limite de 20 MB e feedback de rejeição/remoção. Textos, títulos, links externos e regiões semânticas permanecem HTML dentro das composições.

Os estilos originais do Legalis não foram aplicados. A adaptação usa os tokens existentes em `assets/styles`, estilos específicos dos componentes e pontes em `components/forms/theme.css` e `components/ui/theme.css`. Estas preservam cores, tipografia, contornos, estados e espaçamentos do LicitaRadar. As declarações de tokens existentes não foram alteradas. AppSelect foi ampliado para seleção múltipla mantendo valores numéricos; AppInput preserva o modificador number. Labels apontam para os controles por id, com hints/erros acessíveis.

Nove arquivos de testes de UI/formulários foram trazidos do Legalis. Foram acrescentados testes das adaptações, confirmação, remoção de PDF e uma verificação arquitetural para impedir controles nativos nas páginas. Os testes de fluxo existentes continuam cobrindo JWT e operações manuais. Tela de login conferida visualmente no navegador; áreas privadas verificadas pelos testes de componentes/fluxo, sem alterar credenciais ou dados para acesso visual.

As informações abaixo sobre CSS compilado idêntico descrevem a reorganização anterior. A importação dos componentes acrescenta CSS de adaptação ao tema existente.

Validação final da substituição: 228 testes em 39 arquivos aprovados; build Vite, Prettier e verificação de diff aprovados. Busca estrutural confirmou ausência de controles nativos nas páginas, layout e navegação. Sem consultas PNCP ou envio SMTP reais nesta entrega.

Organização adotada em 08/10/2026, após leitura de `legalis/frontend/src/views`, `components`, `layouts` e `tests`, além de `vitest.config.js`. A aplicação irmã permanece somente como referência.

## Responsabilidades e pastas

```text
frontend/
  src/
    App.vue                         composição de carregamento, login e área privada
    layouts/DefaultLayout.vue       estrutura da área autenticada e composição das páginas
    views/
      auth/LoginPage.vue
      dashboard/DashboardPage.vue
      documents/CompanyDocumentListPage.vue
      profiles/SearchProfileListPage.vue
      profiles/components/SearchProfileDialog.vue
      opportunities/OpportunityListPage.vue
      opportunities/OpportunityShowPage.vue
      opportunities/components/     parecer, anexos, requisitos e diálogos específicos
    components/
      layout/                       SideBar, HeaderBar e PageContainer
      navigation/                   SideBarNav
      public/AuthShell.vue          apresentação e slot para autenticação
      ui/                           AppDialog e ActionFeedback
      documents/DocumentReaderDialog.vue
    composables/                    estado reativo e ações por domínio
    api/                            chamadas à API por domínio
    services/                       HTTP/JWT, downloads e atualização após ações
  tests/
    App.spec.js
    layouts/
    views/                          espelha as páginas e seus components
    components/                     espelha layout, navigation, public, ui e documents
    composables/
    services/
    integration/workspace.spec.js   regressões entre estado, páginas e ações
    helpers/workspace.js            fixtures reativas para testes de interação
    setup.js                        desmontagem dos componentes após cada teste
  vitest.config.js
```

Como no Legalis, componentes compartilhados maiores usam pasta própria com `index.vue`; AppDialog separa sua declaração de props em `props.js`. Exports de layout, navigation e ui são agrupados em `index.js`. Componentes exclusivos de um domínio ficam em `views/<domínio>/components`. Não adicionar ao diretório compartilhado um componente usado exclusivamente em uma página.

Pages compõem a interface e conectam os composables existentes. Componentes de layout/navegação recebem props e emitem eventos; não consultam a API. OpportunityShowPage reúne componentes independentes de parecer, anexos e requisitos. O diálogo de leitura é compartilhado entre acervo e oportunidades; edição de perfil e revisão/exclusão de anexos pertencem às respectivas páginas.

A navegação de estado existente foi preservada nesta reorganização; não foram introduzidos Vue Router, Pinia, permissões ou domínios específicos do Legalis. Backend continua API JWT e o build continua em `frontend/dist`. Classes, tema e layout visual existentes foram preservados.

## Testes

Vitest usa jsdom e Vue Test Utils, alias `@` para `src` e descoberta exclusiva de `tests/**/*.spec.js`/`tests/**/*.test.js`. A integração SSR indica explicitamente ambiente Node. Testes não ficam em `src`.

Os testes de páginas exercitam ações do usuário e estados relevantes: login/configuração inicial, consultas manuais, falhas e espera, filtros/paginação, seleção limitada a 50 itens, descarte/restauração, envio manual de resumos, upload e download autenticado. Os testes de componentes verificam origem das evidências, revisão manual, exclusão explícita, conteúdo das páginas de PDF, foco/teclado, eventos de navegação e feedback. O teste de App verifica carregamento, login, área privada e remoção da área privada ao sair.

Use `npm test` para toda a suíte, `npm test -- tests/views/opportunities/OpportunityListPage.spec.js` para um arquivo e `npm run build` para compilação. `npm run format:check` verifica código e testes; `npm run format` aplica o padrão a ambos. As fixtures substituem chamadas de domínio por spies nos testes de interação; os testes de services e integração mantêm a cobertura da API/JWT e das regras de ações.

Validação final: 52 testes em 27 arquivos aprovados, build Vite e Prettier aprovados, sem referências aos caminhos antigos ou testes remanescentes em src. O CSS compilado manteve o mesmo conteúdo desta entrega anterior. Não houve alterações no backend, nas migrations, no banco ou no Legalis nesta reorganização.
