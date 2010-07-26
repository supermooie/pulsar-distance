<?php

class ModuleData
{
  function __construct($id)
  {
    $this->id = $id;

    // if <$id>.dat does not exist, create it
    $this->data_file = SESSION_DIRECTORY . $id . '.dat';

    if (!file_exists($this->data_file)) {
      echo "file does not exist <br>";
      $this->create_data_file($id);
    }

    $this->init();
  }

  protected function create_data_file($id)
  {
    $file = fopen($this->data_file, 'w+');

    fwrite($file, "distance_guess,NULL\n");
    fwrite($file, "frequencies\n");
    fwrite($file, "times\n");
    fwrite($file, "user_dm,NULL\n");
    fwrite($file, "fov,0\n");
    fwrite($file, "projection,0\n");
    fwrite($file, "names,0\n");
    fwrite($file, "arm_names,0\n");
    fwrite($file, "zoom_start,0\n");
    fwrite($file, "zoom_end,0\n");
    fwrite($file, "\n");

    fclose($file);
  }

  protected function read_data_file()
  {
    $file = fopen($this->data_file, 'r');

    while (!feof($file)) {
      $line = trim(fgets($file)); // get next line

      if (strlen($line) > 0) {
        $this->split_and_extract($line);
      }
    }

    fclose($file);
  }

  // read all values from <$id>.dat
  protected function init()
  {
    $this->read_data_file();
  }

  // split the line (string) by delimite ('=') and store appropriate member
  protected function split_and_extract($line)
  {
    $split = split(',', $line, 2);

    switch ($split[0]) {
    case 'distance_guess':
      $this->set_distance_guess($split[1]);
      break;
    case 'frequencies':
      $this->set_frequencies($split[1]);
      break;
    case 'times':
      $this->set_times($split[1]);
      break;
    case 'user_dm':
      $this->set_user_dm($split[1]);
      break;
    case 'fov':
      $this->set_fov($split[1]);
      break;
    case 'projection':
      $this->set_projection($split[1]);
      break;
    case 'names':
      $this->set_names($split[1]);
      break;
    case 'arm_names':
      $this->set_arm_names($split[1]);
      break;
    case 'zoom_start':
      $this->set_zoom_start($split[1]);
      break;
    case 'zoom_end':
      $this->set_zoom_end($split[1]);
      break;
    default:
      echo 'invalid keyword: ', $split[0], '<br>';
      break;
    }
  }

  public function set_arm_names($arm_names)
  {
    $this->arm_names = (int)$arm_names;
    $this->replace_keyword('arm_names', $this->arm_names);
  }

  public function set_names($names)
  {
    $this->names = (int)$names;
    $this->replace_keyword('names', $this->names);
  }

  public function set_projection($projection)
  {
    $this->projection = (int)$projection;
    $this->replace_keyword('projection', $this->projection);
  }

  public function set_user_dm($user_dm)
  {
    $this->user_dm = (float)$user_dm;
    $this->replace_keyword('user_dm', $this->user_dm);
  }

  public function set_frequencies($frequencies, $split_string = TRUE)
  {
    if ($split_string === TRUE) {
      $this->frequencies = $this->split_csv_string($frequencies);
    } else {
      $this->frequencies = $frequencies;
    }

    $frequencies_str = $this->as_csv_string($this->frequencies);
    $this->replace_keyword('frequencies', $frequencies_str);
  }

  public function set_times($times, $split_string = TRUE) 
  {
    if ($split_string === TRUE) {
      $this->times = $this->split_csv_string($times);
    } else {
      $this->times = $times;
    }

    $times_str = $this->as_csv_string($this->times);
    $this->replace_keyword('times', $times_str);
  }

  public function split_csv_string($string)
  {
    $string = rtrim($string, ',');
    $a = explode(',', $string);

    if (!empty($a[0])) {
      return $a;
    }
  }

  public function set_fov($fov)
  {
    $this->fov = (int)$fov;
    $this->replace_keyword('fov', $this->fov);
  }

  public function set_distance_guess($guess)
  {
    $this->distance_guess = floatval($guess);
    $this->replace_keyword('distance_guess', $this->distance_guess);
  }

  public function set_zoom_start($start)
  {
    $this->zoom_start = floatval($start);
    $this->replace_keyword('zoom_start', $this->zoom_start);
  }

  public function set_zoom_end($end)
  {
    $this->zoom_end = floatval($end);
    $this->replace_keyword('zoom_end', $this->zoom_end);
  }

  // getters

  public function get_id()
  {
    return $this->id;
  }

  public function get_zoom_end()
  {
    return $this->zoom_end;
  }


  public function get_zoom_start()
  {
    return $this->zoom_start;
  }
  public function get_fov()
  {
    return $this->fov;
  }

  public function get_arm_names()
  {
    return $this->arm_names;
  }

  public function get_names()
  {
    return $this->names;
  }

  public function get_projection()
  {
    return $this->projection;
  }

  public function get_user_dm()
  {
    return $this->user_dm;
  }

  public function get_frequencies()
  {
    return $this->frequencies;
  }

  public function get_times()
  {
    return $this->times;
  }

  public function get_distance_guess()
  {
    return $this->distance_guess;
  }

  public function as_csv_string($array)
  {
    if (!empty($array)) {
      foreach ($array as $a) {
        $string .= "$a,";
      }
    }

    return ltrim($string, ',');
  }

  public function add_time($time)
  {
    $this->times[] = $time;
    $times_str = $this->as_csv_string($this->times);

    //echo "times str: $times_str <br>";

    $this->replace_keyword('times', $times_str);
  }

  public function add_frequency($frequency)
  {
    $this->frequencies[] = $frequency;
    $frequencies_str = $this->as_csv_string($this->frequencies);
    $this->replace_keyword('frequencies', $frequencies_str);
  }

  public function time_exists($time)
  {
    if (@in_array($time, $this->times)) {
      return TRUE;
    } else {
      return FALSE;
    }
  }

  public function frequency_exists($frequency)
  {
    if (@in_array($frequency, $this->frequencies)) {
      return TRUE;
    } else {
      return FALSE;
    }
  }

  // replaces a keyword's corresponding value in <$id>.dat
  protected function replace_keyword($keyword, $value)
  {
    $lines = file($this->data_file);

    $pattern = '/^' . $value . '/';

    foreach ($lines as &$l) {

      $pos = strpos($l, $keyword);
      if ($pos === 0) {
        $l = $keyword . ',' . strval($value) . "\n";
      }
    }

    $file = fopen($this->data_file, 'w');

    foreach ($lines as $l) {
      fwrite($file, $l);
    }

    fclose($file);
  }

  protected $data_file;

  protected $distance_guess;
  protected $frequencies = array();
  protected $times = array();
  protected $user_dm;
  protected $fov;
  protected $projection;
  protected $names;
  protected $arm_names;
  protected $id;

  public $zoom_start;
  public $zoom_end;
}

?>
