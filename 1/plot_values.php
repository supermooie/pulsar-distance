<?php

require_once('Config.php');
require_once(ROOT_DIRECTORY . 'Classes/Form.php');
require_once(ROOT_DIRECTORY . 'Classes/Mailer.php');

define(TEMPLATE_FILE, TEMPLATE_DIRECTORY . 'plot_values.html');
define(NEXT_PAGE_FILENAME, 'galaxy_plot.php');
define(NEXT_PAGE_TEXT, 'galaxy information');
define(TITLE, 'x-y Values Plot');

define(J0437_DM, 2.64476);
define(J0437_ELAT, -67.87);
define(J0437_ELONG, 50.47);

try {
  $id = $_GET['id'];
  $data = new ModuleData($id);

  $content = Form::GetPageHeader();
  $content .= Form::LoadBodyTemplate(TEMPLATE_FILE);
  $content .= Form::GetPageFooter();

  $table = MakeTable($data->get_frequencies(), $data->get_times());
  Form::ReplaceText('[@TABLE]', $table, $content);

  MakeGraph($data->get_frequencies(), $data->get_times(), $data);

  $random_string = Identifier::GenerateNewIdentifier();
  $image_path = 'sessions/' . $id . "_freqtime.png?$random_string";
  Form::ReplaceText('[@IMAGE_PATH]', $image_path, $content);

  $next_page = NEXT_PAGE_FILENAME . "?id=$id";

  Form::ReplaceNextPageLink($next_page, $content);
  Form::ReplaceText('[@TITLE]', TITLE, $content);

  Form::ReplaceText('[@DM_PSRCAT]', J0437_DM, $content);
  Form::ReplaceText('[@DM_USER]', $data->get_user_dm(), $content);

  $dm_difference = J0437_DM - $data->get_user_dm();
  Form::ReplaceText('[@DM_DIFFERENCE]', $dm_difference, $content);

  echo $content;
} catch (Exception $e) {
  echo 'Error: ', $e->getMessage();
}

function MakeGraph($x, $y, &$data)
{
  $cmd .= '../../runplotgraph.csh "';

  $count = sizeof($x);
  for ($i = 0; $i < $count; $i++) {
    $cmd .= ' -d ' .  $x[$i] . ' ' . $y[$i];
  }

  $image_path =  'sessions/' . $data->get_id() . '_freqtime.png';

  $cmd .= ' -out ' . $image_path . '/png';
  $cmd .= ' -axis 2';
  $cmd .= ' -fit';
  $cmd .= '"'." ".$image_path." J0437-4715";

  exec($cmd, $out);

  // Store the calculated gradient as the DM.
  $data->set_user_dm($out[0]);
}

function MakeTable($frequencies, $times)
{
  $table = '<table class="gridtable">' .
    '<tr><th><b>Frequency (MHz)</b></th><th><b>Time (s)</b></th></tr>';

  for ($i = 0; $i < sizeof($frequencies); $i++) {
    $table .= '<tr><td align=center>' .
      $frequencies[$i] .
      '</td><td align=center>' .
      $times[$i] .
      '</td></tr>';
  }

  $table .= '</table>';

  return $table;
}

?>
