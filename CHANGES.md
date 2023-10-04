### 3.17.0
* asset/packages: vazirmatn ui fonts
* main/module: switch site lap
* main/scripts: register block asset
* misc/qm: object comments summary
* module/admin: styles for iframe
* module/blog: remove classic theme styles
* module/images: edit thumbnails separately
* module/optimize: :new: module
* module/optimize: better handling jquery enhancements
* module/shortcodes: :new: image shortcode
* module/taxonomy: :new: delete one-count terms
* module/taxonomy: drop support for description editor
* module/taxonomy: jquery deprecated method

### 3.16.2
* module/taxonomy: query similar terms by name/desc on term tabs
* module/typography: title sanitize enabled by default
* module/update: better check for zip mime-type

### 3.16.1
* main/module: :warning: check for multisite function
* main/module: auto hook for plugin loaded action
* module/cleanup: clean agents only for comment type
* module/cleanup: cleanup more user metas
* module/cleanup: drop obsolete db tables
* module/cleanup: more empty contact methods
* module/cleanup: more obsolete keys on postmeta
* module/commerce: delay pluggables after plugin loaded
* module/embed: custom ratio for aparat/balad
* module/image: move quality/format options
* module/notfound: check registered objects slugs
* module/search: default post search columns
* module/search: search context disabled by default
* module/taxonomy: taxonomy tabs enabled by default
* module/typography: constant for disable linkify content

### 3.16.0
* main/module: cache options by site id
* main/scripts: :up: autosize v6.0.1
* main/scripts: :up: prism.js v1.29.0
* main/settings: method for page excludes
* module/authors: force multi author option
* module/blog: avoid post classes on thrift mode
* module/blog: disable global styles
* module/buddypress: enqueue assets in bp pages only
* module/cleanup: removing orphaned post meta
* module/commerce: custom no products found message
* module/commerce: display mobile data on order details
* module/commerce: hide results count/catalog ordering
* module/commerce: ssn support removed
* module/cron: run hooks on front-end
* module/debug: better handling end flush on shutdown
* module/debug: wc log directory size via pointers api
* module/embed: support for balad maps
* module/feed: :new: module
* module/feed: json feed enhanced
* module/images: :new: module
* module/locale: avoid caching the default paths
* module/media: better handling alt/caption on attachment page titles
* module/notfound: better log 404s
* module/notfound: disable/strict guessing 404 permalinks
* module/notfound: display page state for the custom 404 page
* module/notify: disable auto update emails
* module/opensearch: manifest serving revised
* module/optimize: deprecated in favor of wp resource hints
* module/profile: get user from global user id
* module/restricted: check restricted on rest
* module/restricted: more details on robots text
* module/shortcodes: filtering headings toc for ref lists
* module/shortcodes: trim inside ref
* module/taxonomy: :new: term tab interface
* module/taxonomy: export terms revised
* module/taxonomy: force term id type on empty post action
* module/themes: jquery migrate version updated
* module/user: disable avatars across network
* module/user: display mobile info on admin
* module/user: enhanced search on admin

### 3.15.1
* main/module: rename helpers for request action
* main/module: render tools html after
* module/authors: authors manage tags
* module/blog: avoid empty http host on server global
* module/blog: control replace insecure home url by ssl setting
* module/branding: purpose prop for icons on the manifest
* module/code: general filter for markdown conversion
* module/comments: linkify content
* module/debug: summary card for space usage
* module/login: log logged-out username
* module/opensearch: :warning: avoid escaping templates
* module/optimize: :new: module
* module/optimize: support for preconnect links
* module/rest: allow all cors requests
* module/rest: move disable rest setting
* module/shortcodes: :new: circular player short-code
* module/shortcodes: callbacks for csv table cells
* module/shortcodes: css class for csv table
* module/taxonomy:  handle delete empty terms revised
* module/taxonomy: :new: format ordinal action
* module/taxonomy: :new: update count action
* module/taxonomy: :warning: avoid notice after term imports
* module/taxonomy: deferr updating count on actions
* module/taxonomy: simplify handle assign parents
* module/typography: linkify content
* module/user: large user count filtering
* module/user: total user count report on activity box end

### 3.15.0
* main/plugin: avoid escaping on the constant
* main/settings: better handling min/max/step on number inputs
* main/settings: template for value arg on fields
* main/utilities: :new: cached qrcodes
* main/utilities: internal api for cache folder
* misc/buddypress-me: exclude the page from sitemaps
* misc/qm: taxonomy terms of current object
* module/adminbar: core helper for current post rest route
* module/bbq: :up: v20220122
* module/bbq: explicit logging
* module/cleanup: filter post-meta obsoletes
* module/cleanup: multiple value post-meta obsoletes
* module/commerce: access cap check for tablelist
* module/commerce: exclude wc private pages from sitemap
* module/commerce: migrate to editorial
* module/debug: check for more common available functions
* module/editor: load separate core block assets
* module/embed: handle www on aparat
* module/legal: insert default ads.txt
* module/login: hide language switcher by default
* module/media: custom jpeg/webp compression quality
* module/media: exempt attachments from table list
* module/media: webp output format for image sub-sizes
* module/navigation: :new: include children as sub-menu
* module/navigation: support custom navs via filters
* module/notfound: exclude the page from sitemaps
* module/notfound: readable urls in the log
* module/notify: locale on reset password email link
* module/rest: filtering the thumbnail id
* module/rest: logic separation on init
* module/search: custom exclusion prefix
* module/site: body class for network
* module/taxonomy: :new: clone to taxonomy action
* module/taxonomy: :warning: correct termid/action on split terms
* module/taxonomy: explicitly count relations for each term before deletion
* module/taxonomy: optimize term queries
* module/taxonomy: prefix input names
* module/taxonomy: skip delete terms if desc is not empty
* module/taxonomy: support for desc field description string
* module/themes: auto enable/disable enhancements for supported themes
* module/typography: access cap optional for titles tablelist
* module/typography: actions for titles tablelist
* module/typography: more checks on term rewrite slugs
* module/typography: skip title word-wrap on rest
* module/user: :new: application password based on roles
* module/user: missing filter on authentication

