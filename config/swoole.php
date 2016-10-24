<?php

return array(
    "main" => array(
        "process_name" => "yun_test",
        "host" => "0.0.0.0",
        "port" => "9966",
        "work_mode" => 3
    ),
    "setting" => array(
        "log_file" => "/data/wwwlogs/swoole.log",
        "worker_num" => 2,
        "max_request" => 1000,
        "max_connection" => 1024,
        "heartbeat_check_interval" => 30,
        "heartbeat_idle_time" => 90
    )
);