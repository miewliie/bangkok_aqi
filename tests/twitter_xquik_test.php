<?php
require_once __DIR__ . "/../twitter.php";

function assertSameValue($expected, $actual, $label) {
  if ($expected !== $actual) {
    throw new Exception($label . " failed");
  }
}

function assertTrueValue($value, $label) {
  if (!$value) {
    throw new Exception($label . " failed");
  }
}

putenv('XQUIK_ACCOUNT=bangkokaqi');

$aqi = (object) [
  'th_en_status' => "AQI update",
];

assertTrueValue(isXquikBackendValue(' xquik '), 'xquik backend detection');
assertSameValue(false, isXquikBackendValue('twitteroauth'), 'default backend detection');
assertSameValue(
  'https://example.com/api/v1/x/tweets',
  xquikEndpoint('/x/tweets', 'https://example.com/api/v1/'),
  'endpoint builder'
);
assertSameValue(
  ['account' => 'bangkokaqi', 'text' => 'AQI update'],
  xquikTweetPayload($aqi),
  'tweet payload without media'
);
assertSameValue(
  ['account' => 'bangkokaqi', 'text' => 'AQI update', 'media' => ['https://example.com/map.png']],
  xquikTweetPayload($aqi, 'https://example.com/map.png'),
  'tweet payload with media'
);
assertSameValue('tw_123', xquikAcceptedId((object) ['tweetId' => 'tw_123']), 'tweet id selection');
assertSameValue('42', xquikAcceptedId((object) ['writeActionId' => '42']), 'write action id selection');
assertSameValue('accepted', xquikAcceptedId((object) []), 'accepted fallback');

?>
