# TOWER FINANCE — Landing page: Webinár o investovaní

Konverzná landing page s registračným formulárom pre bezplatný webinár
„Ako začať investovať krok po kroku" (Ondrej Broska, TOWER FINANCE).

Stránka je **statická** (HTML/CSS/JS, bez frameworku), takže ju vieš hostovať
kdekoľvek. Logo je vložené priamo v súbore, takže funguje aj po otvorení
lokálne (dvojklikom), aj na akomkoľvek hostingu.

## Obsah repozitára

| Súbor | Popis |
|-------|-------|
| `index.html` | Hlavná landing page (všetky sekcie + registračný formulár + poďakovanie). |
| `dakujeme.html` | Samostatná ďakovacia stránka (voliteľná — viď „Režim poďakovania" nižšie). |
| `ecomail-endpoint.php` | Serverový mostík medzi formulárom a Ecomailom (drží API kľúč v bezpečí). |
| `assets/` | Logo TOWER FINANCE (tmavá a malachitová verzia, PNG s priehľadným pozadím). |

## Čo treba doplniť pred spustením

V `index.html` nahraď zástupné texty:

- `[DOPLNIŤ]` — dátum a čas webinára (3× v texte: hero a aside formulára),
- `[web]`, `[e-mail]`, `[telefón]` — kontakty v pätičke,
- odkaz na stránku s ochranou osobných údajov (atribút `href="#"` pri GDPR).

## Napojenie na Ecomail (3 kroky)

> **Dôležité:** API kľúč nikdy nepatrí do `index.html` — kód stránky vidí každý
> návštevník v prehliadači. Ecomail navyše neprijíma priame volania z prehliadača.
> Preto formulár volá `ecomail-endpoint.php`, ktorý beží na serveri a kľúč drží v bezpečí.

1. Nahraj `ecomail-endpoint.php` na hosting s podporou PHP
   (napr. `https://tvojadomena.sk/ecomail-endpoint.php`).
2. V tomto súbore doplň:
   - `ECOMAIL_API_KEY` — Ecomail → Nastavenia → Integrácie → API,
   - `ECOMAIL_LIST_ID` — číslo tvojho zoznamu kontaktov.
   (Pre `.app` účty zmeň `ECOMAIL_BASE` na `https://api2.ecomailapp.com`.)
3. V `index.html` (hore v bloku `CONFIG`) nastav:
   ```js
   const CONFIG = {
     endpoint: "https://tvojadomena.sk/ecomail-endpoint.php",
     redirectToThankYouPage: false,   // true => presmeruje na dakujeme.html
     thankYouUrl: "dakujeme.html"
   };
   ```

Kým je `endpoint` prázdny, beží **DEMO režim** — formulár sa dá vyskúšať,
dáta sa vypíšu do konzoly prehliadača, ale nikam sa neodosielajú.

### Režim poďakovania
- `redirectToThankYouPage: false` (predvolené) — poďakovanie sa zobrazí priamo
  na stránke po odoslaní (`dakujeme.html` nie je potrebné).
- `redirectToThankYouPage: true` — po odoslaní presmeruje na `dakujeme.html`.

## Lokálny náhľad

Stačí otvoriť `index.html` v prehliadači. Pre náhľad cez lokálny server:
```bash
python3 -m http.server 8000
# potom otvor http://localhost:8000
```

## Nasadenie

**GitHub Pages** (statická časť — landing + poďakovanie):
Settings → Pages → Source: `main` / root. Pozn.: GitHub Pages nevie spustiť PHP,
takže `ecomail-endpoint.php` musí bežať na inom PHP hostingu (alebo použiješ
serverless funkciu — viď nižšie).

**Netlify / Vercel:** nahraj repo. PHP endpoint nahraď serverless funkciou
(viem ju doplniť na požiadanie) alebo nech beží PHP zvlášť.

## Brand farby

| Názov | HEX | Použitie |
|-------|-----|----------|
| Dark Green | `#0E3318` | primárna (pozadia, text, tmavé plochy) |
| Malachite | `#3CD64E` | akcent (CTA, zvýraznenia, ikony) |

Pomer ~60/40 (tmavá dominuje, malachit ako akcent).
