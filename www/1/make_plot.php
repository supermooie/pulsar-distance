<?php

session_start();
$sid = session_id();

$cmd = '../../runplot.csh "';
$cmd .= '-f /nfs/wwwresearch/pulsar/pulseATpks/J0437-4715.22.1.8channels.txt';

$image_path = 'sessions/' . $sid . '.png';

$cmd .= ' -out ' . $image_path . '/png';
$cmd .= ' -rot 600 -p J0437-4715 ';

if (isset($_POST['zoom_start']) && isset($_POST['zoom_end'])) {
  if (strlen($_POST['zoom_start']) > 0 && strlen($_POST['zoom_end']) > 0) {
    $cmd .= ' -zooml ' . $_POST['zoom_start'];
    $cmd .= ' -zoomr ' . $_POST['zoom_end'];
  }
}

$cmd .= '" '." ".$image_path;

#  echo "command: $cmd <br>";

exec($cmd, $out);
$error = $out[0];

?>
