<?php

class ModuleDataPartTwo extends ModuleData
{
  protected function create_data_file($id)
  {
    $file = fopen($this->data_file, 'w+');

    fwrite($file, "distance_guess,NULL\n");
    fwrite($file, "frequencies\n");
    fwrite($file, "times\n");
    fwrite($file, "user_dm,NULL\n");
    fwrite($file, "fov,40\n");
    fwrite($file, "projection,0\n");
    fwrite($file, "names,0\n");
    fwrite($file, "arm_names,0\n");
    fwrite($file, "zoom_start,NULL\n");
    fwrite($file, "zoom_end,NULL\n");
    fwrite($file, "group_name,NULL\n");
    fwrite($file, "group_number,NULL\n");
    fwrite($file, "observation_file,NULL\n");
    fwrite($file, "pulsar_name,NULL\n");
    fwrite($file, "rotate,0\n");
    fwrite($file, "frequency,ALL\n");
    fwrite($file, "plot_type,0\n");
    fwrite($file, "fit_line,0\n");
    fwrite($file, "rajd,NULL\n");
    fwrite($file, "decjd,NULL\n");
    fwrite($file, "\n");

    fclose($file);
  }

  protected function split_and_extract($line)
  {
    $split = split(',', $line, 2);
    $keyword = $split[0];
    $value = $split[1];

    switch ($keyword) {
    case 'distance_guess':
      $this->set_distance_guess($value);
      break;
    case 'frequencies':
      $this->set_frequencies($value);
      break;
    case 'times':
      $this->set_times($value);
      break;
    case 'user_dm':
      $this->set_user_dm($value);
      break;
    case 'fov':
      $this->set_fov($value);
      break;
    case 'projection':
      $this->set_projection($value);
      break;
    case 'names':
      $this->set_names($value);
      break;
    case 'arm_names':
      $this->set_arm_names($value);
      break;
    case 'zoom_start':
      $this->set_zoom_start($value);
      break;
    case 'zoom_end':
      $this->set_zoom_end($value);
      break;
    case 'group_name':
      $this->set_group_name($value);
      break;
    case 'group_number':
      $this->set_group_number($value);
      break;
    case 'observation_file':
      $this->set_observation_file($value);
      break;
    case 'pulsar_name':
      $this->set_pulsar_name($value);
      break;
    case 'rotate':
      $this->set_rotate($value);
      break;
    case 'frequency':
      $this->set_frequency($value);
      break;
    case 'dm':
      $this->set_dm($value);
      break;
    case 'elong':
      $this->set_elong($value);
      break;
    case 'elat':
      $this->set_elat($value);
      break;
    case 'plot_type':
      $this->set_plot_type($value);
      break;
    case 'fit_line':
      $this->set_fit_line($value);
      break;
    case 'rajd':
      $this->set_rajd($value);
      break;
    case 'decjd':
      $this->set_decjd($value);
      break;
    default:
      echo 'invalid keyword: ', $keyword, '<br>';
      break;
    }
  }

  public function set_group_name($group_name)
  {
    $this->group_name = $group_name;
    $this->replace_keyword('group_name', $this->group_name);
  }

  public function set_group_number($group_number)
  {
    $this->group_number = (int)$group_number;
    $this->replace_keyword('group_number', $this->group_number);
  }

  public function set_observation_file($observation_file)
  {
    $this->observation_file= $observation_file;
    $this->replace_keyword('observation_file', $this->observation_file);
  }

  public function set_pulsar_name($pulsar_name)
  {
    $this->pulsar_name = $pulsar_name;
    $this->replace_keyword('pulsar_name', $this->pulsar_name);
  }

  public function set_rotate($rotate)
  {
    $this->rotate = (int)$rotate;
    $this->replace_keyword('rotate', $this->rotate);
  }

  public function set_frequency($frequency)
  {
    $this->frequency = $frequency;
    $this->replace_keyword('frequency', $this->frequency);
  }

  public function set_dm($dm)
  {
    $this->dm = $dm;
    $this->replace_keyword('dm', $this->dm);
  }

  public function set_plot_type($plot_type)
  {
    $this->plot_type = (int)$plot_type;
    $this->replace_keyword('plot_type', $this->plot_type);
  }

  public function set_fit_line($fit_line)
  {
    $this->fit_line = (int)$fit_line;
    $this->replace_keyword('fit_line', $this->fit_line);
  }

  public function set_rajd($rajd)
  {
    $this->rajd = $rajd;
    $this->replace_keyword('rajd', $this->rajd);
  }

  public function set_decjd($decjd)
  {
    $this->decjd = $decjd;
    $this->replace_keyword('decjd', $this->decjd);
  }

  public function get_decjd()
  {
    return $this->decjd;
  }

  public function get_rajd()
  {
    return $this->rajd;
  }

  public function get_fit_line()
  {
    return $this->fit_line;
  }

  public function get_plot_type()
  {
    return $this->plot_type;
  }

  public function get_dm()
  {
    return $this->dm;
  }

  public function set_elong($elong)
  {
    $this->elong = $elong;
    $this->replace_keyword('elong', $this->elong);
  }

  public function get_elong()
  {
    return $this->elong;
  }

  public function set_elat($elat)
  {
    $this->elat = $elat;
    $this->replace_keyword('elat', $this->elat);
  }

  public function get_elat()
  {
    return $this->elat;
  }


  public function get_frequency()
  {
    return $this->frequency;
  }

  public function get_rotate()
  {
    return $this->rotate;
  }

  public function get_pulsar_name()
  {
    return $this->pulsar_name;
  }

  public function get_observation_file()
  {
    return $this->observation_file;
  }

  public function get_group_name()
  {
    return $this->group_name;
  }

  public function get_group_number()
  {
    return $this->group_number;
  }

  protected $group_name;
  protected $group_number;
  protected $observation_file;
  protected $dm;
  protected $elong;
  protected $elat;
  protected $plot_type;
  protected $fit_line;
}

?>
