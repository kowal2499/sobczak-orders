# ProductRow - Komponent pojedynczego wiersza produktu

## 📝 Opis

Dedykowany komponent reprezentujący pojedynczy wiersz produktu w formularzu zamówienia. Używa **vue-select** do wyboru produktu z listy dostępnych produktów pobieranych z API.

## 📂 Lokalizacja

`modules/agreement/components/ProductRow.vue`

## 🎯 Props

| Prop | Type | Required | Default | Opis |
|------|------|----------|---------|------|
| `product` | Object | ✅ Yes | - | Obiekt produktu z polami: idProduct, factor, description, realizationDate |
| `disableRemove` | Boolean | ❌ No | false | Czy przycisk usuń ma być disabled (np. gdy jest tylko 1 produkt) |

## 📤 Emits

| Event | Payload | Opis |
|-------|---------|------|
| `update:product` | Object | Emitowany przy każdej zmianie w produkcie |
| `remove` | - | Emitowany po kliknięciu przycisku usuń |

## 🔧 Użycie

### W AgreementForm.vue

```vue
<product-row
    v-for="(product, index) in form.products"
    :key="index"
    :product="product"
    :disable-remove="form.products.length === 1"
    @update:product="updateProduct(index, $event)"
    @remove="removeProduct(index)"
    class="mb-2"
/>
```

### Metody w rodzicu (AgreementForm)

```javascript
methods: {
    updateProduct(index, updatedProduct) {
        this.form.products[index] = updatedProduct;
    },

    removeProduct(index) {
        if (this.form.products.length > 1) {
            this.form.products.splice(index, 1);
        }
    }
}
```

## 🎨 Struktura produktu

```javascript
{
    idProduct: null,        // ID produktu (Number) - wybrane z vue-select
    factor: 1,              // Ilość (Number)
    description: '',        // Opis produktu (String)
    realizationDate: ''     // Data realizacji (String, YYYY-MM-DD)
}
```

## 📋 Pola formularza

### Pierwszy wiersz (row)
1. **Produkt** (col-md-6)
   - **vue-select** z listą produktów
   - Filtrowalne
   - Placeholder: "Wybierz produkt"

2. **Współczynnik** (col-md-3)
   - Input type="number"
   - Min: 1
   - form-control-sm
   - Tooltip z opisem

3. **Data realizacji** (col-md-2)
   - Przycisk z ikoną kalendarza
   - Wyświetlanie wybranej daty obok przycisku
   - Otwiera modal z CapacityAwareDayPicker

4. **Przycisk usuń** (col-md-1)
   - Czerwony przycisk z ikoną kosza
   - Disabled jeśli `disableRemove === true`

### Drugi wiersz (row)
1. **Opis** (col-12)
   - Textarea
   - 2 wiersze
   - Pełna szerokość
   - form-control-sm

## 🔌 Integracja z API

### Pobieranie listy produktów (w AgreementForm.vue)

**Produkty są pobierane raz** w komponencie nadrzędnym (AgreementForm) i przekazywane jako prop do wszystkich instancji ProductRow.

```javascript
// W AgreementForm.vue
import api from '../../../api/neworder';

created() {
    this.loadProducts();  // Pobierz produkty raz na początku
}

methods: {
    loadProducts() {
        api.fetchProducts()
            .then(({ data }) => {
                if (data && data.products) {
                    this.products = data.products;
                }
            })
            .catch((error) => {
                console.error('Error loading products:', error);
            });
    }
}
```

**Dlaczego tak?**
- ✅ Jeden request do API zamiast wielu
- ✅ Brak opóźnień przy dodawaniu nowych wierszy produktów
- ✅ Lepsza wydajność
- ✅ Wspólny stan dla wszystkich ProductRow

### Format danych z API

```javascript
// Response z api.fetchProducts()
{
    products: [
        {
            id: 1,
            name: "Produkt A",
            // ... inne pola
        },
        {
            id: 2,
            name: "Produkt B",
            // ... inne pola
        }
    ]
}
```

### Transformacja do vue-select options

```javascript
computed: {
    productOptions() {
        return this.productDefinitions.map(product => ({
            value: product.id,      // Wartość zwracana do v-model
            label: product.name     // Wyświetlana nazwa
        }));
    }
}
```

## 🎯 vue-select - Kluczowe parametry

