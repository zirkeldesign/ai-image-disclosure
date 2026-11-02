# Agent Guidelines

## Git Commits

- **Always use gitmoji** for commit messages
- Follow the gitmoji convention: start commit messages with an appropriate emoji
- Common gitmojis:
  - ✨ `:sparkles:` - New feature
  - 🐛 `:bug:` - Bug fix
  - 📝 `:memo:` - Documentation
  - 🚀 `:rocket:` - Deployment
  - 🔧 `:wrench:` - Configuration files
  - ♻️ `:recycle:` - Refactoring
  - 🎨 `:art:` - Code style/formatting
  - ⚡️ `:zap:` - Performance improvements
  - 🔥 `:fire:` - Remove code/files
  - ✅ `:white_check_mark:` - Tests

## Quality gate

```sh
composer format:test && composer phpstan && composer test
composer pcp                         # Plugin Check on the BUILT zip, throwaway WP
WP_VERSION=7.1-RC3 composer pcp      # ... against a specific core
```

`composer test` must print a test count. Silence plus exit 0 means the suite
never ran — every class in `src/` exits when `ABSPATH` is undefined, so
`tests/bootstrap.php` has to define it before anything autoloads.

## Surfaces to smoke test

Static checks never execute the plugin. After any change to the field editor or
validation, and on every WordPress core release, check with Gravity Forms active:

| Surface | What to confirm |
| --- | --- |
| Field settings panel (form editor) | the Regex Validation settings render for Text, Name and Address fields |
| Advanced Mode (Name / Address) | per-input rules save and reload |
| Preset dropdown | suggestions match the field type |
| Front-end submit | a violating value is rejected server-side, not just by the browser |
| Front-end JS validation | the client-side pattern fires before submit |

Gravity Forms is commercial and cannot be downloaded in a throwaway install —
reuse a licensed copy already on disk, e.g. the one under
`../privacy-captcha-for-cap/.wp-integration/wp-content/plugins/gravityforms`.
