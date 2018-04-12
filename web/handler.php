<?php

require '../vendor/autoload.php';

if ($_POST) {

parse_str($_POST['orderlist'], $orderlist);
parse_str($_POST['userdata'], $userdata);
/*
$orderlist - массив со списком заказа
$userdata - данные заказчика
*/

// При желании, можно посмотреть полученные данные, записав их в файл:
// file_put_contents('cart_data_log.txt', var_export($orderlist, 1) . "\r\n");
// file_put_contents('cart_data_log.txt', var_export($userdata, 1), FILE_APPEND);


// Заголовок письма
$subject = 'Заказ от '.date('d.m.Y').'г.';
// ваш Email
$admin_mail = 'eu.bogomaz@gmail.com';
// Email заказчика (как fallback - ваш же Email)
$to = !empty($userdata['user_mail']) ? $userdata['user_mail'] : $admin_mail;
$to2 = 'y.bohomaz@gmail.com';

// Формируем таблицу с заказанными товарами
$tbl = '<table style="width: 100%; border-collapse: collapse;">
	<tr>
		<th style="width: 1%; border: 1px solid #333333; padding: 5px;">ID</th>
		<th style="width: 1%; border: 1px solid #333333; padding: 5px;"></th>
		<th style="border: 1px solid #333333; padding: 5px;">Наименование</th>
		<th style="border: 1px solid #333333; padding: 5px;">Цена</th>
		<th style="border: 1px solid #333333; padding: 5px;">Кол-во</th>
	</tr>';
$total_sum = 0;
foreach($orderlist as $id => $item_data) {
	$total_sum += (float)$item_data['count'] * (float)$item_data['price'];
	$tbl .= '
	<tr>
		<td style="border: 1px solid #333333; padding: 5px;">'.$item_data['id'].'</td>
		<td style="border: 1px solid #333333;"><img src="'.$item_data['img'].'" alt="" style="max-width: 64px; max-height: 64px;"></td>
		<td style="border: 1px solid #333333; padding: 5px;">'.$item_data['title'].'</td>
		<td style="border: 1px solid #333333; padding: 5px;">'.$item_data['price'].'</td>
		<td style="border: 1px solid #333333; padding: 5px;">'.$item_data['count'].'</td>
	</tr>';
}
$tbl .= '<tr>
		<td  style="border: 1px solid #333333; padding: 5px;" colspan="3">Итого:</td>
		<td style="border: 1px solid #333333; padding: 5px;"><b>'.$total_sum.'</b></td>
		<td style="border: 1px solid #333333;">&nbsp;</td>
	</tr>
</table>';
// Тело письма
$body = '
<html>
<head>
  <title>'.$subject.'</title>
</head>
<body>
  <p>Информация о заказчике:</p>
	<ul>
		<li><b>Ф.И.О.:</b> '.$userdata['user_name'].'</li>
		<li><b>Тел.:</b> '.$userdata['user_phone'].'</li>
		<li><b>Email:</b> '.$userdata['user_mail'].'</li>
		<li><b>Адрес:</b> '.$userdata['user_address'].'</li>
		<li><b>Комментарий:</b> '.$userdata['user_comment'].'</li>
	</ul>
	<p>Информация о заказае:</p>
  '.$tbl.'
	<p>Письмо создано автоматически. Пожалуйста, не отвечайте на него, т.к. все ушли на пляж!</p>
</body>
</html>';

// Заголовки
$headers   = []; // или $headers = array() для версии ниже 5.4
$headers[] = 'MIME-Version: 1.0'; // Обязательный заголовок
$headers[] = 'Content-type: text/html; charset=utf-8'; // Обязательный заголовок. Кодировку изменить при необходимости
$headers[] = 'From: Best Shop <noreply@best-shop.piva.net>'; // От кого
$headers[] = 'Bcc: Admin <'.$admin_mail.'>'; // скрытая копия админу сайта, т.е. вам
$headers[] = 'X-Mailer: PHP/'.phpversion();

// SendGrid
$from = new SendGrid\Email(null, $admin_mail);
$to = new SendGrid\Email(null, $to);
$content = new SendGrid\Content("text/html", $body);
$mail = new SendGrid\Mail($from, $subject, $to, $content);
$mail2 = new SendGrid\Mail($from, $subject, $to2, $content);

$apiKey = getenv('SENDGRID_API_KEY');
$sg = new \SendGrid($apiKey);

$response = $sg->client->mail()->send()->post($mail);
$response2 = $sg->client->mail()->send()->post($mail2);
// echo $response->statusCode();
// echo $response->headers();
// echo $response->body();

// Ответ на запрос
$response = [
	'errors' => $response->statusCode() != 202,
	'message' => $response->statusCode() == 202 ? 'Заказ принят в обработку!' : 'Хьюстон! У нас проблемы!'
];
// ! Для версий PHP < 5.4 использовать традиционный синтаксис инициализации массивов:
/*
$response = array (
	'errors' => !$send_ok,
	'message' => $send_ok ? 'Заказ принят в обработку!' : 'Хьюстон! У нас проблемы!'
);
*/
exit( json_encode($response) );

} else {
	exit( json_encode('Хьюстон! У нас проблемы!') );
}