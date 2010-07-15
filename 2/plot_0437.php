<?php

require_once('Classes/Form.php');
require_once('Classes/DataValidation.php');

define(TEMPLATE_FILE, TEMPLATE_DIRECTORY . 'plot_0437.html');
define(NEXT_PAGE_FILENAME, 'values_information.php');
define(NEXT_PAGE_TEXT, 'x-y values information');
define(TITLE, 'Frequency-Vs-Time Plot:J0437-4715 ');

session_start();

print_r($_SESSION);

try {
  $content = Form::GetPageHeader();
  $content .= Form::LoadBodyTemplate(TEMPLATE_FILE);
  $content .= Form::GetPageFooter();

  $entry_error = NULL;
  $plot_error = NULL;

  ProcessFormData();
  MakePlot($plot_error);

  $table = MakeTable($_SESSION['frequencies'], $_SESSION['times']);
  Form::ReplaceText('[@table]', $table, $content);

  Form::ReplaceNextPageLink(NEXT_PAGE_FILENAME, $content);
  Form::ReplaceText('[@NEXT_PAGE_TEXT]', NEXT_PAGE_TEXT, $content);
  Form::ReplaceText('[@TITLE]', TITLE, $content);

  // insert plot
  $plot_path = 'sessions/' . session_id() . '.png';
  Form::ReplaceFileText('[@IMAGE_PATH]', $plot_path, $content);

  // replace errors text (blank if no error returned)
  Form::ReplaceText('[@ZOOM_ERROR]', $plot_error, $content);
  Form::ReplaceText('[@FREQ_TIME_ERROR]', $entry_error, $content);

  echo $content;
} catch (Exception $e) {
  echo 'Error: ', $e->getMessage();
}

// XXX: redo
function MakeTable($frequencies, $times)
{
  $table = "<table cellspacing=3>" .
    "<tr><td><b>Frequency (MHz)</b></td><td><b>Time (s)</b></td></tr>";

  for ($i = 0; $i < sizeof($frequencies); $i++) {
    $table .= "<tr><td align=center>" . $frequencies[$i] .
      "</td><td align=center>" . $times[$i] .
      "</td><td>" .
      '<input type=submit value="clear" name="clear[' . $i .
      ']"/></td></tr>';
  }

  $table .= "</table>";

  return $table;
}

function ProcessFormData()
{
  // if zoom has been defined, store the start and end values
  if (isset($_POST['zoom_start']) && isset($_POST['zoom_end'])) {
    $_SESSION['zoom_start'] = $_POST['zoom_start'];
    $_SESSION['zoom_end'] = $_POST['zoom_end'];
  }

  if (isset($_POST['reset_zoom'])) {
    unset($_SESSION['zoom_start']);
    unset($_SESSION['zoom_end']);
  }

  if (isset($_POST['enter'])) {
    if (strlen($_POST['freq']) > 0 && strlen($_POST['time']) > 0) {
      validate_freq_and_time($_POST['freq'], $_POST['time']);
    }
  }
}

function validate_freq_and_time($frequency, $time)
{
  $frequency = DataValidation::removeXSS($frequency);
  $time = DataValidation::removeXSS($time);

  global $entry_error;

  if (!is_numeric($frequency)) {
    $entry_error = 'frequency entered is not a number';
    return;
  }

  if (strlen($frequency) > 8) {
    $entry_error = 'frequency entered is too long';
    return;
  }

  if (!is_numeric($time)) {
    $entry_error = 'time entered is not a number';
    return;
  }

  if (strlen($time) > 6) {
    $entry_error = 'time entered is too long';
    return;
  }

  if (sizeof($_SESSION['freqs'] > 1)) {
    if (@array_search($freq, $_SESSION['frequencies']) === false &&
      @array_search($time, $_SESSION['times']) === false) {

        $_SESSION['frequencies'][] = $frequency;
        $_SESSION['times'][] = $time;
      } else {
        $entry_error = 'frequency or time entered already exists';
      }
  }
}

function MakePlot(&$error)
{
  $cmd = '../../runplot.csh "';
  $cmd .= '-f /nfs/wwwresearch/pulsar/pulseATpks/J0437-4715.22.1.8channels.txt';

  $image_path = 'sessions/' . session_id() . '.png';

  $cmd .= ' -out ' . $image_path . '/png';
  $cmd .= ' -rot 600';

  if (isset($_SESSION['zoom_start']) && isset($_SESSION['zoom_end'])) {
    if (strlen($_SESSION['zoom_start']) > 0 && strlen($_SESSION['zoom_end']) > 0) {
      $cmd .= ' -zooml ' . $_SESSION['zoom_start'];
      $cmd .= ' -zoomr ' . $_SESSION['zoom_end'];
    }
  }

  $cmd .= '"';
  exec($cmd, $out);
  $error = $out[0];
}
?>
