# WordPress Gutenberg Security & Code Quality Audit

## Context
We experienced critical data loss (3 times) due to incorrect Gutenberg block implementation. Content from Custom Post Types was deleted when blocks were migrated by WordPress. This audit combines security/data integrity checks with general code quality improvements.

## Root Cause Identified
The `save()` function in `blocks/chapter-wrapper/block.js` returned `null` instead of `el(InnerBlocks.Content)`. When WordPress detected a mismatch between saved content and current save() output, it triggered a migration. The `migrate()` function only returned `attributes`, not `innerBlocks`, causing all inner content to be deleted.

## Audit Scope
1. **Critical Security Issues** - Preventing data loss
2. **Code Quality** - Maintainability, performance, best practices
3. **Architecture** - Patterns, consistency, scalability

## Audit Tasks

### 1. Block save() Functions
**Files to check:** All `blocks/*/block.js` files

**What to look for:**
```javascript
// DANGEROUS - Will cause data loss if block structure changes
save: function() {
    return null;
}

// SAFE - Preserves inner blocks
save: function() {
    return el(InnerBlocks.Content);
}
```

**Critical rule:** Any block using `InnerBlocks` in edit() MUST return `el(InnerBlocks.Content)` in save(), even when using server-side rendering via render.php.

### 2. Deprecated Block Definitions
**Files to check:** All `deprecated:` arrays in block.js files

**What to look for:**
```javascript
// DANGEROUS - Only preserves attributes
migrate: function(attributes) {
    return attributes;
}

// SAFE - Preserves both attributes AND inner blocks
migrate: function(attributes, innerBlocks) {
    return {
        attributes: attributes,
        innerBlocks: innerBlocks
    };
}
```

**Note:** Check if deprecated save() functions are consistent with current save(). Mismatches trigger migrations.

### 3. DOMDocument Operations
**Files to check:** All `blocks/*/render.php` files

**What to look for:**
- Any use of `DOMDocument`, `loadHTML()`, or `str_replace()` on `$content`
- Operations that remove nodes from $content before echoing
- Conditional logic that might result in empty $content output

**Critical rule:** render.php should ONLY read and display content, never modify it in ways that could result in data loss.

### 4. Custom Post Type Configuration
**Files to check:** `inc/post-types.php`

**What to look for:**
```php
// Check if 'revisions' is in supports array
'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'revisions'),
```

**All CPTs using Gutenberg editor must have revision support** to allow rollback after accidental data loss.

### 5. Template and TemplateLock
**Files to check:** All block.js files with TEMPLATE definitions

**What to look for:**
```javascript
// POTENTIALLY DANGEROUS - content: '' might override existing content
const TEMPLATE = [
    ['core/heading', { level: 2, content: '' }],
];

// SAFER - No content defaults
const TEMPLATE = [
    ['core/heading', { level: 2, placeholder: 'Add heading...' }],
];

// DANGEROUS - Can prevent users from managing content
templateLock: 'all',  // Blocks ALL modifications
templateLock: 'insert', // May trigger migrations when structure changes

// SAFE - Allows full user control
templateLock: false,
```

### 6. Automatic Content Manipulation
**Search patterns:**
- `add_action('save_post'` - Check if any hooks modify post_content
- `wp.data.dispatch('core/editor').editPost` - Check for automatic edits
- `setAttributes` calls that modify content
- REST API endpoint registrations that modify posts

**Files to check:**
- `functions.php`
- All files in `inc/`
- All JavaScript files

### 7. ACF Field Interactions
**What to check:**
- Do any ACF fields use `update_value` filters that could affect post_content?
- Are there any ACF blocks that programmatically modify their own content?

## Test Procedure for Safety

Before making any structural changes to blocks:

1. **Export database backup**
2. **Create test post with sample content**
3. **Make code changes on development branch**
4. **Open test post in editor** - Watch for migration prompts
5. **Save without making edits** - Verify content remains intact
6. **Check frontend rendering** - Verify nothing disappeared
7. **Check database** - Compare post_content before/after
8. **Only then deploy to production**

## Questions for Opus to Answer

1. Are there any other blocks besides `chapter-wrapper` that use InnerBlocks with unsafe save() functions?

2. Are there any deprecated block definitions with incomplete migrate() functions?

3. Is there any PHP/JavaScript code that automatically modifies post_content in ways that could cause data loss?

4. Are all custom post types that use Gutenberg editor properly configured with revision support?

5. Are there any templateLock configurations that could cause issues when blocks are updated?

6. Should we implement additional safeguards like:
   - Pre-save validation hooks?
   - Automatic database backups before migrations?
   - Warning prompts when block structure changes detected?

7. Are there any ACF-specific patterns that could interact poorly with Gutenberg blocks?

## Code Quality Areas to Review

### 8. JavaScript Code Quality
**Files to check:** All `.js` files in `/js/` and `/blocks/`

**Look for:**
- **ES5 vs Modern Syntax:** Are we using old ES5 patterns unnecessarily? Could arrow functions, const/let, template literals improve readability?
- **Error Handling:** Are there try/catch blocks around risky operations (DOM parsing, API calls, localStorage)?
- **Console Logs:** Any leftover `console.log()` statements that should be removed?
- **Magic Numbers/Strings:** Hard-coded values that should be constants (e.g., colors, IDs, API endpoints)
- **Code Duplication:** Repeated patterns that could be extracted to utility functions
- **Performance:** Are there expensive operations in loops or event handlers?
- **Memory Leaks:** Event listeners that aren't cleaned up, global variables that accumulate

### 9. PHP Code Quality
**Files to check:** All `.php` files in `/inc/`, `/blocks/`, theme root

