<?php


# Core must never modify passed arguments

$fnlist['core'] = [

  'captcha' => function ($args=null) use ($fnlist) {
    return [ 200, "Core Captcha Routine." ];
  },

  'login' => function ($args=null) use ($fnlist) {
    return [ 200, "Core Login Routine." ];
  },

  'dot' => function ($args=null) use ($fnlist) {
    return [ 200, '.' ];
  },

  'about' => function ($args=null) use ($fnlist) {
    return [ 200, 'Developed and Maintained by Nixtec Systems. Developer and Maintainer: Md. Ayub Ali (ayub@nixtecsys.com, mrayub@gmail.com, +8801911192000).' ];
  },

];

?>
