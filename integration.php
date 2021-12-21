<?php
define('CRM_HOST', ''); // Ваш домен CRM системы
define('CRM_PORT', '443'); // Порт сервера CRM. Установлен по умолчанию
define('CRM_PATH', '/crm/configs/import/lead.php'); // Путь к компоненту lead.rest
define('CRM_LOGIN', ''); // Логин пользователя Вашей CRM по управлению лидами
define('CRM_PASSWORD', ''); // Пароль пользователя Вашей CRM по управлению лидами

$Order_ID = (int) $hook->getValue("orderID");

//получаем данные товара из modx
$sql = 'SELECT * FROM `'.$modx->getOption('table_prefix').'shopkeeper3_purchases` WHERE `order_id` = '.$Order_ID;
$q = $modx->prepare($sql);
$q->execute();
$res = $q->fetchAll(PDO::FETCH_ASSOC);

$sql2 = 'SELECT * FROM `'.$modx->getOption('table_prefix').'shopkeeper3_orders` WHERE `id` = '.$Order_ID;
$q2 = $modx->prepare($sql2);
$q2->execute();
$res2 = $q2->fetchAll(PDO::FETCH_ASSOC);

$order = '';
$total = 0;

foreach($res as $val) {
    $options = (array)json_decode($val['options']); 
    
    $order .= '
    <p><b>'.$val['name'].'</b><br>
    '.$options['collection'][0].'<br>
    '.$options['article'][0].'<br>
    '.$options['sizze'][0].'<br>
    Количество: '.$val['count'].'<br>
    Цена: '.$val['price'].' руб.</p>';  
}

$total = $res2[0]['price'];
$order_promo = (array)json_decode($res2[0]['contacts']); 
$promo = '';
foreach ($order_promo as $promo_val) {
    if ($promo_val->name == 'promo') {
        $promo = $promo_val->label.': '.$promo_val->value;
    }
}

$postData = array(
    'TITLE' => 'Лид с сайта №'.htmlspecialchars($hook->getValue("orderID")),
    'LAST_NAME' => htmlspecialchars($hook->getValue("family")),
    'SECOND_NAME' => htmlspecialchars($hook->getValue("secondname")),
    'NAME' => htmlspecialchars($hook->getValue("name")),
    'ADDRESS' => htmlspecialchars($hook->getValue("postcode")).' '.htmlspecialchars($hook->getValue("region")).' '.htmlspecialchars($hook->getValue("city")).' '.htmlspecialchars($hook->getValue("street")),
    'WEB_OTHER' => 'essel.store',
    'PHONE_MOBILE' => htmlspecialchars($hook->getValue("phone")),
    'COMMENTS' => htmlspecialchars($hook->getValue("message")).'<br><p><hr></p><h2>'.$promo.'</h2><p><hr></p><h2>Товары:</h2>'.$order,
    'EMAIL_HOME' => htmlspecialchars($hook->getValue("email")), 
    'SOURCE_ID' => 'WEB',
    'STATUS_ID' => 'NEW',
    'PRODUCT_ID' => 'OTHER',
    'CURRENCY_ID' => 'RUB',
    'OPPORTUNITY' => $total
);

if (defined('CRM_AUTH')) {
    $postData['AUTH'] = CRM_AUTH;
} else {
    $postData['LOGIN']    = CRM_LOGIN;
    $postData['PASSWORD'] = CRM_PASSWORD;
}
$fp = fsockopen("ssl://" . CRM_HOST, CRM_PORT, $errno, $errstr, 30);
if ($fp) {
    $strPostData = '';
    foreach ($postData as $key => $value)
    $strPostData .= ($strPostData == '' ? '' : '&') . $key . '=' . urlencode($value);
    $str = "POST " . CRM_PATH . " HTTP/1.0\r\n";
    $str .= "Host: " . CRM_HOST . "\r\n";
    $str .= "Content-Type: application/x-www-form-urlencoded\r\n";
    $str .= "Content-Length: " . strlen($strPostData) . "\r\n";
    $str .= "Connection: close\r\n\r\n";
    $str .= $strPostData;
    fwrite($fp, $str);
    $result = '';
    while (!feof($fp)) {
        $result .= fgets($fp, 128);
    }
    fclose($fp);
    $response = explode("\r\n\r\n", $result);
    $output   = '<pre>' . print_r($response[1], 1) . '</pre>';
    echo $output;
} else {
    echo 'Connection Failed! ' . $errstr . ' (' . $errno . ')';
}
