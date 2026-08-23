# Moduł BonusSettlement - rozliczanie premii pracowników produkcyjnych

Status: szkic do akceptacji. Dokument opisuje docelowy kształt modułu, nie stan faktyczny.

## 1. Cel

Ustalanie miesięcznej premii pracowników produkcyjnych. Moduł nie liczy produkcji od nowa -
bierze gotowy wynik miernika "Ukończone zadania produkcyjne (w terminie)", mapuje go na
użytkowników według działów, w których mają role, i utrwala wynik przez zamknięcie miesiąca.

Zakres:

- wyliczenie wsadu (współczynniki per dział za miesiąc),
- rozdanie wsadu użytkownikom (każdy dostaje pełną sumę swojego działu),
- ręczna korekta wartości przez administratora,
- zamknięcie okresu, po którym wartości nie zmieniają się same.

Poza zakresem: stawki, przeliczanie współczynników na złotówki, lista płac, eksport do kadr.
Moduł operuje na współczynnikach.

## 2. Decyzje projektowe

**Snapshot zamiast wersjonowanej kadry.** Wartość jest utrwalana w wierszu przy zamknięciu
miesiąca, więc przypisanie użytkownika do działu musi być poprawne tylko w chwili liczenia.
Dlatego nie budujemy osobnej encji przypisań kadrowych - wystarczą istniejące role i granty.

**Premia zespołowa.** Każdy użytkownik działu dostaje pełną sumę współczynników tego działu.
Nie dzielimy jej między ludzi. Użytkownik z rolami w kilku działach dostaje osobny wiersz z
pełną sumą dla każdego z nich.

**Korekta nie nadpisuje wsadu.** Wyliczenie i korekta to dwa osobne pola. Zawsze widać, ile
policzył system i ile przyznał człowiek.

**Zamrażamy też zasady.** Na okresie zapisujemy tolerancję użytą przy liczeniu, a na wierszu
nazwę użytkownika i działu jako tekst, żeby zamknięty miesiąc dało się wyświetlić po zmianie
konfiguracji, zmianie nazwy albo usunięciu użytkownika.

## 3. Model danych

### `BonusPeriod` (tabela `bonus_period`)

| pole | typ | uwagi |
|---|---|---|
| `id` | int | |
| `year` | smallint | |
| `month` | smallint | 1-12; unikat na `(year, month)` |
| `status` | string | `OPEN` / `CLOSED` (enum `BonusPeriodStatus`) |
| `toleranceDays` | smallint | widełki terminowości użyte przy ostatnim przeliczeniu |
| `calculatedAt` | datetime nullable | |
| `closedAt` | datetime nullable | |
| `closedBy` | int nullable | FK do `user` |

### `BonusPeriodEntry` (tabela `bonus_period_entry`)

| pole | typ | uwagi |
|---|---|---|
| `id` | int | |
| `period` | FK | `BonusPeriod`, `onDelete: CASCADE` |
| `user` | int | FK do `user`, bez `onDelete` (RESTRICT) |
| `userLabel` | string | imię i nazwisko w chwili liczenia |
| `departmentSlug` | string(10) | `dpt01`-`dpt06` |
| `departmentLabel` | string | nazwa działu w chwili liczenia |
| `factorsCalculated` | float | wsad z raportu, tylko do odczytu |
| `factorsAdjusted` | float nullable | korekta ręczna |
| `note` | text nullable | uzasadnienie korekty |
| `adjustedAt` | datetime nullable | kiedy zapisano korektę, informacyjnie |
| `adjustedAgainst` | float nullable | wsad, przy którym zapadła decyzja o korekcie; rozjazd z `factorsCalculated` oznacza korektę oderwaną od aktualnego wyliczenia (patrz punkt 8.4) |

Unikat na `(period_id, user_id, department_slug)`.

