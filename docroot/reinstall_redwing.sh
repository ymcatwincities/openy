#!/bin/sh

if [ "$1" = "--windows" ]; then
    time ansible-playbook -vvvv reinstall.yml -i 'localhost,' --connection=local --extra-vars "is_windows=true config_folder=vars_redwing"
else
    time ansible-playbook -vvvv reinstall.yml -i 'localhost,' --connection=local --extra-vars "config_folder=vars_redwing"
fi
