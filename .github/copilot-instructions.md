# Wskazówki przy pracy z projektem

## Kontekst biznesowy

Aplikacja Sobczak Orders to system do zarządzania zamówieniami produkcyjnymi. Kluczowe pojęcia:

- **Agreement** (Umowa): Główny dokument biznesowy
- **AgreementLine** (Pozycja umowy): Pojedynczy produkt w umowie
- **Production** (Produkcja): Zadania produkcyjne dla pozycji umowy
- **WorkConfiguration**: Konfiguracja czasu pracy i dni wolnych

## Moduły systemu

### 1. Orders (Zamówienia)
- Tworzenie i zarządzanie umowami
- Status: DRAFT, WAITING, MANUFACTURING, COMPLETED, ARCHIVED
- Zawiera wiele AgreementLine

### 2. Production (Produkcja)
- Zadania produkcyjne (tasks)
- Status tasks: PENDING, IN_PROGRESS, COMPLETED
- Logi statusów (StatusLog)
- Harmonogram produkcji

### 3. Customers (Klienci)
- Zarządzanie klientami
- Powiązani z umowami

### 4. Products (Produkty)
- Katalog produktów
- Tagi i kategorie
- Powiązane z pozycjami umów

### 5. WorkConfiguration (Konfiguracja pracy)
- Capacity (Wydajność dzienna)
- Schedule (Harmonogram: dni wolne, święta)
- Używane do planowania produkcji

### 6. Reports (Raporty)
- Raporty produkcyjne
- Statystyki zamówień
- Dashboardy

## Typowe scenariusze

### Tworzenie nowej umowy
1. Użytkownik tworzy Agreement
2. Dodaje AgreementLine (produkty)
3. System generuje zadania produkcyjne (Production)
4. Zadania są przypisywane do harmonogramu

### Zarządzanie produkcją
1. Użytkownik przegląda listę zadań
2. Zmienia status zadania
3. System tworzy StatusLog
4. Aktualizuje harmonogram

### Konfiguracja czasu pracy
1. Administrator ustawia WorkCapacity (wydajność na dzień)
2. Dodaje dni wolne/święta w WorkSchedule
3. System używa tego do planowania

## Autoryzacja

System używa własnego systemu uprawnień (nie Symfony Security Voters):

```php
#[IsGranted('module.action')]
```

Przykłady uprawnień:
- `orders.create`
- `orders.edit`
- `orders.delete`
- `production.view`
- `production.edit`
- `work-configuration.capacity`
- `work-configuration.schedule`

## Wzorce zapytań Doctrine

### Pobieranie z relacjami
```php
$qb = $this->createQueryBuilder('al')
    ->leftJoin('al.agreement', 'a')
    ->leftJoin('al.product', 'p')
    ->addSelect('a', 'p')
    ->where('a.status = :status')
    ->setParameter('status', Agreement::STATUS_MANUFACTURING);
```

### Filtrowanie dat
```php
$qb->andWhere('al.productionStartDate BETWEEN :start AND :end')
   ->setParameter('start', $startDate)
   ->setParameter('end', $endDate);
```

## Frontend - komunikacja z API

### Repository pattern
```javascript
// assets/js-vue/src/modules/[module]/repository/[name]Repository.js
import axios from "axios";

export function fetchItems() {
    return axios.get('/api/items');
}

export function saveItem(payload) {
    return axios.post('/api/items', payload);
}
```

### Obsługa w komponencie
```javascript
import { fetchItems } from '../repository/itemRepository';

setup() {
    const items = ref([]);
    
    onMounted(async () => {
        try {
            const response = await fetchItems();
            items.value = response.data;
        } catch (error) {
            console.error('Error fetching items:', error);
        }
    });
    
    return { items };
}
```

## Testy - fixtures helpers

Projekt używa własnych helperów do tworzenia fixtures:

```php
// W teście
$this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks([
    'productionStartDate' => new \DateTime('2021-09-10'),
    'status' => AgreementLine::STATUS_MANUFACTURING
]);
```

## Kluczowe encje i ich relacje

```
Agreement (Umowa)
  └─ AgreementLine (Pozycje) [OneToMany]
      ├─ Product (Produkt) [ManyToOne]
      └─ Production (Zadania produkcyjne) [OneToMany]
          └─ StatusLog (Logi statusów) [OneToMany]

Customer (Klient)
  └─ Agreement [OneToMany]

WorkCapacity (Wydajność)
  └─ dateFrom [DateTimeImmutable]

WorkSchedule (Harmonogram)
  └─ date [DateTimeImmutable]
  └─ dayType [ScheduleDayType]
```

## Konwencje nazewnictwa w bazie danych

