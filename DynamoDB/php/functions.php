<?php
require '../../vendor/autoload.php';

date_default_timezone_set('UTC');

use Aws\DynamoDb\Exception\DynamoDbException;


class Functions{

  private $dynamodb;
  private $sdk;

  function __construct(){

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
  }

  function createTable(){
    $params = [
        'TableName' => 'Movies',
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

    try {
        $result = $this->dynamodb->createTable($params);
        echo 'Created table.  Status: ' .
            $result['TableDescription']['TableStatus'] ."\n";

    } catch (DynamoDbException $e) {
        echo "Unable to create table:\n";
        echo $e->getMessage() . "\n";
    }
  }

}
