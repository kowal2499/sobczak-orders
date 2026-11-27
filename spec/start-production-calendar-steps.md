# Rozpocznij produkcję - drawer z kalendarzem obciążenia - plan implementacji

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Cel:** Akcja "Rozpocznij produkcję" otwiera drawer na pełną szerokość. Zakresy dat działów ustawia się w nim
na trzy sposoby: strategią, ręcznie w polach dat albo przeciągając pasek w kalendarzu. Kalendarz pokazuje
istniejące obciążenie każdego działu.

**Architektura:** Jedno źródło prawdy - tablica `form` (`[{ slug, dateStart, dateEnd }]`) w `StartProductionAction`.
Strategia i pola dat już ją modyfikują. Kalendarz dostaje ją przez `v-model` i emituje nową kopię po
przeciągnięciu. Kalendarz to `ResourceCalendar` z widoku `CalendarTasks`, rozszerzony o tryb dowolnego zakresu dat.
Obciążenie (mapa ciepła) pochodzi z nowego endpointu liczonego dla całej firmy.

**Stack:** Symfony 5.4 / PHP 8.2 (endpoint), Vue 2.7 + bootstrap-vue (`Sidebar`, `ResourceCalendar`), moment, jest
(tylko czyste moduły `.js` - jest nie transformuje `.vue`).

**Spec:** ustalenia z rozmowy (2026-11-27), zebrane poniżej w "Decyzje".

## Decyzje

- Bazą jest widok `CalendarTasks` (`/reports/schedule/view/production`): wiersze to działy, zdarzenia to produkcje.
  `CalendarGeneral` i `CalendarOrders` nie są używane.
- Trzy drogi zmiany dat (strategia, pola, drag & drop) zmieniają ten sam `form`. Każda zmiana odświeża pozostałe
  dwa miejsca. Pola dat zostają bez zmian funkcjonalnych, łącznie z walidacją VeeValidate.
- Domyślnie obciążenie jest pokazane jako **mapa ciepła**: tło komórki dział × dzień, intensywność względna do
  maksimum działu w widocznym zakresie, liczba zadań w `title` komórki. Przełącznik "Pokaż istniejące zadania"
  dokłada szare, nieedytowalne paski (dane z `/reports/schedule/production-resources`).
- Miara obciążenia: **liczba aktywnych zadań produkcyjnych działu w danym dniu** (`dateStart <= dzień <= dateEnd`),
  bez ghostów, bez zadań w statusie `COMPLETED` i `NOT_APPLICABLE`, bez produkcji linii, którą właśnie planujemy.
  Faktor rozłożony na dni to możliwe rozszerzenie później, poza zakresem tego planu.
- Obciążenie liczone **dla całej firmy** (bez filtra `ROLE_CUSTOMER`). Widoczność działów nadal według grantów
  `production.show.*`. Dział bez grantu ma wiersz z paskiem planu, ale bez mapy ciepła i z dopiskiem "brak dostępu".
- Nowe zadania: wyraźny kolor (`--colorPrimary`), edytowalne. Termin dostawy (`confirmedDate`) jako tło kolumny
  w każdym wierszu.
- Zakres kalendarza wylicza się sam: od poniedziałku tygodnia min(dziś, najwcześniejsza data w `form`) do
  max(`confirmedDate`, najpóźniejsza data w `form`) + 14 dni. Bez nawigacji miesiącami.
- Dział bez dat w `form` nie ma paska. Ustawia się go strategią albo w polach - tworzenie paska zaznaczeniem
  komórek jest poza zakresem.
- Brak przyciągania do dni roboczych przy przeciąganiu. Święta są zaznaczone w nagłówku i komórkach.

## Global Constraints

- Nigdy nie używać pauzy (em dash, U+2014) - w kodzie, komentarzach, tłumaczeniach, Markdown. Tylko `-`.
- Minimum komentarzy. Annotacje typów dla PHPStan (`@return array<string, ...>`) zostają.
- **Nie commitować i nie robić `git add`** bez wyraźnej zgody użytkownika (CLAUDE.md). Kroki "Commit" z szablonu
  zastąpione krokiem "Zostaw zmiany w working tree".
