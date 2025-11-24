# Quick Reference: Automated Descriptions

## 🚀 One-Page Cheat Sheet

---

## URLs

```
GET:  http://localhost:8888/placy/wp-json/placy/v1/google-points/descriptions
POST: http://localhost:8888/placy/wp-json/placy/v1/google-points/descriptions
```

---

## Commands

### Check Status
```bash
curl -s "http://localhost:8888/placy/wp-json/placy/v1/google-points/descriptions" | jq '.stats'
```

### Import Descriptions
```bash
cd /Applications/MAMP/htdocs/placy/wp-content/themes/placy
php import-descriptions.php descriptions.json
```

---

## JSON Format

```json
{
  "place_id_1": "Description text 2-3 sentences...",
  "place_id_2": "Description text 2-3 sentences..."
}
```

**Important:** Use `place_id` (Google ID), NOT `post_id` (WordPress ID)

---

## For Claude/AI

### Fetch Data
```javascript
const response = await fetch('http://localhost:8888/placy/wp-json/placy/v1/google-points/descriptions');
const data = await response.json();
const needsDescription = data.points.filter(p => !p.has_description);
```

### Output Format
```javascript
const descriptions = {
  "ChIJab_zEdAxbUYRiMCFnG3IS34": "Speilsalen er et elegant konserthus...",
  // ... more
};
console.log(JSON.stringify(descriptions, null, 2));
```

---

## Writing Guidelines

✅ **DO:**
- 2-3 sentences (30-80 words)
- Concrete and specific details
- What makes it unique
- Research-based facts

❌ **DON'T:**
- Generic phrases ("popular spot")
- Sales language ("the best!")
- Info already in system (rating, address)
- More than 3 sentences

---

## Example Descriptions

**Restaurant:**
> "Speilsalen er et elegant konserthus i Folketeaterbygningen som har vært et kulturelt samlingspunkt siden 1935. Med sin karakteristiske arkitektur og akustikk er dette stedet perfekt for alt fra klassisk musikk til samtidskunst."

**Café:**
> "Tim Wendelboe er en prisvinnende kaffebrenneri og kafé som har satt standarden for spesialkaffé i Norge siden 2007. Stedet brenner egen kaffe og holder barista-kurs for entusiaster."

---

## Authentication

### Create Application Password
```
WordPress Admin → Users → Profile
→ Application Passwords → "Description Import"
→ Copy password
```

### Use in Import Script
```bash
php import-descriptions.php descriptions.json
# Username: [your-username]
# Password: [application-password]
```

---

## Files

```
inc/google-points-descriptions-api.php  # REST API
import-descriptions.php                  # Import script
AUTOMATED_DESCRIPTIONS_GUIDE.md         # Full guide
CLAUDE_DESCRIPTION_WORKFLOW.md          # AI guide
test-descriptions.json                   # Test file
```

---

## Troubleshooting

**"Authentication required"**
→ Create Application Password or skip auth in dev

**"Google Point not found"**
→ Check place_id is correct (case-sensitive)

**"Empty description"**
→ Ensure description has text content

**curl command not working**
→ Install jq: `brew install jq`

---

## Quick Test

```bash
# 1. Check status
curl -s "http://localhost:8888/placy/wp-json/placy/v1/google-points/descriptions" | jq '.stats'

# 2. Run test import
cd /Applications/MAMP/htdocs/placy/wp-content/themes/placy
php import-descriptions.php test-descriptions.json

# 3. Verify in WordPress
open http://localhost:8888/placy/wp-admin/post.php?post=308&action=edit
```

---

## Current Stats (Trondheim)

- **Total:** 30 Google Points
- **With descriptions:** 4 (13.3%)
- **Need descriptions:** 26 (86.7%)

**Ready for batch processing!**

---

**Full docs:** See `AUTOMATED_DESCRIPTIONS_GUIDE.md`
