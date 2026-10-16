@forelse($references as $reference)
<div class="citation"><strong>{{ $reference['source_name'] }} · página {{ $reference['page'] }}</strong><br>
    “{{ $reference['quote'] }}”
    @if($reference['verification'] !== 'text_match')<br><em>Referência visual; conferir no PDF original.</em>@endif
</div>
@empty<p class="meta">Sem referência documental.</p>@endforelse
