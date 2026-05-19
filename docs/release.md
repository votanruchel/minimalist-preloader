# Release Flow

Use GitHub Releases as the source for installable plugin ZIPs.

## Stable Release

1. Create a tag such as `v1.0.0`.
2. Create a GitHub Release from that tag.
3. Leave **Set as a pre-release** unchecked.
4. Publish the release.

The workflow uploads:

- `minimalist-preloader-1.0.0.zip`
- `minimalist-preloader-stable.zip`

## Test Release

Create a pre-release tag such as `v1.1.0-rc.1` and mark the GitHub Release as a pre-release.

The workflow uploads only:

- `minimalist-preloader-1.1.0-rc.1.zip`

Pre-releases do not replace the stable ZIP.

## Manual Rebuild

Run **Actions > Release ZIP > Run workflow** and provide:

- `tag`: release tag to rebuild
- `stable`: whether to also update `minimalist-preloader-stable.zip`
