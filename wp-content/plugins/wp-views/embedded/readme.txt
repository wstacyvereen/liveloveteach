The Embedded version lets you display Content Templates and Views in your site, without requiring any extra plugin.

= Instructions =

1. Install like any other plugin into WordPress

2. Export your configuration from your development site. 
Go to the Views->Import/Export menu and click on the 'Export' button. 
You will receive a ZIP file with the XML and PHP configuration files (both are required).
Unzip that file and place both settings.xml and the setting.php into the root directory of this plugin.

You're done!

= Changelog =

v. 2.3.1
	- Improved the frontend sorting controls so sorting options can force a sorting direction.
	- Improved the frontend sorting controls so sorting options can force sorting direction labels.
	- Improved the usability of pagination controls as nav links by providing some canonical classnames to the HTML structure.
	- Fixed an issue that produced a console error after interacting with a View added dynamically to a page.
	- Fixed an issue that excluded some post classes from the [wpv-post-class] shortcode output when performing AJAX operations.
	- Fixed an issue where posts belonging to a deleted custom post type are among suggestions in WP Views Filter widget search.
	- Fixed an issue where a broken View edit link appears, when the selected View is trashed or deleted.
	- Fixed an issue on conditional output shortcodes when evaluating a missing field value for a parent post.

v. 2.3.0, AKA Wowbagger
	- Added frontend sorting controls for Views and WordPress Archives.
	- Added the capability of automatic detection of the native Customizer in the Views Framework integration.
	- Added the ability to sort by post type in Views and WordPress Archives, for WordPress versions 4.0 and higher.
	- Added a shortcode [wpv-post-menu-order] to display the menu order of posts.
	- Added a new attribute for the [wpv-post-field] shortcode to allow parsing of inner shortcodes.
	- Added a mode for using shortcode attributes in the search query filters.
	- Added the capability of whitelisting external domains and subdomains, to be used for redirecting in password management shortcodes.
	- Added support for using custom fields from a post parent in the Views conditional output.
	- Added the ability to render the raw post excerpts in the form in which hey are stored in the database, using the [wpv-post-excerpt] shortcode.
	- Added "Back" and "Close" buttons to the Fields and Views dialog.
	- Added the option to pause the automatic pagination transition on mouse hover.
	- Added extra information to the array being passed in public hooks to get the settings for a View.
	- Removed the datepicker localization files from WordPress 4.6, as they are now included from the core instead.
	- Improved the Fields and Views button and dialog to make them available to frontend editors.
	- Improved the Fields and Views dialog by separating the fields into better groups.
	- Improved the Fields and Views dialog by providing better options in the admin pages related to editing terms and users.
	- Improved the Fields and Views dialog by creating a first version API for manipulating Views shortcodes using JavaScript.
	- Improved the management of frontend assets by allowing the datepicker stylesheet to load only when needed.
	- Improved the behavior of sorting options to ensure consistency regardless of their source.
	- Improved support for the Content Templates in themes by filtering the PHP functions unable to be accepted as content renderers, and by providing better debug messages.
	- Improved the Views frontend debug tool by adding important query information related to WordPress Archives.
	- Fixed a glitch in the export/import mechanism that erased the stored data to recreate the Loop Wizard selected fields on imported sites.
	- Fixed the issue of id="$current_page" attributes not working on Views with AJAX pagination.
	- Fixed an issue that removed manually added URL parameters from frontend custom searches and paginations.
	- Fixed an issue with the Fields and Views shortcode for taxonomy labels containing a single quote.
	- Fixed the compatibility issue with the Download Manager plugin that was overwriting Download post type Content Templates.
	- Fixed a compatibility issue with Beaver Builder where the Views widgets were breaking the frontend editor.
	- Fixed the compatibility issue with Beaver Builder regarding the front-end preview for [wpv-view] and [wpv-post-author] shortcodes.
	- Fixed an issue with the filters "wpv_filter_start_filter_form" and "wpv_filter_end_filter_form" not being fired when rendering the form-only View shortcode.
	- Fixed the issue where details of the registered framework were not being hidden when a new framework was registered.
    - Fixed an issue where the "Views Integration" tab was being rendered empty when manually registering a theme options framework.
	- Fixed two issues with the Relevanssi integration, related to the ordering of the results under certain circumstances.
	- Fixed an issue where all the query filter attributes inserted by the Shortcode GUI were stripped off when only the View search form was inserted.
	- Fixed an issue with the importing of the Views settings, where WordPress Archives settings to include specific post types were not imported properly.
	- Fixed an issue with the search results not being reset properly in the case of rendering of a View form and results on the same page but with different shortcodes.
	- Fixed the issue with WP CLI (command line intrface) related to an improperly declared global.
    - Fixed an issue related to the WordPress Archive custom search when filtering by the same criteria that the current archive page is for.
	- Fixed the issue of the "Reset" button not fully clearing the URL when clicked on a filter form.
	- Fixed an issue related to the post relationship frontend filter when used on a WordPress Archive assigned to the Home/Blog archive page.
	- Fixed the issue associated with Views added inside an archive page missing the first post returned by that archive page.
	- Fixed the issue of the AJAX search form ignoring the override settings for ordering upon submission.
	- Fixed an issue where JavaScript errors occurred when using automatic pagination inside a nested View and the current View had only one page.
	- Fixed an issue that produced empty links in emails when recovering the password generated using Views shortcodes.
	- Fixed an issue that produced a PHP Warning under PHP 7.1, when using a search by post title filter.
	- Fixed an issue that occurred when the [wpv-post-field] shortcode was used to get the value from a field and the value was an array.
	- Fixed an issue with AJAX custom search causing frontend filters by post relationships to not inserting their query strings into the updated URL.
	- Fixed an issue caused by the translation of placeholders used to display specific data in custom search controls, like counters.
	- Fixed the issue with query filters by termmeta when comparing against a DATE value.
	- Fixed a compatibility issue between the legacy Views Maps add-on and third the party plugins loading the Google Maps API.
	- Fixed a compatibility issue with WooCommerce that caused the product archive pages to not display correctly.

