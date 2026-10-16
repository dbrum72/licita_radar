<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Relatório documental - oportunidade {{ $report['opportunity']['id'] }}</title>
    <style>
        @page { margin: 32px 36px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #243d41; line-height: 1.5; }
        h1 { font-size: 20px; color: #1e4d55; margin-bottom: 8px; }
        h2 { font-size: 15px; color: #1e4d55; border-bottom: 1px solid #e7d6b8; padding-bottom: 5px; margin-top: 22px; }
        h3 { font-size: 12px; margin-bottom: 4px; }
        h2, h3 { page-break-after: avoid; }
        p, li { overflow-wrap: break-word; }
        .notice { padding: 10px; background: #f5ecd8; border-left: 3px solid #d8ad58; }
        .meta { font-size: 9px; color: #586a6e; }
        .citation { padding-left: 10px; border-left: 2px solid #89aa96; font-size: 9px; margin: 8px 0; }
        .item { border-bottom: 1px solid #dce4e5; padding-bottom: 12px; }
        .keep { page-break-inside: avoid; }
        .status { font-weight: bold; color: #a6532b; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { text-align: left; border-bottom: 1px solid #dce4e5; padding: 6px; font-size: 9px; word-wrap: break-word; }
        th { background: #f2f4f5; }
    </style>
</head>
<body>
@php
    $analysis = $report['analysis'];
    $categories = ['tecnica_profissional' => 'Técnica profissional', 'tecnica_operacional' => 'Técnica operacional', 'registro_certificacao' => 'Registro / certificação', 'juridica' => 'Jurídica', 'fiscal' => 'Fiscal', 'economico_financeira' => 'Econômico-financeira', 'outros' => 'Outros'];
    $statuses = ['comprovada' => 'Comprovada', 'parcial' => 'Parcial', 'nao_comprovada' => 'Não comprovada', 'inconclusiva' => 'Inconclusiva', 'aderente' => 'Aderente', 'nao_aderente' => 'Não aderente', 'inconclusivo' => 'Inconclusivo'];
@endphp
<h1>Relatório documental por IA</h1>
<p><strong>{{ $report['opportunity']['object'] }}</strong></p>
<p class="meta">{{ $report['opportunity']['agency'] }} · PNCP {{ $report['opportunity']['pncp_id'] }}<br>
    Gerado em {{ \Carbon\Carbon::parse($report['generated_at'])->format('d/m/Y H:i') }} (America/Sao_Paulo) · Modelo {{ $report['model'] }}<br>
    Prazo cadastrado no PNCP: {{ $report['opportunity']['deadline_pncp'] ? \Carbon\Carbon::parse($report['opportunity']['deadline_pncp'])->timezone('America/Sao_Paulo')->format('d/m/Y H:i') : 'Não informado' }}</p>
<p class="notice">{{ $report['notice'] }}</p>
@if($stale)<p class="notice">Relatório desatualizado: documentos, dados ou data de análise mudaram. Gere uma análise atualizada.</p>@endif
<h2>Resumo e aderência ao escopo</h2>
<p>{{ $analysis['summary'] }}</p>
<p><span class="status">{{ $statuses[$analysis['scope']['status']] }}</span> - {{ $analysis['scope']['reason'] }}</p>
@include('pdf.analysis-citations', ['references' => $analysis['scope']['sources']])
@if($analysis['warnings'])
<h2>Pendências e limitações</h2>
<ul>@foreach($analysis['warnings'] as $warning)<li>{{ $warning }}</li>@endforeach</ul>
@endif
<h2>Prazos identificados nos documentos</h2>
@forelse($analysis['deadlines'] as $deadline)
    <div class="item {{ mb_strlen(json_encode($deadline, JSON_UNESCAPED_UNICODE)) < 2500 ? 'keep' : '' }}"><h3>{{ $deadline['label'] }} - {{ $deadline['value'] }}</h3><p>{{ $deadline['notes'] }}</p>
    @include('pdf.analysis-citations', ['references' => $deadline['sources']])</div>
@empty<p>Nenhum prazo documental identificado. Confira os PDFs originais e o prazo PNCP acima.</p>@endforelse
<h2>Classificação do acervo da empresa</h2>
@forelse($analysis['qualifications'] as $qualification)
    <div class="item {{ mb_strlen(json_encode($qualification, JSON_UNESCAPED_UNICODE)) < 2500 ? 'keep' : '' }}"><h3>{{ $qualification['name'] }}</h3>
    <p>{{ $categories[$qualification['category']] }} · Titular: {{ $qualification['holder'] ?: 'Não identificado' }} · Validade: {{ $qualification['valid_until'] ?: 'Não identificada' }}</p>
    <p>Especialidades: {{ implode(', ', $qualification['specialties']) ?: 'Não identificadas' }}<br>Quantitativos: {{ $qualification['quantities'] ?: 'Não identificados' }}</p>
    @include('pdf.analysis-citations', ['references' => $qualification['sources']])</div>
@empty<p>Nenhuma habilitação classificada. Não há comprovação automática de atendimento.</p>@endforelse
<h2>Exigências e confronto documental</h2>
@forelse($analysis['requirements'] as $requirement)
    <div class="item {{ mb_strlen(json_encode($requirement, JSON_UNESCAPED_UNICODE)) < 2500 ? 'keep' : '' }}"><h3>{{ $loop->iteration }}. {{ $requirement['description'] }}</h3>
    <p>{{ $categories[$requirement['category']] }} · {{ $requirement['mandatory'] ? 'Obrigatória conforme documentos' : 'Obrigatoriedade a conferir' }} · <span class="status">{{ $statuses[$requirement['status']] }}</span></p>
    <p>{{ $requirement['reason'] }}</p>
    @if($requirement['required_quantities'])<p>Quantitativos exigidos: {{ $requirement['required_quantities'] }}</p>@endif
    <strong>Origem no processo</strong>@include('pdf.analysis-citations', ['references' => $requirement['sources']])
    <strong>Evidências da empresa</strong>@include('pdf.analysis-citations', ['references' => $requirement['evidence']])
    @if($requirement['gaps'])<p><strong>Lacunas / ações</strong></p><ul>@foreach($requirement['gaps'] as $gap)<li>{{ $gap }}</li>@endforeach</ul>@endif</div>
@empty<p>Nenhuma exigência identificada. Resultado inconclusivo; revise integralmente o edital.</p>@endforelse
<h2>Arquivos considerados</h2>
<table><thead><tr><th style="width: 60%">Arquivo</th><th style="width: 16%">Conjunto</th><th style="width: 24%">Leitura</th></tr></thead><tbody>
@foreach($report['sources'] as $source)<tr><td>{{ $source['name'] }}</td><td>{{ $source['scope'] === 'company' ? 'Acervo' : 'Processo' }}</td><td>{{ ['text' => 'Texto em memória', 'visual' => 'Visual; revisão necessária', 'missing' => 'Ausente', 'invalid' => 'Não PDF'][$source['reading']] ?? $source['reading'] }}</td></tr>@endforeach
</tbody></table>
@if($report['usage'])
<h2>Consumo da execução</h2>
<p>{{ $report['usage']['input_tokens'] }} tokens de entrada ({{ $report['usage']['cached_input_tokens'] }} em cache), {{ $report['usage']['output_tokens'] }} de saída.
@if($report['usage']['estimated_cost_usd'] !== null) Custo estimado: US$ {{ number_format($report['usage']['estimated_cost_usd'], 4, ',', '.') }}. Estimativa pelas tarifas configuradas, sujeita à cobrança efetiva do provedor.@else Custo não calculado para este modelo.@endif</p>
@endif
</body>
</html>
