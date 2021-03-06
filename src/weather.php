<?php
/*
Weather class, using API of Dark Sky.
More about it: https://darksky.net/poweredby/
Author: t.me/RavenFaus
*/
require_once('lib/curl.php');
require_once('lang.php');

class weather
{
	private $url = 'https://api.darksky.net/forecast/';
	private $app_id;
	private $location;
	private $weather;
	private $lang;

	public function __construct($app_id, $lang='en')
	{
		$this->app_id = $app_id;
		$this->lang = $lang;
	}

	public function set_location($location)
	{
		$this->location = $location;
	}

	public static function get_coord($city, $token)
	{
		$url = "https://api.mapbox.com/geocoding/v5/mapbox.places/".urlencode($city).".json?access_token=".$token;
		$c = new curl();
		curl_setopt($c,CURLOPT_ENCODING, "utf-8");
		$r = $c->request($url, "GET");
		$j = json_decode($r, true);
		$result = [];
		foreach ($j['features'] as $city) {
			$longitude = $city['geometry']['coordinates'][0];
			$latitude = $city['geometry']['coordinates'][1];
			$result[$city['place_name']] = $longitude . ',' . $latitude;
		}
		return $result;
	}

	public function request()
	{
		$c = new curl();
		$r = $c->request($this->url.$this->app_id.'/'.$this->location.'?units=si&lang='.$this->lang->getLang(), 'GET');

		$j = json_decode($r, true);
		if($j)
			return $j;
		else
			return $r;
	}

	public function set_lang($lang)
	{
		$this->lang = new Lang($lang);
	}
	// codes for tg emojis about current weather
	// variants: clear-day, clear-night, rain, snow, sleet, wind, fog, cloudy, partly-cloudy-day, or partly-cloudy-night
	private function get_emoji($code)
	{
		switch ($code) {
            	case 'cloudy':
            		return '☁️';
            		break;
            	case 'clear-day':
            		return '☀️';
            		break;
            	case 'clear-night':
            		return '🌙';
            		break;
            	case 'rain':
            		return '☔️';
            		break;
            	case 'snow':
            		return '❄️';
            		break;
            	case 'sleet':
            		return '☃';
            		break;
            	case 'wind':
            		return '🌬';
            		break;
            	case 'fog':
            		return '🌁';
            		break;
            	case 'thermometer':
            		return '🌡';
            		break;
            	case 'partly-cloudy-day':
            		return '⛅️';
            		break;
            	case 'partly-cloudy-night':
            		return '☁️';
            		break;
            	default:
            		return '❔';
            		break;
            	}
	}

	public function get_current()
	{
		$this->weather = $this->request();
		$current = $this->weather['currently'];
        $temp = $current['temperature'];
        $humidity = json_decode('"\uD83D\uDCA7"').$this->lang->getParam('weather_humidity').' '.($current['humidity']*100).'%';
        $wind = json_decode('"\uD83C\uDF00"').$this->lang->getParam('weather_wind_speed', ['wind' => $current['windSpeed']]);
        $emoji = $this->get_emoji($current['icon']);

        $text = json_decode('"\uD83D\uDCAC"').$this->lang->getParam('weather_cur_temp') . $emoji . " " . $temp . "°C".PHP_EOL.$humidity.PHP_EOL.$wind;
        return $text;
	}

	public function get_today()
	{
		$this->weather = $this->request();
		$today = $this->weather['hourly']['data'];
		$text = '';
		// get weather for every 3 hours
		for ($i=1; $i < 24; $i+= 3) {
			$date = date('H:i', $today[$i]['time']);
			$text .= json_decode('"\uD83D\uDCAC"').$this->lang->getParam('weather_at',['date' => $date]).PHP_EOL;
			$temp = $this->lang->getParam('weather_temp') . ': ' .$today[$i]['temperature'];
			$emoji = $this->get_emoji($today[$i]['icon']);
			$wind = json_decode('"\uD83C\uDF00"').$this->lang->getParam('weather_wind_speed', ['wind' => $today[$i]['windSpeed']]);
			$humidity = json_decode('"\uD83D\uDCA7"').$this->lang->getParam('weather_humidity').': '.($today[$i]['humidity']*100).'%';
			$precips = $this->get_emoji('rain').$this->lang->getParam('weather_precip').': '.($today[$i]['precipProbability']*100).'%';
			$text .= $emoji.$temp.'°C '.PHP_EOL.$wind.PHP_EOL.$humidity.PHP_EOL.$precips.PHP_EOL.PHP_EOL;
		}
		$sunset = date('H:i', $this->weather['daily']['data']['0']['sunsetTime']);
		$sunrise = date('H:i', $this->weather['daily']['data']['0']['sunriseTime']);
		$text .= $this->get_emoji('clear-day') . $this->lang->getParam('weather_sunrise_at', ['date' => $sunrise]) . PHP_EOL;
		$text .= $this->get_emoji('clear-night') . $this->lang->getParam('weather_sunset_at', ['date' => $sunset]) . PHP_EOL;
		return $text;
	}