`user_id` jest wymagane. Aplikacja nie kasuje użytkowników (dezaktywacja to `User::$active`), a
`NULL` rozbroiłby ten unikat (MySQL traktuje `NULL` jako wartości różne) i dopasowanie korekt przy
przeliczeniu, które idzie po kluczu użytkownik + dział. Ręczne usunięcie użytkownika z rozliczonymi
premiami ma się nie udać. `userLabel` zostaje - chroni przed zmianą nazwiska, nie przed usunięciem.

Współczynniki są typu `float`, jak wszystkie współczynniki w projekcie (`Factor::$factorValue`).
Gdy dojdzie mnożnik przeliczający współczynnik na pieniądze, kwota jest osobną wielkością i to
ona - a nie współczynnik - zasługuje na typ dokładny.

Wartość obowiązująca to `factorsAdjusted ?? factorsCalculated` - metoda pomocnicza
`getEffectiveFactors()` na encji. Odebranie premii to `factorsAdjusted = 0` z notatką, a nie
osobna flaga wykluczenia ani kasowanie wiersza.

Rejestracja mapowania w `config/packages/doctrine.yaml` pod `orm.mappings` plus migracja w
`app/migrations/`.

## 4. Źródło wsadu

Miernik `departments_bonus_on_time`
(`App\Module\Reports\Production\Metric\DepartmentsBonusOnTimeMetricStrategy`) zwraca listę
`ProductionReportRecordDTO` za zadany zakres dat.

**Uwaga: sumowanie żyje dziś tylko na froncie.** `DepartmentMetricMixin.aggregateByDepartment()`
sumuje `factors.factor` z warunkiem `onTime !== false && inRange !== false`. Backend zwraca
rekordy spóźnione ze współczynnikiem, a zeruje je dopiero przeglądarka.

Dlatego moduł potrzebuje serwerowego odpowiednika tej reguły:

```
App\Module\BonusSettlement\Service\DepartmentFactorsCalculator
    ->forRange(\DateTimeInterface $from, \DateTimeInterface $to, int $toleranceDays): array
    // zwraca [departmentSlug => suma współczynników]
```

Warunek sumowania musi być identyczny z frontowym: pomijamy `inRange === false`,
pomijamy `onTime === false`, pomijamy ghosty. Test jednostkowy porównujący obie reguły jest
obowiązkowy - rozjazd oznacza, że pulpit pokazuje co innego niż rozliczenie.

Tolerancja: domyślnie `DepartmentsBonusOnTimeMetricStrategy::DEFAULT_TOLERANCE_DAYS`, przekazywana
przez `$options['toleranceDays']`. Wartość faktycznie użyta trafia na `BonusPeriod::toleranceDays`.

Zakres miesiąca liczymy od pierwszego do ostatniego dnia miesiąca okresu; miernik rozlicza po
`completedAt`, więc granice są dobowe.

## 5. Mapowanie działów na użytkowników

Odwzorowanie `departmentSlug -> grant` istnieje już dwa razy, zduplikowane w
`Reports\Schedule\Service\ScheduleOrderResourcesService` i `ScheduleProductionResourcesService`.
Przed napisaniem trzeciej kopii wyciągamy je do jednego miejsca (np.
`App\Module\Production\ValueObject\DepartmentGrantMap`) i podmieniamy w obu serwisach.

Nie ma zapytania odwrotnego "kto ma grant X" - `GrantsResolver` działa per użytkownik i jest
cache'owany. Przy liczbie pracowników rzędu kilkudziesięciu wystarczy iteracja:

```php
foreach ($activeUsers as $user) {
    $grants = $this->grantsResolver->getGrants($user);   // NIE isGranted()
    foreach (DepartmentGrantMap::all() as $slug => $grant) {
        if (in_array($grant, $grants, true)) { /* utwórz wiersz */ }
    }
}
```

Świadomie używamy `getGrants()`, a nie `isGranted()` - to drugie zwraca `true` na wszystko dla
`authorization.admin` i wygenerowałoby administratorowi komplet sześciu działów.

Serwis: `App\Module\BonusSettlement\Service\DepartmentMembershipProvider`.

Grant działowy jest jedynym kryterium: użytkownik bez żadnego z sześciu grantów nie trafia do
rozliczenia w ogóle (nie tworzymy dla niego pustych wierszy). Nie wprowadzamy osobnego
znacznika uczestnictwa.

