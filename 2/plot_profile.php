<?

require_once('Config.php');
require_once(ROOT_DIRECTORY . 'Classes/Form.php');
require_once(ROOT_DIRECTORY . 'Classes/DataValidation.php');
require_once(ROOT_DIRECTORY . 'Classes/Mailer.php');

define(TEMPLATE_FILE, TEMPLATE_DIRECTORY . 'plot_profile.html');
define(NEXT_PAGE_FILENAME, 'values_information.php');
define(NEXT_PAGE_TEXT, 'x-y values information');
define(TITLE, 'Frequency vs Time');
define(PLOT_BUTTON_HTML, '<p><input type="submit" name="plot" value="Plot">');

try {
  $id = $_GET['id'];
  $data = new ModuleDataPartTwo($id);

  RemoveSessionData();

  $content = Form::GetPageHeader();
  $content .= Form::LoadBodyTemplate(TEMPLATE_FILE);
  $content .= Form::GetPageFooter();

  ProcessFormData($data);

  $frequencies = GetFrequencies($data->get_observation_file());
  $options = FrequenciesAsOptions($frequencies, $data);

  Form::ReplaceText('[@FREQUENCIES]', $options, $content);

  $entry_error = NULL;
  $plot_error = NULL;
  MakePlot($plot_error, $frequencies, $data);

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

  Form::ReplaceText('[@PULSAR_NAME]', $data->get_pulsar_name(), $content);

  echo $content;
} catch (Exception $e) {
  echo 'Error: ', $e->getMessage();
}

function MakeTable($frequencies, $times)
{
  $table = "<table cellspacing=3>" .
    "<tr><td><b>Frequency (MHz)</b></td><td><b>Time (s)</b></td></tr>";

  for ($i = 0; $i < sizeof($frequencies); $i++) {
    $table .= "<tr><td align=center>" . $frequencies[$i] .
      "</td><td align=center>" . $times[$i] .
      "</td><td>" .
      '<input type=submit value="Remove" name="clear[' . $i .
      ']"/></td></tr>';
  }

  $table .= "</table>";

  return $table;
}

function RemoveSessionData()
{
  unset($_SESSION['fit_line']);
  unset($_SESSION['plot_type']);
}

function ProcessFormData(&$data)
{
  // order of precedence:
  // 1. freq and time entered
  // 2. zoom
  // 3. single frequency selected
  // 4. rotate left/right

  if (sizeof($data->get_frequency()) == 0) {
    $data->set_frequency('ALL');
  }

  // 1. freq and time entered
  if (strlen($_POST['freq']) > 0 && strlen($_POST['time']) > 0) {
    validate_freq_and_time($_POST['freq'], $_POST['time'], $data);
    return;
  }

  // 2. zoom
  // if zoom has been defined, store the start and end values
  if (isset($_POST['zoom_start']) && isset($_POST['zoom_end'])) {
    if (strlen($_POST['zoom_start']) > 0 && strlen($_POST['zoom_end']) > 0) {
      $zoom_start = $_POST['zoom_start'];
      $zoom_end = $_POST['zoom_end'];

      if (is_numeric($zoom_start) && is_numeric($zoom_end)) {
        $data->set_zoom_start(DataValidation::removeXSS($zoom_start));
        $data->set_zoom_end(DataValidation::removeXSS($zoom_end));
      }

      return;
    }
  }

  if (isset($_POST['reset_zoom'])) {
    $data->set_zoom_start(0);
    $data->set_zoom_end(0);
  }

  if (isset($_POST['show_freq'])) {
    $data->set_frequency($_POST['frequency']);
  }

  if (isset($_POST['shift_left'])) {
    $new_rotate_value = $data->get_rotate() - 200;
    $data->set_rotate($new_rotate_value);
  }

  if (isset($_POST['shift_right'])) {
    $new_rotate_value = $data->get_rotate() + 200;
    $data->set_rotate($new_rotate_value);
    header('Location: ' . 'http://pulseatparkes.atnf.csiro.au/distance/2/plot_profile.php?id=' . $data->get_id());
  }

  if (isset($_POST['plot'])) {
    header('Location: ' . HTTP_ADDRESS . NEXT_PAGE_FILENAME . '?id=' . $data->get_id());
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
  } else {
    $entry_error = 'frequency or time entered already exists';
  }
}

function MakePlot(&$error, $frequencies, &$data)
{
  $cmd = '../../runplot.csh " -f ';
  $cmd .= $data->get_observation_file();

  $image_path = 'sessions/' . $data->get_id() . '.png';

  $cmd .= ' -out ' . $image_path . '/png';
  $cmd .= ' -p ' . $data->get_pulsar_name();

  $zoom_start = $data->get_zoom_start();
  $zoom_end = $data->get_zoom_end();

  if (isset($zoom_start) && isset($zoom_end)) {
    if ($zoom_start > 0 && $zoom_end > 0) {
      $cmd .= ' -zooml ' . $zoom_start;
      $cmd .= ' -zoomr ' . $zoom_end;
    }
  }

  if ($data->get_frequency() !== 'ALL') {
    $key = array_search($data->get_frequency(), $frequencies);
    $key++;

    $cmd .= " -sel $key";
  }

  $cmd .= ' -rot ' . $data->get_rotate();
  $cmd .= '"';

  exec($cmd, $out);
  $error = $out[0];
}

function GetFrequencies($file)
{
  $contents = trim(file_get_contents($file, 'r'));

  $lines = split("\n", $contents);
  $firstLine = $lines[0];

  list($null, $f[0], $f[1], $f[2], $f[3], $f[4], $f[5], $f[6], $f[7]) = 
    explode(',', $firstLine);

  ksort($f);

  return $f;
}

function FrequenciesAsOptions($frequencies, &$data)
{
  $options = '<option ';                                                           

  if ($_SESSION['frequency'] == 'ALL') {                                           
    $options .= 'selected';                                                      
  }

  $options .= 'name="all" onChange="submit();">ALL</option>';                      

  foreach ($frequencies as $f) {
    $options .= '<option ';                                                      

    if ($f == $data->get_frequency()) {                                          
      $options .= 'selected';                                                  
    }                                                                            

    $options .= ' name="' . $f . '" onChange="submit();">' . $f . '</option>';   
  }                                                                                

  return $options;
}


?>
