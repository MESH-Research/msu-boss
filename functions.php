<?php
/**
 * @package Boss Child Theme
 * The parent theme functions are located at /boss/buddyboss-inc/theme-functions.php
 * Add your own functions in this file.
 */

if ( ! defined( 'BP_AVATAR_THUMB_WIDTH' ) ) define ( 'BP_AVATAR_THUMB_WIDTH', 150 );
if ( ! defined( 'BP_AVATAR_THUMB_HEIGHT' ) ) define ( 'BP_AVATAR_THUMB_HEIGHT', 150 );

/**
 * Sets up theme defaults
 *
 * @since Boss Child Theme 1.0.0
 */
function boss_child_theme_setup() {

	/**
	 * Makes child theme available for translation.
	 * Translations can be added into the /languages/ directory.
	 * Read more at: http://www.buddyboss.com/tutorials/language-translations/
	 */

	// Translate text from the PARENT theme.
	load_theme_textdomain( 'boss', get_stylesheet_directory() . '/languages' );

	// Translate text from the CHILD theme only.
	// Change 'boss' instances in all child theme files to 'boss_child_theme'.
	// load_theme_textdomain( 'boss_child_theme', get_stylesheet_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'boss_child_theme_setup' );

/**
 * removes dynamic css (set in boss options admin page) from page output entirely,
 * since we include all necessary rules ourselves in this child theme
 */
function boss_child_theme_remove_dynamic_css( $reduxFramework ) {
	// remove both instances of dynamic css: one from redux, one from boss
	remove_action( 'wp_head', 'boss_generate_option_css', 99 );
	remove_action( 'wp_head', array( $reduxFramework, '_output_css' ), 150 );
}
add_action( 'redux/loaded', 'boss_child_theme_remove_dynamic_css' );

/**
 * Enqueues styles for child theme front-end.
 */
function boss_child_theme_enqueue_style() {
	if (
		class_exists( 'Humanities_Commons' ) &&
		! empty( Humanities_Commons::$society_id ) &&
		file_exists( get_stylesheet_directory() . '/css/' . Humanities_Commons::$society_id . '.css' )
	) {
                $ctime =  filemtime( get_theme_file_path() . '/css/' . Humanities_Commons::$society_id . '.css' );
		wp_enqueue_style( 'boss-child-custom', get_stylesheet_directory_uri() . '/css/' . Humanities_Commons::$society_id . '.css', [], $ctime );
	}

}
// priority 200 to ensure this loads after redux which uses 150
add_action( 'wp_enqueue_scripts', 'boss_child_theme_enqueue_style', 200 );


/**
 * Enqueues scripts for child theme front-end.
 */
function boss_child_theme_enqueue_script() {
        $jtime = filemtime( get_theme_file_path() . '/js/boss-child.js' );
	wp_enqueue_script( 'boss-child-custom', get_stylesheet_directory_uri() . '/js/boss-child.js', [], $jtime );
}
// priority 200 to ensure this loads after redux which uses 150
add_action( 'wp_enqueue_scripts', 'boss_child_theme_enqueue_script' );

function boss_child_theme_enqueue_typekit() {
	wp_enqueue_script( 'typekit', '//use.typekit.net/bgx6tpq.js', array(), null );
	wp_add_inline_script( 'typekit', 'try{Typekit.load();}catch(e){};' );
}
add_action( 'wp_enqueue_scripts', 'boss_child_theme_enqueue_typekit' );

/**
 * some thumbnails have been generated with small dimensions due to
 * BP_AVATAR_THUMB_WIDTH being too small at the time. this is a temporary
 * workaround to prevent artifacts/blurriness where those thumbnails appear by
 * using the full avatar rather than the thumb.
 *
 * TODO once bad thumbnails have been replaced/removed, this filter should be
 * removed to improve performance.
 */
function hcommons_filter_bp_get_group_invite_user_avatar() {
	global $invites_template;
	return $invites_template->invite->user->avatar; // rather than avatar_thumb
}
add_filter( 'bp_get_group_invite_user_avatar', 'hcommons_filter_bp_get_group_invite_user_avatar' );

/**
 * affects boss mobile right-hand main/top user menu
 */
function boss_child_change_profile_edit_to_view_in_adminbar() {
	global $wp_admin_bar;

	if ( is_user_logged_in() ) {
		// the item which has the user's name/avatar as title and links to "edit"
		$user_info_clone = $wp_admin_bar->get_node( 'user-info' );
		// the item which has "Profile" as title and links to "view"
		$my_account_xprofile_clone = $wp_admin_bar->get_node( 'my-account-xprofile' );

		// use "view" url for the name/avatar item
		$user_info_clone->href = $my_account_xprofile_clone->href;
		$wp_admin_bar->add_menu( $user_info_clone );

		// remove the second, now redundant, item
		$wp_admin_bar->remove_menu( 'edit-profile' );
	}
}
// priority 1000 to override boss buddyboss_strip_unnecessary_admin_bar_nodes()
add_action( 'admin_bar_menu', 'boss_child_change_profile_edit_to_view_in_adminbar', 1000 );


/**
 * Handles ajax for the boss-child theme
 * @return void
 */
function boss_child_theme_ajax() {

	//this is for settings-general ajax
	$user = wp_get_current_user();
	$nonce = wp_create_nonce('settings_general_nonce');
	wp_localize_script( 'boss-child-custom', 'settings_general_req', [ 'user' => $user, 'nonce' => $nonce ], ['jquery'] );

}

add_action('wp_enqueue_scripts', 'boss_child_theme_ajax');

function boss_child_fix_redux_script_paths() {
	global $wp_scripts;
	foreach ( $wp_scripts->registered as &$registered ) {
		$registered->src = str_replace( '/srv/www/commons/current/web', '', $registered->src );
	}
}
add_action( 'admin_enqueue_scripts', 'boss_child_fix_redux_script_paths' );

function boss_child_turn_off_redux_ajax_save( $data ) {
	$data['args']['ajax_save'] = false;
	return $data;
}
add_filter( 'redux/boss_options/localize', 'boss_child_turn_off_redux_ajax_save' );

/**
 * This is dequeued by remove_messages_add_autocomplete_js_css(),
 * but Boss adds it back in buddyboss_scripts_styles().
 * Remove it on that action again.
 */
function hcommons_dequeue_bgiframe() {
	wp_dequeue_script( 'bp-jquery-bgiframe' );
}
add_action( 'wp_enqueue_scripts', 'hcommons_dequeue_bgiframe', 20 );

/**
 * Fixes css in admin for discussion forum metabox
 *
 * @return void
 */
function groups_discussion_admin_metabox() {

        echo '<style type="text/css">';
        echo '#bbpress_group_admin_ui_meta_box .field-group, #bbpress_group_admin_ui_meta_box p { max-width: 65% !important; float: left !important; }';
        echo '</style>';

}

add_action( 'admin_head', 'groups_discussion_admin_metabox' );

/**
 * Circumvent the signup allowed option to always show the register button in the header.
 * @uses Humanities_Commons
 */
function hcommons_filter_bp_get_signup_allowed( bool $allowed ) {
	if ( Humanities_Commons::backtrace_contains( 'file', '/srv/www/commons/current/web/app/themes/boss/header.php' ) ) {
		$allowed = true;
	}

	return $allowed;
}
add_filter( 'bp_get_signup_allowed', 'hcommons_filter_bp_get_signup_allowed' );

/**
 * overriding parent function to use group/member avatars in search results
 * TODO use group/member avatars in search results
 */
function buddyboss_entry_meta( $show_author = true, $show_date = true, $show_comment_info = true ) {
	global $post;

	// Translators: used between list items, there is a space after the comma.
	$categories_list = get_the_category_list( __( ', ', 'boss' ) );

	// Translators: used between list items, there is a space after the comma.
	$tag_list = get_the_tag_list( '', __( ', ', 'boss' ) );

	$date = sprintf( '<a href="%1$s" title="%2$s" rel="bookmark" class="post-date fa fa-clock-o"><time class="entry-date" datetime="%3$s">%4$s</time></a>', esc_url( get_permalink() ), esc_attr( get_the_time() ), esc_attr( get_the_date( 'c' ) ), esc_html( get_the_date() )
	);

	$author = sprintf( '<span class="author vcard"><a class="url fn n" href="%1$s" title="%2$s" rel="author">%3$s</a></span>', esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ), esc_attr( sprintf( __( 'View %s', 'boss' ), get_the_author() ) ), get_the_author()
	);

	// for bp avatars
	$args = [
		'item_id' => get_the_id(),
		'height' => 65,
		'width' => 65,
	];

	switch ( $post->post_type ) {
		case EP_BP_API::GROUP_TYPE_NAME:
			$args['type'] = 'group';
			$args = array_merge( $args, [
				'avatar_dir' => 'group-avatars',
				'object'     => 'group',
			] );
			$avatar = sprintf( '<a href="%1$s" rel="bookmark">%2$s</a>', esc_url( $post->permalink ), bp_core_fetch_avatar( $args ) );
			break;
		case 'reply':
		case 'topic':
		case 'humcore_deposit':
			$args['item_id'] = $post->post_author;
		case EP_BP_API::MEMBER_TYPE_NAME:
			$args['type'] = 'user';
			$avatar = sprintf( '<a href="%1$s" rel="bookmark">%2$s</a>', esc_url( $post->permalink ), bp_core_fetch_avatar( $args ) );
			break;
	}

	if ( empty( $avatar ) && function_exists( 'get_avatar' ) ) {
		$avatar = sprintf( '<a href="%1$s" rel="bookmark">%2$s</a>', esc_url( get_permalink() ), get_avatar( get_the_author_meta( 'email' ), 55 )
		);
	}

	if ( $show_author ) {
		echo '<span class="post-author">';
		echo $avatar;
		echo $author;
		echo '</span>';
	}

	if ( $show_date ) {
		echo $date;
	}

	if ( $show_comment_info ) {
		if ( comments_open() ) :
?>
				<!-- reply link -->
				<span class="comments-link fa fa-comment-o">
					<?php comments_popup_link( '<span class="leave-reply">' . __( '0 comments', 'boss' ) . '</span>', __( '1 comment', 'boss' ), __( '% comments', 'boss' ) ); ?>
				</span><!-- .comments-link -->
<?php
			endif; // comments_open()
	}
}
//display bbPress search form above sinle topics and forums
function mla_bbp_search_form(){

    if ( bbp_allow_search()) {
        ?>
        <div class="bbp-search-form">

            <?php bbp_get_template_part( 'form', 'search' ); ?>

        </div>
        <?php
    }
}

