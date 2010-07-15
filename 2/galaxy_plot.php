<?php

require_once('Config.php');
require_once(ROOT_DIRECTORY . 'Classes/Form.php');
require_once(ROOT_DIRECTORY . 'Classes/Mailer.php');

define(TEMPLATE_FILE, TEMPLATE_DIRECTORY . 'galaxy_plot.html');
define(TITLE, 'Galaxy Plot');

define(NE2001_DIRECTORY, $CONF['path_html'] . '../../../apps/NE2001/bin.NE2001');
define(GNUPLOT_SCRIPT_FILE, '../GNUPlotScript.txt');
define(GNUPLOT_CMD, 'gnuplot @SCRIPT_FILE');
define(IMAGEMAGICK_CMD, 'composite -compose darken ../FinalGalaxy.jpg @IN_PNG @OUT_JPG');
define(KPC_TO_LY, 3261.63626); //1 kpc = 3261.63626 light years

define(NE2001_CMD, './NE2001 [@ELONG] [@ELAT] [@DM] 1 | grep ModelDistance');
define(PSRCAT_CMD, '/pulsar/psr/linux/bin/psrcat -nohead -nonumber -c "rajd decjd" [@PULSAR_NAME] | awk \'{print $1, $2}\''); // J0437-4715
define(PLOTSKY_CMD, './runplotsky.csh "-c [@CENTRE_RAJD] [@CENTRE_DECJD] [@PULSAR_RAJD_AND_DECJD] -f [@FOV] -g [@PROJECTION] -d [@CONSTELLATION_NAMES] -k sessions/[@SESSION_ID]_const.png/png"');
define(GALAXYPLOT_CMD, './galaxyPlot "[@NAME]" "[@GROUP]" "[@ARM_NAMES]" [@PULSAR_NAME_AND_DISTANCES]');
define(HIDDEN_FORM_HTML, '<form action="" method="POST" id="[@INDEX]" style="display: none"><input type="text" name="[@INDEX]"/></form>');

define(SINGLE_PROCESSED_TEXT, 'circle indicates the position of ');
define(MULTIPLE_PROCESSED_TEXT, 'circles indicate the positions of ');

define(TEXT, 'The [@PROCESSED_TEXT] [@PULSARS] using your dispersion measure determination');

class ProjectionType
{
  const Rectangular = 1;
  const Aitoff      = 2;
};

class ConstellationNames
{
  const Off = 0;
  const On  = 1;
};

class ArmNames
{
  const Off = 0;
  const On  = 1;
};

