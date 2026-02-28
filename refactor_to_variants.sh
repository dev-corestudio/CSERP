#!/bin/bash

#===============================================================================
# SKRYPT REFAKTORYZACJI: ProductLine → Variant
# Wersja zoptymalizowana dla macOS
#===============================================================================

# Kolory
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[OK]${NC} $1"; }
log_warning() { echo -e "${YELLOW}[WARN]${NC} $1"; }

PROJECT_PATH="./"
BACKEND_PATH="$PROJECT_PATH/cserp-backend"
FRONTEND_PATH="$PROJECT_PATH/cserp-frontend"

log_info "Rozpoczynam refaktoryzację..."
echo ""

#===============================================================================
# BACKEND
#===============================================================================

if [ -d "$BACKEND_PATH" ]; then
    log_info "=== BACKEND ==="
    
    # Rename plików (jeśli jeszcze istnieją)
    [ -f "$BACKEND_PATH/app/Models/ProductLine.php" ] && \
        mv "$BACKEND_PATH/app/Models/ProductLine.php" "$BACKEND_PATH/app/Models/Variant.php" && \
        log_success "Model: ProductLine.php → Variant.php"
    
    [ -f "$BACKEND_PATH/app/Http/Controllers/API/ProductLineController.php" ] && \
        mv "$BACKEND_PATH/app/Http/Controllers/API/ProductLineController.php" \
           "$BACKEND_PATH/app/Http/Controllers/API/VariantController.php" && \
        log_success "Controller: ProductLineController.php → VariantController.php"
    
    # Migration rename
    MIGRATION_FILE=$(find "$BACKEND_PATH/database/migrations" -name "*create_product_lines_table.php" 2>/dev/null | head -1)
    if [ -n "$MIGRATION_FILE" ]; then
        NEW_MIGRATION=$(echo "$MIGRATION_FILE" | sed 's/product_lines/variants/')
        mv "$MIGRATION_FILE" "$NEW_MIGRATION"
        log_success "Migration renamed"
    fi
    
    # Zamiana tekstu - WYKLUCZAMY vendor/
    log_info "Zamieniam tekst w plikach PHP (pomijam vendor/)..."
    
    # Licznik plików
    FILE_COUNT=$(find "$BACKEND_PATH/app" "$BACKEND_PATH/database" "$BACKEND_PATH/routes" -type f -name "*.php" 2>/dev/null | wc -l | tr -d ' ')
    log_info "Znaleziono $FILE_COUNT plików PHP do przetworzenia"
    
    find "$BACKEND_PATH/app" "$BACKEND_PATH/database" "$BACKEND_PATH/routes" -type f -name "*.php" 2>/dev/null | while read -r file; do
        # Używamy LC_ALL=C dla szybszego sed
        LC_ALL=C sed -i '' \
            -e 's/ProductLine/Variant/g' \
            -e 's/productLine/variant/g' \
            -e 's/product_line/variant/g' \
            -e 's/product-lines/variants/g' \
            -e 's/productLines/variants/g' \
            -e 's/line_number/variant_number/g' \
            -e 's/Linia produktowa/Wariant/g' \
            -e 's/Linie produktowe/Warianty/g' \
            -e 's/linii produktowej/wariantu/g' \
            "$file" 2>/dev/null
    done
    
    log_success "Backend zakończony"
    echo ""
else
    log_warning "Nie znaleziono: $BACKEND_PATH"
fi

#===============================================================================
# FRONTEND
#===============================================================================

