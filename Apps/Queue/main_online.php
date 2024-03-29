<?php
/**
+------------------------------------------
| 多线程任务处理（定时任务或队列）
+------------------------------------------
| Author: lazycat <673090083@qq.com>
+------------------------------------------
| 2016-10-25
+------------------------------------------
 */

@ini_set('memory_limit','2048M');   //最大占用内存1024M，启用进程越多，所需内存就越大
//@ini_set('display_errors','off');   //屏弊错误信息

date_default_timezone_set('Asia/Shanghai');
define('APP_DEBUG', true);
define('DS', DIRECTORY_SEPARATOR);
define('ROOT_PATH', realpath(dirname(__FILE__)) . DS);
define('COMMON_PATH',ROOT_PATH . 'Common' . DS);
define('CONFIG_PATH',COMMON_PATH . 'Config' . DS);
define('FUNCTION_PATH',COMMON_PATH . 'Common' . DS);
define('VENDOR_PATH',ROOT_PATH . 'Vendor' . DS);
define('TUBE_PREFIX','trj_');

include_once FUNCTION_PATH . 'function.php';
$config = array(
    //Beanstalkd参数
    'BS_HOST'       => '10.0.0.90',
    'BS_PORT'       => 11300,
    'BS_TIMEOUT'    => 1,
    'BS_LOGGER'     => null,

    //数据库配置
    'DB_TYPE'       => 'mysql',
    'DB_HOST'       => '10.0.0.100',
    'DB_USER'       => 'tangmalluser',
    'DB_PWD'        => 'Ma#-26Tang',
    'DB_PORT'       => '3306',
    'DB_NAME'       => 'tangmall',
    'DB_CHARSET'    => 'utf8',
    'DB_PREFIX'     => 'ylh_',
    'DB_PCONNECT'   => 0,

    'PROCESS_PREV'  => 'lazycat_',   //进程名前缀
    'APIURL'        => 'https://rest.trj.cc', //API接口地址前缀

    'LISTEN_PID_FILE'   => ROOT_PATH . '/listen_tube.pid', //监听队列进程PID
    'WORKER_PID_FILE'   => ROOT_PATH . '/worker.pid', //监听队列进程PID
    //'JOB_PID_FILE'      => ROOT_PATH . '/job.pid', //监听队列进程PID

    'XS_PROJECT_PATH'   => ROOT_PATH . 'Config/Xunsearch', //迅搜项目文件.ini存放目录
);


foreach($config as $key => $val){
    define($key,$val);
}


class Main
{
    static private $options = "hdrmp:s:l:c:";
    static private $longopts = array("help", "daemon","reload", "monitor", "pid:", "log:", "config:","worker:","tasktype:","checktime:" );
    static private $help = <<<EOF

  帮助信息:
  Usage: /path/to/php main.php [options] -- [args...]

  -h [--help]        显示帮助信息
  -p [--pid]         指定pid文件位置(默认pid文件保存在当前目录)
  -s start           启动进程
  -s stop            停止进程
  -s restart         重启进程
  -l [--log]         log文件夹的位置
  -c [--config]      config文件的位置，（停用，默认为mysql）
  -d [--daemon]      是否后台运行
  -r [--reload]      重新载入配置文件
  -m [--monitor]     监控进程是否在运行,如果在运行则不管,未运行则启动进程
  --worker           开启worker
  --tasktype         task任务获取类型,[file|mysql] 默认是mysql
  --checktime        默认精确对时(如果精确对时,程序则会延时到分钟开始0秒启动) 值为false则不精确对时

EOF;

    /**
     * 运行入口
     */
    static public function run()
    {
        $opt = getopt(self::$options, self::$longopts);
        self::spl_autoload_register();
        self::params_h($opt);
        self::params_d($opt);
        self::params_p($opt);
        self::params_l($opt);
        //self::params_c($opt);
        self::params_r($opt);
        self::params_worker($opt);
        self::params_tasktype($opt);
        self::params_checktime($opt);
        $opt = self::params_m($opt);
        self::params_s($opt);
    }

    /**
     * 注册类库载入路径
     */
    static public function spl_autoload_register()
    {
        spl_autoload_register(function ($name) {
            $file_path = ROOT_PATH . "Vendor" . DS . $name . ".class.php";
            if(file_exists($file_path)) include $file_path;
        });
    }

    /**
     * 解析帮助参数
     * @param $opt
     */
    static public function params_h($opt)
    {
        if (empty($opt) || isset($opt["h"]) || isset($opt["help"])) {
            die(self::$help);
        }
    }

