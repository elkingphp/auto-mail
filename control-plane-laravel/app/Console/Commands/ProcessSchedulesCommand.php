<?php

namespace App\Console\Commands;

use App\Models\Schedule;
use App\Models\Execution;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ProcessSchedulesCommand extends Command
{
    protected $signature = 'app:process-schedules';
    protected $description = 'Scan schedules and create due executions';

    public function handle()
    {
        $schedules = Schedule::where('is_active', true)->get();
        $now = Carbon::now();

        foreach ($schedules as $schedule) {
            if ($this->isDue($schedule, $now)) {
                $this->createExecution($schedule);
            }
        }
    }

    private function isDue(Schedule $schedule, Carbon $now): bool
    {
        // 1. Check constraints
        if ($schedule->start_date && $now->toDateString() < $schedule->start_date) {
            return false;
        }

        if ($schedule->start_hour && $now->format('H:i:s') < $schedule->start_hour) {
             return false;
        }

        $opts = $schedule->frequency_options;
        if (!$opts) return false;

        // Simple check: Does it match the current hour and minute?
        // To avoid multiple triggers in the same minute, we should check last execution
        $lastExecutionInMinute = Execution::where('schedule_id', $schedule->id)
            ->where('created_at', '>=', $now->copy()->startOfMinute())
            ->exists();

        if ($lastExecutionInMinute) return false;

        $targetHour = isset($opts['hour']) ? (int)$opts['hour'] : 0;
        $targetMinute = isset($opts['minute']) ? (int)$opts['minute'] : 0;
        $targetDayOfMonth = isset($opts['day_of_month']) ? (int)$opts['day_of_month'] : 1;
        $targetDayOfWeek = isset($opts['day_of_week']) ? (int)$opts['day_of_week'] : 1;

        if ($schedule->frequency === 'Hourly') {
            return $now->minute === $targetMinute;
        }

        if ($schedule->frequency === 'Daily') {
            return $now->hour === $targetHour && $now->minute === $targetMinute;
        }

        if ($schedule->frequency === 'Weekly') {
            return $now->dayOfWeek === $targetDayOfWeek && $now->hour === $targetHour && $now->minute === $targetMinute;
        }

        if ($schedule->frequency === 'Monthly') {
            return $now->day === $targetDayOfMonth && $now->hour === $targetHour && $now->minute === $targetMinute;
        }

        if ($schedule->frequency === 'Quarterly') {
            // Jan(1), Apr(4), Jul(7), Oct(10)
            return ($now->month % 3 === 1) && $now->day === $targetDayOfMonth && $now->hour === $targetHour && $now->minute === $targetMinute;
        }

        if ($schedule->frequency === 'Semiannually') {
            // Jan(1), Jul(7)
            return ($now->month % 6 === 1) && $now->day === $targetDayOfMonth && $now->hour === $targetHour && $now->minute === $targetMinute;
        }

        if ($schedule->frequency === 'Yearly') {
            return $now->month === 1 && $now->day === $targetDayOfMonth && $now->hour === $targetHour && $now->minute === $targetMinute;
        }

        if ($schedule->frequency === 'CustomHours') {
            $interval = isset($opts['interval_hours']) ? (int)$opts['interval_hours'] : 1;
            $lastExec = Execution::where('schedule_id', $schedule->id)->latest()->first();
            if (!$lastExec) return true; // Never run, run now
            
            return $lastExec->created_at->diffInHours($now) >= $interval;
        }

        return false;
    }

    private function createExecution(Schedule $schedule)
    {
        $execution = Execution::create([
            'report_id' => $schedule->report_id,
            'schedule_id' => $schedule->id,
            'status' => 'pending',
            'user_id' => null, // System triggered
        ]);

        $this->info("Created execution for schedule: {$schedule->id}");
        
        // Note: The Go engine or a separate job will pick this up.
        // If the Go engine polls for 'pending' executions, we are good.
    }
}
