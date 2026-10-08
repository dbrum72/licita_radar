<?php

namespace App\Mail;

use App\Models\SearchProfile;
use Carbon\Carbon;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Collection;

class OpportunityDigest extends Mailable
{
    public array $opportunities;

    public function __construct(public SearchProfile $profile, Collection $items, public string $frontendUrl)
    {
        $this->opportunities = $items->map(fn ($item) => [
            'object' => $item->object,
            'agency' => $item->agency,
            'location' => implode(' · ', array_filter([$item->city, $item->state])),
            'modality' => $item->modality,
            'deadline' => Carbon::parse($item->deadline)->timezone(config('app.timezone'))->format('d/m/Y H:i'),
            'value' => $item->estimated_value !== null ? 'R$ '.number_format((float) $item->estimated_value, 2, ',', '.') : 'Não informado',
            'url' => 'https://pncp.gov.br/app/editais/'.rawurlencode($item->cnpj).'/'.(int) $item->year.'/'.(int) $item->sequence,
        ])->values()->all();
    }

    public function build(): self
    {
        $count = count($this->opportunities);

        return $this->subject('LicitaRadar: '.$count.' '.($count === 1 ? 'nova oportunidade' : 'novas oportunidades').' — '.$this->profile->name)
            ->view('emails.opportunity-digest')
            ->text('emails.opportunity-digest-text');
    }
}
