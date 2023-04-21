<?php
require "vendor/autoload.php";

use Abraham\TwitterOAuth\TwitterOAuth;


$CONSUMER_KEY = getenv('CONSUMER_KEY');
$CONSUMER_SECRET = getenv('CONSUMER_SECRET');
$ACCESS_TOKEN = getenv('ACCESS_TOKEN');
$ACCESS_TOKEN_SECRET = getenv('ACCESS_TOKEN_SECRET');

$aqi = getAQI();

sendNewStatusFor($aqi);
setNewDisplayNameFor($aqi);
setNewAvatarFor($aqi);

function getAQI(){
  // Read the JSON file contents into a string
  $json_string = file_get_contents('outputs/aqi-outputs.json');
  // Parse the JSON string into a PHP object
  $data = json_decode($json_string);
  return $data;
}

function sendNewStatusFor($aqi) {
  global $CONSUMER_KEY , $CONSUMER_SECRET , $ACCESS_TOKEN,  $ACCESS_TOKEN_SECRET;
  $connection = new TwitterOAuth($CONSUMER_KEY, $CONSUMER_SECRET, $ACCESS_TOKEN, $ACCESS_TOKEN_SECRET);
  $connection->setTimeouts(10, 60);
  try {
    $media = $connection->upload('media/upload', ['media' => 'outputs/map.png']);
    echo " ** media: ", $media->media_id_string;
    $parameters = ['lat' => 13.03886045, 'long' => 101.69978836, 'place_id' => "49c909a0270e8699", 'display_coordinates' => true, 'status' => $aqi->th_en_status, 'media_ids' => $media->media_id_string];
    $result = $connection->post('statuses/update', $parameters);
  } catch (Exception $e) {
    $to      = 'bangkok_aqi_issue@miewliie.dev';
    $subject = '@BangkokAQI Tweet Issue';
    $message = $e->getMessage();

    mail($to, $subject, $message);
  }
}

function setNewDisplayNameFor($aqi) {
  global $CONSUMER_KEY , $CONSUMER_SECRET , $ACCESS_TOKEN,  $ACCESS_TOKEN_SECRET;
  $connection = new TwitterOAuth($CONSUMER_KEY, $CONSUMER_SECRET, $ACCESS_TOKEN, $ACCESS_TOKEN_SECRET);
  $connection->setTimeouts(10, 60);
  try {
    $parameters = ['name' => $aqi->name];
    $result = $connection->post('account/update_profile', $parameters);
  } catch (Exception $e) {
    $to      = 'bangkok_aqi_issue@miewliie.dev';
    $subject = '@BangkokAQI Name Issue';
    $message = $e->getMessage();

    mail($to, $subject, $message);
  }
}

function setNewAvatarFor($aqi) {
  global $CONSUMER_KEY , $CONSUMER_SECRET , $ACCESS_TOKEN,  $ACCESS_TOKEN_SECRET;
  $connection = new TwitterOAuth($CONSUMER_KEY, $CONSUMER_SECRET, $ACCESS_TOKEN, $ACCESS_TOKEN_SECRET);
  $connection->setTimeouts(10, 60);
  try {
    $filename = $aqi->avatar;
    $parameters = [
      'image' => base64_encode(file_get_contents($filename))
      ];
    echo " ** filename: ", $filename;

    $result = $connection->post('account/update_profile_image', $parameters);
  } catch (Exception $e) {
    $to      = 'bangkok_aqi_issue@miewliie.dev';
    $subject = '@BangkokAQI Name Issue';
    $message = $e->getMessage();

    mail($to, $subject, $message);
  }
}

?>