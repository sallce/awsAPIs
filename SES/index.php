<?php
/**
 * User: Kyle Shang
 * Date: 7/25/2017
 * Time: 5:02 PM
 * Last Modified: 7/25/2017 5:02 PM
 */


// Replace path_to_sdk_inclusion with the path to the SDK as described in
// http://docs.aws.amazon.com/aws-sdk-php/v3/guide/getting-started/basic-usage.html
define('REQUIRED_FILE','../vendor/autoload.php');

// Replace sender@example.com with your "From" address.
// This address must be verified with Amazon SES.
define('SENDER', 'testSender@test.com');

// Replace recipient@example.com with a "To" address. If your account
// is still in the sandbox, this address must be verified.
define('RECIPIENT', 'testReciever@test.com');

// Replace us-east-1 with the AWS Region you're using for Amazon SES.
define('REGION','us-east-1');

define('SUBJECT','Amazon SES test (AWS SDK for PHP)');
define('BODY',"This email was sent with Amazon SES using the AWS SDK for PHP.");

require REQUIRED_FILE;

use Aws\Ses\SesClient;
use Aws\Ses\Exception\SesException;

$client = SesClient::factory(array(
    'version'=> 'latest',
    'region' => REGION,
    'credentials' => array(
        'key' => 'aws key',
        'secret'  => 'aws secret'
    )
));

$request = array();
$request['Source'] = SENDER;
$request['Destination']['ToAddresses'] = array(RECIPIENT);
$request['Message']['Subject']['Data'] = SUBJECT;
$request['Message']['Body']['Text']['Data'] = BODY;

try {
    //http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-email-2010-12-01.html#sendemail
    $result = $client->sendEmail($request);
    echo("Email sent! Message ID: ".$result->get('MessageId')."\n");

} catch (SesException $e) {
    echo("The email was not sent. Error message: ".$e->getAwsErrorMessage()."\n");
}
