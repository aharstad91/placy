# Claude Code - Session Workflow Instructions

> **IMPORTANT:** Read and follow these instructions in every session.

---

## ⚠️ KRITISK: Git-struktur (Symlink)

```
/placy (repo root)
├── themes/placy/              ← KILDEKODE (git sporer denne)
├── wp-content/themes/placy    → symlink til ../../themes/placy
└── wp-admin/, wp-includes/    ← IGNORERT av git
```

**Regler:**
- **ALLTID** rediger filer i `themes/placy/`
- **ALDRI** rediger i `wp-content/themes/placy/` (det er en symlink)
- WordPress finner theme via symlinken automatisk
- Se `.gitignore` for full dokumentasjon

---

## At Session Start (ALWAYS)

1. **Read session-log.md** at `claude/session-log.md`
2. **Give brief summary** of last status to the user
3. **Check "Next Steps"** to see what should be prioritized

## During Work

### Update session-log.md when you:
- Create, modify, or delete files
- Fix bugs or implement features
- Encounter blockers or problems
- Complete a task from "Next Steps"

### Format for entries:

```markdown
#### [Code] HH:MM - Short description
- What was done
- Files affected: `filename.ext`, `other-file.ext`
- **Next:** Any follow-up if needed
```

### Also update:
- **"Last Updated"** section at top
- **"Active Context"** if focus changes
- **"Next Steps"** - check off completed, add new ones

## At Session End

Before ending a longer session, update session-log.md with:
1. Summary of what was done
2. Any unresolved problems
3. Recommended next steps

## Communication with Claude Chat

The user may also use Claude Chat (same app, different tab) for:
- Strategic planning
- Architecture and design discussions
- Review of documentation

Claude Chat may also update session-log.md. **Always read the file** to see if Chat has added decisions or changed priorities.

---

## Build & Deployment Workflow

### Tailwind CSS: Build Before Push

Tailwind only generates CSS for classes actually used in the code. If you add new Tailwind classes (especially arbitrary values like `bg-[#1A1A1A]`), **you must build CSS before pushing**:

```bash
# 1. Build Tailwind CSS
cd /path/to/your/theme
npm run build

# 2. Verify new classes are included (example)
grep "1A1A1A" dist/style.css

# 3. Stage, commit and push
cd /path/to/your/project
git add .
git commit -m "feat: Description of changes"
git push origin main
```

### Commit Message Convention

Use conventional commits format:

| Prefix | Use for |
|--------|---------|
| `feat:` | New features |
| `fix:` | Bug fixes |
| `refactor:` | Code restructuring (no functional change) |
| `style:` | Formatting, CSS changes |
| `docs:` | Documentation updates |
| `chore:` | Maintenance, dependencies |

### Post-Deploy Checklist

1. **Clear cache** on hosting provider
2. **Hard refresh** in browser (`Cmd+Shift+R` / `Ctrl+Shift+R`)
3. **Test in incognito** to rule out browser cache
4. **Verify functionality** - check critical features work

### Troubleshooting: Styling Works Locally But Not Live

1. **Check if CSS contains the class** - Search in compiled CSS on live
2. **Run `npm run build`** locally and push again
3. **Verify deploy** - Check commit hash matches latest push

---

## Important Project Files

| File | Read when |
|------|-----------|
| `claude/session-log.md` | **ALWAYS** at start |
| `claude/instructions.md` | For workflow reference |
| `CLAUDE.md` | When needing project overview |
| `docs/*.md` | Before working on related features |

---

## Example of Good Session

```
User: "Continue where we left off"

Claude Code: *reads session-log.md*

"According to session-log.md, we last worked on X.
Chat has since decided Y.
Next step is Z. Should I continue with that?"

*does the work*

*updates session-log.md*

"Done with Z. Have updated session-log.md with changes."
```

---

## Hook Configuration (recreate if needed)

If the session hook is missing, create the file `.claude/settings.local.json` in the project root with this content:

```json
{
  "hooks": {
	"UserPromptSubmit": [
	  {
		"matcher": "",
		"hooks": [
		  {
			"type": "command",
			"command": "cat claude/session-log.md claude/instructions.md 2>/dev/null || true"
		  }
		]
	  }
	]
  }
}
```

This hook automatically reads session-log.md and instructions.md on every prompt.

---

## WordPress Admin Access

| Field | Value |
|-------|-------|
| URL | `http://localhost:8888/placy/wp-admin/` |
| Username | `Claude-Agent` |
| Password | `sckmQ4@geNr4#eoatjGzrB%t` |

---

## Database Access (WP-CLI)

**IMPORTANT:** For all database operations, use WP-CLI with MAMP's MySQL in PATH:

```bash
PATH="/Applications/MAMP/Library/bin/mysql80/bin:$PATH" wp [command]
```

### Common WP-CLI Commands

```bash
# List posts by type
PATH="/Applications/MAMP/Library/bin/mysql80/bin:$PATH" wp post list --post_type=placy_google_point

# Get post meta
PATH="/Applications/MAMP/Library/bin/mysql80/bin:$PATH" wp post meta list [POST_ID]

# Query database directly
PATH="/Applications/MAMP/Library/bin/mysql80/bin:$PATH" wp db query "SELECT * FROM wp_posts WHERE post_type='project'"

# List all post types with counts
PATH="/Applications/MAMP/Library/bin/mysql80/bin:$PATH" wp post-type list --fields=name,label,count

# Export/import
PATH="/Applications/MAMP/Library/bin/mysql80/bin:$PATH" wp db export backup.sql
PATH="/Applications/MAMP/Library/bin/mysql80/bin:$PATH" wp db import backup.sql
```

### Current Database Content

| Post Type | Count | Description |
|-----------|-------|-------------|
| `placy_google_point` | 30 | Google Places POIs |
| `placy_native_point` | 23 | Native POIs |
| `theme-story` | 6 | Theme stories |
| `project` | 4 | Projects |
| `customer` | 3 | Customers |

---

## Project-Specific Notes

### Deployment Target
[Add your deployment target here - e.g., Vercel, AWS, Netlify, etc.]

### What Gets Deployed?

| Folder/file | Deployed? | Notes |
|-------------|-----------|-------|
| `src/` | Yes | Source code |
| `dist/` | Yes | Compiled assets |
| `node_modules/` | No | Installed on deploy |
| `.env` | No | Environment-specific |

### Development Commands

```bash
# Add common commands for your project
npm run dev      # Start development server
npm run build    # Build for production
npm run test     # Run tests
npm run lint     # Lint code
```

---

**This file is located at:** `_claude/instructions.md`