<?php

require_once('Config.php');
require_once(ROOT_DIRECTORY . 'Classes/Form.php');
require_once(ROOT_DIRECTORY . 'Classes/DataValidation.php');

define(TEMPLATE_FILE, TEMPLATE_DIRECTORY . 'guess_distance.html');
define(NEXT_PAGE_FILENAME, 'freq_time_information.php');
define(NEXT_PAGE_TEXT, 'frequency-vs-time plot information');
define(TITLE, 'Guess the Distance');

define(DISTANCE_ERROR_MESSAGE, '<p><font color=red>Error: Invalid guess</font>');

try {
  $id = $_GET['id'];
  $data = new ModuleDataPartTwo($id);

  $distance_error = FALSE;

  ProcessFormData($data);

  $content = Form::GetPageHeader();
  $content .= Form::LoadBodyTemplate(TEMPLATE_FILE);
  $content .= Form::GetPageFooter();

  $psr = $data->get_pulsar_name();

  Form::ReplaceText('[@TITLE]', TITLE, $content);
  Form::ReplaceText('[@PULSAR_NAME]', $psr, $content);

  if ($_SESSION['valid_guess'] == TRUE) {
    Form::ReplaceText('[@DISTANCE_ERROR_MESSAGE]', '', $content);
  } else {
    Form::ReplaceText('[@DISTANCE_ERROR_MESSAGE]', DISTANCE_ERROR_MESSAGE, $content);
  }

  echo $content;
} catch (Exception $e) {
  echo 'Error: ', $e->getMessage();
}

function ProcessFormData(&$data)
{
  if (!isset($_SESSION['valid_guess'])) {
    $_SESSION['valid_guess'] = TRUE;
  }

  if (isset($_POST['pulsar_distance'])) {
    $distance_guess = DataValidation::removeXSS($_POST['pulsar_distance']);

    //echo "dist guess: $distance_guess";

    if (is_numeric($distance_guess) && $distance_guess > 0) {
      //$_SESSION['distance_guess'] = $distance_guess;
      $data->set_distance_guess($distance_guess);
      $_SESSION['valid_guess'] = TRUE;
      //header('Location: ' . HTTP_ADDRESS . NEXT_PAGE_FILENAME);
      header('Location: ' . HTTP_ADDRESS . NEXT_PAGE_FILENAME . '?id=' . $data->get_id());
    } else {
      global $distance_error;
      $distance_error = TRUE;
      $_SESSION['valid_guess'] = FALSE;
    }
  }
}

function RemoveSessionData()
{
  if ($_SERVER['HTTP_REFERER'] != "http://pulseatparkes.atnf.csiro.au/distance_final/") {
    unset($_SESSION['distance_guess']);
  }
}

?>
