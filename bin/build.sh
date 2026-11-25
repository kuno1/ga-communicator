#!/usr/bin/env bash

set -e

# Set variables.
PREFIX="refs/tags/"
VERSION=${1#"$PREFIX"}

if [ -z "${VERSION}" ]; then
	echo "Usage: bin/build.sh <version>  (e.g. bin/build.sh 3.7.2)" >&2
	exit 1
fi

echo "Building GA Communicator v${VERSION}..."

# Install composer.
composer install --no-dev --prefer-dist

# Create README.txt
curl -L https://raw.githubusercontent.com/fumikito/wp-readme/master/wp-readme.php | php

# ga-communicator.php is the single source of truth for requirements.
header_value() {
	grep -m1 -E "^ \* ${1}:" ./ga-communicator.php | sed -E "s/^ \* ${1}:[[:space:]]*//"
}
REQUIRES_PHP=$(header_value "Requires PHP")
REQUIRES_WP=$(header_value "Requires at least")

# Change version string.
sed -i.bak -E "s/^( \* Version:[[:space:]]*).*/\1${VERSION}/" ./ga-communicator.php
sed -i.bak -E \
	-e "s/^Stable Tag: .*/Stable Tag: ${VERSION}/" \
	-e "s/^Requires PHP: .*/Requires PHP: ${REQUIRES_PHP}/" \
	-e "s/^Requires at least: .*/Requires at least: ${REQUIRES_WP}/" \
	./readme.txt
