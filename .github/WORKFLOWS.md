# CI/CD Workflows

This repository uses four GitHub Actions workflows. The plugin is versioned by
[release-please](https://github.com/googleapis/release-please) based on
[conventional commits](https://www.conventionalcommits.org/):
`fix:` → patch, `feat:` → minor, `feat!:` / `BREAKING CHANGE:` → major.

Tag format: `v*` (e.g. `v1.0.16`).

---

## Overview

```
Push to main
    │
    ├──▶ [release-please.yml]
    │        Creates / updates the release PR, bumping version.txt + CHANGELOG.md
    │
    │    On release PR (opened / synchronize)
    ├──▶ [update-plugin-version.yml]
    │        Syncs the Version header in plugin/ph-content-relations.php
    │        + readme.txt Stable tag & changelog entry
    │
    │    On PR to main
    └──▶ [pr.yml]
             php -l on 7.4 / 8.2 / 8.3 / 8.4
             pack + "is the payload clean?"
             "do the version carriers agree?"


Merge release PR  →  release-please pushes tag v1.0.16 + creates GitHub Release
    │
    └── v*  ──▶ [wordpress-svn-release.yml]
                    version check → pack → upload zip to the Release
                    → deploy to WordPress.org SVN (trunk + tags/1.0.16)
```

There is no build step: the plugin is plain PHP with hand-written JS and CSS.

---

## `pr.yml` — PR checks

Three jobs:

- **php-lint** — `php -l` over every PHP file, on PHP 7.4, 8.2, 8.3 and 8.4.
- **pack** — runs `bin/build-plugin.sh` and asserts the staged payload contains the
  plugin file, the classes, the admin JS, the readme and the licence, and none of the
  repository-only files. It also fails if the payload contains "Content Relations - DEV",
  the development wrapper's plugin name — shipping that would put a second entry in
  everybody's plugin list.
- **versions** — runs `bin/version-checker.sh`, so a hand-edited version number fails in
  the pull request instead of aborting a release. Skipped on the release PR: that one
  arrives with only `version.txt` bumped and gets its other carriers in a second push from
  `update-plugin-version.yml`, so checking its first commit would fail every time.

## `release-please.yml` — release PR

Runs on every push to `main`. Uses a short-lived installation token of the org-owned
"Palasthotel Release Bot" app rather than `GITHUB_TOKEN`, because the tag this job pushes
has to trigger `wordpress-svn-release.yml` — and tags pushed with `GITHUB_TOKEN` trigger
nothing.

`release-type` is `simple`: the version lives in `version.txt`. There is no
`package.json` or `composer.json` to bump.

## `update-plugin-version.yml` — version carriers

Runs only on the release-please PR (`startsWith(github.head_ref, 'release-please--')`). It
reads the version from `version.txt` and writes it into the `Version:` header of
`plugin/ph-content-relations.php`, the `Stable tag:` in `plugin/readme.txt`, and a new
`= x.y.z =` section under `== Changelog ==`, converted from the Markdown release-please
wrote into `CHANGELOG.md`.

The development wrapper in the root is not a version carrier and nothing syncs it. It never
ships, so its header version is decoration.

It pushes with the app token, not `GITHUB_TOKEN`: a `GITHUB_TOKEN` push triggers no
workflows, which would leave the release PR without check results.

## `wordpress-svn-release.yml` — deploy

Triggered by a `v*` tag, or manually by `workflow_dispatch` with a version input.

`bin/version-checker.sh` runs first and compares the tag against the version carriers, so
a mismatch stops the run before anything is published.

`bin/build-plugin.sh` stages `plugin/` in `build/content-relations/` and zips it. The zip
is attached to the GitHub Release, and the SVN commit rsyncs from the same directory — so
the release asset and the wordpress.org download are identical.

`rsync -rL`, not `cp -r`: GNU `cp` keeps symlinks while descending and BSD `cp` resolves
them, so a local rehearsal on macOS would pass while the Ubuntu runner failed.
wordpress.org discards symlinks when it builds the download, and SVN refuses a commit that
puts a symlink where it versions a regular file.

`assets/` (banner, icon, screenshots for the plugin page) is only mirrored when the
repository carries the directory — the guard is there so that adding a banner later cannot
be turned into `--delete`-ing the plugin page's media.

### Required repository configuration

| Kind | Name | Purpose |
|---|---|---|
| Variable | `RELEASE_BOT_APP_ID` | GitHub App id of the release bot |
| Variable | `SVN_REPO_URL` | `https://plugins.svn.wordpress.org/content-relations/` |
| Secret | `RELEASE_BOT_PRIVATE_KEY` | private key of that app |
| Secret | `SVN_USERNAME` | wordpress.org account with commit rights |
| Secret | `SVN_PASSWORD` | its password |

### When a release fails

Do not re-push the tag. A tag event replays the workflow file **as it was at that tag**,
so a fix to the workflow cannot be picked up that way, and a tag ruleset usually refuses
to move a tag (`GH013`). Use **Run workflow** on `wordpress-svn-release.yml` instead, from
a branch that has the fix, and give it the version to deploy.
