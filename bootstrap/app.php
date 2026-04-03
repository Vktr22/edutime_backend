<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        
        //aliasok, class utvonalak - itt adok nevet, h ha atirom az utvonalat akk ne kelljen mindenhol atirni ahol hivatkra, csak itt
        $middleware->alias([
            'student' => \App\Http\Middleware\StudentMW::class,
            'teacher' => \App\Http\Middleware\TeacherMW::class,
        ]);

        //ez a sor majd akk fog kelleni amint lesz bongeszos frontend (nem csak bearer apis token mint a thunder client)
        //$middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
