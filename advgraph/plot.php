<html>
  <head>
  </head>
  <body>
    <?php
    include "graph.oo.php";

    $graph = new graph(800,500);

    $fh = fopen("J0437-4715.21.1.8channels.csv", "r");
    while (($data = fgetcsv($fh, 1000, ",")) !== FALSE) {
      $idata = 1;
      for ($idataset = 0; $idataset < 8; $idataset++, $idata++) {
        $graph->addPoint($data[$idata], $data[0], $idataset);
      }
    }

    $graph->setColor('color', 0, 'blue');
    $graph->setColor('color', 1, 'green');
    $graph->setColor('color', 2, 'orange');
    $graph->setColor('color', 3, 'purple');
    $graph->setColor('color', 4, 'black');
    $graph->setColor('color', 5, 'yellow');
    $graph->setColor('color', 6, 'red');
    $graph->setColor('color', 7, 'pink');

    $graph->setProp('titlesize', '18');
    $graph->setProp('title', 'J0437-4715');
    $graph->setProp('xlabel', 'Time (s)');
    $graph->setProp('ylabel', '');

    $graph->setProp('autosize',false);
    $graph->xMin = 0.0025384;
    $graph->xMax = 0.0050412;
    $graph->yMin = 0.0;
    $graph->yMax = 104.0;

    $graph->textsize = 10;
    $graph->showvertscale = 0;
    $graph->ysclpts = 0;
    $graph->xsclpts = 7;
    $graph->xincpts = 7;
    $graph->sclline = 7;
    $graph->onfreq = .4;

    //$graph->setBulkProps("0|key:alpha,1|key:beta,2|key:delta,3|key:delta,4|key:delta,5|key:delta,6|key:delta,7|key:delta");
    //$graph->showkey = 1;

    $graph->graph();
    $graph->showGraph("test.png");

    ?>

    <img src="test.png">

  </body>
</html>
