# TOPROCO — veřejná pilotní landing page

**Hlavní adresa:** https://toproco.jirijanousek.cz/  
**GitHub:** https://github.com/George-Pura-Vida/TOPROCO  
**Stav:** statický veřejný prototyp; není připojen CRM, bankovní API ani administrace.

## Co je hotové
- Responzivní HTML/CSS/JavaScript bez externích knihoven.
- Přepínání češtiny, slovenštiny, angličtiny, španělštiny a zjednodušené čínštiny.
- Odkazy na Moje renta, Moje krypto, Můj DIP a Můj kmen; poslední aplikace zůstává soukromá.
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
7. Verzi před veřejnou propagací právně a obsahově schvalte a nahraďte `noindex,nofollow` za `index,follow` až po spuštění.

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

Landing page zůstává označená `noindex,nofollow`, dokud nebude hotová kontrola ochranných známek, právního textu, překladů a aktuálních regulatorních požadavků. Před ostrým spuštěním je nutné toto nastavení vědomě změnit.

Nejsou implementovány: jednotná registrace, AI agenti, bankovní/tržní API, soukromá B2B CRM, prodej licencí ani platby. Pro tyto funkce bude potřeba samostatné zabezpečené backendové řešení.
