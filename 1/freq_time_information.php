<?php

require_once('Config.php');
require_once(ROOT_DIRECTORY . 'Classes/Form.php');

define(TEMPLATE_FILE, TEMPLATE_DIRECTORY . 'freq_time_information.html');
define(NEXT_PAGE_FILENAME, 'plot_0437.php');
define(NEXT_PAGE_TEXT, 'plotting J0437');
define(TITLE, 'Frequency vs Time Information');

try {
  $id = $_GET['id'];
  $data = new ModuleData($id);

  RemoveSessionData();

  $content = Form::GetPageHeader();
  $content .= Form::LoadBodyTemplate(TEMPLATE_FILE);
  $content .= Form::GetPageFooter();

  $guess = $data->get_distance_guess();

  $next_page = NEXT_PAGE_FILENAME . '?id=' . $id;

  Form::ReplaceNextPageLink($next_page, $content);
  Form::ReplaceText('[@TITLE]', TITLE, $content);
  Form::ReplaceText('[@GUESS_ENTERED]', $guess, $content);

  echo $content;
} catch (Exception $e) {
  echo 'Error: ', $e->getMessage();
}

function RemoveSessionData()
{
  unset($_SESSION['zoom_start']);
  unset($_SESSION['zoom_end']);
  unset($_SESSION['frequencies']);
  unset($_SESSION['times']);
}

?>
