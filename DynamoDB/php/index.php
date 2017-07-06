<?php
require_once './functions.php';

$func = new Functions();

$action_type = $_GET['action_type'];

switch ($action_type) {
  case 'createTable':
    $func->createTable();
    break;

  default:
    # code...
    break;
}
