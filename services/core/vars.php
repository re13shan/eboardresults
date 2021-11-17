<?php

# cache manipulates the core data, so $fnlist is passed as reference
$fnlist['vars'] = [

  'set' => function ($args=null) use (&$fnlist) {
    $__ds = &$fnlist['__ds'];
    /*
     * args: app, id, val
     */
    $app = $args['app'] ?? 'noapp';
    $ckey = 'vars';
    $id = $args['id'] ?? 'noid';
    $val = $args['val'] ?? null;
    $__ds[$app][$ckey][$id] = $val;
    return [ 200, "set successful [$app/$ckey/$id]" ];
  },


  'get' => function ($args=null) use (&$fnlist) {
    $__ds = &$fnlist['__ds'];
    /*
     * args: app, id
     */
    $app = $args['app'] ?? 'noapp';
    $ckey = 'vars';
    $id = $args['id'] ?? 'noid';
    if (!isset($__ds[$app][$ckey][$id])) {
      return [ 404, null ];
    }
    return [ 200, $__ds[$app][$ckey][$id] ];
  },

];

?>
