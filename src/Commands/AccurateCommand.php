<?php

namespace ChrisLorando\LaravelAccurate\Commands;

use Illuminate\Console\Command;

class AccurateCommand extends Command
{
    public $signature = 'accurate';

    public $description = 'Accurate Online command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}