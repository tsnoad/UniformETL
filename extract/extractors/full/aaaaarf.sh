#!/bin/bash

input=$1
output=$2

sed \
  -e 's/\\/\\\\/g' `: #escape backslashes` \
  -e 's/'\''/\\'\''/g' `: #escape apostrophies` \
  -e '1s/^/'\''/' `: #add a quote to the start of the first line` \
  -e 's/||/'\''|'\''/g' `: #convert field separators and add quotes` \
  $input \
  | \
  sed \
  -e 'N;s/\*\$\%\#\r\n/'\''\n'\''/;P;D;' \
  | \
  sed \
  -e 's/\*\$\%\#/'\''/' \
  | \
  tr -d '\0' \
  | \
  perl -MEncode -ne 'binmode(STDOUT, ":utf8"); print decode("iso-8859-1", "$_");' \
  > $output