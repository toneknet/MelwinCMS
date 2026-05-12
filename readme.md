# MelwinCMS – modulärt PHP-system

Detta repository innehåller ett äldre men kraftfullt modulärt PHP-system där en egen kärna hanterar bootstrapping, routing, autentisering, modul-laddning och template-rendering.

## Innehåll

- [Översikt](#översikt)
- [Bidra](#bidra)
- [Release och deploy](#release-och-deploy)
- [Docker (snabb lokal uppstart)](#docker-snabb-lokal-uppstart)
- [Arkitektur](#arkitektur)
- [Katalogstruktur](#katalogstruktur)
- [Krav](#krav)
- [Kom igång lokalt](#kom-igång-lokalt)
- [Konfiguration](#konfiguration)
- [Hur sidor byggs](#hur-sidor-byggs)
- [Moduler](#moduler)
- [Templating](#templating)
- [Säkerhet och viktiga varningar](#säkerhet-och-viktiga-varningar)
- [Felsökning](#felsökning)
- [Utvecklingsflöde](#utvecklingsflöde)

## Översikt

Applikationen är databasdriven. Det innebär att:

- URL:er, sidor och modulplacering inte enbart styrs av filsystemet.
- Sidors innehåll byggs upp via tabeller som `page` och `page_content`.
- Moduler laddas dynamiskt beroende på vilken sida som routas fram.

Startpunkten är `index.php`, som sätter session, laddar konfiguration, språk, hjälpfunktioner och kernel, och kör `kernel->run()`.

## Arkitektur

Förenklat request-flöde:

1. `index.php` startar session och laddar grundkonfig.
2. `system/configs/localconfig.inc.php` läses in (om den saknas går systemet till install-läge).
3. Kernel initierar systemet och lämnar över till route.
4. Route slår upp sida i databasen, validerar access och laddar moduler.
5. Moduloutput injiceras i template-regioner och renderas.

## Katalogstruktur

```text
.
├── index.php
├── system/
│   ├── kernel.cls.php
│   ├── functions.inc.php
│   ├── libs/               # kärnklasser (db, auth, route, template, m.fl.)
│   ├── modules/            # affärsmoduler
│   ├── configs/            # basconfig + localconfig
│   ├── lang/               # systemspråk
│   └── ajax/               # systemets ajax-endpoints
├── tpl/                    # teman/mallar
└── upload/                 # uppladdade filer
```

## Krav

Minimikrav (rekommenderad nivå):

- PHP 7.4+ (helst PHP 8.1/8.2 där kompatibelt)
- MySQL/MariaDB
- Webbserver (Nginx eller Apache)
- Skrivrättigheter för uppladdningskatalog (`upload/`)

> Notera: Kodbasen har legacy-inslag och kan innehålla delar som kräver mindre justeringar på nyare PHP-versioner.

## Kom igång lokalt

1. **Kopiera repo till webbroot**
   - Exempel: `/var/www/www.hbook.se`

2. **Skapa databas**
   - Skapa en tom databas i MySQL/MariaDB.

3. **Skapa lokal konfiguration**
   - Skapa filen `system/configs/localconfig.inc.php`.
   - Sätt minst:
     - `www-root`
     - `dbHost`, `dbName`, `dbUser`, `dbPass`, `dbPrefix`
     - `language` och `timezone`

4. **Kontrollera webbserverns document root**
   - Ska peka mot projektets rot där `index.php` ligger.

5. **Starta applikationen**
   - Öppna webbplatsen i browser.
   - Om `localconfig.inc.php` saknas försöker systemet skicka vidare till install-flöde.

## Konfiguration

Basinställningar ligger i:

- `system/configs/config.inc.php` (defaultvärden)

Lokala overrides ska ligga i:

- `system/configs/localconfig.inc.php`

### Viktigt

Ändra normalt **inte** `config.inc.php` direkt. Lägg miljöspecifika värden i `localconfig.inc.php`.

## Hur sidor byggs

Systemet är CMS-liknande i sin uppbyggnad:

- En post i `page` representerar en sida/rutt.
- Relaterade poster i `page_content` styr vilka moduler som körs och var output hamnar.
- Modulinställningar ligger ofta serialiserat i `settings`.

Det gör att mycket funktionalitet kan flyttas/ändras i databasen utan kodändring.

## Moduler

En modul brukar följa mönstret:

- Katalog: `system/modules/<modulnamn>/`
- Huvudklass: `system/modules/<modulnamn>/<modulnamn>.php`
- Språkfiler: `lang/sv.php`, ev. `lang/en.php`
- Konfiguration: `config/` (settings, databas, ajax)

Autoload letar först i `system/libs/*.cls.php` och därefter i modulkataloger.

## Templating

Teman finns i `tpl/` (t.ex. `default`, `skeleton`).

- Entrétemplate: oftast `tpl/<tema>/index.tpl`
- Moduloutput placeras i regioner som t.ex. `header`, `navigation`, `main`, `footer`

Systemet använder eval-baserad templating, vilket kräver strikt hygien kring data som renderas.

## Säkerhet och viktiga varningar

- SQL byggs delvis manuellt i kodbasen – validera och escape:a konsekvent.
- Templating via eval kräver extra försiktighet med osäker input.
- Lagra aldrig hemligheter i versionshanterade filer.
- Använd alltid `localconfig.inc.php` för credentials och miljövärden.

## Felsökning

Snabba checkpoints:

1. Finns `system/configs/localconfig.inc.php`?
2. Stämmer databasuppgifter och databasens teckenkodning?
3. Har `upload/` rätt filrättigheter?
4. Är vald template satt och finns motsvarande filer i `tpl/`?
5. Är rätt språkfil tillgänglig i `system/lang/` och i modulens `lang/`?

## Utvecklingsflöde

Rekommenderad start för nya utvecklare:

1. Läs `index.php` → `system/kernel.cls.php` → `system/libs/route.cls.php`.
2. Följ en enkel modul, exempelvis `navigation`, från datahämtning till template-output.
3. Verifiera hur `page`/`page_content` kopplar sida till modul.
4. Bygg en minimal testmodul för att förstå hela kedjan.

## Bidra

Se `CONTRIBUTING.md` för arbetssätt, checklista före PR och rekommenderad PR-struktur.

## Release och deploy

Se `RELEASE_DEPLOY_CHECKLIST.md` för komplett checklista före, under och efter release.

## Docker (snabb lokal uppstart)

En enkel utvecklingssetup finns med Docker Compose.

1. Starta miljön:
   - `docker compose up --build`
2. Applikationen nås på:
   - `http://localhost:8080`
3. Databasen nås på:
   - `localhost:3307` (DB: `hbook`, användare: `hbook`, lösenord: `hbook`)
4. Skapa/uppdatera `system/configs/localconfig.inc.php` så att den pekar mot DB-host `db` inne i containern.

> Tips: Om du kör appen utanför Docker men DB i Docker, använd DB-host `127.0.0.1` och port `3307`.
