/**
 * Chapter Navigation - Sticky sidebar navigation for Tema Story
 *
 * Features:
 * - Auto-generates navigation from chapter-wrapper blocks
 * - Smooth scroll to anchors
 * - Active state based on scroll position
 * - Intersection Observer for active tracking
 *
 * @package Placy
 * @since 1.0.0
 */

(function() {
    'use strict';

    // Configuration
    const CONFIG = {
        NAV_THRESHOLD: 0.3,  // 30% visibility triggers active state
        SCROLL_OFFSET: 80    // Offset for scroll positioning (px) - accounts for sticky nav
    };

    // Guard against multiple initializations
    let isInitialized = false;

    /**
     * Initialize chapter navigation
     */
    function initChapterNav() {
        console.log('[ChapterNav] Initializing...');
        
        // Prevent double initialization
        if (isInitialized) {
            console.log('[ChapterNav] Already initialized, skipping');
            return;
        }

        const navContainer = document.getElementById('chapter-nav');
        const introNavContainer = document.getElementById('intro-chapter-nav');
        const stickyTocNav = document.getElementById('sticky-toc-nav');
        const chapterCardsContainer = document.getElementById('story-chapter-cards');

        console.log('[ChapterNav] Found containers:', {
            navContainer: !!navContainer,
            introNavContainer: !!introNavContainer,
            stickyTocNav: !!stickyTocNav,
            chapterCardsContainer: !!chapterCardsContainer
        });

        if (!navContainer && !introNavContainer && !stickyTocNav && !chapterCardsContainer) {
            console.log('[ChapterNav] No nav containers found, exiting');
            return;
        }

        // Find all chapter sections
        const chapters = document.querySelectorAll('.chapter');
        console.log('[ChapterNav] Found chapters:', chapters.length);

        if (chapters.length === 0) {
            console.log('[ChapterNav] No chapters found, exiting');
            return;
        }

        isInitialized = true;

        // Build navigation menu for all locations
        if (navContainer) {
            buildNavMenu(chapters, navContainer);
            initScrollTracking(chapters, navContainer);
        }

        if (introNavContainer) {
            buildNavMenu(chapters, introNavContainer);
            initScrollTracking(chapters, introNavContainer);
        }

        if (stickyTocNav) {
            buildStickyTocNav(chapters, stickyTocNav);
            initScrollTracking(chapters, stickyTocNav);
        }

        if (chapterCardsContainer) {
            buildChapterCards(chapters, chapterCardsContainer);
        }

    }

    /**
     * Build chapter cards for foreword section
     * @param {NodeList} chapters - Chapter sections
     * @param {HTMLElement} container - Chapter cards container
     */
    function buildChapterCards(chapters, container) {
        container.innerHTML = '';

        // Font Awesome icons for chapters (cycling through a set)
        const chapterIcons = [
            'fa-map-marker-alt',
            'fa-route',
            'fa-building',
            'fa-tree',
            'fa-utensils',
            'fa-shopping-bag',
            'fa-landmark',
            'fa-coffee',
            'fa-bus',
            'fa-home'
        ];

        chapters.forEach(function(chapter, index) {
            // Get anchor
            let anchor = chapter.getAttribute('data-chapter-anchor') || chapter.id;
            if (!anchor) {
                const chapterId = chapter.getAttribute('data-chapter-id');
                anchor = chapterId || 'chapter-' + (index + 1);
                chapter.id = anchor;
                chapter.setAttribute('data-chapter-anchor', anchor);
            }

            // Get title
            const titleAttr = chapter.getAttribute('data-chapter-title');
            const title = (titleAttr && titleAttr.trim() !== '') 
                ? titleAttr 
                : 'Kapittel ' + (index + 1);

            // Create card element
            const card = document.createElement('a');
            card.href = '#' + anchor;
            card.className = 'story-chapter-card';
            card.setAttribute('data-chapter-anchor', anchor);

            // Icon
            const iconWrapper = document.createElement('div');
            iconWrapper.className = 'story-chapter-card-icon';
            const icon = document.createElement('i');
            icon.className = 'fas ' + chapterIcons[index % chapterIcons.length];
            iconWrapper.appendChild(icon);

            // Content
            const content = document.createElement('div');
            content.className = 'story-chapter-card-content';

            const numberSpan = document.createElement('span');
            numberSpan.className = 'story-chapter-card-number';
            numberSpan.textContent = 'Kapittel ' + (index + 1);

            const titleSpan = document.createElement('span');
            titleSpan.className = 'story-chapter-card-title';
            titleSpan.textContent = title;

            content.appendChild(numberSpan);
            content.appendChild(titleSpan);

            card.appendChild(iconWrapper);
            card.appendChild(content);

            // Smooth scroll on click
            card.addEventListener('click', function(e) {
                e.preventDefault();
                scrollToChapter(anchor);
            });

            container.appendChild(card);
        });

        console.log('[ChapterNav] Built', chapters.length, 'chapter cards');
    }

    /**
     * Build sticky TOC navigation bar from chapters
     * @param {NodeList} chapters - Chapter sections
     * @param {HTMLElement} navContainer - Sticky TOC nav container
     */
    function buildStickyTocNav(chapters, navContainer) {
        const innerContainer = navContainer.querySelector('.sticky-toc-inner');
        if (!innerContainer) return;

        innerContainer.innerHTML = '';

        chapters.forEach(function(chapter, index) {
            // Get anchor
            let anchor = chapter.getAttribute('data-chapter-anchor') || chapter.id;
            if (!anchor) {
                const chapterId = chapter.getAttribute('data-chapter-id');
                anchor = chapterId || 'chapter-' + (index + 1);
                chapter.id = anchor;
                chapter.setAttribute('data-chapter-anchor', anchor);
            }

            // Get nav label (short), fallback to title, then generic
            const navLabel = chapter.getAttribute('data-chapter-nav-label');
            const titleAttr = chapter.getAttribute('data-chapter-title');
            const label = (navLabel && navLabel.trim() !== '') 
                ? navLabel 
                : (titleAttr && titleAttr.trim() !== '') 
                    ? titleAttr 
                    : 'Kapittel ' + (index + 1);

            // Create pill element
            const pill = document.createElement('a');
            pill.href = '#' + anchor;
            pill.className = 'toc-pill';
            pill.setAttribute('data-chapter-anchor', anchor);

            // Number badge
            const numberBadge = document.createElement('span');
            numberBadge.className = 'toc-pill-number';
            numberBadge.textContent = (index + 1);

            // Label
            const labelSpan = document.createElement('span');
            labelSpan.textContent = label;

            pill.appendChild(numberBadge);
            pill.appendChild(labelSpan);

            // Smooth scroll on click
            pill.addEventListener('click', function(e) {
                e.preventDefault();
                scrollToChapter(anchor);
            });

            innerContainer.appendChild(pill);
        });
    }

    /**
     * Build navigation menu from chapters
     * @param {NodeList} chapters - Chapter sections
     * @param {HTMLElement} navContainer - Navigation container
     */
    function buildNavMenu(chapters, navContainer) {
        navContainer.innerHTML = '';
        const isIntroNav = navContainer.id === 'intro-chapter-nav';

        chapters.forEach(function(chapter, index) {
            // Try to get anchor from multiple sources
            let anchor = chapter.getAttribute('data-chapter-anchor') || chapter.id;

            // If no anchor, try to get chapter-id and use that
            if (!anchor) {
                const chapterId = chapter.getAttribute('data-chapter-id');
                if (chapterId) {
                    // Generate anchor from chapter-id and set it
                    anchor = chapterId;
                    chapter.id = anchor;
                    chapter.setAttribute('data-chapter-anchor', anchor);
                } else {
                    // Last resort: generate from index
                    anchor = 'chapter-' + (index + 1);
                    chapter.id = anchor;
                    chapter.setAttribute('data-chapter-anchor', anchor);
                }
            }

            // Get title from data attribute, fallback to generic if empty or missing
            const titleAttr = chapter.getAttribute('data-chapter-title');
            const title = (titleAttr && titleAttr.trim() !== '') ? titleAttr : 'Kapittel ' + (index + 1);

            // Count POI items in this chapter
            const poiItems = chapter.querySelectorAll('.poi-list-item');
            const poiCount = poiItems.length;

            // Create navigation item
            const navItem = document.createElement('a');
            navItem.href = '#' + anchor;
            navItem.className = 'chapter-nav-item';
            navItem.setAttribute('data-chapter-anchor', anchor);

            // For intro nav, add title and count as separate elements
            if (isIntroNav) {
                const titleSpan = document.createElement('span');
                titleSpan.textContent = title;
                navItem.appendChild(titleSpan);

                if (poiCount > 0) {
                    const countSpan = document.createElement('span');
                    countSpan.className = 'poi-count';
                    countSpan.textContent = poiCount + ' steder';
                    navItem.appendChild(countSpan);
                }
            } else {
                // For sidebar nav, keep simple text
                navItem.textContent = title;
            }

            // Smooth scroll on click
            navItem.addEventListener('click', function(e) {
                e.preventDefault();
                scrollToChapter(anchor);
            });

            navContainer.appendChild(navItem);
        });
    }

    /**
     * Scroll to chapter with smooth behavior
     * @param {string} anchor - Chapter anchor ID
     */
    function scrollToChapter(anchor) {
        const chapter = document.getElementById(anchor);

        if (!chapter) {
            return;
        }

        // Get chapter position relative to page top
        const chapterTop = chapter.getBoundingClientRect().top + window.pageYOffset;

        // Scroll window to chapter with offset
        window.scrollTo({
            top: chapterTop - CONFIG.SCROLL_OFFSET,
            behavior: 'smooth'
        });
    }

    /**
     * Initialize scroll tracking with Intersection Observer
     * @param {NodeList} chapters - Chapter sections
     * @param {HTMLElement} navContainer - Navigation container
     */
    function initScrollTracking(chapters, navContainer) {
        const observerOptions = {
            root: null, // null means viewport
            rootMargin: '-10% 0px -70% 0px',  // Trigger when chapter enters top 20% of viewport
            threshold: [0, 0.1, 0.2, 0.3]     // Multiple thresholds for better tracking
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const anchor = entry.target.getAttribute('data-chapter-anchor') || entry.target.id;
                    setActiveNavItem(anchor, navContainer);
                }
            });
        }, observerOptions);

        // Observe all chapters
        chapters.forEach(function(chapter) {
            observer.observe(chapter);
        });
    }

    /**
     * Set active navigation item
     * @param {string} anchor - Active chapter anchor
     * @param {HTMLElement} navContainer - Navigation container
     */
    function setActiveNavItem(anchor, navContainer) {

        // Remove active class from all items (both chapter-nav-item and toc-pill)
        const navItems = navContainer.querySelectorAll('.chapter-nav-item, .toc-pill');
        navItems.forEach(function(item) {
            item.classList.remove('active');
        });

        // Add active class to current item
        const activeItem = navContainer.querySelector('[data-chapter-anchor="' + anchor + '"]');

        if (activeItem) {
            activeItem.classList.add('active');
        }
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initChapterNav);
    } else {
        initChapterNav();
    }

})();
