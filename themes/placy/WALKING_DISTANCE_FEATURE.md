# Walking Distance & Route Feature

## Oversikt

Tema Story kapitler viser nå automatisk gangavstand og -tid fra eiendommen til hver POI, samt interaktive ruter på kartet.

## Hva er implementert

### 1. ACF Fields for Startlokasjon
- **Eiendommens Latitude** (`start_latitude`)
- **Eiendommens Longitude** (`start_longitude`)

Disse feltene finnes i sidebar på Theme Story redigeringssiden.

### 2. Mapbox Directions API Integration
- Bruker `walking` profile for fotgjengere
- Beregner automatisk gangavstand og -tid til hver POI
- Cacher resultater for bedre performance

### 3. Visning på Kart
- **Labels viser**: `🚶 8 min · POI navn`
- **Eiendomsmarkør**: Rød dråpeformet markør viser startpunkt
- **POI markører**: Viser bilde eller farge med gangtid i label
- **Popup**: Klikk på POI for å vise full info og rute

### 4. Interaktive Ruter
- Klikk på en POI-markør for å tegne gangrute på kartet
- Ruten vises som en grønn linje fra eiendom til POI
- Popup viser gangtid og avstand
- Klikk igjen eller bytt kapittel for å fjerne rute

## Slik bruker du det

### Steg 1: Legg til Eiendommens Lokasjon
1. Åpne en Theme Story i WordPress admin
2. Finn sidebar med "Theme Story Fields"
3. Legg inn:
   - **Latitude**: f.eks. `63.4305`
   - **Longitude**: f.eks. `10.3951`
4. Publiser/oppdater

### Steg 2: Legg til POIs i Kapitler
1. Bruk Chapter Wrapper block
2. Legg til POI List block inni
3. Velg Points som skal vises
4. Publiser

### Steg 3: Se Resultat
- Åpne Theme Story på frontend
- Scroll til kapittel med POIs
- Se automatisk gangtid på labels
- Klikk på POI for å vise rute

## Teknisk Info

### API Bruk
- **Mapbox Directions API**
- **Profil**: Walking (optimalisert for fotgjengere)
- **Gratis tier**: 100,000 requests/måned
- **Kostnad deretter**: $0.50 per 1000 requests

### Caching
- Walking distances caches i minnet under session
- Reduserer API-kall når samme POIs vises flere ganger
- Cache nullstilles ved sideoppdatering

### Performance
- Alle gangavstander hentes parallelt (Promise.all)
- Markers rendres først etter at alle data er klar
- Smooth transitions mellom kapitler

## Formatering

### Tid
- Under 60 min: `8 min`, `45 min`
- Over 60 min: `1 t 15 min`, `2 t`

### Avstand
- Under 1 km: `650m`, `850m`
- Over 1 km: `1.2km`, `3.5km`

## Eksempel

```javascript
// Start location fra ACF
startLocation: [10.3951, 63.4305]

// POI coords
poiCoords: [10.4012, 63.4325]

// API Response
{
    distance: 650,      // meters
    duration: 480,      // seconds (8 min)
    geometry: {...}     // GeoJSON for route
}

// Vises som
"🚶 8 min · Kafé"
```

## Feilsøking

### Ingen gangtid vises
- Sjekk at Theme Story har latitude/longitude fylt ut
- Sjekk at POIs har coordinates
- Sjekk console for API-feil

### API-feil
- Sjekk at Mapbox token er gyldig
- Sjekk at du ikke har overskredet rate limit
- Sjekk at coordinates er gyldige (lat: -90 til 90, lng: -180 til 180)

### Rute vises ikke
- Sjekk at POI er klikkbar
- Sjekk at start location er satt
- Åpne console og se etter feilmeldinger

## Fremtidige forbedringer

### Potensielle tillegg:
- Valg mellom walking/cycling/driving
- Vise flere alternative ruter
- Lagre populære ruter i database
- Offline fallback med luftlinje-estimat
- Elevation profile på ruter
- "Compare POIs" funksjon

## Filendringer

### Nye filer:
- Ingen - alt er lagt til i eksisterende

### Endrede filer:
1. `/inc/acf-fields.php` - Theme Story ACF fields
2. `/functions.php` - Pass start_location til JS
3. `/js/tema-story-map.js` - Directions API + route drawing

## Support

Ved problemer eller spørsmål, sjekk:
1. Browser console for feilmeldinger
2. Mapbox account for API usage
3. ACF fields er korrekt konfigurert
4. POI List blocks har valgte Points
