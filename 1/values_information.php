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

  $next_page = NEXT_PAGE_FILENAME . '?id=' . $id;

  Form::ReplaceNextPageLink($next_page, $content);
  Form::ReplaceText('[@NEXT_PAGE_TEXT]', NEXT_PAGE_TEXT, $content);
  Form::ReplaceText('[@TITLE]', TITLE, $content);

  echo $content;
} catch (Exception $e) {
  echo 'Error: ', $e->getMessage();
}

?>
