#!/bin/bash

folder="$1"
author="$2"

if [[ -z "$folder" || -z "$author" ]]; then
  echo "Usage: $0 <folder> <author>"
  exit 1
fi

# check if the author has any commits in the repo (exact match)
if ! git log --format='%aN' | grep -Fxq "$author"; then
  echo "No commits found for author: $author"
  echo "Possible authors in this repository:"
  git log --format='%aN' | sort -u
  exit 1
fi

git ls-files "$folder" | while read -r file; do
  total_lines=$(grep -c '^' "$file")
  author_lines=$(git blame --line-porcelain "$file" | \
    awk -v author="$author" '
      /^author / {a=substr($0,8)}
      /^[^\t]/ {next}
      /^\t/ && a==author {count++; lines[count]=NR ": " substr($0,2)}
      END {for(i=1;i<=count;i++) print lines[i]; print "#COUNT#" count}
    ')
  count=$(echo "$author_lines" | tail -n1 | sed 's/#COUNT#//')
  if [[ "$count" -eq "$total_lines" ]]; then
    echo "File: $file (all lines by $author)"
  elif [[ "$count" -gt 0 ]]; then
    echo "File: $file"
    echo "$author_lines" | head -n -1
    echo
  fi
done