### 0.2.12
* modulecore: settings method as internal api
* modulecore: esc_url messing with referer
* blog: option key renamed to `general`
* cleanup: bump jquery core version

### 0.2.11
* code: `[github-repo]` moved and fixed
* comments: set default status for comment and ping on pages, [see](https://make.wordpress.org/core/2015/07/06/comments-are-now-turned-off-on-pages-by-default/).
* bbq: updated to [20150624](https://perishablepress.com/block-bad-queries/)
* admin: [autosize](http://www.jacklmoore.com/autosize/) updated
* admin: better enqueue script
* adminbar: removed `my-sites` text string
* modulecore: check for cron
* modulecore: warning for old options
* modulecore: escape referrer url
* mail: log all outgoing emails

### 0.2.10
* notify: fixed blogname method call
* notify: fixed per blog settings
* locale: `GNETWORK_WPLANG_ADMIN` to override admin language
* comments: disable comments per blog
* comments: blog comments report on settings
* modulecore: settings sidebox styling

### 0.2.9
* typography: correct slug for `[wiki]`
* typography: hex entity number for asterisks in `[three-asterisks]`
* network: add user to blog upon creation
* modulecore: add options to autoload
* themes: locale as body class

### 0.2.8
* modulecore: centralizing all options that results in fewer db queries
* code: new gist embed shortcode: `[github-gist]` using [gist-embed](https://github.com/blairvanderhoof/gist-embed)
* code: `[github-readme]` now grabs wiki pages and any markdown on the repo as well
* code: add `CONTRIBUTING.md` to the list of relative links
* debug: trying to catch cron errors
* buddypress: annoying tool box notice removed
* themes: [publish](https://github.com/kovshenin/publish) refinements
* themes: [hueman](https://github.com/AlxMedia/hueman) support
* blog: init module, redirect moved here
* redirect: module deprecated
* cleanup: removed [BruteProtect](http://bruteprotect.com/) annoying style and empty menu!

### 0.2.7
* media: attachment sizes by post type. must set `GNETWORK_MEDIA_OBJECT_SIZES`
* media: loging seperations in dev mode
* media: minified version of admin.media.js
* adminbar: adding textdomain to all strings

### 0.2.6
* users: change author select now restricted to blog users
* widgets: latest tweets widget removed
* adminbar: query info on admin too
* buddypress: fix fatal: no this in context

### 0.2.5
* language file updated
* opensearch: excluding tag injection on core rss export
* adminbar: new action to hook into plugin menu
* debug: new meta panel for [Debug Bar](https://wordpress.org/plugins/debug-bar/)
* buddypress: disable localizing on bp legacy templates
* buddypress: redirect users after signup
* modulecore: field class instead of class on register settings
* locale: BuddyPress language updated to 2.3.0
* shortcode: ref link with ltr control character
* themes: fix fatal: cannot access self:: when no class scope is active

### 0.2.4
* users: new module init
* users: default post author moved from editor
* users: default post author admin settings
* users: bulk change author from user to another by post types
* general: internal api for tinymce localization
* shortcodes: context attr for all shortcodes
* shortcodes: email shortcode now has a tinymce button and dialog box
* shortcodes: email shortcode now accepts subject and title
* shortcodes: new tel shortcode for phone numbers
* shortcodes: ref & reflist almost rewrite!
* admin: back-to-top removed in favor of adminbar click!
* adminbar: change default icon
* adminbar: rtl refinements
* taxonomy: new filters to extend bulk actions

### 0.2.3
* cleanup: disable emojis in WP 4.2
* shortcodes: siblings now excludes current post
* admin/overview: plugin paths
* buddypress: required fields check moved from buddypress.me

### 0.2.2
* languages: main pot & translation updated
* taxonomy: hiding the description of descriptions
* themes: now we can disable all front styles
* debug: refactoring debugbar panel

### 0.2.1
* languages: include mo files
* themes: css font include moved above

### 0.2.0
* github publish
