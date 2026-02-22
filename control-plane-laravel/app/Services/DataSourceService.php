<?php

namespace App\Services;

use App\Models\DataSource;
use Illuminate\Database\Eloquent\Collection;

class DataSourceService
{
    public function getAll(): Collection
    {
        return DataSource::all();
    }

    public function create(array $data): DataSource
    {
        return DataSource::create($data);
    }

    public function update(DataSource $dataSource, array $data): DataSource
    {
        $dataSource->update($data);
        return $dataSource;
    }

    public function delete(DataSource $dataSource): void
    {
        $dataSource->delete();
    }
}
