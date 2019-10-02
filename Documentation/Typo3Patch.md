# How to patch TYPO3

Currently the TYPO3 version 10.1 still contains a bug for which a patch has already been provided, which is still in the review process:
https://forge.typo3.org/issues/87038

This extension uses data records in which the Labelkey field must be unique. If such a data set is translated, a digit is wrongly appended to the Labelkey.
However, since this is only a translation of the data set, TYPO3 should not change the value of the field.

The patch fixes this problem.

1. Install the sitegeist/translatelabels extension

```
$ composer require sitegeist/translatelabels
```

2. Add the composer plugin cweagans/composer-patches to your TYPO3 project:

```
$ composer require cweagans/composer-patches
```

3. Add the following lines into your composer.json of your TYPO3 project:


```json
	"extra": {
		"typo3/class-alias-loader": {
			"class-alias-maps": [
				"typo3/sysext/core/Migrations/Code/ClassAliasMap.php"
			],
			"always-add-alias-loader": true
		},
		"branch-alias": {
			"dev-master": "10.1.x-dev"
		},
		"enable-patching": true,
		"patches": {
			"typo3/cms-core": {
				"BUGFIX unique for fields with l10n_mode=exclude": "typo3conf/ext/translatelabels/Resources/Private/Patches/TYPO3.CMS/58979/69a0142_core.diff"
			}
		}
	},
```

Adjust the path of the patch relative to your composer.json file.

The patch file is included in sitegeist/translatelabels.

Check carefully if you already have an extra section in your composer.json file. If this is the case then you have to copy only the contents inside the extra section into your existing section.

4. Run `composer install` again.
