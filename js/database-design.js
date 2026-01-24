// database-design.php specific JavaScript
const iframeStyles = `
    body { font-family: system-ui, sans-serif; line-height: 1.7; padding: 1.5rem; margin: 0; color: #334155; }
    h1 { color: #065f46; border-bottom: 2px solid #065f46; padding-bottom: 0.5rem; }
    h2 { color: #065f46; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.3rem; margin-top: 2rem; }
    h3 { color: #1e3a5f; }
    h4 { color: #334155; }
    a { color: #065f46; }
    code { background: #f1f5f9; padding: 0.2rem 0.4rem; border-radius: 0.25rem; font-size: 0.9em; }
    pre { background: #1e293b; color: #e2e8f0; padding: 1rem; border-radius: 0.5rem; overflow-x: auto; }
    pre code { background: transparent; padding: 0; color: inherit; }
    blockquote { border-left: 4px solid #065f46; margin: 1rem 0; padding: 0.5rem 1rem; background: #f0fdf4; }
    table { width: 100%; border-collapse: collapse; margin: 1rem 0; font-size: 0.9rem; }
    th, td { border: 1px solid #e2e8f0; padding: 0.5rem 0.75rem; text-align: left; }
    th { background: #f8fafc; font-weight: 600; }
    tr:nth-child(even) { background: #f8fafc; }
    hr { border: none; border-top: 1px solid #e2e8f0; margin: 2rem 0; }
`;

// Custom renderer to generate heading IDs that match markdown anchor links
const renderer = new marked.Renderer();
renderer.heading = function(text, level) {
    // Handle both old and new marked.js API
    const headingText = typeof text === 'object' ? text.text : text;
    const headingLevel = typeof text === 'object' ? text.depth : level;

    // Generate slug: lowercase, replace spaces with hyphens, remove special chars except hyphens
    const slug = headingText
        .toLowerCase()
        .replace(/<[^>]*>/g, '')     // Remove HTML tags
        .replace(/&amp;/g, '')        // Remove &amp;
        .replace(/[^\w\s-]/g, '')     // Remove special chars except hyphens
        .replace(/\s+/g, '-')         // Replace spaces with hyphens
        .replace(/-+/g, '-')          // Replace multiple hyphens with single
        .trim();

    return `<h${headingLevel} id="${slug}">${headingText}</h${headingLevel}>`;
};

marked.setOptions({ renderer: renderer });

fetch('/NeighborhoodTools.md')
    .then(response => response.text())
    .then(markdown => {
        const html = `
            <!DOCTYPE html>
            <html>
            <head>
                <base target="_self">
                <style>${iframeStyles}</style>
            </head>
            <body>${marked.parse(markdown)}</body>
            <script>
                // Handle anchor link clicks to scroll within the iframe
                document.addEventListener('click', function(e) {
                    const link = e.target.closest('a');
                    if (link && link.getAttribute('href').startsWith('#')) {
                        e.preventDefault();
                        const targetId = link.getAttribute('href').slice(1);
                        const targetEl = document.getElementById(targetId);
                        if (targetEl) {
                            targetEl.scrollIntoView({ behavior: 'smooth' });
                        }
                    }
                });
            </script>
            </html>
        `;
        document.getElementById('markdown-frame').srcdoc = html;
    });