    /**
     * 解析运行模式参数
     * @param $opt
     */
    static public function params_d($opt)
    {
        if (isset($opt["d"]) || isset($opt["daemon"])) {
            Crontab::$daemon = true;
        }
    }

    /**
     * 解析精确对时参数
     * @param $opt
     */
    static public function params_checktime($opt)
    {
        if (isset($opt["checktime"]) && $opt["checktime"] === "false") {
            Crontab::$checktime = false;
        }
    }

    /**
     * 重新载入配置文件
     * @param $opt
     */
    static public function params_r($opt)
    {
        if (isset($opt["r"]) || isset($opt["reload"])) {
            $pid = @file_get_contents(Crontab::$pid_file);
            if ($pid) {
                if (swoole_process::kill($pid, 0)) {
                    swoole_process::kill($pid, SIGUSR1);
                    Main::log_write("对 {$pid} 发送了从新载入配置文件的信号");
                    exit;
                }
            }
            Main::log_write("进程" . $pid . "不存在");
        }
    }

    /**
     * 监控进程是否在运行
     * @param $opt
     * @return array
     */
    static public function params_m($opt)
    {
        if (isset($opt["m"]) || isset($opt["monitor"])) {
            $pid = @file_get_contents(Crontab::$pid_file);
            if ($pid && swoole_process::kill($pid, 0)) {
                exit;
            } else {
                $opt = array("s" => "restart");
            }
        }
        return $opt;
    }

    /**
     * 解析pid参数
     * @param $opt
     */
    static public function params_p($opt)
    {
        //记录pid文件位置
        if (isset($opt["p"]) && $opt["p"]) {
            Crontab::$pid_file = $opt["p"] . "/pid";
        }
        //记录pid文件位置
        if (isset($opt["pid"]) && $opt["pid"]) {
            Crontab::$pid_file = $opt["pid"] . "/pid";
        }
        if (empty(Crontab::$pid_file)) {
            Crontab::$pid_file = ROOT_PATH . "/pid";
        }
    }

    /**
     * 解析日志路径参数
     * @param $opt
     */
    static public function params_l($opt)
    {
        if (isset($opt["l"]) && $opt["l"]) {
            Crontab::$log_path = $opt["l"];
        }
        if (isset($opt["log"]) && $opt["log"]) {
            Crontab::$log_path = $opt["log"];
        }
        if (empty(Crontab::$log_path)) {
            Crontab::$log_path = ROOT_PATH . "/Logs/";
        }
    }

    /**
     * 解析配置文件位置参数
     * @param $opt
     */
    static public function params_c($opt)
    {
        if (isset($opt["c"]) && $opt["c"]) {
            Crontab::$taskParams = $opt["c"];
        }
        if (isset($opt["config"]) && $opt["config"]) {
            Crontab::$taskParams = $opt["config"];
        }
        if (empty(Crontab::$taskParams)) {
            Crontab::$taskParams = ROOT_PATH . "Config/crontab.php";
        }
    }

    /**
     * 解析启动模式参数
     * @param $opt
     */
    static public function params_s($opt)
    {
        //判断传入了s参数但是值，则提示错误
        if ((isset($opt["s"]) && !$opt["s"]) || (isset($opt["s"]) && !in_array($opt["s"], array("start", "stop", "restart")))) {
            Main::log_write("Please run: path/to/php main.php -s [start|stop|restart]");
        }

        if (isset($opt["s"]) && in_array($opt["s"], array("start", "stop", "restart"))) {
            switch ($opt["s"]) {
                case "start":
                    Crontab::start();
                    break;
                case "stop":
                    Crontab::stop();
                    break;
                case "restart":
                    Crontab::restart();
                    break;
            }
        }
    }

    static public function params_worker($opt){
        if (isset($opt["worker"])) {
            Crontab::$worker = true;
        }
    }

    static public function params_tasktype($opt){
        if (isset($opt["tasktype"])) {
            Crontab::$taskType = $opt["tasktype"];
        }
    }

    /**
     * 记录日志
     * @param $message
     */
    static public function log_write($message)
    {
        $now = date("H:i:s");
        if (Crontab::$daemon) {
            $destination = Crontab::$log_path . "log_" . date("Y-m-d") . ".log";
            error_log("{$now} : {$message}\r\n", 3, $destination, '');
        }
        echo "{$now} : {$message}\r\n";
    }
}

//运行
Main::run();
