<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><title>LicitaRadar - Oportunidades selecionadas</title>
<style>
@page { margin: 48px 44px 55px; }
body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5px; color: #40392f; line-height: 1.45; }
h1 { font-size: 24px; color: #496149; margin: 0 0 8px; }
h2 { font-size: 14px; margin: 0 0 12px; line-height: 1.45; }
h3 { font-size: 11px; color: #496149; margin: 14px 0 8px; }
p { margin: 6px 0; }
.muted { color: #6d665c; }
.intro { padding-bottom: 14px; border-bottom: 2px solid #b59a69; margin-bottom: 18px; }
.opportunity { page-break-before: always; }
.opportunity.first { page-break-before: auto; }
.eyebrow { color: #807054; font-size: 9px; margin-bottom: 8px; }
.meta { width: 100%; border-collapse: collapse; margin: 14px 0; }
.meta td { padding: 6px 10px; border-bottom: 1px solid #e2dbce; vertical-align: top; }
.meta td:first-child { width: 100px; color: #6d665c; }
.evidence { background: #f5f2eb; padding: 10px 12px; margin-bottom: 10px; page-break-inside: avoid; }
.evidence strong { font-size: 10px; }
a { color: #496149; word-wrap: break-word; }
.notice { padding: 10px 12px; background: #f0eadc; margin: 14px 0; }
</style></head>
<body>
<div class="intro"><h1>LicitaRadar</h1><h2>Oportunidades selecionadas</h2>
<p class="muted">Gerado em {{ $generatedAt->format('d/m/Y H:i') }} (Brasília) | {{ $opportunities->count() }} oportunidades</p>
<p>Seleção fundamentada no acervo documental da empresa e no objeto publicado no PNCP.</p></div>
@foreach ($opportunities as $item)
<section class="opportunity {{ $loop->first ? 'first' : '' }}">
<p class="eyebrow">OPORTUNIDADE {{ $loop->iteration }} DE {{ $opportunities->count() }} | PNCP {{ $item->pncp_id }}</p>
<h2>{{ $item->object }}</h2>
<table class="meta">
<tr><td>Órgão</td><td>{{ $item->agency }}</td></tr>
<tr><td>Local</td><td>{{ $item->city ?: 'Município não informado' }} / {{ $item->state ?: 'UF não informada' }}</td></tr>
<tr><td>Modalidade</td><td>{{ $item->modality }}</td></tr>
<tr><td>Valor estimado</td><td>{{ $item->estimated_value !== null ? 'R$ '.number_format((float)$item->estimated_value,2,',','.') : 'Não informado' }}</td></tr>
<tr><td>Prazo</td><td>{{ $item->deadline ? $item->deadline->timezone('America/Sao_Paulo')->format('d/m/Y H:i').' (Brasília)' : 'Não informado' }}</td></tr>
<tr><td>Publicação</td><td>{{ $item->published_at ? $item->published_at->format('d/m/Y') : 'Não informada' }}</td></tr>
</table>
<p><a href="{{ $item->pncp_url }}">{{ $item->pncp_url }}</a></p>
<p class="notice">Compatibilidade textual preliminar. Não comprova habilitação integral. Revise todos os requisitos e anexos do edital antes de decidir pela participação.</p>
<h3>Confronto do objeto com a documentação da empresa</h3>
<p>{{ $item->company_match['reason'] }}</p>
@foreach ($item->company_match['evidence'] as $evidence)
<div class="evidence"><strong>{{ $evidence['name'] }} | página {{ $evidence['page'] }}</strong>
<p class="muted">Especialidades: {{ implode(', ',$evidence['concepts']) }}</p>
<p>{{ $evidence['excerpt'] }}</p></div>
@endforeach
</section>
@endforeach
</body></html>
