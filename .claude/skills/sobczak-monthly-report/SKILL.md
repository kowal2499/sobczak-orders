---
name: sobczak-monthly-report
description: Miesięczne zestawienie zmian w aplikacji Sobczak dla klienta (Piotr Sobczak), na podstawie historii gita. Użyj, gdy użytkownik prosi o podsumowanie zmian z miesiąca, notatkę dla klienta lub listę nowości.
---

# Miesięczne zestawienie zmian dla klienta

Krótka lista zmian z danego miesiąca, którą Piotr Sobczak (właściciel firmy, nie programista)
ma przeczytać w kilka sekund. Gotowy tekst wklejasz do czatu, nie tworzysz pliku.

## Zakres

- Domyślnie bieżący miesiąc. Jeśli dziś jest 1-5 dzień miesiąca, a użytkownik nie podał okresu,
  zapytaj, czy chodzi o miesiąc poprzedni.
- Jeśli użytkownik poda poprzednią notatkę (PDF/tekst), przeczytaj ją i nie powtarzaj zmian,
  które już w niej były.

## Zbieranie danych

1. `git log --all --since=<RRRR-MM-01> --until=<RRRR-MM+1-01> --no-merges --date=short --pretty=format:'%ad %h %s%n%b'`
   - opisy commitów są szczegółowe, czytaj także treść (body), nie tylko tytuł
   - dla commitów `wip` lub z ogólnikowym tytułem sprawdź `git show --stat <hash>`, żeby
     ustalić, czego dotyczą
2. Ustal, co jest już na produkcji: `git branch -r --contains <hash>`. Produkcja to
   `origin/master`. Zmiany obecne tylko na `dev` lub lokalnie wymień pod listą jako uwagę dla
   użytkownika (nie w samej liście).

## Co trafia na listę

- Tylko to, co końcowy użytkownik zauważy lub odczuje: nowe funkcje, wyraźne zmiany wyglądu
  i sposobu pracy, odczuwalne przyspieszenie.
- Pomijaj:
  - zmiany techniczne bez widocznego efektu (aktualizacje PHP/bibliotek, refaktoryzacje, testy,
    dokumentacja, zmiany uprawnień i bezpieczeństwa w tle)
  - drobne poprawki błędów i kosmetykę
  - oczywistości (np. "przycisk Wstecz działa jak należy")
  - szczegóły pojedynczych elementów UI (gdzie przeniesiono filtry, kolejność przycisków itp.)
  - rzeczy, które już wcześniej istniały, a tylko zmieniły wygląd
- Funkcję, która jest w budowie lub ukryta (bez linku w menu), opisz jako ostatni punkt
  "W przygotowaniu:". Napisz, jaki efekt zobaczy użytkownik, gdy będzie gotowa, a nie jak działa
  w środku.
- Kilka commitów o tym samym efekcie łącz w jeden punkt.

## Forma

- Bez wstępu, powitania, podpisu i komentarza. Tylko nagłówek i lista.
- Nagłówek: `**Zmiany w aplikacji - <miesiąc słownie> <rok>**`
- Lista numerowana, zwykle 4-8 punktów, każdy w 1-2 krótkich zdaniach.
- Język nietechniczny, polski, w nazewnictwie aplikacji: zamówienie, produkcja, pulpit, miernik,
  kafelek, kalendarz, panel boczny. Bez słów typu SPA, router, endpoint, drawer, cron, commit.
- Bez półpauzy i myślnika (U+2014). Używaj zwykłego `-`.

## Wzorzec (październik 2026, zaakceptowany)

```
**Zmiany w aplikacji - październik 2026**

1. Aplikacja działa szybciej. Przechodzenie między stronami nie przeładowuje całej strony.
2. Nowy, lżejszy wygląd całej aplikacji. Na ekranie mieści się więcej informacji.
3. Zamówienie edytuje się w bocznym panelu, bez opuszczania listy.
4. Listy produktów i klientów mają wyszukiwarkę. Na liście klientów jest nowa kolumna z kontaktem.
5. W przygotowaniu: przy każdym mierniku na pulpicie pojawi się mały wykres. Pokaże, jak
   wartość zmieniała się w ostatnim czasie, np. czy opóźnień przybywa, czy ubywa. Aplikacja już
   codziennie zapisuje wartości mierników, więc historia się gromadzi.
```

Pod listą, oddzielone linią `---`, dopisz ewentualne uwagi tylko dla użytkownika (np. zmiany
jeszcze niewdrożone na produkcję). Gdy nie ma uwag, nie dopisuj nic.
