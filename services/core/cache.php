<?php

# cache manipulates the core data, so $fnlist is passed as reference
$fnlist['cache'] = [

  'addcfg' => function ($args=null) use (&$fnlist) {
    $__ds = &$fnlist['__ds'];
    /*
     * args: app, id, nrows, cols (cfg)
     */
    $app = $args['app'] ?? 'noapp';
    $ckey = 'cache';
    $cckey = $ckey . '.cfg';
    $id = $args['id'] ?? 'noid';
    if (isset($__ds[$app][$ckey][$id])) {
      return [ 403, "Cache Configuration Already Exists, delcfg first [$id]." ];
    }
    $nrows = $args['nrows'] ?? 1024;


    $__ds[$app][$ckey][$id] = new Swoole\Table($nrows, 1); # conflict proportion = 1
    $tbl = &$__ds[$app][$ckey][$id];

    $i = 0;
    foreach ($args['cols'] as $colcfg) {
      $i++;
      $tbl->column($colcfg['name'],
      isset($colcfg['type']) && $colcfg['type'] == 'num' ? Swoole\Table::TYPE_INT : Swoole\Table::TYPE_STRING,
      $colcfg['len']);
    }
    $tbl->column('__ts', Swoole\Table::TYPE_INT); # php time() or $request->server['request_time']
    $tbl->create();

    $__ds[$app][$cckey][$id] = [ 'app' => $app, 'id' => $id, 'nrows' => $nrows, 'ncols' => $i ];

    return [ 200, "Cache Configuration Added [{$app}/{$ckey}/{$id}]." ];
  },

  'getcfg' => function ($args=null) use (&$fnlist) {
    $__ds = &$fnlist['__ds'];
    /*
     * args: app, id
     */
    $app = $args['app'] ?? 'noapp';
    $ckey = 'cache';
    $cckey = $ckey . '.cfg';
    $id = $args['id'] ?? 'noid';
    #$key = $args['key'] ?? 'nokey';
    if (!isset($__ds[$app][$ckey][$id]) || !isset($__ds[$app][$cckey][$id])) {
      return [ 404, "Cache configuration not found [{$app}/{$ckey}/{$id}]." ];
    }
    return [ 200, $__ds[$app][$cckey][$id] ];
  },


  'set' => function ($args=null) use (&$fnlist) {
    $__ds = &$fnlist['__ds'];
    /*
     * args: app, id, key, val (array)
     */
    $app = $args['app'] ?? 'noapp';
    $ckey = 'cache';
    $id = $args['id'] ?? 'noid';
    $key = $args['key'] ?? 'nokey';
    if (!isset($__ds[$app][$ckey][$id])) {
      return [ 403, "Cache configuration not found [{$app}/{$ckey}/{$id}]." ];
    }
    if (!isset($args['val']['__ts'])) $args['val']['__ts'] = time();
    $tbl = &$__ds[$app][$ckey][$id];
    $tbl->set($key, $args['val']);

    return [ 200, "Cache Entry Set [$key]." ];
  },

  'get' => function ($args=null) use (&$fnlist) {
    $__ds = &$fnlist['__ds'];
    /*
     * args: app, id, key, col
     */
    $app = $args['app'] ?? 'noapp';
    $ckey = 'cache';
    $id = $args['id'] ?? 'noid';
    $key = $args['key'] ?? 'nokey';
    $col = $args['col'] ?? 'nocol';
    if (!isset($__ds[$app][$ckey][$id])) {
      return [ 403, "Cache configuration not found [{$app}/{$ckey}/{$id}]." ];
    }
    $tbl = &$__ds[$app][$ckey][$id];
    $val = $tbl->get($key, $col);
    #var_dump($val);
    if ($val === false) {
      $code = 404;
    } else {
      $code = 200;
    }

    return [ $code, $val ];
  },

  'incr' => function ($args=null) use (&$fnlist) {
    $__ds = &$fnlist['__ds'];
    /*
     * args: app, id, key, col, [incrby]
     */
    $app = $args['app'] ?? 'noapp';
    $ckey = 'cache';
    $id = $args['id'] ?? 'noid';
    $key = $args['key'] ?? 'nokey';
    $col = $args['col'] ?? 'nocol';
    $incrby = $args['incrby'] ?? 1;
    if (!isset($__ds[$app][$ckey][$id])) {
      return [ 403, "Cache configuration not found [{$app}/{$ckey}/{$id}]." ];
    }
    $tbl = &$__ds[$app][$ckey][$id];
    $val = $tbl->incr($key, $col, $incrby);

    return [200, $val];
  },

  'decr' => function ($args=null) use (&$fnlist) {
    $__ds = &$fnlist['__ds'];
    /*
     * args: app, id, key, col, [incrby]
     */
    $app = $args['app'] ?? 'noapp';
    $ckey = 'cache';
    $id = $args['id'] ?? 'noid';
    $key = $args['key'] ?? 'nokey';
    $col = $args['col'] ?? 'nocol';
    $decrby = $args['decrby'] ?? 1;
    if (!isset($__ds[$app][$ckey][$id])) {
      return [ 403, "Cache configuration not found [{$app}/{$ckey}/{$id}]." ];
    }
    $tbl = &$__ds[$app][$ckey][$id];
    $val = $tbl->incr($key, $col, $decrby);

    return [ 200, $val ];
  },

  'exists' => function ($args=null) use (&$fnlist) {
    $__ds = &$fnlist['__ds'];
    /*
     * args: app, id, key
     */
    $app = $args['app'] ?? 'noapp';
    $ckey = 'cache';
    $id = $args['id'] ?? 'noid';
    $key = $args['key'] ?? 'nokey';
    if (!isset($__ds[$app][$ckey][$id])) {
      return [ 403, "Cache configuration not found [{$app}/{$ckey}/{$id}]." ];
    }
    $tbl = &$__ds[$app][$ckey][$id];
    $bool = $tbl->exists($key);

    return [ 200, $bool ];
  },

  'count' => function ($args=null) use (&$fnlist) {
    $__ds = &$fnlist['__ds'];
    /*
     * args: app, id
     */
    $app = $args['app'] ?? 'noapp';
    $ckey = 'cache';
    $id = $args['id'] ?? 'noid';
    if (!isset($__ds[$app][$ckey][$id])) {
      return [ 403, "Cache configuration not found [{$app}/{$ckey}/{$id}]." ];
    }
    $tbl = &$__ds[$app][$ckey][$id];
    $cnt = $tbl->count();

    return [ 200, $cnt ];
  },

  'del' => function ($args=null) use (&$fnlist) {
    $__ds = &$fnlist['__ds'];
    /*
     * args: app, id, key
     */
    $app = $args['app'] ?? 'noapp';
    $ckey = 'cache';
    $id = $args['id'] ?? 'noid';
    $key = $args['key'] ?? 'nokey';
    if (!isset($__ds[$app][$ckey][$id])) {
      return [ 403, "Cache configuration not found [{$app}/{$ckey}/{$id}]." ];
    }
    $tbl = &$__ds[$app][$ckey][$id];
    $tbl->del($key);

    return [ 200, "Cache Entry Deleted [$key]." ];
  },

  'dump' => function ($args=null) use (&$fnlist) {
    $__ds = &$fnlist['__ds'];
    /*
     * args: app, id
     */
    $app = $args['app'] ?? 'noapp';
    $ckey = 'cache';
    $id = $args['id'] ?? 'noid';
    if (!isset($__ds[$app][$ckey][$id])) {
      return [ 403, "Cache configuration not found [{$app}/{$ckey}/{$id}]." ];
    }
    $tbl = &$__ds[$app][$ckey][$id];

    $rows = [];
    $tbl->rewind();
    while ($tbl->valid()) {
      $rows[] = [ $tbl->key(), $tbl->current() ];
      $tbl->next();
    }

    return [ 200, $rows ];
  },


  'reset' => function ($args=null) use (&$fnlist) {
    $__ds = &$fnlist['__ds'];
    /*
     * args: app, id
     */
    $app = $args['app'] ?? 'noapp';
    $ckey = 'cache';
    $id = $args['id'] ?? 'noid';
    if (!isset($__ds[$app][$ckey][$id])) {
      return [ 403, "Cache configuration not found [{$app}/{$ckey}/{$id}]." ];
    }
    $tbl = &$__ds[$app][$ckey][$id];

    $bnr = $tbl->count();
    $tbl->rewind();
    while ($tbl->valid()) {
      $key = $tbl->key();
      $tbl->del($key);
      $tbl->next();
    }
    $anr = $tbl->count();

    return [ 200, "Cache has been reset [${bnr}->${anr}]." ];
  },

  'expire' => function ($args=null) use (&$fnlist) {
    $__ds = &$fnlist['__ds'];
    /*
     * args: app, id
     */
    $app = $args['app'] ?? 'noapp';
    $ckey = 'cache';
    $id = $args['id'] ?? 'noid';
    if (!isset($__ds[$app][$ckey][$id])) {
      return [ 403, "Cache configuration not found [{$app}/{$ckey}/{$id}]." ];
    }
    $tbl = &$__ds[$app][$ckey][$id];

    $exptime = $args['expire'] ?? 60;
    $curtime = time();

    $bnr = $tbl->count();
    $tbl->rewind();
    while ($tbl->valid()) {
      $key = $tbl->key();
      $row = $tbl->current();
      if (($curtime - $row['__ts']) > $exptime) {
	#print_r($row);
        $tbl->del($key);
      }
      $tbl->next();
    }
    $anr = $tbl->count();

    return [ 200, "Expired [$exptime] Cache entries have been removed [${bnr}->${anr}]." ];
  },


  # call this only if you don't 'addcfg' again, if you want to 'addcfg' same 'id' then rather call 'reset'
  'delcfg' => function ($args=null) use (&$fnlist) {
    $__ds = &$fnlist['__ds'];
    /*
     * args: app, id
     */
    $app = $args['app'] ?? 'noapp';
    $ckey = 'cache';
    $id = $args['id'] ?? 'noid';
    if (isset($__ds[$app][$ckey][$id])) {
      $__ds[$app][$ckey][$id]->destroy();
      unset($__ds[$app][$ckey][$id]);
    }

    return [ 200, "Cache Configuration Deleted [{$app}/{$ckey}/{$id}]." ];
  },

  # implement cache cleanup routine


];

?>
