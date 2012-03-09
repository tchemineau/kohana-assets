<?php

/**
 * Compilers and other helper functions.
 *
 * @package  Kohana/Assets
 * @author   Alex Little
 */
class Kohana_Assets {

  static $config;

  static function compile_coffee($coffee)
  {
    $level = error_reporting();
    error_reporting(0);

    self::vendor('coffeescript/coffeescript');

    error_reporting($level);

    return self::compile_js(CoffeeScript\compile($coffee));
  }

  static function compile_css($css)
  {
    self::vendor('cssmin');

    return CssMin::minify($css);
  }

  static function compile_js($js)
  {
    self::vendor('jsminplus');

    // Strips end semi-colons, which can break multi-source assets; should try
    // to find a better solution for this.
    return JSMinPlus::minify($js).';';
  }

  static function compile_less($less, $filename)
  {
    self::vendor('lessphp/lessc.inc');

    $lessc = new lessc();

    $lessc->importDisabled = FALSE;
    $lessc->importDir = dirname($filename);

    return self::compile_css($lessc->parse($less));
  }

  /**
   * Find the source files for a target asset.
   *
   * @param   string  Target asset (e.g. css/style.css)
   *
   * @return  array   Array containing the target's source files, or NULL
   */
  static function find_sources($target)
  {
    $target = pathinfo( substr($target, strlen(self::target_dir())) );

    if ( ! isset($target['extension']))
    {
      $target['extension'] = '';
    }

    $target += array
    (
      // Path without the extension
      'pathname' => "{$target['dirname']}/{$target['filename']}",

      // Target type
      'type' => self::get_type($target['extension'])
    );

    $source = array
    (
      // Directory that will contain source file(s)
      'dirname' => self::source_dir().$target['dirname'],

      // Possible extension(s)
      'extension' => $target['extension'],

      // Possible type(s)
      'type' => (array) Arr::get(self::$config->target_types, $target['type']),
    );

    if ($source['type'])
    {
      // It's a known type, so there is a compilation step possibly involving
      // multiple sources of different types
      $source['extension'] = self::get_type_ext($source['type']);

      if (in_array($target['pathname'], self::$config->concatable))
      {
        foreach (Kohana::include_paths() as $dir)
        {
          if (is_dir($dir.= $source['dirname'].'/'.$target['filename']))
          {
            // Multiple sources
            return self::ls($dir, $source['extension']);
          }
        }
      }
    }

    foreach ((array) $source['extension'] as $ext)
    {
      if ($ext && $ext{0} === '.')
      {
        $ext = substr($ext, 1);
      }

      if ($file = Kohana::find_file($source['dirname'], $target['filename'], $ext))
      {
        // Single source
        return array($file);
      }
    }

    return NULL;
  }

  /**
   * Determine the type given a file extension.
   */
  static function get_type($ext)
  {
    if ($ext && $ext{0} !== '.')
    {
      $ext = ".{$ext}";
    }

    foreach (self::$config->types as $type => $extensions)
    {
      if (in_array($ext, $extensions, TRUE))
      {
        return $type;
      }
    }

    return NULL;
  }

  /**
   * Get the extension(s) for the given type(s).
   */
  static function get_type_ext($types)
  {
    $ext = array();

    foreach ((array) $types as $type)
    {
      $ext = array_merge($ext, Arr::get(self::$config->types, $type, array()));
    }

    return $ext;
  }

  /**
   * Check for modifications (if enabled) and set asset route.
   */
  static function init()
  {
    self::$config = Kohana::$config->load('assets');

    if (self::$config->watch)
    {
      foreach (self::ls(self::target_dir(), NULL, TRUE) as $asset)
      {
        // Delete assets whose source files have changed (they'll be recompiled
        // the next time they are requested).
        self::modified($asset) && unlink($asset);
      }
    }

    // Set route.
    Route::set('assets', self::source_dir().'<target>', array('target' => '.+'))
      ->defaults(array(
          'controller' => 'assets',
          'action'     => 'serve'
        ));
  }

  /**
   * List files in a directory. Optionally filter for file extensions and 
   * recurse into sub-directories.
   *
   * @param  string
   * @param  array
   * @param  boolean
   *
   * @return  array  List of files
   */
  static function ls($dir, array $extensions = NULL, $recurse = FALSE)
  {
    $files = array();

    foreach (new DirectoryIterator($dir) as $file)
    {
      if ($file->isFile())
      {
        $ext = '.'.pathinfo($file->getFilename(), PATHINFO_EXTENSION);

        if ($extensions === NULL || in_array($ext, $extensions))
        {
          $files[] = $file->getPathname();
        }
      }
      else if ($file->isDir() && ! $file->isDot() && $recurse)
      {
        $files = array_merge($files, self::ls($file->getPathname(), $extensions, TRUE));
      }
    }

    return $files;
  }

  /**
   * Check whether the source files for an asset have been modified since the
   * last time they were compiled.
   *
   * @param  string
   *
   * @return  boolean
   */
  static function modified($target)
  {
    if (is_file($target))
    {
      $target_modified = filemtime($target);

      foreach ((array) self::find_sources($target) as $source)
      {
        if (filemtime($source) > $target_modified)
        {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  static function source_dir()
  {
    return 'assets/';
  }

  static function target_dir()
  {
    return DOCROOT.self::source_dir();
  }

  /**
   */
  static function vendor()
  {
    foreach (func_get_args() as $file)
    {
      require_once Kohana::find_file('vendor', $file);
    }
  }

}

?>
