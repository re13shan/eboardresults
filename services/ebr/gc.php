<?php

#echo "Before GC:\n";
#print_r($server->session);

/* we need to make sure only one instance of this routine runs at a time */
function ebr_session_cleanup_cb($timerid, $param)
{
  list($server, $timername) = $param;

  $locked = $server->timer_config[$timername]['locked'];
  if ($locked->get() == 1) {
    #echo "Another instance of $timername already running.\n";
    return; # already locked
  }

  $locked->set(1);
  #echo "[" . date("r") . "] " . "Timer [id=$timerid] started\n";
  #Swoole\Coroutine::sleep(10); # testing if locking works (uncomment and see if you get 'already running' message

  #var_dump($timerid);
  #var_dump($server);


  foreach ($server->cache_config as $ckey => $cfg) {

    if (isset($cfg['cleanup']) && $cfg['cleanup'] == false) continue;

    $i = 0;
    $tmp_expiry = $server->cache_expiry;
    while (true) {
      $curtime = time();
      $gc_count = 0;

      $current_count = $server->cache[$ckey]->count();
      $current_usage = (int) (($current_count/$cfg['nrows']) * 100);
      if ($current_usage < $server->cache_threshold) break;

      if ($i++ > 0) {
	$tmp_expiry -= (int) ($tmp_expiry/$server->cache_expiry_step);
        Swoole\Coroutine::sleep(2); # sleep only if the cache is not cleaned up in first shot
      }

      echo "+ Cache [ $ckey ]. Current Usage: ${current_usage}%, Expiry Time: $tmp_expiry, Going to walk over $current_count Cache Entries.\n";


      $server->cache[$ckey]->rewind();
      while ($server->cache[$ckey]->valid()) {
	$k = $server->cache[$ckey]->key();
	$server->cache[$ckey]->next(); # advance the pointer to next entry

	$ts = $server->cache[$ckey]->get($k, '__ts');
	if (($curtime - $ts) > $tmp_expiry) {
	  #echo "Reaped\n";
	  $server->cache[$ckey]->del($k);
	  $gc_count++;
	}
      }
      echo "- Total $gc_count Cache Entries Removed.\n";
      #break; # later we may keep the step version, now not needed
    }

  }

  #echo "[" . date("r") . "] " . "Timer [id=$timerid] ended\n";
  $locked->set(0);
}

/*

# if we keep modifying the array/table while iterating over it the array/table changes reference and we don't touch all the elements
# So we are taking keys of the array/table to loop over them
$keys = array();
$server->cache['session']->rewind();
#$started = microtime(true);
while ($server->cache['session']->valid()) {
  $keys[] = $server->cache['session']->key();
  $server->cache['session']->next();
}
#$ended = microtime(true);
*/

/*
$started = microtime(true);
foreach ($server->cache['session'] as $k => $v) {
  $keys[] = $k;
}
$ended = microtime(true);
*/

#echo "Time taken to iterate over the keys: " . ($ended - $started) . "\n";

# we can't use array_keys() because it is not regular array, instead it is object, which overloads operations of an array
#$keys = array_keys($server->cache['session']);
/*
foreach ($keys as $k) {
  $sess = unserialize($server->cache['session'][$k]['sess']);
  #echo "k=$k, __ts=" . $sess['__ts'] . "\n";
  if (($curtime - $sess['__ts']) > $expiry) {
    #echo "Collecting session [$k]\n";
    unset($server->cache['session'][$k]);
    #$server->cache['session']->del($k);
    $gc_count++;
  }
}
*/

#echo "Total $gc_count Sessions Collected.\n";
#echo "Remaining " . count($server->cache['session']) . " sessions.\n";

#echo "After GC:\n";
#print_r($server->session);

?>
