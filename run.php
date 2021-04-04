<?php
$data = include_once __DIR__ . "/config.php";
include_once __DIR__ . "/lib/Moguding.php";

$Moguding = new Moguding();

sleep(rand(3, 6));//随机延迟 2~6s

//遍历配置文件进行签到
foreach ($data as ['phone' => $phone, 'password' => $password, 'data' => $locationData]) {
    try {
        //执行签到
        $Moguding->setData($phone, $password, $locationData)//设置数据
        ->run();
    } catch (Exception $e) {
        echo "啊哦~【{$phone}】签到失败了，原因竟然是：{$e->getMessage()}" . PHP_EOL;
    }

    usleep(rand(120, 700));//随机延迟 120~700ms
}

//周报提交，使用方法，可以提交历史未提交的周报
//$Moguding->setData('手机号', '密码', []);
//$Moguding->sendWeekNote(
//    '2021-03-22 00:00:00',//某周开始时间
//    '2021-03-28 23:59:59',//某周结束时间
//    '第4周',//格式必须是 第x周   x替换为实际周次的数字
//    '第4周周报',
//    '了解了、掌握了、你猜、我凑字数、对超大数据库（百万级别）查询语句的优化，了解了掌握了索引的重要性.............'
//);