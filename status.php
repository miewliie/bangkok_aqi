<?php
require_once "aqi.php";

function statusFor($aqi) {
  return 'ดัชนีคุณภาพอากาศ (AQI): ' . $aqi . " " . emojiFor($aqi) . "\n" . messageFor($aqi);
}

function statusEnFor($aqi) {
  switch (true) {
    case $aqi <= LIMIT_GOOD: return "Good";
    case $aqi <= LIMIT_MODERATE: return "Moderate";
    case $aqi <= LIMIT_UNHEALTHY_SENSITIVE: return "Unhealthy for Sensitive Groups";
    case $aqi <= LIMIT_UNHEALTHY: return "Unhealthy";
    case $aqi <= LIMIT_VERY_UNHEALTY: return "Very Unhealthy";
    default: return "Hazardous";
  }
}

function statusThFor($aqi) {
  switch (true) {
    case $aqi <= LIMIT_GOOD: return "คุณภาพดี";
    case $aqi <= LIMIT_MODERATE: return "คุณภาพปานกลาง";
    case $aqi <= LIMIT_UNHEALTHY_SENSITIVE: return "มีผลกระทบต่อสุขภาพ";
    case $aqi <= LIMIT_UNHEALTHY: return "มีผลกระทบต่อสุขภาพ";
    case $aqi <= LIMIT_VERY_UNHEALTY: return "มีผลกระทบต่อสุขภาพมาก";
    default: return "อันตราย";
  }
}

function messageFor($aqi) {
  $emoji = emojiFor($aqi);
  $thaiMessage = statusThFor($aqi);
  $engMessage = statusEnFor($aqi);

  return $emoji . " " . $thaiMessage . "\n" . $emoji . " " . $engMessage;
}
?>