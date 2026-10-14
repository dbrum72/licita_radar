<?php

namespace App\Integrations\Pncp\Contracts;

use Generator;
use Illuminate\Http\Client\Response;

interface PncpClient
{
    public function openProposals(int $modality): Generator;

    public function request(string $url, array $query = [], array $options = []): Response;
}
