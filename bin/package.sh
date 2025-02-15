#!/usr/bin/env bash

PACKAGE_FILES="includes static CHANGELOG.md LICENSE README.md ped-eu-net.php"

SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )

mkdir -p $SCRIPT_DIR/../dist/ped-eu-net
cd $SCRIPT_DIR/..

for file in $PACKAGE_FILES; do
    cp -r $file dist/ped-eu-net
done

cd dist
zip -r ped-eu-net.zip ped-eu-net
