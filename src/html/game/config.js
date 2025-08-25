// PalStory Configuration
const PALSTORY_CONFIG = {
    IMAGE_BASE_URL: "https://palplanner.com/story/uploads/"
};

// Helper function to get full image URL
function getImageUrl(imagePath) {
    // Normalize input
    if (typeof imagePath !== 'string') {
        imagePath = String(imagePath || '');
    }
    var p = imagePath.trim();

    if (!p) return '';

    // Passthrough absolute or protocol-relative URLs
    // e.g., https://..., http://..., //cdn.example.com/img.png
    if (/^(https?:)?\/\//i.test(p)) {
        return p;
    }

    // Remove leading slashes
    p = p.replace(/^\/+/, '');
    // Remove existing story/uploads prefix (case-insensitive)
    p = p.replace(/^(story\/)?uploads\//i, '');

    // Ensure single slash when joining with base
    var base = PALSTORY_CONFIG.IMAGE_BASE_URL.replace(/\/+$|$/, '/');
    return base + p;
}
