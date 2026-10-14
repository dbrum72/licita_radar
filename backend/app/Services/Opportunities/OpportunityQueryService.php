<?php

namespace App\Services\Opportunities;

use App\Models\Opportunity;
use App\Services\CompanyDocuments\CompanyScopeService;

class OpportunityQueryService
{
    public function __construct(
        private CompanyScopeService $company,
        private OpportunityLifecycleService $lifecycle,
    ) {}

    public function paginate(array $filters): array
    {
        $this->lifecycle->pruneExpiredDiscarded();
        $query = Opportunity::where('deadline', '>=', Opportunity::openDeadlineBoundary())->withExists('dismissal as discarded');
        if ($filters['discarded'] ?? false) {
            $query->whereHas('dismissal');
        } else {
            $query->whereIn('id', $this->company->compatibleIds());
            $query->where('bookmarked', (bool) ($filters['bookmarked'] ?? false));
        }
        foreach (['keyword' => 'object', 'agency' => 'agency'] as $field => $column) {
            if (isset($filters[$field]) && $filters[$field] !== '') {
                $query->where($column, 'like', '%'.$filters[$field].'%');
            }
        }
        foreach (['state' => 'state', 'modality' => 'modality_id', 'scope' => 'scope_status'] as $field => $column) {
            if (isset($filters[$field]) && $filters[$field] !== '') {
                $query->where($column, $filters[$field]);
            }
        }
        foreach (['min_value' => '>=', 'max_value' => '<='] as $field => $operator) {
            if (isset($filters[$field]) && $filters[$field] !== '') {
                $query->where('estimated_value', $operator, $filters[$field]);
            }
        }
        if (! empty($filters['deadline'])) {
            $query->whereDate('deadline', '<=', $filters['deadline']);
        }
        $results = $query->orderBy('deadline')->orderBy('id')->paginate(20, ['*'], 'page', $filters['page'] ?? 1)->withQueryString();
        $results->getCollection()->each(fn ($item) => $item->setAttribute('company_match', $this->company->assess($item->object, $item->deadline)));

        return [...$results->toArray(), 'company_notice' => $this->company->notice()];
    }
}
