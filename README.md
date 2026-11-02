# AI Image Disclosure

Labels AI-generated images in WordPress with a small, accessible badge. CSS only, no frontend JavaScript; recognises AI images from IPTC and C2PA metadata on upload. The wordpress.org description is in [readme.txt](readme.txt).

## How it works

- **Marking** lives on the attachment (`_aiid_generated`), so every place the image is used follows it. `_aiid_position` is stored only when a corner is picked; everything else follows the site default (`aiid_position`). `_aiid_source` records where the marking came from: `manual`, `iptc`, `c2pa`, `filename`.
- **Detection** (`src/Detection/Provenance.php`) looks for the IPTC Digital Source Type `trainedAlgorithmicMedia` or `compositeWithTrainedAlgorithmicMedia`, either in an XMP packet or inside a C2PA manifest. Plain `algorithmicMedia` and camera captures do not count.
- **Output** (`src/Frontend/Badge.php`) changes only the rendered block: classes `has-aiid-badge aiid--<corner>` on the element framing the image, an `aria-hidden` badge before the image, and the label appended to a non-empty alt text. Saved content is never touched.
- **Placement** is CSS only (`assets/css/badge.css`): `inset: var(--aiid-inset)` inside the frame, `--aiid-gap` for the distance to the edge, custom properties for the look.

Not covered on purpose: images a theme prints outside of blocks, page builder backgrounds, writing metadata into files.

## Development

```sh
composer install
composer test          # Pest, pure logic
composer phpstan       # level 6
composer format:test   # Pint
composer integration   # end-to-end in a throwaway WordPress (SQLite)
composer pcp           # Plugin Check on the built zip
bun run translate      # .pot, update .po, compile .mo
```

Commits follow gitmoji, see [AGENTS.md](AGENTS.md).
