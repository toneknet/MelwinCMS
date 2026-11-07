# Loggfiler

Lägg till modulen `logfiles` på en administrativ sida via sidans modulinställningar, med målzon `main`. Modulen hittas via samma katalogstruktur som övriga moduler. Endast inloggade superadministratörer får läsa innehållet.

Visar `.log`-filer i `system/logs/` och dess undermappar samt exakt `error.log` i projektroten. Filvalen skapas automatiskt från en kontrollerad katalog, aldrig från en fritt angiven sökväg. Symboliska länkar och sökvägar som går utanför loggträdet utesluts. Befintliga databasmodulen `logs` påverkas inte. JSON per rad, även med tidsstämpel före JSON, visas som fält och värden med utfällbar JSON. Text och stackspår visas med bevarade radbrytningar. Även daterade mejlloggar får mejlvisning.

## Arkivering och filval

När en superadministratör öppnar loggvisaren flyttas daterade filer direkt i `system/logs/` till månadsmappen `YYYYMM/` om datumet i filnamnet är äldre än sju kalenderdagar, räknat i Europe/Stockholm. `db_20261107.log` flyttas exempelvis till `system/logs/202611/db_20261107.log` från 15 november. Både `_YYYYMMDD.log` och `.YYYYMMDD.log` stöds. Ogiltiga datum och odaterade filer lämnas kvar. Rotens `error.log` flyttas aldrig. Webbservern behöver rättigheter att skapa mappar och flytta filer i loggmappen. Kollisioner skrivs aldrig över; originalet lämnas kvar med ett meddelande i vyn. Samtidiga arkiveringar samordnas med `.archive.lock`.

- **Aktuella filer:** endast filer direkt i `system/logs/` samt rotens `error.log`.
- **Arkivmapp:** välj en upptäckt undermapp; endast dess egna loggfiler listas. Nästlade mappar kan väljas separat.
- **Tidsperiod:** välj från/till (inklusive gränsdatumen). Daterade filnamn söks i hela det tillåtna loggträdet, med senaste datum först. Tom gräns betyder obegränsad åt det hållet. Odaterade loggar ingår inte; logginläggens datum används inte för filurvalet.

För arkivering även när ingen öppnar loggvisaren kan en befintlig daglig cron köra `php /sajtens/sökväg/system/modules/logfiles/archive.php`. Ingen schemaläggning skapas av modulen. Skriptet är spärrat för HTTP.

Tester: `php system/modules/logfiles/storage_test.php` och `php system/modules/logfiles/test.php`. Arkivtesterna använder separata tillfälliga testfiler.

Läsningen begränsas till filens sista 2 MB och högst 500 inlägg. Sökning filtrerar dessa inlägg i webbläsaren. Uppdatera läser om filen. Loggfilerna ändras aldrig.