v. 2.2.2
	- Improved compatibility with WordPress 4.7.
	- Improved compatibility with WPML 3.6.0.
	- Improved the GUI for WPML-related shortcodes.
	- Fixed an issue with the js_event_wpv_pagination_completed pagination event being triggered too early.
	- Fixed an issue with automatic pagination being manually altered using pagination controls.
	- Fixed some broken links to documentation.
	- Fixed an issue with the [wpv-forgot-password-form] shortcode when WooCommerce is active.
	- Fixed an issue with taxonomy labels containing quotes in the fields and Views dialog.
	- Fixed an issue with third party plugins using Colorbox by not setting globals.
	- Fixed an issue with select2 instances inside Colorbox modals regarding z-index styles.
	- Fixed a deprecation notice when using the [wpv-bloginfo show="text_direction"] shortcode.
	- Fixed an issue with API functions being called before init.

v. 2.2.1
	- Added decimal places support for Views meta query filters.
	- Fixed the Fields and Views dialog when using Visual Composer on Content Templates without Toolset shortcodes in the admin bar.
	- Fixed the frontend custom search filter by post relationship when two or more ancestors on the same level have the same post title.
	- Fixed the frontend custom search filter by Types checkboxes fields when used on a WordPress Archive.
	- Fixed a problem with getting data from parent posts using the id attribute on search results provided by the Relevanssi plugin.
	- Fixed a problem with localization for the password management shortcodes and their related forms labels.
	- Fixed a problem on manual pagination with page reload when the stored effect for AJAX pagination is infinite scrolling.
	- Fixed a problem with frontend filters for custom fields if they are using custom values containing commas.
	- Fixed a problem with Relevanssi searches not getting sorted by relevance.

