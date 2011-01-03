#!/bin/bash

echo 'starting extract'
echo `date`

echo '========'
echo 'clearing old files'
echo `date`
echo '--------'

rm /home/user/hotel/dumps/*.*
rm /home/user/hotel/dumps/dailydumps/*

echo '========'
echo 'get latest dump from easysadmin@foxrep:'
echo `date`
echo '--------'

scp -i /home/user/.ssh/id_rsa_foxrep easysadmin@foxrep.nat.internal:/data01/datadump/*.tgz /home/user/hotel/dumps/dailydumps/

echo '========'
echo 'decompressing selected files'
echo `date`
echo '--------'

cd /home/user/hotel/dumps/dailydumps/
tar -xvzf *.tgz taboutcpgCustomer.dat taboutName.dat taboutEMail.dat taboutAddress.dat taboutGroupMember.dat taboutUserTableColumns.dat

echo '========'
echo 'processing legend'
echo `date`
echo '--------'

cd /home/user/hotel/dumps/dailydumps/
sed -e 's/||/,/g' -e 's/*$%#\r//g' taboutUserTableColumns.dat > ../taboutUserTableColumns.csv
rm taboutUserTableColumns.dat

echo '========'
echo 'primary processing data'
echo `date`
echo '--------'

cd /home/user/hotel/dumps/dailydumps/
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

echo '========'
echo 'secondary processing data'
echo `date`
echo '--------'

cd /home/user/hotel/dumps/
for i in *.dat ; do
	origin="$i"
	destinationtmp="`basename "$i" .${i##*.}`.dat.tmp2"
	destination="`basename "$i" .${i##*.}`.sql"

	echo `date +%T`'	secondary processing file: '$i

	sed -e '1s/^/~~/' -e '$d' -e 's/\\/\\\\/g' -e "s/'/\\\'/g" -e 's/||/|/g' -e "s/~~/'/g" $origin > $destinationtmp

	perl -MEncode -ne 'binmode(STDOUT, ":utf8"); print decode("iso-8859-1", "$_");' < $destinationtmp > $destination

done;

echo '========'
echo 'preparing psql import'
echo `date`
echo '--------'

cd /home/user/hotel/
rm dump.sql
./extract.php >> dump.sql

echo '========'
echo 'importing to psql'
echo `date`
echo '--------'


cd /home/user/hotel/
psql hotel < dump.sql

echo '========'
echo 'extract complete'
echo `date`