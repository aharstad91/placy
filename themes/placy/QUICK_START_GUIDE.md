# Quick Start Guide - Walking Distance Feature

## 🎯 Hva gjør denne funksjonen?

Viser automatisk gangtid og -avstand fra eiendommen til hver POI (Point of Interest) i Tema Story kapitler, samt interaktive gangruter på kartet.

## 🚀 Slik setter du det opp (5 minutter!)

### Steg 1: Åpne Theme Story
```
WordPress Admin → Tema Historier → Velg/Lag ny
```

### Steg 2: Legg til Eiendommens Lokasjon
I **Sidebar** (høyre side), finn **Theme Story Fields**:

```
Eiendommens Latitude:  63.4305
Eiendommens Longitude: 10.3951
```

💡 **Finn coordinates på Google Maps**:
- Høyreklikk på kart → "What's here?"
- Kopier tallene (lat, lng)

### Steg 3: Legg til POIs i Kapittel
1. Bruk **Chapter Wrapper** block
2. Legg til **POI List** block inni
3. Velg Points fra lista
4. **Publiser** ✓

### Steg 4: Se Resultatet! 🎉
Åpne Theme Story på frontend:
- **På labels**: `🚶 8 min · Kafé`
- **Rød markør**: Viser eiendommen
- **Klikk POI**: Se rute på kartet

## 📊 Hva vises hvor?

### På Kartet:
```
[🔴] ← Eiendom (rød dråpe)
  |
  | ← Gangrute (grønn linje)
  |
[📍] ← POI (bilde/farge)
  "🚶 8 min · Kafé"
```

### I Popup (når du klikker):
```
┌─────────────────────────┐
│ Kafé                    │
│ 🚶 8 min (650m)         │
│ Klikk for å vise rute   │
└─────────────────────────┘
```

## 🎨 UX Detaljer

### Labels
- **Vises når**: Zoom > 15
- **Format tid**: "8 min" eller "1 t 15 min"
- **Format avstand**: "650m" eller "1.2km"

### Ruter
- **Farge**: Grønn (#76908D)
- **Stil**: Smooth linje, 4px bred
- **Fjernes**: Automatisk ved kapittelbytte

### Interaksjon
1. Scroll til kapittel → POIs dukker opp
2. Klikk POI → Rute tegnes
3. Scroll videre → Rute fjernes, nye POIs vises

## 🔍 Eksempler

### Eksempel 1: Kort avstand
```
Input:
- Eiendom: [10.3951, 63.4305]
- Kafé: [10.4012, 63.4325]

Output:
🚶 8 min · Kafé
```

### Eksempel 2: Lang avstand
```
Input:
- Eiendom: [10.3951, 63.4305]
- Butikk: [10.5123, 63.4567]

Output:
🚶 1 t 15 min · Butikk
```

### Eksempel 3: Uten startlokasjon
```
Input:
- Eiendom: [ikke satt]
- Kafé: [10.4012, 63.4325]

Output:
Kafé
(ingen gangtid vises)
```

## 💰 Kostnad & API Bruk

### Mapbox Gratis Tier
- **100,000 requests/måned** = GRATIS
- Deretter: $0.50 per 1000 requests

### Estimat:
```
1 Theme Story med 3 kapitler × 5 POIs = 15 API calls
100,000 / 15 = ~6,666 visninger/måned GRATIS
```

Med caching: Første load = 15 calls, påfølgende = 0 calls (cached)

## 🐛 Feilsøking

### Problem: Ingen gangtid vises
**Løsning**:
1. ✓ Sjekk at Theme Story har lat/lng fylt ut
2. ✓ Sjekk at POIs har coordinates
3. ✓ Åpne browser console, se etter feil

### Problem: API-feil i console
**Løsning**:
1. ✓ Sjekk Mapbox token i `/inc/mapbox-config.php`
2. ✓ Sjekk at du ikke har overskredet rate limit
3. ✓ Test coordinates er gyldige (lat: -90 til 90)

### Problem: Rute vises ikke
**Løsning**:
1. ✓ Sjekk at start location er satt
2. ✓ Prøv å klikke direkte på POI-markør
3. ✓ Zoom inn/ut og prøv igjen

## 📚 Mer Info

- Full dokumentasjon: `WALKING_DISTANCE_FEATURE.md`
- Implementation details: `IMPLEMENTATION_SUMMARY.md`
- Mapbox Directions API: https://docs.mapbox.com/api/navigation/directions/

## ✨ Tips & Tricks

### For Best Performance:
- Legg alltid inn eiendomskoordinater først
- Test med få POIs (2-3) før du scaler opp
- Bruk thumbnail bilder på POIs for raskere lasting

### For Best UX:
- Velg POIs som faktisk er gangbare (<30 min)
- Group POIs logisk i kapitler (f.eks. "Mat & Drikke")
- Bruk beskrivende titler: "Kafé Møllenberg" ikke bare "Kafé"

### For Redaktører:
- ⚠️ Husk å **Publiser** etter endringer
- 💡 Test alltid på frontend etter oppdatering
- 🎯 Bruk samme coordinates for alle Tema Historier i samme eiendom

---

**Spørsmål?** Sjekk dokumentasjonen eller test det ut! 🚀