### 3.14.0
* assets/js: :up: PDFObject v2.2.5
* assets/js: :up: prismjs 1.23.0
* module/adminbar: :warning: custom adminbar callback
* module/adminbar: link to all items posttypes
* module/commerce: admin bar menu for shop and attributes
* module/commerce: drop support for shetab cards
* module/debug: additional tests on accessing the log files
* module/dev: log canonical redirects
* module/login: math problem on wc login form
* module/media: disable space check on main site
* module/notfound: :new: module
* module/notfound: moved custom 404 page
* module/notfound: sidebox with 404 location info
* module/notfound: using correct hook for logging
* module/rest: :new: module
* module/search: :new: log search queries
* module/taxonomy: :new: custom query for terms
* module/taxonomy: filtering the term count before deletion
* module/theme: :up: jquery versions
* module/update: disable update tests on non main sites

### 3.13.1
* main/logger: missing level for sites
* main/logger: public actions to use for other plugins
* module/authors: disable sitemap for users
* module/bbq: :up: v20210211
* module/locale: avoid double checks for files
* module/locale: filtred bypassed domains
* module/taxonomy: handling messages in taxonomy tabs ui
* module/taxonomy: prefix secondary input names

### 3.13.0
* main/module: internal nonce field/check methods
* main/module: tablelist action conditional
* main/utilities: limit on get seperated
* misc/qm: current object taxonomy data
* module/bbq: :up: v20201209
* module/commerce: avoid deprection notice for data access
* module/commerce: custom hooks on products tablelist
* module/commerce: related products on tabs
* module/commerce: status column on tablelist
* module/cron: avoid undefined constant on setup actions
* module/profile: handle import file with custom name
* module/shortcodes: lazy loading for iframe shortcode
* module/taxonomy: :new: bulk action for splitting the terms by delimiter
* module/taxonomy: :new: import terms from csv file
* module/taxonomy: :new: support for default term
* module/taxonomy: :new: tab ui api for each taxonomy with exporting terms
* module/taxonomy: capability checks on taxonomy tabs api
* module/taxonomy: check type before splitting the target terms
* module/taxonomy: default option-key for wc categories
* module/taxonomy: default term key helper
* module/taxonomy: delete all terms on tools tab
* module/taxonomy: exclude default term from deletion
* module/taxonomy: filter meta data on export terms
* module/taxonomy: hook for handle tab content actions
* module/taxonomy: maintenance tab: delete empty terms
* module/taxonomy: make taxonomy tabs optional
* module/taxonomy: new tab for maintenance
* module/taxonomy: prefix meta column titles on export terms
* module/taxonomy: tabs if actions exists
* module/themes: initial support for astera theme
* module/themes: latest version of jquery
* module/typography: :new: title and slug tools page
* module/update: disable update checks on all non-main networks
* module/update: partial support for gitlab hosted
* module/user: disable application passwords
* module/user: multisite-aware settings

### 3.12.0
* main/module: custom postbox classes
* main/module: dashboard widget api refreshed
* main/module: get posts for tablelist as api
* main/plugin: refactoring the main plugin class
* main/settings: css class for empty strings
* module/bbq: :up: v20201123
* module/branding: :warning: previous hook not works on every permalink setup
* module/branding: manifest enabled by default
* module/buddypress: optional disable mentions
* module/cleanup: :warning: correct nounce on tools
* module/commerce: :new: optional gtin field with custom label
* module/commerce: :new: purchased products on the account page
* module/commerce: check for duplicate meta if user is not logged in
* module/commerce: custom string for in stock
* module/commerce: custom string for out of stocks
* module/commerce: fallbacks for empty measurements
* module/commerce: format numbers back on postcode/phone
* module/commerce: hide price options
* module/commerce: more fields options on checkout
* module/commerce: quantity price preview
* module/commerce: recalculate stock tools
* module/commerce: shetab card tweaks
* module/commerce: skip on non-releated locale
* module/commerce: ssn field
* module/commerce: validation patterns for mobile/ssn html fields
* module/dashboard: last logins on non-multisite
* module/debug: auto-loading debug-bar clasess
* module/debug: query monitor panel for current object
* module/locale: skip filtering empty script translations
* module/media: format slug on media file names
* module/notify: disable new site email
* module/opensearch: :warning: previous hook not works on every permalink setup
* module/profile: check for multisite on settings
* module/restricted: better naming for helper methods
* module/restricted: double sure on avoiding sitemap generation
* module/search: foolproof posts group by filter
* module/search: include terms on search
* module/search: refactoring include meta on search
* module/search: titles only search
* module/site: includes all sites on allowed redirect hosts
* module/taxonomy: :new: bulk action for assign parents to posts
* module/taxonomy: :new: handle multiple terms on merge action
* module/taxonomy: :warning: set term meta values correctly
* module/taxonomy: better handling targeted terms
* module/taxonomy: better handling term parents
* module/taxonomy: late deletion of the term on multiple merge
* module/taxonomy: simplify the term append on multiple merge
* module/themes: storefront: force woocomerce defaults
* module/themes: support for storefront theme
* module/themes: underscores credits helper
* module/typography: more extra shortcodes
* module/typography: move up format slugs
* module/update: better handling uri on remote packages
* module/update: check for cap and correct query arg on flush
* module/update: disable major updates by default
* module/user: :new: authenticate by mobile number

### 3.11.3
* module/update: :warning: correct class for empty transients

### 3.11.2
* assets/js: :up: prismjs 1.21.0
* main/logger: support for upcomming bot module
* main/module: loading providers revised
* main/provider: more defaults
* module/bbq: :up: 20200811
* module/code: cache invalid http responses for one hour
* module/embed: cache invalid http responses for one hour
* module/locale: core translation overrides revised
* module/shortcodes: default download string on audio
* module/typography: :new: bismillah shortcode
* module/update: :warning: core transients renamed
* module/update: avoid notice on empty pre-transients
* module/update: cache invalid responses for one hour

