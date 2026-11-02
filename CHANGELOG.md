# Changelog

Technical changes for developers. The user-facing changelog is in `readme.txt`.

## 1.0.0

- Attachment meta `_aiid_generated`, `_aiid_position`, `_aiid_source` with fields in the media modal and edit screen, a list view column and two bulk actions.
- Settings on Settings > Media: `aiid_label`, `aiid_position`, and the toggles `aiid_detect`, `aiid_filename`, `aiid_alt` stored as "1"/"0", because `update_option(false)` on a missing option stores nothing.
- `Detection\Provenance`: IPTC Digital Source Type in XMP packets and C2PA manifests (JUMBF `jumd` labelled `c2pa`), reading the first 2 MB and the last 256 KB of the file.
- `Frontend\Badge`: `render_block` for `core/image`, `core/cover`, `core/media-text`, `core/post-featured-image` with `WP_HTML_Tag_Processor`; skipped in admin and REST requests.
- Captioned images (`aiid--captioned`): the figure becomes a one-column grid, image and badge share the first cell, so the bottom corners sit on the image and not on the caption. `figcaption { contain: inline-size }` keeps a long caption from widening the column; aligned figures use `width: fit-content` in place of Core's `display: table`.
- The badge is the first child of the framing element, so it sits beside a link around the image rather than inside it.
- `bin/integration-check.sh`: end-to-end checks in a throwaway WordPress with SQLite.