try {
  $id = $_GET['id'];
  $data = new ModuleDataPartTwo($id);

  ProcessFormData($data);

  $content = Form::GetPageHeader();
  $content .= Form::LoadBodyTemplate(TEMPLATE_FILE);
  $content .= Form::GetPageFooter();

  $guess = $data->get_distance_guess();
  $user_dm = $data->get_user_dm();

  $pulsar_distance = GetPulsarDistance($user_dm, $data);
  $pulsar_name = $data->get_pulsar_name();

  $pulsar_distance_ly = round($pulsar_distance * KPC_TO_LY, 0);
  Form::ReplaceText('[@DISTANCE_IN_LY]', $pulsar_distance_ly, $content);
  //Form::ReplaceText('[@PULSAR_NAME]', $pulsar_name, $content);

  CreateGalaxyPlot($pulsar_distance_ly, $data);

  Form::ReplaceText('[@TITLE]', TITLE, $content);
  Form::ReplaceText('[@GUESS]', $guess, $content);

  $jpg = 'sessions/' . $data->get_id() . '_final.jpg';
  Form::ReplaceText('[@PULSAR_LOCATION_IMAGE]', $jpg, $content);

  $cmd = PLOTSKY_CMD;
  // XXX

  //$centre_rajd = $_SESSION['processed_pulsars']['rajd'][$_SESSION['centred_pulsar']];
  //$centre_decjd = $_SESSION['processed_pulsars']['decjd'][$_SESSION['centred_pulsar']];

  $cmd = str_replace('[@CENTRE_RAJD]', $data->get_rajd(), $cmd);
  $cmd = str_replace('[@CENTRE_DECJD]', $data->get_decjd(), $cmd);

  $count = sizeof($_SESSION['processed_pulsars']['name']);

  $rajd_and_decjd = ' -p ' . $data->get_rajd() . ' ' . $data->get_decjd();

  /*for ($i = 0; $i < $count; $i++) {
    $rajd_and_decjd .= ' -p ' .
      $_SESSION['processed_pulsars']['rajd'][$i] . ' ' .
      $_SESSION['processed_pulsars']['decjd'][$i];
  }*/

  for ($i = 0; $i < $count; $i++) {
    $form = HIDDEN_FORM_HTML;
    $form = str_replace('[@INDEX]', $i, $form);

    $forms .= $form;
  }

  Form::ReplaceText('[@HIDDEN_FORMS]', $forms, $content);

  $cmd = str_replace('[@PULSAR_RAJD_AND_DECJD]', $rajd_and_decjd, $cmd);
  $cmd = str_replace('[@SESSION_ID]', $data->get_id(), $cmd);

  $cmd = str_replace('[@FOV]', $data->get_fov(), $cmd);
  $cmd = str_replace('[@PROJECTION]', $data->get_projection(), $cmd);
  $cmd = str_replace('[@CONSTELLATION_NAMES]', $data->get_names(), $cmd);

  exec($cmd, $out);

  $const_image = 'sessions/' . $data->get_id() . '_const.png';
  Form::ReplaceText('[@CONST_IMAGE]', $const_image, $content);

  $processed_table = MakeProcessedTable();
  Form::ReplaceText('[@PROCESSED_TABLE_CONTENT]', $processed_table, $content);

  switch ($data->get_fov()) {
  case 0:
    Form::ReplaceText('[@F0V_0_CHECKED]', 'checked', $content);
    Form::ReplaceText('[@F0V_40_CHECKED]', '', $content);
    Form::ReplaceText('[@F0V_80_CHECKED]', '', $content);
    break;
  case 40:
    Form::ReplaceText('[@F0V_0_CHECKED]', '', $content);
    Form::ReplaceText('[@F0V_40_CHECKED]', 'checked', $content);
    Form::ReplaceText('[@F0V_80_CHECKED]', '', $content);
    break;
  case 80:
    Form::ReplaceText('[@F0V_0_CHECKED]', '', $content);
    Form::ReplaceText('[@F0V_40_CHECKED]', '', $content);
    Form::ReplaceText('[@F0V_80_CHECKED]', 'checked', $content);
    break;
  }

  switch ($data->get_projection()) {
  case ProjectionType::Rectangular:
    Form::ReplaceText('[@AITOFF_CHECKED]', '', $content);
    Form::ReplaceText('[@RECTANGULAR_CHECKED]', 'checked', $content);
    break;
  case ProjectionType::Aitoff:
    Form::ReplaceText('[@AITOFF_CHECKED]', 'checked', $content);
    Form::ReplaceText('[@RECTANGULAR_CHECKED]', '', $content);
    break;
  }

  switch ($data->get_names()) {
  case ConstellationNames::On:
    Form::ReplaceText('[@NAMES_ON_CHECKED]', 'checked', $content);
    Form::ReplaceText('[@NAMES_OFF_CHECKED]', '', $content);
    break;
  case ConstellationNames::Off:
    Form::ReplaceText('[@NAMES_ON_CHECKED]', '', $content);
    Form::ReplaceText('[@NAMES_OFF_CHECKED]', 'checked', $content);
    break;
  }

  switch ($data->get_arm_names()) {
  case ArmNames::On:
    Form::ReplaceText('[@ARM_NAMES_ON_CHECKED]', 'checked', $content);
    Form::ReplaceText('[@ARM_NAMES_OFF_CHECKED]', '', $content);
    break;
  case ArmNames::Off:
    Form::ReplaceText('[@ARM_NAMES_ON_CHECKED]', '', $content);
    Form::ReplaceText('[@ARM_NAMES_OFF_CHECKED]', 'checked', $content);
    break;
  }

  $text = TEXT;
  if (sizeof($_SESSION['processed_pulsars']['name']) > 1) {
    $text = str_replace('[@PROCESSED_TEXT]', MULTIPLE_PROCESSED_TEXT, $text);
  } else {
    $text = str_replace('[@PROCESSED_TEXT]', SINGLE_PROCESSED_TEXT, $text);
  }

  foreach ($_SESSION['processed_pulsars']['name'] as $n) {
    $names .= " $n,";
  }

  $text = str_replace('[@PULSARS]', $names, $text);
  Form::ReplaceText('[@PROCESSED_TEXT]', $text, $content);

  echo $content;
} catch (Exception $e) {
  echo 'Error: ', $e->getMessage();
}

