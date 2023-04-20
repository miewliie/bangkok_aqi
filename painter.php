<?php
require_once "aqi.php";

class AQIStation {
  // the station name, to be printed in the map
  var $name;
  // use the background.png image for reference
  var $coordinatePosition;
  // the aqi value
  var $aqi;
  // an array of identifiers used in the aqicn backend
  var $x;
  // the color associated with the AQI set
  var $color;

  function __construct($name, $coordinate, $x) {
    $this->name = $name;
    $this->coordinatePosition = $coordinate;
    $this->x = $x;
  }

  function setAQI($aqi) {
    $this->aqi = $aqi;
    $this->color = colorFor($this->aqi);
  }
}

function createMap() {
  define("IMAGE_WIDTH", 1280);
  define("IMAGE_HEIGHT", 768);
  define("IMG_WIDTH", 1024);
  define("IMG_HEIGHT", 512);

  // array containing all the stations info
  $stations = [];
  array_push($stations, new AQIStation("Bangkok Yai", [590, 330], [1859]));
  array_push($stations, new AQIStation("Pathum Wan", [738, 323], [1857]));
  array_push($stations, new AQIStation("Rat Burana", [640, 500], [1833]));
  array_push($stations, new AQIStation("Phra Pradaeng", [735, 525], [1836, 1841]));
  array_push($stations, new AQIStation("Bang Na", [950, 500], [1834]));
  array_push($stations, new AQIStation("Phaya Thai", [733, 190], [1862,1840]));
  array_push($stations, new AQIStation("Wang Thonglang", [964, 204], [3684]));
  array_push($stations, new AQIStation("Bang Kapi", [1290, 166], [1864]));
  array_push($stations, new AQIStation("Phra Nakhon", [645 ,258], [5773]));
  array_push($stations, new AQIStation("Bang Plat", [625, 90], [1838]));
  array_push($stations, new AQIStation("Yan Nawa", [765, 382], [1813]));

  // Gets the correct, current AQI values to display.
  // aqiDict is a dictionary with guaranteed numeric values
  // on the same of { "x": "value", ...} 
  $aqiDict = getMapAQI();

  // after this function, the stations dictionary is completed
  addAqis($aqiDict, $stations);

  // creating the canvas
  $canvas = imagecreatetruecolor(IMAGE_WIDTH, IMAGE_HEIGHT);

  // filling the canvas with a white background
  imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));

  // coloring
  paintBackground($canvas, $stations);

  // adding top layer
  $topLayer = imagecreatefrompng(__DIR__."/assets/topLayer.png");
  imagecopymerge_alpha($canvas, $topLayer, 0, 0, 0, 0, IMAGE_WIDTH, IMAGE_HEIGHT, 99);
  imagedestroy($topLayer);

  // writing things
  if (function_exists('imagettftext')) {
    paintNames($canvas, $stations);
  }

  $finalImage = imagecreatetruecolor(IMG_WIDTH, IMG_HEIGHT);
  cropPicture($canvas, $finalImage);
  imagedestroy($canvas);

  imagepng($finalImage, __DIR__ . "/outputs/map.png", 9, PNG_ALL_FILTERS);
  imagedestroy($finalImage);
}

function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct){ 
  // creating a cut resource 
  $cut = imagecreatetruecolor($src_w, $src_h); 

  // copying relevant section from background to the cut resource 
  imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h); 
  
  // copying relevant section from watermark to the cut resource 
  imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h); 
  
  // insert cut resource to destination image 
  imagecopymerge($dst_im, $cut, $dst_x, $dst_y, 0, 0, $src_w, $src_h, $pct); 
} 

// paints the background of the given canvas based on the stations
function paintBackground($canvas, $stations) {
  for ($x = 0; $x < IMAGE_WIDTH; $x++) {
    for ($y = 0; $y < IMAGE_HEIGHT; $y++) {
      $point = [$x, $y];
      $colorComponents = computeColorFor($point, $stations);
      imagesetpixel($canvas, $x, $y, imageColorComponentsAllocate($canvas, $colorComponents));
    }
  }
}

