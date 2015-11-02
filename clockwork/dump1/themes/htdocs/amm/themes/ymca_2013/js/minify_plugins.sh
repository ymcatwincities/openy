#!/bin/bash
# To run "bash minify.sh" on pion in this directory (no quotes)
# Add your js scripts here with whitespace inbetween. Escape linebreaks with a "\" if needed.
SCRIPT_IN=( plugins.js )
SCRIPT_OUT=plugins.min.js

# Compiler settings
API_URL=http://closure-compiler.appspot.com/compile
COMPILATION_LEVEL=SIMPLE_OPTIMIZATIONS

# Check if curl is installed
if [ -z "$(which curl)" ]
then
	echo 'Please install curl to proceed.'
	exit
fi

# Itearate through all files
for f in ${SCRIPT_IN[@]}
do
	if [ -r ${f} ]
	then
		code="${code} --data-urlencode js_code@${f}"
	else
		echo "File ${f} does not exist or is not readable. Skipped."
	fi
done

# If there is no code, terminate
if [ -z "${code}" ]
then
	echo 'Nothing to compile.'
	exit
fi

# Compile & save new file
`curl \
	--url ${API_URL} \
	--header 'Content-type: application/x-www-form-urlencoded' \
	${code} \
	--data output_format=text \
	--data output_info=compiled_code \
	--data compilation_level=${COMPILATION_LEVEL} \
	--output ${SCRIPT_OUT}`