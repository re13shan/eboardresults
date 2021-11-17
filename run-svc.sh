#!/bin/bash

pushd `dirname $0`
sock="/vh/p/sock.d/eboardresults-svc.sock"

while true; do
  echo "Starting Service"
  php7.4 -f main.php &
  jobid=$!
  trap "echo 'Killing service'; kill ${jobid}; sleep 3; exit" SIGINT SIGQUIT
  echo "Process ID: ${jobid}"
  sleep 3
  chmod 0666 "${sock}"
  wait $jobid
  echo "Service Terminated!!!"
  sleep 5
done
popd
