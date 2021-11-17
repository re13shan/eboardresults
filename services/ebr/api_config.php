<?php

$api_config['lo_ips'] = [ '::1', '127.0.0.1' ];
$api_config['smtpy_ips'] = [ '2001:41d0:2:d733::1', '2001:41d0:2:d733::2', '46.105.104.51', '193.70.79.13', '178.32.61.143' ];
$api_config['self_ips'] = array_merge($api_config['lo_ips'], $api_config['smtpy_ips']);

# token is just sha1sum of some random string (not necessarily password)
$api_config['api_users'] = [
  'bisedhk' => [
    'pass' => 'j23W7dzw6ht79vV',
    'token' => '172179e3fc7cdde3c89c1b85bc62720411f479cf', # sha1('9jdWH2Ap24ak53F')
    'ipallow' => $api_config['self_ips'],
    'ipdeny' => '*',
  ],
  'bmeb' => [
    'pass' => '5FXAfkekshSW8FH',
    'token' => '5f69db99548972d91b36e49ec89a61edd934d294', # sha1('d26R856S8Q76fnf')
    'ipallow' => $api_config['self_ips'],
    'ipdeny' => '*',
  ],
  'jb' => [
    'pass' => 'w4v9FyF278X7T5n',
    'token' => '3cc8d2b1980383c2cbefea72bdbcccdee8c9580c', # sha1('sq4BkG493tX46Ye')
    'ipallow' => $api_config['self_ips'],
    'ipdeny' => '*',
  ],
  'bisesylapp' => [
    'pass' => 'w72JiACr8dEh3Zys',
    'token' => 'df9356688369b698a31a7b3324ccfe597efa47fc', # sha1('7X2FC4B176uRVn7B')
    'ipallow' => $api_config['self_ips'],
    'ipdeny' => '*',
  ],
  'rb' => [
    'pass' => 'nK8xJ4kR2iT3yJ8o',
    'token' => '3ac1a82d4186be8b60e7dde2bf518e0ec1f020ad', # sha1('nK8xJ4kR2iT3yJ8o')
    'ipallow' => $api_config['self_ips'],
    'ipdeny' => '*',
  ],
  'nixtec' => [
    'pass' => 'sRYBCwJgyPf92NX',
    'token' => '5caad4573828ef2dc56a57e722d1fac2909698cd', # sha1('sRYBCwJgyPf92NX')
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
