#!/bin/sh
# Stages public/ in build/content-relations/ - exactly what is deployed to
# WordPress.org - and zips it to content-relations.zip in the project root.
#
# The build directory is left in place on purpose: the release workflow rsyncs from
# it into the SVN checkout, so the zip and the SVN trunk are byte-identical. The old
# script deleted it at the end, which would have left that rsync with nothing.
#
# The plugin is plain PHP with hand-written JS and CSS: nothing to compile.
set -e

PLUGIN_SLUG="content-relations"
SCRIPT_DIR=$(cd "$(dirname "$0")" && pwd)
PROJECT_PATH=$(cd "$SCRIPT_DIR/.." && pwd)
BUILD_PATH="$PROJECT_PATH/build"
DEST_PATH="$BUILD_PATH/$PLUGIN_SLUG"

echo "Generating build directory..."
rm -rf "$BUILD_PATH"
mkdir -p "$DEST_PATH"

if [ ! -f "$PROJECT_PATH/public/ph-content-relations.php" ]; then
  echo "public/ph-content-relations.php is missing - is this the right directory?" >&2
  exit 1
fi

# public/dist/ holds the compiled block editor sidebar and is not in the repository.
if [ ! -f "$PROJECT_PATH/public/dist/block-editor.js" ]; then
  echo "public/dist/ is missing - run \"npm ci && npm run build\" first." >&2
  exit 1
fi

echo "Syncing files..."
# -L resolves symlinks into real files. wordpress.org discards symlinks when it builds
# the download, and SVN refuses to put one where it versions a regular file.
rsync -rL "$PROJECT_PATH/public/" "$DEST_PATH/"

echo "Generating zip file..."
cd "$BUILD_PATH" || exit 1
zip -q -r "${PLUGIN_SLUG}.zip" "$PLUGIN_SLUG/"
mv "${PLUGIN_SLUG}.zip" "$PROJECT_PATH/"

cd "$PROJECT_PATH" || exit 1
echo "${PLUGIN_SLUG}.zip file generated!"
echo "Build done!"
