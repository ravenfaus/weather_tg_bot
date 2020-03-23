<?php

/**
 *
 */
class InlineQueryResultArticle
{
  public $type = 'article';
  public $id;
  public $title;
  public $input_message_content;
  public $description;
  public $reply_markup;

  public function __construct($id, $title, $content, $desc='', $reply_markup=null)
  {
    $this->id = $id;
    $this->title = $title;
    $this->input_message_content = $content;
    $this->description = $desc;

    $this->reply_markup = $reply_markup;
  }

  public function get()
  {
    $arr = ['type' => $this->type,
            'id' => $this->id,
            'title' => $this->title,
            'input_message_content' => $this->input_message_content,
            'description' => $this->description];
    if (!is_null($this->reply_markup))
      $arr['reply_markup'] = $this->reply_markup;
    return $arr;
  }
}


?>
