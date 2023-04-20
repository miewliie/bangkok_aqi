<?php
require "./vendor/autoload.php";
require "aqi.php";
require "status.php";
require "painter.php";
require "name.php";
require "avatar.php";

use Abraham\TwitterOAuth\TwitterOAuth;


$CONSUMER_KEY = getenv('CONSUMER_KEY');
$CONSUMER_SECRET = getenv('CONSUMER_SECRET');
$ACCESS_TOKEN = getenv('ACCESS_TOKEN');
$ACCESS_TOKEN_SECRET = getenv('ACCESS_TOKEN_SECRET');
$EMAIL_FOR_ISSUE = getenv('EMAIL_FOR_ISSUE');


$aqi = currentAQI();
if (!is_numeric($aqi) || is_nan($aqi)) {
  exit();
}

sendNewStatusFor($aqi);
setNewDisplayNameFor($aqi);
setNewAvatarFor($aqi);

function sendNewStatusFor($aqi) {
  createMap();
  global $CONSUMER_KEY , $CONSUMER_SECRET , $ACCESS_TOKEN,  $ACCESS_TOKEN_SECRET, $EMAIL_FOR_ISSUE;
  $connection = new TwitterOAuth($CONSUMER_KEY, $CONSUMER_SECRET, $ACCESS_TOKEN, $ACCESS_TOKEN_SECRET);
  $connection->setTimeouts(10, 60);
  try {
    $media = $connection->upload('media/upload', ['media' => './assets/map.png']);
    $parameters = ['lat' => 13.03886045, 'long' => 101.69978836, 'place_id' => "49c909a0270e8699", 'display_coordinates' => true, 'status' => statusFor($aqi), 'media_ids' => $media->media_id_string];
    $result = $connection->post('statuses/update', $parameters);
  } catch (Exception $e) {
    $to      = $EMAIL_FOR_ISSUE;
    $subject = '@BangkokAQI Tweet Issue';
    $message = $e->getMessage();

    mail($to, $subject, $message);
  }
}

function setNewDisplayNameFor($aqi) {
  global $CONSUMER_KEY , $CONSUMER_SECRET , $ACCESS_TOKEN,  $ACCESS_TOKEN_SECRET, $EMAIL_FOR_ISSUE;
  $connection = new TwitterOAuth($CONSUMER_KEY, $CONSUMER_SECRET, $ACCESS_TOKEN, $ACCESS_TOKEN_SECRET);
  $connection->setTimeouts(10, 60);
  try {
    $parameters = ['name' => nameFor($aqi)];
    $result = $connection->post('account/update_profile', $parameters);
  } catch (Exception $e) {
    $to      = $EMAIL_FOR_ISSUE;
    $subject = '@BangkokAQI Name Issue';
    $message = $e->getMessage();

    mail($to, $subject, $message);
  }
}

function setNewAvatarFor($aqi) {
  global $CONSUMER_KEY , $CONSUMER_SECRET , $ACCESS_TOKEN,  $ACCESS_TOKEN_SECRET, $EMAIL_FOR_ISSUE;
  $connection = new TwitterOAuth($CONSUMER_KEY, $CONSUMER_SECRET, $ACCESS_TOKEN, $ACCESS_TOKEN_SECRET);
  $connection->setTimeouts(10, 60);
  try {
    $filename = avatarFor($aqi);
    $parameters = [
      'image' => base64_encode(file_get_contents($filename))
      ];
    echo "** filename: ", $filename;

    $result = $connection->post('account/update_profile_image', $parameters);
  } catch (Exception $e) {
    $to      = $EMAIL_FOR_ISSUE;
    $subject = '@BangkokAQI Name Issue';
    $message = $e->getMessage();

    mail($to, $subject, $message);
  }
}
?>