#!/bin/sh

if [ "$1" = "--windows" ]; then
    time ansible-playbook -vvvv reinstall.yml -i 'localhost,' --connection=local --extra-vars "is_windows=true"
    time ansible-playbook -vvvv reinstall.yml -i 'localhost,' --connection=local --extra-vars "is_windows=true config_folder=vars_openy existing_container_name=drupal"
    time ansible-playbook -vvvv reinstall.yml -i 'localhost,' --connection=local --extra-vars "is_windows=true config_folder=vars_redwing existing_container_name=drupal"
else
    time ansible-playbook -vvvv reinstall.yml -i 'localhost,' --connection=local
    time ansible-playbook -vvvv reinstall.yml -i 'localhost,' --connection=local --extra-vars "config_folder=vars_openy existing_container_name=drupal"
    time ansible-playbook -vvvv reinstall.yml -i 'localhost,' --connection=local --extra-vars "config_folder=vars_redwing existing_container_name=drupal"
fi
