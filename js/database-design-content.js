'use strict';

/**
 * database-design-content.js
 * Renders markdown documentation with anchor link navigation
 */
(function() {
    /**
     * Generate URL-safe slug from heading text
     * @param {string} text - The heading text to convert
     * @returns {string} URL-safe slug
     */
    function generateSlug(text) {
        return text
            .toLowerCase()
            .replace(/<[^>]*>/g, '')      // Remove HTML tags
            .replace(/&amp;/g, '')         // Remove &amp; entities
            .replace(/[^\w\s-]/g, '')      // Remove special chars except hyphens
            .replace(/\s+/g, '-')          // Replace spaces with hyphens
            .trim();
    }

    /**
     * Custom renderer for marked.js to generate heading IDs
     */
    const renderer = new marked.Renderer();
    renderer.heading = function(text, level) {
        // Handle both old and new marked.js API
        const headingText = typeof text === 'object' ? text.text : text;
        const headingLevel = typeof text === 'object' ? text.depth : level;
        const slug = generateSlug(headingText);

        return `<h${headingLevel} id="${slug}">${headingText}</h${headingLevel}>`;
    };

    marked.setOptions({ renderer: renderer });

    /**
     * Fetch and render the markdown file
     */
    function loadMarkdown() {
        const contentElement = document.getElementById('markdown-content');

        fetch('/NeighborhoodTools.md')
            .then(function(response) {
                if (!response.ok) {
                    throw new Error(`HTTP error: ${response.status}`);
                }
                return response.text();
            })
            .then(function(markdown) {
                contentElement.innerHTML = marked.parse(markdown);
            })
            .catch(function(error) {
                contentElement.innerHTML = `<p>Error loading documentation: ${error.message}</p>`;
                console.error('Failed to load markdown:', error);
            });
    }

    /**
     * Handle anchor link clicks for smooth scrolling
     * @param {Event} event - The click event
     */
    function handleAnchorClick(event) {
        const link = event.target.closest('a');

        if (!link) {
            return;
        }

        const href = link.getAttribute('href');

        if (href && href.charAt(0) === '#') {
            event.preventDefault();
            event.stopPropagation();

            const targetId = href.substring(1);
            const targetElement = document.getElementById(targetId);

            if (targetElement) {
                targetElement.scrollIntoView({ behavior: 'smooth' });
            }
        }
    }

    // Initialize
    loadMarkdown();
    document.addEventListener('click', handleAnchorClick, true);
})();
