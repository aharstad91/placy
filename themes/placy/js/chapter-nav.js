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
        SCROLL_OFFSET: 32    // Offset for scroll positioning (px)
    };

    // Guard against multiple initializations
    let isInitialized = false;

    /**
     * Initialize chapter navigation
     */
    function initChapterNav() {
        // Prevent double initialization
        if (isInitialized) {
            return;
        }

        const navContainer = document.getElementById('chapter-nav');
        const introNavContainer = document.getElementById('intro-chapter-nav');

        if (!navContainer && !introNavContainer) {
            return;
        }

        // Find all chapter sections
        const chapters = document.querySelectorAll('.chapter');

        if (chapters.length === 0) {
            return;
        }

        isInitialized = true;

        // Build navigation menu for both locations
        if (navContainer) {
            buildNavMenu(chapters, navContainer);
            // Initialize scroll tracking
            initScrollTracking(chapters, navContainer);
        }

        if (introNavContainer) {
            buildNavMenu(chapters, introNavContainer);
            // Initialize scroll tracking for intro nav too
            initScrollTracking(chapters, introNavContainer);
        }

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

        // Remove active class from all items
        const navItems = navContainer.querySelectorAll('.chapter-nav-item');
        navItems.forEach(function(item) {
            item.classList.remove('active');
        });

        // Add active class to current item
        const activeItem = navContainer.querySelector('[data-chapter-anchor="' + anchor + '"]');

        if (activeItem) {
            activeItem.classList.add('active');
        } else {
            console.warn('Chapter Navigation: Nav item not found for anchor:', anchor);
        }
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initChapterNav);
    } else {
        initChapterNav();
    }

})();
