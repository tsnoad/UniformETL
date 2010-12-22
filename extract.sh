#!/bin/bash

echo 'starting extract'
echo `date`

echo '========'
echo 'clearing old files'
echo `date`
echo '--------'

cd dumps/
rm *.*
cd dailydumps/
rm *

echo '========'
echo 'get latest dump from easysadmin@foxrep:'
echo `date`
echo '--------'

scp -i /home/user/.ssh/id_rsa easysadmin@foxrep.nat.internal:/data01/datadump/*.tgz ./

echo '========'
echo 'decompressing selected files'
echo `date`
echo '--------'

tar -xvzf *.tgz taboutcpgCustomer.dat taboutName.dat taboutEMail.dat taboutAddress.dat taboutGroupMember.dat taboutUserTableColumns.dat

echo '========'
echo 'processing legend'
echo `date`
echo '--------'

sed -e 's/||/,/g' -e 's/*$%#\r//g' taboutUserTableColumns.dat > ../taboutUserTableColumns.csv
rm taboutUserTableColumns.dat

echo '========'
echo 'primary processing data'
echo `date`
echo '--------'

for i in *.dat ; do
	origin="$i"
	destination="../"$i

	echo `date +%T`'	primary processing file: '$i

	if [ "$origin" = "taboutcpgCustomer.dat" ] ; then
		sed -e '2~1s/^[0-9][0-9]\+\ *||\ *\(IEA\|TS[0-9][0-9]\)\ *||/~~&/g' -e 's/||/~~||~~/g' -e 's/*$%#\r/~~/g' -i $origin
	else
		sed -e '2~1s/^[0-9][0-9]\+\ *||/~~&/g' -e 's/||/~~||~~/g' -e 's/*$%#\r/~~/g' -i $origin
	fi

	mv $origin $destination
done;
cd ../../

cd dumps/

echo '========'
echo 'secondary processing data'
echo `date`
echo '--------'

for i in *.dat ; do
	origin="$i"
	destinationtmp="`basename "$i" .${i##*.}`.dat.tmp2"
	destination="`basename "$i" .${i##*.}`.sql"

	echo `date +%T`'	secondary processing file: '$i

	sed -e '1s/^/~~/' -e '$d' -e 's/\\/\\\\/g' -e "s/'/\\\'/g" -e 's/||/|/g' -e "s/~~/'/g" $origin > $destinationtmp

	perl -MEncode -ne 'binmode(STDOUT, ":utf8"); print decode("iso-8859-1", "$_");' < $destinationtmp > $destination

done;

cd ../

echo '========'
echo 'preparing psql import'
echo `date`
echo '--------'

rm dump.sql
./extract.php >> dump.sql

echo '========'
echo 'importing to psql'
echo `date`
echo '--------'

psql hotel < dump.sql

echo '========'
echo 'extract complete'
echo `date`