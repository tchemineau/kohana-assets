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

  static function compile_less($less, array $source)
  {
    self::vendor('lessphp/lessc.inc');

    $lessc = new LessCss();

    $lessc->importDisabled = FALSE;

    // Cascaded imports
    $lessc->importRelativeDir = substr($source['path'], strlen($source['include_path']));
    $lessc->importDirs = self::include_paths();

    return self::compile_css($lessc->parse($less));
  }

  /**
   * Find the source files for a target asset.
   *
   * @param   string  Target asset (e.g. css/style.css)
   *
   * @return  array   NULL or array(include path, source file(s))
   */
  static function find_sources($target)
  {
    $target = pathinfo(substr($target, strlen(self::target_dir())));

    if ( ! isset($target['extension']))
    {
      $target['extension'] = '';
    }

    $target += array(
      // Path without the extension
      'pathname' => "{$target['dirname']}/{$target['filename']}",

      // Target type
      'type' => self::get_type($target['extension'])
    );

    $source = array(
      // Possible extension(s)
      'extension' => $target['extension'],

      // Possible type(s)
      'type' => (array) Arr::get(self::$config->target_types, $target['type']),
    );

    $concatable = FALSE;

    if ($source['type'])
    {
      // Target could consist of a directory of source files
      $concatable = in_array($target['pathname'], self::$config->concatable, TRUE);

      // It's a known type, so there is a compilation step possibly involving
      // multiple sources of multiple different types
      $source['extension'] = self::get_type_ext($source['type']);
    }

    foreach (self::include_paths() as $include_path)
    {
      // Path to test
      $path = $include_path.$target['pathname'];

      if ($concatable && is_dir($path))
      {
        // Multiple sources
        return array($include_path, self::ls($path, $source['extension']));
      }

      foreach ((array) $source['extension'] as $ext)
      {
        if ($ext && $ext{0} !== '.')
        {
          $ext = ".$ext";
        }

        if (file_exists($file = $path.$ext))
        {
          // Single source
          return array($include_path, $file);
        }
      }
    }

    return NULL;
  }

  /**
   * Determine the asset type given its file extension.
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
   * Get the file extension(s) for the given type(s).
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
   */
  static function include_paths($path = '')
  {
    $paths = array();

    foreach (Kohana::include_paths() as $include_path)
    {
      $paths[] = $include_path.self::source_dir().$path;
    }

    return $paths;
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
  static function ls($dir, $extensions = NULL, $recurse = FALSE)
  {
    $files = array();

    foreach (new DirectoryIterator($dir) as $file)
    {
      if ($file->isFile())
      {
        $ext = '.'.pathinfo($file->getFilename(), PATHINFO_EXTENSION);

        if ($extensions === NULL || in_array($ext, (array) $extensions, TRUE))
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

      list($include_path, $sources) = self::find_sources($target);

      foreach ((array) $sources as $source)
      {
        if (filemtime($source) > $target_modified)
        {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   */
  static function source_dir()
  {
    return 'assets/';
  }

  /**
   */
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
