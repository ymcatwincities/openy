### What’s new in OpenY’s Carnation Theme?

Developers will be happy to know;

* Yarn has been chosen as the primary package management system.




	npm install
	npm ci
	yarn
	install without cache (without node_modules)
	61s
	60s
	46s
	install with cache (without node_modules)
	58s
	43s
	36s
	install with cache (with node_modules)
	10s
	46s
	2s
	

* Cleaned up theme structure (Templates, SASS & JS). 
   * About a 20% reduction in compiled CSS code.
* Use of jQuery.once(), so javascript events are only applied to a given element one time.
* Proper use of TWIG extends & includes (for reusable templating).
* Bootstrap library is built “on-the-fly” when the yarn package manager installs all of its needed node_modules.
* Breadcrumb support; long page titles will be trimmed to display “Current Page” to avoid breaking the theme structure.




Themers will be happy to know;


* Truly a mobile-first theme. All styling has been built to conform for mobile all the way up to large desktop.
* Bootstrap Upgraded (v4); Only a handful of drupal distributions have made this upgrade.
* Banner paragraph types now properly set the background images using CSS background property for better responsive support. 
* Fallback support for pages without dedicated header banners paragraphs.
   * Will display a grey banner with page title & breadcrumbs.
* SASS Colour audit has been performed and slight offset hues have been replaced with the proper OpenY colour pallette.




Administrators will be happy to know;

* A new user login, password reset and forgotten password pages.
* No configuration needed for carnation, just need to enable the theme.




________________




Themed Components (Paragraphs)


Initial release (8-x.1.13)




LABEL
	MACHINE NAME
	1 column
	1c
	2 columns
	2c
	3 columns
	3c
	4 columns
	4c
	All Amenities
	all_amenities
	Banner
	banner
	Blog Posts Listing
	blog_posts_listing
	Branches popup (All)
	branches_popup_all
	Branches popup (Class)
	branches_popup_class
	Camp menu
	camp_menu
	Categories Listing
	categories_listing
	Classes Listing
	classes_listing
	Classes Listing Filters
	classes_listing_filters
	Class location
	class_location
	Class Sessions
	class_sessions
	Code
	code
	Date Block
	block_date
	Embedded GroupEx Pro Schedule
	embedded_groupexpro_schedule
	Event Posts Listing
	event_posts_listing
	Featured Blog Posts
	featured_blogs
	Featured Content
	featured_content
	Featured Highlights
	featured_highlights
	Featured News Posts
	featured_news
	Gallery
	gallery
	Grid columns
	grid_columns
	Grid Content
	grid_content
	Group Schedules (GroupEx Pro)
	group_schedules
	Latest blog posts
	latest_blog_posts
	Latest blog posts (branch)
	latest_blog_posts_branch
	Latest blog posts (camp)
	latest_blog_posts_camp
	Latest news posts
	latest_news_posts
	Latest news posts (branch)
	latest_news_posts_branch
	Latest news posts (camp)
	latest_news_posts_camp
	Limited Time Offer
	lto
	Location filter by amenities
	location_filter_by_amenities
	Location finder
	prgf_location_finder
	Location finder filters
	prgf_location_finder_filters
	Membership calculator
	openy_prgf_mbrshp_calc
	Membership info
	membership_info
	Microsites Menu
	microsites_menu
	News Posts Listing
	news_posts_listing
	Program Registration (Daxko)
	program_registration
	Promo Card
	promo_card
	Schedule search form
	schedule_search_form
	Schedule search list
	schedule_search_list
	Secondary Description and Sidebar
	secondary_description_sidebar
	Session Time
	session_time
	Simple content
	simple_content
	Small Banner
	small_banner
	Social share icons
	addthis
	Social List
	social_list
	Story Card
	story_card
	Teaser
	teaser
	Upcoming Events
	upcoming_events
	Webform
	webform
