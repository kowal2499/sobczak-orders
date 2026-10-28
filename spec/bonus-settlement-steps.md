# BonusSettlement - kroki wdrożenia

Rozbicie specyfikacji `spec/bonus-settlement.md` na kroki możliwe do osobnego review i wdrożenia.
Każdy krok to jeden PR: zamknięta całość, zielone testy, bezpieczny na produkcji nawet gdy kolejne
kroki nigdy nie powstaną. Kolejność jest wiążąca - każdy krok korzysta wyłącznie z rzeczy
dostarczonych wcześniej.

Skrót kolumny "widoczne dla użytkownika": czy po wdrożeniu tego kroku ktokolwiek zobaczy zmianę.

| # | krok | widoczne dla użytkownika |
|---|---|---|
| 1 | Mapa `departmentSlug -> grant` w jednym miejscu | nie (refaktor) |
| 2 | Szkielet modułu, granty, encje, migracja | granty w panelu uprawnień |
| 3 | `DepartmentFactorsCalculator` i `DepartmentMembershipProvider` | nie |
| 4 | Okresy: lista, utworzenie, podgląd | tylko API |
| 5 | Przeliczenie i zerowanie korekt | tylko API |
| 6 | Korekta wiersza | tylko API |
| 7 | Zamknięcie i ponowne otwarcie okresu | tylko API |
| 8 | Widok: wybór okresu i tabela (tryb odczytu) | tak, po wejściu na adres |
| 9 | Widok: akcje nagłówka i korekty w popoverze | tak |
| 10 | Eksport do Excela, menu, tłumaczenia | tak, moduł widoczny w menu |

Moduł jest niedostępny dla użytkowników aż do kroku 10 (wejście w menu). Do tego czasu adres
`/bonus-settlement` istnieje, ale nikt na niego nie trafi przypadkiem, a granty i tak go pilnują.

---

## Krok 1. Mapa `departmentSlug -> grant` w jednym miejscu

**Dlaczego osobno:** to refaktor istniejącego kodu, nie ma nic wspólnego z premiami. Zmieszany
z nową funkcją zaszumiłby review.

**Zakres**

- Nowy `App\Module\Production\ValueObject\DepartmentGrantMap` z metodami `all(): array` (slug =>
  grant) i `grantFor(string $slug): ?string`.
- Podmiana prywatnych map w `Reports\Schedule\Service\ScheduleOrderResourcesService` (linie 24-29)
  i `ScheduleProductionResourcesService` (linie 18-23).

**Definicja ukończenia**

- Test jednostkowy: mapa zawiera dokładnie sześć działów `dpt01`-`dpt06`.
- `grep` po `production.show.gluing` w `src/` daje trafienia tylko w `module.yaml` i nowym VO.
- `make test` i `make check` zielone, istniejące testy Schedule bez zmian.

**Ryzyko:** żadne, zachowanie identyczne.

---

## Krok 2. Szkielet modułu, granty, encje, migracja

**Zakres**

- `src/Module/BonusSettlement/` z `module.yaml` (granty `bonus-settlement.view` i
  `bonus-settlement.manage`), `config.yaml`, `routes.yaml`.
- Encje `BonusPeriod` i `BonusPeriodEntry` wraz z `BonusPeriodStatus` (punkt 3 spec), atrybuty PHP,
  unikaty na `(year, month)` i `(period_id, user_id, department_slug)`.
- `getEffectiveFactors()` na wpisie - uwaga na pułapkę `??`: korekta `0` musi wygrać z wsadem.
- Repozytoria `BonusPeriodRepository`, `BonusPeriodEntryRepository`.
- Rejestracja mapowania w `config/packages/doctrine.yaml` pod `orm.mappings`.
- Migracja w `app/migrations/`.

**Definicja ukończenia**

- `bin/console doctrine:schema:validate` bez rozjazdu po migracji.
- Migracja przechodzi w obie strony (`up` i `down`) na kopii bazy.
- Test jednostkowy `getEffectiveFactors()`: `null` daje wsad, `0` daje zero, wartość daje wartość.
- Granty widoczne w panelu uprawnień, nikomu nie przypisane.
- `bin/console cache:clear --env=test` przed uruchomieniem testów.

**Ryzyko:** migracja na produkcji tworzy dwie puste tabele, brak wpływu na istniejące dane.

---

## Krok 3. Wyliczanie wsadu i przynależności

**Zakres**

- `Service\DepartmentFactorsCalculator::forRange(from, to, toleranceDays): array` - woła miernik
  `departments_bonus_on_time` i sumuje `factors.factor` po działach z regułą frontu: pomijamy
  `inRange === false`, `onTime === false` i ghosty.
- `Service\DepartmentMembershipProvider` - iteracja po aktywnych użytkownikach
  (`User::isActive()`), `GrantsResolver::getGrants()` (nie `isGranted()`), dopasowanie do
  `DepartmentGrantMap` z kroku 1.

