# Deklarativ installation, format 1

## Installera inkopierade moduler utan GitHub

1. Kopiera kompletta modulmappar till `system/modules/`, inklusive modulens
   PHP-fil, `config/install.json`, migreringar och eventuella AJAX-filer.
2. Öppna System Update Tool som superuser och välj **Kontrollera lokala moduler**
   under **Lokala installationer**. Addon-repo behöver inte vara konfigurerat.
3. Välj **Installera lokalt**. Bekräftelsen visar även beroenden i installationsordning.
   Alla beroenden måste finnas lokalt med giltiga manifest och rätt versioner.
4. Bekräfta. Verktyget säkerhetskopierar varje moduls lokala filer och använder
   samma motor för databasbackup, migreringar, register och AJAX som vid repoinstallation.

Mappar vars namn börjar med `_` ignoreras och kan inte installeras som beroenden.
Lokala installationer anropar inte GitHub och ersätter inte de inkopierade filerna.
Kontroll och planering kör aldrig modulernas PHP-kod och ändrar ingen databas.
En modul utan manifest listas inte i denna vy; gamla config/ajax.php eller
config/database.sql exekveras inte automatiskt. Ett ofullständigt paket eller ett
ogiltigt manifest visas som ett fel. Länkade kataloger/filer stöds inte.

Efter fel: kontrollera listan igen och välj modulen för ett nytt försök. Färdiga
migreringar hanteras av det befintliga registret. Avbruten SQL kan kräva manuell
kontroll enligt avsnittet nedan. Ändrade paketfiler kräver en ny installationsplan.
**Kontrollera installation** kör samma motor även när manifestversionen redan är
registrerad och skapar därför nya backuper. Filbackupen motsvarar de redan
inkopierade filerna; den kan inte återskapa filer som skrevs över vid manuell kopiering.

Testa det lokala flödet med `php -d extension=zip tests/system_update_local_install.php`.

Moduler ligger i addon-repot, en katalog per modul. CORE innehåller endast
installationsmotorn. Modulen deklarerar installationen i `config/install.json`:

```json
{
  "schema_version": 1,
  "module": "example",
  "version": "1.0.0",
  "requires": { "customer": ">=1.0.0" },
  "required_files": [],
  "database": {
    "migrations": [
      {
        "id": "001_create_example",
        "table": "example",
        "file": "config/migrations/001_create_example.sql",
        "adopt_existing": true,
        "columns": { "id": "int(11)", "label": "varchar(100)" }
      }
    ],
    "required_tables": {}
  },
  "ajax": [{ "name": "example", "script": "example/ajax.php", "auth": 1 }],
  "uninstall": { "preserve_data": true }
}
```

## Migreringar och befintliga installationer

En SQL-fil innehåller ett CREATE TABLE- eller ALTER TABLE-kommando och börjar
direkt med kommandot. `{{table}}` ersätts med den deklarerade tabellens namn via
`db::o_prefix()`. Hårdkoda inte `m_`. Använd inte flera SQL-kommandon,
stored procedures, DELIMITER eller godtyckliga PHP-installationsskript.

`columns` beskriver förväntade kolumntyper efter steget. Namn är skiftlägeskänsliga.
Integer-visningsbredd normaliseras för kompatibilitet mellan MySQL-versioner.
Om id deklareras kontrolleras även primärnyckel och auto_increment. Detta är inte
en fullständig jämförelse av index, foreign keys, nullbarhet, standardvärden eller
collation. Sådana ändringar behöver granskas separat före produktionsinstallation.

`adopt_existing` används för första baslinjen: en befintlig kompatibel tabell
registreras utan CREATE eller dataskrivning. En avvikande befintlig tabell stoppar
installationen. Befintliga rader och id:n importeras eller numreras aldrig om.

Ändra inte en publicerad migrering. Lägg till en ny med nytt id, versionsnummer
och fullständiga förväntade kolumntyper för berörd tabell. Stegen körs i listordning.
Checksumman låser även migreringens deklaration till den registrerade versionen.
Versionskrav stöder `>=1.0.0`, `=1.0.0` och `1.0.0` (lägsta version).

Tabeller som endast ska kontrolleras kan anges med kolumnnamn i `required_tables`.
`required_files` är sökvägar relativt webbplatsens rot. Filerna kontrolleras men
laddas inte ned automatiskt. Externa Composer-paket hanteras ännu inte.

## Register, backup och fel

`CONFIG.dbPrefix + module_installations` är installationsregistret. Tillståndet
avgränsas per privat tabellprefix. Filkatalogens existens räcker inte för att en
modul med manifest ska räknas som färdiginstallerad. Moduler vars manifestversion
saknar motsvarande färdig registrering erbjuds installation även om filerna matchar.

Beroenden hämtas rekursivt från addon-repots manifest och installeras först.
Cirklar, saknade moduler och oförenliga versioner stoppas. En enskild installation
och Uppdatera alla använder samma motor. Moduler utan manifest behåller sitt äldre
filbaserade beteende; gamla PHP-configfiler eller SQL-filer exekveras inte implicit.

Befintliga filer säkerhetskopieras före nedladdning. Före databasändringar sparas
ZIP med CREATE TABLE och binärsäkra INSERT-satser för deklarerade tabeller, AJAX och
modulplaceringar. READ-lås används under exporten, även för MyISAM. Servern behöver
LOCK TABLES-behörighet. Misslyckad backup stoppar arbetet. Arkivets innehåll verifieras.
Första versionen läser tabellinnehållet i minnet och lämpar sig därför inte för
mycket stora tabeller utan vidare utveckling av strömmad backup.

Varje databasbackup får ett unikt namn; tidigare försök skrivs inte över. Sökvägar
visas i gränssnittet och sparas i installationstillståndet när steget har startats.
Återställning är manuell, först till en separat databas. SQL-dumpar innehåller
verksamhetsdata och skyddas av samma backupkatalog som befintliga filbackuper.

SQL-DDL kan implicit committa. Därför utlovas ingen automatisk transaktionsrollback.
Färdiga steg körs inte om. Ett avbrutet steg kan godkännas vid återförsök om dess
deklarerade slutstruktur redan finns. Annars krävs manuell kontroll innan det
registrerade steget får repareras. Fel rapporteras aldrig som lyckad installation.

Avinstallation kräver bekräftelse. Hela modulmappen och berörda tabeller backas upp,
modulens AJAX-registreringar avaktiveras och modulplaceringar döljs innan filerna
tas bort. Verksamhetstabeller, kunddata och fakturor raderas aldrig automatiskt.

Muterande addon-anrop kräver superuser, POST och sessionstoken. Installer och manifest
är betrodd kod från det konfigurerade addon-repot; JSON är inte en säker sandlåda för
SQL från okända leverantörer. Migreringar ska endast ändra deklarerade tabeller.

## Verifiering

Kör `php -d extension=zip tests/system_update_installer.php` från repots rot.
Testerna använder en isolerad databasadapter och tillfälliga filer. De verifierar
motorns kontrakt, inte MySQL/MariaDB-serverns exekvering. Innan produktionsbruk krävs
ett installationstest, ett uppdateringstest och ett avinstallationstest på en
databaskopia med webbplatsens riktiga prefix, behörigheter och serverversion.
