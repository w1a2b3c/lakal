<?php
require dirname(dirname(__DIR__)) . '/includes/common.php';

function http_post($url, $data, $headers){
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}
function orders($channel_id) {
    global $DB;
    $orders = $DB->query('SELECT * FROM `pre_order` WHERE `channel` = ' . $channel_id . ' AND `status` = 0;')->fetchAll(PDO::FETCH_ASSOC);
    $data = [];
    foreach($orders as $order) {
        $data[$order['trade_no']] = $order;
    }
    return $data;
}
function bills($shopno, $auth){
    return json_decode(http_post('https://m1.lakala.com/m/a/mertransquery/statement/queryTransDetail', '{"startTime":"' . date('YmdHis', time() - 43200) . '","endTime":"' . date('Ymd') . '235959","page":1,"size":30,"merNo":"' . $shopno . '"}', ['Authorization:' . $auth, 'Content-Type:application/json;charset=utf-8']), true)['data'];
}

$channel_id = $argv[1];
$channel = $DB->getRow('SELECT * FROM `pre_channel` WHERE `plugin` = \'lklnew\' AND `id` = ' . $channel_id . ';');
if(!$channel) exit('通道不存在' . PHP_EOL);
$channel_config = json_decode($channel['config'], true);
$bills = bills($channel_config['shopno'], $channel_config['auth']);
$orders = orders($channel['id']);
foreach($bills as $bill) {
    if($bill['txnSts'] != '成功') continue;
    if(!($order = $orders[$bill['remark']])) continue;
    if($order['realmoney'] != $bill['txnAmt']) continue;
    processNotify($order);
    echo '成功回调 订单号：' . $order['trade_no'] . PHP_EOL;
}