**Definicja ukończenia** (testy jednostkowe, `tests/Unit/Modules/BonusSettlement/`)

- Kalkulator: rekord spóźniony, rekord poza zakresem i ghost nie wchodzą do sumy; rekord
  terminowy wchodzi; suma jest per dział.
- Test parzystości z regułą z `DepartmentMetricMixin.aggregateByDepartment()` - komentarz w teście
  wskazujący plik frontowy, żeby rozjazd był łatwy do namierzenia.
- Membership: użytkownik w dwóch działach daje dwa wpisy; bez grantu działowego zero wpisów;
  administrator z `authorization.admin`, ale bez grantów działowych, nie dostaje sześciu wpisów;
  użytkownik nieaktywny jest pomijany.

**Uwaga wykonawcza:** miernik przyjmuje `Security` w konstruktorze. Jeśli kiedykolwiek wołalibyśmy
kalkulator z CLI, trzeba to sprawdzić - na razie wołamy go wyłącznie z requestu HTTP.

**Ryzyko:** żadne, serwisy nie mają jeszcze wywołań.

---

## Krok 4. Okresy: lista, utworzenie, podgląd

**Zakres**

- `CreateBonusPeriodCommand` i handler: walidacja `year`, `month` 1-12, odrzucenie duplikatu.
- `GetBonusPeriodsQuery` (lista ze statusem) i `GetBonusPeriodQuery` (okres z wierszami, na razie
  pustymi).
- `Controller\BonusPeriodController`: `GET /bonus-settlement/periods`, `POST /bonus-settlement/periods`,
  `GET /bonus-settlement/periods/{id}`.
- Read modele odpowiedzi w camelCase.

**Definicja ukończenia** (E2E, `tests/End2End/Modules/BonusSettlement/`)

- Utworzenie okresu zwraca 201 i zapisuje wiersz ze statusem `OPEN`.
- Powtórzone `(year, month)` daje błąd, nie wyjątek 500.
- `GET /periods/{id}` zwraca okres z pustą listą wierszy.
- Brak grantu `manage` daje 403 na `POST`, brak `view` daje 403 na `GET`.

---

## Krok 5. Przeliczenie i zerowanie korekt

**Zakres**

- `RecalculateBonusPeriodCommand(periodId, ?toleranceDays)` i handler: zakres dobowy od pierwszego
  do ostatniego dnia miesiąca, kalkulator plus membership z kroku 3, zapis `factorsCalculated`,
  `userLabel`, `departmentLabel`, `calculatedAt` i `toleranceDays` na okresie.
- Zachowanie korekt po kluczu `użytkownik + dział`: `factorsAdjusted`, `note` i `adjustedAt`
  przechodzą nietknięte, `adjustedAt` celowo nie jest odświeżane.
- Wiersz użytkownika, który stracił grant działowy, znika razem z korektą.
- `ResetBonusPeriodAdjustmentsCommand` - czyści trzy pola w całym okresie.
- Wpisy `bonus.period.recalculated` i `bonus.period.adjustments_reset` w `ActivityLog` na kanale
  `activity_log` (wstrzyknięty `monolog.logger.activity_log`).
- Endpointy `POST /periods/{id}/recalculate` i `POST /periods/{id}/reset-adjustments`.

**Definicja ukończenia** (E2E)

- Przeliczenie tworzy wiersze o oczekiwanych wartościach dla przygotowanych produkcji.
- Ponowne przeliczenie odświeża `factorsCalculated`, zachowując `factorsAdjusted` i `note`.
- Ponowne przeliczenie nie odświeża `adjustedAt`, więc korekta sprzed przeliczenia spełnia
  `adjustedAt < calculatedAt`.
- Utrata grantu działowego usuwa wiersz.
- Zerowanie czyści `factorsAdjusted`, `note` i `adjustedAt` w całym okresie.
- Obie akcje zapisują wpis w `ActivityLog`.

**Ryzyko:** najcięższy krok merytorycznie. Warto wdrożyć go osobno i przeliczyć jeden zamknięty
miesiąc historyczny, porównując sumy z kaflem "Ukończone zadania produkcyjne (w terminie)".

---

## Krok 6. Korekta wiersza

**Zakres**

- `AdjustBonusEntryCommand(entryId, ?factorsAdjusted, ?note)` i handler: ustawia `adjustedAt`,
  a wyczyszczenie korekty zeruje wszystkie trzy pola.
- `PUT /bonus-settlement/entries/{id}`.
- Wpis `bonus.entry.adjusted` z wartością przed i po oraz notatką.

**Definicja ukończenia** (E2E)

- Zapis korekty ustawia wartość, notatkę i `adjustedAt`.
- Korekta `0` jest zapisywana i wygrywa z wsadem w wartości obowiązującej.
- Wyczyszczenie zeruje trzy pola.
- Brak grantu `manage` daje 403.

