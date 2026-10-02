# CI/CD Workflows

The four workflows in `.github/workflows/` call the shared ones in
[palasthotel/github-workflows](https://github.com/palasthotel/github-workflows). How
they work, every input and what to do when a deploy fails is described there, in
[docs/wp-plugin.md](https://github.com/palasthotel/github-workflows/blob/main/docs/wp-plugin.md).

What is specific to this plugin:

| | |
|---|---|
| wordpress.org slug | `content-relations` |
| version file | `version.txt` (`release-type: simple`) - `package.json` only carries the build tooling and has no version |
| build step | `npm ci && npm run build` - compiles `src/` into `public/dist/`, which is not in the repository |
| required files | the three bundles in `public/dist/` with their `.asset.php` files and `types-edit.css`, the German translation - see `pr.yml` |
| development wrapper | `ph-content-relations.php` in the root, `Plugin Name: Content Relations (DEV)`; never shipped |
| SVN | `assets/` (the plugin page icons) is only in SVN, not in this repository, so the deploy leaves it alone |

Versions are never edited by hand: release-please bumps `version.txt` and `CHANGELOG.md`
in the release PR, and the sync workflow writes the same version into the header of
`public/ph-content-relations.php` and the `Stable tag:` of `public/readme.txt`.
