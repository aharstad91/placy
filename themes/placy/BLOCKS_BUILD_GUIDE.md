# Block Styles Build System

## Oversikt

Alle ACF-blokkene i Placy-temaet bruker Tailwind CSS for styling. For at Tailwind-klassene skal fungere både i frontend og i WordPress-editoren (admin), må CSS-en kompileres fra `input.css` til `style.css` for hver blokk.

## Blokker med Tailwind

Følgende blokker bruker Tailwind CSS:
- `poi-map-card` - POI Kart
- `poi-list` - POI Liste  
- `poi-highlight` - POI Highlight
- `poi-gallery` - POI Gallery
- `image-column` - Image Column

## Fil-struktur per blokk

Hver blokk har følgende filer:

```
blocks/[block-name]/
  ├── block.json              # Block metadata
  ├── template.php            # PHP template (bruker Tailwind-klasser)
  ├── input.css              # Tailwind source med @tailwind directives
  ├── tailwind.config.js     # Tailwind config for denne blokken
  └── style.css              # Kompilert output (ikke rediger manuelt!)
```

## Hvordan kompilere styles

### Kompilere alle blokker

```bash
# Fra theme root
npm run build:blocks

# Eller direkte:
./build-blocks.sh
```

### Kompilere én blokk manuelt

```bash
cd blocks/[block-name]
npx tailwindcss -i ./input.css -o ./style.css --minify
```

### Kompilere alt (theme CSS + blocks)

```bash
npm run build
```

## Når må du kompilere?

Du må kjøre build når du:

1. **Endrer HTML/PHP** i `template.php` og legger til nye Tailwind-klasser
2. **Endrer custom CSS** i `input.css`  
3. **Oppretter en ny blokk** med Tailwind

## Hvordan det fungerer

1. **input.css** inneholder:
   - `@tailwind components;` - Tailwind komponenter
   - `@tailwind utilities;` - Tailwind utility-klasser
   - Custom CSS for blokken

2. **tailwind.config.js** sier:
   - Hvilke filer som skal skannes for Tailwind-klasser (`content: ['./template.php']`)
   - Dette gjør at bare klasser som faktisk brukes inkluderes i output

3. **style.css** genereres automatisk:
   - Inneholder alle Tailwind-klasser som brukes i template.php
   - Inneholder custom CSS fra input.css
   - Er minified for bedre ytelse

4. **functions.php** laster style.css:
   - Via `enqueue_block_assets` hook
   - Styles lastes både i frontend og editor
   - Garanterer identisk utseende

## Tips

- **Ikke rediger style.css manuelt** - den overskrives ved hver build
- **Bruk Tailwind-klasser direkte i template.php** for beste resultat
- **Legg custom CSS i input.css** hvis du trenger noe Tailwind ikke kan gi deg
- **Kjør build før du committer** endringer i blokkene

## Eksempel workflow

```bash
# 1. Rediger template.php, legg til nye Tailwind-klasser
code blocks/poi-gallery/template.php

# 2. Kompiler blokken
npm run build:blocks

# 3. Refresh WordPress admin - blokkene ser nå riktige ut!
```

## Feilsøking

### "Blokken ser feil ut i admin"
→ Kjør `npm run build:blocks` for å kompilere CSS

### "Mine Tailwind-klasser fungerer ikke"
→ Sjekk at klassen er brukt i template.php og kjør build

### "Browserslist is outdated" warning
→ Kan ignoreres, eller kjør: `npx update-browserslist-db@latest`