Skutek uboczny do zaakceptowania: kierownik mający podgląd wszystkich działów dostanie sześć
wierszy. Przy edytowalnych wierszach to kwestia sprzątania, nie modelu danych - wpisuje mu się
`0` z notatką.

## 6. Przepływ

1. **Utworzenie okresu** - `CreateBonusPeriod(year, month)`, status `OPEN`. Wyłącznie ręcznie,
   z listy okresów. Przeliczenie nie zakłada brakującego miesiąca w locie: rozliczeniu
   podlegają tylko te miesiące, które ktoś świadomie otworzył.
2. **Przeliczenie** - `RecalculateBonusPeriod(periodId, ?toleranceDays)`:
   - dozwolone tylko przy `OPEN`,
   - liczy sumy działów, pobiera przynależności, odświeża `factorsCalculated`,
   - **korekty są zachowywane** - dopasowanie po kluczu `użytkownik + dział`, pola
     `factorsAdjusted`, `note`, `adjustedAt` i `adjustedAgainst` przechodzą na nowy wiersz
     nietknięte; `adjustedAgainst` celowo nie jest odświeżane, bo to na nim opiera się
     ostrzeżenie o korekcie do nieaktualnego wyliczenia (punkt 8.4),
   - wiersz, którego użytkownik stracił grant działowy, znika razem z korektą,
   - akcja za potwierdzeniem w UI.

   Skutek uboczny do świadomego zaakceptowania: korekta wpisana do poprzedniego wsadu
   zostaje przy nowym, nawet jeśli wsad się zmienił. Widok musi więc pokazywać obie
   wartości obok siebie, żeby dało się wychwycić korektę oderwaną od aktualnego wyliczenia
   (patrz punkt 8).
3. **Zerowanie korekt** - `ResetBonusPeriodAdjustments(periodId)`: czyści `factorsAdjusted`,
   `note`, `adjustedAt` i `adjustedAgainst` we wszystkich wierszach okresu, przywracając czysty
   wsad. Osobny przycisk, dozwolone tylko przy `OPEN`, za potwierdzeniem w UI.
4. **Korekta** - `AdjustBonusEntry(entryId, factorsAdjusted, note)`, tylko przy `OPEN`; ustawia
   `adjustedAt` na czas zapisu i `adjustedAgainst` na bieżący wsad. Wyczyszczenie korekty zeruje
   wszystkie cztery pola.
5. **Zamknięcie** - `CloseBonusPeriod(periodId)`: ustawia `CLOSED`, `closedAt`, `closedBy`.
   **Zamknięty okres jest niezmienny** - przeliczenie, zerowanie i korekty są zablokowane.
   Żeby cokolwiek poprawić, trzeba go najpierw otworzyć ponownie.
6. **Otwarcie ponowne** - `ReopenBonusPeriod(periodId)`: wraca do `OPEN`, czyści `closedAt`
   i `closedBy`. Za grantem `bonus-settlement.manage`, za potwierdzeniem w UI, **zawsze
   logowane** - to jedyna droga do zmiany rozliczonego miesiąca, więc musi zostawiać ślad.

Każda zmiana wartości i każda zmiana statusu idzie do `ActivityLog` na kanale `activity_log`
(typy `bonus.period.closed`, `bonus.period.reopened`, `bonus.period.recalculated`,
`bonus.period.adjustments_reset`, `bonus.entry.adjusted`; pola: okres, użytkownik, dział,
wartość przed i po, notatka). To pieniądze - historia musi być odtwarzalna.

## 7. API

Prefiks `/bonus-settlement`, kontrolery cienkie, CQRS zgodnie z konwencją projektu.

