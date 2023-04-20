<?php
// Wishlist:
// - river color changes throughout the day (dark at night, light during the day)
// - (accessibility feature) text color turns black when background is too light
define("LIMIT_GOOD", 50);
define("LIMIT_MODERATE", 100);
define("LIMIT_UNHEALTHY_SENSITIVE", 150);
define("LIMIT_UNHEALTHY", 200);
define("LIMIT_VERY_UNHEALTY", 300);
define("AQICN_TOKEN", getenv('AQICN_TOKEN'));

function emojiFor($aqi) {
  switch (true) {
    case $aqi <= LIMIT_GOOD: return "💚";
    case $aqi <= LIMIT_MODERATE: return "💛";
    case $aqi <= LIMIT_UNHEALTHY_SENSITIVE: return "🔶";
    case $aqi <= LIMIT_UNHEALTHY: return "🛑";
    case $aqi <= LIMIT_VERY_UNHEALTY: return "😈";
    default: return "👿";
  }
}

// returns a color based on the AQI passed
function colorFor($aqi) {
  $baseColor = baseColorFor($aqi);
  $nextLevelColor = nextLevelColorFor($aqi);
  $percentage = percentageFor($aqi);

  $newColor = [];
  for ($i=0; $i < count($baseColor); $i++) { 
    $colorComponentsDifference = $nextLevelColor[$i] - $baseColor[$i];
    $proposedColor = $baseColor[$i] + round(($percentage / 100) * $colorComponentsDifference);
    $newColor[$i] = min(255, max(0, $proposedColor));
  }

  return $newColor;
}

function baseColorFor($aqi) {
  switch (true) {
    case $aqi <= LIMIT_GOOD: return [0, 153, 102];
    case $aqi <= LIMIT_MODERATE: return [255, 222, 51];
    case $aqi <= LIMIT_UNHEALTHY_SENSITIVE: return [255, 153, 51];
    case $aqi <= LIMIT_UNHEALTHY: return [204, 0, 51];
    case $aqi <= LIMIT_VERY_UNHEALTY: return [102, 0 ,153];
    case $aqi > LIMIT_VERY_UNHEALTY: return [126, 0, 35];
    default: return [170, 170, 170];
  }
}

function nextLevelColorFor($aqi) {
  switch (true) {
    case $aqi <= LIMIT_GOOD: return [255, 222, 51];
    case $aqi <= LIMIT_MODERATE: return [255, 153, 51];
    case $aqi <= LIMIT_UNHEALTHY_SENSITIVE: return [204, 0, 51];
    case $aqi <= LIMIT_UNHEALTHY: return [102, 0 ,153];
    case $aqi <= LIMIT_VERY_UNHEALTY: return [126, 0, 35];
    case $aqi > LIMIT_VERY_UNHEALTY: return [0, 0, 0];
  }
}

// % to the next limit
// (nextLevelLimit - currentLimitBase) : 100 = (value - currentLimitBase) : x
function percentageFor($aqi) {
  switch (true) {
    case $aqi <= LIMIT_GOOD: return percentageFormula(LIMIT_GOOD, 0, $aqi);
    case $aqi <= LIMIT_MODERATE: return percentageFormula(LIMIT_MODERATE, LIMIT_GOOD, $aqi);
    case $aqi <= LIMIT_UNHEALTHY_SENSITIVE: return percentageFormula(LIMIT_UNHEALTHY_SENSITIVE, LIMIT_MODERATE, $aqi);
    case $aqi <= LIMIT_UNHEALTHY: return percentageFormula(LIMIT_UNHEALTHY, LIMIT_UNHEALTHY_SENSITIVE, $aqi);
    case $aqi <= LIMIT_VERY_UNHEALTY: return percentageFormula(LIMIT_VERY_UNHEALTY, LIMIT_UNHEALTHY, $aqi);
    case $aqi > LIMIT_VERY_UNHEALTY: return percentageFormula(1400, LIMIT_VERY_UNHEALTY, $aqi);
  }
}

