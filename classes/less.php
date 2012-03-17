<?php

/**
 * @package Kohana/Assets
 */
class Less extends lessc {

  public $importRelativeDir = '';
  public $importDirs = array();

  function findImport($url)
  {
    if ($url)
    {
      if (substr($url, 0, 1) !== '/')
      {
        $url = $this->importRelativeDir.'/'.$url;
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