| metoda | ścieżka | grant | opis |
|---|---|---|---|
| GET | `/periods` | `bonus-settlement.view` | lista okresów ze statusem |
| POST | `/periods` | `bonus-settlement.manage` | utwórz okres |
| GET | `/periods/{id}` | `bonus-settlement.view` | okres z wierszami |
| POST | `/periods/{id}/recalculate` | `bonus-settlement.manage` | przelicz wsad, zachowaj korekty |
| POST | `/periods/{id}/reset-adjustments` | `bonus-settlement.manage` | wyzeruj wszystkie korekty |
| POST | `/periods/{id}/close` | `bonus-settlement.manage` | zamknij |
| POST | `/periods/{id}/reopen` | `bonus-settlement.manage` | otwórz ponownie |
| PUT | `/entries/{id}` | `bonus-settlement.manage` | korekta wartości |

Odpowiedzi w camelCase, komunikaty w JSON (bez `addFlash`).

Cztery akcje modyfikujące okres (`recalculate`, `reset-adjustments`, `close`, `reopen`)
odrzucają żądanie, gdy status okresu na to nie pozwala - walidacja siedzi w handlerze, nie w
kontrolerze, żeby ten sam warunek obowiązywał niezależnie od źródła wywołania.

## 8. Frontend

`assets/js-vue/src/modules/bonus-settlement/`, repozytorium
`repository/bonusSettlementRepository.js`, tłumaczenia `locale/pl.json` i `en.json` w
przestrzeni `bonus_settlement.*`.

### 8.1. Jeden widok zamiast listy i szczegółów

Rozliczenie to rutyna raz w miesiącu: otwórz okres, przelicz, popraw kilka wierszy, zamknij.
Osobny ekran z listą okresów byłby stroną, przez którą się tylko przechodzi, więc całość
mieści się pod jednym adresem `/bonus-settlement`, a okres wybiera się z rozwijanej listy
w nagłówku. Lista pokazuje istniejące miesiące ze statusem, co zastępuje widok archiwum.

```
+- SectionBlock ------------------------------------------------------------+
| Rozliczenie premii                             [ Sierpien 2026  v ]       |
| Premie > Rozliczenie                             + Utworz okres           |
+---------------------------------------------------------------------------+

+- SectionBlock -- naglowek okresu -----------------------------------------+
|  * OTWARTY      Tolerancja: 5 dni roboczych                               |
|  Przeliczono: 2026-09-01 09:14           Suma przyznana: 412,50           |
|                                                                           |
|  [ Przelicz ]  [ Wyzeruj korekty ]  [ Excel ]        [ Zamknij okres ]    |
+---------------------------------------------------------------------------+

+- SectionBlock -- wiersze -------------------------------------------------+
| Pracownik           Dzial           Wyliczono   Przyznano        Razem    |
| ------------------------------------------------------------------------- |
| Jan Kowalski        Lakierowanie        84,00      84,00                  |
|                     Pakowanie          31,50      31,50        115,50     |
| ------------------------------------------------------------------------- |
| Anna Nowak          Szlifowanie        62,00    e 40,00 !       40,00     |
| ------------------------------------------------------------------------- |
| Piotr Wisniewski    CNC                97,00      97,00         97,00     |
+---------------------------------------------------------------------------+
```

Widok Twig plus `SectionBlock` i `SectionBlockTitle` z okruszkami, zgodnie z konwencją
projektu. Tabela na `components/base/TablePlus.vue` (przyklejony nagłówek, sortowanie).

### 8.2. Grupowanie po pracowniku

Wiersze grupujemy po pracowniku, działy są podwierszami, a kolumna "Razem" sumuje na
poziomie osoby. Premię wypłaca się człowiekowi i to jest jedyne pytanie zadawane tej tabeli -
grupowanie po dziale byłoby wierniejsze wobec źródła danych, ale wtedy sumy per osoba nie da
się odczytać z ekranu.

Pivot `pracownik x 6 działów` odpada: większość ludzi ma jeden dział, więc siatka byłaby
w większości pusta.

### 8.3. Edycja korekty w popoverze

Kolumna "Przyznano" to `FactorCell` z `trigger="click"`, a w slocie treści `BonusAdjustmentForm`
- ten sam gest, który kierownik zna z kafla "Ukończone zadania produkcyjne (w terminie)".
W popoverze: wartość wyliczona tylko do odczytu, pole korekty, notatka i przycisk czyszczenia
korekty. Bez osobnego modala i bez trybu edycji całej tabeli.