add_action( 'bbp_template_before_single_forum', 'mla_bbp_search_form' );


function mla_bbp_filter_search_results( $r ){

    $r['ep_integrate'] = false;

    //Get the submitted forum ID (added in gethub > bbpress > form-search.php)
    $forum_id = sanitize_title_for_query( $_GET['bbp_search_forum_id'] );

    //If the forum ID exits, filter the query
    if( $forum_id && is_numeric( $forum_id ) ){

        $r['meta_query'] = array(
            array(
                'key' => '_bbp_forum_id',
                'value' => $forum_id,
                'compare' => '=',
            )
        );

        $group_id = bbp_get_forum_group_ids( $forum_id );
        if( groups_is_user_member( bp_loggedin_user_id(), $group_id[0] ) )
        {
            function mla_allow_all_forums () { return true; }
            add_filter( 'bbp_include_all_forums', 'mla_allow_all_forums' );
        }
    }

    return $r;
}

add_filter( 'bbp_after_has_search_results_parse_args' , 'mla_bbp_filter_search_results' );


add_filter( 'body_class', 'mla_search_body_class' );

function mla_search_body_class( $classes ) {

   $name = null;
   if(bbp_is_search()) {
       $name = 'groups single-item';
   }

   return array_merge( $classes, array($name ) );
}

function mla_search_results_pagination( $args ) {
   global $wp_rewrite;

   $base = trailingslashit( get_permalink() );
   $group_slug = bp_get_current_group_slug();

   $base = $base . 'groups/' .  $group_slug . '/forum/search-forum/' .  user_trailingslashit( $wp_rewrite->pagination_base . '/%#%/' );
   $args['base'] = $base;

   return $args;
}

add_filter('bbp_search_results_pagination', 'mla_search_results_pagination');

$data = array(
            'key' => '58782ab2cd897a44bdada5bc87e84cce',
            'api_key' => 'iAvIQegQRmTSIlpl7fSQuPAa',
            'entity' => 'mla',
            'action' => 'processregistration',
            'registration' => 'hello',
        );

        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data)
            )
        );
    //    $context = stream_context_create($options);
  //      $result = file_get_contents('https://act.mla-dev.org/wp-content/plugins/civicrm/civicrm/extern/rest.php', false, $context);

//var_dump($result);
