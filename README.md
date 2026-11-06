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
