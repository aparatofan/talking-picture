# TBT Talking Picture — Claude Code guidance

Keep this file concise. It is loaded at the start of every Claude Code session.

## Start here

- Work from the task rather than scanning the whole repository.
- Check `git status`, use targeted search, and read only the relevant sections/files.
- Main bootstrap is `talking-picture.php`; implementation is split across `includes/` and `assets/`.
- Do not change unrelated marker behavior, stored meta, shortcode output, styling, or copy.
- Inspect the final diff before finishing.

## Project basics

- WordPress plugin for The Blue Tree.
- Purpose: create reusable interactive images with microphone markers/tooltips and embed them by shortcode.
- Main file: `talking-picture.php`.
- Core areas: post type, metadata, admin builder, and shortcode rendering under `includes/`; frontend/admin assets under `assets/`.
- Media Library integration is part of the authoring flow.
- The plugin registers itself with TBT Hub but must continue to function independently.

## Behavior to preserve

- A Talking Picture is a reusable WordPress object; existing IDs, saved marker positions, labels/tooltips, and shortcode usage are compatibility surfaces.
- Do not change stored metadata shape or coordinate semantics without an explicit migration task.
- Preserve marker positioning across the builder and frontend renderer.
- Keep the bundled inline microphone SVG self-contained; do not introduce an external icon/CDN dependency for a cosmetic change.
- Keep authoring/admin logic separate from public rendering.

## Security and WordPress rules

- Preserve nonce/capability checks on admin writes.
- Sanitize stored values and escape rendered output for its context.
- Treat Media Library selections and author-entered tooltip text as untrusted input.
- Never commit credentials, FTP details, API keys, or local configuration.

## Coding style

- Follow the surrounding WordPress/PHP and vanilla JS/CSS style.
- Prefer small local changes and reuse existing hooks, helpers, selectors, and asset handles.
- Do not reformat unrelated files or add a framework/build tool for a focused task.
- Keep comments that explain compatibility or coordinate/layout decisions.

## Validation

Run `php -l <changed-file>` for changed PHP files.

Run `node --check <changed-file>` for changed JavaScript files when Node is available.

For marker/positioning changes, verify both the admin builder and public shortcode output. Image scaling, drag/placement, tooltip positioning, and Divi/theme interaction require a real browser check when affected.

## Git and deployment

- `main` is the integration branch; use a focused feature branch.
- A push to `main` deploys to `/talking-picture/` over FTPS.
- Markdown is excluded from the FTP upload, although a push to `main` still starts the workflow.
- Never alter deployment paths or secrets unless the task is specifically about deployment.

## Context discipline

- Prefer targeted search + narrow reads over broad exploration.
- Read `readme.txt` only when user-facing setup/behavior details are needed.
- Do not paste whole files or large generated markup into the conversation when an excerpt is enough.
- Finish with a short summary of changes, checks, and remaining live-site verification.
- For a new unrelated task, prefer a fresh Claude Code session.
