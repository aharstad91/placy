#!/bin/bash
# Remove console.log statements from production JavaScript files
# Usage: ./remove-console-logs.sh

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Placy Theme - Console.log Removal Script${NC}"
echo "=========================================="
echo ""

# Backup directory
BACKUP_DIR="js/console-logs-backup-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP_DIR"

echo -e "${YELLOW}Creating backups in: $BACKUP_DIR${NC}"
echo ""

# Files to process (exclude backups)
FILES=(
    "js/tema-story-map.js"
    "js/tema-story-map-multi.js"
    "js/poi-map-modal.js"
    "js/chapter-nav.js"
    "js/chapter-header.js"
    "js/container-gradient.js"
    "js/intro-parallax.js"
    "js/scroll-indicator.js"
)

TOTAL_REMOVED=0

for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        # Count console.log statements
        COUNT=$(grep -c "console\.log" "$file" 2>/dev/null || echo "0")
        
        if [ "$COUNT" -gt 0 ]; then
            echo -e "${YELLOW}Processing: $file${NC} ($COUNT console.log statements)"
            
            # Create backup
            cp "$file" "$BACKUP_DIR/$(basename $file)"
            
            # Remove console.log statements (with and without semicolons)
            # This sed command removes lines containing console.log
            sed -i.tmp '/console\.log/d' "$file"
            rm "${file}.tmp"
            
            TOTAL_REMOVED=$((TOTAL_REMOVED + COUNT))
            echo -e "${GREEN}✓ Removed $COUNT statements${NC}"
        else
            echo -e "${GREEN}✓ $file - No console.log statements${NC}"
        fi
    else
        echo -e "${RED}✗ $file - File not found${NC}"
    fi
done

echo ""
echo "=========================================="
echo -e "${GREEN}Complete! Removed $TOTAL_REMOVED console.log statements${NC}"
echo -e "${YELLOW}Backups saved to: $BACKUP_DIR${NC}"
echo ""
echo "IMPORTANT: Test your site thoroughly after running this script."
echo "If anything breaks, restore from the backups."
