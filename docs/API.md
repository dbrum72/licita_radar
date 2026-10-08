# Backend API e frontend independente

Decisão vigente em 08/10/2026: Laravel serve exclusivamente API e arquivos privados autenticados; Vue é servido separadamente. Não há autenticação por sessão, cookie ou CSRF. Todas as operações PNCP e resumos por e-mail continuam iniciadas manualmente na interface.

## Execução local

Na raiz, execute `./scripts/start.ps1` para a API em `http://127.0.0.1:8000`. Em outro terminal, execute `./scripts/frontend.ps1` para a interface em `http://127.0.0.1:5173`.

O script usa Node.js nativo como entrada HTTP na porta 8000 e quatro processos PHP locais nas portas 9081–9084, todos vinculados somente a `127.0.0.1`. Duas instâncias atendem GET/HEAD/OPTIONS; duas atendem ações de escrita, com distribuição pela menor quantidade de requisições em andamento. Uma consulta PNCP longa não ocupa a capacidade reservada para inicialização e leituras. Não há dependência adicional, serviço instalado, fila ou worker de tarefas. Todas as operações continuam iniciadas manualmente. JWT, CORS e cache/banco compartilhados permanecem no Laravel; corpos e respostas são transmitidos em fluxo. Nenhuma requisição é repetida pelo intermediário em caso de falha.

Use Node e PHP disponíveis no PATH. `composer run dev` também chama esse script no Windows. Não execute `php artisan serve` em paralelo: ele usa o servidor antigo de uma requisição por vez e pode ocupar a porta 8000. O script recusa portas ocupadas; ao encerrar normalmente, finaliza os processos PHP que criou. Em interrupção forçada do lançador, confira processos/portas antes de reiniciar. Logs PHP ficam em `backend/storage/logs/php-http-*.log`.

Se a interface não iniciar, confira `http://127.0.0.1:8000/up` e o terminal do backend. Autenticação, painel e lista de perfis têm limite de 15 segundos; a tela autenticada é liberada antes da carga do painel. Não existe reinício automático ou repetição automática de consultas. Reiniciar o servidor interrompe a requisição em andamento; confira seu estado antes de refazer uma ação. A capacidade é finita (duas leituras e duas ações simultâneas); não é configuração de produção.

Verificação do transporte: `node --test scripts/http-server.test.mjs` na raiz. Cobre leituras durante ação pendente, transmissão de JWT/corpo e erro sem repetir POST. Na API local real, requisição lenta em `/up` foi mantida aberta enquanto saúde e auth/status responderam HTTP 200 (~300 ms), sem PNCP/SMTP.

O frontend usa `VITE_API_URL`, com padrão `http://127.0.0.1:8000/api`. Copie `frontend/.env.example` para `frontend/.env.local` para personalizar. Variáveis Vite são incorporadas ao build; recompilar após mudar o endereço de produção. `npm run build` gera somente `frontend/dist`; `npm run preview` permite conferir esse build localmente.

Backend e frontend têm instalações independentes: `composer install` em `backend`; `npm ci` em `frontend`. O Composer não instala nem compila a interface. Laravel não entrega a SPA nem possui rota de fallback para HTML. `/up` é o endpoint de saúde.

## Autenticação

- `GET /api/auth/status`: informa se a configuração inicial está disponível, sem emitir token.
- `POST /api/setup`: cria o primeiro administrador, exclusivamente via loopback enquanto não há usuários.
- `POST /api/login`: recebe `email` e `password`; devolve `user`, `access_token`, `token_type: Bearer` e `expires_in` em segundos.
- Todas as rotas privadas exigem `Authorization: Bearer <access_token>`, inclusive uploads, downloads e exportações.
- `GET /api/me`: retorna o usuário autenticado.
- `POST /api/logout`: invalida o token apresentado e retorna HTTP 204.

JWT HS256 é assinado e validado com `firebase/php-jwt`. São verificados assinatura, algoritmo fixo, emissor, destinatário, validade, identificador do token e usuário existente. Tokens ativos são registrados no cache do backend até sua expiração, permitindo logout imediato. Limpar o cache invalida os tokens. Em múltiplas instâncias, usar cache compartilhado e a mesma chave/configuração JWT.

O token dura 60 minutos por padrão (`JWT_TTL`). Não há renovação automática; após HTTP 401, o frontend limpa o token e os dados privados e solicita novo login. O navegador usa `sessionStorage`, limitado à aba, com armazenamento em memória caso indisponível; não envia cookies. Logout também limpa o estado local se a rede falhar.

`JWT_SECRET` permite configurar um segredo separado, aleatório, com pelo menos 32 bytes. Sem essa variável, a chave de assinatura é derivada da `APP_KEY` válida com separação de contexto. Nunca publicar a chave em configuração Vite. Trocar a chave invalida tokens anteriores.

## Consulta PNCP com seleção explícita

`POST /api/sync` exige JWT Bearer e corpo JSON `{"profile_ids": [1, 2]}`. IDs devem ser distintos, existentes e de perfis ativos. Array vazio aplica somente compatibilidade com o acervo, sem filtros/associações de perfil. Com IDs selecionados, exige atender a todos eles; uma exclusão em qualquer selecionado impede a importação. Perfis não selecionados não são aplicados. Corpo sem `profile_ids` ou seleção inválida retorna HTTP 422 antes de iniciar a consulta.

A seleção não altera o histórico global de identificadores consultados: somente inéditos são importados; trocar perfis não recupera resultados anteriormente rejeitados. Pendentes, salvas e descartadas continuam preservadas entre consultas. Não altera as regras do envio manual em `/api/digest`.

## CORS e implantação

`FRONTEND_URL` no backend contém origens autorizadas separadas por vírgula, incluindo esquema e porta. O padrão permite apenas `http://127.0.0.1:5173` e `http://localhost:5173`. Configure os domínios efetivos na implantação. CORS aceita o cabeçalho Authorization, sem credenciais de cookie, e expõe Retry-After/Content-Disposition.

A primeira origem de `FRONTEND_URL` também é o endereço do painel nos resumos por e-mail; coloque primeiro o endereço da interface usado pelos destinatários.

Hospede `frontend/dist` no servidor estático da interface e a API com raiz em `backend/public`. Use HTTPS e `APP_DEBUG=false` em produção. O navegador acessa diretamente o endereço da API configurado; não há proxy Vite obrigatório. Tabelas históricas de sessões/infraestrutura foram preservadas, sem alteração de migrations ou dados do domínio.

Biblioteca e uso de assinatura/validação: [documentação oficial PHP-JWT](https://github.com/googleapis/php-jwt).

## Validação da entrega

Os números desta seção registram a entrega de separação/JWT. A validação posterior do frontend está em [FRONTEND.md](FRONTEND.md): 228 testes em 39 arquivos. A atualização documental não executou novamente essas suítes.

56 testes PHP, 313 assertions, no MySQL isolado; todos os testes de domínio usam JWT real no cabeçalho, sem bypass do guard. Cobertura de assinatura/algoritmo incorretos, expiração, início de validade futuro, destinatário incorreto, usuário excluído, logout, perda do registro no cache, CORS e downloads privados. 25 testes frontend aprovados; build independente, Prettier, Pint, Composer e verificação de diff aprovados. Não houve consulta real PNCP nem envio real SMTP.
