<?php

require_once('Config.php');
require_once(ROOT_DIRECTORY . 'Classes/Form.php');
require_once(ROOT_DIRECTORY . 'Classes/DataValidation.php');
require_once(ROOT_DIRECTORY . 'Classes/Mailer.php');

define(TEMPLATE_FILE, TEMPLATE_DIRECTORY . 'plot_0437.html');
define(NEXT_PAGE_FILENAME, 'values_information.php');
define(NEXT_PAGE_TEXT, 'x-y values information');
define(TITLE, 'PSR J0437-4715');
define(PLOT_BUTTON_HTML, '<p><input type="submit" name="plot" value="Plot entered values">');


try {
  $id = $_GET['id'];
  $data = new ModuleData($id);

  $content = Form::GetPageHeader();
  $content .= Form::LoadBodyTemplate(TEMPLATE_FILE);
  $content .= Form::GetPageFooter();

  ProcessFormData($data);

  $entry_error = NULL;
  $plot_error = NULL;
  MakePlot($plot_error, $data);

  // XXX
  $table = MakeTable($data->get_frequencies(), $data->get_times());

  Form::ReplaceText('[@table]', $table, $content);

  Form::ReplaceText('[@TITLE]', TITLE, $content);

  $plot_path = 'sessions/' . $data->get_id() . '.png';

  Form::ReplaceFileText('[@IMAGE_PATH]', $plot_path, $content);

  // replace errors text (blank if no error returned)
  Form::ReplaceText('[@ZOOM_ERROR]', $plot_error, $content);
  Form::ReplaceText('[@FREQ_TIME_ERROR]', $entry_error, $content);

  if (sizeof($data->get_frequencies()) > 1) {
    Form::ReplaceText('[@PLOT_BUTTON]', PLOT_BUTTON_HTML, $content);
  } else {
    Form::ReplaceText('[@PLOT_BUTTON]', '', $content);
  }

  echo $content;
} catch (Exception $e) {
  echo 'Error: ', $e->getMessage();
}

// XXX: redo
function MakeTable($frequencies, $times)
{
  if (!empty($frequencies)) {
    $table = "<table class=\"gridtable\">" .
      "<tr><th><b>Frequency (MHz)</b></th><th><b>Time (s)</b></th></tr>";

    for ($i = 0; $i < sizeof($frequencies); $i++) {
      $table .= "<tr><td align=center>" . $frequencies[$i] .
        "</td><td align=center>" . $times[$i] .
        "</td><td>" .
        '<input type=image src="../images/remove.png" width="20" value="Remove" name="clear[' . $i .
        ']"/></td></tr>';
    }

    $table .= "</table>";

    return $table;
  }
}

function ProcessFormData(&$data)
{
  // if zoom has been defined, store the start and end values
  if (isset($_POST['zoom_start']) && isset($_POST['zoom_end'])) {
    if (strlen($_POST['zoom_start']) > 0 && strlen($_POST['zoom_end']) > 0) {
      $zoom_start = $_POST['zoom_start'];
      $zoom_end = $_POST['zoom_end'];

      if (is_numeric($zoom_start) && is_numeric($zoom_end)) {
        $data->set_zoom_start(DataValidation::removeXSS($zoom_start));
        $data->set_zoom_end(DataValidation::removeXSS($zoom_end));
      }
    }
  }

  if (isset($_POST['reset_zoom'])) {
    $data->set_zoom_start(0);
    $data->set_zoom_end(0);
  }

  if (isset($_POST['enter']) || isset($_POST['zoom'])) {
    if (strlen($_POST['freq']) > 0 && strlen($_POST['time']) > 0) {
      validate_freq_and_time($_POST['freq'], $_POST['time'], $data);
    }
  }

  if (isset($_POST['plot'])) {
    $id = $data->get_id();
    header('Location: ' . HTTP_ADDRESS . NEXT_PAGE_FILENAME . '?id=' . $id);
  }

  if (isset($_POST['clear'])) {
    $clear_index = array_search('Remove', $_POST['clear']);

    $old_frequencies = $data->get_frequencies();
    $old_times = $data->get_times();

    for ($i = 0; $i < sizeof($old_frequencies); $i++) {
      if ($i != $clear_index) {
        $new_frequencies[] = $old_frequencies[$i];
        $new_times[] = $old_times[$i];
      }
    }

    $data->set_frequencies($new_frequencies, FALSE);
    $data->set_times($new_times, FALSE);
  }
}

function validate_freq_and_time($frequency, $time, &$data)
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

  if (strlen($time) > 10) {
    $entry_error = 'time entered is too long';
    return;
  }

  if (!$data->time_exists($time) && !$data->frequency_exists($frequency)) { 
    $data->add_time($time);
    $data->add_frequency($frequency);
  }
}

//function MakePlot(&$error)
function MakePlot(&$error, $data)
{
  $cmd = '../../runplot.csh "';
  $cmd .= '-f /nfs/wwwresearch/pulsar/pulseATpks/J0437-4715.22.1.8channels.txt';

  //$image_path = 'sessions/' . session_id() . '.png';
  $image_path = 'sessions/' . $data->get_id() . '.png';

  $cmd .= ' -out ' . $image_path . '/png';
  $cmd .= ' -rot 600 -p J0437-4715 ';

  $zoom_start = $data->get_zoom_start();
  $zoom_end = $data->get_zoom_end();

  if (isset($zoom_start) && isset($zoom_end)) {
    if ($zoom_start > 0 && $zoom_end > 0) {
      $cmd .= ' -zooml ' . $zoom_start;
      $cmd .= ' -zoomr ' . $zoom_end;
    }
  }

  $cmd .= '" '." ";

  exec($cmd, $out);
  $error = $out[0];
}

?>
