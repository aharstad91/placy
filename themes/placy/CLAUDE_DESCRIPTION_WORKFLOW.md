# Claude AI - Quick Start Guide for Description Writing

**Rask guide for å skrive beskrivelser til Google Points**

---

## 🎯 Din oppgave

Skriv korte, engasjerende beskrivelser (2-3 setninger) for POI-er i Oslo som mangler innhold.

---

## ⚡ Arbeidsflyt (4 steg)

### Steg 1: Hent data fra WordPress

```javascript
// Fetch all Google Points
const response = await fetch('http://localhost:8888/placy/wp-json/placy/v1/google-points/descriptions');
const data = await response.json();

// Show statistics
console.log(`Total points: ${data.stats.total}`);
console.log(`Need descriptions: ${data.stats.without_description}`);

// Filter to points without descriptions
const needsDescription = data.points.filter(p => !p.has_description);
console.log(`Processing ${needsDescription.length} points`);
```

---

### Steg 2: Research og skriv (gjør dette for 25-50 om gangen)

**For hvert sted:**

1. **Se på kontekst:**
   ```javascript
   const place = needsDescription[0];
   console.log(place.name);          // "Speilsalen"
   console.log(place.address);       // "Prinsens gate 18, Oslo"
   console.log(place.types);         // ["concert_hall", "point_of_interest"]
   console.log(place.rating);        // 4.7
   console.log(place.categories);    // ["Kultursteder"]
   console.log(place.website);       // "https://..."
   ```

2. **Research online:**
   - Google: `[stedsnavn] Oslo`
   - Besøk nettside hvis tilgjengelig
   - Sjekk Visit Oslo / Timeout Oslo
   - Wikipedia for historiske steder

3. **Skriv beskrivelse:**
   - **2-3 setninger**
   - Konkret og engasjerende
   - Fremhev det unike
   - Unngå klisjéer

**Eksempel:**
> "Speilsalen er et elegant konserthus i Folketeaterbygningen som har vært et kulturelt samlingspunkt siden 1935. Med sin karakteristiske arkitektur og akustikk er dette stedet perfekt for alt fra klassisk musikk til samtidskunst."

---

### Steg 3: Lag JSON-fil

```javascript
// Build descriptions object
const descriptions = {};

// Add each description with place_id as key
descriptions['ChIJab_zEdAxbUYRiMCFnG3IS34'] = "Speilsalen er et elegant konserthus...";
descriptions['ChIJN1t_tDeuEmsRUsoyG83frY4'] = "Tim Wendelboe er en prisvinnende...";
// ... add more

// Save to file (you can do this or just output the JSON)
const json = JSON.stringify(descriptions, null, 2);
console.log(json);
```

**Save as:** `descriptions.json`

**Format:**
```json
{
  "place_id_1": "Beskrivelse her...",
  "place_id_2": "Beskrivelse her..."
}
```

---

### Steg 4: Be brukeren kjøre import

**Tell brukeren:**
```
Jeg har skrevet beskrivelser for [N] steder.
Lagre følgende JSON som 'descriptions.json' og kjør import:

[paste JSON]

Kjør deretter i terminal:
cd /Applications/MAMP/htdocs/placy/wp-content/themes/placy
php import-descriptions.php descriptions.json
```

---

## ✍️ Skriveretningslinjer

### ✅ BRA eksempler

**Restaurant:**
> "En minimalistisk restaurant på Grünerløkka som serverer nordisk fusionskjøkken med råvarer fra lokale gårder. Menyen endres ukentlig basert på sesong, og interiøret er inspirert av japansk design."

**Kafé:**
> "Tim Wendelboe er en prisvinnende kaffebrenneri og kafé som har satt standarden for spesialkaffé i Norge siden 2007. Stedet brenner egen kaffe og holder barista-kurs for entusiaster."

**Museum:**
> "Nasjonalmuseet samler norsk kunst fra Dahl til Munch under ett tak, med over 400 000 objekter. Det nye bygget ved havnepromenaden åpnet i 2022 og er Norges største kunstmuseum."

**Park:**
> "En rolig bypark i Frogner med skulptursamling av Gustav Vigeland, bestående av over 200 bronse- og granittskulpturer. Parken er et populært sted for piknik og løpeturer, spesielt på sommeren."

### ❌ UNNGÅ

**For generisk:**
> ❌ "Et populært sted som mange liker å besøke. Har god mat og hyggelig atmosfære."

**For salgs-orientert:**
> ❌ "Den beste restauranten i Oslo! Du må absolutt oppleve dette stedet!"

**For lang:**
> ❌ "Dette stedet ble grunnlagt i 1920 av en kjent arkitekt som også designet flere andre bygninger i byen. Interiøret har original parkettgulv og vinduer, og det serveres mat fra hele verden. De har også uteservering på sommeren med plass til 50 personer. Menyen skifter hver måned og de tar imot gruppebestillinger."

**Informasjon som allerede finnes:**
> ❌ "Ligger på Grünerløkka og har 4.7 i rating på Google." 
> *(Dette har vi allerede fra Google Places API)*

---

## 🔄 Batch Processing Template

**Prosesser i batches på 25-50:**

