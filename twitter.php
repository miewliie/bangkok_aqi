<?php
use Abraham\TwitterOAuth\TwitterOAuth;

$autoload = __DIR__ . "/vendor/autoload.php";
if (file_exists($autoload)) {
  require $autoload;
}

$CONSUMER_KEY = getenv('CONSUMER_KEY');
$CONSUMER_SECRET = getenv('CONSUMER_SECRET');
$ACCESS_TOKEN = getenv('ACCESS_TOKEN');
$ACCESS_TOKEN_SECRET = getenv('ACCESS_TOKEN_SECRET');

if (isset($_SERVER["SCRIPT_FILENAME"]) && realpath($_SERVER["SCRIPT_FILENAME"]) === __FILE__) {
  main();
}

function main() {
  $aqi = getAQI();

  sendNewStatusFor($aqi);
  if (!isXquikBackend()) {
    setNewDisplayNameFor($aqi);
    setNewAvatarFor($aqi);
  }
}

function getAQI(){
  // Read the JSON file contents into a string
  $json_string = file_get_contents('outputs/aqi-outputs.json');
  // Parse the JSON string into a PHP object
  $data = json_decode($json_string);
  return $data;
}

function sendNewStatusFor($aqi) {
  if (isXquikBackend()) {
    sendNewStatusWithXquik($aqi);
    return;
  }

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

function isXquikBackend() {
  return isXquikBackendValue(getenv('TWITTER_BACKEND'));
}

function isXquikBackendValue($backend) {
  return strtolower(trim((string) $backend)) === 'xquik';
}

function xquikBaseUrl() {
  $baseUrl = trim((string) getenv('XQUIK_BASE_URL'));
  if ($baseUrl === '') {
    $baseUrl = 'https://xquik.com/api/v1';
  }
  return rtrim($baseUrl, '/');
}

function xquikEndpoint($path, $baseUrl = null) {
  $base = $baseUrl === null ? xquikBaseUrl() : rtrim((string) $baseUrl, '/');
  return $base . '/' . ltrim($path, '/');
}

function xquikAccount() {
  return trim((string) getenv('XQUIK_ACCOUNT'));
}

function xquikApiKey() {
  return trim((string) getenv('XQUIK_API_KEY'));
}

function requireXquikEnv() {
  $missing = [];
  if (xquikApiKey() === '') {
    $missing[] = 'XQUIK_API_KEY';
  }
  if (xquikAccount() === '') {
    $missing[] = 'XQUIK_ACCOUNT';
  }
  if (count($missing) > 0) {
    throw new Exception('Missing required environment variables: ' . implode(', ', $missing));
  }
}

function xquikTweetPayload($aqi, $mediaUrl = null) {
  $payload = [
    'account' => xquikAccount(),
    'text' => $aqi->th_en_status,
  ];
  if ($mediaUrl !== null && $mediaUrl !== '') {
    $payload['media'] = [$mediaUrl];
  }
  return $payload;
}

function xquikMimeType($filePath) {
  if (function_exists('mime_content_type')) {
    $mimeType = mime_content_type($filePath);
    if (is_string($mimeType) && $mimeType !== '') {
      return $mimeType;
    }
  }
  return 'image/png';
}

function sendNewStatusWithXquik($aqi) {
  requireXquikEnv();
  $mediaUrl = uploadXquikMedia($aqi->map);
  $result = xquikJsonRequest('/x/tweets', xquikTweetPayload($aqi, $mediaUrl));
  $id = xquikAcceptedId($result);
  echo " ** xquik status: ", $id;
}

function xquikAcceptedId($result) {
  if (property_exists($result, 'tweetId') && $result->tweetId !== '') {
    return $result->tweetId;
  }
  if (property_exists($result, 'writeActionId') && $result->writeActionId !== '') {
    return $result->writeActionId;
  }
  return 'accepted';
}

function uploadXquikMedia($filePath) {
  if (!file_exists($filePath)) {
    return null;
  }

  $result = xquikMultipartRequest('/x/media', [
    'account' => xquikAccount(),
    'file' => new CURLFile($filePath, xquikMimeType($filePath), basename($filePath)),
  ]);
  return property_exists($result, 'mediaUrl') ? $result->mediaUrl : null;
}

function xquikJsonRequest($path, $payload) {
  $json = json_encode($payload);
  if ($json === false) {
    throw new Exception('Could not encode Xquik request payload.');
  }
  return xquikRequest($path, $json, ['Content-Type: application/json']);
}

function xquikMultipartRequest($path, $postFields) {
  return xquikRequest($path, $postFields, []);
}

function xquikRequest($path, $postFields, $headers) {
  if (!function_exists('curl_init')) {
    throw new Exception('PHP curl extension is required for Xquik posting.');
  }

  $requestHeaders = array_merge($headers, ['x-api-key: ' . xquikApiKey()]);
  $handle = curl_init(xquikEndpoint($path));
  curl_setopt($handle, CURLOPT_POST, true);
  curl_setopt($handle, CURLOPT_POSTFIELDS, $postFields);
  curl_setopt($handle, CURLOPT_HTTPHEADER, $requestHeaders);
  curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($handle, CURLOPT_TIMEOUT, 60);

  $body = curl_exec($handle);
  if ($body === false) {
    $error = curl_error($handle);
    curl_close($handle);
    throw new Exception($error);
  }

  $statusCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
  curl_close($handle);
  $decoded = json_decode($body);
  if ($statusCode < 200 || $statusCode >= 300) {
    $message = is_object($decoded) && property_exists($decoded, 'message')
      ? $decoded->message
      : 'Xquik request failed with status ' . $statusCode;
    throw new Exception($message);
  }

  return is_object($decoded) ? $decoded : (object) [];
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
