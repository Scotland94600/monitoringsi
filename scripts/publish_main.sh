#!/usr/bin/env bash
set -euo pipefail

VERSION="$(tr -d '[:space:]' < VERSION)"

if [[ ! "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
  echo "VERSION invalide: $VERSION"
  exit 1
fi

echo "Préparation publication v$VERSION vers main"

git fetch origin

git checkout main
git pull --ff-only origin main

git merge --ff-only work

git tag -a "v$VERSION" -m "Release v$VERSION"

echo "Publication prête. Commandes à lancer:"
echo "  git push origin main"
echo "  git push origin v$VERSION"
