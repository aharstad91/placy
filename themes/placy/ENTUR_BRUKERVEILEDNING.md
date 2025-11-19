# Entur Live Avganger - Brukerveiledning

## Oversikt
Dette systemet integrerer sanntids avgangsinformasjon fra Entur i transport-POI kort. Avgangene vises automatisk når brukere klikker på transport-markører på kartet.

## Hvordan aktivere Live Avganger

### Steg 1: Finn StopPlace ID

1. Gå til [stoppested.entur.org](https://stoppested.entur.org)
2. Søk etter stoppestedet (f.eks. "Trondheim hurtigbåtterminal")
3. Klikk på riktig stoppested i resultatlisten
4. Kopier StopPlace ID fra URL-en eller siden
   - Format: `NSR:StopPlace:xxxxx` (f.eks. `NSR:StopPlace:74006`)

### Steg 2: Konfigurer Point i WordPress

1. Åpne eller opprett et Point med **Point Type = "Transport"**
2. Scroll ned til seksjonen **"API Integrations"** (vises kun for Transport-POIs)
3. Fyll ut feltene:
   - **Entur StopPlace ID**: Lim inn ID-en du fant (f.eks. `NSR:StopPlace:74006`)
   - **Entur Quay ID**: *Valgfritt* - La stå tom for å vise alle kaier
   - **Vis Live Avganger**: ✓ Aktiver denne
4. Publiser eller oppdater Point

### Steg 3: Test

1. Åpne tema-story siden der POI-et vises
2. Klikk på transport-markøren på kartet
3. POI-kortet åpnes og avganger lastes automatisk
4. Du skal se:
   - Liste med 3-5 neste avganger
   - Tidspunkt + relativ tid (f.eks. "14:32 • 8 min")
   - Destinasjon
   - Sanntidsstatus (● = sanntid, ? = rutetid)
   - "Sanntidsdata fra Entur.no" med timestamp

## Eksempel-data for Testing

### Trondheim Hurtigbåtterminal
- **StopPlace ID**: `NSR:StopPlace:74006`
- **Beskrivelse**: Hovedterminal for hurtigbåtruter langs kysten
- **Avganger**: Kystekspressen til Kristiansund

### Trondheim S (Trondheim Sentralstasjon)
- **StopPlace ID**: `NSR:StopPlace:41129`
- **Beskrivelse**: Hovedjernbanestasjon i Trondheim
- **Avganger**: Tog til Oslo, Bodø, lokal-tog

### Værnes Flyplass
- **StopPlace ID**: `NSR:StopPlace:43189`
- **Beskrivere**: Trondheim lufthavn Værnes
- **Avganger**: Flybuss til/fra sentrum

### Trondheim Bussterminal
- **StopPlace ID**: `NSR:StopPlace:41124`
- **Beskrivelse**: Hovedbussterminal ved Trondheim S
- **Avganger**: Regionale busser

## Avansert: Bruk av Quay ID

Hvis du vil vise avganger fra KUN én spesifikk plattform/kai:

1. På stoppested.entur.org, klikk på stoppestedet
2. Se listen over "Quays" (kaier/plattformer)
3. Kopier Quay ID for den spesifikke kaien
   - Format: `NSR:Quay:xxxxx`
4. Lim inn i **Entur Quay ID**-feltet

**Eksempel**: Trondheim S har mange plattformer. Du kan filtrere kun spor 3 med:
- StopPlace ID: `NSR:StopPlace:41129`
- Quay ID: `NSR:Quay:xxxxx` (finn på stoppested.entur.org)

## Hva vises til brukeren?

### Når avganger er tilgjengelige:
```
─── Neste avganger ───
● 14:32  Kristiansund  8 min  ●
  14:47  Bergen       23 min  ●
  15:15  Trondheim S  51 min  ?

Sanntidsdata fra Entur.no
Hentet kl 14:24
```

### Når INGEN avganger:
- Ingenting vises (ingen feilmelding)
- Kun den kuraterte beskrivelsen du har skrevet

### Hvis API er nede:
- Ingenting vises (ingen feilmelding)
- Kun den kuraterte beskrivelsen du har skrevet

**Viktig prinsipp**: Live avganger er et *supplement* til din kuraterte beskrivelse, ikke en erstatning. Skriv alltid god beskrivelse av transport-POI-et selv om live avganger er aktivert.

## Ikoner og indikatorer

- **● Grønn prikk**: Sanntidsdata (nøyaktig avgangstid)
- **? Grå spørsmålstegn**: Rutetid (ingen sanntidsdata tilgjengelig)
- **Animert grønn prikk** ved tittel: Indikerer live data

## Tekniske detaljer

### API-kall
- Triggere KUN når bruker klikker på POI-markør
- INGEN requests ved page load, hover, eller zoom
- Timeout: 5 sekunder
- Automatisk retry ved feil (2 forsøk)

### Caching
- Data caches i 60 sekunder i nettleseren
- Nye data hentes ved neste kort-åpning etter cache utløp
- Ingen server-side caching

### Rate limits
- Entur API: ~100 requests/minutt (konservativt estimat)
- Gratis å bruke
- Ingen API-nøkkel nødvendig

## Feilsøking

### "Ingen avganger vises"
1. Sjekk at **Vis Live Avganger** er aktivert
2. Verifiser at StopPlace ID er korrekt format: `NSR:StopPlace:xxxxx`
3. Test stoppestedet på stoppested.entur.org
4. Sjekk om det faktisk er avganger i tidsperioden
5. Se JavaScript console for feilmeldinger (F12 i Chrome)

### "Feil StopPlace ID"
- Formatet MÅ være: `NSR:StopPlace:xxxxx` (tall på slutten)
- Ikke bruk Quay ID i StopPlace ID-feltet
- Kopier fra stoppested.entur.org for å være sikker

### "Avganger vises ikke for riktig stoppested"
- Du har kanskje brukt StopPlace ID for feil stoppested
- Gå tilbake til stoppested.entur.org og dobbelsjekk
- Noen steder har flere stoppesteder (f.eks. forskjellige sider av veien)

### "API Integrations-seksjonen vises ikke"
- Sjekk at Point har **Point Type = "Transport"**
- Refresh siden etter å ha satt Point Type
- Seksjonen vises KUN for Transport-POIs

## Support og kontakt

For teknisk support eller spørsmål om Entur-integrasjonen:
- Kontakt utvikler/teknisk ansvarlig
- Dokumentasjon: Se `ENTUR_INTEGRATION.md` i tema-mappen

For spørsmål om Entur API selv:
- [Entur Developer Portal](https://developer.entur.org/)
- [Entur API Documentation](https://developer.entur.org/pages-intro-overview)