// adds the AQI values in the proper stations,
// removes station without a AQI value
function addAqis($aqiDict, &$stations) {
  foreach ($stations as $key => $station) {
    $aqiArray = filterStation($station->x, $aqiDict);
    if (count($aqiArray) > 0) {
      $station->setAQI(computeAverage($aqiArray));
    } else {
      unset($stations[$key]);
    }
  }
}

function paintNames($canvas, $stations) {
  $whiteColor = imagecolorallocate($canvas, 255, 255, 255);
  $font = __DIR__ ."/assets/fonts/SF-Compact-Display-Regular.ttf";
  foreach ($stations as $station) {
    $name = $station->name;
    $centerPoint = $station->coordinatePosition;

    drawAQI($canvas, $station->aqi, $centerPoint, $font, $whiteColor);
    drawName($canvas, $name, $centerPoint, $font, $whiteColor);
  }
}

function drawAQI($canvas, $value, $origin, $font, $color) {
  $textSize = 26;
  $newCenter = [$origin[0], $origin[1] - $textSize/2 - 2];

  $textOrigin = centerFor($newCenter, $textSize, $font, $value);
  imagettftext($canvas, $textSize, 0, $textOrigin[0], $textOrigin[1], $color, $font, $value);
}

function drawName($canvas, $name, $origin, $font, $color) {
  $textSize = 18;
  $newCenter = [$origin[0], $origin[1] + $textSize/2 + 2];
  
  $textOrigin = centerFor($newCenter, $textSize, $font, $name);
  imagettftext($canvas, $textSize, 0, $textOrigin[0], $textOrigin[1], $color, $font, $name);
}

function centerFor($origin, $size, $font, $text) {
  $box = imageTTFBbox($size, 0, $font, $text);
  $width = abs($box[4] - $box[0]);
  $box = imageTTFBbox($size, 0, $font, "0123456789ABCDEFGHILMNOPQRSTUVZabcdefghilmnopqrstuvz");
  $height = abs($box[5] - $box[1]);
  return [round($origin[0] - $width / 2), round($origin[1] + $height / 2)];
}

function filterStation($idxs, $aqiDict) {
  $array = [];
  foreach ($idxs as $idx) {
    $aqiValue = $aqiDict[$idx];
    if ($aqiValue != NULL) {
      array_push($array, $aqiValue);
    }
  }
  return $array;
}


function computeAverage($aqiArray) {
  return round(array_sum($aqiArray) / count($aqiArray));
}

function imageColorComponentsAllocate($canvas, $colorComponents) {
  return imagecolorallocate($canvas, $colorComponents[0], $colorComponents[1], $colorComponents[2]);
}

// return the color for the given point
function computeColorFor($point, $stations) {
  $currentColor = [0,0,0];
  $totalWeight = 0;
  $i = 0;

  foreach ($stations as $station) {
    $distance = distanceBetweenPoints($point, $station->coordinatePosition);

    if ($distance == 0) {
      return [$station->color[0], $station->color[1], $station->color[2]];
    }

    $weight = 1 / pow($distance, 2);

    for ($i=0; $i < 3; $i++) { 
      $currentColor[$i] += $weight * $station->color[$i];
    }
    $totalWeight += $weight;
  }

  return [round($currentColor[0] / $totalWeight), round($currentColor[1] / $totalWeight), round($currentColor[2] / $totalWeight)];
}


function distanceBetweenPoints($a, $b) {
  return distanceBetween($a[0], $a[1], $b[0], $b[1]);
}

function distanceBetween($x1, $y1, $x2, $y2) {
  $x = pow($x2 - $x1, 2);
  $y = pow($y2 - $y1, 2);
  return sqrt($x + $y);
}

function cropPicture($original, $crop) {
  define("ORIGIN_X", 214);
  define("ORIGIN_Y", 46);

  imagecopyresampled($crop, $original, 0, 0, ORIGIN_X, ORIGIN_Y, IMG_WIDTH, IMG_HEIGHT, IMG_WIDTH, IMG_HEIGHT);
}
?>