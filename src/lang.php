<?php
class Lang
{
    private $lang;
    private $data;
    private $dir;

    public function __construct($lang)
    {
        $this->lang = $lang;
        $this->dir = __DIR__ . '/../lang/';
        $this->getData();
    }

    public function getLang()
    {
    	return $this->lang;
    }

    private function getData()
    {
        $this->data = json_decode(file_get_contents( $this->dir . $this->lang . '.json'), true);
    }

    public function getParam($param, $data = [])
    {
        $text = $this->data[$param];
        if (count($data) > 0) {
            foreach ($data as $key => $val) {
                $text = str_replace("{" . $key . "}", $val, $text);
            }
        }
        return $text;
    }
}

?>
