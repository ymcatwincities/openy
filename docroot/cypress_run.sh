#!/bin/sh

if [ "$1" = "--windows" ]; then
    time ansible-playbook -vvvv reinstall.yml -i 'localhost,' --connection=local --extra-vars "is_windows=true cypress_run=true" --tags="cypress-commands"
else
    time ansible-playbook -vvvv reinstall.yml -t cypress-commands -e cypress_run=true -i 'localhost,' --connection=local
fi
