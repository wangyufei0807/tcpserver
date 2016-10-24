<?php
/**
 * Created by PhpStorm.
 * User: 王宇飞
 * Date: 2016/10/21
 * Time: 11:59
 */

namespace App\Libraries\Kenyon\server;

class Swoole {
    private $serv;
    private $config;
    private $handler;
    private $host = "0.0.0.0";
    private $port = "9999";
    private $work_mode = "3";
    private $process_name = "server";
    private $pid_path = "/tmp";
    private $log_file;
    private $master_pid_file;
    private $manager_pid_file;

    public function __construct($config,$handler){
        //设置回调类
        $this->handler = $handler;

        //资源初始化
        $this->_init($config);
    }

    /**
     * 资源初始化
     * @param $config
     */
    public function _init($config){

        //设置启动参数
        $this->config = $config;
        $this->host = $config["main"]["host"] ? $config["main"]["host"]:$this->host;
        $this->port = $config["main"]["port"] ? $config["main"]["port"]:$this->port;
        $this->work_mode = $config["main"]["work_mode"] ? $config["main"]["work_mode"]:$this->work_mode;
        $this->process_name = $config["main"]["process_name"] ? $config["main"]["process_name"]:$this->process_name;

        //设置进程id文件
        $this->master_pid_file = $this->pid_path . '/' . $this->process_name . '.master.pid';
        $this->manager_pid_file = $this->pid_path . '/' . $this->process_name . '.manager.pid';

        //设置日志文件
        $this->log_file = $config["setting"]["log_file"];
    }

    /**
     * 启动服务
     * @return bool
     */
    public function start(){
        if ($this->checkServerIsRunning()) {
            $this->log("[warning] " . $this->process_name . ": master process " . $this->master_pid_file . " has already exists!");
            $this->log($this->process_name . ": start\033[31;40m [OK] \033[0m");
            return false;
        }else{
            //构建Server对象
            $this->serv = new \swoole_server($this->host, $this->port, $this->work_mode, SWOOLE_SOCK_TCP);

            //设置配置选项
            $config = $this->config;
            $this->serv->set($config["setting"]);

            //设置进程名
            swoole_set_process_name($this->process_name);

            //注册事件回调函数
            $this->serv->on("Start",array($this,"onMasterStart"));
            $this->serv->on("Connect",array($this->handler,"onConnect"));
            $this->serv->on("Receive",array($this->handler,"onReceive"));
            $this->serv->on("Close",array($this->handler,"onClose"));

            $this->log($this->process_name . ": start\033[32;40m [OK] \033[0m");
            $this->serv->start();
        }
    }

    /**
     * 关闭服务
     * @return bool
     */
    public function stop(){
        $masterId = $this->getMasterPid();
        if (empty($masterId)) {
            $this->log("[warning] " . $this->process_name . ": can not find master pid file");
            $this->log($this->process_name . ": stop\033[31;40m [FAIL] \033[0m");
            return false;
        }

        //kill -15 主进程PID 实现关闭服务器
        if(posix_kill($masterId, 15)){
            if(unlink($this->master_pid_file) && unlink($this->manager_pid_file)){
                usleep(50000);
                $this->log($this->process_name . ": stop\033[32;40m [OK] \033[0m");
                return true;
            }else{
                echo "delete pid_file error!";
            }
        }else{
            $this->log("[warning] " . $this->process_name . ": send signal to master failed");
            $this->log($this->process_name . ": stop\033[31;40m [FAIL] \033[0m");
            return false;
        }
    }

    /**
     * 日志
     * @param $msg
     */
    public function log($msg)
    {
        if ($this->log_file && file_exists($this->log_file))
        {
            error_log($msg . PHP_EOL, 3, $this->log_file);
        }
        echo $msg . PHP_EOL;
    }

    /**
     * 将pid写入文件
     */
    public function onMasterStart(){
        file_put_contents($this->master_pid_file, $this->serv->master_pid);
        file_put_contents($this->manager_pid_file, $this->serv->manager_pid);
    }

    /**
     * 获取当前服务器主进程的PID
     * @return bool|string
     */
    protected function getMasterPid() {
        $pid = false;
        if (file_exists($this->master_pid_file)) {
            $pid = file_get_contents($this->master_pid_file);
        }
        return $pid;
    }

    /**
     * 获取当前服务器管理进程的PID
     * @return bool|string
     */
    protected function getManagerPid() {
        $pid = false;
        if (file_exists($this->manager_pid_file)) {
            $pid = file_get_contents($this->manager_pid_file);
        }
        return $pid;
    }

    /**
     * 检查server 是否在运行
     * @return bool
     */
    protected function checkServerIsRunning() {
        $pid = $this->getMasterPid();
        return $pid && $this->checkPidIsRunning($pid);
    }

    /**
     * 检查pid是否在运行
     * @param $pid
     * @return bool
     */
    protected function checkPidIsRunning($pid) {
        return posix_kill($pid, 0);
    }
}