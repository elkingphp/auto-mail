<?php

namespace App\Services;

use App\Models\ReportFilter;
use Illuminate\Database\Eloquent\Collection;

class ReportFilterService
{
    public function getAll(): Collection
    {
        return ReportFilter::all();
    }

    public function create(array $data): ReportFilter
    {
        return ReportFilter::create($data);
    }

    public function update(ReportFilter $reportFilter, array $data): ReportFilter
    {
        $reportFilter->update($data);
        return $reportFilter;
    }

    public function delete(ReportFilter $reportFilter): void
    {
        $reportFilter->delete();
    }
}