function ProcessFormData(&$data)
{
  $jump_to_constellation_plot = FALSE;

  if (isset($_POST['display_toggle'])) {
    $index_to_toggle = $_POST['display_toggle'];
    $_SESSION['processed_pulsars']['display'][$index_to_toggle] =
       !$_SESSION['processed_pulsars']['display'][$index_to_toggle];
  }

  $cmd = PSRCAT_CMD;
  $cmd = str_replace('[@PULSAR_NAME]', $data->get_pulsar_name(), $cmd);

  exec($cmd, $out);

  // XXX
  //list($_SESSION['rajd'], $_SESSION['decjd']) = explode(' ', $out[0]);
  list($rajd, $decjd) = explode(' ', $out[0]);
  //echo "rajd: " . $_SESSION['rajd'] . " decjd: " . $_SESSION['decjd'] . "<br>";

  $data->set_rajd($rajd);
  $data->set_decjd($decjd);

  if (isset($_POST['arm_names_on'])) {
    $data->set_arm_names(ArmNames::On);
  }

  if (isset($_POST['arm_names_off'])) {
    $data->set_arm_names(ArmNames::Off);
  }

  if (isset($_POST['fov_0'])) {
    $data->set_fov(0);
    $jump_to_constellation_plot = TRUE;
  }

  if (isset($_POST['fov_40'])) {
    $data->set_fov(40);
    $jump_to_constellation_plot = TRUE;
  }

  if (isset($_POST['fov_80'])) {
    $data->set_fov(80);
    $jump_to_constellation_plot = TRUE;
  }


  if (isset($_POST['aitoff'])) {
    $data->set_projection(ProjectionType::Aitoff);
    $jump_to_constellation_plot = TRUE;
  }

  if (isset($_POST['rectangular'])) {
    $data->set_projection(ProjectionType::Rectangular);
    $jump_to_constellation_plot = TRUE;
  }

  if (isset($_POST['names_on'])) {
    $data->set_names(ConstellationNames::On);
    $jump_to_constellation_plot = TRUE;
  }

  if (isset($_POST['names_off'])) {
    $data->set_names(ConstellationNames::Off);
    $jump_to_constellation_plot = TRUE;
  }

    if ($jump_to_constellation_plot === TRUE) {
      header('Location: ' . HTTP_ADDRESS . 'galaxy_plot.php?id=' . $data->get_id() . '#constellation_plot');
    }


  if (isset($_POST['arm_names_on'])) {
    $data->set_arm_names(ArmNames::On);
  }

  if (isset($_POST['arm_names_off'])) {
    $data->set_arm_names(ArmNames::Off);
  }

    if (isset($_POST['centre'])) {
      $centre_index = array_search('centre', $_POST['centre']);
      echo "centre: $centre_index <br>";

      $_SESSION['centred_pulsar'] = $centre_index;
    }

    $count = sizeof($_SESSION['processed_pulsars']);
    for ($i = 0; $i < $count; $i++) {
      if (isset($_POST[$i])) {
        $_SESSION['centred_pulsar'] = $i;
      }
    }
}

function MakeProcessedTable()
{

  if (!isset($_SESSION['centred_pulsar'])) {
    // set the initial centred pulsar to be the first one
    $_SESSION['centred_pulsar'] = 0;
  }

  $count = sizeof($_SESSION['processed_pulsars']['name']);

  for ($i = 0; $i < $count; $i++) {
    $table .= '<tr>';

    if ($i == $_SESSION['centred_pulsar']) {
     // $table .= '<input type=radio name="name" value="value" checked/>';
      $table .= "<td><input type=radio name=names_on onClick=\"javascript: document.getElementById('$i').submit();\" checked/>";
    } else {
      $table .= "<td><input type=radio name=names_on onClick=\"javascript: document.getElementById('$i').submit();\"/>";
      //$table .= '<input type=radio name="name" value="value"/>';
    }

    $table .= $_SESSION['processed_pulsars']['name'][$i];

    //$table .= '</td><td><input type=submit value="centre" name="centre['.$i.']"';

    $table .= '</td></tr>';
  }

  return $table;
}

