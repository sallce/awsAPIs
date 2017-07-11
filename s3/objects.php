<?php
require '../vendor/autoload.php';
use Aws\S3\MultipartUploader;
use Aws\Exception\MultipartUploadException;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;


$bucket = 'BucketName';
$keyname = 'FilePath';

// Instantiate the client.
$s3Client = new S3Client([
      'version' => 'latest',
      'region'  => 'us-east-1',
      'credentials' => [
          'key'    => 'YourKey',//this key is provided by aws adminstrator
          'secret' => 'YourSecret'//this secret is provided by aws adminstrator
      ]
  ]);

//upload object request
if($_SERVER['REQUEST_METHOD'] === 'POST'){

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

}



//retrive object request
if($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['key'])){

  try {
      // Get the object
      $result = $s3Client->getObject(array(
          'Bucket' => $bucket,
          'Key'    => $_GET['key']
      ));

      // Display the object in the browser
      header("Content-Type: {$result['ContentType']}");
      echo $result['Body'];
  } catch (S3Exception $e) {
      echo $e->getMessage() . "</br>";
  }

//   $result = $s3Client->getObject(array(
//     'Bucket' => $bucket,
//     'Key' => $keyname
//   ));
//
//   // Get a range of bytes from an object.
//   $result = $s3Client->getObject(array(
//       'Bucket' => $bucket,
//       'Key'    => $keyname,
//       'Range'  => 'bytes=0-99'
//   ));
//
//   // Save object to a file.
//   $result = $s3Client->getObject(array(
//       'Bucket' => $bucket,
//       'Key'    => $keyname,
//       'SaveAs' => $filepath
//   ));

}

//get objects list
if($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['list'])){

  // Use the high-level iterators (returns ALL of your objects).
  try {
      $objects = $s3Client->getIterator('ListObjects', array(
          'Bucket' => $bucket
      ));

      echo "Keys retrieved!</br>";
      foreach ($objects as $object) {
          echo $object['Key'] . "</br>";
      }
  } catch (S3Exception $e) {
      echo $e->getMessage() . "</br>";
  }

  // //Use the plain API (returns ONLY up to 1000 of your objects).
  // try {
  //     $result = $s3Client->listObjects(array('Bucket' => $bucket));
  //
  //     echo "Keys retrieved!</br>";
  //     foreach ($result['Contents'] as $object) {
  //         echo $object['Key'] . "</br>";
  //     }
  // } catch (S3Exception $e) {
  //     echo $e->getMessage() . "</br>";
  // }

}




//delete single object request
if($_SERVER['REQUEST_METHOD'] === 'DELETE' && isset($_GET['single']) && $_GET['single'] == true){
  // 1. Create a few objects.
  for ($i = 1; $i <= 3; $i++) {
      $s3Client->putObject(array(
          'Bucket' => $bucket,
          'Key'    => "key{$i}",
          'Body'   => "content{$i}",
      ));
  }

  // 2. List the objects and get the keys.
  $keys = $s3Client->listObjects(array('Bucket' => $bucket))
      ->getPath('Contents/*/Key');

  // 3. Delete the objects.
  $result = $s3Client->deleteObjects(array(
      'Bucket'  => $bucket,
      'Objects' => array_map(function ($key) {
          return array('Key' => $key);
      }, $keys),
  ));
}

//delete multiple objects
if($_SERVER['REQUEST_METHOD'] === 'DELETE' && isset($_GET['multi']) && $_GET['multi'] == true){

  // 1. Enable object versioning for the bucket.
  $s3Client->putBucketVersioning(array(
      'Bucket' => $bucket,
      'Status' => 'Enabled',
  ));

  // 2. Create a few versions of an object.
  for ($i = 1; $i <= 3; $i++) {
      $s3Client->putObject(array(
          'Bucket' => $bucket,
          'Key'    => $keyname,
          'Body'   => "content{$i}",
      ));
  }

  // 3. List the objects versions and get the keys and version IDs.
  $versions = $s3Client->listObjectVersions(array('Bucket' => $bucket))
      ->getPath('Versions');

  // 4. Delete the object versions.
  $result = $s3Client->deleteObjects(array(
      'Bucket'  => $bucket,
      'Objects' => array_map(function ($version) {
          return array(
              'Key'       => $version['Key'],
              'VersionId' => $version['VersionId']
          );
      }, $versions),
  ));

  echo "The following objects were deleted successfully:</br>";
  foreach ($result['Deleted'] as $object) {
      echo "Key: {$object['Key']}, VersionId: {$object['VersionId']}</br>";
  }

  echo "</br>The following objects could not be deleted:</br>";
  foreach ($result['Errors'] as $object) {
      echo "Key: {$object['Key']}, VersionId: {$object['VersionId']}</br>";
  }

  // 5. Suspend object versioning for the bucket.
  $s3->putBucketVersioning(array(
      'Bucket' => $bucket,
      'Status' => 'Suspended',
  ));
}
