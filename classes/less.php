<?php

/**
 * Less PHP compiler, modified to make specifying import paths a bit easier.
 *
 * @package  Kohana/Assets
 * @author   Alex Little
 */
class Less extends lessc {

  // Don't actually import files, just look for them.
  public $imports_check = FALSE;

  public $imports = array();

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
          $this->imports[] = $file;

          return $this->imports_check ? NULL : $file;
        }
      }
    }

    return NULL;
  }

  function parse($str = null, $initial_variables = null)
  {
    $this->imported = array();

    return parent::parse($str, $initial_variables);
  }

}

?>
