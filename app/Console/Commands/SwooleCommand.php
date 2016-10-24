<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Libraries\Kenyon\server\Swoole;
use App\Handlers\SwooleHandler;
use Illuminate\Support\Facades\Config;


class SwooleCommand extends Command
{
    protected $serv;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swoole {instruct}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'tcpserver start and stop ';

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
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $instruct = $this->argument('instruct');
        $handler = new SwooleHandler();
        $swoole = new Swoole(Config::get('swoole'), $handler);
        switch ($instruct) {
            case 'start':
                $swoole->start();
                break;
            case 'stop':
                $swoole->stop();
                break;
            default:
                echo 'Usage:php swoole.php start | stop | reload | restart | status | help' . PHP_EOL;
                break;
        }
    }
}