### 3.11.1
* main/module: better handling provider status
* main/module: default provider helper
* main/module: min cap for tools
* main/plugin: colors on brand helper
* main/provider: move default provider option into general settings
* main/scripts: enqueue styles from cdn
* main/settings: field description for supported placeholders
* main/settings: overriding code-editor settings
* main/utilities: empty html helper
* module/bbq: :up: 20200706
* module/blacklist: less log on skipped updates
* module/branding: custom adminbar styles
* module/branding: filter theme copyright text
* module/branding: network site icon on favicon requests
* module/cron: :warning: avoid notice on undefined hook
* module/cron: better handling ajax errors on manual status check
* module/cron: switch weekly to monthly schedules
* module/legal: :new: module
* module/login: code editor for styles field
* module/login: new theme: split-screen
* module/login: placeholder only with activated style class
* module/login: proper handling login footer action
* module/mail: append smtp server on logging
* module/mail: avoid manually instantiating phpmailer
* module/mail: better output handling on test form
* module/mail: correct filename on logging
* module/mail: logic separation on logging
* module/mail: refine logs on logging emails
* module/media : check if separation enabled before cleanup thumbs
* module/media: original size on overview images
* module/media: search box on attachment list
* module/opensearch: better handling manifest requests
* module/opensearch: better handling xml/json requests
* module/opensearch: proper handling of manifest url
* module/profile: :new: optional disabling of profile edits
* module/profile: check for option before disable password checkbox
* module/shortcodes: :warning: avoid format number on phones
* module/shortcodes: extra space after ref markup
* module/shortcodes: trim text on ref editor plugin
* module/site: fixed deprecated action
* module/site: resync entire network on cron
* module/update: check for force check on query
* module/update: remove token via query args

### 3.11.0
* main/logger: failed for network
* main/module: add dashboard widget api
* main/module: another pass at api for blocktypes
* main/module: auto hook dashboard setup
* main/module: better handling nonce in contexts
* main/module: default folder for script translations
* main/module: initial api for blocks
* main/module: prevent scripts for hidden widgets
* main/module: register shortcodes helper
* main/module: rename screen setup method
* main/module: word wrap for widget info
* main/module: wrap attr as html tag
* main/plugin: brand helper
* main/scripts: avoid enqueue on all
* main/scripts: correct cdn for gist-embed
* main/scripts: proper way to add rtl data for styles
* main/settings: custom wrap tags in field types
* main/settings: field after methods revised
* main/settings: new field type: quicktags tokens
* main/settings: version only on settings titles
* module/adminbar: avoid double linking custom styles
* module/blacklist: skip saving if remote content is the same
* module/branding: :new: linkify brand on the content
* module/branding: custom brand email
* module/branding: default brand name/url
* module/cron: simplify widget
* module/dashboard: blog public on pointers
* module/dashboard: skip default number format for precent sign
* module/dashboard: update message on pointers
* module/debug: correct cap for pointers
* module/debug: log size in pointers
* module/locale: override script translations
* module/locale: skip pages on network admin
* module/login: better ambiguous errors
* module/login: header text/url from branding
* module/login: logged in indicator
* module/mail: :warning: key correction
* module/media: intime init action
* module/media: purge meta for attachments
* module/media: simplify widget info
* module/media: widget notice on plupload ui
* module/navigation: filter for menu class
* module/search: :new: linkify hashtags
* module/search: skip single redirect on paged
* module/search: support for telegram hash-tag links
* module/shortcodes: :new: post-title blocktype
* module/shortcodes: linking post titles
* module/shortcodes: prevent orphans on refs
* module/support: :new: module
* module/support: empty values in form data
* module/support: trigger autosize after submit form
* module/taxonomy: check if tax is available on globals
* module/typography: :new: asterisks blocktype
* module/typography: more tweaks for arabic

### 3.10.0
* main/module: beta features constant
* main/module: column icon helper
* main/module: reorder register ment/tool args
* main/module: sidebox based on context
* main/scripts: :new: main
* main/settings: code editor field type
* main/settings: custom nbsp after submit buttons
* main/utilities: color picker helper
* main/utilities: custom kses helper
* main/utilities: initial support for async transients
* main/utilities: prep contact method
* module/admin: custom menu names
* module/admin: enable dark mode for query monitor
* module/authors: remove & relink author pages
* module/authors: status code for redirection
* module/commerce: :new: module
* module/dashboard: disable browser checks
* module/debug: extra system checks
* module/editor: custom block styles
* module/embed: :new: support instagram
* module/embed: applying custom count from attr
* module/embed: custom error message for channels
* module/glotpress: :new: dev module
* module/glotpress: setting for home title
* module/locale: exclude wordpress pages on admin
* module/locale: override default domain mo files
* module/locale: whitelist for network admin
* module/login: :new: dark style for login page
* module/media: remove accents on sanitize filename
* module/rewrite: :new: module
* module/shortcodes: better handling html on refs
* module/shortcodes: email shortcode on prep contacts
* module/shortcodes: more control on email shortcode
* module/shortcodes: ref shortcode: optional combine of identical notes
* module/taxonomy: avoid repeat hooking
* module/taxonomy: better handling bulk actions
* module/taxonomy: better handling edit descriptions
* module/taxonomy: better normalize whitespaces
* module/taxonomy: extra actions on single term edit
* module/taxonomy: informative placeholder on merge terms
* module/taxonomy: later check for nonce on description editing
* module/taxonomy: moved to handle bulk actions filter
* module/taxonomy: passing action into callback filter
* module/taxonomy: refine term management tools
* module/taxonomy: suffix for add form fields
* module/taxonomy: suffix for edit form fields
* module/taxonomy: support term id on merge terms
* module/theme: jquery summary
* module/themes: correct cap for settings
* module/themes: header/footer custom codes
* module/themes: simplify content wrapping
* module/typography: sanitize titles for term slugs
* module/update: transient filter changed
* module/user: :warning: fixed network roles not saving
* module/user: better option name
* module/user: custom display names as rows
* module/user: default role for all users on main site
* module/user: extra column
* module/user: link to tools tab on settings sidebox
* module/user: move up getting users with/without roles
* module/user: passing main site roles to setting
* :arrow_down: jquery 3.3.1
* :up: min php 5.6.20