- Tabele: `snake_case` (np. `agreement_line`)
- Kolumny: `snake_case` (np. `production_start_date`)
- Klucze obce: `[table]_id` (np. `agreement_id`)

## Tłumaczenia

Klucze tłumaczeń:
- `agreements.*` - dla modułu umów
- `production.*` - dla produkcji
- `work_configuration.*` - dla konfiguracji pracy
- `dashboard.*` - dla dashboardu

Przykład:
```yaml
# translations/production.pl.yml
production:
  list:
    title: "Lista zadań produkcyjnych"
  status:
    pending: "Oczekujące"
    in_progress: "W trakcie"
    completed: "Zakończone"
```

## Claude - specjalne instrukcje

Gdy generujesz kod dla tego projektu:

1. **Zawsze sprawdzaj kontekst modułu** - kod powinien być zgodny z istniejącą strukturą modułu
2. **Używaj istniejących helperów** - sprawdź, czy nie ma już podobnej funkcjonalności
3. **Dokumentuj złożoną logikę** - zwłaszcza obliczenia związane z harmonogramem produkcji
4. **Testuj edge cases** - szczególnie przy datach i statusach
5. **Dbaj o wydajność** - unikaj N+1 queries w Doctrine

## Przykłady złożonych operacji

### Obliczanie daty zakończenia produkcji
System musi uwzględnić:
- Dni robocze (WorkSchedule)
- Wydajność dzienną (WorkCapacity)
- Istniejące zadania w kolejce

### Raportowanie statusu produkcji
- Agregacja po statusach
- Filtrowanie po datach
- Grupowanie po miesiącach/kwartałach

### Optymalizacja harmonogramu
- Równomierne rozłożenie zadań
- Respektowanie priorytetów
- Uwzględnienie dni wolnych

## Debugowanie

Typowe problemy:
1. **N+1 queries** - używaj `->addSelect()` w Query Builder
2. **Timezone** - zawsze używaj `DateTimeImmutable` z właściwą strefą
3. **Cache Doctrine** - czyść cache po zmianach w encjach
4. **CORS** - sprawdź konfigurację w `config/packages/nelmio_cors.yaml`

## Uruchamianie testów

Testy uruchamiamy w kontenerze Dockera. Projekt zawiera testy jednostkowe (Unit) oraz testy integracyjne (End2End).

### Struktura folderów testowych

**Docelowa struktura (wymagana dla nowych testów):**
- `tests/Unit/` - testy jednostkowe
- `tests/End2End/` - testy integracyjne/end-to-end

**Aktualne wyjątki (legacy, do refaktoryzacji):**
- `tests/Service/` - stare testy jednostkowe (docelowo należy przenieść do `tests/Unit/Service/`)
- `tests/Reports/Production/Integration/` - stare testy integracyjne (docelowo należy przenieść do `tests/End2End/Modules/Reports/`)
- `tests/Utilities/` - helpery i narzędzia testowe (nie są testami, pozostają w tej lokalizacji)

> ⚠️ **Uwaga:** Przy tworzeniu nowych testów ZAWSZE używaj struktury docelowej (`tests/Unit/` lub `tests/End2End/`). Wyjątki istnieją tylko ze względów historycznych.

### Uruchomienie konkretnego testu

```bash
cd /home/romek/projects/sobczak-app && docker compose exec php-apache php vendor/bin/phpunit tests/End2End/Modules/WorkConfiguration/WorkCapacityControllerTest.php
```

### Uruchomienie konkretnej metody testowej

```bash
cd /home/romek/projects/sobczak-app && docker compose exec php-apache php vendor/bin/phpunit tests/End2End/Modules/WorkConfiguration/WorkCapacityControllerTest.php --filter testShouldCreateWorkCapacity
```

### Opcje przydatne przy uruchamianiu testów

- `--testdox` - czytelny output z opisami testów
- `--filter <pattern>` - uruchomienie testów pasujących do wzorca
- `--stop-on-failure` - zatrzymanie po pierwszym błędzie
- `--coverage-html coverage` - raport pokrycia kodu (wymaga Xdebug)

Przykład:
```bash
cd /home/romek/projects/sobczak-app && docker compose exec php-apache php vendor/bin/phpunit tests/End2End --testdox --stop-on-failure
```

### Konfiguracja testów

- Plik konfiguracyjny: `app/phpunit.xml`
- Bootstrap: `app/tests/bootstrap.php`
- Środowisko testowe: `APP_ENV=test` (automatycznie ustawiane w phpunit.xml)

## Deployment

Projekt używa Docker Compose:
- PHP-Apache w `services/php-apache/`
- MySQL w `services/mysql/`
- Frontend buildowany przez Webpack

Komendy:
```bash
# Backend
cd app && composer install
php bin/console doctrine:migrations:migrate

# Frontend
cd app/assets && npm install && npm run build
```

