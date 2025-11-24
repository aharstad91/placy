# Automated Descriptions for Google Points

**Halvautomatisk pipeline for kvalitetsbeskrivelser av Google Places-importerte POI-er**

---

## 📋 Oversikt

Placy importerer hundrevis av POI-er fra Google Places API. Disse kommer med navn, rating og kategori, men **uten redaksjonelle beskrivelser**. For å oppnå Placy-kvalitet trenger hvert punkt en kort, engasjerende tekst.

Denne løsningen lar Claude AI skrive beskrivelser basert på web-research, og et script importerer dem tilbake til WordPress i batch.

---

## 🔄 Arbeidsflyt

```
┌─────────────────────────────────────────────────────────────┐
│                  1. EKSPORTER DATA                          │
│  WordPress REST API → JSON med alle Google Points          │
│  Inkluderer: navn, rating, adresse, kategorier, status     │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                  2. CLAUDE RESEARCHER                       │
│  - Fetcher endpoint                                         │
│  - Filtrerer til punkter som mangler beskrivelse           │
│  - Søker opp hvert sted på nett                            │
│  - Skriver 2-3 setninger per punkt                         │
│  - Lagrer som JSON: { "place_id": "description", ... }     │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                  3. IMPORTER TILBAKE                        │
│  PHP-script matcher på place_id                            │
│  Oppdaterer editorial_text-feltet via REST API             │
│  Viser resultat med statistikk                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 🚀 Bruksanvisning

### Steg 1: Hent data fra WordPress

**REST API Endpoint:**
```
GET http://localhost:8888/placy/wp-json/placy/v1/google-points/descriptions
```

**Response format:**
```json
{
  "success": true,
  "stats": {
    "total": 150,
    "with_description": 45,
    "without_description": 105,
    "percentage_complete": 30.0
  },
  "points": [
    {
      "post_id": 123,
      "place_id": "ChIJab_zEdAxbUYRiMCFnG3IS34",
      "name": "Speilsalen",
      "description": "",
      "has_description": false,
      "rating": 4.7,
      "review_count": 234,
      "address": "Prinsens gate 18, Oslo",
      "types": ["restaurant", "food"],
      "categories": ["Restaurant", "Kultursteder"],
      "website": "https://example.com"
    }
  ]
}
```

**I VS Code (via Claude):**
```javascript
// Claude kan fetche dette direkte
const response = await fetch('http://localhost:8888/placy/wp-json/placy/v1/google-points/descriptions');
const data = await response.json();

// Filtrer til de som mangler beskrivelse
const needsDescription = data.points.filter(p => !p.has_description);
```

---

### Steg 2: Skriv beskrivelser (Claude)

**Prompt for Claude:**
```
Jeg har en liste med steder fra Oslo som mangler beskrivelser.
For hvert sted:
1. Søk opp stedet på nett for å få kontekst
2. Skriv 2-3 setninger som:
   - Er engasjerende og konkrete
   - Fremhever hva som gjør stedet spesielt
   - Passer for et urbant publikum
3. Unngå klisjéer som "populær blant lokalbefolkningen"

Returner resultat som JSON:
{
  "place_id": "beskrivelse...",
  "place_id": "beskrivelse..."
}

Steder:
[paste needsDescription]
```

**Eksempel output (descriptions.json):**
```json
{
  "ChIJab_zEdAxbUYRiMCFnG3IS34": "Speilsalen er et elegant konserthus i Folketeaterbygningen som har vært et kulturelt samlingspunkt siden 1935. Med sin karakteristiske arkitektur og akustikk er dette stedet perfekt for alt fra klassisk musikk til samtidskunst.",
  "ChIJN1t_tDeuEmsRUsoyG83frY4": "En moderne kafé i Grünerløkka som brenner sin egen kaffe og serverer hjemmebakte bakevarer. Stedet er kjent for sitt minimalistiske interiør og seriøse forhold til kaffekvalitet."
}
```

---

### Steg 3: Importer til WordPress

**Terminal (fra theme root):**
```bash
cd /Applications/MAMP/htdocs/placy/wp-content/themes/placy
php import-descriptions.php descriptions.json
```

**Interaktiv prosess:**
```
========================================
  Placy Description Import
========================================

File: descriptions.json
Descriptions to import: 105

API Endpoint: http://localhost:8888/placy/wp-json/placy/v1/google-points/descriptions

Authentication:
You need to authenticate to update descriptions.
Options:
  1. Application Password (recommended)
  2. Skip authentication (if endpoint is public)

Enter your username (or press Enter to skip auth): admin
Enter your application password: [hidden]

Sending request...

