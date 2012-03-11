<?php

/**
 * @package Kohana/Assets
 */
class LessCss extends lessc {

  public $importRelativePath = '';
  public $importDirs = array();

  function findImport($url)
  {
    if ($url)
    {
      if ($url{0} !== '/')
      {
        $url = $this->importRelativePath.'/'.$url;
      }

      foreach ((array) $this->importDirs as $dir)
      {
        $full = $dir.'/'.$url;

        if ($this->fileExists($file = $full.'.less') || $this->fileExists($file = $full))
        {
          return $file;
        }
      }
    }

    return NULL;
  }

}

?>
