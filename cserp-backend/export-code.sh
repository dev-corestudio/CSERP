#!/bin/bash

##############################################################################
# CSERP Backend Code Exporter
# Eksportuje cały kod Laravel do pliku tekstowego z timestampem
##############################################################################

# Timestamp w formacie: YYYY-MM-DD_HH-MM-SS
TIMESTAMP=$(date +%Y-%m-%d_%H-%M-%S)
OUTPUT_FILE="cserp-backend-code.txt"
OUTPUT_FILE_OLD="cserp-backend-code_${TIMESTAMP}.txt"
BACKEND_DIR="./cserp-backend"

# Kolory
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}================================================${NC}"
echo -e "${BLUE}CSERP Backend Code Exporter${NC}"
echo -e "${BLUE}================================================${NC}\n"

if [ ! -d "$BACKEND_DIR" ]; then
    BACKEND_DIR="./"
fi

if [ ! -d "$BACKEND_DIR" ]; then
    echo "❌ Katalog $BACKEND_DIR nie istnieje!"
    exit 1
fi

# Wyczyść stary plik
> "$OUTPUT_FILE"

echo "📦 Eksportuję kod Laravel do: $OUTPUT_FILE"
echo "🕐 Data eksportu: $(date '+%Y-%m-%d %H:%M:%S')"
echo ""

# Funkcja do dodawania pliku
add_file() {
    local file_path=$1
    local relative_path=${file_path#$BACKEND_DIR/}

    echo "   ✓ $relative_path"

    echo "=================================================================================" >> "$OUTPUT_FILE"
    echo "FILE: $relative_path" >> "$OUTPUT_FILE"
    echo "LOCATION: $file_path" >> "$OUTPUT_FILE"
    echo "=================================================================================" >> "$OUTPUT_FILE"
    echo "" >> "$OUTPUT_FILE"
    cat "$file_path" >> "$OUTPUT_FILE"
    echo "" >> "$OUTPUT_FILE"
    echo "" >> "$OUTPUT_FILE"
}

# Header
cat >> "$OUTPUT_FILE" << EOF
################################################################################
#                                                                              #
#                        BACKEND CODE EXPORT                                   #
#                      Laravel 11 + MySQL 8.0                                  #
#                                                                              #
#  Export Date: $(date '+%Y-%m-%d %H:%M:%S')                                          #
#  Timestamp: ${TIMESTAMP}                                                   #
#                                                                              #
################################################################################

EOF

echo "📄 Eksportuję pliki konfiguracyjne..."

# composer.json
if [ -f "$BACKEND_DIR/composer.json" ]; then
    add_file "$BACKEND_DIR/composer.json"
fi

echo ""
echo "🗄️  Eksportuję migracje..."

# Migracje
for file in "$BACKEND_DIR"/database/migrations/*.php; do
    if [ -f "$file" ]; then
        add_file "$file"
    fi
done

echo ""
echo "🎯 Eksportuję modele..."

# Modele
for file in "$BACKEND_DIR"/app/Models/*.php; do
    if [ -f "$file" ]; then
        add_file "$file"
    fi
done

echo ""
echo "🎮 Eksportuję controllery..."

# Controllers
if [ -d "$BACKEND_DIR/app/Http/Controllers/API" ]; then
    for file in "$BACKEND_DIR"/app/Http/Controllers/API/*.php; do
        if [ -f "$file" ]; then
            add_file "$file"
        fi
    done
fi

echo ""
echo "⚙️  Eksportuję enums..."

# Services
if [ -d "$BACKEND_DIR/app/Services" ]; then
    for file in "$BACKEND_DIR"/app/Enums/*.php; do
        if [ -f "$file" ]; then
            add_file "$file"
        fi
    done
fi

echo ""
echo "⚙️  Eksportuję services..."

# Services
if [ -d "$BACKEND_DIR/app/Services" ]; then
    for file in "$BACKEND_DIR"/app/Services/*.php; do
        if [ -f "$file" ]; then
            add_file "$file"
        fi
    done
fi

echo ""
echo "🛣️  Eksportuję routes..."

# Routes
if [ -f "$BACKEND_DIR/routes/api.php" ]; then
    add_file "$BACKEND_DIR/routes/api.php"
fi

# if [ -f "$BACKEND_DIR/routes/web.php" ]; then
#     add_file "$BACKEND_DIR/routes/web.php"
# fi

# echo ""
# echo "📋 Eksportuję seeders..."

# # Seeders
# for file in "$BACKEND_DIR"/database/seeders/*.php; do
#     if [ -f "$file" ]; then
#         add_file "$file"
#     fi
# done

echo ""
echo "⚙️  Eksportuję konfigurację..."

# Config files
if [ -f "$BACKEND_DIR/config/cors.php" ]; then
    add_file "$BACKEND_DIR/config/cors.php"
fi

if [ -f "$BACKEND_DIR/config/sanctum.php" ]; then
    add_file "$BACKEND_DIR/config/sanctum.php"
fi

if [ -f "$BACKEND_DIR/config/database.php" ]; then
    add_file "$BACKEND_DIR/config/database.php"
fi

echo ""
echo "📝 Eksportuję middleware..."

# Middleware (jeśli są custom)
if [ -d "$BACKEND_DIR/app/Http/Middleware" ]; then
    for file in "$BACKEND_DIR"/app/Http/Middleware/*.php; do
        if [ -f "$file" ] && [[ $(basename "$file") != "TrustProxies.php" ]] && [[ $(basename "$file") != "EncryptCookies.php" ]]; then
            add_file "$file"
        fi
    done
fi

echo ""
echo "🧪 Eksportuję testy..."

# Tests (jeśli są)
if [ -d "$BACKEND_DIR/tests/Feature" ]; then
    for file in "$BACKEND_DIR"/tests/Feature/*.php; do
        if [ -f "$file" ]; then
            add_file "$file"
        fi
    done
fi

# Requests (Form Requests)
if [ -d "$BACKEND_DIR/app/Http/Requests" ]; then
    echo ""
    echo "📮 Eksportuję Form Requests..."
    for file in "$BACKEND_DIR"/app/Http/Requests/*.php; do
        if [ -f "$file" ]; then
            add_file "$file"
        fi
    done
fi

# Resources (API Resources)
if [ -d "$BACKEND_DIR/app/Http/Resources" ]; then
    echo ""
    echo "📦 Eksportuję API Resources..."
    for file in "$BACKEND_DIR"/app/Http/Resources/*.php; do
        if [ -f "$file" ]; then
            add_file "$file"
        fi
    done
fi

# Footer
cat >> "$OUTPUT_FILE" << EOF

################################################################################
#                                                                              #
#                            END OF BACKEND CODE                               #
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
echo "💡 Możesz teraz skopiować zawartość pliku do AI"
echo ""