v. 2.2, AKA Dr. Dick Somolon
    - Added Relevanssi compatibility with custom searches to support searching in some custom fields content.
	- Added Beaver Builder compatibility with Content Templates.
	- Added a shortcode [wpv-forgot-password-link] for Lost Password link.
	- Added a shortcode [wpv-forgot-password-form] for custom Forgot Password Form.
	- Added a shortcode [wpv-reset-password-form] for custom Reset Password Form.
	- Added support for selecting multiple roles in Views listing users on WordPress 4.4 and above.
	- Added a sortable ID column to admin listing screens for Views, Content Templates and WordPress Archives.
	- Added a sub-section in “Third-party shortcode arguments” to display custom shortcodes registered using Views' PHP API.
	- Added support for sorting a View listing users by usermeta fields.
	- Added support for a secondary sorting setting on Views listing posts.
	- Added two new rollover effects that replace the old slideUp and slideRight, to make them move in the right pagination order.
	- Added support for the user_nicename field value on the [wpv-user] shortcode.
	- Added the [wpv-theme-option] shortcode to the list of automatically registered shortcodes supported inside other shortcodes.
	- Removed the shortcode [wpv-forgot-password-link] from the GUI.
	- Improved the Views pagination settings GUI, following the schema we introduced for the WordPress Archives pagination settings.
	- Improved the Views and WordPress Archives editing experience by encouraging the usage of the Loop Wizard for new elements.
	- Improved the way we sanitize and validate slugs for Views, Content Templates and WordPress Archives, specially when they contain non-latin characters.
	- Improved the way we fire the automatic AJAX pagination in Views sliders, so we also support rollovers in nested Views.
	- Improved the user experience by renaming Views "parametric search" to "custom search" instead.
	- Improved support for HTTP/HTTPS based URLs output in the [wpv-post-featured-image] shortcode.
	- Improved support for image resize and crop in the [wpv-post-featured-image] shortcode.
	- Improved compatibility with PHP 7.
	- Fixed and improved Cherry Framework detection for the Views integrations.
	- Fixed an issue with shortcode attributes containing special characters used in a View with AJAX pagination.
	- Fixed an issue with AJAX pagination when a View search form and results are rendered using different shortcodes.
	- Fixed an issue with frontend custom search when it should get results without reloading the page and display all options for all filters.
	- Fixed an issue with frontend custom search when the form is placed on a widget and the results are in the main page content.
	- Fixed an issue with frontend custom search when a filter by post relationships is used on a WordPress Archive.
	- Fixed an issue with Views AJAX pagination and shortcode attributes with numeric values, when the View is rendered using the PHP API.
	- Fixed an issue with the content to display when there are no posts on a WordPress Archive.
	- Fixed the redirection for the [wpv-login-form] shortcode, specially in case of failed authentication attempts.
	- Fixed the ability to sort a View by a numeric field created outside Types.
	- Fixed the quick links on the Fields and Views dialog, to move between field sections.
	- Fixed a problem with taxonomy filters in custom searches used on another taxonomy archive loop.
	- Fixed a problem with custom search and frontend URL management when using a BETWEEN comparison for a custom field filter.
	- Fixed a problem with old Views combining pagination, limit and offset and failing to calculate the right number of pages.
	- Fixed an issue with datepickers on custom search filters not working after performing an AJAX pagination.
	- Fixed an issue with AJAX pagination on Views used in taxonomy archive pages containing query filters by the term set on the current archive.
	- Fixed an issue with WordPress Archives pagination controls, as ellipsis were getting the same classnames as the current page number.
	- Fixed a problem with the [wpv-current-user field="role"] shortcode when a superadmin does not have a specific role in a network site.
	- Fixed an issue with conditional shortcodes having a zero as their only content.

v. 2.1.1
	- Improved compatibility with WPML 3.5.

