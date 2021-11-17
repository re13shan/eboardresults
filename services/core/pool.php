<?php

# cache manipulates the core data, so $fnlist is passed as reference
$fnlist['pool'] = [

  # must be invoked out of coroutine context
  'addcfg' => function ($args=null) use (&$fnlist) {
    $__ds = &$fnlist['__ds'];
    /*
     * args: app, id, type, cfg
     */
    $app = $args['app'] ?? 'noapp';
    $ckey = 'pool';
    $id = $args['id'] ?? 'noid';
    if (isset($__ds[$app][$ckey][$id])) {
      return [ 403, "Pool Configuration Already Exists, delcfg first [$id]." ];
    }

    $type = $args['type']; # mysql/redis/etc.
    $cfg = $args['cfg']; # must be a Swoole\Database\MysqliConfig object instance
    switch ($type) {
      case 'mysqli':
      case 'mysql':
      $__ds[$app][$ckey][$id] = new Swoole\Database\MysqliPool($cfg);
      break;
      default:
      return [ 403, "Unrecognised Pool Configuration type [{$type}]." ];
      break;
    }
    return [ 200, "Pool Configuration Added [{$app}/{$ckey}/{$id}]." ];
  },

  # must be called from within coroutine context
  'get' => function ($args=null) use (&$fnlist) {
    $__ds = &$fnlist['__ds'];
    /*
     * args: app, id
     */
    $app = $args['app'] ?? 'noapp';
    $ckey = 'pool';
    $id = $args['id'] ?? 'noid';
    if (!isset($__ds[$app][$ckey][$id])) {
      return [ 403, "Pool configuration not found [{$app}/{$ckey}/{$id}]." ];
    }

    return [ 200, $__ds[$app][$ckey][$id]->get() ];
  },

  'put' => function ($args=null) use (&$fnlist) {
    $__ds = &$fnlist['__ds'];
    /*
     * args: app, id, handle
     */
    $app = $args['app'] ?? 'noapp';
    $ckey = 'pool';
    $id = $args['id'] ?? 'noid';
    $handle = $args['handle'] ?? null;
    if (!$handle || !isset($__ds[$app][$ckey][$id])) {
      return [ 403, "Empty handle or Pool configuration not found [{$app}/{$ckey}/{$id}]." ];
    }

    return [ 200, $__ds[$app][$ckey][$id]->put($handle) ];
  },

  # you don't need to call this service at all
  # close all connection of pool
  'close' => function ($args=null) use (&$fnlist) {
    $__ds = &$fnlist['__ds'];
    /*
     * args: app, id
     */
    $app = $args['app'] ?? 'noapp';
    $ckey = 'pool';
    $id = $args['id'] ?? 'noid';
    if (isset($__ds[$app][$ckey][$id])) {
      $__ds[$app][$ckey][$id]->close();
    }

    return [ 200, "Closed All connection of the pool [{$app}/{$ckey}/{$id}]." ];
  },

  # implement pool cleanup routine

];

?>
