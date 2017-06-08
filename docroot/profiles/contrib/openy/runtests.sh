#!/bin/sh
# You should install ansible for ability to run this script
export PYTHONUNBUFFERED=1
export ANSIBLE_FORCE_COLOR=true
time ansible-playbook -vvvv build/tests.yml -i 'localhost,' --connection=local $@
