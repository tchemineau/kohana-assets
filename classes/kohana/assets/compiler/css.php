<?php

class Kohana_Assets_Compiler_Css extends Kohana_Assets_Compiler {

  function __construct()
  {
    parent::__construct();

    $this->vendor('cssmin');
  }

  function compile($css)
  {
    return CssMin::minify($css);
  }

  function dependencies($css)
  {
    return NULL;
  }

}

?>
