#!/bin/bash

source /etc/uniformetl/config.sh

echo 'starting extract'
echo `date`

echo '========'
echo 'checking environment'
echo `date`
echo '--------'

if [ ! "$1" ]; then
	echo "extract id has not been supplied"
	exit 1
fi

if [ ! "$2" ]; then
	echo "dump source path has not been supplied"
	exit 1
fi

if [ ! "$3" ]; then
	echo "dump source timestamp has not been supplied"
	exit 1
fi

if [ ! "$4" ]; then
	echo "dump source md5 hash has not been supplied"
	exit 1
fi

echo '========'
echo 'recording start'
echo `date`
echo '--------'

extractdir=$dumpdir$1
extractuntardir=$dumpdir$1"/untar"

mkdir $extractdir \
|| { echo "could not create dump folder"; exit 1; }

mkdir $extractuntardir \
|| { echo "could not create dump untar folder"; exit 1; }

$scriptdir/extract/process_recorder.php "started" "$1" "$2" "$3" "$4" "$$" \
|| { echo "could not record start of extract process"; exit 1; }

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
tar -xvzf *.tgz $untar_files \
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

	elif [ "$origin" = "taboutInvoice.dat" ] ; then
		sed -e '2~1s/^\(IEA\|TS[0-9][0-9]\)\ *||/~~&/g' -e 's/||/~~||~~/g' -e 's/*$%#\r/~~/g' -i $origin

	elif [ "$origin" = "taboutReceipt.dat" ] ; then
		sed -e '2~1s/^\(IEA\|TS[0-9][0-9]\)\ *||/~~&/g' -e 's/||/~~||~~/g' -e 's/*$%#\r/~~/g' -i $origin

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
	destinationtmp2="`basename "$i" .${i##*.}`.dat.tmp22"
	destination="`basename "$i" .${i##*.}`.sql"

	echo `date +%T`'	secondary processing file: '$i

	sed -e '1s/^/~~/' -e '$d' -e 's/\\/\\\\/g' -e "s/'/\\\'/g" -e 's/||/|/g' -e "s/~~/'/g" $origin > $destinationtmp

	tr -d '\0' < $destinationtmp > $destinationtmp2

	mv $destinationtmp2 $destinationtmp

	perl -MEncode -ne 'binmode(STDOUT, ":utf8"); print decode("iso-8859-1", "$_");' < $destinationtmp > $destination

done;

echo '========'
echo 'preparing psql import'
echo `date`
echo '--------'

cd $extractdir
$scriptdir/extract/extractors/full/create_sql.php "$1" >> $extractdir/dump.sql

echo '========'
echo 'importing to psql'
echo `date`
echo '--------'

psql hotel < $extractdir/dump.sql

echo '========'
echo 'recording finish'
echo `date`
echo '--------'

$scriptdir/extract/process_recorder.php "finished" "$1" \
|| { echo "could not record finish of extract process"; exit 1; }

echo '========'
echo 'extract complete'
echo `date`

