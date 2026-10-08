<?php

namespace App\Integrations\Pncp;

use RuntimeException;

class PncpOpportunityMapper
{
    public function map(array $row): array
    {
        foreach (['cnpj', 'razaoSocial'] as $field) {
            if (! isset($row['orgaoEntidade'][$field]) || ! is_string($row['orgaoEntidade'][$field]) || trim($row['orgaoEntidade'][$field]) === '') {
                throw new RuntimeException("PNCP: órgão sem {$field} válido.");
            }
        }
        foreach (['numeroControlePNCP', 'orgaoEntidade', 'anoCompra', 'sequencialCompra', 'objetoCompra', 'modalidadeId'] as $key) {
            if (! isset($row[$key])) {
                throw new RuntimeException("PNCP: campo ausente {$key}");
            }
        }

        foreach (['numeroControlePNCP', 'objetoCompra'] as $field) {
            if (! is_string($row[$field]) || trim($row[$field]) === '') {
                throw new RuntimeException("PNCP: campo inválido {$field}");
            }
        }

        return [
            'pncp_id' => $row['numeroControlePNCP'], 'cnpj' => $row['orgaoEntidade']['cnpj'],
            'year' => $row['anoCompra'], 'sequence' => $row['sequencialCompra'], 'object' => $row['objetoCompra'],
            'agency' => $row['orgaoEntidade']['razaoSocial'], 'state' => $row['unidadeOrgao']['ufSigla'] ?? null,
            'city' => $row['unidadeOrgao']['municipioNome'] ?? null, 'modality_id' => $row['modalidadeId'],
            'modality' => $row['modalidadeNome'] ?? 'Não informada', 'estimated_value' => $row['valorTotalEstimado'] ?? null,
            'published_at' => $row['dataPublicacaoPncp'] ?? null, 'deadline' => $row['dataEncerramentoProposta'] ?? null, 'source' => $row,
        ];
    }
}
