<?php

class Moguding
{
    private $phone = '';//手机号
    private $password = '';//密码

    private $token = '';//登录后可以获取到的
    private $planId = '';//签到计划ID

    private $signType = '';//签到类型，默认上班签到

    /**
     * @var array
     */
    private $location = [];

    public function setData($phone, $password, array $location): Moguding
    {
        $this->phone = $phone;
        $this->password = $password;
        $this->location = $location;

        return $this;
    }

    /**
     * 设置签到类型，不设置默认就是上班签到
     * @param string $signType START：上班  END：下班签到
     * @return $this
     */
    public function setSignType(string $signType): Moguding
    {
        $this->signType = strtoupper($signType);
        return $this;
    }

    /**
     * 运行脚本
     * @throws Exception
     */
    public function run()
    {
        $this->writeLog("开始运行");
        $this->token = $this->getToken();
        usleep(rand(200, 800));
        $this->planId = $this->getPlanId();
        usleep(rand(200, 800));
        $this->doSign();
    }

    /**
     * 登录账号获取 Token
     * @return string
     * @throws Exception
     */
    public function getToken(): string
    {
        $url = 'https://api.moguding.net:9000/session/user/v1/login';
        $res = self::postRaw($url, [
            'Content-Type: application/json; charset=UTF-8'
        ], [
            'phone' => $this->phone,
            'password' => $this->password,
            'uuid' => '',
            'loginType' => 'android'
        ]);

        if (empty($res)) {
            $this->writeLog("{$this->phone} >> 登陆失败，请稍后再试");
            throw new Exception('登陆失败，请稍后再试');
        }

        if ($res['code'] !== 200) {
            $this->writeLog("{$this->phone} >>> 登陆失败 --- {$res['msg']}");
            return $res['msg'] ?? '登陆失败';
        }

        if (empty($res['data']['token'])) {
            $this->writeLog("{$this->phone} >>> data.token 字段不存在，请重试！");
            throw new Exception('data.token 字段不存在，请重试！');
        }

        $this->writeLog("{$this->phone} >>> 登陆成功，获取 Token 成功");
        return $res['data']['token'];
    }

    /**
     * 获取计划 ID
     * @return mixed|string
     * @throws Exception
     */
    public function getPlanId(): string
    {
        $url = 'https://api.moguding.net:9000/practice/plan/v1/getPlanByStu';
        $res = self::postRaw($url, [
            'Authorization: ' . $this->token,
            'Content-Type: application/json; charset=UTF-8',
            'roleKey: student'
        ], []);

        if (empty($res)) {
            $this->writeLog("{$this->phone} >>> 获取 PlanID 失败，请稍后再试");
            throw new Exception('获取 PlanID 失败，请稍后再试');
        }

        if ($res['code'] !== 200) {
            $this->writeLog("{$this->phone} >>> 获取 PlanID 失败 --- {$res['msg']}");
            return $res['msg'] ?? '获取 PlanID 失败';
        }

        if (empty($res['data'][0]['planId'])) {
            $this->writeLog("{$this->phone} >>> data[0].planId 字段不存在，请重试！");
            throw new Exception('data[0].planId 字段不存在，请重试！');
        }

        $this->writeLog("{$this->phone} >>> 获取 planId 成功！");
        return $res['data'][0]['planId'];
    }

    /**
     * 执行签到
     * @param string $device
     * @return string
     * @throws Exception
     */
    public function doSign(string $device = 'android'): string
    {
        if ($this->autoOutSignType() === 'STOP') die('终止执行' . PHP_EOL);
        $this->location['device'] = strtolower($device);//设备
        $this->location['planId'] = $this->planId;
        $this->location['type'] = empty($this->signType) ? $this->autoOutSignType() : $this->signType;//签到类型
        $this->location['state'] = 'NORMAL';//状态
        $this->location['attendanceType'] = '';//未知属性

        $url = 'https://api.moguding.net:9000/attendence/clock/v1/save';
        $res = self::postRaw($url, [
            'Authorization: ' . $this->token,
            'Content-Type: application/json; charset=UTF-8',
            'roleKey: student'
        ], $this->location);

        if (empty($res['code'])) {
            $this->writeLog("{$this->phone} >>> 签到失败，code 字段不存在，请重试！");
            throw new Exception('code 字段不存在，请重试！');
        }

        if ($res['code'] == 200) {
            $this->writeLog("{$this->phone} >>> 签到成功");
            return '签到成功';
        }

        $this->writeLog("{$this->phone} >>> 请求签到 API 失败，请重试 --- {$res['msg']}");
        return $res['msg'] ?? '请求签到 API 失败，请重试';//否则输出相关信息
    }

    /**
     * 写周报
     * @param string $startTime 一周开始时间，比如 2021-01-01 00:00:00
     * @param string $endTime 一周结束时间，比如 2021-01-01 23:59:59
     * @param string $weeks 第几周，比如     第5周
     * @param string $title 标题，比如       第5周周报
     * @param string $content 内容
     * @throws Exception
     */
    public function sendWeekNote(string $startTime, string $endTime, string $weeks, string $title, string $content)
    {
        $this->token = $this->getToken();
        $this->planId = $this->getPlanId();

        $url = 'https://118.190.120.71:9000/practice/paper/v1/save';

        $postData = [
            'reportType' => 'week',
            'address' => '',
            'weeks' => $weeks,
            'latitude' => '0.0',
            'planId' => $this->planId,
            'startTime' => $startTime,
            'yearmonth' => '',
            'endTime' => $endTime,
            'title' => $title,
            'content' => $content,
            'longitude' => '0.0'
        ];

        $res = self::postRaw($url, [
            'Authorization: ' . $this->token,
            'Content-Type: application/json; charset=UTF-8',
            'roleKey: student',
            'Host' => 'api.moguding.net:9000'
        ], $postData);

        print_r($res);
    }

    /**
     * POST RAW 数据
     * @param string $url 网址
     * @param array $headers 头部
     * @param array $jsonArr POST的数据，传入数组
     * @return array 输出结果
     */
    public static function postRaw(string $url, array $headers, array $jsonArr): array
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        //取消 SSL 证书验证
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers ?? [
                'Content-Type: application/json; charset=UTF-8'
            ]
        );

        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($jsonArr, JSON_UNESCAPED_UNICODE));

        curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4121.0 Safari/537.36 Edg/84.0.495.2");
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);

        $content = curl_exec($curl);
        curl_close($curl);

        return json_decode($content, true) ?? [];
    }

    /**
     * 智能获取签到类型，上班还是下班
     * @param int $nowTime
     * @return string
     */
    public function autoOutSignType(int $nowTime = -1): string
    {
        if ($nowTime < 0) $nowTime = time();
        if ($nowTime <= strtotime(date('Y-m-d 09:50:00'))) return 'START';
        if ($nowTime >= strtotime(date('Y-m-d 18:00:00'))) return 'END';
        return 'STOP';
    }

    /**
     * 编写日志文件
     * @param string $content 日志内容
     * @return bool
     */
    private function writeLog(string $content): bool
    {
        $fileName = date("Y-m-d") . '.log';
        $content = date('[Y-m-d H:i:s] ==> ') . $content . PHP_EOL;
        return !!file_put_contents(dirname(__DIR__) . "/logs/{$fileName}", $content, FILE_APPEND);
    }
}