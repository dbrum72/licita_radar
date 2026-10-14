LicitaRadar — {{ $profile->name }}

{{ count($opportunities) }} nova(s) oportunidade(s) para sua avaliação.

@foreach ($opportunities as $opportunity)
{{ $opportunity['object'] }}
Órgão: {{ $opportunity['agency'] }}
Local: {{ $opportunity['location'] ?: 'Não informado' }}
Modalidade: {{ $opportunity['modality'] }}
Prazo: {{ $opportunity['deadline'] }} ({{ config('app.timezone') }})
Valor estimado: {{ $opportunity['value'] }}
Edital no PNCP: {{ $opportunity['url'] }}

@endforeach
Acesse o painel: {{ $frontendUrl }}

Revise antes de decidir. A compatibilidade com o acervo é uma triagem preliminar e não comprova habilitação integral. Confira o edital e a validade das evidências.

Resumo enviado manualmente pela equipe no LicitaRadar.