```vue
<vue-select
    :options="productOptions"      // Lista opcji
    :filterable="true"             // Możliwość filtrowania
    :reduce="opt => opt.value"     // Zwróć tylko value, nie cały obiekt
    v-model="proxyProduct.idProduct"
    label="label"                  // Wyświetlaj pole 'label'
    placeholder="Wybierz produkt"
    class="style-chooser"
/>
```

## 💫 Proxy Pattern

Komponent używa **proxy pattern** do zarządzania stanem:

```javascript
data() {
    return {
        proxyProduct: { ...this.product }  // Kopia prop
    };
}

watch: {
    // Emit zmian z proxy do rodzica
    proxyProduct: {
        handler(newVal) {
            this.$emit('update:product', newVal);
        },
        deep: true
    },

    // Aktualizuj proxy gdy prop się zmieni
    product: {
        handler(newVal) {
            this.proxyProduct = { ...newVal };
        },
        deep: true
    }
}
```

## 🎨 Styling

### Container (.product-row-item)
- Białe tło na jasnym tle sekcji
- Border: 1px solid #e9ecef
- Border-radius: 6px
- Padding: 0.875rem
- Hover: cień + zmiana border-color

### Layout
- **Pierwszy wiersz**: `mb-2` (margines dolny)
- **Drugi wiersz**: bez marginesu
- **Gap**: `g-2` między kolumnami

## 📊 Przykład wizualny

```
┌─ ProductRow (białe tło) ────────────────────────────┐
│                                                      │
│ [vue-select: Produkt...] [Ilość] [Data] [🗑]       │
│                                                      │
│ [Opis produktu.....................................] │
│ [...................................................] │
│                                                      │
└──────────────────────────────────────────────────────┘
```

## ✅ Zalety componentyzacji

1. **Separation of concerns** - logika produktu w osobnym komponencie
2. **Reużywalność** - łatwe użycie w innych miejscach
3. **Testowanie** - łatwiejsze testy jednostkowe
4. **Maintenance** - zmiany w jednym miejscu
5. **Clean code** - AgreementForm jest prostszy

## 🔗 Zależności

- `vue-select` - do wyboru produktu
- `api/neworder.js` - do pobierania listy produktów (w AgreementForm)
- `ModalAction` - komponent modala (base component)
- `CapacityAwareDayPicker` - kalendarz z obsługą capacity (schedule module)

## 📅 Modal z kalendarzem

### Komponenty
- **ModalAction** - obsługa modala (otwarcie/zamknięcie)
- **CapacityAwareDayPicker** - zaawansowany kalendarz z:
  - Wizualizacją dostępności (capacity)
  - Blokowaniem dni z brakiem capacity
  - Zaznaczaniem świąt
  - Blokowaniem przeszłych dat
  - Zamrożonym okresem (3 tygodnie)

### Flow wyboru daty
1. Użytkownik klika przycisk z ikoną kalendarza
2. Otwiera się modal z kalendarzem
3. Kalendarz pokazuje dostępne dni (zielone) i niedostępne (szare/czerwone)
4. Użytkownik wybiera datę z kalendarza
5. Klika "OK" - data zapisuje się i wyświetla obok przycisku
6. Lub klika "Anuluj" - modal zamyka się bez zmian

### Props przekazywane do CapacityAwareDayPicker
```vue
<capacity-aware-day-picker
    v-model="tempRealizationDate"
    :incoming-factor-value="proxyProduct.factor || 1"
    :strict-mode="false"
/>
```

- `v-model` - tymczasowa data (zapisywana po kliknięciu OK)
- `incoming-factor-value` - współczynnik produktu (do sprawdzenia capacity)
- `strict-mode="false"` - pozwala wybierać dni z przekroczonym capacity (z ostrzeżeniem)

### Formatowanie daty
Data z kalendarza (YYYY-MM-DD) jest formatowana do polskiego formatu (DD.MM.YYYY):
```javascript
formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}.${month}.${year}`;
}
```

### Metody obsługi modala
```javascript
openDatePicker() {
    this.tempRealizationDate = this.proxyProduct.realizationDate;
    this.showDatePicker = true;
}

confirmDateSelection() {
    if (this.tempRealizationDate) {
        this.proxyProduct.realizationDate = this.tempRealizationDate;
    }
    this.showDatePicker = false;
}

cancelDateSelection() {
    this.tempRealizationDate = null;
    this.showDatePicker = false;
}
```

## 🚀 Gotowe do użycia!

Komponent jest w pełni funkcjonalny i zintegrowany z AgreementForm.vue.