	public function get_daily()
	{
		$this->weather = $this->request();
		$list = $this->weather['daily']['data'];
		$text = '';
		foreach ($list as $day) {
			$date = date('l', $day['time']);
			$tempIcon = $this->get_emoji($day['icon']);
			$tempMin = $tempIcon.$this->lang->getParam('weather_min_temp').': '.$day['temperatureMin'].'°C at '.date('H:i',$day['temperatureMinTime']);
			$tempMax = $tempIcon.$this->lang->getParam('weather_max_temp').': '.$day['temperatureMax'].'°C at '.date('H:i',$day['temperatureMaxTime']);
			$humidity = json_decode('"\uD83D\uDCA7"').$this->lang->getParam('weather_humidity').' '.($day['humidity']*100).'%';
			$wind = json_decode('"\uD83C\uDF00"').$this->lang->getParam('weather_wind_speed',['wind' => $day['windSpeed']]);
			$precips = $this->get_emoji('rain').$this->lang->getParam('weather_precip').': '.($day['precipProbability']*100).'%';
			$sunrise = $this->get_emoji('clear-day').$this->lang->getParam('weather_sunrise_at', ['date' => date('H:i', $day['sunriseTime'])]);
			$sunset = $this->get_emoji('clear-night').$this->lang->getParam('weather_sunset_at', ['date' => date('H:i', $day['sunsetTime'])]);
			$summary = '📊'.$day['summary'];
			$text .= json_decode('"\uD83D\uDCAC"').$date.PHP_EOL.$tempMin.PHP_EOL.$tempMax.PHP_EOL.$humidity.PHP_EOL.$wind.PHP_EOL.$precips.PHP_EOL.$sunrise.PHP_EOL.$sunset.PHP_EOL.$summary.PHP_EOL.PHP_EOL;
		}
		return $text;
	}

	public function get_day($day)
	{
		$this->weather = $this->request();
		$day = $this->weather['daily']['data'][$day];
		$date = date('l', $day['time']);
		$tempIcon = $this->get_emoji($day['icon']);
		$tempMin = $tempIcon.$this->lang->getParam('weather_min_temp').': '.$day['temperatureMin'].'°C at '.date('H:i',$day['temperatureMinTime']);
		$tempMax = $tempIcon.$this->lang->getParam('weather_max_temp').': '.$day['temperatureMax'].'°C at '.date('H:i',$day['temperatureMaxTime']);
		$humidity = json_decode('"\uD83D\uDCA7"').$this->lang->getParam('weather_humidity').' '.($day['humidity']*100).'%';
		$wind = json_decode('"\uD83C\uDF00"').$this->lang->getParam('weather_wind_speed',['wind' => $day['windSpeed']]);
		$precips = $this->get_emoji('rain').$this->lang->getParam('weather_precip').': '.($day['precipProbability']*100).'%';
		$sunrise = $this->get_emoji('clear-day').$this->lang->getParam('weather_sunrise_at', ['date' => date('H:i', $day['sunriseTime'])]);
		$sunset = $this->get_emoji('clear-night').$this->lang->getParam('weather_sunset_at', ['date' => date('H:i', $day['sunsetTime'])]);
		$summary = '📊'.$day['summary'];
		$text = json_decode('"\uD83D\uDCAC"').$date.PHP_EOL.$tempMin.PHP_EOL.$tempMax.PHP_EOL.$humidity.PHP_EOL.$wind.PHP_EOL.$precips.PHP_EOL.$sunrise.PHP_EOL.$sunset.PHP_EOL.$summary.PHP_EOL.PHP_EOL;
		return $text;
	}
}
?>