---

## Krok 7. Zamknięcie i ponowne otwarcie okresu

**Zakres**

- `CloseBonusPeriodCommand` (`CLOSED`, `closedAt`, `closedBy`) i `ReopenBonusPeriodCommand`
  (powrót do `OPEN`, czyszczenie `closedAt` i `closedBy`).
- Strażnik niezmienności w handlerach kroków 5 i 6: przeliczenie, zerowanie i korekta odrzucane
  przy `CLOSED`. Warunek siedzi w handlerze, nie w kontrolerze.
- Endpointy `POST /periods/{id}/close` i `POST /periods/{id}/reopen`.
- Wpisy `bonus.period.closed` i `bonus.period.reopened`.

**Definicja ukończenia** (E2E)

- Zamknięcie blokuje przeliczenie, zerowanie i korektę (oczekiwane błędy, nie 500).
- Ponowne otwarcie odblokowuje te akcje i zapisuje `bonus.period.reopened`.
- Brak grantu `manage` daje 403 na obu akcjach.

**Uwaga:** dopiero po tym kroku API jest kompletne. Backend nadaje się do wdrożenia niezależnie od
frontu - można nim rozliczyć miesiąc z poziomu klienta HTTP.

---

## Krok 8. Widok: wybór okresu i tabela w trybie odczytu

**Zakres**

- Trasa i szablon Twig pod `/bonus-settlement`, `SectionBlock` i `SectionBlockTitle` z okruszkami,
  bez `{% block breadcrumbs %}`.
- `assets/js-vue/src/modules/bonus-settlement/` z `repository/bonusSettlementRepository.js`.
- Wybór okresu z listy rozwijanej w nagłówku, nagłówek okresu (status, tolerancja, data
  przeliczenia, suma przyznana), tabela na `TablePlus` grupowana po pracowniku z kolumną "Razem".
- Tłumaczenia `bonus_settlement.*` w `locale/pl.json` i `en.json`.

**Definicja ukończenia**

- Widok renderuje istniejący okres, w tym zamknięty, bez żadnej akcji zapisu.
- Brak wejścia w menu (dochodzi w kroku 10), adres pilnowany grantem `bonus-settlement.view`.

---

## Krok 9. Widok: akcje nagłówka i korekty w popoverze

**Zakres**

- Utworzenie okresu, `Przelicz`, `Wyzeruj korekty`, `Zamknij okres`, `Otwórz ponownie` - każda
  w `ConfirmationModal` z treścią z tabeli 8.5, widoczna zależnie od statusu i grantu `manage`.
- Kolumna "Przyznano" na `FactorCell` z `trigger="click"`, `tone="value"`, w slocie
  `BonusAdjustmentForm` (wartość wyliczona do odczytu, pole korekty, notatka, czyszczenie).
- Znacznik przeterminowanej korekty: ikona z tooltipem przy
  `adjustedAt !== null && adjustedAt < period.calculatedAt`.
- Stan zamknięty: plakietka z datą i autorem, popovery nieaktywne, znikają `Przelicz`,
  `Wyzeruj korekty` i `Zamknij okres`.
- Odsyłacz z wiersza do raportu on-time z ustawionym działem i miesiącem.

**Definicja ukończenia**

- Pełny obieg wyklikany ręcznie: utwórz, przelicz, popraw wiersz, zamknij, otwórz ponownie.
- Sprawdzone, że w stanie zamkniętym nie da się nic zapisać z poziomu UI.
- Czerwień pojawia się wyłącznie przy ręcznym obcięciu, nie przy spóźnieniu.

---

## Krok 10. Eksport, menu, tłumaczenia, domknięcie

**Zakres**

- Eksport po stronie klienta na `ExcelExport` wzorem `Metrics/BaseMetric.js::exportExcel()`:
  kolumny pracownik, dział, wyliczono, przyznano, notatka; w nagłówku arkusza okres i tolerancja.
- Wejście w `templates/_sidebar.html.twig` za `is_granted('bonus-settlement.view')`.
- Uzupełnienie tłumaczeń backendowych, jeśli jakieś komunikaty ich wymagają.
- `make check`, `make test`, `make cc`.

**Definicja ukończenia**

- Plik otwiera się w Excelu, liczby zgodne z ekranem.
- Pozycja w menu widoczna tylko z grantem.
- Aktualizacja `CLAUDE.md` o sekcję modułu, jeśli review uzna to za potrzebne.

---

## Co świadomie zostaje poza planem

- Stawki i przeliczanie współczynników na złotówki.
- Automatyczne zakładanie okresów - miesiąc otwiera człowiek.
- Osobna encja przypisań kadrowych - snapshot w wierszu wystarcza (punkt 2 spec).
- Endpoint eksportu na backendzie - eksport jest po stronie klienta.
