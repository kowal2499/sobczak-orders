# Wskazówki przy pracy z projektem

## Kontekst biznesowy

Aplikacja Sobczak Orders to system do zarządzania zamówieniami produkcyjnymi. Kluczowe pojęcia:

- **Agreement** (Umowa): Główny dokument biznesowy
- **AgreementLine** (Pozycja umowy): Pojedynczy produkt w umowie, zwykle tylko jedna dla danego Agreement
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
- Raporty kalendarzowe
- Statystyki zamówień
- Dashboardy

## Organizacja kodu api
- kod grupujemy w modułach (src/Modules/[ModuleName])
- każdy moduł ma swoje kontrolery, serwisy, encje, repository
- konfiguracja modułu w module.yaml, zawiera m.in. nazwę, uprawnienia
- konfiguracja autowiringu w config.yaml w katalogu modułu
- konfiguracja routing w routes.yaml w katalogu modułu

## Kontrolery
- dbamy o to, by kontrolery były cienkie, delegują logikę do serwisów za pośrednictwem dependency injection 
- walidują dane wejściowe i uruchamiają serwisy i delegują eventy, komendy lub queries (preferujemy CQRS)
- nie zawierają bezpośrednio logiki biznesowej, ale mogą zawierać logikę specyficzną dla API (np. formatowanie odpowiedzi)

## Logika biznesowa
- umieszczamy w serwisach (src/Modules/[ModuleName]/Service) 
- proferowane stosowanie wzorca CQRS poprzez App\System\CommandBus, App\System\EventBus, App\System\QueryBus

## Encje i Doctrine
- Encje znajdują się w src/Modules/[ModuleName]/Entity
- Dbamy o to, by encje były czyste, bez logiki biznesowej (poza prostymi metodami pomocniczymi)

### Kluczowe encje i ich relacje

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

### Read Model
- Dla encji AgreementLine istnieje read-model zbierający dane także z powiązanych encji 
(np. Product, Customer, Production, Agreement) 
- znajduje się w App\Module\AgreementLine\Entity\AgreementLineRM. 
- Komenda aktualizująca ten read-model (App\Module\AgreementLine\Command\UpdateAgreemenLineRM) jest uruchamiana po każdej zmianie w encji AgreementLine lub powiązanych encjach.


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

## Testy - ogólne zasady
- Testy jednostkowe (Unit) do testowania logki biznesowej i warunków brzegowych
- Testy integracyjne (End2End) głównie do testowania kontrolerów i operacji bazodanowych
- Używamy własnych helperów do tworzenia fixtures (np autoryzacja, logowanie, granty)
- Testy End2End wrapujemy w transakcje, które są rollbackowane po teście
```php
// W teście
$this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks([
    'productionStartDate' => new \DateTime('2021-09-10'),
    'status' => AgreementLine::STATUS_MANUFACTURING
]);
$user = $this->createUser([], [], ['work-configuration.capacity']);
$client = $this->login($user);
```
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
### Konfiguracja testów
- Plik konfiguracyjny: `app/phpunit.xml`
- Bootstrap: `app/tests/bootstrap.php`
- Środowisko testowe: `APP_ENV=test` (automatycznie ustawiane w phpunit.xml)

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


## Frontend 

Widoki na froncie zwracane są w pierwszej kolejności przez api Symfony (twig). Szablony twig są proste i
sprowadzają się głównie do zwrócenia customowego tagu html. Za rendering i obsługę na froncie odpowiadają komponenty Vue.
Lista globalnych komponentów możliwych do osadzania w szablonach twig znajduje się w `assets/js-vue/src/components/root-componenets.js`

Komponenty Vue powiązane z konkretną logiką biznesową, grupowane są per moduł i znajdują w katalogu `assets/js-vue/src/modules/[ModuleName]/components/`.
Komponenty wspólne trafiają do katalogu `assets/js-vue/src/components/base`

### tłumaczenia
Na froncie aplikacja wspiera języki angielski i polski. Do tłumaczeń używane jest vue-i18n. Pliki z kluczami tłumaczeń ładowane
są dynamiczne i umieszczane są w:
- katalogach modułów: `assets/js-vue/src/modules/[ModuleName]/locale/[lang].json`
- globalnych plikach: `assets/js-vue/src/locale/[lang].json`
Użycie w kodzie odpowiednio `this.$t('[moduleName].key')` lub `this.$t(`key`)`

### komunikacja z API

#### Repository pattern
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

#### Obsługa w komponencie
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