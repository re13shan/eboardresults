<?php

$api_config['lo_ips'] = [ '::1', '127.0.0.1' ];
$api_config['smtpy_ips'] = [ '2001:41d0:2:d733::1', '2001:41d0:2:d733::2', '46.105.104.51', '193.70.79.13', '178.32.61.143' ];
$api_config['self_ips'] = array_merge($api_config['lo_ips'], $api_config['smtpy_ips']);

# token is just sha1sum of some random string (not necessarily password)
$api_config['api_users'] = [
  'apiuser' => [
    'pass' => 'pass_text',
    'token' => 'pass_hash', # sha1('pass_text')
    'ipallow' => $api_config['self_ips'],
    'ipdeny' => '*',
  ],
  'demo' => [
    'pass' => 'rG0CN9a6eO51MGX',
    'token' => '9084fc1cee5eb61e25bf99f0261f5b254890e166', # sha1('rG0CN9a6eO51MGX')
    'ipallow' => '*',
    'ipdeny' => '*',
    'rollpattern' => '/123456/',
  ],
];


?>