v. 2.1, AKA Clarisse McClellan
	- Added pagination settings to WordPress Archives, including AJAX pagination with fade, horizontal and vertical slide and infinite scroll effects.
	
	- Added query filters to WordPress Archives.
	
	- Added parametric search to WordPress Archives.
	
	- Added support for adjusting the post types to include on an archive page.
	
	- Added new settings to sort a View by a field as a number or a string.
	
	- Added a shortcode [wpv-logout-link] to display the logout link, with an option to redirect when logout is completed.
	
	- Added a new attribute redirect_url_fail to the [wpv-login-form] shortcode to allow redirection on login failure.
	
	- Added a new API filter wpv_filter_public_wpv_get_view_shortcodes_attributes for getting the current View shortcode attributes.
	
	- Added an option for sorting Views that list users by the order of the values passed to a query filter.
	
	- Added support for Types shortcodes with single or double quotes in the Loop Wizard.
	
	- Improved the security in frontend parametric search forms.
	
	- Improved the way we gather information about the current View parametric search filters for building our internal cache.
	
	- Improved the frontend AJAX for parametric search to avoid problems with nested Views structures.
	
	- Extended the post selection on the shortcodes GUI so you can set a post parent on broader situations.
	
	- Fixed an issue with Views AJAX pagination related to long URLs.
	
	- Fixed an issue with Views AJAX pagination related to installations with custom WordPress directory structures.
	
	- Fixed a bug in AJAX pagination and fade effect on Firefox that caused the content to bounce up and down the page.
	
	- Fixed an issue with the query filter by specific terms set by the current post on Views listing terms.
	
	- Fixed an issue with misleading data passed on the js_event_wpv_parametric_search_triggered JavaScript frontend event.
	
	- Fixed an issue with select dropdowns in parametric searches if a custom class is passed as an attribute.
	
	- Fixed an issue with some auxiliar hidden inputs on parametric search forms having duplicated class attributes.
	
	- Fixed an issue with the [wpv-attribute] shortcode returning an empty string after performing a parametric search.
	
	- Fixed an issue with the parametric search form action attribute, when the form and the results are displayed on different pages.
	
	- Fixed an issue with importing the Views and Content Templates extra CSS and JS content when it is made of line breaks only.
	
	- Fixed an issue with the parametric search reset button, that caused that sometimes the results were not updated when they should have been.

v. 2.0, AKA Cthulhu
	- Added Views to the new shared Toolset admin menu and Export / Import page.
	
	- Added the functionality to sort a View listing terms by termmeta.
	
	- Added a "format" attribute to the [wpv-post-excerpt] shortcode to control whether its output should be wrapped in paragraph tags.
	
	- Added an information tab to the [wpv-post-body] shortcode GUI.
	
	- Added a missing info="login" attribute value to the [wpv-current-user] shortcode.
	
	- Added a global setting to disable history management related to parametric search, and a specific setting to control this on each View.
	
	- Added a tolerance setting for the infinite scrolling AJAX pagination effect.
	
	- Added two new URL attributes, wpv_sort_orderby and wpv_sort_order, to control a View ordering.
	
	- Removed the Help page.
	
	- Improved the View output by removing some hidden inputs and reviewing ID attributes.
	
	- Improved the query filters that depend on the current page, current post and objects set by a parent View.
	
	- Improved the management of cached data, resulting in better performance.
	
	- Improved the parametric search by checking that all taxonomies involved do exist.
	
	- Improved the parametric search by avoiding counters to display double values when the form and the results are rendered using different shortcodes.
	
	- Improved the compatibility with WPML by disabling the Content Template selection when creating or editing a translation.
	
	- Fixed the combination of query filters using the post__in query argument.
	
	- Fixed an issue on the frontend Views API: it was not being loaded.
	
	- Fixed an issue on frontend pagination controls stopping events delegation.
	
	- Fixed an issue regarding AJAX pagination and parametric search, resulting in wrong pagination outcome.
	
	- Fixed an issue regarding WPML and query filters by specific post IDs, when setting values that belong to translated posts.

