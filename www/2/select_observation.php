<?php

require_once('Config.php');
require_once(ROOT_DIRECTORY . 'Classes/Form.php');

define(TEMPLATE_FILE, TEMPLATE_DIRECTORY . 'select_observation.html');
define(NEXT_PAGE_FILENAME, 'guess_distance.php');
define(NEXT_PAGE_TEXT, 'guess distance to the pulsar');
define(TITLE, 'Select Observation');

define(OBSERVATIONS_DIRECTORY, '/nfs/wwwresearch/pulsar/pulseATpks/');

define(TABLE_ROW_HTML, '<td>[@PULSAR_NAME]</td><td align=center>[@OBSERVATION_NUMBER]</td><td><input type="image" name="[@TEXT_PATH]" src="[@IMAGE_PATH]" width=108 height=100/></td>');

define(IMAGE_PATH_DEFAULT, 'Profiles/' . '[@PULSAR_NAME].[@GROUP_NUMBER].[@OBSERVATION_NUMBER].gif');
define(PROCESSED_PULSARS_TEXT, 'You have already processed [@PULSAR_NAMES]. Please select another pulsar.');

//session_start();

try {
  $id = $_GET['id'];
  $data = new ModuleDataPartTwo($id);

  ProcessFormData($data);
  CheckSessionData($data);

  $content = Form::GetPageHeader();
  $content .= Form::LoadBodyTemplate(TEMPLATE_FILE);
  $content .= Form::GetPageFooter();

  // XXX: check session data (group, group number)
  $observations_table =
    CreateObservationsTable($data->get_group_name(), $data->get_group_number());

  Form::ReplaceText('[@TABLE_CONTENTS]', $observations_table, $content);
  Form::ReplaceText('[@TITLE]', TITLE, $content);

  if (isset($_SESSION['processed_pulsars'])) {
    $text = PROCESSED_PULSARS_TEXT;

    foreach ($_SESSION['processed_pulsars']['name'] as $n) {
      $names .= " $n";
    }

    $text = str_replace('[@PULSAR_NAMES]', $names, $text);
    Form::ReplaceText('[@PROCESSED_PULSARS_TEXT]', $text, $content);
  } else {
    Form::ReplaceText('[@PROCESSED_PULSARS_TEXT]', '', $content);
  }

  echo $content;
} catch (Exception $e) {
  echo 'Error: ', $e->getMessage();
}

function CheckSessionData(&$data)
{
  // If no group name has been stored, redirect to first page.
  if (sizeof($data->get_group_name()) === 0) {
    header('Location: ' . HTTP_ADDRESS);
  }
}

function ProcessFormData(&$data)
{
  $keys = array_keys($_POST);
  $temp_key = $keys[0];

  $return = strrpos($temp_key, '_x');

  if ($return !== FALSE) {
    $file = substr($temp_key, 0, -2);
    $file = str_replace('_', '.', $file);
    $data->set_observation_file($file);

    $pattern = '/J[0-9]{4}(\+|\-)[0-9]{4}/';
    preg_match($pattern, $file, $matches);

    $data->set_pulsar_name($matches[0]);

    //header('Location: ' . HTTP_ADDRESS . NEXT_PAGE_FILENAME);
    header('Location: ' . HTTP_ADDRESS . NEXT_PAGE_FILENAME . '?id=' . $data->get_id());
  }
}

function CreateObservationsTable($group, $group_number)
{
  foreach (glob(OBSERVATIONS_DIRECTORY . '*.8channels.txt') as $file) {
    list ($pulsar_name, $number, $observation_number, $NULL, $NULL) = explode('.', $file);

    if ($group_number == $number) {
      $pulsar_name = trim(str_replace(OBSERVATIONS_DIRECTORY, ' ', $pulsar_name));
      $pulsar_names[] = $pulsar_name;
      $files[] = $file;
      $observation_numbers[] = $observation_number;
    }
  }

  $toggle = true;
  $count = sizeof($pulsar_names);

  for ($i = 0; $i < $count; $i++) {

    if ($toggle) {
      $table_row = '<tr>';
    } else {
      $table_row = '';
    }

    $table_row .= TABLE_ROW_HTML;
    $table_row = str_replace('[@PULSAR_NAME]', $pulsar_names[$i], $table_row);
    $table_row =
      str_replace('[@OBSERVATION_NUMBER]', $observation_numbers[$i], $table_row);

    $image_path = IMAGE_PATH_DEFAULT;
    $image_path = str_replace('[@PULSAR_NAME]', $pulsar_names[$i], $image_path);
    $image_path = str_replace('[@GROUP_NUMBER]', $group_number, $image_path);
    $image_path = str_replace('[@OBSERVATION_NUMBER]', $observation_numbers[$i], $image_path);

    $table_row = str_replace('[@IMAGE_PATH]', $image_path, $table_row);
    $table_row = str_replace('[@TEXT_PATH]', $files[$i], $table_row);

    if (!$toggle) {
      $table_row .= '</tr>';
    }

    $table_rows_html .= $table_row;
    $toggle = !$toggle;
  }

  return $table_rows_html;
}

?>
