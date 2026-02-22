<?php

namespace App\Services;

use App\Models\Schedule;
use Illuminate\Database\Eloquent\Collection;

class ScheduleService
{
    public function getAll(): Collection
    {
        return Schedule::all();
    }

    public function create(array $data): Schedule
    {
        $schedule = Schedule::create($data);
        
        if (isset($data['ftp_server_ids'])) {
            $schedule->ftpServers()->sync($data['ftp_server_ids']);
        }

        return $schedule;
    }

    public function update(Schedule $schedule, array $data): Schedule
    {
        $schedule->update($data);

        if (isset($data['ftp_server_ids'])) {
            $schedule->ftpServers()->sync($data['ftp_server_ids']);
        }
        
        return $schedule;
    }

    public function delete(Schedule $schedule): void
    {
        $schedule->delete();
    }
}
