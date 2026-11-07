# Site settings

Modulnamn: `site_settings`. Lägg till modulen på en administrationssida via den vanliga sid-/modulhanteringen. Modulen kräver superanvändare även om sidan har andra behörigheter.

Läser `system/configs/config.inc.php` och därefter `localconfig.inc.php`. Visar även egna lokala nycklar. Sparar endast lokala värden, direkt i det lokala filsystemet. Katalogen och filen måste vara skrivbara för PHP. Standardfilen ändras aldrig.

Svenska och engelska etiketter och förklaringar finns i `lang/`. Arrayer redigeras som JSON. Tomma lösenordsfält behåller det befintliga värdet. Återställning tar bort den lokala nyckeln så standardvärdet används igen. Ändringar gäller från nästa anrop.

Filen ersätts atomärt under lås. Samtidiga ändringar avvisas om filen ändrats sedan formuläret öppnades. PHP-värden serialiseras med `var_export`. Vid sparning normaliseras localconfig till värdetilldelningar; egna kommentarer och eventuell dynamisk PHP-kod bevaras inte.

Kontroll: `php tests/site_settings.php` (tests-katalogen ignoreras av projektets .gitignore).