- Testy PHP: `make test F=...` (w kontenerze, z katalogu repo). Testy JS: `cd app/assets && npx jest <ścieżka>`.
- Nowe testy tylko w `tests/Unit/`, `tests/End2End/` i `app/assets/js-vue/__tests__/`.
- Po zmianie API: `make cc`.
- Tłumaczenia frontu: `modules/production/locale/pl.js` i `en.js` (oba pliki w każdym zadaniu, które dodaje tekst).
- Daty w całym froncie jako stringi `YYYY-MM-DD`, parsowane `moment(str, 'YYYY-MM-DD')` (bez stref czasowych).

## Review Focus

1. **Regresja `CalendarTasks` i `CalendarOrders` po przejściu na kolumny zakresu.** Zadanie przechodzące przez
   granicę miesiąca ma być przycięte jak dziś, a przeciąganie ma zmieniać daty o tyle samo dni. Test `clipToRange`
   dla zdarzenia z poprzedniego miesiąca (Task 2).
2. **Dział z `dateStart` albo `dateEnd` równym `null`** (np. strategia nie objęła działu, ręcznie wyczyszczone pole)
   nie ma paska i nie wywraca kalendarza. Test `planToEvents` (Task 4).
3. **Ponowne planowanie linii, która ma już produkcje z datami w przeszłości.** Zakres zaczyna się od najwcześniejszej
   daty, pasek jest widoczny, a jej własne produkcje nie zawyżają obciążenia. Testy `defaultRange` (Task 4)
   i `excludeAgreementLineId` (Task 1).
4. **Przeciągnięcie paska za `confirmedDate`.** Kalendarz nie przycina dat, a pole dostaje błąd walidacji
   `dateEarlierThan`, tak jak przy ręcznym wpisaniu. Test `applyEventToForm` (Task 4) plus ręczna weryfikacja w Task 6.
5. **Użytkownik `ROLE_CUSTOMER` albo bez grantu działu.** Obciążenie nie jest zawężane do klientów użytkownika,
   a działy bez grantu nie zwracają liczb. Testy E2E (Task 1).

---

### Task 1: Endpoint obciążenia działów