**Look for:**
- **Security:** Proper escaping (`esc_attr()`, `esc_html()`, `wp_kses()`), nonce verification, capability checks
- **Error Handling:** Proper validation and error messages, no PHP warnings/notices
- **Performance:** Database queries in loops (N+1 problem), missing indexes, unused queries
- **WordPress Best Practices:** Using WP functions instead of raw PHP (e.g., `wp_remote_get()` vs `curl`)
- **Documentation:** PHPDoc blocks for functions, clear parameter types
- **Code Organization:** Functions too long (>50 lines), files too large (>500 lines)
- **Magic Values:** Hard-coded post IDs, taxonomy names, option names that should be constants

### 10. CSS Architecture
**Files to check:** All `.css` files, Tailwind config

**Look for:**
- **Specificity Issues:** Over-qualified selectors, !important usage
- **Duplication:** Repeated color/spacing values that should use CSS variables or Tailwind classes
- **Performance:** Expensive selectors, unused CSS
- **Naming Conventions:** Inconsistent class naming (BEM vs utility vs semantic)
- **Responsive Design:** Missing breakpoints, mobile-first approach
- **Accessibility:** Focus states, color contrast ratios

### 11. File Organization & Architecture
**Review:**
- **Naming Consistency:** Are files named logically? (`tema-story-map-multi.js` vs `temastorymapmulti.js`)
- **Folder Structure:** Are related files grouped together?
- **Separation of Concerns:** Is business logic mixed with presentation?
- **Dependencies:** Are there circular dependencies or tight coupling?
- **Dead Code:** Unused functions, commented-out code blocks, orphaned files
- **Documentation:** Is there a README explaining the architecture? Block usage guides?

### 12. Mapbox Integration Review
**Files to check:** `tema-story-map-multi.js`, map-related PHP

**Look for:**
- **API Key Management:** Is the Mapbox token properly secured? Not committed to git?
- **Error Handling:** What happens if Mapbox fails to load? Network errors?
- **Performance:** Are we creating too many map instances? Proper cleanup on unmount?
- **Memory Leaks:** Markers and layers properly removed when components unmount?
- **Accessibility:** Keyboard navigation, screen reader support for map interactions?
- **Code Organization:** Is the 1000+ line file split into logical sections? Could it be multiple modules?

### 13. ACF Integration Patterns
**Files to check:** `inc/acf-fields.php`, ACF block templates

**Look for:**
- **Field Group Organization:** Logical grouping, clear naming conventions
- **Performance:** Loading only needed fields, avoiding unnecessary queries
- **Validation:** Proper field validation on save, user-friendly error messages
- **Flexibility vs Structure:** Are fields too rigid or too loose?
- **Documentation:** Are field purposes clear? Usage examples for complex fields?

### 14. Accessibility (a11y)
**Check across all templates and blocks:**
- **Semantic HTML:** Proper heading hierarchy, landmark elements
- **ARIA Labels:** Buttons/links with meaningful labels, aria-expanded states
- **Keyboard Navigation:** Tab order, focus management, keyboard shortcuts
- **Screen Readers:** Alt text for images, descriptive link text
- **Color Contrast:** WCAG AA compliance (4.5:1 for normal text)
- **Form Accessibility:** Labels, error messages, required field indicators

### 15. Performance & Optimization
**Areas to review:**
- **Asset Loading:** Are scripts/styles minified? Properly enqueued with dependencies?
- **Lazy Loading:** Images, maps, heavy components loaded on demand?
- **Caching:** Transients for expensive operations? Object caching?
- **Database Queries:** Use of WP_Query best practices, proper pagination
- **Third-Party Scripts:** Mapbox, fonts - are they loaded optimally?

## Improvement Recommendations

After completing the audit, provide:

1. **Critical Issues** (Must fix immediately - data loss risk)
2. **High Priority** (Security vulnerabilities, major bugs)
3. **Medium Priority** (Performance issues, code quality)
4. **Low Priority** (Nice-to-have improvements, refactoring opportunities)
5. **Technical Debt** (Areas that need eventual refactoring)

For each issue found:
- **Location:** File path and line numbers
- **Problem:** What's wrong and why it matters
- **Risk Level:** Critical/High/Medium/Low
- **Solution:** Specific code changes or architectural recommendations
- **Effort Estimate:** Small (< 1hr), Medium (1-4hr), Large (> 4hr)

## Success Criteria

### Security & Data Integrity (Critical)
- ✅ All blocks using InnerBlocks have safe save() functions
- ✅ All deprecated definitions have proper migrate() functions
- ✅ No code automatically modifies post_content in unsafe ways
- ✅ All CPTs have revision support enabled
- ✅ No dangerous template or templateLock configurations
- ✅ Documentation exists for safe block development patterns
- ✅ Test procedure established for future block changes

### Code Quality (High Priority)
- ✅ No console.log statements in production code
- ✅ Consistent code style across JS/PHP/CSS
- ✅ Proper error handling in all critical paths
- ✅ No security vulnerabilities (XSS, SQL injection, CSRF)
- ✅ Performance bottlenecks identified and documented
- ✅ Accessibility issues catalogued with remediation plan

### Architecture (Medium Priority)
- ✅ Clear separation of concerns
- ✅ No circular dependencies or tight coupling
- ✅ Dead code identified for removal
- ✅ Documentation updated to reflect current architecture
- ✅ Consistent naming conventions throughout codebase

## Priority

**CRITICAL** - This is a data loss issue. Security audit (tasks 1-7) must be completed before any other block modifications. Code quality review (tasks 8-15) should follow immediately after.
