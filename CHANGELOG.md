# Changelog

All notable changes to this project are documented here. The format follows
[Conventional Commits](https://www.conventionalcommits.org/) and from the next release
on the file is maintained by
[release-please](https://github.com/googleapis/release-please) — do not edit it by hand.

The plugin's user-facing history lives in `plugin/readme.txt`, which is what shows on the
wordpress.org plugin page.

## [1.1.0](https://github.com/palasthotel/ph-wp-content-relations/compare/v1.0.16...v1.1.0) (2026-08-05)


### Features

* add a Query Loop variation for related content ([3a51a66](https://github.com/palasthotel/ph-wp-content-relations/commit/3a51a66269694509d9ff29f3e92d621967f50e09))
* add an edit screen for a relation type, grouped by source post ([a0c3b74](https://github.com/palasthotel/ph-wp-content-relations/commit/a0c3b74564eddb110c0211680d487d40f3f22220))
* rebuild the Tools screen as a WP_List_Table ([bec0d3f](https://github.com/palasthotel/ph-wp-content-relations/commit/bec0d3fa862dece1189dfbdf2b96494a913f5e03))
* rework the type-edit screen onto ph-postqueue's item table ([00f4d5e](https://github.com/palasthotel/ph-wp-content-relations/commit/00f4d5ef7808a5c497b414b93260d940f65c0b08))
* show each related post's type next to its title ([062ac13](https://github.com/palasthotel/ph-wp-content-relations/commit/062ac1301771c1f941cc75f78882a10e8cba83cc))


### Bug Fixes

* hide the Query Loop's post type, order and sticky controls ([15385ef](https://github.com/palasthotel/ph-wp-content-relations/commit/15385efb44fdf10eda2b41dc5eb816e5faa6b294))
* link the relation type's name to its edit screen ([658d69c](https://github.com/palasthotel/ph-wp-content-relations/commit/658d69c56b7e88a69a6adacfe05fd9a7b40211fc))
* raise Requires at least to 6.6 ([75fd296](https://github.com/palasthotel/ph-wp-content-relations/commit/75fd296f21428518022c1d56a7822401a6be4c27))
* redirect the misspelled legacy Tools page slug ([78bf51c](https://github.com/palasthotel/ph-wp-content-relations/commit/78bf51cea4268fd7e34096a0c226aae4f03abbd1))
* repair and complete the translations ([11270a8](https://github.com/palasthotel/ph-wp-content-relations/commit/11270a89e80aa7e036c7ee014a10112091f24a21))
* replace the redundant "Back to types" button with Reset ([d48a8b5](https://github.com/palasthotel/ph-wp-content-relations/commit/d48a8b5308e0f4fe065b87909ceda4589a608d6c))
* translate the JS UI strings in PHP instead of at runtime ([5bdb58b](https://github.com/palasthotel/ph-wp-content-relations/commit/5bdb58bcde073c008ee3046f309d1a1c58249edc))

## [1.0.16](https://github.com/palasthotel/ph-wp-content-relations/compare/v1.0.15...v1.0.16) (2026-08-05)


### Bug Fixes

* build the relations admin UI with the DOM instead of HTML strings ([08c0b7c](https://github.com/palasthotel/ph-wp-content-relations/commit/08c0b7cf57bc5c320746182411ef58e5fb69e953))
* build the relations admin UI with the DOM instead of HTML strings ([3ac2b97](https://github.com/palasthotel/ph-wp-content-relations/commit/3ac2b971b516e5245e76ec4eefdadae2fa725383))
* close information disclosure, SQL injection and CSRF holes ([19860e6](https://github.com/palasthotel/ph-wp-content-relations/commit/19860e6c0c5579bcde4d393edb82cc4ca0c85834))
* close information disclosure, SQL injection and CSRF holes ([3d4fa95](https://github.com/palasthotel/ph-wp-content-relations/commit/3d4fa95e1bfaaefcf2f1cffda7f623b6add92e66))

## 1.0.15

* Fix: Remove deprecated dynamic properties

Earlier releases are listed in `plugin/readme.txt`.