function percentageFormula($nextLimit, $currentLimit, $value) {
  return min(100, 100 * ($value - $currentLimit) / ($nextLimit - $currentLimit));
}

function currentAQI() {
  $newAQIarray = array_values(getNewAQIForMap());
  $filteredArray = [];
  foreach ($newAQIarray as $key => $value) {
    if (is_numeric($value)) {
      array_push($filteredArray, $value);
    }
  }
  return getAverage($filteredArray);
}

function getAverage($array) {
  return round(array_sum($array) / count($array));
}

function getCurrentAQIFor($name) {
  $url = "https://api.waqi.info/feed/".$name."/?token=" . AQICN_TOKEN;
  $json = json_decode(file_get_contents($url), true);
  return $json["data"]["aqi"];
}

function getMapAQI() {
  $latestAQI = getNewAQIForMap();
  $legacyAQI = fetchOldAQIForMap();
  return validateAndFilterAQI($latestAQI, $legacyAQI);
}

function storeAQI($data) {
  $fp = fopen(__DIR__.'/assets/data.json', 'w');
  fwrite($fp, json_encode($data));
  fclose($fp);
}

// important note:
// internetAQI is a dictionary like { "x": "value", ... }
// oldAQI is a dictionary like { "x": ["value", "value", "value"], ... }
// returns a dictionary like { "x": "value", ... }
function validateAndFilterAQI($internetAQI, $oldAQI) {
  // cleanup from old code
  foreach ($oldAQI as $key => $value) {
    if (!is_array($value)) {
      $oldAQI[$key] = [];
    }
  }

  // the following mess is because PHP can't distinguish between string and integers keys...
  $ALLoldAQIValues = array_values($oldAQI);
  $ALLoldAQIKeys = array_keys($oldAQI);

  $filterAQI = [];
  // checking data integrity of the latest fetched data
  foreach ($internetAQI as $key => $value) {
    $oldAQIValues = $ALLoldAQIValues[array_search($key, $ALLoldAQIKeys)];

    if (is_numeric($value) && valueIsValid($value, $oldAQIValues)) {
      $filterAQI[$key] = $value;
      if (is_array($oldAQIValues)) {
        if (count($oldAQIValues) > 2) {
          array_pop($oldAQIValues);
        }
        array_unshift($oldAQIValues, $value);
        $oldAQI[$key] = $oldAQIValues;
      } else {
        $oldAQI[$key] = [$value];
      }
    }
  }
  storeAQI($oldAQI);

  if (count($filterAQI) == 0) {
    $filterAQI[5773] = currentAQI();
  }
  return $filterAQI;
}

// makes sure that the value is not the same as previous values
function valueIsValid($value, $oldValues) {
  // return true if new value is different than the previous one
  // or if the previous 3 values are different

  return !is_array($oldValues) || count($oldValues) < 3 || $value != $oldValues[0] || ( $oldValues[0] != $oldValues[1] && $oldValues[1] != $oldValues[2] );
}

// Downloads and parses the map AQI json.
// It returns a dictionary with the AQI station idx as keys and the aqi value as the value 
// ⚠️ important: 
//   - both keys and the values at this point are strings
function getNewAQIForMap() {
  $url = "https://api.waqi.info/mapq/bounds/?bounds=13,100,14,101&token=" . AQICN_TOKEN;
  $json = json_decode(curl_get_contents($url), true);
  $array = [];
  for ($i = 0; $i < count($json); $i++) { 
    $obj = $json[$i];
    $array[$obj["x"]] = $obj["aqi"];
  }

  return $array;
}

function curl_get_contents($url) {
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
  $data = curl_exec($ch);
  curl_close($ch);
  return $data;
}

// just parses the stored json and return the dictionary in it.
function fetchOldAQIForMap() {
  $url = __DIR__.'/assets/data.json';
  return (array) json_decode(file_get_contents($url));
}
?>