<?php

return [
    'pncp_url' => 'https://pncp.gov.br/api/consulta/v1',
    'modalities' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13],
    'proposal_days' => 365,
    'request_delay_ms' => env('PNCP_REQUEST_DELAY_MS', 2000),
];
