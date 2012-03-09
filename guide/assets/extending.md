# Extending

In the (untested) example below we add an optimizer for our PNG images. This
case is a bit different from the rest since a). we aren't dealing with text,
and b). we're using an external program.

**Step 1**: Specify the PNG asset type in `APPPATH/config/assets.php`:

    'types' => array(
      'png' => array('.png')
    ),

    'target_types' => array(
        'png' => array('png')
    )

**Step 2**: Create the compiler in `APPPATH/classes/assets.php`.

    class Assets extends Kohana_Assets {

      function compile_png($png, $filename)
      {
        // Create a temporary location to store the optimized image
        $tmp = tempnam('/tmp/', '');

        // Run
        exec('optipng '.$filename.' -out '.$tmp);

        // Return image
        return file_get_contents($tmp);
      }

    }

**Step 3**: Clear `DOCROOT/assets/` to make sure any PNGs are recompiled.


