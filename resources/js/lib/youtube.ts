export function extractYoutubeId(url: string | null | undefined): string | null {
    if (!url) return null;
    const patterns = [
        /(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/|youtube\.com\/shorts\/)([A-Za-z0-9_-]{11})/,
    ];
    for (const pattern of patterns) {
        const match = url.match(pattern);
        if (match) return match[1];
    }
    return null;
}

export function youtubeThumbnail(url: string | null | undefined, quality: 'default' | 'hqdefault' | 'mqdefault' = 'hqdefault'): string | null {
    const id = extractYoutubeId(url);
    if (!id) return null;
    return `https://img.youtube.com/vi/${id}/${quality}.jpg`;
}

export function youtubeEmbedUrl(url: string | null | undefined): string | null {
    const id = extractYoutubeId(url);
    if (!id) return null;
    return `https://www.youtube.com/embed/${id}`;
}

export function isYoutubeUrl(url: string | null | undefined): boolean {
    return extractYoutubeId(url) !== null;
}
