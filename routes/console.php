<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment('Have a great day!');
})->purpose('Display an inspiring quote');