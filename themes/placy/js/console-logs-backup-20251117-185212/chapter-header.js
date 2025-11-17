/**
 * Chapter Header Extractor
 * 
 * Moves heading and paragraph blocks from chapter-content to chapter-header
 * to display them above the 2-column layout
 * 
 * @package Placy
 * @since 1.0.0
 */

(function() {
    'use strict';

    /**
     * Extract and move chapter headers
     */
    function extractChapterHeaders() {
        const chapters = document.querySelectorAll('.chapter-with-map');
        
        chapters.forEach(function(chapter) {
            const chapterContent = chapter.querySelector('.chapter-content');
            const chapterGrid = chapter.querySelector('.chapter-grid');
            
            if (!chapterContent || !chapterGrid) return;
            
            // Find first heading and paragraph (if they exist at the start)
            const firstChild = chapterContent.firstElementChild;
            const secondChild = firstChild ? firstChild.nextElementSibling : null;
            
            // Check if we have heading and/or paragraph to extract
            const hasHeading = firstChild && (firstChild.tagName === 'H2' || firstChild.tagName === 'H3' || firstChild.classList.contains('wp-block-heading'));
            const hasParagraph = secondChild && (secondChild.tagName === 'P' || secondChild.classList.contains('wp-block-paragraph'));
            
            if (!hasHeading && !hasParagraph) return;
            
            // Create header container
            const headerContainer = document.createElement('div');
            headerContainer.className = 'chapter-header';
            
            // Move heading
            if (hasHeading) {
                headerContainer.appendChild(firstChild);
            }
            
            // Move paragraph (if it exists right after heading)
            if (hasParagraph) {
                headerContainer.appendChild(secondChild);
            }
            
            // Insert header before grid
            chapter.insertBefore(headerContainer, chapterGrid);
        });
        
        console.log('Chapter Headers: Extracted and moved headers above grid');
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', extractChapterHeaders);
    } else {
        extractChapterHeaders();
    }

})();
