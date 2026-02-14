# 🔍 Komendy CodeSniffer w projekcie

## 📋 Wszystkie dostępne komendy Composer

### **🔧 Główna komenda (napraw + pokaż):**

#### `composer check` ⭐ **ZALECANA**
```bash
composer check
```
- **Co robi:** 
  1. **Krok 1:** Automatycznie naprawia wszystkie możliwe błędy (`phpcbf`)
  2. **Krok 2:** Pokazuje podsumowanie tego co pozostało (`phpcs --report=summary`)
- **Modyfikuje pliki:** ✅ TAK (automatycznie naprawia)
- **Użyj gdy:** Chcesz szybko naprawić i zobaczyć wyniki
- **⚠️ Uwaga:** Commituj przed użyciem!

**Przykładowy output:**
```
> bash -c './vendor/bin/phpcbf; ./vendor/bin/phpcs --report=summary; exit 0'
........................ 24 / 24 (100%)

PHPCBF RESULT SUMMARY
----------------------------------------------------------------------
FILE                                                  FIXED  REMAINING
----------------------------------------------------------------------
/var/www/html/src/Controller/DashboardController.php  3      1
----------------------------------------------------------------------

....W.....W.........W... 24 / 24 (100%)

PHP CODE SNIFFER REPORT SUMMARY
----------------------------------------------------------------------------------------------------
FILE                                                                                ERRORS  WARNINGS
----------------------------------------------------------------------------------------------------
/var/www/html/src/Controller/DashboardController.php                                1       0
----------------------------------------------------------------------------------------------------
A TOTAL OF 1 ERROR AND 0 WARNINGS WERE FOUND IN 1 FILE
```

---

### **Sprawdzanie (bez modyfikacji plików):**

#### `composer check`
```bash
composer check
```
- **Co robi:** Pokazuje wszystkie błędy i ostrzeżenia (pełny raport)
- **Output:** Szczegółowy raport z numerami linii
- **Kod wyjścia:** 0 = OK, 1 = błędy, 2 = ostrzeżenia

#### `composer check-summary`
```bash
composer check-summary
```
- **Co robi:** Pokazuje podsumowanie błędów (skrócona wersja)
- **Output:** Tabela z liczbą błędów na plik
- **Użyj gdy:** Chcesz szybki przegląd

#### `composer cs` (alias dla `check`)
```bash
composer cs
```
- **Co robi:** To samo co `composer check`

#### `composer cs-report` (alias dla `check-summary`)
```bash
composer cs-report
```
- **Co robi:** To samo co `composer check-summary`

#### `composer cs-full`
```bash
composer cs-full
```
- **Co robi:** Najbardziej szczegółowy raport (wszystkie detale)

#### `composer cs-diff`
```bash
composer cs-diff
```
- **Co robi:** Pokazuje różnice - jak kod POWINIEN wyglądać
- **Użyj gdy:** Chcesz zobaczyć, jak phpcbf naprawi kod

---

### **Naprawianie kodu (modyfikuje pliki):**

#### `composer fix`
```bash
composer fix
```
- **Co robi:** Automatycznie naprawia wszystkie możliwe błędy
- **⚠️ UWAGA:** Modyfikuje pliki! Zrób commit przed użyciem!
- **Output:** Lista naprawionych plików

#### `composer cs-fix` (alias dla `fix`)
```bash
composer cs-fix
```
- **Co robi:** To samo co `composer fix`

---

## 🎯 Praktyczne przykłady użycia

### Scenario 1: Codzienne sprawdzenie kodu (NAJPROSTSZE)
```bash
# Jedna komenda robi wszystko: naprawia + pokazuje
composer check
```

### Scenario 2: Tylko podgląd (bez zmian)
```bash
# Zobacz co jest nie tak (bez modyfikacji)
composer check-summary

# Szczegóły konkretnego pliku
./vendor/bin/phpcs src/Controller/DashboardController.php
```