```javascript
// Step 1: Fetch data
const response = await fetch('http://localhost:8888/placy/wp-json/placy/v1/google-points/descriptions');
const data = await response.json();
const needsDescription = data.points.filter(p => !p.has_description);

// Step 2: Split into batches
const batchSize = 25;
const batches = [];
for (let i = 0; i < needsDescription.length; i += batchSize) {
  batches.push(needsDescription.slice(i, i + batchSize));
}

console.log(`Total batches: ${batches.length}`);
console.log(`Processing batch 1 of ${batches.length}...`);

// Step 3: Process first batch
const batch1 = batches[0];
const descriptions = {};

for (const place of batch1) {
  console.log(`\nResearching: ${place.name}`);
  console.log(`  Address: ${place.address}`);
  console.log(`  Types: ${place.types.join(', ')}`);
  console.log(`  Categories: ${place.categories.join(', ')}`);
  console.log(`  Website: ${place.website || 'N/A'}`);
  console.log(`  Rating: ${place.rating || 'N/A'} (${place.review_count || 0} reviews)`);
  
  // TODO: Research and write description
  // descriptions[place.place_id] = "Your description here...";
}

// Step 4: Output JSON
console.log('\n=== DESCRIPTIONS JSON ===\n');
console.log(JSON.stringify(descriptions, null, 2));
```

---

## 📋 Kategorispesifikke tips

### Restaurant / Kafé
- Nevn type kjøkken / spesialitet
- Interiør / atmosfære
- Hva som skiller dem ut

### Kultur (museum, galleri, konserthus)
- Hva slags innhold / samlinger
- Arkitektoniske særtrekk
- Historisk betydning

### Butikk
- Hva de selger (specifikt)
- Målgruppe / stil
- Unike features

### Park / Uteområde
- Hva som finnes der (lekeplass, skulpturer, etc)
- Stemning / bruksområde
- Spesielle features

### Bar / Natteliv
- Konsept / atmosfære
- Type musikk / underholdning
- Målgruppe

---

## 🧪 Testeksempel (komplett)

```javascript
// 1. Fetch data
const response = await fetch('http://localhost:8888/placy/wp-json/placy/v1/google-points/descriptions');
const data = await response.json();

// 2. Filter
const needsDescription = data.points.filter(p => !p.has_description);
console.log(`Found ${needsDescription.length} points needing descriptions`);

// 3. Take first 3 as test
const testBatch = needsDescription.slice(0, 3);

// 4. Research and write (simulated)
const descriptions = {
  "ChIJab_zEdAxbUYRiMCFnG3IS34": "Speilsalen er et elegant konserthus i Folketeaterbygningen som har vært et kulturelt samlingspunkt siden 1935. Med sin karakteristiske arkitektur og akustikk er dette stedet perfekt for alt fra klassisk musikk til samtidskunst.",
  
  "ChIJN1t_tDeuEmsRUsoyG83frY4": "Tim Wendelboe er en prisvinnende kaffebrenneri og kafé som har satt standarden for spesialkaffé i Norge siden 2007. Stedet brenner egen kaffe og holder barista-kurs for entusiaster.",
  
  "ChIJ8bCCB8gQQUYRmz-ZN2z-N0w": "Astrup Fearnley Museet er et moderne kunstmuseum på Tjuvholmen med fokus på samtidskunst. Bygget er tegnet av Renzo Piano og har en imponerende skulpturpark ved sjøen."
};

// 5. Output
console.log(JSON.stringify(descriptions, null, 2));
```

**Tell brukeren:**
```
Save this as descriptions.json and run:
php import-descriptions.php descriptions.json
```

---

## 🚨 Vanlige feil å unngå

1. **Bruker feil nøkkel:** Bruk `place_id`, IKKE `post_id`
   ```javascript
   ✅ descriptions[place.place_id] = "...";
   ❌ descriptions[place.post_id] = "...";
   ```

2. **Tom eller for kort beskrivelse:**
   ```javascript
   ❌ descriptions[place.place_id] = "En kafé.";
   ✅ descriptions[place.place_id] = "En minimalistisk kafé...";
   ```

3. **Feil JSON-format:**
   ```json
   ❌ ["description1", "description2"]
   ✅ {
     "place_id_1": "description1",
     "place_id_2": "description2"
   }
   ```

---

## ✅ Suksesskriterier

**En god beskrivelse:**
- ✅ 2-3 setninger (30-80 ord)
- ✅ Konkret og spesifikk
- ✅ Engasjerende tone
- ✅ Fremhever det unike
- ✅ Faktabasert (fra research)

**En god batch:**
- ✅ 25-50 beskrivelser
- ✅ Konsistent kvalitet
- ✅ Korrekt JSON-format
- ✅ Riktig place_id som nøkkel

---

## 📞 Next Steps

1. **Fetch data** fra API
2. **Velg batch** (25-50 steder)
3. **Research** hvert sted
4. **Skriv** beskrivelser
5. **Output JSON** for brukeren
6. **Instruer brukeren** om import

**Start command:**
```javascript
const response = await fetch('http://localhost:8888/placy/wp-json/placy/v1/google-points/descriptions');
const data = await response.json();
console.log(`Ready to process ${data.stats.without_description} points`);
```

---

**Versjon:** 1.0.0  
**Target:** Claude AI / LLM Assistant  
**Purpose:** Automated content generation for Placy POIs
