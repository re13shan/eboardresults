<?php


$fnlist['session'] = [

  'init' => function ($args=null) use ($fnlist) {
    # app, nsessions, sesslen
    $app = $args['app'] ?? 'noapp';
    $nsessions = $args['nsessions'] ?? 1000;
    $sesslen = $args['sesslen'] ?? 1024;
    $col = 'sess';
    $sconfig = ['app' => $app, 'id' => 'session', 'title' => 'Session', 'nrows' => $nsessions, 'ncols' => 1, 'cols' => [['name' => $col, 'len' => $sesslen]]];

    echo "Configuring Session [{$sconfig['title']}]\n";
    return $fnlist['cache']['addcfg']($sconfig);
  }, 

  'start' => function ($args=null) use ($fnlist) {
    $sidname = $args['sidname'] ?? 'NOSESSID'; # session keyname (EBSESSID, etc.)
    $request = &$args['request'];
    $response = &$args['response'];
    $sid = $request->cookie[$sidname] ?? null;
    $app = $args['app'] ?? 'noapp';
    #$id = $args['id'] ?? 'session';
    $id = 'session';
    #$col = $args['col'] ?? 'sess'; # which column has the session string
    $col = 'sess'; # which column has the session string
    $sidpath = $args['sidpath'] ?? '/';
    $expire = $args['expire'] ?? 0; # 0=don't expire, otherwise integer seconds to expire
    $curtime = $request->server['request_time'] ?? time();
    $xargs = [ 'app' => $app, 'id' => $id, 'key' => $sid, 'col' => $col ];

    #echo "expire=$expire\n";
    #print_r($args);


    #$sid = 'sid1234';

    #echo "sid=$sid\n";
    $xargs['col'] = '__ts'; # for timestamp checking
    if (($sid == null)
      || ((list($code, $bool) = $fnlist['cache']['exists']($xargs)) && $bool === false)
      || ($expire != 0 ? (((list($code, $__ts) = $fnlist['cache']['get']($xargs)) && (($curtime - $__ts) > $expire)) && ($fnlist['cache']['del']($xargs) || true)) : false)
      ) {
      /*
      if ($sid == null) {
	echo "Initializing New Session\n";
      } else {
	echo "Expiring old session Data [sid=$sid] and Initializing New Session\n";
      }
      */
      do {
        $sid = uniqid($app) . rand(100000, 999999);
	$xargs['key'] = $sid;
	$xargs['col'] = $col;
        list($code, $bool) = $fnlist['cache']['exists']($xargs);
      } while ($bool === true);
      #echo "new sid=$sid\n";
      $xargs['key'] = $sid;
      $xargs['col'] = $col;
      $xargs['val'] = [ $col => serialize([]), '__ts' => $curtime ];
      $fnlist['cache']['set']($xargs);
      $sdata = [];
    } else {
      #echo "Session exists [sid=$sid], using it\n";
      $xargs['col'] = $col;
      list($code, $str) = $fnlist['cache']['get']($xargs);
      #$sdata = [];
      $sdata = unserialize($str);
    }

    #echo "resp [$sidname] [$sid] [$sidpath]\n";

    $response->cookie($sidname, $sid, 0, $sidpath);

    return [ 200, [ $sid, $sdata ] ];

  },

  'save' => function ($args=null) use ($fnlist) {
    $request = &$args['request'];
    #$response = &$args['response'];
    $app = $args['app'] ?? 'noapp';
    #$id = $args['id'] ?? 'session';
    $id = 'session';
    #$col = $args['col'] ?? 'sess'; # which column has the session string
    $col = 'sess'; # which column has the session string
    $sid = $args['sid'] ?? 'nosid';
    $sdata = $args['sdata'];
    $xargs = [ 'app' => $app, 'id' => $id, 'key' => $sid ];
    $curtime = $request->server['request_time'] ?? time();

    $xargs['val'] = [ $col => serialize($sdata), '__ts' => $curtime ];
    return $fnlist['cache']['set']($xargs);

  },

  'destroy' => function ($args=null) use ($fnlist) {
    $app = $args['app'] ?? 'noapp';
    #$id = $args['id'] ?? 'session';
    $id = 'session';
    $sid = $args['sid'] ?? 'nosid';
    $xargs = [ 'app' => $app, 'id' => $id, 'key' => $sid ];

    return $fnlist['cache']['del']($xargs);

  },

  'expire' => function ($args=null) use ($fnlist) {
    # args: app
    $app = $args['app'] ?? 'noapp';
    #$id = $args['id'] ?? 'session';
    $id = 'session';
    #$sid = $args['sid'] ?? 'nosid';
    $xargs = [ 'app' => $app, 'id' => $id, 'expire' => $args['expire'] ?? 60 ];

    return $fnlist['cache']['expire']($xargs);

  },



];

?>
