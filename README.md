# TOPROCO — veřejná pilotní landing page

**Hlavní adresa:** https://toproco.jirijanousek.cz/  
**GitHub:** https://github.com/George-Pura-Vida/TOPROCO  
**Stav:** statický veřejný prototyp; není připojen CRM, bankovní API ani administrace.

## Co je hotové
- Responzivní HTML/CSS/JavaScript bez externích knihoven.
- Přepínání češtiny, slovenštiny, angličtiny, španělštiny a zjednodušené čínštiny.
- Odkazy na Moje renta, Moje krypto, Můj DIP, Můj kmen a Můj obchod. Můj kmen zůstává soukromý.
- Komunitní koncept, nabídka partnerství, **ilustrativní** finanční kalkulace.
- Mobilní hamburger menu, klávesnicové ovládání a viditelná právní upozornění.

## Nasazení na WebKitty
Subdoména `toproco.jirijanousek.cz` je vytvořená v hostingu; musí být propojena s GitHub repozitářem a úspěšně nasazena.

1. V https://my.webkitty.eu/ přejděte k dané subdoméně a zjistěte její přesný kořenový adresář (Document Root).
2. Ve **Web Files → Git Version Control** použijte **Create Repository → Clone a Repository**.
3. **Clone URL:** `https://github.com/George-Pura-Vida/TOPROCO.git`.
4. **Repository Path:** přesný document root subdomény TOPROCO (ověřte v administraci, neodhadujte jej). Pokud už obsahuje soubory, nejprve bezpečně vyhodnoťte obsah; nepřepisujte cizí aplikace.
5. **Deployment Branch:** `main`, poté spusťte **Deploy**. Při aktualizacích použijte **Manage → Deploy**.
6. Otevřete `https://toproco.jirijanousek.cz/`, ověřte HTTPS, přesměrování, mobilní menu, všech 5 jazyků a odkazy na aplikace.
7. Veřejná úvodní stránka má povolené indexování. Vlastník webu může odeslat `sitemap.xml` do Google Search Console.

Alternativou je nasazení pomocí zabezpečeného GitHub Actions (deploy token/SSH). Bez bezpečně nastavených přístupových údajů není automatický deploy aktivní.

## Důležité bezpečnostní zásady
- Repozitář je **veřejný**. Nevkládejte sem API klíče, hesla, soukromé zákaznické údaje ani konfiguraci bankovních účtů.
- Platební funkce, přihlášení, analytika a administrace nejsou implementovány.
- Finanční scénáře jsou pouze ilustrativní hypotézy, nikoli garance výnosu nebo veřejná nabídka cenných papírů.
- Název TOPROCO není doložený jako bezkolizní ochranná známka; vyžaduje samostatnou rešerši a právní ověření.
- Před ostrým marketingem doplnit právní dokumenty, zásady ochrany osobních údajů, souhlasy a případnou cookie lištu podle skutečně nasazených technologií.
- Pro napojení bank a AI agentů použít samostatné zabezpečené backendové služby, minimální oprávnění a schvalování citlivých operací.

## Další fáze
1. Schválit brand a obsah.
2. Nasadit na WebKitty a udělat testy reálného mobilního zobrazení.
3. Přidat bezpečný formulář pro partnerství, analytiku a administraci.
4. Oddělit veřejnou landing page od neveřejného obchodního a AI backendu.


## Vizuální verze TOPROCO 2.0 (6. 11. 2026)
- Nová tmavě modrá a zlatá navigace včetně mobilního hamburger menu.
- Vlastní vektorová horská scéna `assets/hero-sunrise.svg`, obrázky čtyř produktů a `favicon.svg`; bez závislosti na externím obrázkovém CDN.
- Landing page pro DIP, rentu, krypto a budoucí firemní CRM; vlastní CRM zůstává soukromé.
- Sekce výhod, komunity, obchodních partnerů, bezpečnosti, obchodních scénářů a ilustrační vizualizace.
- Ukázkový interaktivní investiční graf používá modelový vklad 100 000 Kč, měsíční investici 3 000 Kč a hypotetické sazby 3/5/7 % p.a.; **nejde o výkonnost skutečných produktů**.
- Jazykové mutace: CS, SK, EN, ES, ZH.
- Automatické kontroly `node --test tests/site.test.mjs` a `node tests/browser.mjs` (browser test vyžaduje Playwright a Chromium). V GitHub Actions se zhotovují i mobilní a desktopové screenshoty.

### Jak nasadit nejnovější verzi
WebKitty **Web Files → Git Version Control → TOPROCO → Manage → Deploy**, větev `main`. Na hostingu musí být kořen subdomény přímo adresář s `index.html` a `assets/`. Ověřte HTTPS, načtení favicon a ilustrací, přepínání jazyků, graf, kartu každé aplikace a mobilní menu.

Veřejná úvodní stránka má kanonickou adresu, metadata pro sdílení, strukturovaná data a sitemapu. Administrace a stránka soukromí zůstávají mimo sitemapu a mají `noindex`.

Nejsou implementovány: jednotná registrace, AI agenti, bankovní/tržní API, soukromá B2B CRM, prodej licencí ani platby. Pro tyto funkce bude potřeba samostatné zabezpečené backendové řešení.


## Aktivace administrace vzhledu na WebKitty

Veřejný web má výchozí paletu i na statickém hostingu. Pro změny sdílené všemi návštěvníky je třeba PHP 8.1 nebo novější, HTTPS, zapisovatelná soukromá složka a heslo správce.

1. V domovské složce hostingového účtu vytvořte `.toproco-theme` mimo `public_html` a mimo všechny ostatní veřejné kořeny. Nastavte přístup pouze pro hostingový účet (oprávnění `0700`).
2. V terminálu hostingového účtu vytvořte hash silného hesla a uložte jej do `.toproco-theme/password.hash` s oprávněním `0600`. Příklad pro Bash. Zadané heslo se nevypíše, nepředává se jako argument procesu a neukládá se do repozitáře.

   ```bash
   umask 077
   mkdir -p ~/.toproco-theme
   chmod 700 ~/.toproco-theme
   read -r -s -p "Heslo správce vzhledu (alespoň 20 znaků): " toproco_theme_password
   printf '\n'
   if [ "${#toproco_theme_password}" -ge 20 ]; then
     printf '%s' "$toproco_theme_password" | php -r '$password = stream_get_contents(STDIN); if (strlen($password) > 72) { exit(1); } echo password_hash($password, PASSWORD_DEFAULT), PHP_EOL;' > ~/.toproco-theme/password.hash.new &&
       mv ~/.toproco-theme/password.hash.new ~/.toproco-theme/password.hash
   fi
   unset toproco_theme_password
   chmod 600 ~/.toproco-theme/password.hash
   ```

   Při použití jiné soukromé složky nastavte PHP proměnnou prostředí `TOPROCO_THEME_DIR` na její absolutní cestu. U výchozí složky musí PHP vidět domovský adresář stejného hostingového účtu.
3. Otevřete `https://toproco.jirijanousek.cz/vzhled.php` a přihlaste se. Po uložení ověřte změnu v anonymním okně. Výsledná paleta se ukládá do `palette.json` ve stejné soukromé složce a další nasazení ji nepřepíše.