**Pliki:**
- Create: `app/src/Module/Reports/Schedule/Service/ScheduleDepartmentLoadService.php`
- Modify: `app/src/Module/Reports/Schedule/Controller/ScheduleController.php` (nowa akcja obok `productionResources`)
- Test: `app/tests/End2End/Modules/Reports/Schedule/ScheduleDepartmentLoadControllerTest.php`
  (rozszerza `BaseScheduleReportsTestCase`, fixture'y jak w `ScheduleProductionResourcesControllerTest::makeLineWithProductions`)

**Interfejsy:**
- Produkuje: `GET /reports/schedule/department-load?startDate=YYYY-MM-DD&endDate=YYYY-MM-DD[&excludeAgreementLineId=int]`,
  `#[IsGranted('ROLE_PRODUCTION')]`, walidacja zakresu przez istniejące `validateDateRange()`. Odpowiedź:
  ```json
  {
    "departments": [{ "id": "dpt01", "name": "Klejenie", "hasAccess": true }, "... zawsze wszystkie 6"],
    "load": { "dpt01": { "2026-12-01": 3, "2026-12-02": 4 } }
  }
  ```
  `load` zawiera tylko działy z `hasAccess: true` i tylko dni z liczbą > 0.
- `ScheduleDepartmentLoadService::getLoad(\DateTimeInterface $start, \DateTimeInterface $end, ?int $excludeAgreementLineId): array`
  z `@return array{departments: list<array{id: string, name: string, hasAccess: bool}>, load: array<string, array<string, int>>}`.
  Linie z `AgreementLineRMRepository::search()` z kluczami `statusNot: [STATUS_DELETED]`, `hasProduction: true`,
  `dptDateRange` (jak w `ScheduleProductionResourcesService`), **bez** `ownedBy`. Widoczne działy przez
  `DepartmentGrantMap::all()` + `isGranted()`, lista działów z `DepartmentEnum::getProductionDepartments()`.

- [ ] **Krok 1: Napisz testy E2E**
  - `testShouldReturn403WhenUserHasNoRoleProduction`
  - `testShouldCountActiveTasksPerDepartmentAndDay`: dpt01 produkcje 2026-05-05..05-07 i 2026-05-06..05-08 →
    `load.dpt01` = `{"2026-05-05":1,"2026-05-06":2,"2026-05-07":2,"2026-05-08":1}`
  - `testShouldSkipCompletedNotApplicableAndGhostTasks`: trzy takie produkcje → `load` bez klucza `dpt01`
  - `testShouldExcludeGivenAgreementLine`: dwie linie, `excludeAgreementLineId` jednej → liczy się tylko druga
  - `testShouldReturnAllDepartmentsButLoadOnlyForGranted`: grant tylko `production.show.gluing` →
    `departments` ma 6 pozycji, `hasAccess` true tylko dla dpt01, `load` ma tylko klucz `dpt01` mimo produkcji w dpt02
  - `testShouldNotFilterLoadByCustomerOwnership`: użytkownik `ROLE_CUSTOMER` + `ROLE_PRODUCTION` przypisany do
    klienta A, produkcja klienta B w tym samym dniu → liczba 2
  - `testShouldClipToRequestedRange`: produkcja 2026-04-28..05-03, zakres 2026-05-01..05-31 → klucze tylko 05-01..05-03

- [ ] **Krok 2: Uruchom, oczekiwany FAIL (404)**
  `make test F=tests/End2End/Modules/Reports/Schedule/ScheduleDepartmentLoadControllerTest.php`

- [ ] **Krok 3: Zaimplementuj serwis i akcję kontrolera**
  Status produkcji przez `ProductionTaskStatus::tryFrom((int) $production->getStatus())`, jak w `mapStatus()`.
  Liczenie: dla każdej produkcji iteracja po dniach `max(dateStart, start)..min(dateEnd, end)`.

- [ ] **Krok 4: Uruchom testy, oczekiwany PASS; potem `make check` i `make cc`**

- [ ] **Krok 5: Zostaw zmiany w working tree**

---

### Task 2: `ResourceCalendar` - tryb dowolnego zakresu dat

Dziś pozycja zdarzenia liczona jest z `moment.date()` (dzień miesiąca), więc kalendarz zna tylko jeden miesiąc.
Zamiana na indeks kolumny od początku zakresu. Tryb miesięczny staje się zakresem `[1. dzień, ostatni dzień]`.

**Pliki:**
- Create: `app/assets/js-vue/src/modules/schedule/components/ResourceCalendar/utils/timeline.js`
- Modify: `.../ResourceCalendar/utils/gridHelpers.js`, `ResourceCalendar.vue`, `CalendarGrid.vue`, `CalendarEvent.vue`
  (tylko przez `eventPositionStyle`)
- Test: `app/assets/js-vue/__tests__/modules/schedule/timeline.test.js`, `.../gridHelpers.test.js`

**Interfejsy:**
- Produkuje (`timeline.js`):
  - `buildColumns(start: string, end: string): moment.Moment[]` - kolejne dni włącznie
  - `columnOf(rangeStart: string, date: string): number` - różnica w dniach, może być ujemna
  - `clipToRange(dateStart: string, dateEnd: string, rangeStart: string, rangeEnd: string): { startCol: number, endCol: number } | null`
    (`null`, gdy brak części wspólnej)
- Zmienia (`gridHelpers.js`): `assignLanes(events)` i `eventPositionStyle(event)` czytają `event.startCol`/`event.endCol`
  zamiast `clippedStart.date()`/`clippedEnd.date()`.
- `ResourceCalendar` - nowe propsy: `range: { start: string, end: string } | null` (domyślnie `null` = tryb miesięczny
  jak dziś), `showHeader: Boolean = true`, `cellTitle: Function | null` (`(resourceId, dateString) => string`).
  W trybie `range` nagłówek z nawigacją miesięcy jest ukryty. Zdarzenia i emisje (`event-moved`, `event-resized`)
  bez zmian w kształcie.
- `CalendarGrid` - zamiast `daysInMonth` i `currentMonthMoment` dostaje `columns: moment.Moment[]`. Komórki identyfikuje
  indeks kolumny (`data-col`), zdarzenia `cell-mousedown/enter/up` przekazują indeks kolumny. `title` komórki z `cellTitle`.

- [ ] **Krok 1: Napisz testy jest**
  - `buildColumns('2026-11-30', '2026-12-02')` → 3 elementy, ostatni `2026-12-02`
  - `columnOf('2026-11-30', '2026-12-02') === 2`, `columnOf('2026-11-30', '2026-11-28') === -2`
  - `clipToRange('2026-10-28', '2026-11-03', '2026-11-01', '2026-11-30')` → `{ startCol: 0, endCol: 2 }` (regresja miesiąca)
  - `clipToRange('2026-12-29', '2027-01-04', '2026-12-28', '2027-01-31')` → `{ startCol: 1, endCol: 7 }` (przez granicę roku)
  - `clipToRange` dla zakresu rozłącznego → `null`
  - `assignLanes` dla `[{startCol:0,endCol:3},{startCol:2,endCol:4},{startCol:4,endCol:5}]` → pasy `[0,1,0]`
  - `eventPositionStyle({startCol:2,endCol:4,lane:1})` → `left: '260px'`, `width: '118px'`, `top: '30px'`

- [ ] **Krok 2: Uruchom, oczekiwany FAIL** - `cd app/assets && npx jest js-vue/__tests__/modules/schedule`

- [ ] **Krok 3: Zaimplementuj `timeline.js`, przepnij `gridHelpers`, `ResourceCalendar`, `CalendarGrid`**
  `ResourceCalendar` liczy `effectiveRange` (prop `range` albo granice `currentMonth`), `columns` i w `visibleEvents`
  dokłada `startCol/endCol` przez `clipToRange`. Podgląd przeciągania przesuwa `startCol/endCol` o offset i przycina
  do `[0, columns.length - 1]`. `nowLineLeft` przez `columnOf`. `bgEventStyle` przez `startCol/endCol`.

- [ ] **Krok 4: Uruchom testy jest, oczekiwany PASS**

- [ ] **Krok 5: Weryfikacja ręczna regresji** (`make dev`): na `/reports/schedule/view/production` przesuń zadanie
  przechodzące na kolejny miesiąc o 2 dni i rozciągnij inne. Daty po odświeżeniu przesunięte o 2 dni. Na
  `/reports/schedule/view/orders` paski i nawigacja miesięcy jak przed zmianą.

- [ ] **Krok 6: Zostaw zmiany w working tree**

---

### Task 3: `ResourceCalendar` - rozciąganie od lewej

**Pliki:**
- Modify: `.../ResourceCalendar/CalendarEvent.vue` (uchwyt `resize-handle--start`, emit `resize-start-mousedown`),
  `CalendarGrid.vue` (przekazanie emitu), `ResourceCalendar.vue` (`dragState.type === 'resize-start'`)
- Modify: `.../ResourceCalendar/utils/timeline.js` - dodaj czystą funkcję ograniczenia offsetu
- Test: `timeline.test.js`

**Interfejsy:**
- Produkuje: `clampResizeOffset(edge: 'start' | 'end', dateStart: string, dateEnd: string, offset: number): number` -
  zwraca offset, po którym `dateStart <= dateEnd` (minimum 1 dzień). Używane przez oba kierunki rozciągania.
- `event-resized` emitowane także dla lewej krawędzi, z tym samym kształtem `{ previous, updated }`
  (`updated.dateStart` zmienione). `CalendarTasks` obsługuje to bez zmian, bo zapisuje obie daty.

- [ ] **Krok 1: Testy**: `clampResizeOffset('start', '2026-12-01', '2026-12-03', 5) === 2`,
  `clampResizeOffset('start', '2026-12-01', '2026-12-03', -4) === -4`, `clampResizeOffset('end', '2026-12-01', '2026-12-03', -5) === -2`
- [ ] **Krok 2: FAIL** - `cd app/assets && npx jest js-vue/__tests__/modules/schedule/timeline.test.js`
- [ ] **Krok 3: Implementacja** (zastąp obecne liczenie `minOffset` w `onGlobalMousemove` wywołaniem `clampResizeOffset`)
- [ ] **Krok 4: PASS + ręcznie**: na `/reports/schedule/view/production` rozciągnij zadanie od lewej, odśwież, `dateStart` zmienione
- [ ] **Krok 5: Zostaw zmiany w working tree**

---

### Task 4: Logika planera (czyste funkcje)

**Pliki:**
- Create: `app/assets/js-vue/src/modules/production/services/productionPlanCalendar.js`
- Test: `app/assets/js-vue/__tests__/modules/production/productionPlanCalendar.test.js`

**Interfejsy:**
- Konsumuje: kształt `form` z `StartProductionAction` (`[{ slug, dateStart: string|null, dateEnd: string|null }]`),
  odpowiedź `department-load` z Task 1.
- Produkuje:
  - `PLAN_EVENT_PREFIX = 'plan-'`
  - `planToEvents(form): Event[]` - jedno zdarzenie na dział z obiema datami: `{ id: 'plan-dpt01', resourceId: 'dpt01',
    dateStart, dateEnd, eventType: 'plan', orderName: <nazwa działu przez getDepartmentName>, color: 'var(--colorPrimary)' }`
  - `applyEventToForm(form, { resourceId, dateStart, dateEnd }): form` - nowa tablica, zmieniony tylko wiersz `slug === resourceId`,
    daty bez przycinania
  - `loadToBackgroundEvents(load: Object<slug, Object<date, int>>): Event[]` - po jednym `{ id: 'load-dpt01-2026-12-01',
    resourceId, dateStart: date, dateEnd: date, eventType: 'background', color }` na dzień z liczbą > 0;
    `color = rgba(var(--colorPrimaryRgb), alpha)`, `alpha` w 4 krokach `0.12 / 0.24 / 0.36 / 0.48` wg `count / max` działu
    (progi `<= 0.25`, `<= 0.5`, `<= 0.75`, reszta)
  - `deadlineEvents(resourceIds: string[], confirmedDate: string): Event[]` - tło kolumny terminu, `color: 'rgba(231, 120, 131, 0.35)'`
  - `defaultRange(form, confirmedDate: string, today: string): { start: string, end: string }` - reguła z "Decyzje"
  - `isPlanEvent(event): boolean`

- [ ] **Krok 1: Testy**
  - `planToEvents` pomija wiersz z `dateEnd: null` i wiersz z `dateStart: null`; dla pełnego wiersza zwraca `id: 'plan-dpt02'`
  - `applyEventToForm` zmienia tylko dpt02, nie mutuje wejścia (`expect(input).toEqual(kopiaPrzed)`), nie przycina daty po terminie
  - `loadToBackgroundEvents({ dpt01: { '2026-12-01': 1, '2026-12-02': 4 } })` → alfy `0.12` i `0.48`; pusty obiekt → `[]`
  - `defaultRange([{slug:'dpt01',dateStart:'2026-11-18',dateEnd:'2026-11-20'}], '2026-12-17', '2026-11-27')` →
    `{ start: '2026-11-16', end: '2026-12-31' }` (przeszła data planu poszerza zakres w lewo)
  - `defaultRange` z pustymi datami → start w poniedziałek tygodnia `today`, koniec `confirmedDate + 14`
- [ ] **Krok 2: FAIL** - `cd app/assets && npx jest js-vue/__tests__/modules/production`
- [ ] **Krok 3: Implementacja**
- [ ] **Krok 4: PASS**
- [ ] **Krok 5: Zostaw zmiany w working tree**

---

### Task 5: Komponent `ProductionPlanCalendar`

**Pliki:**
- Create: `app/assets/js-vue/src/modules/production/components/ProductionPlanCalendar.vue`
- Modify: `app/assets/js-vue/src/modules/schedule/repository/scheduleRepository.js` - dodaj
  `fetchDepartmentLoad(startDate, endDate, excludeAgreementLineId)`
- Modify: `modules/production/locale/pl.js`, `en.js` - klucze `production.planCalendar.*`
  (`showExistingTasks`, `noAccess`, `loadTitle` z parametrem `{count}`, `deadline`, `loadError`)

**Interfejsy:**
- Konsumuje: `ResourceCalendar` z propsami `range`, `showHeader`, `cellTitle` (Task 2); funkcje z Task 4;
  `fetchDepartmentLoad` (Task 1), `fetchHolidays`, `fetchProductionResources`.
- Produkuje: `<ProductionPlanCalendar v-model="form" :confirmed-date="'YYYY-MM-DD'" :agreement-line-id="int" />`.
  Emituje `input` z nową tablicą `form` (przez `applyEventToForm`) po `event-moved` i `event-resized` paska planu.

Zachowanie:
- `range = defaultRange(value, confirmedDate, dziś)`. Pobranie danych przy zmianie `range.start|range.end` (klucz
  stringowy, żeby przesunięcie paska wewnątrz zakresu nie wywoływało zapytania); stara odpowiedź nie nadpisuje nowszej
  (licznik żądań jak w `CapacityAwareDayPicker`).
- `resources` = `departments` z odpowiedzi; etykieta działu bez dostępu przez slot `resource-label-<id>` z dopiskiem `noAccess`.
- `events` = `planToEvents(value)` + `loadToBackgroundEvents(load)` + `deadlineEvents(...)` + (gdy przełącznik włączony)
  produkcje z `fetchProductionResources` bez tych z `agreementLineId`, z `color: '#e9ecef'`.
- `canEditEvent = isPlanEvent`, `allowCreate = false`, `allowCrossResource = false`.
- `cellTitle(resourceId, date)` → `loadTitle` z liczbą z `load`, pusty string przy 0.
- Przełącznik "Pokaż istniejące zadania" (`b-form-checkbox switch`), domyślnie wyłączony, niezapamiętywany.

- [ ] **Krok 1: Implementacja komponentu, repozytorium, tłumaczeń**
- [ ] **Krok 2: Weryfikacja ręczna przez `DevContainer`** (`/dev`, `StartProductionActionDev.vue`): dodaj tam
  `ProductionPlanCalendar` z przykładowym `form`. Sprawdź: paski w kolorze primary, mapa ciepła w działach, liczba
  w dymku komórki, przeciągnięcie zmienia `form` (wyświetl `JSON.stringify(form)` w dev-komponencie), szare paski po włączeniu.
- [ ] **Krok 3: Zostaw zmiany w working tree**

---

### Task 6: `StartProductionAction` jako drawer z trzema drogami edycji

**Pliki:**
- Modify: `app/assets/js-vue/src/modules/production/components/StartProductionAction.vue`
- Modify: `app/assets/js-vue/src/modules/agreementLineRM/components/LineActions2.vue`,
  `app/assets/js-vue/src/components/common/LineActions.vue`,
  `app/assets/js-vue/src/components/dev/components/StartProductionActionDev.vue`

**Interfejsy:**
- Konsumuje: `ProductionPlanCalendar` (Task 5), `Sidebar` (`components/base/Sidebar.vue`), `StrategySelect`, `StartProductionForm` bez zmian.
- Produkuje: `<StartProductionAction v-model="open" :agreement-line="line" @lineChanged="..." />` - sterowany wyłącznie
  przez `v-model`; slot `open-action` znika. Przy przejściu `value` na `true` wywołuje obecne `getForm(productions)`.

Układ:
- `Sidebar` z `sidebar-class="size-100"`, tytuł `agreement_line_list.startProductionForm.modalTitle`,
  `ValidationObserver` obejmuje całość.
- Góra: wiersz z `StrategySelect` i `StartProductionForm` (pola dat jak dziś), obok wąska kolumna z widgetami
  kontekstu (`agreement-line-widget`, `product-widget`, `agreement-widget`) - zwijane `CollapsibleCard` zostają,
  bo to zawartość panelu, nie szkielet widoku.
- Pod spodem: `ProductionPlanCalendar v-model="form"` na pełną szerokość.
- Stopka (`#sidebar-footer`): Anuluj / Rozpocznij produkcję, logika `startProduction()` bez zmian.
- `onStrategySelected` przypisuje nową tablicę (`this.form = ...`) zamiast mutować `this.form[idx][dateField]`,
  żeby wszystkie trzy miejsca reagowały tak samo.

W `LineActions*`: drawer renderowany **poza** `b-dropdown` (jak `FactorsView`), bo zawartość schowanego menu
dropdown ma `display: none`. Pozycja menu ustawia `startProductionState = true`.

- [ ] **Krok 1: Przebuduj `StartProductionAction`, przepnij `LineActions`, `LineActions2`, dev-komponent**
- [ ] **Krok 2: Weryfikacja ręczna** (`make dev`, `/orders/5`, akcja "Rozpocznij produkcję" na linii z przyszłym terminem):
  1. Wybór strategii → pola i paski zmieniają się razem.
  2. Wpisanie daty w polu → pasek przesuwa się bez przeładowania obciążenia (gdy zakres się nie zmienił).
  3. Przeciągnięcie paska → pole pokazuje nową datę.
  4. Przeciągnięcie paska Pakowania za termin → pole dostaje błąd walidacji, przycisk zapisu odrzuca formularz.
  5. Rozciągnięcie od lewej i od prawej → obie daty w polach.
  6. Zapis → komunikat `addedToSchedule`, drawer się zamyka, lista odświeża status.
  7. Ponowne otwarcie dla innej linii → formularz z danymi tej linii, nie poprzedniej.
  8. Użytkownik z grantem tylko jednego działu → pięć wierszy z "brak dostępu", paski nadal przesuwalne.
- [ ] **Krok 3: `cd app/assets && npx jest` (całość) i `make lint` - bez nowych błędów**
- [ ] **Krok 4: Zostaw zmiany w working tree, zgłoś wynik użytkownikowi**
