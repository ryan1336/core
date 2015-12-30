<?php

namespace App\Modules\Statistics\Console\Commands;

use App\Modules\Statistics\Jobs\StatisticsDownloadAndParse as StatisticsDownloadAndParseJob;
use DB;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use App\Models\Mship\Account;
use Illuminate\Foundation\Bus\DispatchesJobs;

class StatisticsDownloadAndParse extends \App\Console\Commands\aCommand
{
    use DispatchesJobs;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'statistics:download';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download and parse the VATSIM data feed file.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Executes all necessary console commands.
     *
     * @return mixed
     */
    public function fire()
    {
        $downloadJob = (new StatisticsDownloadAndParseJob())->onQueue("low");
        $this->dispatch($downloadJob);
    }
}