v. 1.12.1
	- Fixed an issue regarding parametric search, related to taxonomies no longer existing.
	
	- Fixed an issue regarding parametric search, related to results counters returning a wrong number.
	
	- Fixed a problem in AJAX pagination returning no results in the second and other pages.
	
	- Fixed a PHP notice on an undefined variable.
	
	- Fixed a PHP error on a file not loaded when needed.
		
	- Fixed a problem with the Divi page builder related to the Tolset shortcodes generator.
	
	- Fixed a compatibility issue between WPML and the parametric search, that returned results only in the default language.

v. 1.12, AKA Peter Pan
	- Added termmeta query filters to Views used to list terms.
	
	- Added support for Types termmeta integration to Fields and Views dialogs and Loop Wizards.
	
	- Added a Fields and Views button to a new shortcode generator in the admin bar.
	
	- Added an option to display the last modified date on the [wpv-post-date] shortcode by using a new "type" attribute.
	
	- Improved post relationship filter for a parametric search, so that only ancestors with actual descendants are shown as options, and counters are accurate.
	
	- Improved the output of Views by removing hidden inputs that are no longer needed.
	
	- Improved compatibility of Fields and Views dialogs with Layouts and Visual Composer.
	
	- Fixed a compatibility issue between Views parametric searches and server object caching.
	
	- Fixed an issue regarding the frontend cache of Views, related to Content Templates with custom CSS used inside a View.
	
	- Fixed an issue regarding AJAX pagination history events, and slider Views with numeric signatures.
	
	- Fixed an issue regarding AJAX pagination history events, and nested Views structures.
	
	- Fixed an issue regarding AJAX pagination on Views loaded through AJAX events, such as those in nested structures.
	
	- Fixed an issue regarding responsive Views output, and its debounce tolerance.
	
	- Fixed a problem in Views conditionals when one of the compared values is a number and the other is a string.
	
	- Fixed a compatibility issue with third party plugins that register 404 events.
	
	- Fixed a compatibility issue with Masonry-based themes, related to a forced Views output width set on a resize event.
	
	- Performed a security review on generic POSTed data for several AJAX calls.

v. 1.11.1
	- Improved the media management in preparation of the upcoming Toolset Maps plugin.
	
	- Fixed a problem when using AJAX pagination on the results of a parametric search.

v. 1.11, AKA Lady Jessica

	- Added infinite scrolling as an AJAX effect for the Views pagination.

	- Added proper target attributes to links used as pagination controls.
	
	- Added proper history management when using AJAX pagination and/or a parametric search with AJAX results.
	
	- Improved the dialogs to insert a View or a conditional shortcode into an editor.
	
	- Improved compatibility with Layouts related to Content Template cells.

	- Improved compatibility with WordPress related to admin layout structures.

	- Fixed an issue related to archive loops where some information was missing, resulting in some shortcodes not working.

	- Fixed an issue on wpv-post-body shortcodes not being parsed if they referred to non-existing Content Templates.

v. 1.10.1

	- Improved compatibility with WordPress 4.3.1.

v. 1.10, AKA Marty McFly
	
	- Added a new shortcode, wpv-conditional, for conditional output, along with a GUI for inserting it.
	
	- Added a new caching system for eligible Views in the frontend.
	
	- Added extra options when inserting a View to override limit, offset, order, and orderby settings.
	
	- Added extra options when inserting a View to set values for filters using a shortcode attribute.
	
	- Added a new shortcode wpv-theme-option to obtain the values of registered options when integrating a framework into Views.
	
	- Added a new method for detecting and registering the most used frameworks into the Views Integration.
	
	- Added a new shortcode, wpv-autop, to force formatting on pieces of content.
	
	- Improved the read-only page for a View when using the embedded mode, so that it shows the Content Templates assigned to it.
	
	- Improved the wpv-user shortcode so that it can be used outside the loop of a View that lists users.
	
	- Improved compatibility with WordPress 4.3 by removing PHP4 class constructors.
	
	- Improved compatibility with WordPress 4.3 by adjusting the admin pages to the new structures.
	
	- Improved the internal APIs with several new actions and filters.
	
	- Migrated almost all dialogs from Colorbox to jQueryUI Dialogs.
	
	- Fixed the query filter by post date when the selected date can have an ambiguous meaning.
	
	- Fixed several typos and updated old texts.

