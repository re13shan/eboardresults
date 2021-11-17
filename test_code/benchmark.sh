#!/bin/bash

CMD="wrk -t100 -c1000 -d10s"
#RND=( $(openssl rand 100000 | sha1sum | fold -w 13 | head -n 1) )
#RHDR="Cookie: EBRSESSID=${RND}"

#RND="ebr5f15b0226113a825478"

#baseurl='http://eboardresults.com/c'
baseurl='http://127.0.0.1:9501'
cfile="cookie.txt"

echo "Benchmarking CAPTCHA"
${CMD} "${baseurl}/core/captcha"
exit


# First (new session) call
CURLS="curl -c ${cfile} -s -o /dev/null"
# Next calls (continue using session cookie from previously started sessions)
CURL="curl -b ${cfile} -c ${cfile} -s -o /dev/null -w http_code=%{http_code}\n"
#CURL="curl -b ${cfile} -c ${cfile} -s -w \"\n\nhttp_code=%{http_code}\n\""
#$CURL -I "${baseurl}/ebr/configure"

while true; do
  $CURL "${baseurl}/ebr/home"
  sleep 10
done

exit

# New session
$CURLS "${baseurl}/ebr/captcha"
#sleep 1
echo "Enter a CAPTCHA to supply with request for result"
read captcha

rtype=1
exam=ssc
board=dhaka
year=2020
roll=100788
reg=1710794981
#$CURL "${baseurl}/ebr/getres?captcha=$captcha&result_type=${rtype}&exam=${exam}&board=${board}&year=${year}&roll=${roll}"
$CURL "${baseurl}/ebr/getres?captcha=$captcha&result_type=${rtype}&exam=${exam}&board=${board}&year=${year}&roll=${roll}&reg=${reg}"

#$CURL "${baseurl}/ebr/getres?captcha=$captcha&result_type=1&exam=ssc&board=dhaka&year=2020&roll=123456"
#$CURL "${baseurl}/ebr/getres"
exit

rtype=2
exam=ssc
board=dhaka
year=2020
eiin=108181
$CURL "${baseurl}/ebr/getres?captcha=$captcha&result_type=${rtype}&exam=${exam}&board=${board}&year=${year}&eiin=${eiin}"
$CURL "${baseurl}/ebr/pdl"

exit


HDR=""
if [ -f "${cfile}" ]; then
  HDR="`cat $cfile`"
fi


# get a session cookie first
if [ "${HDR}" = "" ]; then
  HDR="Cookie: `curl -I "${baseurl}/ebr/home" 2>&1 | grep -i 'Set-cookie' | cut -d ':' -f 2 | cut -d ';' -f 1 | sed -e 's/ //g'`"
  echo "[$HDR]"
  echo "${HDR}" > $cfile
fi
# just testing fraud attacks (from same sid)
curl -H "${HDR}" -I "${baseurl}/ebr/getres"
#curl -H "${HDR}" "${baseurl}/ebr/getres"
exit

sleep 6
curl -H "${HDR}" "${baseurl}/ebr/getres"
curl -H "${HDR}" "${baseurl}/ebr/getres"
exit


echo "Benchmarking CAPTCHA"
${CMD} -H "${HDR}" "${baseurl}/core/captcha"

exit

echo "Benchmarking Detailed Result with test data"
${CMD} -H "${HDR}" "${baseurl}/ebr/getres?result_type=1&testmode=yes"
#${CMD} -H "${HDR}" "${baseurl}/getres.php?result_type=1&testmode=yes&random=yes"

echo "Benchmarking Institute Result with test data"
${CMD} -H "${HDR}" "${baseurl}/ebr/getres?result_type=2&testmode=yes"
#${CMD} -H "${HDR}" "${baseurl}/getres.php?result_type=2&testmode=yes&random=yes"

echo "Benchmarking Center Result with test data"
${CMD} -H "${HDR}" "${baseurl}/ebr/getres?result_type=4&testmode=yes"
#${CMD} -H "${HDR}" "${baseurl}/getres.php?result_type=4&testmode=yes&random=yes"

echo "Benchmarking District Result with test data"
${CMD} -H "${HDR}" "${baseurl}/ebr/getres?result_type=5&testmode=yes"
#${CMD} -H "${HDR}" "${baseurl}/getres.php?result_type=5&testmode=yes&random=yes"

echo "Benchmarking Institute Analytics with test data"
${CMD} -H "${HDR}" "${baseurl}/ebr/getres?result_type=6&testmode=yes"
#${CMD} -H "${HDR}" "${baseurl}/getres.php?result_type=6&testmode=yes&random=yes"

echo "Benchmarking Board Analytics with test data"
${CMD} -H "${HDR}" "${baseurl}/ebr/getres?result_type=7&testmode=yes"
#${CMD} -H "${HDR}" "${baseurl}/getres.php?result_type=7&testmode=yes&random=yes"

exit
