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
$weather = new Weather($config->weather->token);
if (isset($data['callback_query'])) {
	parse_callback($data['callback_query']);
} elseif (isset($data['message'])) {
	parse_message($data['message']);
} elseif (isset($data['inline_query'])) {
  parse_inline($data['inline_query']);
} elseif (isset($data['chosen_inline_result']))
  parse_choosen($data['chosen_inline_result']);

function parse_choosen($res)
{
    global $db;
    $user = new user($res['from']);
    $db->set_last_msg_id($user->id, $res['inline_message_id']);
}

function parse_inline($iquery)
{
    global $api, $db, $weather;
    $id = $iquery['id'];
    $user = new User($iquery['from']);
    $lang = new Lang($db->user_lang($user->id));
    $weather->set_location($db->user_coord($user->id));
    $weather->set_lang($lang->getLang());
    $arr = [];
    $art = new InlineQueryResultArticle(
        0,
        $lang->getParam('weather_current'),
        ['message_text' => $weather->get_current()]
      );
    array_push($arr, $art->get());
    $art = new InlineQueryResultArticle(
        1,
        $lang->getParam('weather_today'),
        ['message_text' => $weather->get_today()]
      );
    array_push($arr, $art->get());

    $ik = new InlineKeyboard();
    $ik->addRow([new InlineButton('<', 'daily 0'), new InlineButton('>', 'daily 1')]);
    $art = new InlineQueryResultArticle(
        2,
        $lang->getParam('weather_daily'),
        ['message_text' => $weather->get_day(0)],
        '',
        $ik->replyMarkup()
      );
    array_push($arr, $art->get());

    $api->answerInlineQuery($id, $arr, 0);
}
function parse_callback($callback)
{
	global $api, $db, $weather;
	$user = new User($callback['from']);
  $id = $callback['inline_message_id'];
  if (!$db->user_exists($user->id))
    $user = $db->get_user_by('last_msg_id', $id);
	$lang = new Lang($db->user_lang($user->id));
	$cid = $callback['id'];
	$query = $callback['data'];
	$data = explode(' ', $query);
  $weather->set_location($db->user_coord($user->id));
  $weather->set_lang($lang->getLang());
	switch ($data[0]) {
    case 'daily':
      $offset = $data[1];
      $ik = new InlineKeyboard();
      $prev_day = ($offset - 1) < 0 ? 0 : $offset - 1;
      $next_day = ($offset + 1) > 7 ? 7 : $offset + 1;
      $ik->addRow([new InlineButton('<', 'daily ' . $prev_day), new InlineButton('>', 'daily ' . $next_day)]);
      $text = $weather->get_day($offset);
      $api->editInlineMessageText($id, $text, $ik->replyMarkup());
      break;
		case 'weather':
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
        case 'coord':
          $coord = $data[2];
          $db->set_coord($user->id, $coord);
          $text = $lang->getParam('location_changed');
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
    case '/help':
      $api->sendMessage($user->id, $lang->getParam('help_message'));
      break;
    case '/start':
      $api->sendMessage($user->id, $lang->getParam('welcome_message', ['name' => $user->first_name]), $keyboard->replyMarkup());
      $db->set_last_msg($user->id, 'weather location');
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
    case $lang->getParam('btn_location'):
        $api->sendMessage($user->id, $lang->getParam('set_location'), $keyboard->replyMarkup());
        $db->set_last_msg($user->id, 'weather location');
        break;
    default:
      $last_msg = explode(' ', $db->get_last_msg($user->id));
      switch ($last_msg[0]) {
        case 'weather':
          switch ($last_msg[1]) {
            case 'location':
              global $config;
              $result = Weather::get_coord($text, $config->mapbox->token);
              $ik = new InlineKeyboard();
              foreach ($result as $city => $coord) {
                $ik->addRow([new InlineButton($city, 'weather coord ' . $coord)]);
              }
              $api->sendMessage($user->id, $lang->getParam('choose_location'), $ik->replyMarkup());
              $db->set_last_msg($user->id, '');
              break;
          }
          break;
      }
      break;
  }
}

function get_keyboard($lang)
{
	$keyboard = new Keyboard();
	$keyboard->addRow([$lang->getParam('btn_weather')]);
	$keyboard->addRow([$lang->getParam('btn_language')]);
  $keyboard->addRow([$lang->getParam('btn_location')]);
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
