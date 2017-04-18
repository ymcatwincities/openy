#!/usr/bin/env bash
# Remove .git folders from vendor folder to avoid git submodule issues for some
# workflows.
echo Removing .git folders from vendor folder to avoid possible git issues.
find vendor -name ".git" -exec rm -rf {} \; > /dev/null 2>&1
