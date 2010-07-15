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

define(J0437_DM, 2.64476);
define(J0437_ELAT, -67.87);
define(J0437_ELONG, 50.47);

define(J0437_RAJD, 69.31618); // `psrcat -c rajd J0437-4715`
define(J0437_DECJD, -47.25251); // `psrcat -c decjd J0437-4715`

define(NE2001_CMD, './NE2001 ' . J0437_ELONG . ' ' . J0437_ELAT . ' [@DM] 1 | grep ModelDistance');

define(PSRCAT_CMD, '/pulsar/psr/linux/bin/psrcat -nohead -nonumber -c "rajd decjd" [@PULSAR_NAME] | awk \'{print $1, $2}\''); // J0437-4715

define(PLOTSKY_CMD, './runplotsky.csh "-c [@CENTRE_RAJD] [@CENTRE_DECJD] -p [@PULSAR_RAJD] [@PULSAR_DECJD] -f [@FOV] -g [@PROJECTION] -d [@CONSTELLATION_NAMES] -k sessions/[@SESSION_ID]_const.png/png"');

define(GALAXYPLOT_CMD, './galaxyPlot "[@NAME]" "[@GROUP]" "[@ARM_NAMES]" [@PULSAR] [@PULSAR_DISTANCE]');


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
  $data = new ModuleData($id);

  $content = Form::GetPageHeader();
  $content .= Form::LoadBodyTemplate(TEMPLATE_FILE);
  $content .= Form::GetPageFooter();

  ProcessFormData($data);

  $guess = $data->get_distance_guess();
  $user_dm = $data->get_user_dm();

  $pulsar_distance = GetPulsarDistance($user_dm);

  $pulsar_distance_ly = round($pulsar_distance * KPC_TO_LY, 0);
  Form::ReplaceText('[@DISTANCE_IN_LY]', $pulsar_distance_ly, $content);

  CreateGalaxyPlot($pulsar_distance_ly, $data);

  Form::ReplaceText('[@TITLE]', TITLE, $content);
  Form::ReplaceText('[@GUESS]', $guess, $content);

  $jpg = 'sessions/' . $id . '_final.jpg';

  $plot_filename = 'sessions/' . $id . '_galaxy.jpg';

  Form::ReplaceText('[@PULSAR_LOCATION_IMAGE]', $jpg, $content);

  $cmd = PLOTSKY_CMD;
  $cmd = str_replace('[@CENTRE_RAJD]', J0437_RAJD, $cmd);
  $cmd = str_replace('[@CENTRE_DECJD]', J0437_DECJD, $cmd);
  $cmd = str_replace('[@PULSAR_RAJD]', J0437_RAJD, $cmd);
  $cmd = str_replace('[@PULSAR_DECJD]', J0437_DECJD, $cmd);
  $cmd = str_replace('[@SESSION_ID]', $id, $cmd);
  $cmd = str_replace('[@FOV]', $data->get_fov(), $cmd);
  $cmd = str_replace('[@PROJECTION]', $data->get_projection(), $cmd);
  $cmd = str_replace('[@CONSTELLATION_NAMES]', $data->get_names(), $cmd);

  exec($cmd, $out);

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

  $const_image = 'sessions/' . $data->get_id() . '_const.png';

  Form::ReplaceText('[@CONST_IMAGE]', $const_image, $content);

  echo $content;
} catch (Exception $e) {
  echo 'Error: ', $e->getMessage();
}

function ProcessFormData(&$data)
{
  $jump_to_constellation_plot = FALSE;

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

  $projection = $data->get_projection();
  if ($projection != ProjectionType::Rectangular && $projection != ProjectionType::Aitoff) {
    $data->set_projection(ProjectionType::Rectangular);
  }

  if (isset($_POST['aitoff'])) {
    $data->set_projection(ProjectionType::Aitoff);
    $jump_to_constellation_plot = TRUE;
  }

  if (isset($_POST['rectangular'])) {
    $data->set_projection(ProjectionType::Rectangular);
    $jump_to_constellation_plot = TRUE;
  }

  $names = $data->get_names();
  if ($names != ConstellationNames::On && $names != ConstellationNames::Off) {
    $data->set_names(ConstellationNames::On);
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
}

function MakePositionPlot($x, $y, $pulsar_name, $data)
{
  $position_filename = 'sessions/' . session_id() . '_position.dat';
  $position_file = fopen($position_filename, 'w+');
  fwrite($position_file, "$x $y \n");
  fclose($position_file);

  $script_filename = 'sessions/' . session_id() . '_gnuplot_script.txt';

  copy(GNUPLOT_SCRIPT_FILE, 'sessions/' . session_id() . '_gnuplot_script.txt');
  $contents = file_get_contents($script_filename, 'r');

  //$contents = str_replace('output.png', 'sessions/' . session_id() .
  $contents = str_replace('output.png', 'sessions/' . $data->get_id() .
    '_final.png', $contents);

  $scriptFile = fopen($script_filename, 'w+');
  fwrite($scriptFile, $contents);

  $x += 0.5;
  fwrite($scriptFile, "set label \"$pulsar_name\" at $x,$y\n"); 

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

function CalculateXY(&$x, &$y, $elat, $elong, $distance)
{
  $distanc = round($distance, 2);
  $x = ($distance * -1) * sin(deg2rad($elong - 180)) * cos(deg2rad($elat)) / 0.73;
  $y = $distance * cos(deg2rad($elong - 180)) * cos(deg2rad($elat)) / 0.73;
  $x = round($x, 2);
  $y = round($y, 2);
}

function GetPulsarDistance($dm)
{

  $cmd = NE2001_CMD;
  $cmd = str_replace('[@DM]', $dm, $cmd);

  $root_directory = getcwd();

  // change to this directoyr or NE2001 will whinge and cry
  chdir(NE2001_DIRECTORY);
  exec($cmd, $out);

  chdir($root_directory);

  preg_match('{(\d+\.\d+)}', $out[0], $m);  // extract number from output
  $distance = $m[1];

  return $distance;
}

// $distance in ly
function CreateGalaxyPlot($distance, $data)
{
  $cmd = GALAXYPLOT_CMD;
  $cmd = str_replace('[@PULSAR]', 'J0437-4715', $cmd);
  $cmd = str_replace('[@PULSAR_DISTANCE]', $distance, $cmd);
  $cmd = str_replace('[@NAME]', 'Student', $cmd);
  $cmd = str_replace('[@GROUP]', 'VSSEC', $cmd);

  $cmd = str_replace('[@ARM_NAMES]', $data->get_arm_names(), $cmd);

  exec($cmd, $out);

  $plot_filename = 'sessions/' . $data->get_id() . '_final.jpg';
  copy('new.jpg', $plot_filename);
}

?>
