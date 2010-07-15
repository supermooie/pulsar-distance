<?php

require_once('Classes/Form.php');

define(TEMPLATE_FILE, TEMPLATE_DIRECTORY . 'galaxy_information.html');
define(NEXT_PAGE_FILENAME, 'galaxy_plot.php');
define(NEXT_PAGE_TEXT, 'galaxy plot');
define(TITLE, 'Galaxy Information');

session_start();

//print_r($_SESSION);

try {
  $content = Form::GetPageHeader();
  $content .= Form::LoadBodyTemplate(TEMPLATE_FILE);
  $content .= Form::GetPageFooter();

  Form::ReplaceNextPageLink(NEXT_PAGE_FILENAME, $content);
  Form::ReplaceText('[@TITLE]', TITLE, $content);

  echo $content;
} catch (Exception $e) {
  echo 'Error: ', $e->getMessage();
}

?>
