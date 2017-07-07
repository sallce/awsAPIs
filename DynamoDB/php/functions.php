<?php
require '../../vendor/autoload.php';

date_default_timezone_set('UTC');

use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Marshaler;


class Functions{

  //dynamodb instance
  private $dynamodb;
  //aws sdk instance
  private $sdk;
  //table name
  private $tableName;
  //marshaler instance to handle DynamoDb item
  private $marshaler;

  function __construct(){

    //instantiate
    $this->sdk = new Aws\Sdk([
      'endpoint' => 'http://localhost:8000',
      'region' => 'us-east-1',
      'version' => 'latest',
      'credentials' => array(
        'key' => "fakeKeyId",
        'secret'  => "fakeSecretAccessKey",
      )
    ]);

    $this->dynamodb = $this->sdk->createDynamoDb();

    $this->tableName = 'Movies';

    //instantiate marshaler object to convert JSON documents and php array into DynamoDb item
    $this->marshaler = new Marshaler();
  }

  function deleteTable(){
    $params = [
      'TableName' => $this->tableName
    ];

    try {
      $result = $this->dynamodb->deleteTable($params);
      echo "Deleted table.\n";

    } catch (DynamoDbException $e) {
      echo "Unable to delete table:</br>";
      echo $e->getMessage() . "</br>";
    }
  }

