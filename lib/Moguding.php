<?php

class Moguding
{
    private $phone = '';
    private $password = '';

    private $token = '';
    private $planId = '';

    /**
     * @var array
     */
    private $location = [];

    public function setData($phone, $password, array $location)
    {
        $this->phone = $phone;
        $this->password = $password;
        $this->location = $location;

        return $this;
    }

    public function run()
    {
        $this->token = $this->getToken();
        $this->planId = $this->getPlanId();
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

        if (empty($res)) throw new Exception('登陆失败，请稍后再试');

        if ($res['code'] !== 200) return $res['msg'] ?? '登陆失败';

        if (empty($res['data']['token'])) throw new Exception('data.token 字段不存在，请重试！');;

        return $res['data']['token'];
    }

    /**
     * 获取计划 ID
     * @return mixed|string
     * @throws Exception
     */
    public function getPlanId()
    {
        $url = 'https://api.moguding.net:9000/practice/plan/v1/getPlanByStu';
        $res = self::postRaw($url, [
            'Authorization: ' . $this->token,
            'Content-Type: application/json; charset=UTF-8',
            'roleKey: student'
        ], []);

        if (empty($res)) throw new Exception('获取 PlanID 失败，请稍后再试');

        if ($res['code'] !== 200) return $res['msg'] ?? '获取 PlanID 失败';

        if (empty($res['data'][0]['planId'])) throw new Exception('data[0].planId 字段不存在，请重试！');

        return $res['data'][0]['planId'];

    }

    /**
     * 执行签到
     * @param string $type START：上班签到   END：下班签到
     */
    public function doSign(string $type = 'START', string $device = 'android')
    {
        $this->location['device'] = strtolower($device);//设备
        $this->location['planId'] = $this->planId;
        $this->location['type'] = strtoupper($type);//签到类型
        $this->location['state'] = 'NORMAL';//状态
        $this->location['attendanceType'] = '';//未知属性

        $url = 'https://api.moguding.net:9000/attendence/clock/v1/save';
        $res = self::postRaw($url, [
            'Authorization: ' . $this->token,
            'Content-Type: application/json; charset=UTF-8',
            'roleKey: student'
        ], $this->location);

        print_r($res);
    }

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
}