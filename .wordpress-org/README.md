# WordPress.org plugin assets

Committed to the SVN `assets/` folder, not part of the plugin zip.

| File | Use |
| --- | --- |
| `icon.svg`, `icon-128x128.png`, `icon-256x256.png` | Plugin icon: an image frame with an "AI" label in the corner |
| `banner-772x250.svg/.png`, `banner-1544x500.svg/.png` | Directory banner |
| `screenshot-1.png` | The label on a cover block (Twenty Twenty-Five) |
| `screenshot-2.png` | Checkbox, detected source and position on the image's edit screen |
| `screenshot-3.png` | The section on Settings > Media |

Colours: background `#1f2a44` to `#3b2a5a`, accent `#ffb36b` to `#ff6f61`, label `#11151f` at 82 %.

PNGs are rendered from the SVGs with `rsvg-convert`:

```sh
rsvg-convert -w 256 -h 256 icon.svg -o icon-256x256.png
rsvg-convert -w 128 -h 128 icon.svg -o icon-128x128.png
rsvg-convert -w 772 -h 250 banner-772x250.svg -o banner-772x250.png
rsvg-convert -w 1544 -h 500 banner-1544x500.svg -o banner-1544x500.png
```

Screenshots come from a throwaway WordPress 7.1 with Twenty Twenty-Five, captured with headless Chrome at 1280 px.
