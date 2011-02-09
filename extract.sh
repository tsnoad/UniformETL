#!/bin/bash

psql -c "UPDATE processes SET extract_pid='"$$"' WHERE process_id='"$1"';" hotel

server=easysadmin@foxrep.nat.internal

scriptdir=/home/user/hotel/

dumpdir=/home/user/hotel/extract_processes/

identity=/home/user/.ssh/id_rsa_foxrep

echo 'starting extract'
echo `date`

echo '========'
echo 'checking environment'
echo `date`
echo '--------'

if [ ! "$1" ]; then
	echo "extract process id has not been supplied"
	exit 1
fi

if [ ! "$2" ]; then
	echo "dump source path has not been supplied"
	exit 1
fi

if [ ! -d "$dumpdir$1" ]; then
	echo "invalid process id, or could not find process folder in export_processes"
	exit 1
fi

extractdir=$dumpdir$1;
extractuntardir=$dumpdir$1"/untar";

mkdir $extractuntardir

echo '========'
echo 'get latest dump from easysadmin@foxrep:'
echo `date`
echo '--------'

scp -i $identity $server:$2 $extractuntardir \
|| { echo "could not get specified dump"; exit 1; }

echo '========'
echo 'decompressing selected files'
echo `date`
echo '--------'

cd $extractuntardir
tar -xvzf *.tgz taboutcpgCustomer.dat taboutName.dat taboutEMail.dat taboutAddress.dat taboutGroupMember.dat taboutUserTableColumns.dat \
|| { echo "could not untar dump"; exit 1; }

echo '========'
echo 'processing legend'
echo `date`
echo '--------'

cd $extractuntardir
sed -e 's/||/,/g' -e 's/*$%#\r//g' taboutUserTableColumns.dat > $extractdir/taboutUserTableColumns.csv
rm taboutUserTableColumns.dat

echo '========'
echo 'processing taboutGroupMember.dat'
echo `date`
echo '--------'

cd $extractuntardir
sed -n '/^[^|]*||[^|]*||\ *6052\ *||/p' taboutGroupMember.dat > taboutGroupMember.dat.tmp
mv taboutGroupMember.dat.tmp taboutGroupMember.dat

echo '========'
echo 'primary processing data'
echo `date`
echo '--------'

cd $extractuntardir
for i in *.dat ; do
	origin="$i"
	destination=$extractdir/$i

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

cd $extractdir
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

cd $extractdir
$scriptdir/extract.php $1 >> $extractdir/dump.sql

# echo '========'
# echo 'importing to psql'
# echo `date`
# echo '--------'

# cd /home/user/hotel/
# psql hotel < dump.sql

echo '========'
echo 'extract complete'
echo `date`

psql -c "UPDATE processes SET finished='TRUE', finish_date=now() WHERE process_id='"$1"';" hotel
