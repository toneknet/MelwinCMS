# Studio

En ljus och luftig arbetsyta med mörk sidomeny och limegröna accenter.
Responsiv layout, tangentbordsfokus, hopplänk och separat utskriftsläge.
Innehållet kommer från kärnan – mallen skapar inga låtsassiffror eller menyer.

## Aktivera

Välj `studio` som template i installationens konfiguration (normalt
`system/configs/localconfig.inc.php`):

```php
$GLOBALS['CONFIG']['template'] = 'studio';
```

Mallen är tillagd separat och ändrar inte ditt nuvarande mallval.
Byt tillbaka till föregående värde för att återställa utseendet.

## Kompatibilitet

`index.tpl` använder kärnans `title`, `header`, `navigation`, `main`, `footer`,
`outside`, `css` och `js`. `AjaxResult`, `page_overlay`, `URLroot`, jQuery,
jQuery UI, tableDnD, Spectrum, Fancybox och common.php behålls för modulerna.
Skeletons befintliga grid och modulstilar laddas före Studio-stilarna.
Navigationens listor och topnav stöds; topnavs undermenyer kan fällas ihop.

Inloggning, lösenord och 404 använder kärnans befintliga default-fallback.
De följer alltså inte Studios utseende i denna version.
Externa bibliotek laddas från samma CDN-tjänster som i Skeleton-mallen.

Ändra färgerna i CSS-variablerna överst i `style.css`.
Verifiera installationens egna moduler efter aktivering, särskilt tabeller,
dialoger, menyer och utskrift. Modulernas egna inline-stilar kan påverka resultatet.
