<?php
require_once './functions.php';

$func = new Functions();

$action_type = $_GET['action_type'];

switch ($action_type) {
  case 'createTable':
    $func->createTable();
    break;

  case 'loadData':
    $func->loadData();
    break;

  case 'createItem':
    $func->createItem($_GET['year'], $_GET['title'], $_GET['plot'], $_GET['rating']);
    break;

  case 'readItem':
    $func->readItem($_GET['year'], $_GET['title']);
    break;

  case 'deleteItem':
    $func->deleteItem($_GET['year'], $_GET['title']);
    break;

  case 'deleteTable':
    $func->deleteTable();
    break;

  case 'scanItems':
    $func->scanItems();
    break;

  case 'queryItems':
    $func->queryItems();
    break;

  case 'updateItem':
    $data = json_decode(file_get_contents('php://input'));
    $func->updateItem($_GET['year'], $_GET['title'], $data->item);
    // var_dump($data->item->info->rating);
    break;

  default:
    # code...
    break;
}