  //scan item
  function scanItems(){
    //Expression attribute values
    $eav = $this->marshaler->marshalJson('
      {
        ":start_yr": 1950,
        ":end_yr": 1959
      }
    ');

    $params = [
      'TableName' => $this->tableName,
      'ProjectionExpression' => '#yr, title, info.rating',
      'FilterExpression' => '#yr between :start_yr and :end_yr',
      'ExpressionAttributeNames'=> [ '#yr' => 'year' ],
      'ExpressionAttributeValues'=> $eav
    ];

    echo "Scanning Movies table.</br>";

    try {
      while (true) {
        $result = $this->dynamodb->scan($params);
        foreach ($result['Items'] as $i) {
            $movie = $this->marshaler->unmarshalItem($i);
            echo $movie['year'] . ': ' . $movie['title'];
            echo ' ... ' . $movie['info']['rating']
                . "</br>";
        }

        if (isset($result['LastEvaluatedKey'])) {
            $params['ExclusiveStartKey'] = $result['LastEvaluatedKey'];
        } else {
            break;
        }
      }

    } catch (DynamoDbException $e) {
      echo "Unable to scan:</br>";
      echo $e->getMessage() . "</br>";
    }
  }


  //query movies based on year and title
  function queryItems(){
    $eav = $this->marshaler->marshalJson('
      {
        ":yyyy" : 1992,
        ":letter1" : "A",
        ":letter2" : "L"
      }
    ');
    $params = [
      "TableName" => $this->tableName,
      'ProjectionExpression' => '#yr, title, info.genres, info.actors[0]',
      'KeyConditionExpression' => '#yr = :yyyy and title between :letter1 and :letter2',
      'ExpressionAttributeNames' => ['#yr' => 'year'],
      'ExpressionAttributeValues' => $eav
    ];
    echo "Querying for movies from 1992 - titles A-L, with genres and lead actor</br>";

    try {
      $result = $this->dynamodb->query($params);

      echo "Query succeeded.</br>";

      foreach ($result['Items'] as $i) {
        //unmarshlItem converts DynamoDb item into PHP array
        $movie = $this->marshaler->unmarshalItem($i);
        print $movie['year'] . ': ' . $movie['title'] . ' ... ';

        foreach ($movie['info']['genres'] as $gen) {
          print $gen . ' ';
        }

        echo ' ... ' . $movie['info']['actors'][0] . "</br>";
      }

    } catch (DynamoDbException $e) {
      echo "Unable to query:</br>";
      echo $e->getMessage() . "</br>";
    }
  }


  //delete an item
  function deleteItem($year,$title){
    $key = $this->marshaler->marshalJson('
      {
        "year" : '. $year .',
        "title" : "'. $title .'"
      }
    ');

    $eav = $this->marshaler->marshalJson('
      {
        ":val" : 5
      }
    ');

    $params = [
      'TableName' => $this->tableName,
      'Key' => $key,
      'ConditionExpression' => 'info.rating <= :val',
      'ExpressionAttributeValues' => $eav
    ];

    try {
      $result = $this->dynamodb->deleteItem($params);
      echo "Deleted item.</br>";
    } catch (DynamoDbException $e) {
        echo "Unable to delete item:</br>";
        echo $e->getMessage() . "</br>";
    }

  }

  //update item
  function updateItem($year, $title, $item){
    //condition Key
    $key = $this->marshaler->marshalJson('
      {
        "year" : '. $year .',
        "title" : "'. $title .'"
      }
    ');

    //set expressiong attribute values which are the new values
    $expressionAtrrValue = $this->marshaler->marshalJson('
      {
        ":r" : '. $item->info->rating .',
        ":p" : "'. $item->info->plot .'",
        ":a" : '. $item->info->actors .',
        ":num" : 3
      }
    ');

    //set params
    $params = [
      'TableName' => $this->tableName,
      'Key' => $key,
      'UpdateExpression' => 'set info.rating = :r, info.plot = :p, info.actors = :a',
      'ConditionExpression' => 'size(info.actors) > :num',
      'ExpressionAttributeValues' => $expressionAtrrValue,
      'ReturnValues' => 'UPDATED_NEW'
    ];

    //handler ErrorException
    try {
      $result = $this->dynamodb->updateItem($params);
      echo "Uploaded item.</br>";
      print_r($result['Attributes']);
    } catch (DynamoDbException $e) {
      echo "Unable to update item:</br>";
      echo $e->getMessage() . "</br>";
    }

  }

  //read item from table
  function readItem($year, $title){
    //get item based on primary key
    $key = $this->marshaler->marshalJson('
      {
        "year" : '. $year .',
        "title" : "'. $title .'"
      }
    ');

    //configure params
    $params = [
      'TableName' => $this->tableName,
      'Key' => $key
    ];

    //handler ErrorException
    try {
      $result = $this->dynamodb->getItem($params);
      print_r($result["Item"]);
    } catch (DynamoDbException $e) {
      echo "Unable to get item:</br>";
      echo $e->getMessage() . "</br>";
    }

  }

  //create a new item
  function createItem($year, $title, $plot, $rating){
    //create DynamoDb item
    $item = $this->marshaler->marshalJson('
      {
        "year" : '. $year .',
        "title" : "'. $title .'",
        "info" : {
          "plot" : "'. $plot .'",
          "rating" : '. $rating .'
        }
      }
    ');
    //set params
    $params = [
      'TableName' => $this->tableName,
      'Item' => $item
    ];

    //handle ErrorException
    try {
      $result = $this->dynamodb->putItem($params);
      echo "Added item : $year - $title</br>";
    } catch (DynamoDbException $e) {
      echo "Unable to add item:</br>";
      echo $e->getMessage() . "</br>";
    }

  }


  //load data from json File
  function loadData(){

    //get json data from file and decode it
    $movies = json_decode(file_get_contents('moviedata.json'), true);

    //loop through all movies and put each in table
    foreach($movies as $movie){
      //get each attributes
      $year = $movie['year'];
      $title = $movie['title'];
      $info = $movie['info'];
      //get json formatted data
      $json = json_encode([
        'year' => $year,
        'title' => $title,
        'info' => $info
      ]);

      //configure params
      $params = [
        'TableName' => $this->tableName,
        'Item' => $this->marshaler->marshalJson($json)
      ];

      //handle ErrorException
      try {
        $result = $this->dynamodb->putItem($params);
        echo "Added movie: " . $movie['year'] . " " . $movie['title'] . "</br>";
      } catch (DynamoDbException $e) {
          echo "Unable to add movie:</br>";
          echo $e->getMessage() . "</br>";
          break;
      }
    }

  }

  //create movie table function
  function createTable(){
    //table structure
    $params = [
        'TableName' => $this->tableName,
        'KeySchema' => [
            [
                'AttributeName' => 'year',
                'KeyType' => 'HASH'  //Partition key
            ],
            [
                'AttributeName' => 'title',
                'KeyType' => 'RANGE'  //Sort key
            ]
        ],
        'AttributeDefinitions' => [
            [
                'AttributeName' => 'year',
                'AttributeType' => 'N'
            ],
            [
                'AttributeName' => 'title',
                'AttributeType' => 'S'
            ],

        ],
        'ProvisionedThroughput' => [
            'ReadCapacityUnits' => 10,
            'WriteCapacityUnits' => 10
        ]
    ];
    //handle error exception
    try {
        $result = $this->dynamodb->createTable($params);
        echo 'Created table.  Status: ' .
            $result['TableDescription']['TableStatus'] ."</br>";

    } catch (DynamoDbException $e) {
        echo "Unable to create table:</br>";
        echo $e->getMessage() . "</br>";
    }
  }

}
