<?php

require_once('Config.php');
require_once(ROOT_DIRECTORY . 'Classes/Form.php');

define(TEMPLATE_FILE, TEMPLATE_DIRECTORY . 'freq_time_information.html');
define(NEXT_PAGE_FILENAME, 'plot_profile.php');
define(NEXT_PAGE_TEXT, 'plotting profile');
define(TITLE, 'Frequency vs Time Information');

session_start();

try {
  $id = $_GET['id'];
  $data = new ModuleDataPartTwo($id);

  $content = Form::GetPageHeader();
  $content .= Form::LoadBodyTemplate(TEMPLATE_FILE);
  $content .= Form::GetPageFooter();

  $next_page = NEXT_PAGE_FILENAME . '?id=' . $data->get_id();
  Form::ReplaceNextPageLink($next_page, $content);
  Form::ReplaceText('[@TITLE]', TITLE, $content);

  echo $content;
} catch (Exception $e) {
  echo 'Error: ', $e->getMessage();
}

?>
