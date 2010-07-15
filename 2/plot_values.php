<?php

require_once('Config.php');
require_once(ROOT_DIRECTORY . 'Classes/Form.php');

define(TEMPLATE_FILE, TEMPLATE_DIRECTORY . 'plot_values.html');
define(NEXT_PAGE_FILENAME, 'galaxy_plot.php');
define(NEXT_PAGE_TEXT, 'Continue');
define(TITLE, 'x-y Values Plot');

define(PSRCAT_CMD, '/pulsar/psr/linux/bin/psrcat -c "dm gl gb" [@PULSAR_NAME] -nohead -nonumber -o short | awk ' . "'{print $1, $2, $3}'");

class PlotType
{
  const FrequencyTime = 0;
  const DmTime = 1;
};

try {
  $id = $_GET['id'];
  $data = new ModuleDataPartTwo($id);

  ProcessFormData($data);

  GetPsrcatValues($dm, $glat, $glong, $data);

  $content = Form::GetPageHeader();
  $content .= Form::LoadBodyTemplate(TEMPLATE_FILE);
  $content .= Form::GetPageFooter();

  $table = MakeTable($data->get_frequencies(), $data->get_times());
  Form::ReplaceText('[@TABLE]', $table, $content);

  MakeGraph($data->get_frequencies(), $data->get_times(), $user_dm, $data);

  //if (!isset($_SESSION['user_dm'])) {
    // only store the dm returned from the automatic fit if the user has not
    // entered a custom value

    //$_SESSION['user_dm'] = $user_dm;
    $data->set_user_dm($user_dm);
  //}

  $image_path = 'sessions/' . $data->get_id() . '_freqtime.png';
  Form::ReplaceText('[@IMAGE_PATH]', $image_path, $content);

  $line_information = 'DM: ' . $user_dm;

  Form::ReplaceText('[@TITLE]', TITLE, $content);

  Form::ReplaceText('[@DM_PSRCAT]', $data->get_dm(), $content);
  Form::ReplaceText('[@DM_USER]', $data->get_user_dm(), $content);

  $dm_difference = $data->get_dm() - $user_dm;
  Form::ReplaceText('[@DM_DIFFERENCE]', $dm_difference, $content);

  switch ($data->get_plot_type()) {
  case PlotType::FrequencyTime:
    Form::ReplaceText('[@TIME_FREQ_CHECKED_VALUE]', 'checked', $content);
    Form::ReplaceText('[@DM_CHECKED_VALUE]', '', $content);
    break;
  case PlotType::DmTime:
    Form::ReplaceText('[@DM_CHECKED_VALUE]', 'checked', $content);
    Form::ReplaceText('[@TIME_FREQ_CHECKED_VALUE]', '', $content);
    break;
  }

  Form::ReplaceText('[@DM]', $data->get_user_dm(), $content);

  echo $content;
} catch (Exception $e) {
  echo 'Error: ', $e->getMessage();
}

function GetPsrcatValues(&$dm, &$glat, &$glong, &$data)
{
  // XXX
  $cmd = PSRCAT_CMD;
  $cmd = str_replace('[@PULSAR_NAME]', $data->get_pulsar_name(), $cmd);

  exec($cmd, $out);

  list($dm, $elong, $elat) = explode(' ', $out[0]);
  $data->set_dm($dm);
  $data->set_elong($elong);
  $data->set_elat($elat);
}

function ProcessFormData(&$data)
{
  if (isset($_POST['custom_dm'])) {
    // if the submit button for custom dm has been pressed
    if (sizeof($_POST['dm']) > 0) { // and if a value has been entered
      // validate +ve number
      $data->set_user_dm($_POST['dm']);
      $data->set_dm($_POST['dm']);

      header('Location: ' . HTTP_ADDRESS . NEXT_PAGE_FILENAME . '?id=' . $data->get_id());
    }
  }

  if (isset($_POST['fit_line'])) {
    $data->set_fit_line(1);
  }

  if (isset($_POST['dm'])) {
    $data->set_plot_type(PlotType::DmTime);
  } else if (isset($_POST['time_freq'])) {
    $data->set_plot_type(PlotType::FrequencyTime);
  }
}

function MakeGraph($x, $y, &$gradient, &$data)
{
  $cmd .= '../../runplotgraph.csh "';

  $count = sizeof($x);
  for ($i = 0; $i < $count; $i++) {
    $cmd .= ' -d ' .  $x[$i] . ' ' . $y[$i];
  }

  $image_path =  'sessions/' . $data->get_id() . '_freqtime.png';
  $cmd .= ' -out ' . $image_path . '/png';

  switch ($data->get_plot_type()) {
  case PlotType::FrequencyTime:
    $cmd .= ' -axis 1';
    break;
  case PlotType::DmTime:
    $cmd .= ' -axis 2';
    break;
  }

  if ($data->get_fit_line() == 1) {
    $cmd .= ' -fit';
  }

  $cmd .= '"';

  exec($cmd, $out);

  if ($data->get_fit_line() == 1) {
    $gradient = $out[0];
    $data->set_user_dm($gradient);
  }
}

function MakeTable($frequencies, $times)
{
  $table = "<table cellspacing=3>" .
    "<tr><td><b>Frequency (MHz)</b></td><td><b>Time (s)</b></td></tr>";

  for ($i = 0; $i < sizeof($frequencies); $i++) {
    $table .= "<tr><td align=center>" .
      $frequencies[$i] .
      "</td><td align=center>" .
      $times[$i] .
      "</td></tr>";
  }

  $table .= "</table>";

  return $table;
}


?>
