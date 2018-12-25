#!/bin/sh
# You should install ansible for ability to run this script
# sudo apt-get install software-properties-common
# sudo apt-add-repository ppa:ansible/ansible
# sudo apt-get update
# sudo apt-get install ansible
# sudo apt-get install python-mysqldb

# use 
# time ansible-playbook -vvvv sniffers.yml -i 'localhost,' --connection=local --extra-vars="update_tools: true"
# when you need to get all dependencies installed.
export PYTHONUNBUFFERED=1
time ansible-playbook -vvvv sniffers.yml -i 'localhost,' --connection=local
