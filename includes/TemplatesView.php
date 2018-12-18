<?php

class TemplatesView {
  public function __construct() {
    $theme = '';
    if (isset($_GET['theme'])) {
      $theme = $_GET['theme'];
    }
    
    $this->setTheme($theme);
  }
  
  function setTheme($theme = '') {
    if ($theme != '')
      $this->theme = $theme;
    else
      $this->theme = DEFAULT_THEME;
  }

  function setContentType($content_type) {
      $this -> content_type = $content_type;
  }

  function displayTheme($template, $data) {
    $ret = null;
    $tpl_file = $template . '.php';
    if (is_file(TEMPLATE_DIR . $this->theme . '/' . $tpl_file)) {
      include TEMPLATE_DIR . $this->theme . '/' . $tpl_file;

      $ret = $contents;
    } 
    return $ret;
  }
  
}

?>