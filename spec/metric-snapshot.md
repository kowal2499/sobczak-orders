# Moduł MetricSnapshot - dzienna historia mierników pulpitu

Status: etap 1 zaimplementowany (zapis, odczyt, widok "Dynamika mierników"). Widok jest roboczy -
dostępny pod `/metric-history`, ale celowo bez linku w nawigacji, bo forma prezentacji jest jeszcze
do ustalenia. Kierunek: minimalistyczne wykresy bezpośrednio w kaflach pulpitu, obok wartości.
Etap 2 - pulpit czytający sumy z zapisów - poza zakresem.

## 1. Cel

Pulpit liczy mierniki na żywo i nie pamięta, jak wyglądały wczoraj. Moduł co 30 minut zapisuje
wartości wszystkich kafli dla bieżącego dnia; po północy dzień jest zamrożony. Z tych zapisów
widok "Dynamika mierników" (`/metric-history`) rysuje wykresy zmian w czasie.

## 2. Decyzje projektowe

**Zamrożony stan, nie przeliczanie wstecz.** Wiersz dnia D to ostatnie przeliczenie wykonane w dniu
D. Pokazuje, co wtedy wiedzieliśmy - tego nie da się później odtworzyć z bazy (plan obłożenia,
stan zamówień w realizacji). Miniony dzień można tylko uzupełnić (`--backfill`), i to wyłącznie
gdy nie ma żadnego zapisu.

**Per klient, nie per użytkownik.** Mierniki zamówień i premii są zawężane dla `ROLE_CUSTOMER` do
przypisanych klientów. Zapis ma wymiar `customer_id`, więc widok dowolnego użytkownika to suma
wierszy jego klientów, a widok firmowy - suma wszystkich. Kolektor zawsze liczy firmowo
(`OPTION_COMPANY_WIDE`), niezależnie od tego, kto kliknął "Przelicz". Mierniki firmowe z założenia
(obłożenie) mają `customer_id = 0`. Po przypisaniu użytkownikowi nowego klienta jego wykres pokaże
historię tego klienta także wstecz - świadomie.

**Przepływowe vs stanowe.**

| rodzaj | mierniki | wartość | przyrost |
|---|---|---|---|
| przepływowe | `orders_finished`, `departments_bonus`, `on_time_in`, `on_time_late` | narastająco od 1. dnia miesiąca | różnica dni; 1. dnia = wartość |
| stanowe | `orders_pending`, `capacity`, `weekly_capacity(_limit)`, `attention_lists` | stan na dzień | - |

Obłożenie to plan: `capacity` liczy pełny bieżący i następny miesiąc (`period_key` = `YYYY-MM`),
`weekly_capacity` - 6 tygodni od bieżącego (`period_key` = tydzień ISO). Uzupełnianie wstecz pomija
mierniki stanowe, bo wyszłyby z dzisiejszego stanu.

**Terminowość z widełkami rozliczeń.** `on_time_*` liczone zawsze z
`DepartmentsBonusOnTimeMetricStrategy::DEFAULT_TOLERANCE_DAYS`, zapisanym w `params`. Suwak z pulpitu
to podgląd i do historii nie trafia.

**Jedna reguła sumowania.** `DepartmentFactorRule` decyduje, które rekordy wchodzą do sumy działu -
używa jej i rozliczenie premii, i kolektor (odpowiednik `DepartmentMetricMixin.aggregateByDepartment`).

## 3. Model

Tabela `metric_snapshot`, format długi, unikalny indeks
`(snapshot_date, metric, dimension, period_key, customer_id)`. Puste wartości to `0` / `'-'`, nie
`NULL` - MySQL traktuje NULL-e w indeksie unikalnym jako różne. Przeliczenie zastępuje wszystkie
wiersze dnia (klient, który spadł do zera, znika). Zapisywane są tylko wiersze niezerowe - na danych
z produkcji około 120-150 wierszy dziennie.

`dimension`: `total`, slug działu, nazwa listy uwagi albo `lista:dział` (listy produkcji liczą
zadania w działach, żeby odczyt mógł ukryć działy bez grantu).

## 4. Widoczność

Odczyt (`GET /metric-snapshot/history`, grant `metric-snapshot.view`) stosuje te same reguły co kafle:
mierniki zamówień, premii i list uwagi - `ROLE_PRODUCTION`; terminowość -
`reports.dashboard:on-time-bonus`; obłożenie - `reports.dashboard:capacity-utilization` lub
`ROLE_ADMIN`; tydzień - `reports.dashboard:weekly-capacity`; działy - `production.show.*`; listy -
granty `reports.dashboard:attention-*`. Ręczne przeliczenie dzisiejszego dnia:
`POST /metric-snapshot/capture`, grant `metric-snapshot.manage`, wpis w ActivityLog.

## 5. Cron

Założyć ręcznie w panelu hostingu, osobno dla prod i test:

```bash
*/30 * * * * cd <deploy_path>/current && /usr/local/bin/php82 bin/console app:metric-snapshot:capture --env=prod >> var/log/metric_snapshot.log 2>&1
```

Ostatni bieg dnia (~23:30) to wartość zamrożona; zmiany z ostatnich 30 minut wpadają do dnia
następnego. Cron i przycisk nie liczą równolegle (Symfony Lock `metric_snapshot_capture`) - drugi
dostaje komunikat "Przeliczenie już trwa".

Jednorazowe uzupełnienie bieżącego miesiąca po wdrożeniu (dla każdego minionego dnia):

```bash
bin/console app:metric-snapshot:capture --date=2026-10-01 --backfill
```

## 6. Etap 2 (otwarte)

Kontroler pulpitu ma oddawać sumy kafli z zapisu, gdy zapis jest, a w przeciwnym razie liczyć na
żywo. Pulpit ma filtr miesiąca (domyślnie bieżący, można wybrać miniony i przyszły), więc reguła
zależy od miesiąca i rodzaju miernika:

| miesiąc | stanowe (w realizacji, obłożenie, listy uwagi) | przepływowe (zrealizowane, ukończone, terminowość) |
|---|---|---|
| bieżący | dzisiejszy zapis | dzisiejszy zapis, ale patrz niżej - nieaktualność |
| miniony | zapis z ostatniego dnia miesiąca (na żywo nie da się ich odtworzyć) | **na żywo** |
| przyszły | na żywo (obłożenie jest też w dzisiejszym zapisie jako `period_key` następnego miesiąca) | na żywo (i tak zero) |

Dlaczego przepływowe z minionego miesiąca na żywo: zapis ostatniego dnia to stan "co wiedzieliśmy
31-go". Korekty premii za miniony miesiąc i poprawione daty ukończenia wprowadzone później by w nim
nie wystąpiły (przeszłych dni nie nadpisujemy), więc pulpit pokazywałby inną premię niż rozliczenie.

Zawsze na żywo: zakres inny niż cały miesiąc, widełki terminowości inne niż domyślne (podgląd
suwakiem), szczegóły rekordów (popovery, sidebary) - zapis ma tylko sumy.

Do rozstrzygnięcia dla bieżącego miesiąca: dzisiejszy zapis bywa do 30 minut nieaktualny, a korekta
premii z popovera musi być widoczna od razu. Opcje: odświeżać dzisiejszy zapis po zdarzeniach
zmieniających dane (korekta, ukończenie produkcji) albo pokazywać sumy z zapisu jako pierwsze
wyświetlenie i dociągać wartości na żywo w tle. Odpowiedź powinna nieść informację, skąd pochodzi
wartość (zapis z godziną / na żywo).
