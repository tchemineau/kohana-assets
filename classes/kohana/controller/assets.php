<?php

/**
 * Main controller.
 *
 * @package  Kohana/Assets
 * @author   Alex Little
 */
class Kohana_Controller_Assets extends Controller {

  public function action_serve()
  {
    $target = Assets::target_dir() . $this->request->param('target');

    // Search for source files
    list($include_path, $sources) = Assets::find_sources($target);

    if ($sources)
    {
      // Create parent directories as necessary
      if (is_dir($dir = dirname($target)) || mkdir($dir, 0777, TRUE))
      {
        $result = FALSE;

        foreach ((array) $sources as $source)
        {
          $info = pathinfo($source);

          // Additional info
          $info += array('path' => $source, 'include_path' => $include_path);

          // Check if the asset type is known
          $type = Assets::get_type($info['extension']);

          if ( ! $type)
          {
            // Simple, single-source asset with no compilation step. Just link
            // to it and we're done.
            symlink($source, $target);
          }
          else if (is_callable($fn = "Assets::compile_{$type}"))
          {
            // Compiled asset
            $result.= call_user_func($fn, file_get_contents($source), $info);
          }
          else
          {
            throw new Kohana_Exception('Missing compiler for asset type :type', array('type' => $type));
          }
        }

        if ($result !== FALSE)
        {
          file_put_contents($target, $result);
        }

        if (is_file($target) || is_link($target))
        {
          // Success!
          $this->request->redirect($this->request->uri());
        }
      }
    }

    throw new HTTP_Exception_404();
  }

}

?>
