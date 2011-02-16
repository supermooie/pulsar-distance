<?php

require_once('Config.php');
require_once(ROOT_DIRECTORY . 'Classes/Form.php');
require_once(ROOT_DIRECTORY . 'Classes/DataValidation.php');

define(TEMPLATE_FILE, TEMPLATE_DIRECTORY . 'index.html');
define(CURRENT_PAGE, 'index.php');
define(NEXT_PAGE_FILENAME, 'freq_time_information.php');
define(NEXT_PAGE_TEXT, 'frequency-vs-time plot information');
define(TITLE, 'Module 1');
define(DISTANCE_ERROR_MESSAGE, '<p><font color=red>Error: Invalid estimate</font>');

try {
  $id = $_GET['id'];

  // if there is no (valid) id set, create one and reload the page
  if (!Identifier::IdExists($id)) {
    $id = Identifier::GenerateNewIdentifier();
    Identifier::AddId($id);

    header('Location: ' . HTTP_ADDRESS . CURRENT_PAGE . '?id=' . $id);
  }

  $data = new ModuleData($id);

  /*echo 'dist: ', $data->get_distance_guess(), '<br>';
  echo 'freqs: ', $data->get_frequencies(), '<br>';
  echo 'times: ', $data->get_times(), '<br>';
  echo 'user_dm: ', $data->get_user_dm(), '<br>';
  echo 'fov: ', $data->get_fov(), '<br>';
  echo 'proj: ', $data->get_projection(), '<br>';
  echo 'names: ', $data->get_names(), '<br>';
  echo 'arm names: ', $data->get_arm_names(), '<br>';*/

  RemoveSessionData();

  $distance_error = FALSE;

  ProcessFormData($data);

  $content = Form::GetPageHeader();
  $content .= Form::LoadBodyTemplate(TEMPLATE_FILE);
  $content .= Form::GetPageFooter();

  Form::ReplaceText('[@TITLE]', TITLE, $content);

  if (isset($_SESSION['distance_guess'])) {
    Form::ReplaceText('[@GUESS_ENTERED]', $_SESSION['distance_guess'], $content);
  } else {
    Form::ReplaceText('[@GUESS_ENTERED]', 0, $content);
  }

  if ($_SESSION['valid_guess'] == TRUE) {
    Form::ReplaceText('[@DISTANCE_ERROR_MESSAGE]', '', $content);
  } else {
    Form::ReplaceText('[@DISTANCE_ERROR_MESSAGE]', DISTANCE_ERROR_MESSAGE, $content);
  }

  echo $content;
} catch (Exception $e) {
  echo 'Error: ', $e->getMessage();
}

function RemoveSessionData()
{
  if ($_SERVER['HTTP_REFERER'] != "http://pulseatparkes.atnf.csiro.au/distance/") {
    unset($_SESSION['distance_guess']);
  }
}

function ProcessFormData(&$data)
{
  if (!isset($_SESSION['valid_guess'])) {
    $_SESSION['valid_guess'] = TRUE;
  }

  if (isset($_POST['distance_guess'])) {
    $distance_guess = DataValidation::removeXSS($_POST['pulsar_distance']);

    if (is_numeric($distance_guess) && $distance_guess > 0) {
      //$_SESSION['distance_guess'] = $_POST['pulsar_distance'];
      $data->set_distance_guess($_POST['pulsar_distance']);
      $_SESSION['valid_guess'] = TRUE;
      //header('Location: ' . HTTP_ADDRESS . NEXT_PAGE_FILENAME);

      header('Location: ' . HTTP_ADDRESS . NEXT_PAGE_FILENAME . '?id=' . $_GET['id']);

    } else {
      global $distance_error;
      $distance_error = TRUE;
      $_SESSION['valid_guess'] = FALSE;
      header('Location: ' . HTTP_ADDRESS . 'index.php#error');
    }
  }
}

?>
