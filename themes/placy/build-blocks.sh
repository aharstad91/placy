#!/bin/bash

# Build script for compiling Tailwind CSS for all blocks
# This script compiles input.css to style.css for each block

echo "🎨 Building Tailwind CSS for all blocks..."

THEME_DIR="/Applications/MAMP/htdocs/placy/wp-content/themes/placy"
BLOCKS_DIR="$THEME_DIR/blocks"

# Array of blocks that need Tailwind compilation
BLOCKS=("poi-map-card" "poi-list" "poi-list-dynamic" "poi-highlight" "poi-gallery" "image-column")

for BLOCK in "${BLOCKS[@]}"; do
    BLOCK_DIR="$BLOCKS_DIR/$BLOCK"
    
    if [ -f "$BLOCK_DIR/input.css" ]; then
        echo "📦 Building $BLOCK..."
        cd "$BLOCK_DIR"
        npx tailwindcss -i ./input.css -o ./style.css --minify
        echo "✅ $BLOCK compiled"
    else
        echo "⚠️  Skipping $BLOCK - no input.css found"
    fi
done

echo ""
echo "✨ All blocks compiled successfully!"
