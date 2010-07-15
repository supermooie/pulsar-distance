<?php

require_once('Config.php');
require_once(ROOT_DIRECTORY . 'Classes/Form.php');
require_once(ROOT_DIRECTORY . 'Classes/DataValidation.php');

define(TEMPLATE_FILE, TEMPLATE_DIRECTORY . 'index.html');
define(CURRENT_PAGE, 'index.php');
define(NEXT_PAGE_FILENAME, 'select_observation.php');
define(NEXT_PAGE_TEXT, 'select observation');
define(TITLE, 'Select Group');

define(SESSION_FILE, '/nfs/wwwresearch/pulsar/pulseATpks/session');
define(SCHOOL_DELIMIT, 'ID:');
define(DATE_DELIMIT, 'DATE:');

define(TABLE_ROW, '<tr><td>[@GROUP]</td><td>[@DATE]</td><td><input type=radio name="group" value="[@GROUP]*[@GROUP_NUMBER]" onclick="submit();"></td></tr>');

session_start();

try {
  $id = $_GET['id'];

  // if there is no (valid) id set, create one and reload the page
  if (!Identifier::IdExists($id)) {
    $id = Identifier::GenerateNewIdentifier();
    Identifier::AddId($id);

    header('Location: ' . HTTP_ADDRESS . CURRENT_PAGE . '?id=' . $id);
  }

  $data = new ModuleDataPartTwo($id);

  /*echo 'dist: ', $data->get_distance_guess(), '<br>';
  echo 'freqs: ', $data->get_frequencies(), '<br>';
  echo 'times: ', $data->get_times(), '<br>';
  echo 'user_dm: ', $data->get_user_dm(), '<br>';
  echo 'fov: ', $data->get_fov(), '<br>';
  echo 'proj: ', $data->get_projection(), '<br>';
  echo 'names: ', $data->get_names(), '<br>';
  echo 'arm names: ', $data->get_arm_names(), '<br>';
  echo 'group_name:', $data->get_group_name(), '<br>';
  echo 'group_number:', $data->get_group_number(), '<br>';*/

  $content = Form::GetPageHeader();
  $content .= Form::LoadBodyTemplate(TEMPLATE_FILE);
  $content .= Form::GetPageFooter();

  ProcessFormData($data);

  $table_contents = CreateTableOfGroups();

  Form::ReplaceText('[@TITLE]', TITLE, $content);
  Form::ReplaceText('[@TABLE_CONTENTS]', $table_contents, $content);

  echo $content;
} catch (Exception $e) {
  echo 'Error: ', $e->getMessage();
}

function CreateTableOfGroups()
{
  $contents = file_get_contents(SESSION_FILE);
  $lines = split("\n", trim($contents));

  // extract school and dates from the session file
  foreach ($lines as $l) {
    if (stripos($l, SCHOOL_DELIMIT) !== FALSE) {
      $groups[] = trim(substr($l, strlen(SCHOOL_DELIMIT),
        strlen($l) - strlen(SCHOOL_DELIMIT)));
    }

    if (stripos($l, DATE_DELIMIT) !== FALSE) {
      $dates[] = trim(substr($l, strlen(DATE_DELIMIT),
        strlen($l) - strlen(DATE_DELIMIT)));
    }
  }

  if (sizeof($groups) == 0) {
    throw new Exception('index.php::CreateTableOfGroups no groups found');
  }

  $group_number = sizeof($groups);

  $table_contents;

  $count = sizeof($groups);
  for ($i = $count - 1; $i >= 0; $i--) {
    $table_row = TABLE_ROW;

    $table_row = str_replace('[@GROUP]', $groups[$i], $table_row);
    $table_row = str_replace('[@DATE]', $dates[$i], $table_row);
    $table_row = str_replace('[@GROUP_NUMBER]', $group_number, $table_row);

    $table_contents .= $table_row;
    $group_number--;
  }

  return $table_contents;
}

function ProcessFormData(&$data)
{
  if (isset($_POST['group'])) {
    // delimited by '*' => [school name], [group number]
    list($group, $group_number) = explode("*", $_POST['group']);

    $data->set_group_name(stripslashes($group));
    $data->set_group_number($group_number);

    // redirect to next page
    $next_page = HTTP_ADDRESS . NEXT_PAGE_FILENAME . '?id=' . $data->get_id();
    //header('Location: ' . HTTP_ADDRESS . NEXT_PAGE_FILENAME);
    //echo "redirecting $next_page <br>";
    header('Location: ' . $next_page);
  }
}

function RemoveSessionData()
{
  if ($_SERVER['HTTP_REFERER'] == 'http://pulseatparkes.atnf.csiro.au/distance/') {
    session_destroy();
  }

  unset($_SESSION['distance_guess']);
  unset($_SESSION['zoom_start']);
  unset($_SESSION['zoom_end']);
  unset($_SESSION['frequencies']);
  unset($_SESSION['times']);
  unset($_SESSION['user_dm']);
  unset($_SESSION['group']);
  unset($_SESSION['group_number']);
  unset($_SESSION['frequency']);
}

?>

