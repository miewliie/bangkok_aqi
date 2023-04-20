<?php
require "aqi.php";
require "status.php";
require "painter.php";
require "name.php";
require "avatar.php";


$aqi = currentAQI();

if (!is_numeric($aqi) || is_nan($aqi)) {
  exit();
}

createMap();
$mapFilePath = "./outputs/map.png";
$nameAndEmoji = nameFor($aqi);
$statusDetails = statusFor($aqi);
$thaiStatus = statusThFor($aqi);
$engStatus = statusEnFor($aqi);
$avatarFilePath = avatarFor($aqi);
  
$jsonData = [
      "name" => $nameAndEmoji,
      "th_en_status" => $statusDetails,
      "thai_status" => $thaiStatus,
      "eng_status" => $engStatus,
      "avatar" => $avatarFilePath,
      "map" => $mapFilePath
];

$path = './outputs/aqi-outputs.json';
$jsonString = json_encode($jsonData, JSON_UNESCAPED_UNICODE);
$fp = fopen($path, 'w');
fwrite($fp, $jsonString);
fclose($fp);

?>