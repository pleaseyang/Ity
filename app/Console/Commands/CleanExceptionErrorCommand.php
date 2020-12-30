<?php

namespace App\Console\Commands;

use App\Models\ExceptionError;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;

class CleanExceptionErrorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exceptionerror:clean
                            {--days= : (optional) Records older than this number of days will be cleaned.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old records from the exception error.';

    /**
     * Execute the console command.
     *
     * @return void
     * @throws Exception
     */
    public function handle()
    {
        $this->comment('Cleaning exception error...');

        $maxAgeInDays = $this->option('days') ?? 365;

        $cutOffDate = Carbon::now()->subDays($maxAgeInDays)->format('Y-m-d H:i:s');

        $amountDeleted = ExceptionError::where('created_at', '<', $cutOffDate)
            ->delete();

        $this->info("Deleted {$amountDeleted} record(s) from the exception error.");

        $this->comment('All done!');
    }
}
