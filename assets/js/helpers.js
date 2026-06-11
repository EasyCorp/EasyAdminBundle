export function toggleVisibilityClasses(element, removeVisibility) {
    if (removeVisibility) {
        element.classList.remove('d-block');
        element.classList.add('d-none');
    } else {
        element.classList.remove('d-none');
        element.classList.add('d-block');
    }
}

// Validates a URL read from the DOM before using it to navigate or fetch. It only
// allows safe schemes and rejects dangerous ones (e.g. "javascript:" or "data:") to
// prevent DOM-based XSS. When requireSameOrigin is true, cross-origin URLs are also
// rejected; this is required for flows that fetch a URL and inject the response into
// the page, so that attacker-controlled remote content can never be loaded. Returns
// the original URL when it's safe or null otherwise.
export function sanitizeUrl(url, requireSameOrigin = false) {
    if (null === url || '' === url) {
        return null;
    }

    try {
        // relative URLs are resolved against the current origin; absolute URLs keep their own scheme
        const parsedUrl = new URL(url, window.location.origin);
        const allowedProtocols = ['http:', 'https:', 'mailto:', 'tel:'];
        if (!allowedProtocols.includes(parsedUrl.protocol)) {
            return null;
        }

        if (requireSameOrigin && parsedUrl.origin !== window.location.origin) {
            return null;
        }

        return url;
    } catch {
        return null;
    }
}