Tone `value`, nie `timeliness`. Czerwień w tym module znaczy "obcięto ręcznie", a nie "premia
przepadła przez termin", więc nie może wyglądać jak w raporcie on-time.

### 8.4. Znacznik przeterminowanej korekty

Korekty przeżywają przeliczenie (punkt 6.2), więc prędzej czy później ktoś obetnie premię do
40 przy wsadzie 50, wsad urośnie potem do 62, a korekta 40 zostanie i nikt tego nie zauważy.
Sama obecność obu liczb obok siebie nie wystarczy, bo nikt nie pamięta, która jest starsza.

Dlatego `BonusPeriodEntry` ma pole `adjustedAgainst` (patrz punkt 3): wsad zapamiętany w chwili
zapisu korekty, przenoszony razem z nią przy przeliczeniu. Warunek ostrzeżenia liczy backend
(`BonusPeriodEntry::isAdjustmentStale()`), a odpowiedź API niesie gotowy `adjustmentStale`:

```
adjustedAgainst !== null && abs(adjustedAgainst - factorsCalculated) > 0.005
```

Porównujemy wartości, nie daty. Warunek na datach (`adjustedAt < calculatedAt`) zapalałby się po
każdym przeliczeniu we **wszystkich** skorygowanych wierszach, również tych, których wsad się nie
ruszył - a ostrzeżenie, które zawsze świeci, przestaje być ostrzeżeniem. Tolerancja 0,005 zjada
końcówki sumowania floatów, niewidoczne przy dwóch miejscach po przecinku.

Przy wartości pojawia się wtedy ikona ostrzeżenia z tooltipem "korekta do nieaktualnego
wyliczenia". Czyszczenie korekty zeruje `adjustedAgainst` razem z `factorsAdjusted`, `note`
i `adjustedAt`.

### 8.5. Akcje nagłówka

Każda za potwierdzeniem w `ConfirmationModal`, widoczna tylko przy odpowiednim statusie:

| akcja | status | treść potwierdzenia |
|---|---|---|
| Przelicz | `OPEN` | wsad zostanie odświeżony, korekty zachowane |
| Wyzeruj korekty | `OPEN` | wszystkie korekty i notatki zostaną usunięte, bez odwrotu |
| Zamknij okres | `OPEN` | po zamknięciu nic nie da się zmienić bez ponownego otwarcia |
| Otwórz ponownie | `CLOSED` | otwarcie rozliczonego miesiąca zostanie zapisane w dzienniku |
| Eksport do Excela | dowolny | brak, akcja nie zmienia danych |

### 8.6. Stan zamknięty

Plakietka `ZAMKNIĘTY` z datą i autorem zamknięcia, popovery nieaktywne, przyciski `Przelicz`,
`Wyzeruj korekty` i `Zamknij okres` znikają. Zostają `Excel` i `Otwórz ponownie`.

### 8.7. Odsyłacz do źródła

Przy każdym wierszu link do raportu on-time z ustawionym działem i miesiącem. Pierwsze pytanie
po każdym rozliczeniu brzmi "skąd te 62?" - bez odsyłacza odpowiedź wymaga ręcznego klikania
po pulpicie.

### 8.8. Eksport

Po stronie klienta, tak jak w miernikach pulpitu: `ExcelExport` z
`@/services/ExcelExport/ExcelExport`, wzorzec z `Metrics/BaseMetric.js::exportExcel()`. Brak
endpointu na backendzie. Kolumny: pracownik, dział, wyliczono, przyznano, notatka; w nagłówku
arkusza okres i użyta tolerancja.

Wejście do modułu w menu za `this.$user.can('bonus-settlement.view')`.

## 9. Uprawnienia

Nowe granty w `module.yaml`:

- `bonus-settlement.view` - podgląd okresów i wierszy, eksport do Excela,
- `bonus-settlement.manage` - tworzenie, przeliczanie, zerowanie korekt, korekty, zamykanie
  i ponowne otwieranie okresu.

