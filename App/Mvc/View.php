<?php
namespace App\Mvc;

header('Content-type: text/plain');
class View
{
  private $date = [];
  private $folder;

  function __construct($app)
  {
    $this->folder = DIR.DS.'App'.DS.'View'.DS.$app.DS;
  }

  public function render($app)
  {
    $f2 = file_get_contents($this->folder.'/templates/editor.html');
    $filename = $this->folder.$app.'.html';
    if (file_exists($filename)) {
      $filename = file_get_contents($filename);
      // $filename = str_replace("@painel-cadastro-empresa", $f2, $filename);
       echo $filename;
    }
  }
}
