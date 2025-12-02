<?php

use Illuminate\Support\Carbon;
use App\Models\NotificationPanel;
use App\Jobs\ProcessNotificationJob;
use Illuminate\Foundation\Application;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->call(function () {
            $now = \Illuminate\Support\Carbon::now();

           echo('⏰ Scheduler triggered at: ' . $now->toDateTimeString());

            $pending = NotificationPanel::where('is_scheduled', '0')
                // ->where('status', 'queued')
                // ->where('scheduled_at', '<=', $now)
                ->get();



           echo('Found ' . $pending->count() . ' pending notifications.');

            foreach ($pending as $notification) {
                \App\Jobs\ProcessNotificationJob::dispatchInChunks($notification);
                $notification->update(['status' => 'processing']);
               echo('Dispatched job for notification ID: ' . $notification->id);
            }
        })->everyMinute();
    })

    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
