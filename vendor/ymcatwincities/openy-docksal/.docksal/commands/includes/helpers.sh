#!/usr/bin/env bash

# Console colors
black='\033[0;30m'
red='\033[0;31m'
green='\033[0;32m'
green_bg='\033[39;42m'
yellow='\033[1;33m'
bold='\033[1m'
NC='\033[0m'

echo-red () { echo -e "${red}$1${NC}"; }
echo-green () { echo -e "${green}$1${NC}"; }
echo-green-bg () { echo -e "${green_bg}$1${NC}"; }
echo-yellow () { echo -e "${yellow}$1${NC}"; }

is_windows ()
{
	local res=$(uname | grep 'CYGWIN_NT')
	if [[ "$res" != "" ]]; then
		return 0
	else
		return 1
	fi
}

progress_bar ()
{
    printf "["
    # While process is running...
    while kill -0 $PID 2> /dev/null; do
        printf  "="
        sleep 0.5
    done
    printf "]"
    echo
}
