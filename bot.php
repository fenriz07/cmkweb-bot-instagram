<?php

include __DIR__. '/cmantikweb.php';

$cmkw = new BOTCMKW();

//Menu
echo "Opciones: \n";
echo "1 - Balancear Seguidores \n";
echo "2 - Ganar Seguidores \n";

$option = trim(fgets(STDIN));

switch ($option) {
  case 1:
    $cmkw->balanceFollower();
    break;

  case 2:
    $cmkw->obtainFollow();
    break;

  default:
    exit(0);
    break;
}
