<?php
require_once "aqi.php";

function nameFor($aqi) {
  return "☁️ Bangkok AQI " . emojiFor($aqi);
}

?>