### 3.9.3
* module/blacklist: log blacklisted
* module/blacklist: log cron events
* module/cron: failed status on failed logs
* module/notify: drop wraps around emails on logs
* module/themes: :up: jquery 3.4.0
* module/themes: late stylesheet for twentyeleven/twentytwelve

### 3.9.2
* module/blacklist: :new: schedule updates from remote content
* module/cron: always register weekly schedules
* module/site: :warning: register cron action only on main site

### 3.9.1
* main/settings: better check for network/site users
* module/cron: :new: missing actions on dashboard pointers
* module/restricted: correct check for site members
* module/site: :warning: resync cron only on the main site

### 3.9.0
* main/module: settings button only with settings options
* main/settings: passing default cap for dashboard widget
* main/settings: status options helper
* main/utilities: better handling layouts
* main/utilities: github markdown styles
* main/utilities: masonry helper
* main/utilities: redirect home helper
* module/admin: attachment mime-types on currents
* module/admin: avoid script for no tabs
* module/admin: check for custom cap on image sizes report
* module/admin: currents action hook
* module/admin: masonry layout for currents
* module/admin: reports on tools overview
* module/adminbar: account for xfn on menus
* module/authors: adding user site to current site
* module/authors: cleanup user site summary
* module/authors: do shortcode on shortcode contents
* module/blog: handling 404 on json feeds
* module/cleanup: clean core files
* module/cron: delete revisions before one week
* module/debug: masonry layout for currents
* module/debug: non static die handler
* module/login: header title renamed to text
* module/maintenance: :new: store layout as core page
* module/maintenance: before/after action hooks for default template
* module/maintenance: better option keys
* module/maintenance: core template only on main site
* module/maintenance: rethinking workflow
* module/maintenance: using header/footer from layouts
* module/media: handle uploaded filenames with normalizer
* module/navigation: default extra menu cap to subscribers
* module/navigation: enqueue script in meta box
* module/navigation: skip sites on non-multisite
* module/navigation: trim item lables
* module/profile: old and new contact methods
* module/restricted: better check for page now
* module/restricted: change default layout name to status
* module/restricted: complete overhaul
* module/restricted: fewer checks
* module/restricted: hide admin menu on profile
* module/restricted: profile link if member of site
* module/shortcodes: :new: shortcode for permalink
* module/shortcodes: return content on failure
* module/taxonomy: check if active editor defined
* module/tracking: support for restricted/maintenance pages
* module/tracking: switch to tags manager
* module/typography: more chars removed on sanitize titles
* module/typography: more words on arabic typography
* module/update: better handling main network
* module/update: cleanup after core update
* module/user: purge spam count after user delete

### 3.8.2
* main/module: hooking admin post actions
* main/module: more control over hooking ajax
* main/provider: conditional settings
* main/provider: log all default setting
* main/settings: switch side menu on tools/settings
* main/utilities: data log helpers
* main/utilities: log folder checks moved
* module/admin: more exclution for chosen
* module/adminbar: use constant for main network
* module/blog: hiding jetpack promotions
* module/login: disabling install page
* module/login: unblock access to admin post page
* module/mail: better handling raw email contents
* module/mail: log actions row
* module/mail: log table title
* module/mail: logging current user id
* module/mail: using path join for deletions
* module/sms: logging sms data
* module/tracking: :warning: fixed empty override
* module/typography: arabic within html tags

### 3.8.1
* module/admin: prior 5 check for block editor

### 3.8.0
* main/module: auto setup providers
* main/module: field as key on settings prep
* main/module: multi-network in menu urls
* main/module: providers as dashboard pointers
* main/plugin: debug log path support
* main/plugin: late constants
* main/plugin: unified ssl helper
* main/provider: unified arg names
* main/provider: working class on dashboard pointers
* misc/meta panel: un-html post-content
* module/admin: check if adminbar module exists
* module/admin: skip chosen on block editor
* module/adminbar: cron status on all admins
* module/adminbar: custom stylesheet for adminbar
* module/adminbar: multi-network support
* module/adminbar: network domain as node id
* module/bbq: adopted from last version
* module/blog: custom login/logout after url
* module/blog: force ssl status on plugins loaded
* module/blog: ssl for attachment urls
* module/brandig: skip empty data
* module/branding: background color for webapp manifest
* module/branding: manifest data filter
* module/cleanup: bulk purge site meta
* module/cleanup: more meta keys
* module/cron: ready cron jobs pointer
* module/dashboard: pointers api
* module/debug: better handling meta data panel
* module/debug: http api debug logs moved into failed
* module/debug: separete failed logs
* module/editor: skip buttons on block editor
* module/extend: active plugins on the summary
* module/extend: overview of current active themes
* module/locale: disable overrides constant
* module/login: break-down login page logic
* module/login: current network css class
* module/login: late use of constants
* module/login: loging logouts
* module/mail: better test mail subject
* module/mail: quick nav buttons to other subs
* module/maintenance: move to pointers api
* module/media: custom access for tools menu
* module/media: initial ui for ssl correction
* module/media: trim & i18n friendly basename
* module/notify: check for notify type
* module/restricted: dashboard pointer
* module/shortcodes: :new: post title shortcode
* module/shortcodes: :new: shortcode for menus
* module/shortcodes: :new: shortcode redirect
* module/shortcodes: support attachment id for pdfs
* module/site: bulk delete site meta
* module/site: resync sitemeta via cron
* module/site: rethinking site meta items
* module/sms: unifed arg names
* module/themes: current network as body class
* module/tracking: constant override for ga account
* module/tracking: remove support for gplus
* module/tracking: remove support for gplus
* module/tracking: twitter site tag even for editors
* module/typography: custom wiki options
* module/typography: extra title sanitization
* module/typography: general typography api
* module/typography: word wrap for widget titles
* module/update: browser download url for github
* module/update: custom main network
* module/update: disables updates on others than the main network
* module/user: using internal api for network sites
* provider/kavenegar: provider status

