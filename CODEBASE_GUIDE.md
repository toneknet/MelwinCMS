# Codebase-guide för nykomlingar

Detta projekt är ett klassiskt, modulärt PHP-system där en egen “kernel” styr inloggning, routing, modul-laddning och template-rendering.

## 1) Översikt: request-flödet

1. `index.php` startar session, laddar global config och språkfiler.
2. Därefter laddas hjälpfunktioner + kernel.
3. `kernel->run()` avgör om systemet ska in i install-läge, kräver inloggning, eller routa vidare till en sida.
4. `route` slår upp aktuell sida i DB-tabellen `page`, kontrollerar access, laddar modulers språk/config/DCA och kör modulerna för aktuell sida.
5. Modulernas output injiceras i `template` och publiceras.

## 2) Viktiga mappar

- `system/`
  - Kärna, libs, funktioner, språkfiler, AJAX-endpoints.
- `system/libs/`
  - Core-klasser som `auth`, `db`, `route`, `template`, `input`, `form`.
- `system/modules/`
  - Funktionsmoduler (t.ex. planering, navigation, användarverktyg).
- `tpl/`
  - Teman och mallar (`default`, `skeleton`, `skeleton2`).
- `resources/`
  - Statisk frontend (JS, ikoner, templates för header/footer).

## 3) Arkitektur i praktiken

### Kernel
- `kernel` är “orchestratorn” som driver hela startup-flödet.
- Den kontrollerar DB-konfig och login-state, och lämnar sedan över till router.

### Routing + sidmodell
- Routing bygger inte primärt på filsystemets URL-mappning utan på databasen (`page`).
- Vilka moduler som laddas på en sida styrs av `page_content`.
- Access knyts till grupper/sidor och appliceras innan sida/modul laddas.

### Modulsystem
- En modul är i princip en klass med samma namn som katalogen, t.ex. `system/modules/navigation/navigation.php` med klass `navigation`.
- Autoload söker först i `system/libs/*.cls.php`, sedan i `system/modules/<module>/<module>.php`.
- Modulinställningar kommer från serialiserad data i DB-fältet `settings` (i `page_content`).

### Template/rendering
- `template` laddar vald template från `tpl/<template>/index.tpl`.
- Moduler lägger output i regioner som `header`, `navigation`, `main`, `footer`.
- Templating bygger på `eval` av `.tpl`, vilket är snabbt men kräver disciplin (ingen osäker data i template-kod).

### Databaslager
- `db` är ett eget wrapper-lager med `o_get_all`, `o_get_row`, `o_insert`, `o_update`, `o_query`.
- SQL skrivs till stor del manuellt och tabellnamn injiceras via `[table]` + prefixlogik.

## 4) Viktigt att veta innan du ändrar

1. **Mycket är runtime- och DB-drivet**
   - Meny/sidor/moduler styrs i stor grad av data i DB, inte bara av kod.
2. **Legacy-karaktär**
   - Koden blandar äldre och nyare stil (singletons, global state, procedural helpers).
3. **Konfigstrategi**
   - `system/configs/config.inc.php` är default och ska normalt inte ändras; lokala overrides ska ligga i `localconfig.inc.php`.
4. **Säkerhetsdisciplin**
   - Eftersom SQL och template-eval används brett är validering/escaping extra viktigt.
5. **Språkstöd finns överallt**
   - Både system och moduler kan ha `lang/en.php`, `lang/sv.php`.

## 5) Bra nästa steg att lära sig

1. **Läs en liten modul från start till mål**
   - Förslag: `navigation` eftersom den visar access, DB-läsning, trädbyggnad och template-output.
2. **Förstå `page` + `page_content`-tabellerna**
   - Det förklarar hur sidor “sätts ihop” utan att ändra kod.
3. **Spåra inloggning i `auth`**
   - Viktigt för behörigheter, lösenordsflöde och session-referenser.
4. **Gå igenom hjälpfunktioner i `functions.inc.php`**
   - Där finns autoload, URL-byggare och återkommande util-funktioner.
5. **Skapa en egen minimal modul**
   - En “hello-module” är snabbaste vägen att förstå hela kedjan kernel → route → module → template.

## 6) Rekommenderad onboarding-plan (1–2 dagar)

- **Dag 1 (förmiddag):** Startflöde (`index.php` → `kernel` → `route`).
- **Dag 1 (eftermiddag):** `auth`, `db`, och en liten modul (`navigation`).
- **Dag 2 (förmiddag):** Templating + språkfiler + module settings.
- **Dag 2 (eftermiddag):** Implementera en minimal modul och lägg in på en testsida via `page_content`.

---

Om du vill kan nästa steg vara att jag tar fram en konkret “första uppgift” (med fil-för-fil checklista) för just den här kodbasen.
