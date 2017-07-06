
//add event listener for file input to populate data
document.getElementById('fileinput').addEventListener('change', processFile, false);

//set basic configuration information
AWS.config.update({
  region: "us-east-1",
  endpoint: 'http://localhost:8000', // dynamoDB is running locally
  // get the key id from aws adminstrator;
  //dynamoDB is installed locally so that it is not important
  accessKeyId: "fakeKeyId",
  // get the secret key from aws adminstrator;
  //dynamoDB is installed locally so that it is not importan
  secretAccessKey: "fakeSecretAccessKey"
});

//global variable
var TABLENAME = "roomStays";

//instantiate dynamoDB client connection
var dynamodb = new AWS.DynamoDB();

//instantiate document client to populate data
var docClient = new AWS.DynamoDB.DocumentClient();

//function for populate data from a file
function processFile(e){

  //empty textarea which is used for showing message
  document.getElementById('textarea').innerHTML = "";
  document.getElementById('textarea').innerHTML = "Importing data into DynamoDB. Please wait...\n";

  //get file object
  var file = e.target.files[0];

  //check if the file is valid
  if (file) {
    //instantiate the file reader
    var readFile = new FileReader();

    //file on load event
    readFile.onload = function(ev) {
      //get all data
      var contents = ev.target.result;
      //convert string to json format
      var allRoomStays = JSON.parse(contents);
      //insert each data set
      allRoomStays.forEach(function(roomStay){
        //show messge about the uploading process
        document.getElementById('textarea').innerHTML += "Processing: " + roomStay.orderID + "\n";
        //configure params
        var params = {
          TableName:TABLENAME,
          Item: {
            "orderID":roomStay.orderID,
            "client_id":roomStay.client_id,
            "rooms":roomStay.rooms,
            "adult_count":roomStay.adult_count,
            "child_count":roomStay.child_count,
            "nights":roomStay.nights,
            "check_in":roomStay.check_in,
            "check_out":roomStay.check_out,
            "average_night_rate":roomStay.average_night_rate,
            "room_rate_info":roomStay.room_rate_info
          }
        };

        //insert item into dynamodb
        docClient.put(params, function(err,data){
          if (err) {
              document.getElementById('textarea').innerHTML += "Unable to add movie: " + count + roomStay.orderID + "\n";
              document.getElementById('textarea').innerHTML += "Error JSON: " + JSON.stringify(err) + "\n";
          } else {
              document.getElementById('textarea').innerHTML += "PutItem succeeded: " + roomStay.orderID + "\n";
              textarea.scrollTop = textarea.scrollHeight;
          }
        });
      });
    };
    //read data from file
    readFile.readAsText(file);
  }else{
    alert("Could not read file!");
  }
};

var createRoomStays = function(){
  //configure the table structure
  var params = {
    TableName : TABLENAME,
    KeySchema: [
      { AttributeName:"orderID",  KeyType: "HASH" },
      { AttributeName:"client_id", KeyType: "RANGE" }
    ],
    AttributeDefinitions:[
      { AttributeName:"orderID", AttributeType: "N" },
      { AttributeName:"client_id", AttributeType: "N" },
    ],
    ProvisionedThroughput:{
      ReadCapacityUnits: 5,
      WriteCapacityUnits: 5
    }
  };

  //create table
  dynamodb.createTable(params, function(err,data){
    if(err){
      document.getElementById('textarea').innerHTML = "Unable to create table: " + "\n" + JSON.stringify(err, undefined, 2);
    } else {
      document.getElementById('textarea').innerHTML = "Created table: " + "\n" + JSON.stringify(data, undefined, 2);
    }
  });
}

//delete table
function deleteMovies() {
    var params = {
        TableName : TABLENAME
    };

    dynamodb.deleteTable(params, function(err, data) {
        if (err) {
            document.getElementById('textarea').innerHTML = "Unable to delete table: " + "\n" + JSON.stringify(err, undefined, 2);
        } else {
            document.getElementById('textarea').innerHTML = "Table deleted.";
        }
    });
}


//insert
function insertItem() {
  //configure params object
  var params = {
    TableName : TABLENAME,
    Item:{
      "orderID":21,
      "client_id":3,
      "rooms":2,
      "adult_count":2,
      "child_count":0,
      "nights":2,
      "check_in":"2017-07-30",
      "check_out":"2017-08-01",
      "average_night_rate":150,
      "room_rate_info":[{
          "check_in": "2017-07-30",
          "check_out": "2017-08-01",
          "night_rate": 150,
          "rooms": 2,
          "sub_total": 300,
          "room_type": "two beds"
      }]
    },
    ReturnValues: "ALL_OLD"
  };

  //insert data into database
  docClient.put(params, function(err,data){
    if (err) {
           document.getElementById('textarea').innerHTML = "Unable to add item: " + "\n" + JSON.stringify(err, undefined, 2);
       } else {
           document.getElementById('textarea').innerHTML = "PutItem succeeded: " + "\n" + JSON.stringify(data, undefined, 2);
       }
  });

}


