#!/bin/bash

depth=$1
path=$2
file=$3
dryrun=$4

if [[ "$4" == "--dry-run" ]]; then
    echo "dry run"
    exit 0
fi

echo "not doing anything with the depth, but it was set to ${depth}"

echo "copying file into place"
cp $file ${path}/src/OneMoreTest.php
