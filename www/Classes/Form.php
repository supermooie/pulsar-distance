<?php

require_once('Config.php');
require_once('Identifier.php');
require_once('ModuleData.php');
require_once('ModuleDataPartTwo.php');

define(HEADER_TEMPLATE, GENERIC_TEMPLATE_DIRECTORY . 'Header.html');
define(FOOTER_TEMPLATE, GENERIC_TEMPLATE_DIRECTORY . 'Footer.html');

class Form
{
  static public function GetPageHeader()
  {
    $content = file_get_contents(HEADER_TEMPLATE);

    Form::CreateMenuLinks($content);

    return $content;
  }

  static public function GetPageFooter()
  {
    $content = file_get_contents(FOOTER_TEMPLATE);

    return $content;
  }

  static public function LoadBodyTemplate($template_filename)
  {
    // check for file exists
    if (file_exists($template_filename)) {
      $content = file_get_contents($template_filename);
      return $content;
    } else {
      throw new Exception('Form::LoadBodyTemplate - template filename: ' .
        $template_filename . ' not found');
    }
  }

  // XXX: convert to replace file?
  static public function ReplaceNextPageLink($link, &$content)
  {
    $content = str_replace('[@NEXT_PAGE_LINK]', $link, $content);
  }

  static public function ReplaceFileText($tag, $filename, &$content)
  {
    $content = str_replace($tag, $filename, $content);

    /*if (file_exists($filename)) {
      $content = str_replace($tag, $filename, $content);
    } else {
      throw new Exception('Form::ReplaceFileText - filename: ' .
        $filename. ' not found');
    }*/
  }

  // just replace text via a tag in the html file
  static public function ReplaceText($tag, $text, &$content)
  {
    if (strrpos($content, $tag) === FALSE) {
      throw new Exception('Form::ReplaceText - tag: ' . $tag . ' not found');
    }

    $content = str_replace($tag, $text, $content);
  }

  static public function CreateMenuLinks(&$content)
  {
    $file = $_SERVER["SCRIPT_NAME"];
    $break = explode('/', $file);
    $pfile = $break[count($break) - 1];
  }

  static $files = array(
    'index.php',
    'guess_distance.php',
    'freq_time_information.php',
    'plot_0437.php',
    'values_information.php',
    'plot_values.php',
    'galaxy_information.php',
    'galaxy_plot.php'
  );



};

?>
