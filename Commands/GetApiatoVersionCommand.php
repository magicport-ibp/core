<?php

namespace Apiato\Core\Commands;

use Apiato\Core\Abstracts\Commands\ConsoleCommand;
use Apiato\Core\Foundation\Apiato;

class GetApiatoVersionCommand extends ConsoleCommand
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = "magicport";

    /**
     * The console command description.
     */
    protected $description = "Display the current MagicPort core version.";

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info(Apiato::VERSION);
    }
}
