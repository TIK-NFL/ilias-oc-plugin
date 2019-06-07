#!/bin/bash
set -e

cd ../../../../../../../

for i in $(find . -path "*Customizing/global/plugins/Services/Repository/RepositoryObject/Opencast/test/class.*Test.php"); do
 phpunit --stderr ${i::-4}
done
cd -