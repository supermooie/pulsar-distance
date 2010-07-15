<?php

require_once('Config.php');
require_once(ROOT_DIRECTORY . 'Classes/Form.php');

define(TEMPLATE_FILE, TEMPLATE_DIRECTORY . 'values_information.html');
define(NEXT_PAGE_FILENAME, 'plot_values.php');
define(NEXT_PAGE_TEXT, 'plotting time and frequency values');
define(TITLE, 'x-y Values Plot Information');

try {
  $id = $_GET['id'];

  $content = Form::GetPageHeader();
  $content .= Form::LoadBodyTemplate(TEMPLATE_FILE);
  $content .= Form::GetPageFooter();

  Form::ReplaceNextPageLink(NEXT_PAGE_FILENAME . '?id=' . $id, $content);
  Form::ReplaceText('[@TITLE]', TITLE, $content);

  echo $content;
} catch (Exception $e) {
  echo 'Error: ', $e->getMessage();
}

?>
