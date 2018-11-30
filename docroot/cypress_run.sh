#!/bin/sh

if [ ! -z $1 ]; then
    time ansible-playbook -vvvv reinstall.yml -t cypress-commands -e all_cypress_tests=false -e cypress_test_to_run="$1"  -e cypress_run=true -i 'localhost,' --connection=local
    cat build_reports/cypress.html
else
    time ansible-playbook -vvvv reinstall.yml -t cypress-commands -e cypress_run=true -i 'localhost,' --connection=local
fi