if [ -d "$FRONTEND_PATH" ]; then
    log_info "=== FRONTEND ==="
    
    # Rename plików
    [ -f "$FRONTEND_PATH/src/services/productLineService.ts" ] && \
        mv "$FRONTEND_PATH/src/services/productLineService.ts" "$FRONTEND_PATH/src/services/variantService.ts" && \
        log_success "Service renamed"
    
    [ -f "$FRONTEND_PATH/src/stores/productLines.ts" ] && \
        mv "$FRONTEND_PATH/src/stores/productLines.ts" "$FRONTEND_PATH/src/stores/variants.ts" && \
        log_success "Store renamed"
    
    # Components
    COMP_PATH="$FRONTEND_PATH/src/components/orders"
    [ -f "$COMP_PATH/ProductLineCard.vue" ] && mv "$COMP_PATH/ProductLineCard.vue" "$COMP_PATH/VariantCard.vue" && log_success "VariantCard.vue"
    [ -f "$COMP_PATH/ProductLineFormDialog.vue" ] && mv "$COMP_PATH/ProductLineFormDialog.vue" "$COMP_PATH/VariantFormDialog.vue" && log_success "VariantFormDialog.vue"
    [ -f "$COMP_PATH/ProductLineItem.vue" ] && mv "$COMP_PATH/ProductLineItem.vue" "$COMP_PATH/VariantItem.vue" && log_success "VariantItem.vue"
    
    # Views
    [ -f "$FRONTEND_PATH/src/views/orders/ProductLineDetail.vue" ] && \
        mv "$FRONTEND_PATH/src/views/orders/ProductLineDetail.vue" "$FRONTEND_PATH/src/views/orders/VariantDetail.vue" && \
        log_success "VariantDetail.vue"
    
    # Zamiana tekstu - TYLKO src/, pomijamy node_modules
    log_info "Zamieniam tekst w plikach Vue/TS..."
    
    FILE_COUNT=$(find "$FRONTEND_PATH/src" -type f \( -name "*.vue" -o -name "*.ts" -o -name "*.js" \) 2>/dev/null | wc -l | tr -d ' ')
    log_info "Znaleziono $FILE_COUNT plików do przetworzenia"
    
    find "$FRONTEND_PATH/src" -type f \( -name "*.vue" -o -name "*.ts" -o -name "*.js" \) 2>/dev/null | while read -r file; do
        LC_ALL=C sed -i '' \
            -e 's/ProductLine/Variant/g' \
            -e 's/productLine/variant/g' \
            -e 's/product_line/variant/g' \
            -e 's/product-lines/variants/g' \
            -e 's/productLines/variants/g' \
            -e 's/useProductLinesStore/useVariantsStore/g' \
            -e 's/productLinesStore/variantsStore/g' \
            -e 's/line_number/variant_number/g' \
            -e 's/currentLine/currentVariant/g' \
            -e 's/fetchLine/fetchVariant/g' \
            -e 's/updateLine/updateVariant/g' \
            -e 's/createLine/createVariant/g' \
            "$file" 2>/dev/null
        
        # Polskie teksty osobno (dla pewności)
        LC_ALL=C sed -i '' \
            -e 's/Linia produktowa/Wariant/g' \
            -e 's/Linie produktowe/Warianty/g' \
            -e 's/Dodaj linię/Dodaj wariant/g' \
            -e 's/Edytuj linię/Edytuj wariant/g' \
            -e 's/Nowa linia/Nowy wariant/g' \
            -e 's/Nazwa linii/Nazwa wariantu/g' \
            -e 's/Anuluj linię/Anuluj wariant/g' \
            -e 's/Brak linii/Brak wariantów/g' \
            -e 's/Szczegóły Linii/Szczegóły Wariantu/g' \
            -e 's/Ładowanie linii/Ładowanie wariantów/g' \
            "$file" 2>/dev/null
    done
    
    log_success "Frontend zakończony"
    echo ""
else
    log_warning "Nie znaleziono: $FRONTEND_PATH"
fi

#===============================================================================
# PODSUMOWANIE
#===============================================================================

echo ""
log_success "✅ Refaktoryzacja zakończona!"
echo ""
log_warning "Następne kroki:"
echo "  1. git diff - sprawdź zmiany"
echo "  2. cd cserp-backend && php artisan migrate:fresh --seed"
echo "  3. cd cserp-frontend && npm run build"
echo ""