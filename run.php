<?php
$data = include_once __DIR__ . "/config.php";
include_once __DIR__ . "/lib/Moguding.php";

$Moguding = new Moguding();

//遍历配置文件进行签到
foreach ($data as ['phone' => $phone, 'password' => $password, 'data' => $locationData]) {
    $Moguding
        ->setData($phone, $password, $locationData)//设置数据
        ->run();//执行签到
}