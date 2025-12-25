# WordPress.org Plugin Assets

Place your plugin assets here. These will be uploaded to the WordPress.org plugin page.

## Automated Screenshot Generation

Generate screenshots automatically from your running WordPress test environment:

```bash
# 1. Start the test environment
cd test
./start.sh

# 2. Generate screenshots
./generate-screenshots.sh
```

This will capture:
- `screenshot-1.png` - Admin Dashboard
- `screenshot-2.png` - Add Event Dialog
- `screenshot-3.png` - Calendar Date Picker
- `screenshot-4.png` - Frontend Agenda Display
- `screenshot-5.png` - Settings Page

## Required/Recommended Files

| File | Size | Description |
|------|------|-------------|
| `banner-772x250.png` | 772×250px | Plugin banner (required) |
| `banner-1544x500.png` | 1544×500px | High-DPI banner (recommended) |
| `icon-128x128.png` | 128×128px | Plugin icon (required) |
| `icon-256x256.png` | 256×256px | High-DPI icon (recommended) |
| `screenshot-1.png` | any | First screenshot |
| `screenshot-2.png` | any | Second screenshot |

## Screenshot Naming

Screenshots are matched to descriptions in `readme.txt`:

```
== Screenshots ==

1. This describes screenshot-1.png
2. This describes screenshot-2.png
```

## Tips

- Use PNG format for best quality
- Keep file sizes reasonable (< 1MB each)
- Banners should be visually appealing and represent your plugin
- Icons work best with simple, recognizable designs