### Scenario 3: Przed commitem
```bash
# Commituj obecny stan
git add .
git commit -m "Before code style fixes"

# Napraw i zobacz wyniki (jedna komenda!)
composer check

# Sprawdź zmiany
git diff

# Commituj naprawiony kod
git add .
git commit -m "Apply PSR-12 code style fixes"
```

---

## 🔢 Kody wyjścia phpcs

| Kod | Znaczenie | Co zrobić |
|-----|-----------|-----------|
| 0 | ✅ Brak problemów | Wszystko OK! |
| 1 | ❌ Znaleziono błędy (ERRORS) | Użyj `composer fix` lub napraw ręcznie |
| 2 | ⚠️ Tylko ostrzeżenia (WARNINGS) | Opcjonalnie napraw |
| 3 | ❌ Błędy przetwarzania | Problem z konfiguracją |

**UWAGA:** Kod 2 to NIE jest błąd! To oznacza, że są tylko ostrzeżenia (warnings), co jest OK.

---

## 📊 Różnice między raportami

### `composer check` (full report)
```
FILE: /var/www/html/src/Controller/DashboardController.php
-----------------------------------------------------------------------------------------------------
FOUND 4 ERRORS AFFECTING 3 LINES
-----------------------------------------------------------------------------------------------------
 11  ERROR  [x] Header blocks must be separated by a single blank line
 33  ERROR  [ ] Method name "DashboardController::ApiFetchOrdersCount" is not in camel caps format
 38  ERROR  [x] Expected 1 newline at end of file; 0 found
-----------------------------------------------------------------------------------------------------
```

### `composer check-summary` (summary report)
```
FILE                                                                                    ERRORS  WARNINGS
--------------------------------------------------------------------------------------------------------
/var/www/html/src/Controller/DashboardController.php                                    4       0
/var/www/html/src/Controller/ProductionController.php                                   20      2
--------------------------------------------------------------------------------------------------------
A TOTAL OF 24 ERRORS AND 2 WARNINGS WERE FOUND IN 2 FILES
```

### `composer cs-diff` (diff report)
```
--- /var/www/html/src/Controller/DashboardController.php
+++ PHP_CodeSniffer
@@ -7,7 +7,8 @@
 use Symfony\Component\HttpFoundation\Response;
 use Symfony\Component\Routing\Annotation\Route;
+
 class DashboardController extends AbstractController
```

---

## 💡 Porady

### Ignorowanie ostrzeżeń
Jeśli chcesz sprawdzić tylko błędy (bez ostrzeżeń):
```bash
./vendor/bin/phpcs -n  # -n = tylko errors, bez warnings
```

### Sprawdzenie konkretnego pliku
```bash
./vendor/bin/phpcs src/Controller/SomeController.php
```

### Sprawdzenie konkretnego katalogu
```bash
./vendor/bin/phpcs src/Service/
```

### Zapis raportu do pliku
```bash
./vendor/bin/phpcs --report=full --report-file=code-review.txt
```

---

## 🚀 Rekomendowany workflow

### Na co dzień:
```bash
composer check-summary    # Szybkie sprawdzenie
```

### Przed commitem:
```bash
composer fix              # Automatyczna naprawa
composer check-summary    # Sprawdź co zostało
```

### Code review:
```bash
composer check            # Pełny raport dla reviewera
```

---

## ❓ FAQ

**Q: Dlaczego `composer check` zwraca "error code 2"?**  
A: To NIE jest błąd! Kod 2 oznacza ostrzeżenia (warnings). Kod 0 = wszystko OK, 1 = błędy, 2 = ostrzeżenia.

**Q: Jak naprawić automatycznie tylko jeden plik?**  
A: `./vendor/bin/phpcbf src/Controller/SomeController.php`

**Q: Jak wyłączyć sprawdzanie długości linii?**  
A: Jest już wyłączone w `phpcs.xml` (reguła `Generic.Files.LineLength`)

**Q: Czy mogę używać phpcs w PHPStorm/IDE?**  
A: Tak! Skonfiguruj w Settings → PHP → Quality Tools → PHP_CodeSniffer

---

## 📚 Dokumentacja

Pełna dokumentacja: `PHPCS_README.md`
Konfiguracja: `phpcs.xml`
