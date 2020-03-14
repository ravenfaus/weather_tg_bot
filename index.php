<?php
define('PATH', realpath('./'));
// Require all files from lib
$files = glob('lib/*.php');

foreach ($files as $file) {
    require_once($file);
}
// Require all files from src
$files = glob('src/*.php');

foreach ($files as $file) {
    require_once($file);
}

$data = getRequest();
$config = json_decode(file_get_contents(PATH."/config.json"));
$api = new api($config->telegram->token);
$db = new database('src/users.db');
$weather = new Weather($config->weather->token, $config->weather->location);
if (isset($data['callback_query'])) {
	parse_callback($data['callback_query']);
} elseif (isset($data['message'])) {
	parse_message($data['message']);
}

function parse_callback($callback)
{
	global $api, $db, $weather;
	$user = new User($callback['from']);
	$lang = new Lang($db->user_lang($user->id));
	$cid = $callback['id'];
	$query = $callback['data'];
	$data = explode(' ', $query);
	switch ($data[0]) {
		case 'weather':
			$weather->set_lang($lang->getLang());
			switch ($data[1]) {
				case 'current':
					$text = $weather->get_current();
					break;
				case 'today':
					$text = $weather->get_today();
					break;
				case 'daily':
					$text = $weather->get_daily();
					break;
			}
			$api->sendMessage($user->id, $text);
			break;
    }
	$api->answerCallbackQuery($cid);
}

function parse_message($msg)
{
  global $api, $db;
  $user = new User($msg['from']);
  if (!$db->user_exists($user->id))
    $db->add_user($user);
  $lang = new Lang($db->user_lang($user->id));
  $text = $msg['text'];
  $keyboard = get_keyboard($lang);
  switch ($text) {
    case '/start':
      $api->sendMessage($user->id, $lang->getParam('welcome_message', ['name' => $user->first_name]), $keyboard->replyMarkup());
      break;
    case $lang->getParam('btn_weather'):
      $ik = new InlineKeyboard();
      $ik->addRow([new InlineButton($lang->getParam('weather_current'), 'weather current'),
            new InlineButton($lang->getParam('weather_today'), 'weather today'),
            new InlineButton($lang->getParam('weather_daily'), 'weather daily')]);
      $api->sendMessage($user->id, $lang->getParam('weather_choose'), $ik->replyMarkup());
      break;
    case $lang->getParam('btn_language'):
        if ($lang->getLang() == 'en')
          $lang = new Lang('ru');
        else
          $lang = new Lang('en');
        $db->set_lang($user->id, $lang->getLang());
        $keyboard = get_keyboard($lang);
        $api->sendMessage($user->id, $lang->getParam('language_changed'), $keyboard->replyMarkup());
        break;
  }
}

function get_keyboard($lang)
{
	$keyboard = new Keyboard();
	$keyboard->addRow([$lang->getParam('btn_weather')]);
	$keyboard->addRow([$lang->getParam('btn_language')]);
	return $keyboard;
}

function getRequest()
{
	$postdata = file_get_contents("php://input");
	$json = json_decode($postdata, true);
	if($json)
		return $json;
	return $postdata;
}
?>
