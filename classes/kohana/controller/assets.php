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
    $target = Assets::target_dir().$this->request->param('target');

    if ($sources = Assets::find_sources($target))
    {
      // Create parent directories
      if (is_dir($dir = dirname($target)) || mkdir($dir, 0777, TRUE))
      {
        $result = FALSE;

        foreach ($sources as $source)
        {
          $type = Assets::get_type(pathinfo($source, PATHINFO_EXTENSION));

          if ( ! $type)
          {
            // Simple, single-source asset with no compilation step. Just link
            // to it and we're done.
            symlink($source, $target);
          }
          else if (is_callable($fn = "Assets::compile_{$type}"))
          {
            // Compile asset
            $result.= call_user_func($fn, file_get_contents($source), $source);
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