### 3.7.9
* main/module: default method for tools subs
* main/module: extra args on getting menu url
* main/module: multiple subs on settings/tools
* module/admin: time moved from system report
* module/authors: correct method for rendering tools
* module/authors: more info on site overview
* module/captcha: disable locale for wpcf7
* module/captcha: logging verify errors
* module/captcha: using wrap script helper
* module/cleanup: moved into tools
* module/cron: correct method for rendering tools
* module/debug: disable current image tools
* module/extend: :new: module
* module/mail: correct subject wrapping for display
* module/mail: display missing sites on logs
* module/mail: link to mail-tester.com
* module/mail: test/logs subs moved into tools
* module/mail: unescaped unicode on json logs
* module/mail: using correct method for settings actions
* module/media: images sub moved into tools
* module/navigation: stripping non-essentials from menu items
* module/notify: using correct method for settings actions
* module/profile: earlier check for user email on import
* module/profile: using correct method for settings actions
* module/site: using correct method for settings actions
* module/taxonomy: list bulk actions as help tab
* module/update: using correct method for refresh packages
* module/user: roles sub moved into tools

### 3.7.8
* module/taxonomy: filter for term rewrite slug
* module/typography: :warning: disabling sanitize title filter
* module/update: check for empty response on assets

### 3.7.7
* main/module: prep pre-custom setting fields
* main/module: safer assign options into globals
* main/utilities: constant for redirect 404 url
* module/admin: network enabled plugins on active view
* module/embed: disable embeds by default
* module/mail: check for recipient email before logging
* module/navigation: default menu id/class
* module/navigation: flush menu only on main site
* module/navigation: help tab summary of nav placeholders
* module/search: search again uri removed
* module/shortcodes: drop flash shortcode
* module/shortcodes: non breakable non space before ref
* module/shortcodes: template for ref numbers
* module/typography: non breakable non space before sup
* module/typography: remove space before ref
* module/typography: sanitize more chars on titles
* module/update: cleanup response data before transient
* assets/js: :up: PDFObject 2.1.1

### 3.7.6
* main/module: auto hook current screen actions
* main/settings: disabled field type
* module/admin: initial support for dark mode
* module/adminbar: methods on http calls
* module/blog: deprecated methods updated for wpcf7
* module/blog: disable admin pointers
* module/blog: disable emojies constant dropped
* module/blog: disable resource hints for emojis
* module/cron: rtl wrapper for notification email
* module/dashboard: distribute screen logics
* module/editor: enqueue underscore as dependency
* module/embed: add support for kavimo
* module/embed: disable oembed discovery
* module/embed: wrapping oembed html
* module/maintenance: new default layout for 503
* module/profile: restrict fields to core pages
* module/search: fixed search terms notice
* module/site: check options before sync meta button
* module/site: log ssl switch
* module/taxonomy: empty editor after adding new term
* module/taxonomy: enqueue underscore as dependency
* module/themes: body class for empty title posts
* module/themes: jquery latest from cdn
* module/typography: hide section titles on non single

### 3.7.5
* assets/js: :up: chosen 1.8.7
* assets/js: :up: prismjs 1.15.0
* assets/js: focus tab based on location hash
* main/module: scheme for menu url
* main/module: using user locale
* main/settings: branding logo on login logo link
* module/blog: skip local ssl verify if no ssl
* module/buddypress: better handling init
* module/buddypress: late check for open directories
* module/buddypress: remove translation notice
* module/code: cdn for gist embed shortcode
* module/code: drop github repo widget shortcode
* module/comments: moved to autosize from growfield
* module/cron: better handling status reporting
* module/cron: manual trigger buttons
* module/cron: using option autoload instead of transient
* module/dev: drop current screen info
* module/editor: :up: table plugin
* module/login: math problem on lost password form
* module/login: math problem on register form
* module/mail: check for log constant before filters
* module/mail: log failed emails