Moduł pokazuje dane firmowe, więc bez filtrowania po `ROLE_CUSTOMER`. Sam grant `view` jest
wystarczającą bramką - nie wpuszczamy tu nikogo bez niego.

## 10. Testy

Jednostkowe (`tests/Unit/Modules/BonusSettlement/`):

- `DepartmentFactorsCalculator` - sumowanie zgodne z regułą frontu: rekordy `onTime = false`,
  `inRange = false` i ghosty nie wchodzą do sumy,
- `DepartmentMembershipProvider` - użytkownik w dwóch działach daje dwa wiersze; użytkownik
  bez grantu działowego nie daje żadnego; administrator z kompletem grantów nie dostaje
  sześciu wierszy z tytułu samego `authorization.admin`,
- `getEffectiveFactors()` - `0` jako korekta wygrywa z wsadem (pułapka `??` kontra falsy).

End2End (`tests/End2End/Modules/BonusSettlement/`):

- przeliczenie okresu tworzy wiersze o oczekiwanych wartościach,
- **ponowne przeliczenie zachowuje `factorsAdjusted` i `note`** po kluczu użytkownik + dział,
  odświeżając samo `factorsCalculated`,
- wiersz użytkownika, który stracił grant działowy, znika przy ponownym przeliczeniu,
- ponowne przeliczenie nie odświeża `adjustedAgainst`, więc korekta do wsadu, który się zmienił,
  spełnia warunek ostrzeżenia, a korekta do wsadu bez zmian - nie,
- zerowanie korekt czyści `factorsAdjusted`, `note`, `adjustedAt` i `adjustedAgainst` w całym
  okresie,
- zamknięcie blokuje przeliczenie, zerowanie i korektę (oczekiwane błędy),
- ponowne otwarcie odblokowuje te akcje i zapisuje wpis `bonus.period.reopened`
  w `ActivityLog`,
- korekta zapisuje wartość, notatkę i wpis w `ActivityLog`,
- brak grantu `manage` daje 403 na przeliczeniu, zerowaniu, zamknięciu i otwarciu ponownym.

## 11. Etapy wdrożenia

1. Wyciągnięcie mapy `departmentSlug -> grant` do jednego miejsca, podmiana w dwóch serwisach
   Schedule. Osobny, samodzielny commit.
2. Serwerowy `DepartmentFactorsCalculator` plus testy zgodności z regułą frontu.
3. Encje, migracja, rejestracja mapowania w `doctrine.yaml`, szkielet modułu
   (`module.yaml`, `config.yaml`, `routes.yaml`).
4. Komendy, handlery, zapytania, kontrolery plus testy E2E.
5. Frontend: widok okresu z wyborem miesiąca, tabelą grupowaną po pracowniku, korektami
   w popoverze i kompletem akcji nagłówka (punkt 8).
6. Eksport do Excela na `ExcelExport`.
7. Wpięcie w menu, tłumaczenia, `make check` i `make test`.

Po każdej istotnej zmianie API czyścimy cache Symfony.

## 12. Decyzje domknięte

Cztery pytania otwarte przy pierwszej wersji szkicu zostały rozstrzygnięte i wbudowane
w plan powyżej:

1. **Przeliczenie zachowuje korekty** (klucz `użytkownik + dział`), a do ich skasowania
   służy osobny przycisk "Wyzeruj korekty". Obie akcje za potwierdzeniem w UI. (punkt 6)
2. **Premia obejmuje wszystkich z grantem działowym**; użytkownicy bez grantu są pomijani,
   bez osobnego znacznika uczestnictwa. (punkt 5)
3. **Zamknięty okres jest niezmienny.** Zmiana wymaga akcji ponownego otwarcia, za grantem
   `bonus-settlement.manage` i zawsze zapisywanej w `ActivityLog`. (punkt 6)
4. **Eksport do Excela jest w zakresie**, po stronie klienta, na istniejącym
   `ExcelExport`. (punkt 8)
