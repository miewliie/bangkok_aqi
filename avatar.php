<?php 
require_once "aqi.php";

function avatarFor($aqi) {
    switch (true) {
      case $aqi <= LIMIT_GOOD: return "./assets/avatar/good.gif";
      case $aqi <= LIMIT_MODERATE: return "./assets/avatar/moderate.gif";
      case $aqi <= LIMIT_UNHEALTHY_SENSITIVE: return "./assets/avatar/unhealthySensitive.gif";
      case $aqi <= LIMIT_UNHEALTHY: return "./assets/avatar/unhealthy.gif";
      case $aqi <= LIMIT_VERY_UNHEALTY: return "./assets/avatar/veryUnhealthy.gif";
      default: return "./assets/avatar/hazardous.gif";
    }
  }

?>
