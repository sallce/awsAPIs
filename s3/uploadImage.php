<?php
require '../vendor/autoload.php';
use Aws\S3\MultipartUploader;
use Aws\Exception\MultipartUploadException;
use Aws\S3\S3Client;

$bucket = 'yourBucketName';
$keyname = 'uploadedPathFileName';

// Instantiate the client.
$s3Client = new S3Client([
      'version' => 'latest',
      'region'  => 'us-east-1',//this needs to be matched with bucket region
      'credentials' => [
          'key'    => 'YourKEY',//this key is provided by aws adminstrator
          'secret' => 'YourSecret'//this secret is provided by aws adminstrator
      ]
  ]);


$uploader = new MultipartUploader($s3Client, './lamp.png', [
    'bucket' => $bucket,
    'key'    => $keyname,
    'ACL'    => 'public-read',//set public read permission so that anyone can read the uploaded image
]);

try {
    $result = $uploader->upload();
    echo "Upload complete: {$result['ObjectURL']}</br>";
} catch (MultipartUploadException $e) {
    echo $e->getMessage() . "</br>";
}
