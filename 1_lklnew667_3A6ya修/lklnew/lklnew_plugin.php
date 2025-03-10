<?php
class lklnew_plugin
{
    public static $info = [
        'name'        => 'lklnew',
        'showname'    => '拉卡拉个人账户支付重写版-25/03/08更新',
        'author'      => '拉卡拉',
        'link'        => 'https://zibovip.top',
        'types'       => ['alipay', 'wxpay'],
        'inputs' => [
            'shopno' => [
                'name' => '商户号',
                'type' => 'input',
                'note' => '',
            ],
            'termno' => [
                'name' => '终端号',
                'type' => 'input',
                'note' => '',
            ],
            'shopname' => [
                'name' => '店铺名',
                'type' => 'input',
                'note' => '',
            ],
            'auth' => [
                'name' => 'Authorization',
                'type' => 'input',
                'note' => '',
            ],
        ],
        'select' => null,
        'note' => '<span style="color: red;font-size:2em;">更多资源：https://zibovip.top</span><br>宝塔定时任务监控<br>任务类型：Shell脚本<br>执行周期：N秒 3秒（这里根据实际情况调节）<br>执行用户：www<br>脚本内容：php ' . __DIR__ . '/monitor.php 通道ID',
        'bindwxmp' => false,
        'bindwxa' => false,
    ];
    public static function submit(){
        global $order;
        return ['type'=>'jump','url'=>'/pay/leader/' . $order['trade_no'] . '/'];
    }
    public static function leader(){
        global $channel, $order;
        $this_time = time();
        $submit_data = [
            'ver' => '1.0.0',
            'sign' => '',
            'timestamp' => date('YmdHis', $this_time),
            'reqId' => '',
            'rnd' => '',
            'reqData' => [
                'shopNo' => $channel['shopno'],
                'termNo' => $channel['termno'],
                'shopName' => $channel['shopname'],
                'type' => 'MICROCODE',
                'expireTime' => '300',
                'orderField' => json_encode([
                    'amount' => $order['realmoney'] * 100 . '.0',
                    'exterMerOrderNo' => '',
                    'exterOrderSource' => '',
                    'subject' => '',
                    'description' => '',
                    'orderRemark' => $order['trade_no']
                ]),
                'txnField' => json_encode([
                    'outTradeNo' => md5($this_time . rand(11111, 99999)),
                    'operatorId' => '',
                    'amount' => $order['realmoney'] * 100 . '.0',
                    'remark' => $order['trade_no']
                ]),
                'snAutoExpireFlag' => ''
            ]
        ];
        $submit_headers = [
            'Authorization:' . $channel['auth'],
            'Content-Type:application/json;charset=utf-8'
        ];
        $result = json_decode(self::http_post('https://wallet.lakala.com/m/a/code/generate', json_encode($submit_data), $submit_headers), true);
        $pay_url = $result['respData']['url'];
        if($order['typename'] == 'alipay') {
            if(checkwechat()) return ['type'=>'page','page'=>'wxopen'];
            if(strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient')) return ['type' => 'qrcode', 'page' => 'alipay_wap', 'url' => $pay_url];
            return ['type' => 'qrcode', 'page' => 'alipay_qrcode', 'url' => $pay_url];
        }
        if($order['typename'] == 'wxpay') {
            if(checkwechat()) return ['type' => 'scheme', 'page' => 'wxpay_mini', 'url' => $pay_url];
            if(checkmobile()) return ['type' => 'qrcode', 'page' => 'wxpay_wap', 'url' => $pay_url];
            return ['type' => 'qrcode', 'page' => 'wxpay_qrcode', 'url' => $pay_url];
        }
    }
    public static function http_post($url, $data, $headers){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}