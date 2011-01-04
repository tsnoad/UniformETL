#!/bin/bash

export dumpdir=/home/user/hotel/dumps/
export dumpuntardir=/home/user/hotel/dumps/dailydumps/

export identity=/home/user/.ssh/id_rsa_foxrep

echo 'starting extract'
echo `date`

echo '========'
echo 'checking environment'
echo `date`
echo '--------'

if [ ! -d "$dumpdir" ]; then
	echo "environment is missing dumps folder"
	exit 1
fi

if [ ! -d "$dumpuntardir" ]; then
	echo "environment is missing dailydumps folder"
	exit 1
fi

echo '========'
echo 'clearing old files'
echo `date`
echo '--------'

rm $dumpdir/*.*
rm $dumpuntardir/*

echo '========'
echo 'get latest dump from easysadmin@foxrep:'
echo `date`
echo '--------'

scp -i $identity easysadmin@foxrep.nat.internal:/data01/datadump/*.tgz $dumpuntardir \
|| { echo "could not get latest dump"; exit 1; }

echo '========'
echo 'decompressing selected files'
echo `date`
echo '--------'

cd $dumpuntardir
tar -xvzf *.tgz taboutcpgCustomer.dat taboutName.dat taboutEMail.dat taboutAddress.dat taboutGroupMember.dat taboutUserTableColumns.dat

echo '========'
echo 'processing legend'
echo `date`
echo '--------'

cd $dumpuntardir
sed -e 's/||/,/g' -e 's/*$%#\r//g' taboutUserTableColumns.dat > ../taboutUserTableColumns.csv
rm taboutUserTableColumns.dat

echo '========'
echo 'primary processing data'
echo `date`
echo '--------'

cd $dumpuntardir
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