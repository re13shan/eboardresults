<?php


$fnlist['captcha'] = [

  'init' => function ($args=null) use (&$fnlist) {
    $__ds = &$fnlist['__ds'];

    $app = $args['app'] ?? 'noapp';
    $col = 'captcha';
    $id = 'captcha';

    $wordfile_default = '/vh/g/nixtec/vhosts/eboardresults.com/www/cdn/svc/cgen/rand_nums_4.txt';
    $sendfile_prefix_default = '/captcha/static';

    $wordfile = $args['wordfile'] ?? $wordfile_default;
    $sendfile_prefix = $args['sendfile_prefix'] ?? $sendfile_prefix_default;
    $cwords = explode("\n", trim(file_get_contents($wordfile)));
    $nwords = (int) $cwords[0]; # first line has the word count
    $wordlen = 0;
    for ($i = 1; $i <= $nwords; $i++) {
      $wordlen = max($wordlen, strlen($cwords[$i]));
    }
    #echo "nwords=$nwords, wordlen=$wordlen\n";

    $captcha_file_ext = "jpg"; # need to make it configurable by application
    $captcha_file_mime = "image/jpeg"; # need to make it configurable by application


    $sconfig = [ 'app' => $app, 'id' => $id, 'title' => 'Captcha', 'nrows' => $nwords, 'ncols' => 1, 'cleanup' => false, 'cols' => [ [ 'name' => $col, 'len' => $wordlen ] ] ];

    echo "Configuring Captcha Cache [{$sconfig['title']}]\n";
    $fnlist['cache']['addcfg']($sconfig);

    $curtime = time();

    for ($i = 1; $i <= $nwords; $i++) {
      $key = (string) ($i-1);
      $fnlist['cache']['set']([ 'app' => $app, 'id' => $id, 'key' => $key, 'val' => [ $col => $cwords[$i], '__ts' => $curtime ] ]);
    }
    unset($cwords);

    $__ds[$app][$id]['nwords'] = $nwords;
    $__ds[$app][$id]['file_ext'] = $captcha_file_ext;
    $__ds[$app][$id]['file_mime'] = $captcha_file_mime;
    $__ds[$app][$id]['sendfile_prefix'] = $sendfile_prefix;

    return [ 200, "Captcha initialized" ];
  }, 



  'get' => function ($args=null) use (&$fnlist) {
    $__ds = $fnlist['__ds'];
    $app = $args['app'] ?? 'noapp';
    #$id = $args['id'] ?? 'session';
    $id = 'captcha';
    $key = rand(0, $__ds[$app][$id]['nwords']-1);
    $xargs = [ 'app' => $app, 'id' => $id, 'key' => $key, 'col' => $id ];


    list ($code, $cword) = $fnlist['cache']['get']($xargs);
    #echo "nwords=" . $__ds[$app][$id]['nwords'] . ", key=$key, cword=$cword\n";

    $cinfo = [
      'index' => $key, 'word' => $cword, 'nwords' => $__ds[$app][$id]['nwords'], 'file_ext' => $__ds[$app][$id]['file_ext'], 'file_mime' => $__ds[$app][$id]['file_mime'], 'sendfile_prefix' => $__ds[$app][$id]['sendfile_prefix'],
    ];

    return [ 200, $cinfo ];
  },

];

?>
