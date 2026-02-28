#!/bin/bash

##############################################################################
# CSERP Frontend Code Exporter (TypeScript Supported)
# Eksportuje cały kod Vue.js + TS do pliku tekstowego z timestampem
##############################################################################

# Timestamp w formacie: YYYY-MM-DD_HH-MM-SS
TIMESTAMP=$(date +%Y-%m-%d_%H-%M-%S)
OUTPUT_FILE="cserp-frontend-code.txt"
FRONTEND_DIR="./cserp-frontend"

# Kolory
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}================================================${NC}"
echo -e "${BLUE}CSERP Frontend Code Exporter (Vue 3 + TS)${NC}"
echo -e "${BLUE}================================================${NC}\n"

if [ ! -d "$FRONTEND_DIR" ]; then
    FRONTEND_DIR="./"
fi

if [ ! -d "$FRONTEND_DIR" ]; then
    echo "❌ Katalog $FRONTEND_DIR nie istnieje!"
    exit 1
fi

# Wyczyść stary plik
> "$OUTPUT_FILE"

echo "📦 Eksportuję kod do: $OUTPUT_FILE"
echo "🕐 Data eksportu: $(date '+%Y-%m-%d %H:%M:%S')"
echo ""

# Funkcja do dodawania pliku
add_file() {
    local file_path=$1
    if [ -f "$file_path" ]; then
        local relative_path=${file_path#$FRONTEND_DIR/}
        
        echo "   ✓ $relative_path"
        
        echo "=================================================================================" >> "$OUTPUT_FILE"
        echo "FILE: $relative_path" >> "$OUTPUT_FILE"
        echo "LOCATION: $file_path" >> "$OUTPUT_FILE"
        echo "=================================================================================" >> "$OUTPUT_FILE"
        echo "" >> "$OUTPUT_FILE"
        cat "$file_path" >> "$OUTPUT_FILE"
        echo "" >> "$OUTPUT_FILE"
        echo "" >> "$OUTPUT_FILE"
    fi
}

# Funkcja do skanowania katalogu pod kątem rozszerzeń .ts, .js, .vue
scan_dir() {
    local dir=$1
    if [ -d "$dir" ]; then
        # Szukaj TS
        for file in "$dir"/*.ts; do
            [ -e "$file" ] && add_file "$file"
        done
        # Szukaj JS (wsteczna kompatybilność)
        for file in "$dir"/*.js; do
            [ -e "$file" ] && add_file "$file"
        done
        # Szukaj Vue
        for file in "$dir"/*.vue; do
            [ -e "$file" ] && add_file "$file"
        done
    fi
}

# Header
cat >> "$OUTPUT_FILE" << EOF
################################################################################
#                                                                              #
#                        FRONTEND CODE EXPORT                                  #
#                  Vue.js 3 + Vite + Tailwind + TypeScript                     #
#                                                                              #
#  Export Date: $(date '+%Y-%m-%d %H:%M:%S')                                          #
#  Timestamp: ${TIMESTAMP}                                                   #
#                                                                              #
################################################################################

EOF

echo "📄 Eksportuję pliki konfiguracyjne..."

add_file "$FRONTEND_DIR/package.json"
add_file "$FRONTEND_DIR/tsconfig.json"
add_file "$FRONTEND_DIR/tsconfig.app.json"
add_file "$FRONTEND_DIR/tsconfig.node.json"
add_file "$FRONTEND_DIR/vite.config.ts"
add_file "$FRONTEND_DIR/vite.config.js" # Fallback
add_file "$FRONTEND_DIR/tailwind.config.js"
add_file "$FRONTEND_DIR/postcss.config.js"
add_file "$FRONTEND_DIR/index.html"
add_file "$FRONTEND_DIR/src/env.d.ts" # Ważne dla TS

echo ""
echo "🎯 Eksportuję pliki główne..."

add_file "$FRONTEND_DIR/src/main.ts"
add_file "$FRONTEND_DIR/src/main.js" # Fallback
add_file "$FRONTEND_DIR/src/App.vue"

echo ""
echo "🛣️  Eksportuję router..."
# Sprawdza index.ts i index.js
if [ -f "$FRONTEND_DIR/src/router/index.ts" ]; then
    add_file "$FRONTEND_DIR/src/router/index.ts"
elif [ -f "$FRONTEND_DIR/src/router/index.js" ]; then
    add_file "$FRONTEND_DIR/src/router/index.js"
fi

echo ""
echo "🗄️  Eksportuję stores (Pinia)..."
scan_dir "$FRONTEND_DIR/src/stores"

echo ""
echo "🔌 Eksportuję services (API)..."
scan_dir "$FRONTEND_DIR/src/services"

echo ""
echo "🧩 Eksportuję composables..."
scan_dir "$FRONTEND_DIR/src/composables"

echo ""
echo "🔧 Eksportuję utils, config, enums, plugins..."
scan_dir "$FRONTEND_DIR/src/utils"
scan_dir "$FRONTEND_DIR/src/config"
scan_dir "$FRONTEND_DIR/src/enums"
scan_dir "$FRONTEND_DIR/src/layouts"
scan_dir "$FRONTEND_DIR/src/types"
# scan_dir "$FRONTEND_DIR/src/plugins"

echo ""
echo "🎨 Eksportuję style..."
add_file "$FRONTEND_DIR/src/assets/main.css"
add_file "$FRONTEND_DIR/src/style.css"

echo ""
echo "📱 Eksportuję views (rekurencyjnie proste podejście)..."

# Lista folderów w views do przeszukania
VIEWS_DIRS=(
    "$FRONTEND_DIR/src/views"
    "$FRONTEND_DIR/src/views/assortment"
    "$FRONTEND_DIR/src/views/auth"
    "$FRONTEND_DIR/src/views/customers"
    "$FRONTEND_DIR/src/views/orders"
    "$FRONTEND_DIR/src/views/production"
    "$FRONTEND_DIR/src/views/quotations"
    "$FRONTEND_DIR/src/views/rcp"
    "$FRONTEND_DIR/src/views/users"
    "$FRONTEND_DIR/src/views/workstations"
)

for dir in "${VIEWS_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        # Tylko .vue w widokach zazwyczaj
        for file in "$dir"/*.vue; do
            [ -e "$file" ] && add_file "$file"
        done
    fi
done

echo ""
echo "🧱 Eksportuję komponenty (rekurencyjnie proste podejście)..."

COMPONENTS_DIRS=(
    "$FRONTEND_DIR/src/components/assortment"
    "$FRONTEND_DIR/src/components/common"
    "$FRONTEND_DIR/src/components/customers"
    "$FRONTEND_DIR/src/components/layout"
    "$FRONTEND_DIR/src/components/materials"
    "$FRONTEND_DIR/src/components/orders"
    "$FRONTEND_DIR/src/components/production"
    "$FRONTEND_DIR/src/components/prototypes"
    "$FRONTEND_DIR/src/components/quotations"
    "$FRONTEND_DIR/src/components/users"
    "$FRONTEND_DIR/src/components/variants"
    "$FRONTEND_DIR/src/components/workstations"
)

for dir in "${COMPONENTS_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        for file in "$dir"/*.vue; do
            [ -e "$file" ] && add_file "$file"
        done
    fi
done

echo ""
echo "🧪 Eksportuję testy..."
scan_dir "$FRONTEND_DIR/src/__tests__"

echo ""
echo "📐 Eksportuję layouts..."
scan_dir "$FRONTEND_DIR/src/layouts"

# Footer
cat >> "$OUTPUT_FILE" << EOF

################################################################################
#                                                                              #
#                            END OF FRONTEND CODE                              #
#                                                                              #
#  Export completed: $(date '+%Y-%m-%d %H:%M:%S')                                     #
#                                                                              #
################################################################################
EOF

FILE_SIZE=$(du -h "$OUTPUT_FILE" | cut -f1)
FILE_COUNT=$(grep -c "^FILE:" "$OUTPUT_FILE")

echo ""
echo -e "${GREEN}================================================${NC}"
echo -e "${GREEN}✅ EKSPORT ZAKOŃCZONY!${NC}"
echo -e "${GREEN}================================================${NC}"
echo ""
echo "📊 Statystyki:"
echo "   📁 Liczba plików: $FILE_COUNT"
echo "   💾 Rozmiar: $FILE_SIZE"
echo "   📄 Plik wyjściowy: $OUTPUT_FILE"
echo "   🕐 Timestamp: $TIMESTAMP"
echo ""