# Video download via yt-dlp instead of ScrapeCreators download_media

ScrapeCreators charges 10 credits per video when `download_media=true` is set. With 100 free credits and 10 creators × 3 videos = 30 videos, enabling that flag would cost 300 credits — 3× the budget. Instead, we fetch video metadata only (1 credit/call, ~30 credits total for 10 creators) and pass the TikTok page URL to `yt-dlp` for the actual download. yt-dlp handles TikTok CDN resolution natively and is invoked as a subprocess from PHP, consistent with how Whisper and ffmpeg are called.

## Consequences

TikTok occasionally rotates CDN URLs or introduces bot-detection that breaks yt-dlp. If yt-dlp begins failing at demo time, the fallback is to enable `download_media=true` for the affected creator and accept the credit cost for that one handle.
