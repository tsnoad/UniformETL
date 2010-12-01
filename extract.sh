#!/bin/bash

cd dumps/
rm *
cd dailydumps/
rm *

echo 'get latest dump from easysadmin@foxrep:'

scp easysadmin@foxrep.nat.internal:/data01/datadump/*.tgz ./

tar -xvzf *.tgz taboutcpgCustomer.dat taboutName.dat taboutEMail.dat taboutAddress.dat taboutGroupMember.dat taboutUserTableColumns.dat

sed -e 's/||/,/g' -e 's/*$%#\r//g' taboutUserTableColumns.dat > ../taboutUserTableColumns.csv
rm taboutUserTableColumns.dat

for i in *.dat ; do
	origin="$i"
	destination="../"$i

	if [ "$origin" = "taboutcpgCustomer.dat" ] ; then
		sed -e '2~1s/^[0-9][0-9]\+\ *||\ *\(IEA\|TS[0-9][0-9]\)\ *||/~~&/g' -e 's/||/~~||~~/g' -e 's/*$%#\r/~~/g' -i $origin
	else
		sed -e '2~1s/^[0-9][0-9]\+\ *||/~~&/g' -e 's/||/~~||~~/g' -e 's/*$%#\r/~~/g' -i $origin
	fi

	mv $origin $destination
done;
cd ../../

cd dumps/

for i in *.dat ; do
	origin="$i"
	destinationtmp="`basename "$i" .${i##*.}`.dat.tmp2"
	destination="`basename "$i" .${i##*.}`.sql"

	sed -e '1s/^/~~/' -e '$d' -e 's/\\/\\\\/g' -e "s/'/\\\'/g" -e 's/||/|/g' -e "s/~~/'/g" $origin > $destinationtmp

	perl -MEncode -ne 'binmode(STDOUT, ":utf8"); print decode("iso-8859-1", "$_");' < $destinationtmp > $destination

done;

cd ../


rm dump.sql
# cat create.sql >> dump.sql
./extract.php >> dump.sql

# dropdb hotel
# createdb hotel
# 
# psql hotel < dump.sql