========================================
  Results
========================================

✓ Success!

Statistics:
  Total:   105
  Updated: 103 ✓
  Failed:  2 ✗
  Skipped: 0

Updated 103 of 105 descriptions.

Updated posts:
  ✓ Speilsalen
    Post ID: 123
    Place ID: ChIJab_zEdAxbUYRiMCFnG3IS34
    Edit URL: http://localhost:8888/placy/wp-admin/post.php?post=123&action=edit

  ✓ Tim Wendelboe
    Post ID: 124
    Place ID: ChIJN1t_tDeuEmsRUsoyG83frY4
    Edit URL: http://localhost:8888/placy/wp-admin/post.php?post=124&action=edit

========================================
Import completed successfully!
========================================
```

---

## 🔐 Autentisering

### Metode 1: Application Password (anbefalt)

**Opprett Application Password:**
1. WordPress Admin → Brukere → Din profil
2. Scroll ned til "Application Passwords"
3. Navn: "Description Import"
4. Klikk "Add New Application Password"
5. Kopier passordet (vises bare én gang)

**Bruk i script:**
```bash
php import-descriptions.php descriptions.json
# Når promptet: bruk ditt brukernavn + application password
```

### Metode 2: Gjør endpoint public (utviklingsmiljø)

**I `inc/google-points-descriptions-api.php`:**
```php
function placy_api_auth_check( $request ) {
    // Disable auth check in development
    if ( defined( 'WP_ENV' ) && WP_ENV === 'development' ) {
        return true;
    }
    
    return current_user_can( 'edit_posts' );
}
```

---

## 📁 Filstruktur

```
placy/wp-content/themes/placy/
├── inc/
│   └── google-points-descriptions-api.php   # REST API endpoints
├── import-descriptions.php                   # CLI import script
├── AUTOMATED_DESCRIPTIONS_GUIDE.md          # Denne filen
└── descriptions.json                         # Generated by Claude
```

---

## 🔧 API Reference

### GET `/wp-json/placy/v1/google-points/descriptions`

**Beskrivelse:** Hent alle Google Points med beskrivelse-status

**Auth:** Ingen (public endpoint)

**Response:**
```json
{
  "success": true,
  "stats": {
    "total": 150,
    "with_description": 45,
    "without_description": 105,
    "percentage_complete": 30.0
  },
  "points": [...]
}
```

**Point object:**
```typescript
interface Point {
  post_id: number;
  place_id: string;
  name: string;
  description: string;
  has_description: boolean;
  rating: number | null;
  review_count: number | null;
  address: string;
  types: string[];
  categories: string[];
  website: string;
  edit_url: string;
}
```

---

### POST `/wp-json/placy/v1/google-points/descriptions`

**Beskrivelse:** Bulk-oppdater beskrivelser

**Auth:** Krever `edit_posts` capability (Application Password eller Cookie)

**Request Body:**
```json
{
  "descriptions": {
    "place_id_1": "Description text...",
    "place_id_2": "Description text..."
  }
}
```

**Response:**
```json
{
  "success": true,
  "stats": {
    "total": 100,
    "updated": 98,
    "failed": 2,
    "skipped": 0
  },
  "updated": [
    {
      "post_id": 123,
      "place_id": "ChIJ...",
      "name": "Speilsalen",
      "edit_url": "http://..."
    }
  ],
  "failed": [
    {
      "place_id": "ChIJ...",
      "reason": "Google Point not found"
    }
  ],
  "skipped": [],
  "message": "Updated 98 of 100 descriptions."
}
```

---

## 💡 Tips for Claude

### Best Practices for Description Writing

**Bra eksempel:**
> "Speilsalen er et elegant konserthus i Folketeaterbygningen som har vært et kulturelt samlingspunkt siden 1935. Med sin karakteristiske arkitektur og akustikk er dette stedet perfekt for alt fra klassisk musikk til samtidskunst."

**Unngå:**
- Generiske fraser: "et must-see", "populær blant turister"
- For lang tekst: Hold deg til 2-3 setninger
- Reklame-språk: "den beste kaffen i byen"
- Opplagt informasjon som allerede finnes (rating, adresse)

### Research Strategy

1. **Google søk:** `[stedsnavn] + Oslo + hva er kjent for`
2. **Sjekk offisiell nettside:** Historie, konsept, unike features
3. **Timeoutdk/Visit Oslo:** Ofte gode beskrivelser
4. **Wikipedia:** For historiske steder

### Batch Processing

Anbefalt batch-størrelse: **25-50 steder per kjøring**

```javascript
// Split into batches
const batches = [];
for (let i = 0; i < needsDescription.length; i += 25) {
  batches.push(needsDescription.slice(i, i + 25));
}

