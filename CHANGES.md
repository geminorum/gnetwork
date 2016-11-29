### 3.5.1
* module/adminbar: :warning: fix fatal

### 3.5.0
* assets/js: passing tinymce object into mce plugins
* core/number: internal format handling
* main/modulecore: internal api for calling hooks
* main/modulecore: new methods for wrapping scripts
* main/modulecore: selector helper
* main/modulecore: non-jquery scripts array
* main/modulecore: check for xml-rpc and iframe
* main/settings: id/name generator callback
* main/settings: extra info on message strings
* main/utilities: layout folder moved
* main/utilities: enqueue from vendor folder
* module/adminbar: complete overhaul
* module/blog: :warning: fixed fatal on json feed template
* module/blog: :warning: global post missed
* module/blog: json feed unescaped unicode
* module/blog: json feed content filters
* module/blog: json feed skip empty terms
* module/blog: hide json feed link if disabled
* module/buddypress: complete overhaul
* module/buddypress: option to close directories for non logged-in
* module/buddypress: option to check for empty require fields
* module/buddypress: redirect signup page to register
* module/cleanup: :new: actions to clean up user meta
* module/cleanup: hiding default buttons
* module/cleanup: returning count rows
* module/code: removed `[github-repo]` shortcode
* module/code: fixed broken `[github]` shortcode
* module/code: `[github-gist]` updated to [blairvanderhoof/gist-embed](https://github.com/blairvanderhoof/gist-embed) v2.6
* module/comments: second attempt to archiving comments
* module/comments: using internal hook generator
* module/comments: moved [Growfield](http://code.google.com/p/jquery-dynamic/) to vendor folder
* module/debug: current time section on overview tab
* module/debug: missed printing the actual line!
* module/editor: accept registering button with no js file
* module/editor: table plugin moved to vendor folder
* module/editor: short-links for internal links
* module/editor: removed the pipe separators
* module/locale: check locale for ajax calls on network admin
* module/network: blog id column by default
* module/navigation: check for classes prop
* module/site: :new: extra contact method fields on user profiles
* module/site: option to disable user locale
* module/shortcodes: correct gmt timestamp and human time diff for `[last-edited]`
* module/shortcodes: rewrite `[pdf]` shortcode with [pipwerks/PDFObject](https://github.com/pipwerks/PDFObject) v2.0
* module/shortcodes: before/after atts
* module/shortcodes: editor button js moved
* module/shortcodes: moved editor buttons to second row
* module/shortcodes: mce string context
* module/shortcodes: discarding whitespace in page lists, [see](https://make.wordpress.org/core/?p=20577)
* module/themes: flexslider-rtl updated to [layalk/FlexSlider](https://github.com/layalk/FlexSlider) v2.6.1
* module/themes: skip on xml-rpc/iframe
* module/typography: editor button js moved
* misc/buddypress me: cover slug
* misc/buddypress me: better handling profile slug

### 3.4.0
* admin notice upon no autoload file
* assets/js: :up: autosize v3.0.17
* core/date: :new: core class
* core/number: :new: core class
* core/wordpress: extra args for getting users
* core/wordpress: more generic get posttypes
* main/modulecore: action/filter shorthand
* main/modulecore: settings form before/after helpers
* main/modulecore: php native to get the keys
* main/modulecore: display name & username on users dropdown
* main/modulecore: before/after args on shortcode wrap helper
* main/modulecore: setting type `roles` renamed to `cap`
* main/settings: field generator separated from module api
* main/settings: heading tag for the section intro
* module/network: large network is 1000 users by default
* module/admin: parent menu hack for non admin users
* module/blog: :new: all public taxes on JSON feed
* module/blog: :new: revised meta tag
* module/blog: skip redirect if admin
* module/blacklist: user ip summary as settings sidebox
* module/blacklist: earlier check for blacklisted
* module/blacklist: default message
* module/buddypress: cleanup tos inline styles
* module/buddypress/me: short link for edit user link filter
* module/cleanup: :new: purge orphaned featured image matadata
* module/debug: :warning: fixed notice when `phpinfo()` disabled
* module/debug: updated die handler from core
* module/debug: strip html tags before trim on error box
* module/debug: :new: ip summary on overview
* module/editor: wp page button in mce first row
* module/locale: using internal setting loader for loaded mo files
* module/locale: check for frontend ajax/tinymce
* module/login: skip loading custom css if saved on options
* module/media: thumbnail column for clean attachments table
* module/navigation: better styling
* module/restricted: default cap for access key operations
* module/restricted: open feed links in new window
* module/shortcodes: :new: `[last-edited]` shortcode
* module/shortcodes: html format i18n for ref texts
* module/themes: pluggable functions
* module/themes: :new: jquery cdn
* module/themes: better jquery on bottom of the page
* module/themes: moved jquery migrate removal
* module/themes: simplifing style enqueue
* module/themes: by line/posted on helpers
* module/themes: continue reading refactored
* module/themes: support for didi-lite theme
* module/taxonomy: search descriptions as well on admin edit terms page
* module/taxonomy: trim and normalize whitespaces on term insertion
* module/typography: :new: Arabic/Persian typography filters
* module/typography: :new: title word wrapper, [see](https://davidwalsh.name/word-wrap-mootools-php)
* module/update: disable async update translations
* module/users: roles tab to list caps
* module/users: :new: `[logged-in]`, `[not-logged-in]` shortcodes
* utilities: :new: update notice api

### 3.3.2
* html: skip strtolower on sanitizing tags
* html: escape textarea helper
* Arraay: correct check for needle in the haystack!
* modulecore: check the module for CLI
* modulecore: getting module key from class name if not defined
* adminbar: rethinking menu links
* blog: postpone redirect checking in favor of [WP Remote](https://wpremote.com/)
* blog: adding comments post file to the redirect whitelist
* debug: debug shortcut button on admin update page
* opensearch: caching headers
* restricted: skip on buddypress activation page
* restricted: drop old version support
* maintenance: skip unnecessary hooks
* mail: logging bp email
* mail: dropping mail from for bp email
* network: default plugins upon new blog
* notify: runs on cron
* comments: runs on ajax/cron
* comments: disable notify author
* locale: internal mo on network admin
* locale: short circuit mo overrides
* shortcodes: closing shortcode on empty ref
* taxonomy: preserve search in redirect

### 3.3.1
* restricted: fixed fatal: omitted class name changes
* bp me: fixed fatal: omitted class name changes
* shortcodes: fixed notice for global post object in `[in-term]`
* typography: new `[pad]`

### 3.3.0
* all: new folder structure
* all: refactoring localization strings
* modulecore: callback type for setting fields
* site: redirect to page if registration disabled
* utilities: deprecate github readme method
* taxonomy: quick edit glitch fixed
* widgets: selective disable of sidebar/dashboard widgets, inspired by [WP Widget Disable](https://wordpress.org/plugins/wp-widget-disable/)

### 3.2.0
* media: skip cleaning custom attachments
* shortcodes: sanitizing phone numbers, [see](http://www.billerickson.net/code/phone-number-url/)
* cleanup: purge old slugs
* blog: fix duplicate keys
* locale: Persian twentyfifteen updated
* themes: fixed setup theme on admin

### 3.1.0
* blog: redirect status code
* blog: option to disable/remove wp api, [see](http://wordpress.stackexchange.com/a/212472)
* themes: new content actions
* themes: option to load jquery in footer on front
* themes: custom body class
* themes: option to disable theme enhancements, `GNETWORK_DISABLE_THEMES` removed
* themes: rtl enhancements for [Twenty Fifteen](https://wordpress.org/themes/twentyfifteen/)
* search: prevent search bots from indexing search results
* restricted: hiding feed keys on user admin pages
* taxonomy: quick edit description
* taxonomy: new bulk action: empty description
* taxonomy: new bulk action: rewrite slug
* typography: title case post titles, [see](https://gist.github.com/geminorum/fe2a9ba25db5cf2e5ad6718423d00f8a)
* typography: option to disable editor buttons

### 3.0.0
* moved to [Semantic Versioning](http://semver.org/)
* moved to namespace structure
* comments: frontend autogrow, adopted from [Comment Autogrow](https://wordpress.org/plugins/comment-autogrow/) by [scribu](http://scribu.net/)
* comments: new comment type as `archived`, also hides and excluded from comments count
* themes: `GNETWORK_DISABLE_THEMES` constant

### 0.2.33
* media: media objects now cleans the attachments
* media: clean attachments on media row & bulk actions
* typography: do shortcode inside ltr shortcode
* login: pre login page body classes

### 0.2.32
* network: default email helper
* taxonomy: unnecessary escape on redirection
* shortcodes: new `[in-term]`
* shortcodes: new `[all-terms]`
* shortcodes: correct syntax for tel & sms links, [see](http://stackoverflow.com/a/19126326/4864081)
* mail: email & from defaults for `bp_mail()`
* debug: `phpversion()` & loaded extensions on overview
* cleanup: jQuery Updated, [see](https://make.wordpress.org/core/2016/03/29/jquery-updates-in-wordpress-4-5/)
* cleanup: checking script debug before disabling jquery migrate
* login: custom style setting

### 0.2.31
* debug: network settings tab for `phpinfo()`
* captcha: [recaptcha](https://www.google.com/recaptcha/) for comment forms
* shortcodes: new `[sms]`
* admin: [jacklmoore.com/autosize](http://jacklmoore.com/autosize) updated to v3.0.15
* media: draft for clean attachments

### 0.2.30
* modulecore: check for user admin
* shortcodes: new `[search]`
* shortcodes: keyboard shortcut for `[ref]`, `[email]`, `[search]`
* shortcodes: rewrite editor plugins based on [TinyMCE docs](http://archive.tinymce.com/wiki.php/Tutorials:Creating_a_plugin)
* typography: editor quote button
* code: [gist-embed](https://github.com/blairvanderhoof/gist-embed) updated to v2.4
* debug: simpler logs

### 0.2.29
* all: using exception for module constructors
* taxonomy: term management tools updated
* shortcodes: `[qrcode]` using [Google Chart Tools](https://developers.google.com/chart/infographics/docs/qr_codes)
* cleanup: purge transient moved and working!
* cleanup: `GNETWORK_DISABLE_EMOJIS` for overriding, default is true
* debug: display error logs

### 0.2.28
* refactoring main classes
* basecore: table helper from gEditorial
* constants: default `FS_CHMOD_DIR` / `FS_CHMOD_FILE`
* utilities: utilizing mustache template engine
* code: moved to [erusev/parsedown-extra](https://github.com/erusev/parsedown-extra)
* code: new `[textarea]` shortcode
* code: new `[shields-io]` shortcode
* tracking: track login pageviews
* login: credits badge, can disabled with `GNETWORK_DISABLE_CREDITS`
* blog: better check for redirect
* admin: autosize updated
* adminbar: display `$pagenow` global
* navigation: `gnetwork_navigation_replace_nav_menu` filter for dynamic strings on nav menu
* comments: option to disable form notes
* cleanup: purge akismet meta
* cleanup: purge comment agent field
* cleanup: optimize comment tables
* mail: sort and delete email logs
* bbq: updated to [20151107](https://wordpress.org/plugins/block-bad-queries/changelog/)

### 0.2.27
* locale: custom blog admin language
* utilizing composer

### 0.2.26
* shortcodes: reflist global style reset
* shortcodes: wrap option
* login: custom title/url for login logo
* login: adoption of [BruteProtect](http://bruteprotect.com/) math fallback

### 0.2.25
* locale: custom network admin language
* cron: unscheduling events
* network: github readme as overview

### 0.2.24
* all: `503`/`403` template helpers
* modulecore: notice updated/error helpers
* modulecore: options/buttons/scripts are now public
* tracking: support for [GA Beacon](https://github.com/igrigorik/ga-beacon) with shortcode
* tracking: override GA account by blog
* tracking: twitter site meta tag
* widgets: simple g+ badge
* widgets: simple quantcast data badge
* widgets: simple changelog legend
* blog: XML-RPC disable option
* blog: copyright page meta tag
* opensearch: another attempt to make suggestion work!

### 0.2.23
* locale: adminbar menus to quickly change current language
* locale: no actions/filters if no `GNETWORK_WPLANG`/`GNETWORK_WPLANG_ADMIN`
* utilities: cleanup current url helper
* utilities: html generator now sanitize classes
* tracking: analytics default domain to `auto`
* tracking: also ignoring editors in footer codes
* comments: quick tags in frontend
* modulecore: javascript in footer helper
* modulecore: new settings textarea with quick-tags
* restricted: support for json feed
* maintenance: support for json feed
* maintenance: fewer checks
* reference: also parsing ref list numbers with dash
* blog: fewer actions/filters
* admin: not loading in front end
* admin: `GNETWORK_IS_WP_EXPORT` when wp is exporting
* themes: excerpt link for revera
* debug: overview initial default constants
* dev: screen variables helper
* including [Kint](https://github.com/raveren/kint/) debugging library

### 0.2.22
* restricted: fixed fatal using old method
* themes: fixed fatal cannot access self
* modulecore: single checkbox desc as title
* debug: supporting user & term meta in debugbar

### 0.2.21
* cron: new module, initially just a list of scheduled tasks
* all: helper for site user id calls

### 0.2.20
* themes: excerpt link for hueman
* debug: upload path and server info
* locale: new tab and better listing loaded mo files
* utilities: new table side helper
* utilities: wrapper for file contents io
* mail: logging in json files
* mail: new tab & table for last 25 logged in json
* mail: table on test mail debug

### 0.2.19
* notify: `wp_new_user_notification()` changes again!
* all: safe json encoding with `wp_json_encode()`
* modulecore: check if module must load on frontend
* modulecore: drop check for old options

### 0.2.18
* modulecore: log only if `WP_DEBUG_LOG`
* modulecore: `is_network()` to override network behavior
* modulecore: `register_menu()` for conditional menus
* notify: 4.3 way of handling of credential
* shortcodes: check for feed in shortcodes
* code: check for feed in shortcodes
* admin: style adjusting for [WP4.3](https://make.wordpress.org/core/2015/07/31/headings-in-admin-screens-change-in-wordpress-4-3/)
* media: new helper for additional image sizes
* blog: custom 404 page, originally based on [kasparsd/custom-404-page](https://github.com/kasparsd/custom-404-page)
* blog: JSON feed, originally based on [Feed JSON](https://wordpress.org/plugins/feed-json/)
* blog: custom page template on frontpage
* utilities: check user cap before flush

### 0.2.17
* search: include authors removed
* locale: custom override actually working!
* locale: check on ajax calls
* media: attachment title by alt/caption of the image
* media: correct checking for initial thumbs on separation mode
* media: `GNETWORK_MEDIA_DISABLE_META` to disable saving meta (EXIF) data of the attachments
* admin: all options menu removed
* tracking: option to send userid data to analytics
* tracking: new initial `[google-plus-badge]`

### 0.2.16
* admin: internal api for tinymce plugins
* typography: editor button for asterisks
* shortcodes: `[person]` moved here from gpeople module
* shortcodes: format i18n for `[ref]` title attr
* shortcodes: correct title attr for `[reflist]` back to top
* shortcodes: tooltip support anchors
* gpeople: module removed
* debug: include the wp error object on the log

### 0.2.15
* cleanup: new `GNETWORK_DISABLE_JQUERY_MIGRATE` constant
* new module: site
* login: always checked moved to site module and disabled by default
* widgets: shortcode widget working!

### 0.2.14
* utilities: support new headings structure in WP4.3,  [see](https://make.wordpress.org/core/2015/07/31/headings-in-admin-screens-change-in-wordpress-4-3/)
* themes: new theme support for [Revera](http://www.fabthemes.com/revera/)

### 0.2.13
* debug: more details of http api debug log
* debug: log test cookie errors
* modulecore: internal log api
* shortcodes: using default `wp_audio_shortcode()` with wrapper
* shortcodes: new `[audio-go]` ([source](http://bavotasan.com/2015/working-with-wordpress-and-mediaelement-js/))
* themes: new theme support for [Ari](http://www.elmastudio.de/wordpress-themes/ari/)

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