function MakePositionPlot($x, $y, $pulsar_name, &$data)
{
  // save name, x, y, as session

  // loop for each completed pulsar
  // XXX: check for same size???

  if (!@in_array($pulsar_name, $_SESSION['processed_pulsars']['name'])) {
    // prevent multiple entries of the same pulsar to be stored
    $_SESSION['processed_pulsars']['name'][] = $pulsar_name;
    $_SESSION['processed_pulsars']['rajd'][] = $_SESSION['rajd'];
    $_SESSION['processed_pulsars']['decjd'][] = $_SESSION['decjd'];
    //$_SESSION['processed_pulsars']['display'][] = 1;
  }

  // sessions/<session id>_position.dat
  $position_filename = 'sessions/' . $data->get_id() . '_position.dat';
  $position_file = fopen($position_filename, 'w+');

  for ($i = 0; $i < sizeof($_SESSION['processed_pulsars']['name']); $i++) {
    if ($_SESSION['processed_pulsars']['display'][$i] == 1) {
      $x = $_SESSION['processed_pulsars']['x'][$i];
      $y = $_SESSION['processed_pulsars']['y'][$i];

      fwrite($position_file, "$x $y \n");
    }
  }

  fclose($position_file);

  $script_filename = 'sessions/' . $data->get_id() . '_gnuplot_script.txt';

  copy(GNUPLOT_SCRIPT_FILE, 'sessions/' . $data->get_id() . '_gnuplot_script.txt');
  $contents = file_get_contents($script_filename, 'r');
  $contents = str_replace('output.png', 'sessions/' . $data->get_id() .
    '_final.png', $contents);

  $scriptFile = fopen($script_filename, 'w+');

  fwrite($scriptFile, $contents);

  for ($i = 0; $i < sizeof($_SESSION['processed_pulsars']['name']); $i++) {
    if ($_SESSION['processed_pulsars']['display'][$i] == 1) {
      $name = $_SESSION['processed_pulsars']['name'][$i];
      $x = $_SESSION['processed_pulsars']['x'][$i];
      $y = $_SESSION['processed_pulsars']['y'][$i];

      $x += 0.5;

      fwrite($scriptFile, "set label \"$name\" at $x,$y\n");
    }
  }

  fwrite($scriptFile, "plot \"$position_filename\"\n");
  fclose($scriptFile);

  $cmd = str_replace('@SCRIPT_FILE', $script_filename, GNUPLOT_CMD);

  exec($cmd, $out);

  $jpg = 'sessions/' . $data->get_id() . '_final.jpg';
  $png = 'sessions/' . $data->get_id() . '_final.png';

  $cmd = str_replace('@IN_PNG', $png, IMAGEMAGICK_CMD);
  $cmd = str_replace('@OUT_JPG', $jpg, $cmd);

  exec($cmd, $out);
}

function GetPulsarDistance($dm, &$data)
{
  $cmd = NE2001_CMD;
  $cmd = str_replace('[@DM]', $dm, $cmd);
  $cmd = str_replace('[@ELONG]', $data->get_elong(), $cmd);
  $cmd = str_replace('[@ELAT]', $data->get_elat(), $cmd);

  $root_directory = getcwd();

  // change to this directoyr or NE2001 will whinge and cry
  chdir(NE2001_DIRECTORY);
  exec($cmd, $out);

  chdir($root_directory);

  preg_match('{(\d+\.\d+)}', $out[0], $m);  // extract number from output
  $distance = $m[1];

  return $distance;
}

function CreateGalaxyPlot($distance, &$data)
{
  // prevent multiple entries of the same pulsar to be stored
  if (!@in_array($_SESSION['pulsar_name'],
    $_SESSION['processed_pulsars']['name'])) {

    $_SESSION['processed_pulsars']['name'][] = $_SESSION['pulsar_name'];
    $_SESSION['processed_pulsars']['distance'][] = $distance;

    $_SESSION['processed_pulsars']['rajd'][] = $_SESSION['rajd'];
    $_SESSION['processed_pulsars']['decjd'][] = $_SESSION['decjd'];
  }

  // extract all pulsar names and distances from $_SESSION['processed_pulsars']
  $p = &$_SESSION['processed_pulsars'];

  $count = sizeof($p['name']);

  for ($i = 0; $i < $count; $i++) {
    $names_and_distances .= ' ' . $p['name'][$i] . ' ' . $p['distance'][$i];
  }

  //$names_and_distances = trim($names_and_distances);
  $names_and_distances = $data->get_pulsar_name() . ' ' . $distance;

  //echo "names: $names_and_distances <br>";

  $cmd = GALAXYPLOT_CMD;
  $cmd = str_replace('[@NAME]', 'Student', $cmd);
  $cmd = str_replace('[@GROUP]', 'VSSEC', $cmd);
  $cmd = str_replace('[@ARM_NAMES]', $data->get_arm_names(), $cmd);
  $cmd = str_replace('[@PULSAR_NAME_AND_DISTANCES]', $names_and_distances, $cmd);

  exec($cmd, $out);

  $plot_filename = 'sessions/' . $data->get_id() . '_final.jpg';
  copy('new.jpg', $plot_filename);
}

?>