v. 1.9.1
	
	- Restored the functionality affected by the WordPress 4.2.3 update.

v. 1.9, AKA Meina Gladstone

	- Added a GUI for inserting Views shortcodes.
		
	- Added class and style attributes to several shortcodes that output HTML tags.
	
	- Added a new shortcode wpv-noautop to display pieces of content without paragraph formatting - included a Quicktag button for easy insertion.
		
	- Added a new debug output to the wpv-if conditional shortcode.
				
	- Improved the combination of limit, offset and pagination settings on a View to avoid expensive auxiliar queries.
	
	- Improved the output of custom CSS and JS for Views and Content Templates - HTML comments should make it easier to identify their source.
	
	- Improved the frontend javascript that controls the pagination, the parametric search interaction and the table sorting.
	
	- Improved the Views AJAX pagination when using a custom spinner - avoided enforcing fixed dimensions and improved the positioning of the spinner.
	
	- Fixed the WordPress media shortcodes (audio, video, playlist) when used on Views with AJAX pagination or with parametric search with automatic results.
	
	- Fixed lower-than comparison functions for date, custom field and usermeta field query filters - a previous security review broke them.
	
	- Fixed the Views pagination spinner “No spinner” setting.
		
	- Fixed edit View links on Views widgets when using the Views Embedded plugin.
		
	- Fixed the query filter by specific users on a View listing users - the URL parameter mode was not being applied.
	
	- Fixed the “Don’t include current page” setting on a View when it is used on a post displayed on an archive page.
	
	- Fixed the API functions to display a View or return its results - avoided errors by checking that the requested item is a published View.
	
	- Improved some shortcodes attributes, like the ones for wpv-post-taxonomy, wpv-post-featured-image and wpv-post-edit-link.
	
	- Improved the compatibility with WPML by setting better translation settings to some private custom fields used to store Views settings.
	
	- Improved the compatibility with WPML by adjusting AJAX pagination when adding language settings as URL parameters.
	
	- Improved the compatibility with 4.2 related to loading spinners.
	
	- Improved the compatibility with 4.2 related to the link Quicktag dialog.
	
	- Improved the compatibility with RTL languages.

v. 1.8.1

	- Fixed an inconsistency on query filters getting values from shortcode attributes - empty values should apply no filter.
	
	- Fixed a bug on Views listing users and filtering by specific users set on a URL parameter.
	
	- Fixed a bug about "lower than" options on the query filters by custom field, usermeta field and post date.
	  https://wp-types.com/forums/topic/custom-date-filter-stopped-working/
	
	- Fixed the frameworks integration - frameworks using an option to store values were not registered correctly.
		
	- Improved the compatibility with Layouts related to archives pagination.

v. 1.8, AKA R2-D2

	- Added a complete GUI for the Views Embedded plugin.
	
	- Added a new API function is_wpv_content_template_assigned.
	
	- Added a new API function is_wpv_wp_archive_assigned.
	
	- Improved the import workflow.
	
	- Improved the compatibility with WordPress 4.1 related to meta_query entries for custom fields and sorting by meta values.
	
	- Improved the compatibility with WordPress 4.2 related to term splitting.
	
	- Improved the compatibility with WordPress 4.2 related to cache objects for taxonomies.
	
	- Improved the compatibility with WordPress 4.2 related to accessing the global $wp_filter.
	
	- Changed the Google Maps script register handler for better third-party compatibility.
	
	- Fixed an issue about filtering by custom field values containing a plus sign.
	
	- Fixed an issue about filtering by Types checkboxes fields - extended support for complex queries in WordPress 4.1+.
	
	- Fixed an issue about multiselect items on a parametric search - avoid force selecting the first option by default.
	
	- Cleaned some deprecated code.

v. 1.7, AKA Zaphod Beeblebrox

v. 1.6.2

	- First release as standalone plugin.