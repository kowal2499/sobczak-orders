# Deployment Strategy - Assets Build

## Jak to działa?

### 1. **Lokalne środowisko (development)**
- Buduj assety lokalnie: `cd app/assets && npm run watch` lub `npm run build`
- Zbudowane pliki trafiają do `app/public/build/` (ignorowane przez git)

### 2. **GitHub Actions (CI/CD)**
- Po pushu do `test` lub `master` uruchamia się workflow `.github/workflows/symfony.yml`
- Workflow wykonuje:
  1. **Job: test** - testy PHPUnit i budowanie assetów
     - Instaluje zależności PHP i Node.js
     - Uruchamia testy
     - Buduje assety (npm run build)
     - Zapisuje zbudowane assety jako artefakt
  2. **Job: deploy** - deployment na serwer
     - Pobiera zbudowane assety z artefaktu
     - Uruchamia Deployer
     - Deployer uploaduje assety na serwer

### 3. **Serwer zdalny**
- Hosting nie wspiera Node.js
- Assety są przesyłane gotowe z GitHub Actions
- Deployer kopiuje je do odpowiedniego release

## Dlaczego nie commitujemy zbudowanych assetów?

❌ **Zła praktyka**: Commitowanie zbudowanych plików do repo
- Niepotrzebnie powiększa repozytorium
- Generuje konflikty w mergach
- Utrudnia code review

✅ **Dobra praktyka**: Budowanie assetów w CI/CD
- Repozytorium zawiera tylko kod źródłowy
- Assety budowane podczas deploymentu
- Clean git history

## Komendy

### Deployment z GitHub Actions (automatyczny)
```bash
# Push do brancha test lub master automatycznie uruchamia deployment
git push origin test
git push origin master
```

### Deployment lokalny (ręczny)
⚠️ **Uwaga**: Przed ręcznym deploymentem musisz zbudować assety lokalnie!

```bash
# 1. Zbuduj assety lokalnie
cd app/assets
npm run build
cd ../..

# 2. Deploy
dep deploy test    # Deploy na test
dep deploy prod    # Deploy na produkcję (OSTROŻNIE!)
```

## Struktura folderów

```
app/
├── public/
│   └── build/              # Folder z zbudowanymi assetami
│       ├── .gitignore      # Ignoruje wszystkie pliki oprócz siebie
│       ├── app.js          # (ignorowany przez git)
│       ├── app.css         # (ignorowany przez git)
│       └── ...
└── assets/
    ├── js/
    ├── js-vue/
    └── webpack.config.js
```

## Konfiguracja

### deploy.php
- `upload:build_assets` - task uploadujący zbudowane assety na serwer
- Wykonywany przed `deploy:symlink`
- Jeśli folder build nie istnieje (deployment lokalny bez buildu), wyświetla ostrzeżenie

### .github/workflows/symfony.yml
- Job `test` - buduje assety i tworzy artefakt
- Job `deploy` - pobiera artefakt i uruchamia Deployer
- Artefakt jest przechowywany tylko przez 1 dzień

## Troubleshooting

### "Warning: Build directory does not exist"
Przy ręcznym deploymencie nie zbudowałeś assetów. Wykonaj:
```bash
cd app/assets && npm run build
```

### Assety nie działają na serwerze
1. Sprawdź czy workflow zakończył się sukcesem
2. Sprawdź logi Deployera czy upload się powiódł
3. Sprawdź na serwerze czy pliki istnieją:
```bash
ssh sobczak@s7.zenbox.pl "ls -la ~/domains/app.sobczak.com.pl/deployer/current/public/build/"
```
