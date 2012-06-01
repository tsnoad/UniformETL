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
  -e 'N;s/\*\$\%\#\r\n/'\''\n'\''/;P;D;' `: #each row ends with *$%#\r. convert to an quote, and add a quote at the start of the next line ` \
  | \
  sed \
  -e 's/\*\$\%\#\r/'\''/' `: #remove *$%#\r from the end of the last line. replace with a quote` \
  | \
  tr -d '\0' `: #remove unspeakable horrors` \
  | \
  perl -MEncode -ne 'binmode(STDOUT, ":utf8"); print decode("iso-8859-1", "$_");' `: #recode from iso8859 to utf8` \
  > $output