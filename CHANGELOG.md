# [2.6.0](https://github.com/Automattic/newspack-network/compare/v2.5.0...v2.6.0) (2025-02-17)


### Bug Fixes

* **content-distribution:** use payload hash on partial updates ([#207](https://github.com/Automattic/newspack-network/issues/207)) ([31b342d](https://github.com/Automattic/newspack-network/commit/31b342d71b54676c488eb6823baca0f9c392ce96))
* **event-log:** data css overflow ([#206](https://github.com/Automattic/newspack-network/issues/206)) ([f81adfe](https://github.com/Automattic/newspack-network/commit/f81adfed5a76047f9db3156a743411f4e1f11c67))


### Features

* **content-distribution:** migration tweaks ([#201](https://github.com/Automattic/newspack-network/issues/201)) ([9c61fa8](https://github.com/Automattic/newspack-network/commit/9c61fa8e059108ef1693a159bf356bd989b7d0df))
* **content-distribution:** partial payload ([#205](https://github.com/Automattic/newspack-network/issues/205)) ([b844d06](https://github.com/Automattic/newspack-network/commit/b844d06037bd49b5f0eb552d737d437d8a2fc40d))
* **content-distribution:** remove "Quick Edit" from linked posts ([#200](https://github.com/Automattic/newspack-network/issues/200)) ([6ad3a4a](https://github.com/Automattic/newspack-network/commit/6ad3a4a06a07b2bed506ce1f5cb183430e10354c))
* **content-distribution:** Sync authors ([#194](https://github.com/Automattic/newspack-network/issues/194)) ([3156d35](https://github.com/Automattic/newspack-network/commit/3156d358816114c9cd911f2a6f01a627e3fcf7e4))
* increase pull frequency and amount ([#209](https://github.com/Automattic/newspack-network/issues/209)) ([6791da1](https://github.com/Automattic/newspack-network/commit/6791da125999cf7e046aebb3a8b1e32194c89c80))

# [2.5.0](https://github.com/Automattic/newspack-network/compare/v2.4.0...v2.5.0) (2025-02-03)


### Bug Fixes

* **content-distribution:** block additional UI components ([#197](https://github.com/Automattic/newspack-network/issues/197)) ([3f30cdb](https://github.com/Automattic/newspack-network/commit/3f30cdbc2c49e1dfe57d5a03e9dc7477d38f249f))
* **content-distribution:** handling multiple post meta ([#199](https://github.com/Automattic/newspack-network/issues/199)) ([c93084d](https://github.com/Automattic/newspack-network/commit/c93084d3cf3c08b12e63e82fbd4120e50f1e03fd))
* **content-distribution:** Improve CSS for blocked editor ([#193](https://github.com/Automattic/newspack-network/issues/193)) ([295bdad](https://github.com/Automattic/newspack-network/commit/295bdad4b418ad4162a41c502bb58c0258793125)), closes [#181](https://github.com/Automattic/newspack-network/issues/181)
* **content-distribution:** persist site hash ([#186](https://github.com/Automattic/newspack-network/issues/186)) ([120b759](https://github.com/Automattic/newspack-network/commit/120b759553b8bd25371e2a928de8eb8acd47ddc9))
* **content-distribution:** prevent consecutive dispatches ([#198](https://github.com/Automattic/newspack-network/issues/198)) ([2149a62](https://github.com/Automattic/newspack-network/commit/2149a62e5cdc9cf5e7d05993306221c9bf8d9661))
* **content-distribution:** refactor outgoing post js ([#188](https://github.com/Automattic/newspack-network/issues/188)) ([cc08edc](https://github.com/Automattic/newspack-network/commit/cc08edccbda3a84a24586e3fd76a0d2409947e01))
* **memberships:** remove managed fields on cancel or expire ([#192](https://github.com/Automattic/newspack-network/issues/192)) ([67a5cf0](https://github.com/Automattic/newspack-network/commit/67a5cf0274802525bbb4a9e494ccc4fe3244f7ec))


### Features

* **content-distribution:** Block the editor for incoming posts ([#181](https://github.com/Automattic/newspack-network/issues/181)) ([48b4cae](https://github.com/Automattic/newspack-network/commit/48b4cae890dfbcb5bf3d90dd08b00f1cbec2b426))
* **content-distribution:** confirm dialog for unlinking and relinking posts ([#190](https://github.com/Automattic/newspack-network/issues/190)) ([f36bb28](https://github.com/Automattic/newspack-network/commit/f36bb284efba690261dbb985961bbc3839b96cc0))
* **content-distribution:** migrator ([#185](https://github.com/Automattic/newspack-network/issues/185)) ([06ec18a](https://github.com/Automattic/newspack-network/commit/06ec18a0259ce19b5aae050ea0f7a950683de353))
* **content-distribution:** post status on create ([#189](https://github.com/Automattic/newspack-network/issues/189)) ([1ad3e0c](https://github.com/Automattic/newspack-network/commit/1ad3e0ceaf7dc6599d5a0300e1330341da03ea60))
* sync multiple user roles ([#187](https://github.com/Automattic/newspack-network/issues/187)) ([9fa833c](https://github.com/Automattic/newspack-network/commit/9fa833c157fc795fda466c3bfcd00594b98f1543))

# [2.4.0](https://github.com/Automattic/newspack-network/compare/v2.3.4...v2.4.0) (2025-01-20)


### Bug Fixes

* **content-distribution:** post insertion hook and additional meta for incoming post event ([#173](https://github.com/Automattic/newspack-network/issues/173)) ([48df13c](https://github.com/Automattic/newspack-network/commit/48df13c65c06f3edabd88122c5a6816c006b70f6))
* load text domain on init hook ([#171](https://github.com/Automattic/newspack-network/issues/171)) ([01fb89c](https://github.com/Automattic/newspack-network/commit/01fb89ca5c97da18a3e0eb772e4b20afb24e8db7))


### Features

* content distribution - experimental ([#168](https://github.com/Automattic/newspack-network/issues/168)) ([dc837d8](https://github.com/Automattic/newspack-network/commit/dc837d884ab4992a90e99347e363cd61116db770))
* **content-distribution:** add CLI command for distribute post ([#159](https://github.com/Automattic/newspack-network/issues/159)) ([7a43b86](https://github.com/Automattic/newspack-network/commit/7a43b863cd11eadade73aabd060110c27576c6d4)), closes [#155](https://github.com/Automattic/newspack-network/issues/155) [#156](https://github.com/Automattic/newspack-network/issues/156) [#157](https://github.com/Automattic/newspack-network/issues/157) [#160](https://github.com/Automattic/newspack-network/issues/160) [#165](https://github.com/Automattic/newspack-network/issues/165)
* **content-distribution:** canonical url ([#177](https://github.com/Automattic/newspack-network/issues/177)) ([5ca60ce](https://github.com/Automattic/newspack-network/commit/5ca60cee132f811c5efadc8d91778410a562880d))
* **content-distribution:** capability and admin page ([#176](https://github.com/Automattic/newspack-network/issues/176)) ([5285285](https://github.com/Automattic/newspack-network/commit/52852851909b02f2876c737ec351f77ee263ea05))
* **content-distribution:** control distribution meta and prevent multiple dispatches ([#170](https://github.com/Automattic/newspack-network/issues/170)) ([e76a2dc](https://github.com/Automattic/newspack-network/commit/e76a2dc8d4c097d7e56943f2a904b4841020c62a))
* **content-distribution:** editor plugin for distribution ([#167](https://github.com/Automattic/newspack-network/issues/167)) ([e10aef4](https://github.com/Automattic/newspack-network/commit/e10aef43ce7842df1c48cafc17037700b7b9f49a))
* **content-distribution:** handle status changes ([#166](https://github.com/Automattic/newspack-network/issues/166)) ([4af5da1](https://github.com/Automattic/newspack-network/commit/4af5da1b0edbfcdf5330878cceed0349f91cc36e))
* **content-distribution:** log incoming post errors ([#182](https://github.com/Automattic/newspack-network/issues/182)) ([74c9119](https://github.com/Automattic/newspack-network/commit/74c9119435f32370838fd0b249a2dcec1189ae26))
* **content-distribution:** posts column ([#178](https://github.com/Automattic/newspack-network/issues/178)) ([8e07640](https://github.com/Automattic/newspack-network/commit/8e076407f6a837201e8773cf88635ee405311a4d))
* **content-distribution:** reserved taxonomies ([#174](https://github.com/Automattic/newspack-network/issues/174)) ([a2c54d2](https://github.com/Automattic/newspack-network/commit/a2c54d2f702f197b77c27fad7bde7dddaadafd5f))
* **content-distribution:** sync comment and ping statuses ([#179](https://github.com/Automattic/newspack-network/issues/179)) ([90c5425](https://github.com/Automattic/newspack-network/commit/90c5425a5d5e52172a5de2e26003199112e8dd22))
* **content-distribution:** sync post meta ([#163](https://github.com/Automattic/newspack-network/issues/163)) ([353a3d8](https://github.com/Automattic/newspack-network/commit/353a3d880077f9060544c8e764f780535d1ba6b8))
* **event-log:** collapse data ([#180](https://github.com/Automattic/newspack-network/issues/180)) ([956219d](https://github.com/Automattic/newspack-network/commit/956219dad76cd2b020fc96004b45d9075380b514))
* limit purchase of a network membership ([#169](https://github.com/Automattic/newspack-network/issues/169)) ([deb2683](https://github.com/Automattic/newspack-network/commit/deb268310406d4dfa42caa7b4c32a2927980ce62))

## [2.3.4](https://github.com/Automattic/newspack-network/compare/v2.3.3...v2.3.4) (2024-12-18)


### Bug Fixes

* Force Distributor to not override bylines ([#175](https://github.com/Automattic/newspack-network/issues/175)) ([efbc979](https://github.com/Automattic/newspack-network/commit/efbc9798cc7c13985e2a9a2e4920f485dbe8a11e))

## [2.3.3](https://github.com/Automattic/newspack-network/compare/v2.3.2...v2.3.3) (2024-12-16)


### Bug Fixes

* load text domain on init hook ([#171](https://github.com/Automattic/newspack-network/issues/171)) ([f8d03f6](https://github.com/Automattic/newspack-network/commit/f8d03f6e5a6b8dcea433eed2ba7d7d29ab07d70e))

## [2.3.2](https://github.com/Automattic/newspack-network/compare/v2.3.1...v2.3.2) (2024-11-25)


### Bug Fixes

* submenu highlights ([#146](https://github.com/Automattic/newspack-network/issues/146)) ([387d178](https://github.com/Automattic/newspack-network/commit/387d1783a5ae9216c0e4cffb95dfed7020da9116))

## [2.3.1](https://github.com/Automattic/newspack-network/compare/v2.3.0...v2.3.1) (2024-11-11)


### Bug Fixes

* merge pull request [#147](https://github.com/Automattic/newspack-network/issues/147) from Automattic/trunk ([7d0a118](https://github.com/Automattic/newspack-network/commit/7d0a118023ac87345c4fe3df360339cc55245ea1))

# [2.3.0](https://github.com/Automattic/newspack-network/compare/v2.2.1...v2.3.0) (2024-10-28)


### Bug Fixes

* remove recursive import `use` ([b03bb52](https://github.com/Automattic/newspack-network/commit/b03bb52f702f873f465f8b0d62d935301933689d))


### Features

* handle email change on propagated users ([#133](https://github.com/Automattic/newspack-network/issues/133)) ([2efa226](https://github.com/Automattic/newspack-network/commit/2efa2266dee3bb22d4b01ce8fbe0f0393e37cf2f))

## [2.2.1](https://github.com/Automattic/newspack-network/compare/v2.2.0...v2.2.1) (2024-10-10)


### Bug Fixes

* exclude refund orders from backfill ([cef82c1](https://github.com/Automattic/newspack-network/commit/cef82c11b2c95f62809a2ca537ea071c7a2c90cc))

# [2.2.0](https://github.com/Automattic/newspack-network/compare/v2.1.0...v2.2.0) (2024-10-08)


### Bug Fixes

* **sync:** use `newspack_esp_sync_contact` filter for the `network_registration_site` field ([#132](https://github.com/Automattic/newspack-network/issues/132)) ([3755d07](https://github.com/Automattic/newspack-network/commit/3755d07f83c813c1cc0b4599371722e9eecfbd18))


### Features

* subscriptions and  memberships sync reimplementation ([#122](https://github.com/Automattic/newspack-network/issues/122)) ([04fc845](https://github.com/Automattic/newspack-network/commit/04fc845168865bb03e59c1972be319fc8f99d4a5))

# [2.1.0](https://github.com/Automattic/newspack-network/compare/v2.0.0...v2.1.0) (2024-08-26)


### Features

* consolidate data flows ([#127](https://github.com/Automattic/newspack-network/issues/127)) ([d5f9c8f](https://github.com/Automattic/newspack-network/commit/d5f9c8f06dd06c8ec7a76ba827c7b11f6dd65c36))

# [2.0.0](https://github.com/Automattic/newspack-network/compare/v1.10.1...v2.0.0) (2024-08-13)


### Bug Fixes

* **memberships-sync:** handle active subs from other nodes ([#114](https://github.com/Automattic/newspack-network/issues/114)) ([97f9a58](https://github.com/Automattic/newspack-network/commit/97f9a58b90e660ec9511833b84af881357176163))
* update dependencies to support `@wordpress/scripts` ([#104](https://github.com/Automattic/newspack-network/issues/104)) ([e9691b8](https://github.com/Automattic/newspack-network/commit/e9691b8edcdc58cd3466bff46d2cbe4eacba9eaf))


### Features

* **distributor:** sync comment status ([#116](https://github.com/Automattic/newspack-network/issues/116)) ([5844853](https://github.com/Automattic/newspack-network/commit/58448533aa562238a6ea080b866155cb13764853))
* ensure that membership plans have unique network id ([#108](https://github.com/Automattic/newspack-network/issues/108)) ([d2d63a0](https://github.com/Automattic/newspack-network/commit/d2d63a04127718cadca74a57ff074d5fcfebb300))


### BREAKING CHANGES

* Updates dependencies for compatibility with WordPress 6.6.*, but breaks JS in WordPress 6.5.* and below. If you need support for WP 6.5.*, please do not upgrade to this new major version.

* chore: refactor for newspack-scripts dependency updates

* chore: update composer

* fix: peer dependencies

* chore: update newspack-scripts to v5.6.0-alpha.3

* chore: update newspack-scripts to v5.6.0-alpha.4

* chore: remove unnecessary prettier config file

* chore: update newspack-scripts to v5.6.0-alpha.7

* fix: update phpcs.xml

* chore: bump newspack-scripts to v5.5.2

## [1.10.1](https://github.com/Automattic/newspack-network/compare/v1.10.0...v1.10.1) (2024-08-01)


### Bug Fixes

* **memberships-sync:** handle active subs from other nodes ([#114](https://github.com/Automattic/newspack-network/issues/114)) ([4255f8c](https://github.com/Automattic/newspack-network/commit/4255f8c7676cc22f9a354de163fea763986b6a7e))

# [1.10.0](https://github.com/Automattic/newspack-network/compare/v1.9.2...v1.10.0) (2024-07-30)


### Features

* **membership-plans:** improvements  ([#110](https://github.com/Automattic/newspack-network/issues/110)) ([f994478](https://github.com/Automattic/newspack-network/commit/f994478f9033854f499ad5343c16fda43e460643))
* user discrepancies display ([#100](https://github.com/Automattic/newspack-network/issues/100)) ([a054967](https://github.com/Automattic/newspack-network/commit/a05496735e1e8f691e3e5f680dbcd8e3a3909228))
* **users:** bulk user network sync ([d1e9533](https://github.com/Automattic/newspack-network/commit/d1e953335bd92058319ca57ad801c2ee770c681e))

## [1.9.2](https://github.com/Automattic/newspack-network/compare/v1.9.1...v1.9.2) (2024-07-16)


### Bug Fixes

* **api:** improve /info endpoint ([70e2208](https://github.com/Automattic/newspack-network/commit/70e2208b9ca47ad71506f030f5a4765ab64b4bc9))

## [1.9.1](https://github.com/Automattic/newspack-network/compare/v1.9.0...v1.9.1) (2024-07-01)


### Bug Fixes

* update newspack-scripts to v5.5.1 ([#105](https://github.com/Automattic/newspack-network/issues/105)) ([32acd37](https://github.com/Automattic/newspack-network/commit/32acd377ae0bda3acca4bde970c8db46557ad256))

# [1.9.0](https://github.com/Automattic/newspack-network/compare/v1.8.0...v1.9.0) (2024-06-12)


### Features

* handle existing users by adding a role; username generation and membership dedupe command fixes ([#98](https://github.com/Automattic/newspack-network/issues/98)) ([9f74e75](https://github.com/Automattic/newspack-network/commit/9f74e75d65c6abeb8bcceca30299a533ddd1aed9)), closes [#84](https://github.com/Automattic/newspack-network/issues/84)

# [1.9.0-alpha.1](https://github.com/Automattic/newspack-network/compare/v1.8.0...v1.9.0-alpha.1) (2024-05-31)


### Features

* handle existing users by adding a role; username generation and membership dedupe command fixes ([#98](https://github.com/Automattic/newspack-network/issues/98)) ([9f74e75](https://github.com/Automattic/newspack-network/commit/9f74e75d65c6abeb8bcceca30299a533ddd1aed9)), closes [#84](https://github.com/Automattic/newspack-network/issues/84)

# [1.8.0](https://github.com/Automattic/newspack-network/compare/v1.7.0...v1.8.0) (2024-05-28)


### Bug Fixes

* **backfill:** reader registered, membership updated ([b177538](https://github.com/Automattic/newspack-network/commit/b177538edded61491582cf72f254e51e026553f6))


### Features

* **admin:** subscriptions view ([cb5bcb7](https://github.com/Automattic/newspack-network/commit/cb5bcb7be0235c72a21d89ffcdac6ae154a3fa15))
* auditing features env variable name change ([e1df42b](https://github.com/Automattic/newspack-network/commit/e1df42b605e284723c9d8babeee4456125307e27))
* remove hub's subscriptions and orders ([e234425](https://github.com/Automattic/newspack-network/commit/e23442525922b95d66455ff48b40b1cb85ed7eeb))
* sync Network Registration Site on network reader creation or fetch ([eeba099](https://github.com/Automattic/newspack-network/commit/eeba099429d87e7012c1397d432140dadd8c2916))

# [1.8.0-alpha.1](https://github.com/Automattic/newspack-network/compare/v1.7.0...v1.8.0-alpha.1) (2024-05-21)


### Bug Fixes

* **backfill:** reader registered, membership updated ([b177538](https://github.com/Automattic/newspack-network/commit/b177538edded61491582cf72f254e51e026553f6))


### Features

* **admin:** subscriptions view ([cb5bcb7](https://github.com/Automattic/newspack-network/commit/cb5bcb7be0235c72a21d89ffcdac6ae154a3fa15))
* auditing features env variable name change ([e1df42b](https://github.com/Automattic/newspack-network/commit/e1df42b605e284723c9d8babeee4456125307e27))
* remove hub's subscriptions and orders ([e234425](https://github.com/Automattic/newspack-network/commit/e23442525922b95d66455ff48b40b1cb85ed7eeb))
* sync Network Registration Site on network reader creation or fetch ([eeba099](https://github.com/Automattic/newspack-network/commit/eeba099429d87e7012c1397d432140dadd8c2916))

# [1.7.0](https://github.com/Automattic/newspack-network/compare/v1.6.0...v1.7.0) (2024-05-15)


### Features

* handle user deletion event on the network ([#91](https://github.com/Automattic/newspack-network/issues/91)) ([cf3df3b](https://github.com/Automattic/newspack-network/commit/cf3df3b5d2d61f2a1679823f80c4780467a0f4f5))

# [1.7.0-alpha.1](https://github.com/Automattic/newspack-network/compare/v1.6.0...v1.7.0-alpha.1) (2024-04-25)


### Features

* handle user deletion event on the network ([#91](https://github.com/Automattic/newspack-network/issues/91)) ([cf3df3b](https://github.com/Automattic/newspack-network/commit/cf3df3b5d2d61f2a1679823f80c4780467a0f4f5))

# [1.6.0](https://github.com/Automattic/newspack-network/compare/v1.5.0...v1.6.0) (2024-04-24)


### Features

* experimental auditing features ([#79](https://github.com/Automattic/newspack-network/issues/79)) ([0c31fd6](https://github.com/Automattic/newspack-network/commit/0c31fd60eeb404ab36f0d5eff7482f4cd4a5085e))

# [1.6.0-alpha.1](https://github.com/Automattic/newspack-network/compare/v1.5.0...v1.6.0-alpha.1) (2024-04-11)


### Features

* experimental auditing features ([#79](https://github.com/Automattic/newspack-network/issues/79)) ([0c31fd6](https://github.com/Automattic/newspack-network/commit/0c31fd60eeb404ab36f0d5eff7482f4cd4a5085e))

# [1.5.0](https://github.com/Automattic/newspack-network/compare/v1.4.2...v1.5.0) (2024-04-08)


### Bug Fixes

* cli commands ([#73](https://github.com/Automattic/newspack-network/issues/73)) ([dc2bef9](https://github.com/Automattic/newspack-network/commit/dc2bef9b3dc2de8357f8deb6c7efb6139eec404f))
* correct typo in manual sync description ([#74](https://github.com/Automattic/newspack-network/issues/74)) ([a0bf23f](https://github.com/Automattic/newspack-network/commit/a0bf23fe8837cf7eefcbb867aa75819dc2e1b531))
* webhook encryption error handling ([#77](https://github.com/Automattic/newspack-network/issues/77)) ([ceee31f](https://github.com/Automattic/newspack-network/commit/ceee31f78fe4452b79485369a38409e5ca32413a))
* woo links in wp-admin-bar ([#71](https://github.com/Automattic/newspack-network/issues/71)) ([d2a76e5](https://github.com/Automattic/newspack-network/commit/d2a76e51084176cef9b6549c0c7940b17411904f))


### Features

* add Network-specific custom metadata to ESP syncs ([#83](https://github.com/Automattic/newspack-network/issues/83)) ([153a733](https://github.com/Automattic/newspack-network/commit/153a733080a78b0035e3b3bd98b7093b9d20f8fb))
* **cli:** --yes option for sync-all command ([268f7fe](https://github.com/Automattic/newspack-network/commit/268f7fed7892ae922ac6106ee824f37058c2ecad))
* **cli:** membership de-duplication CLI command ([#84](https://github.com/Automattic/newspack-network/issues/84)) ([c7ece71](https://github.com/Automattic/newspack-network/commit/c7ece717c297f55e4ae9db0791346e27953dc651))
* **manual-user-sync:** sync user login ([#81](https://github.com/Automattic/newspack-network/issues/81)) ([3f9755e](https://github.com/Automattic/newspack-network/commit/3f9755ed5b75a280c0a0e23b6d91f319ad0b8fa4))

## [1.4.2](https://github.com/Automattic/newspack-network/compare/v1.4.1...v1.4.2) (2024-04-02)


### Bug Fixes

* add new filter callback for Network Registration Site field ([#87](https://github.com/Automattic/newspack-network/issues/87)) ([00facbc](https://github.com/Automattic/newspack-network/commit/00facbcfe9468493c480c80872ebcf7b13824fc1))

# [1.5.0-alpha.2](https://github.com/Automattic/newspack-network/compare/v1.5.0-alpha.1...v1.5.0-alpha.2) (2024-03-29)


### Bug Fixes

* **event-log:** only show users filter with environment constant ([#86](https://github.com/Automattic/newspack-network/issues/86)) ([cd4a91a](https://github.com/Automattic/newspack-network/commit/cd4a91ab65a81d7abc54359ff6e1537aae253b84))

# [1.5.0-alpha.1](https://github.com/Automattic/newspack-network/compare/v1.4.0...v1.5.0-alpha.1) (2024-03-27)

### Features

* **event-log:** only show users filter with environment constant ([#86](https://github.com/Automattic/newspack-network/issues/86)) ([cd4a91a](https://github.com/Automattic/newspack-network/commit/cd4a91ab65a81d7abc54359ff6e1537aae253b84))


## [1.4.1](https://github.com/Automattic/newspack-network/compare/v1.4.0...v1.4.1) (2024-03-29)


### Bug Fixes

* cli commands ([#73](https://github.com/Automattic/newspack-network/issues/73)) ([dc2bef9](https://github.com/Automattic/newspack-network/commit/dc2bef9b3dc2de8357f8deb6c7efb6139eec404f))
* correct typo in manual sync description ([#74](https://github.com/Automattic/newspack-network/issues/74)) ([a0bf23f](https://github.com/Automattic/newspack-network/commit/a0bf23fe8837cf7eefcbb867aa75819dc2e1b531))
* webhook encryption error handling ([#77](https://github.com/Automattic/newspack-network/issues/77)) ([ceee31f](https://github.com/Automattic/newspack-network/commit/ceee31f78fe4452b79485369a38409e5ca32413a))
* woo links in wp-admin-bar ([#71](https://github.com/Automattic/newspack-network/issues/71)) ([d2a76e5](https://github.com/Automattic/newspack-network/commit/d2a76e51084176cef9b6549c0c7940b17411904f))


### Features

* add Network-specific custom metadata to ESP syncs ([#83](https://github.com/Automattic/newspack-network/issues/83)) ([153a733](https://github.com/Automattic/newspack-network/commit/153a733080a78b0035e3b3bd98b7093b9d20f8fb))
* **cli:** --yes option for sync-all command ([268f7fe](https://github.com/Automattic/newspack-network/commit/268f7fed7892ae922ac6106ee824f37058c2ecad))
* **cli:** membership de-duplication CLI command ([#84](https://github.com/Automattic/newspack-network/issues/84)) ([c7ece71](https://github.com/Automattic/newspack-network/commit/c7ece717c297f55e4ae9db0791346e27953dc651))
* **manual-user-sync:** sync user login ([#81](https://github.com/Automattic/newspack-network/issues/81)) ([3f9755e](https://github.com/Automattic/newspack-network/commit/3f9755ed5b75a280c0a0e23b6d91f319ad0b8fa4))

# [1.4.0](https://github.com/Automattic/newspack-network/compare/v1.3.0...v1.4.0) (2024-03-27)


### Features

* add Network-specific custom metadata to ESP syncs ([#83](https://github.com/Automattic/newspack-network/issues/83)) ([#85](https://github.com/Automattic/newspack-network/issues/85)) ([5850129](https://github.com/Automattic/newspack-network/commit/5850129b90109103fbe1f268f8f6f7256f394520))


# [1.4.0-alpha.1](https://github.com/Automattic/newspack-network/compare/v1.3.0...v1.4.0-alpha.1) (2024-03-26)


### Bug Fixes

* cli commands ([#73](https://github.com/Automattic/newspack-network/issues/73)) ([dc2bef9](https://github.com/Automattic/newspack-network/commit/dc2bef9b3dc2de8357f8deb6c7efb6139eec404f))
* correct typo in manual sync description ([#74](https://github.com/Automattic/newspack-network/issues/74)) ([a0bf23f](https://github.com/Automattic/newspack-network/commit/a0bf23fe8837cf7eefcbb867aa75819dc2e1b531))
* webhook encryption error handling ([#77](https://github.com/Automattic/newspack-network/issues/77)) ([ceee31f](https://github.com/Automattic/newspack-network/commit/ceee31f78fe4452b79485369a38409e5ca32413a))
* woo links in wp-admin-bar ([#71](https://github.com/Automattic/newspack-network/issues/71)) ([d2a76e5](https://github.com/Automattic/newspack-network/commit/d2a76e51084176cef9b6549c0c7940b17411904f))


### Features

* add Network-specific custom metadata to ESP syncs ([#83](https://github.com/Automattic/newspack-network/issues/83)) ([153a733](https://github.com/Automattic/newspack-network/commit/153a733080a78b0035e3b3bd98b7093b9d20f8fb))
* **cli:** --yes option for sync-all command ([268f7fe](https://github.com/Automattic/newspack-network/commit/268f7fed7892ae922ac6106ee824f37058c2ecad))
* **cli:** membership de-duplication CLI command ([#84](https://github.com/Automattic/newspack-network/issues/84)) ([c7ece71](https://github.com/Automattic/newspack-network/commit/c7ece717c297f55e4ae9db0791346e27953dc651))
* **manual-user-sync:** sync user login ([#81](https://github.com/Automattic/newspack-network/issues/81)) ([3f9755e](https://github.com/Automattic/newspack-network/commit/3f9755ed5b75a280c0a0e23b6d91f319ad0b8fa4))

# [1.3.0](https://github.com/Automattic/newspack-network/compare/v1.2.0...v1.3.0) (2024-02-28)


### Bug Fixes

* backfill duplicate handling; woo links in wp-admin-bar ([#71](https://github.com/Automattic/newspack-network/issues/71), [#72](https://github.com/Automattic/newspack-network/issues/72)) ([bbce13b](https://github.com/Automattic/newspack-network/commit/bbce13b9963437d9ef802ac0f6f343fbe59da630))
* cli commands ([#73](https://github.com/Automattic/newspack-network/issues/73)) ([ff563ac](https://github.com/Automattic/newspack-network/commit/ff563acee53b899592269a1121e2552b678cf9c9))
* memberships sync ([#63](https://github.com/Automattic/newspack-network/issues/63)) ([0a54f1d](https://github.com/Automattic/newspack-network/commit/0a54f1dbeb2da8281324ed3fc6323dc5a9100337))
* missing woocommerce-memberships handling ([76dbdf7](https://github.com/Automattic/newspack-network/commit/76dbdf72ae44a27f25c2c35c75720eae637fd518))


### Features

* add hub bookmarks to nodes ([#56](https://github.com/Automattic/newspack-network/issues/56)) ([54700a0](https://github.com/Automattic/newspack-network/commit/54700a0ae4d272254902278bd208abb8df5a0805))
* add option to manually sync users  ([#53](https://github.com/Automattic/newspack-network/issues/53)) ([3ec1b19](https://github.com/Automattic/newspack-network/commit/3ec1b1906a504ea83c8f99a673b994cb983b0f83))
* display membership plans from the network ([#59](https://github.com/Automattic/newspack-network/issues/59)) ([6fa01fd](https://github.com/Automattic/newspack-network/commit/6fa01fd0ee903ff3e9a3cd496128811cc1600de3))
* enhance network subscriptions and orders search ([#55](https://github.com/Automattic/newspack-network/issues/55)) ([bcb0615](https://github.com/Automattic/newspack-network/commit/bcb06155cb7d148f3dc735dbd88f566f8cf6f65c))
* Node connection using a link ([#58](https://github.com/Automattic/newspack-network/issues/58)) ([721f8b2](https://github.com/Automattic/newspack-network/commit/721f8b2ed62db8f941bf11789cd3ece577606931))
* **wp-cli:** add command to backfill events ([#51](https://github.com/Automattic/newspack-network/issues/51)) ([13d2498](https://github.com/Automattic/newspack-network/commit/13d24988ba15757f1a228ec87855122b13b45a59))
* **wp-cli:** add process pending webhook requests command ([#67](https://github.com/Automattic/newspack-network/issues/67)) ([7dbd8dc](https://github.com/Automattic/newspack-network/commit/7dbd8dc6813942ad8f457120cf4ad01b84882c3a))
* **wp-cli:** sync all events from the Hub ([#65](https://github.com/Automattic/newspack-network/issues/65)) ([dc595ca](https://github.com/Automattic/newspack-network/commit/dc595ca69ae5bae0da45cb9f96e581a1eb6b2579))

# [1.3.0-alpha.4](https://github.com/Automattic/newspack-network/compare/v1.3.0-alpha.3...v1.3.0-alpha.4) (2024-02-28)


### Bug Fixes

* cli commands ([#73](https://github.com/Automattic/newspack-network/issues/73)) ([ff563ac](https://github.com/Automattic/newspack-network/commit/ff563acee53b899592269a1121e2552b678cf9c9))

# [1.3.0-alpha.3](https://github.com/Automattic/newspack-network/compare/v1.3.0-alpha.2...v1.3.0-alpha.3) (2024-02-28)


### Bug Fixes

* backfill duplicate handling; woo links in wp-admin-bar ([#71](https://github.com/Automattic/newspack-network/issues/71), [#72](https://github.com/Automattic/newspack-network/issues/72)) ([bbce13b](https://github.com/Automattic/newspack-network/commit/bbce13b9963437d9ef802ac0f6f343fbe59da630))

# [1.3.0-alpha.2](https://github.com/Automattic/newspack-network/compare/v1.3.0-alpha.1...v1.3.0-alpha.2) (2024-02-27)


### Bug Fixes

* missing woocommerce-memberships handling ([76dbdf7](https://github.com/Automattic/newspack-network/commit/76dbdf72ae44a27f25c2c35c75720eae637fd518))

# [1.3.0-alpha.1](https://github.com/Automattic/newspack-network/compare/v1.2.0...v1.3.0-alpha.1) (2024-02-23)


### Bug Fixes

* memberships sync ([#63](https://github.com/Automattic/newspack-network/issues/63)) ([0a54f1d](https://github.com/Automattic/newspack-network/commit/0a54f1dbeb2da8281324ed3fc6323dc5a9100337))


### Features

* add hub bookmarks to nodes ([#56](https://github.com/Automattic/newspack-network/issues/56)) ([54700a0](https://github.com/Automattic/newspack-network/commit/54700a0ae4d272254902278bd208abb8df5a0805))
* add option to manually sync users  ([#53](https://github.com/Automattic/newspack-network/issues/53)) ([3ec1b19](https://github.com/Automattic/newspack-network/commit/3ec1b1906a504ea83c8f99a673b994cb983b0f83))
* display membership plans from the network ([#59](https://github.com/Automattic/newspack-network/issues/59)) ([6fa01fd](https://github.com/Automattic/newspack-network/commit/6fa01fd0ee903ff3e9a3cd496128811cc1600de3))
* enhance network subscriptions and orders search ([#55](https://github.com/Automattic/newspack-network/issues/55)) ([bcb0615](https://github.com/Automattic/newspack-network/commit/bcb06155cb7d148f3dc735dbd88f566f8cf6f65c))
* Node connection using a link ([#58](https://github.com/Automattic/newspack-network/issues/58)) ([721f8b2](https://github.com/Automattic/newspack-network/commit/721f8b2ed62db8f941bf11789cd3ece577606931))
* **wp-cli:** add command to backfill events ([#51](https://github.com/Automattic/newspack-network/issues/51)) ([13d2498](https://github.com/Automattic/newspack-network/commit/13d24988ba15757f1a228ec87855122b13b45a59))
* **wp-cli:** add process pending webhook requests command ([#67](https://github.com/Automattic/newspack-network/issues/67)) ([7dbd8dc](https://github.com/Automattic/newspack-network/commit/7dbd8dc6813942ad8f457120cf4ad01b84882c3a))
* **wp-cli:** sync all events from the Hub ([#65](https://github.com/Automattic/newspack-network/issues/65)) ([dc595ca](https://github.com/Automattic/newspack-network/commit/dc595ca69ae5bae0da45cb9f96e581a1eb6b2579))

# [1.2.0](https://github.com/Automattic/newspack-network/compare/v1.1.0...v1.2.0) (2024-02-20)


### Bug Fixes

* assorted error handling fixes ([#52](https://github.com/Automattic/newspack-network/issues/52)) ([234f883](https://github.com/Automattic/newspack-network/commit/234f883716dab7ce9ba2bac8a2bf93e58bbeb887))
* **data-listeners:** handle no user in wc data update ([4eeefc0](https://github.com/Automattic/newspack-network/commit/4eeefc088b12ffdb17db669d9b1f6605c546d1b1))
* dynamic class property deprecated warnings ([#47](https://github.com/Automattic/newspack-network/issues/47)) ([693865c](https://github.com/Automattic/newspack-network/commit/693865c71ea18e3b3aff2810cc61ec407144dd43))
* prevent coauthors capability check infinite loop ([#46](https://github.com/Automattic/newspack-network/issues/46)) ([9565cef](https://github.com/Automattic/newspack-network/commit/9565cefeedd7cfc518b35dd84cce21b171664781))
* race condition when creating a Node ([1946473](https://github.com/Automattic/newspack-network/commit/1946473d0d1baf2bef2c66b5377e0e48b630df75))
* set Yoast primary category ([#41](https://github.com/Automattic/newspack-network/issues/41)) ([3457d19](https://github.com/Automattic/newspack-network/commit/3457d19ab5aea56d13ea2f7179dd1296d563ae7e))


### Features

* **ci:** add epic/* release workflow and rename `master` to `trunk` ([#39](https://github.com/Automattic/newspack-network/issues/39)) ([9cee51d](https://github.com/Automattic/newspack-network/commit/9cee51dfd407919631df144b11e9599a312cffce))
* sync billing and shipping addresses ([#50](https://github.com/Automattic/newspack-network/issues/50)) ([6a05580](https://github.com/Automattic/newspack-network/commit/6a055808f244ec2ad790a13d068431feb6acdc17))
* sync publish, trash post statuses ([#42](https://github.com/Automattic/newspack-network/issues/42)) ([fd5d8b9](https://github.com/Automattic/newspack-network/commit/fd5d8b9d2c613486a43f061df1caf73bd6fb3979))

# [1.2.0-alpha.3](https://github.com/Automattic/newspack-network/compare/v1.2.0-alpha.2...v1.2.0-alpha.3) (2024-02-16)


### Bug Fixes

* race condition when creating a Node ([1946473](https://github.com/Automattic/newspack-network/commit/1946473d0d1baf2bef2c66b5377e0e48b630df75))

# [1.2.0-alpha.2](https://github.com/Automattic/newspack-network/compare/v1.2.0-alpha.1...v1.2.0-alpha.2) (2024-02-15)


### Bug Fixes

* assorted error handling fixes ([#52](https://github.com/Automattic/newspack-network/issues/52)) ([234f883](https://github.com/Automattic/newspack-network/commit/234f883716dab7ce9ba2bac8a2bf93e58bbeb887))
* dynamic class property deprecated warnings ([#47](https://github.com/Automattic/newspack-network/issues/47)) ([693865c](https://github.com/Automattic/newspack-network/commit/693865c71ea18e3b3aff2810cc61ec407144dd43))
* prevent coauthors capability check infinite loop ([#46](https://github.com/Automattic/newspack-network/issues/46)) ([9565cef](https://github.com/Automattic/newspack-network/commit/9565cefeedd7cfc518b35dd84cce21b171664781))
* set Yoast primary category ([#41](https://github.com/Automattic/newspack-network/issues/41)) ([3457d19](https://github.com/Automattic/newspack-network/commit/3457d19ab5aea56d13ea2f7179dd1296d563ae7e))


### Features

* sync billing and shipping addresses ([#50](https://github.com/Automattic/newspack-network/issues/50)) ([6a05580](https://github.com/Automattic/newspack-network/commit/6a055808f244ec2ad790a13d068431feb6acdc17))
* sync publish, trash post statuses ([#42](https://github.com/Automattic/newspack-network/issues/42)) ([fd5d8b9](https://github.com/Automattic/newspack-network/commit/fd5d8b9d2c613486a43f061df1caf73bd6fb3979))

# [1.2.0-alpha.1](https://github.com/Automattic/newspack-network/compare/v1.1.0...v1.2.0-alpha.1) (2024-02-08)


### Bug Fixes

* **data-listeners:** handle no user in wc data update ([4eeefc0](https://github.com/Automattic/newspack-network/commit/4eeefc088b12ffdb17db669d9b1f6605c546d1b1))


### Features

* **ci:** add epic/* release workflow and rename `master` to `trunk` ([#39](https://github.com/Automattic/newspack-network/issues/39)) ([9cee51d](https://github.com/Automattic/newspack-network/commit/9cee51dfd407919631df144b11e9599a312cffce))

# [1.1.0](https://github.com/Automattic/newspack-network/compare/v1.0.1...v1.1.0) (2024-01-25)


### Bug Fixes

* add workaround for distributor bug ([#36](https://github.com/Automattic/newspack-network/issues/36)) ([123ac89](https://github.com/Automattic/newspack-network/commit/123ac894154a6b92321bff92c6b0413357840b2f))


### Features

* add event queue debug ([#34](https://github.com/Automattic/newspack-network/issues/34)) ([a5e48ae](https://github.com/Automattic/newspack-network/commit/a5e48ae516b1b33ace4047eb2ee7f640ff96d514))

# [1.1.0-alpha.1](https://github.com/Automattic/newspack-network/compare/v1.0.1...v1.1.0-alpha.1) (2024-01-25)


### Bug Fixes

* add workaround for distributor bug ([#36](https://github.com/Automattic/newspack-network/issues/36)) ([123ac89](https://github.com/Automattic/newspack-network/commit/123ac894154a6b92321bff92c6b0413357840b2f))


### Features

* add event queue debug ([#34](https://github.com/Automattic/newspack-network/issues/34)) ([a5e48ae](https://github.com/Automattic/newspack-network/commit/a5e48ae516b1b33ace4047eb2ee7f640ff96d514))

## [1.0.1](https://github.com/Automattic/newspack-network/compare/v1.0.0...v1.0.1) (2024-01-18)


### Bug Fixes

* namespace ([7c4d560](https://github.com/Automattic/newspack-network/commit/7c4d5608cbdd79ea0c63091ba04f6ad6e5798436))
* update author distribution on pull ([e9548a5](https://github.com/Automattic/newspack-network/commit/e9548a5dcb313abfc50b056a52a80a70d3432200))

# 1.0.0 (2023-12-22)


### Bug Fixes

* adjust node_id check logic ([9ceb09f](https://github.com/Automattic/newspack-network/commit/9ceb09f762c63f4a51119c310640e48bb511df82))
* canonical url processing ([c9ee2c9](https://github.com/Automattic/newspack-network/commit/c9ee2c9edeb998206882ecdbb8c9f046d0355192))
* dont sync authors terms ([#26](https://github.com/Automattic/newspack-network/issues/26)) ([a4a788b](https://github.com/Automattic/newspack-network/commit/a4a788b1f21ff0e330365d890434cc602c4b4b33))
* failing CI jobs ([#11](https://github.com/Automattic/newspack-network/issues/11)) ([c62248b](https://github.com/Automattic/newspack-network/commit/c62248b3e70cb6c7cdeb017ce2a012b0df7cfbcf))
* **newspack-ads:** parse targeting site url ([#22](https://github.com/Automattic/newspack-network/issues/22)) ([acb84c1](https://github.com/Automattic/newspack-network/commit/acb84c121db9bdb7bab68a267f7ed45ad333bd83))
* remove gam ad targeting ([#16](https://github.com/Automattic/newspack-network/issues/16)) ([c47cb15](https://github.com/Automattic/newspack-network/commit/c47cb15efb52d6a4470f85e5e1df9a1898faa2b6))


### Features

* add circle ci config ([3f9d752](https://github.com/Automattic/newspack-network/commit/3f9d752ad18819e9c3525e8fdec90166c817dfc4))
* Add site role selection and unify menus ([bbc8eb5](https://github.com/Automattic/newspack-network/commit/bbc8eb5cd6eba8b228eb5e54356e0a11537072db))
* add support to local Woo events ([1273ee1](https://github.com/Automattic/newspack-network/commit/1273ee1c52e711481cecb5401c3b965504d0e437))
* **ads:** support network targeting key-val ([#13](https://github.com/Automattic/newspack-network/issues/13)) ([37f677a](https://github.com/Automattic/newspack-network/commit/37f677aedd1cd9932bca942a84667c7e6315f784))
* allow editors to pull content ([#28](https://github.com/Automattic/newspack-network/issues/28)) ([3021f7e](https://github.com/Automattic/newspack-network/commit/3021f7e3d50876e2655a2955a5fe64cb25a7dba9))
* bump limit of events pulled by nodes from to 2 to 20 ([#7](https://github.com/Automattic/newspack-network/issues/7)) ([c5fbdc5](https://github.com/Automattic/newspack-network/commit/c5fbdc56526034760081156208a49820de0f3d85))
* network donation events as reader data ([#8](https://github.com/Automattic/newspack-network/issues/8)) ([9a114a4](https://github.com/Automattic/newspack-network/commit/9a114a49bd1fa6d47321e979e096587c481f183e))
* **node:** debug tools ([#12](https://github.com/Automattic/newspack-network/issues/12)) ([07b5470](https://github.com/Automattic/newspack-network/commit/07b5470b619a900a1cf264e24fead3ca6ff9cc5b))
* refactor to symetric crypto ([1f55551](https://github.com/Automattic/newspack-network/commit/1f555513cb282446c075f4e296711298feea12e8))
* sync author bio and meta ([#15](https://github.com/Automattic/newspack-network/issues/15)) ([f60236d](https://github.com/Automattic/newspack-network/commit/f60236dfb1324d8d7da7e09eb386047b7d02a3fa))
* sync user avatar ([#21](https://github.com/Automattic/newspack-network/issues/21)) ([70e4c83](https://github.com/Automattic/newspack-network/commit/70e4c834067cf3a2571a7b26b3b570e6c36ad376))
* update authors on post update ([#29](https://github.com/Automattic/newspack-network/issues/29)) ([25cb65f](https://github.com/Automattic/newspack-network/commit/25cb65f1f89cc6c4ac05b3be0f4aaab5029440c1))
* update menu icon and position ([#18](https://github.com/Automattic/newspack-network/issues/18)) ([11638b2](https://github.com/Automattic/newspack-network/commit/11638b22b50b555c4310336ebdd9df1f4df6ba23))

# [1.0.0-alpha.2](https://github.com/Automattic/newspack-network/compare/v1.0.0-alpha.1...v1.0.0-alpha.2) (2023-12-22)


### Bug Fixes

* dont sync authors terms ([#26](https://github.com/Automattic/newspack-network/issues/26)) ([a4a788b](https://github.com/Automattic/newspack-network/commit/a4a788b1f21ff0e330365d890434cc602c4b4b33))
* **newspack-ads:** parse targeting site url ([#22](https://github.com/Automattic/newspack-network/issues/22)) ([acb84c1](https://github.com/Automattic/newspack-network/commit/acb84c121db9bdb7bab68a267f7ed45ad333bd83))


### Features

* allow editors to pull content ([#28](https://github.com/Automattic/newspack-network/issues/28)) ([3021f7e](https://github.com/Automattic/newspack-network/commit/3021f7e3d50876e2655a2955a5fe64cb25a7dba9))
* network donation events as reader data ([#8](https://github.com/Automattic/newspack-network/issues/8)) ([9a114a4](https://github.com/Automattic/newspack-network/commit/9a114a49bd1fa6d47321e979e096587c481f183e))
* sync user avatar ([#21](https://github.com/Automattic/newspack-network/issues/21)) ([70e4c83](https://github.com/Automattic/newspack-network/commit/70e4c834067cf3a2571a7b26b3b570e6c36ad376))
* update authors on post update ([#29](https://github.com/Automattic/newspack-network/issues/29)) ([25cb65f](https://github.com/Automattic/newspack-network/commit/25cb65f1f89cc6c4ac05b3be0f4aaab5029440c1))
* update menu icon and position ([#18](https://github.com/Automattic/newspack-network/issues/18)) ([11638b2](https://github.com/Automattic/newspack-network/commit/11638b22b50b555c4310336ebdd9df1f4df6ba23))

# 1.0.0-alpha.1 (2023-12-01)


### Bug Fixes

* adjust node_id check logic ([9ceb09f](https://github.com/Automattic/newspack-network/commit/9ceb09f762c63f4a51119c310640e48bb511df82))
* canonical url processing ([c9ee2c9](https://github.com/Automattic/newspack-network/commit/c9ee2c9edeb998206882ecdbb8c9f046d0355192))
* failing CI jobs ([#11](https://github.com/Automattic/newspack-network/issues/11)) ([c62248b](https://github.com/Automattic/newspack-network/commit/c62248b3e70cb6c7cdeb017ce2a012b0df7cfbcf))
* remove gam ad targeting ([#16](https://github.com/Automattic/newspack-network/issues/16)) ([c47cb15](https://github.com/Automattic/newspack-network/commit/c47cb15efb52d6a4470f85e5e1df9a1898faa2b6))


### Features

* add circle ci config ([3f9d752](https://github.com/Automattic/newspack-network/commit/3f9d752ad18819e9c3525e8fdec90166c817dfc4))
* Add site role selection and unify menus ([bbc8eb5](https://github.com/Automattic/newspack-network/commit/bbc8eb5cd6eba8b228eb5e54356e0a11537072db))
* add support to local Woo events ([1273ee1](https://github.com/Automattic/newspack-network/commit/1273ee1c52e711481cecb5401c3b965504d0e437))
* bump limit of events pulled by nodes from to 2 to 20 ([#7](https://github.com/Automattic/newspack-network/issues/7)) ([c5fbdc5](https://github.com/Automattic/newspack-network/commit/c5fbdc56526034760081156208a49820de0f3d85))
* refactor to symetric crypto ([1f55551](https://github.com/Automattic/newspack-network/commit/1f555513cb282446c075f4e296711298feea12e8))
* **ads:** support network targeting key-val ([#13](https://github.com/Automattic/newspack-network/issues/13)) ([37f677a](https://github.com/Automattic/newspack-network/commit/37f677aedd1cd9932bca942a84667c7e6315f784))
* bump limit of events pulled by nodes from to 2 to 20 ([#7](https://github.com/Automattic/newspack-network/issues/7)) ([c5fbdc5](https://github.com/Automattic/newspack-network/commit/c5fbdc56526034760081156208a49820de0f3d85))
* **node:** debug tools ([#12](https://github.com/Automattic/newspack-network/issues/12)) ([07b5470](https://github.com/Automattic/newspack-network/commit/07b5470b619a900a1cf264e24fead3ca6ff9cc5b))
* refactor to symetric crypto ([1f55551](https://github.com/Automattic/newspack-network/commit/1f555513cb282446c075f4e296711298feea12e8))
* sync author bio and meta ([#15](https://github.com/Automattic/newspack-network/issues/15)) ([f60236d](https://github.com/Automattic/newspack-network/commit/f60236dfb1324d8d7da7e09eb386047b7d02a3fa))
