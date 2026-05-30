# Deploy to dev and master

Promocja zmian z aktualnego brancha przez `dev` do `master`. Faktyczny deploy
odpala CI po pushu na te branche — ta komenda zajmuje się tylko gitem.

## Założenia i zasady (czytaj przed wykonaniem)

- **Zapamiętaj branch startowy** na początku (`git branch --show-current`), żeby
  wrócić na niego w ostatnim kroku.
- **Merge strategia:** używaj `--no-ff` przy mergach do `dev` i `master`
  (czytelna historia, jeden commit merge na promocję).
- **Zawsze aktualizuj branche docelowe z remote** przed mergem
  (`git fetch origin`, a `dev`/`master` doprowadź do stanu remote), żeby nie
  mergować na nieaktualny stan.
- **Mergujesz będąc NA gałęzi docelowej:** czyli `git checkout dev && git merge --no-ff <branch>`,
  analogicznie dla `master`. (Mimo kolejności słów w krokach poniżej.)
- **Bramka jakości:** przed commitem uruchom `make check` (cs-fix + phpstan).
  Poprawki cs-fix mają trafić do commitu; jeśli phpstan zgłosi błędy — zatrzymaj
  się i zgłoś, nie promuj.
- **Commit message:** zwięzły komunikat. Jeśli zakres zmian jest niejasny — zapytaj o treść.
- **Pusty working tree:** jeśli nie ma nic do commitu, pomiń krok commitu i
  przejdź do promocji już wypchniętego stanu.
- **Branch startowy to już `dev` lub `master`:** zatrzymaj się i zapytaj
  użytkownika, bo część kroków (merge „brancha do dev/master", powrót na
  branch startowy) traci sens — potwierdź zamiar przed kontynuacją.

## Kroki

1. Zapamiętaj branch startowy. Sprawdź `git status` i `git diff`, żeby zobaczyć
   jakie zmiany czekają do commitu.
2. Uruchom `make check`. Jeśli nie przejdzie (phpstan) — zatrzymaj się i zgłoś.
3. Zacommituj zmiany na aktualnym branchu (zwięzły komunikat, stopka
   `Co-Authored-By`; zapytaj o treść jeśli zakres niejasny). Pomiń, jeśli brak
   zmian.
4. Wypchnij aktualny branch na remote.
5. `git fetch origin`. Przejdź na `dev`, zaktualizuj go do stanu remote,
   zmerguj aktualny branch (`--no-ff`) i wypchnij `dev`.
6. Przejdź na `master`, zaktualizuj go do stanu remote, zmerguj `dev`
   (`--no-ff`) i wypchnij `master`.
7. Wróć na branch startowy.
8. Podsumuj co zostało zrobione (commity, zmergowane branche, wypchnięte refy).

## Konflikty

W przypadku konfliktów zatrzymaj się, wygeneruj raport (które pliki, na którym
mergu), zasugeruj rozwiązanie i zapytaj użytkownika o zgodę na rozwiązanie
konfliktu. Nie kontynuuj promocji do czasu rozwiązania.