function readItem() {
    var orderID = 21;
    var client_id = 3;

    var params = {
      TableName : TABLENAME,
      Key:{
        "orderID":21,
        "client_id":3
      }
    };

    docClient.get(params, function(err,data){
      if (err) {
            document.getElementById('textarea').innerHTML = "Unable to read item: " + "\n" + JSON.stringify(err, undefined, 2);
        } else {
            document.getElementById('textarea').innerHTML = "GetItem succeeded: " + "\n" + JSON.stringify(data, undefined, 2);
        }
    });
}

function updateItem() {

  var orderID = 21;
  var client_id = 3;

  var params = {
    TableName : TABLENAME,
    Key:{
      "orderID":orderID,
      "client_id":client_id
    },
    UpdateExpression: "set room_rate_info[0].room_type = :r",//which field to update
    ConditionExpression: "size(room_rate_info[0]) > :num",//update condition
    ExpressionAttributeValues:{
        ":r":"King bed room", // the updated value
        ":num":2
    },
    ReturnValues:"UPDATED_NEW"
  };

  docClient.update(params, function(err, data) {
      if (err) {
          document.getElementById('textarea').innerHTML = "Unable to update item: " + "\n" + JSON.stringify(err, undefined, 2);
      } else {
          document.getElementById('textarea').innerHTML = "UpdateItem succeeded: " + "\n" + JSON.stringify(data, undefined, 2);
      }
  });

}

function deleteItem(){
  var orderID = 21;
  var client_id = 3;

  var params = {
    TableName : TABLENAME,
    Key:{
      "orderID":orderID,
      "client_id":client_id,
      // ConditionExpression: "room_rate_info[0].night_rate >= :num",
      // ExpressionAttributeValues:{
      //     ":num":100
      // }
    },
  };

  docClient.delete(params, function(err, data) {
      if (err) {
          document.getElementById('textarea').innerHTML = "The conditional delete failed: " + "\n" + JSON.stringify(err, undefined, 2);
      } else {
          document.getElementById('textarea').innerHTML = "The conditional delete succeeded: " + "\n" + JSON.stringify(data, undefined, 2);
      }
  });
}

//BASED ON PRIMARY KEY
function queryDataKey() {
    document.getElementById('textarea').innerHTML += "Querying for orders orderID is 1.\n";

    var params = {
        TableName : TABLENAME,
        KeyConditionExpression: "#clid = :id",
        ExpressionAttributeNames:{
            "#clid": "orderID"
        },
        ExpressionAttributeValues: {
            ":id":1
        }
    };

    docClient.query(params, function(err, data) {
        if (err) {
            document.getElementById('textarea').innerHTML += "Unable to query. Error: " + "\n" + JSON.stringify(err, undefined, 2);
        } else {
            document.getElementById('textarea').innerHTML += "Querying for orderID is 1: " + "\n" + JSON.stringify(data, undefined, 2);
        }
    });
}


//based on conditions and select specific fields
function queryData() {
    document.getElementById('textarea').innerHTML += "Querying for orderID is 1.";

    var params = {
        TableName : TABLENAME,
        ProjectionExpression:"#orid, rooms, check_in, check_out",
        KeyConditionExpression: "#orid = :id and rooms between :num1 and :num2",
        ExpressionAttributeNames:{
            "#orid": "orderID"
        },
        ExpressionAttributeValues: {
            ":id":1,
            ":num1": 1,
            ":num2": 2
        }
    };

    docClient.query(params, function(err, data) {
        if (err) {
            document.getElementById('textarea').innerHTML += "Unable to query. Error: " + "\n" + JSON.stringify(err, undefined, 2);
        } else {
            document.getElementById('textarea').innerHTML += "Querying for orderID is 1 - rooms between 1-2, with check_in and check_out: " + "\n" + JSON.stringify(data, undefined, 2);
        }
    });
}



function scanData() {
    document.getElementById('textarea').innerHTML += "Scanning orders table." + "\n";

    var params = {
        TableName: TABLENAME,
        ProjectionExpression: "#order, check_in, check_out",
        FilterExpression: "#order between :start_id and :end_id",
        ExpressionAttributeNames: {
            "#order": "orderID",
        },
        ExpressionAttributeValues: {
            ":start_id": 1,
            ":end_id": 5
        }
    };

    docClient.scan(params, onScan);

    function onScan(err, data) {
        if (err) {
            document.getElementById('textarea').innerHTML += "Unable to scan the table: " + "\n" + JSON.stringify(err, undefined, 2);
        } else {
            // Print all the movies
            document.getElementById('textarea').innerHTML += "Scan succeeded. " + "\n";
            data.Items.forEach(function(order) {
                document.getElementById('textarea').innerHTML += order.orderID + ": check_in" + order.check_in + " - check_out: " + order.check_out + "\n";
            });

            // Continue scanning if we have more orders (per scan 1MB limitation)
            document.getElementById('textarea').innerHTML += "Scanning for more..." + "\n";
            params.ExclusiveStartKey = data.LastEvaluatedKey;
            docClient.scan(params, onScan);
        }
    }
}
