# This Day In History

This repo contains the This Day In History WordPress plugin. The included GitHub Actions workflow automates building a ZIP suitable for installing via the WordPress admin UI and publishes it as a GitHub release asset.

WordPress likes to use a [readme.txt](readme.txt) file, so the user documentation and changelog can be found there.

## Release & installation

### How to make a release

- Create a tag following the pattern `vX.Y.Z` (example: `v1.2.0`).
    - git tag v1.2.0
    - git push origin v1.2.0
- The workflow `.github/workflows/release.yml` runs on the pushed tag:
    - Builds a ZIP named `this-day-in-history-vX.Y.Z.zip`
    - Creates a GitHub release for the tag and uploads the ZIP as a release asset

### Install the release into WordPress

- Download the ZIP from the GitHub release page.
- In WP Admin go to Plugins → Add New → Upload Plugin, choose the ZIP, and install.

### Notes and customization

- Excluded files/folders: .git, .github, node_modules, and existing zip files. Adjust the rsync exclude list in the workflow if you need different behaviour.
- The ZIP contains a top-level folder `this-day-in-history/` so WordPress installs it correctly.
- The workflow uses the automatically provided `GITHUB_TOKEN` to create the release; no extra secrets needed.

### Troubleshooting

- If the release asset does not appear, confirm the pushed tag matches the `v*` pattern and actions have run in the Actions tab.
- To change the tag pattern, edit `on.push.tags` in the workflow.
