<?php
$data = include_once __DIR__ . "/config.php";
include_once __DIR__ . "/lib/Moguding.php";

$Moguding = new Moguding();

sleep(rand(2, 6));//随机延迟 2~6s

//遍历配置文件进行签到
foreach ($data as ['phone' => $phone, 'password' => $password, 'data' => $locationData]) {
    try {
        //执行签到
        $Moguding->setData($phone, $password, $locationData)//设置数据
        ->setSignType('END')//设置签到类型
        ->run();
    } catch (Exception $e) {
        echo "啊哦~【{$phone}】签到失败了，原因竟然是：{$e->getMessage()}" . PHP_EOL;
    }

    usleep(rand(100, 600));//随机延迟 100~600ms
}