// Process batch 1
const batch1 = batches[0];
// ... research and write descriptions
```

---

## 🧪 Testing

### Test API Endpoint

**Terminal:**
```bash
curl http://localhost:8888/placy/wp-json/placy/v1/google-points/descriptions
```

**Browser:**
```
http://localhost:8888/placy/wp-json/placy/v1/google-points/descriptions
```

### Test Import (Dry Run)

**Lag testfil (test-descriptions.json):**
```json
{
  "ChIJab_zEdAxbUYRiMCFnG3IS34": "Dette er en testbeskrivelse."
}
```

**Kjør import:**
```bash
php import-descriptions.php test-descriptions.json
```

**Verifiser i WordPress:**
```
WP Admin → Google Points → Finn stedet → Sjekk "Editorial Text" felt
```

---

## 🚨 Feilsøking

### Problem: "Authentication required"

**Løsning:** Bruk Application Password eller gjør endpoint public

### Problem: "Google Point not found"

**Årsak:** Place ID eksisterer ikke i databasen

**Løsning:** Verifiser at place_id matcher eksakt (case-sensitive)

### Problem: "Empty description"

**Årsak:** Beskrivelsen er tom eller bare whitespace

**Løsning:** Sjekk JSON-fil for tomme strenger

### Problem: Import script ikke kjører

**Sjekk PHP-versjon:**
```bash
php -v  # Må være 7.4+
```

**Sjekk cURL:**
```bash
php -m | grep curl
```

---

## 📊 Skalerbarhet

**Tidsestimat:**
- Claude research: ~2-3 min per sted
- Skriving: ~30 sek per sted
- Import: ~1 sek per sted

**Total tid for 100 steder:**
- Research + skriving: ~45 minutter
- Import: ~2 minutter
- **Total: ~47 minutter vs. timer med manuelt arbeid**

**Best practice:**
- Kjør i batches på 25-50 steder
- Gjør en kvalitetssjekk på første batch
- Automatiser resten når du er fornøyd med stilen

---

## 🔄 Re-import og Oppdatering

**Scenario:** Nye Google Points importert, trenger beskrivelser

**Prosess:**
1. Fetch endpoint på nytt
2. Filtrer til `has_description: false`
3. Claude skriver beskrivelser
4. Import

**Scenario:** Oppdatere eksisterende beskrivelser

**Prosess:**
1. Lag ny JSON med place_id + forbedret beskrivelse
2. Kjør import (overskriver eksisterende)

---

## 📝 Vedlikehold

### Overvåke status

**Quick check:**
```bash
curl -s http://localhost:8888/placy/wp-json/placy/v1/google-points/descriptions | \
  jq '.stats'
```

**Output:**
```json
{
  "total": 150,
  "with_description": 145,
  "without_description": 5,
  "percentage_complete": 96.7
}
```

### Automatisk rapportering

**Legg til i WordPress:**
```php
// Send epost ved nye punkter uten beskrivelse
add_action( 'placy_google_point_imported', function( $post_id ) {
    $editorial_text = get_field( 'editorial_text', $post_id );
    if ( empty( $editorial_text ) ) {
        // Send notification to editor
        wp_mail( 'editor@placy.no', 'New point needs description', ... );
    }
} );
```

---

## 🎯 Resultat

**Før:**
- Manuelt arbeid: 2-5 min per sted
- 100 steder = 3-8 timer

**Etter:**
- Automatisert research og skriving
- 100 steder = ~45 min + 2 min import
- **Tidsbesparelse: 80-90%**

**Kvalitet:**
- Konsistent tone
- Faktasjekket via web-research
- Engasjerende og konkret
- Placy-kvalitet på skala

---

## 📚 Relaterte filer

- `inc/google-points-descriptions-api.php` - REST API implementation
- `import-descriptions.php` - CLI import tool
- `inc/placy-bulk-import.php` - Original Google Places import
- `inc/placy-acf-fields.php` - ACF field definitions (editorial_text)
- `inc/placy-graphql.php` - GraphQL integration

---

## 🤝 Brukerstøtte

**Spørsmål om API:**
- Sjekk `inc/google-points-descriptions-api.php` for implementasjonsdetaljer

**Spørsmål om import:**
- Sjekk `import-descriptions.php` for script-logikk

**Spørsmål om feltstruktur:**
- Sjekk `inc/placy-acf-fields.php` → `placy_register_google_point_fields()`

---

**Versjon:** 1.0.0  
**Dato:** November 2025  
**Forfatter:** Placy Development Team
