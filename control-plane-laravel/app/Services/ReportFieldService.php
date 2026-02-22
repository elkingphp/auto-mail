<?php

namespace App\Services;

use App\Models\ReportField;
use Illuminate\Database\Eloquent\Collection;

class ReportFieldService
{
    public function getAll(?string $reportId = null): Collection
    {
        if ($reportId) {
            return ReportField::where('report_id', $reportId)->orderBy('order_position')->get();
        }
        return ReportField::all();
    }

    public function create(array $data): ReportField
    {
        return ReportField::create($data);
    }

    public function update(ReportField $reportField, array $data): ReportField
    {
        $reportField->update($data);
        return $reportField;
    }

    public function delete(ReportField $reportField): void
    {
        $reportField->delete();
    }
}
