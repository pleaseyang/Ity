<?php

namespace App\Console\Commands;

use App\Workerman\Events;
use GatewayWorker\BusinessWorker;
use GatewayWorker\Gateway;
use GatewayWorker\Register;
use Illuminate\Console\Command;
use Workerman\Worker;

class WorkerMan extends Command
{
    /**
     * The name and signature of the console command.
     * 命令名称及签名
     *
     * @var string
     */
    protected $signature = 'workerman
                            {action : action}
                            {--start=all : start}
                            {--d : daemon mode}';

    /**
     * The console command description.
     * 命令描述
     *
     * @var string
     */
    protected $description = 'Start a Workerman server.';

    /**
     * Create a new command instance.
     * 创建命令
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     * 执行命令
     *
     */
    public function handle()
    {
        global $argv;
        $action = $this->argument('action');

        // 针对 Windows 一次执行，无法注册多个协议的特殊处理
        if ($action === 'single') {
            $start = $this->option('start');
            if ($start === 'register') {
                $this->startRegister();
            } elseif ($start === 'gateway') {
                $this->startGateWay();
            } elseif ($start === 'worker') {
                $this->startBusinessWorker();
            }
            Worker::runAll();
            return;
        }


        $options = $this->options();
        $argv[1] = $action;
        $argv[2] = $options['d'] ? '-d' : '';
        $this->start();
    }

    private function start()
    {
        $this->startGateWay();
        $this->startBusinessWorker();
        $this->startRegister();
        Worker::runAll();
    }

    /**
     * gateway进程启动脚本，包括端口号等设置
     *
     */
    private function startGateWay()
    {
        // 指定websocket协议
        $gateway = new Gateway(config('app.websocket_url'));
        $gateway->name = 'Gateway ' . config('app.name');
        $gateway->count = 1; // CPU核数
        $gateway->lanIp = '127.0.0.1';
        $gateway->startPort = 2300;
        $gateway->pingInterval = 30; // 心跳检测时间间隔 单位：秒。如果设置为0代表不做任何心跳检测
        $gateway->pingNotResponseLimit = 0; // 客户端在pingInterval秒内有pingNotResponseLimit次未回复就断开连接
        $gateway->pingData = '{"type":"heart"}'; // 发给客户端的心跳数据
        $gateway->registerAddress = '127.0.0.1:1236';
    }

    /**
     * businessWorker进程启动脚本
     *
     */
    private function startBusinessWorker()
    {
        $worker = new BusinessWorker();
        $worker->name = 'BusinessWorker ' . config('app.name');
        $worker->count = 3; // CPU核数 1-3倍
        $worker->registerAddress = '127.0.0.1:1236';
        $worker->eventHandler = Events::class;
    }

    /**
     * 注册服务启动脚本
     *
     */
    private function startRegister()
    {
        new Register('text://0.0.0.0:1236');
    }
}