### 3.7.4
* main/plugin: using bbp hook for bbpress module
* main/module: moving away from `*_site_option` for clarity, [see](https://core.trac.wordpress.org/ticket/28290)
* main/utilities: better iso639 locale
* module/admin: rtl only styles
* module/admin: summary of site meta
* module/adminbar: :warning: correct rest base
* module/adminbar: get site name helper
* module/adminbar: separate styles
* module/bbpress: custom styles
* module/blog: ssl disabled constant
* module/blog: check if request not encoded for shortlinks
* module/captcha: recaptcha for buddypress register
* module/comments: block notify author
* module/cron: correct prop for status check callback
* module/debug: ssl constant summary on overview
* module/debug: log line not trimmed by default
* module/navigation: :new: global nav menu
* module/navigation: :new: sites section on metabox
* module/site: :new: sync site meta
* module/site: only sync current network
* module/site: store unserialized meta
* module/site: ssl enable/disable for main site
* module/themes: jquery cdn on ssl
* module/themes: hyde: remove unsecure font link
* module/themes: :new: hidden title for posttypes
* module/widgets: :new: widget for site icon
* misc/buddypress-me: hook to bp setup componnet

### 3.7.3
* main/module: tools page for admin/network
* main/module: better handling buttons
* main/module: help tab/sidebar tweaks
* main/settings: new side navigation
* main/utilities: 404 redirect helper
* module/adminbar: login node constant
* module/blog: is custom 404 constant
* module/blog: correct status for custom 404 page itself
* module/cron: moved summary into tools
* module/debug: moved menus into tools
* module/debug: correct check for html in die message
* module/notify: log updated version
* module/search: :new: `[search-form]` shortcode
* module/site: :new: initial support for ssl
* module/site: :warning: alternative to `NOBLOGREDIRECT`
* module/profile: also export meta/contacts
* module/login: :warning: missing filters for hide login
* module/login: skip redirect on admin email hash
* module/themes: content actions disabled by default
* module/themes: :warning: fixed not removing core scripts

### 3.7.2
* main/main: using psr-4 autoload
* main/plugin: moved constants
* main/plugin: delay pluggables to plugins loaded
* main/module: drop settings folder
* main/utilities: general iso639 helper
* module/branding: custom network logo/icon
* module/mail: moved test mail form into module
* module/media: sync ajax actions
* module/login: :new: hide login page
* module/shortcodes: drop google groups subscription shortcode
* module/themes: enhancements disabled by default

### 3.7.1
* module/admin: hide network-active plugins
* module/blog: disable xml-rpc headers
* module/branding: support theme color
* module/branding: support web app manifest
* module/branding: multisite awareness
* module/comments: removed archives draft
* module/debug: current mysql version report
* module/debug: current image tools report
* module/dev: support wp rest
* module/maintenance: support wp rest
* module/maintenance: activity box notice
* module/mail: correct display of html mail logs
* module/notify: more control over change emails
* module/profile: :new: import users from csv file
* module/profile: :warning: fixed not saving profile settings
* module/login: support math on wp login form helper
* module/restricted: support wp rest
* module/shortcodes: csv lib namespace changes
* module/shortcodes: initial support for amp
* module/taxonomy: char/word counter for desc editor
* module/taxonomy: localize count numbers
* module/taxonomy: avoid a tags on quick edit
* module/tracking: initial support for amp
* module/themes: initial support for amp

### 3.7.0
* main/logger: check for server address
* main/module: default method for cron events
* module/authors: check for edit user cap on sidebox info
* module/blog: :new: optional no found rows setting
* module/blog: :new: support for numeric shortlinks
* module/blog: :new: support for universal edit button
* module/buddypress: customized display name (from gMember)
* module/cleanup: fewer hooks to avoid loading on front
* module/dashboard: network signups/logins widgets (from gMember)
* module/dashboard: spam count on right now widget
* module/debug: closing db connection on shutdown
* module/debug: more info on uploads
* module/media: bypass cache on fallbacks
* module/media: BibTeX mime
* module/profile: new module (from gMember)
* module/login: last logins/redirect logout/disabled notice (from gMember)
* module/shortcodes: correct atts for people shortcode
* module/themes: check for logged in user locale
* module/themes: disable jquery migrate on admin
* module/update: :new: first attempt on remote updates
* module/uptime: limit logs on the widget
* module/user: spam count/timestamps on admin listtable (from gMember)
* module/user: setting ui for site user id/role

### 3.6.7
* main/main: incorrect install notice for non multisite
* main/main: check for min php
* main/module: internal api for using core strings as default
* main/module: passing description to setting methods
* main/logger: set remote ip if it's not the same as the server
* main/settings: support for multiple text fields
* main/utilities: parsdown extra as utility method
* module/admin: :new: metabox controls: toggle boxes
* module/adminbar: exclude current locale from switcher
* module/branding: custom slogan on admin footer
* module/blog: heartbeat dep as null
* module/blog: emoji from embed head
* module/blog: :new: [chosen](https://harvesthq.github.io/chosen/) as admin service
* module/buddypress: tool box for super admins only
* module/code: changelog type for github-readme shortcode
* module/dashboard: refactoring right now widget
* module/dashboard: remove akismet actions
* module/embed: switch to ssl for aparat urls
* module/embed: embed on ajax calls
* module/media: filter fallback for object sizes
* module/media: no default fallback for posttype sizes
* module/media: csv/pdf shortcode for insert none from modal
* module/notify: override signup user email
* module/site: overwrite default admin page access denied
* module/site: refactor access denied splash
* module/shortcodes: refactor csv shortcode
* module/shortcodes: check for rest
* module/themes: default embed style
* module/themes: support for Twenty Seventeen
* module/user: no help tab on user admin
* module/user: :new: global user navigation menu
* module/user: :new: admins to edit users of their sites

### 3.6.6
* main/plugin: postpone loading language till plugins loaded
* main/module: settings sidebox review
* main/module: internal api for custom setting
* main/logger: site helper methods
* main/utilities: prep title/desc helpers
* main/utilities: include wordpress custom format
* module/admin: unified admin body class filter
* module/adminbar: rewrite to avoid expensive queries
* module/adminbar: current post edit/rest/embed link nodes
* module/cleanup: user meta obsolete
* module/cleanup: empty geditorial series meta
* module/cron: refactoring ajax & check status
* module/cron: status on activity box end
* module/cron: :new: pre-configured tasks: weekly delete revisions
* module/dashboard: :new: right-now widget replica
* module/embed: refactoring aparat channel items
* module/login: optional ambiguous error
* module/login: override html title
* module/login: late log logged-in
* module/media: :new: large file uploader widget, [ref](https://github.com/deliciousbrains/wp-dbi-file-uploader)
* module/media: check headers for attachments on summary
* module/media: reverse order on summary
* module/media: refactoring cleanup procedures
* module/media: check meta context for site-icon attachments
* module/media: support uploading: psd/mobi/epub
* module/search: override default search form
* module/tracking: sanitize twitter handle
* module/uptime: :new: module
* module/user: :new: simple csv export
* module/user: current contact mehods as help tab

### 3.6.5
* main/module: diff only options as help tab
* main/module: extra attrs for shortcode wrap
* module/adminbar: strip tags from cron status message
* module/blog: override copyright notice
* module/branding: site icon fallback disabled by default
* module/branding: default action for credits
* module/buddypress: theme style for bp pages
* module/cleanup: disbale wpcf7 auto p
* module/code: correct processing wiki contents
* module/code: convert wikilinks
* module/debug: bp custom on system reports
* module/editor: correct statuses on link query
* module/locale: mo folder moved to ssets
* module/login: default class for login page
* module/media: optional strip exif metadata
* module/media: check if no additional image sizes
* module/notify: add new filters
* module/notify: not disabled by default
* module/notify: correct site name
* module/search: :new: include post meta in results
* module/site: new large user count hook
* module/shortcodes: ref overhaul

### 3.6.4
* main/module: default callback for settings
* main/module: exclude activate page from wpinstalling
* main/utilities: support separate rtl styles
* module/branding: custom copyright/powered texts
* module/cleanup: better purging transients
* module/debug: ltr wrap for cache stats/upload dirs
* module/embed: autolink on wrapped
* module/mail: check for folder on empty log summary
* module/mail: better sidebox
* module/maintenance: better handling admin
* module/media: whitelist uploading markdown text files
* module/media: correct size for thumbnail metabox
* module/navigation: refactoring
* module/site: using current network id for site
* module/themes: support for chosen
* module/themes: support for twentyeleven
* module/themes: tweaks for ari
* module/themes: tinymce styles folder
* module/themes: css class for posts with empty title
* module/tracking: internal api for shortcodes
* module/tracking: cleanup scripts
* module/typography: skip on empty titles
* module/user: respect constant for signup/activate styles

### 3.6.3
* main/ajax: :new: main class
* main/module: help tab to list module shortcodes
* main/module: using internal helper for help tab keys
* main/module: optional registering extra shortcodes
* main/settings: admin notices revised
* main/utilities: support timestamp for date edit row
* main/utilities: internal date format api
* module/admin: overview tabs
* module/adminbar: all blogs for all sites for super admins
* module/adminbar: fixed notice on api calls arrays
* module/authors: bulk change all users into one
* module/branding: :new: module
* module/code: :new: prismjs shortcode/tinymce plugin
* module/cleanup: :new: close comments/pings on posts before last month
* module/cleanup: blog obsolete options
* module/cleanup: post meta obsolete keys
* module/cron: run only on blog admin
* module/cron: status message on adminbar for super admins
* module/debug: :new: prismjs for syntax highlight
* module/debug: wp-config tab on system reports
* module/debug: htaccess tab on system reports
* module/debug: gnetwork-custom tab on system reports
* module/debug: phpinfo moved to system reports
* module/debug: php funcs on system reports
* module/embed: responsive iframe
* module/login: blog name before logs
* module/media: action hook for cleaning term image sizes
* module/media: thickbox for attachment summary links
* module/media: cleanup table for editors
* module/media: utilizing new bulk action hooks
* module/media: ajax cleaning action row
* module/media: skip storing image exif meta
* module/media: check if images are external on overview
* module/media: standalone menu for attachments
* module/media: inline ajax actions
* module/typography: epigraph/reverse for blockquotes
* module/widget: removing shortcode widget

### 3.6.2
* main/module: ajax action hook helper
* main/settings: support for links via submit button generator
* main/setting: new email field type
* module/adminbar: fixed fatal for older php versions
* module/blog: rest api enabled by default
* module/captcha: correct locale for wpcf7 recaptcha
* module/cron: :new: status check dashboard widget/failure email
* module/editor: :new: context for tinymce buttons
* module/media: support image size for all parent posttypes
* module/media: :new: support for taxonomy image sizes
* module/tracking: ga event for wpcf7 submit form
* module/themes: :new: support for omega theme
* module/themes: :new: support for p2-breath theme and o2 plugin

### 3.6.1
* main/module: :warning: cap check for phpinfo tab
* main/functions: fallback for gmeta
* module/adminbar: :new: wpcf7 reset messages node
* module/adminbar: :new: edit current wpcf7 form node
* module/blocklist: support for CIDR/wildcard
* module/blog: :new: thrift mode
* module/blog: :new: page/frequency for heartbeat api
* module/blog: :new: custom autosave intervals
* module/blog: referer on redirect logs
* module/comments: filter hidden comment types
* module/cleanup: :new: purge network obsolete options
* module/debug: meta debugbar for post arg in request
* module/embed: :new: support for docs pdf
* module/embed: :new: support for giphy.com
* module/locale: whitelist for network
* module/login: ambiguous error message
* module/restricted: :warning: fixed fatal
* module/site: initial post content for new blogs
* module/taxonomy: :new: transliterate slug action for terms
* module/themes: passing rtl into mce style url generator
* module/themes: :new: support Twenty Twelve
* module/themes: test with hueman v3.3.14
* module/themes: test with semicolon v0.9.1

### 3.6.0
* assets/js: :up: autosize 3.0.21
* main/constants: new cache ttl `GNETWORK_CACHE_TTL`
* main/plugin: better file loader
* main/module: priority for menus
* main/module: setup checks method
* main/provider: default settings helpers
* main/settings: not removing limit from request uri
* main/settings: correct check if exclude is an array
* main/settings: cpt/tax names along with labels
* main/settings: new wp page heading structure, [ref](https://make.wordpress.org/core/?p=22141)
* main/functions: using dashicon on footer powered
* main/admin: override admin title
* module/adminbar: check for error in nav menu term
* module/blog: log redirects
* module/blog: override content width global
* module/captcha: :up: migrate to recaptcha v2
* module/debug: post object on debugbar
* module/editor: code button
* module/embed: :new: new module
* module/opensearch: using internal constants for seconds
* module/media: check for images in content via curl
* module/media: shortlink also for audio/video attachments
* module/taxonomy: later hook for other plugins actions
* module/tracking: tab before additional lines

### 3.5.5
* main/plugin: seperate loop for modules
* main/modulecore: class on shortcode wrapper
* module/admin: customized style for inside editor iframe
* module/authors: default author now defaults to current user
* module/blacklist: new line as sep on the list
* module/blog: drop page template on frontpage, messes with loops
* module/captcha: logging empty/invalid logins
* module/captcha: logging failed comment answers
* module/dev: jetpack dev mode
* module/editor: default media uploader view, [props](https://wordpress.org/plugins/default-media-uploader-view/)
* module/login: no default css class
* module/mail: dropping wpms constants
* module/notify: again including phpass
* module/shortcodes: first attempt toward shortcake
* module/shortcodes: :new: `[google-form]` shortcode
* module/shortcodes: :new: `[button]` shortcode
* module/shortcodes: :new: `[thickbox]` shortcode
* module/shortcodes: loading string in iframe
* module/themes: user locale as body class
* module/themes: support [Tribes](https://www.competethemes.com/tribes/) theme

### 3.5.4
* plugin/uninstall: option deletion for site & blog
* main/constants: prevent extra redirect by trailing slash
* main/modulecore: check if sub action exists
* main/modulecore: sanitizing settings action key
* main/logger: overrride machine name with user ip
* main/settings: passing tag into after wrapper
* main/pluggable: again include phpass
* main/utilities: factoring css urls
* main/utilities: better handling custom files
* module/blog: disable rsd link/wlw manifest by default
* module/media: :warning: fixed notice!
* module/login: custom credits badge
* module/login: passing username to logger
* module/login: optional login log
* module/notify: optional override of the new blog email
* module/themes: filtering scandir exclusions
* module/user: facebook url as pre configured contact method

### 3.5.3
* main/plugin: not available string helper
* main/modulecore: skip saving default settings
* main/modulecore: settings hook helper
* main/modulecore: passing sub into settings buttons/help methods
* main/modulecore: module current saved options as help tab
* main/modulecore: check for wp installing
* main/logger: :new: new main class
* main/settings: submit button helper
* main/settings: rethinking after helpers
* main/settings: reverse enabled strings helper
* main/utilities: refactor get layout helper
* main/utilities: post title helper
* main/utilities: better handling time ago
* main/utilities: support for jquery time ago
* module/adminbar: more checks for shortlink menu
* module/authors: not caching logged in user shortcode messages
* module/authors: not hooking settings for the second time
* module/blog: :new: delay feed option
* module/blog: site default as option on admin locale
* module/blog: :warning: returning the rest disabled error!
* module/buddypress: redirect to signup for closed directories
* module/buddypress: default not to set avatar sizes
* module/dashboard: :new: user site list widget
* module/dashboard: posttype supports as help tab
* module/dashboard: :warning: misspelled hook name
* module/debug: new tab for analog logs
* module/debug: disabling debug logs with constant
* module/debug: list current http calls as adminbar submenu
* module/debug: refactoring wp die handler
* module/debug: list missing php ext on overview tab
* module/debug: ip lookup service for ip summary
* module/debug: reordering overview reports
* module/debug: download button for logs
* module/debug: new tabs for system report & remote tests
* module/dev: current blog name on the benchmark logs
* module/dev: push screen information help tab to the last
* module/dev: shutdown logger on cron/installing
* module/mail: disabling email logs by constant
* module/mail: old help tab removed
* module/mail: display multiple recipient on email logs
* module/media: using [jbroadway/urlify](https://github.com/jbroadway/urlify) to sanitize utf filenames
* module/media: send attachment shortlinks to the editor
* module/media: attachments table revised
* module/media: :warning: fixed not counting all posts
* module/navigation: refactoring the module
* module/network: :warning: empty admin menu notices fixed!
* module/network: no need to manually switch while updating admin email
* module/notify: pluggable updated
* module/notify: utilizing the logger
* module/login: :new: logging not correct math answers
* module/login: using wp hash instead of sha1 for math problem
* module/login: login error logging moved and with support for more error codes
* module/login: option to disable credits badge
* module/restricted: using wp hash/wp generate passwords for feed keys
* module/redtricted: avoid using plugin global
* module/site: :new: ip lookup service link api
* module/site: :new: override access denied admin page on network
* module/shortcodes: :new: `[csv]` shortcode draft
* module/taxonomy: :new: settings tab for current functionalities
* module/taxonomy: :new: handling meta after term merging
* module/taxonomy: correct html selectors for desc editor
* module/typography: more generic/arabic modifications
* module/typography: default title attr for wiki links
* module/user: :new: default user roles for each site, adopted from [thenbrent/multisite-user-management](https://github.com/thenbrent/multisite-user-management)
* module/user: overwrite signup/activate page default styles
* module/user: buddypress tos moved here
* module/user: :new: tos as user dashboard widget
* module/user: :new: tos on network signup page

### 3.5.2
* assets/js: :up: [jacklmoore/autosize](http://jacklmoore.com/autosize) v3.0.20
* main/modulecore: checking if must register ui hooks
* main/modulecore: better hook/hash/hash with salt helpers
* main/modulecore: using base prefix fo settings ui
* main/modulecore: key/context in hidden settings fields
* main/modulecore: :warning: fixed not passing bulk action into form fields
* main/settings: html wrap open/close helper
* main/settings: user dashboard url helper
* main/settings: check for cap before version display
* main/settings: new taxonomies field type
* main/wordpress: helper instead of deprecated is super admin
* main/utilities: more date helpers
* main/utilities: api for [ipinfo.io](http://ipinfo.io/) via [DavidePastore/ipinfo](https://github.com/DavidePastore/ipinfo)
* module/admin: check if register admin ui
* module/admin: register menu only on blog admin
* module/admin: context for localized strings on overview
* module/authors: module renamed from users
* module/blog: checking rest auth filter
* module/blog: no index meta tag for attachments
* module/code: better hashing the cache key
* module/comments: :new: option to hide trackback/pingbacks from comments
* module/debug: hiding the notice if cannot delete the log file
* module/login: using js exception on remember me checkbox
* module/maintenance: :warning: fixed fatal: using $this when not in object context
* module/media: more mime types for media views
* module/network: :new: reset site admin email in bulk
* module/network: more default options on new site
* module/notify: supporting switch locale based on user
* module/login: auto complete off for number input
* module/typography: :warning: fixed not wrapping the asterisks
* module/user: :new: new module
* module/site: non admin site hooks moved here
* module/shortcodes: last edited with simpler calls

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
* shortcodes: correct syntax for tel & sms links, [see](http://stackoverflow.com/a/19126326)
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
