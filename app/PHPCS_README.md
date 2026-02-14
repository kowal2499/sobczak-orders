# PHP_CodeSniffer - Przewodnik użycia

## Podstawowe komendy (z Dockerem)

### Uruchamianie przez Composer (zalecane):
```bash
# Z hosta (poza kontenerem)
docker exec -it container-php-apache bash -c "cd /var/www/html && composer cs"
docker exec -it container-php-apache bash -c "cd /var/www/html && composer cs-fix"
docker exec -it container-php-apache bash -c "cd /var/www/html && composer cs-report"

# Lub wejdź do kontenera
docker exec -it container-php-apache bash
cd /var/www/html
composer cs                    # Sprawdź kod
composer cs-fix               # Automatycznie napraw proste błędy
composer cs-report            # Pokaż podsumowanie błędów
```

### Bezpośrednie uruchamianie (w kontenerze):
```bash
./vendor/bin/phpcs                           # Sprawdź kod (używa phpcs.xml)
./vendor/bin/phpcbf                          # Automatycznie napraw
./vendor/bin/phpcs --report=summary          # Podsumowanie
./vendor/bin/phpcs --report=source           # Statystyki błędów
```

## Konfiguracja

Konfiguracja znajduje się w pliku `phpcs.xml`.

### Aktualnie sprawdzane foldery:
- `src/Controller`
- `src/Service`

### Jak dodać więcej folderów?

W pliku `phpcs.xml` dodaj kolejne linie:
```xml
<file>src/Repository</file>
<file>src/Entity</file>
<file>src/Form</file>
```

## Zaawansowane opcje

### Sprawdź konkretny plik/folder:
```bash
./vendor/bin/phpcs src/Controller/DashboardController.php
./vendor/bin/phpcs src/Entity
```

### Różne formaty raportów:
```bash
./vendor/bin/phpcs --report=full            # Pełny raport (domyślny)
./vendor/bin/phpcs --report=summary         # Podsumowanie
./vendor/bin/phpcs --report=source          # Statystyki rodzajów błędów
./vendor/bin/phpcs --report=diff            # Pokazuje różnice do naprawy
./vendor/bin/phpcs --report=json            # Format JSON
```

### Pokaż tylko ostrzeżenia lub błędy:
```bash
./vendor/bin/phpcs -n                       # Tylko błędy (bez ostrzeżeń)
./vendor/bin/phpcs --warning-severity=0     # Wyłącz ostrzeżenia
```

### Zapisz raport do pliku:
```bash
./vendor/bin/phpcs --report=full --report-file=phpcs-report.txt
```

## Rozszerzanie reguł

### Wyłączanie konkretnych reguł

W `phpcs.xml` dodaj:
```xml
<rule ref="PSR12">
    <exclude name="NazwaReguły"/>
</rule>
```

### Dostępne standardy:
- **PSR12** - Nowoczesny standard (obecnie używany)
- **PSR2** - Starszy standard
- **PSR1** - Podstawowy standard
- **Squiz** - Bardzo rygorystyczny
- **PEAR** - Standard PEAR
- **Zend** - Standard Zend Framework

### Zmiana standardu:
```bash
./vendor/bin/phpcs --standard=PSR2 src/
```

## Ignorowanie błędów w kodzie

### Ignoruj cały plik:
```php
<?php
// phpcs:ignoreFile
```

### Ignoruj konkretną linię:
```php
$variable = something(); // phpcs:ignore
```

### Ignoruj blok kodu:
```php
// phpcs:disable
$messy = code();
$here = true;
// phpcs:enable
```

### Ignoruj konkretną regułę:
```php
// phpcs:disable PSR1.Methods.CamelCapsMethodName
public function some_old_method() {
// phpcs:enable PSR1.Methods.CamelCapsMethodName
}
```

## Stopniowe wdrażanie

### Krok 1: Zaczynamy (obecna konfiguracja)
- Sprawdzamy tylko `Controller` i `Service`
- Wyłączone surowe reguły długości linii
- Standard PSR12 z łagodnymi regułami

### Krok 2: Po naprawieniu błędów
Dodaj kolejne foldery w `phpcs.xml`:
```xml
<file>src/Repository</file>
<file>src/Entity</file>
```

### Krok 3: Zaostrzanie reguł
Usuń wyłączone reguły z `phpcs.xml`:
```xml
<!-- Usuń tę linię, aby włączyć sprawdzanie długości linii -->
<!-- <exclude name="Generic.Files.LineLength"/> -->
```

### Krok 4: Włącz wszystkie katalogi
```xml
<file>src</file>
```

## Integracja z CI/CD

### Dodaj do pipeline:
```bash
./vendor/bin/phpcs --report=checkstyle --report-file=phpcs-report.xml
```

## Docker

Jeśli używasz Dockera, uruchom wewnątrz kontenera:
```bash
docker exec -it container-php-apache composer cs
```

Lub:
```bash
docker exec -it container-php-apache ./vendor/bin/phpcs
```

## Przydatne linki

- [Dokumentacja PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer)
- [PSR-12 Standard](https://www.php-fig.org/psr/psr-12/)
- [Lista reguł](https://github.com/squizlabs/PHP_CodeSniffer/wiki)

## Problemy i rozwiązania

### Problem: Zbyt wiele błędów
**Rozwiązanie**: Użyj `composer cs-report` aby zobaczyć podsumowanie. Skup się na jednym typie błędu naraz.

### Problem: Nie mogę naprawić automatycznie
**Rozwiązanie**: Niektóre błędy wymagają ręcznej poprawki. Użyj `phpcbf` dla automatycznych napraw, resztę ręcznie.

### Problem: Fałszywe alarmy
**Rozwiązanie**: Użyj komentarzy `// phpcs:ignore` dla uzasadnionych wyjątków.
