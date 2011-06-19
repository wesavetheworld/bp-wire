<?php

class BP_Wire_Posts_Template {
	var $current_wire_post = -1;
	var $wire_post_count;
	var $wire_posts;
	var $wire_post;

	var $in_the_loop;

	var $pag_page;
	var $pag_num;
	var $pag_links;
	var $total_wire_post_count;

	var $can_post;

	var $table_name;

	function bp_wire_posts_template( $item_id, $component_slug, $can_post, $per_page, $max ) {
		global $bp;

		if ( $bp->current_component == $bp->wire->slug ) {
			$this->table_name = $bp->profile->table_name_wire;
		} else
			$this->table_name = $bp->{$bp->active_components[$component_slug]}->table_name_wire;

		$this->pag_page = isset( $_REQUEST['wpage'] ) ? intval( $_REQUEST['wpage'] ) : 1;
		$this->pag_num = isset( $_REQUEST['num'] ) ? intval( $_REQUEST['num'] ) : $per_page;

		$this->wire_posts = BP_Wire_Post::get_all_for_item( $item_id, $this->table_name, $this->pag_page, $this->pag_num );
		$this->total_wire_post_count = (int)$this->wire_posts['count'];

		$this->wire_posts = $this->wire_posts['wire_posts'];
		$this->wire_post_count = count($this->wire_posts);

		if ( is_site_admin() || ( (int)get_site_option('non-friend-wire-posting') && ( $bp->current_component == $bp->profile->slug || $bp->current_component == $bp->wire->slug ) ) )
			$this->can_post = 1;
		else
			$this->can_post = $can_post;

		$this->pag_links = paginate_links( array(
			'base' => add_query_arg( 'wpage', '%#%' ),
			'format' => '',
			'total' => ceil($this->total_wire_post_count / $this->pag_num),
			'current' => $this->pag_page,
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
			'mid_size' => 1
		));

	}

	function has_wire_posts() {
		if ( $this->wire_post_count )
			return true;

		return false;
	}

	function next_wire_post() {
		$this->current_wire_post++;
		$this->wire_post = $this->wire_posts[$this->current_wire_post];

		return $this->wire_post;
	}

	function rewind_wire_posts() {
		$this->current_wire_post = -1;
		if ( $this->wire_post_count > 0 ) {
			$this->wire_post = $this->wire_posts[0];
		}
	}

	function user_wire_posts() {
		if ( $this->current_wire_post + 1 < $this->wire_post_count ) {
			return true;
		} elseif ( $this->current_wire_post + 1 == $this->wire_post_count ) {
			do_action('bp_wire_loop_end');
			// Do some cleaning up after the loop
			$this->rewind_wire_posts();
		}

		$this->in_the_loop = false;
		return false;
	}

	function the_wire_post() {
		global $wire_post;

		$this->in_the_loop = true;
		$this->wire_post = $this->next_wire_post();

		if ( 0 == $this->current_wire_post ) // loop has just started
			do_action('bp_wire_loop_start');
	}
}

function bp_has_wire_posts( $args = '' ) {
	global $wire_posts_template, $bp;

	$defaults = array(
		'item_id' => false,
		'component_slug' => $bp->current_component,
		'can_post' => true,
		'per_page' => 5,
		'max' => false
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	if ( !$item_id )
		return false;
//print_r($defaults);
	$wire_posts_template = new BP_Wire_Posts_Template( $item_id, $component_slug, $can_post, $per_page, $max );
	return apply_filters( 'bp_has_wire_posts', $wire_posts_template->has_wire_posts(), &$wire_posts_template );
}

function bp_wire_posts() {
	global $wire_posts_template;
	return $wire_posts_template->user_wire_posts();
}

function bp_the_wire_post() {
	global $wire_posts_template;
	return $wire_posts_template->the_wire_post();
}

function bp_wire_get_post_list( $item_id = null, $title = null, $empty_message = null, $can_post = true, $show_email_notify = false ) {
	global $bp_item_id, $bp_wire_header, $bp_wire_msg, $bp_wire_can_post, $bp_wire_show_email_notify;

	if ( !$item_id )
		return false;

	if ( !$empty_message )
		$empty_message = sprintf(__("There are currently no %s posts.", 'bp-wire'),  strtolower (BP_WIRE_LABEL));

	if ( !$title )
		$title = BP_WIRE_LABEL;

	/* Pass them as globals, using the same name doesn't work. */
	$bp_item_id = $item_id;
	$bp_wire_header = $title;
	$bp_wire_msg = $empty_message;
	$bp_wire_can_post = $can_post;
	$bp_wire_show_email_notify = $show_email_notify;

	locate_template( array( '/wire/post-list.php' ), true );
}

function bp_wire_title() {
	echo bp_get_wire_title();
}
	function bp_get_wire_title() {
		global $bp_wire_header;
		return apply_filters( 'bp_get_wire_title', $bp_wire_header );
	}

function bp_wire_item_id( $deprecated = false ) {
	global $bp_item_id;

	if ( $deprecated )
		echo bp_get_wire_item_id();
	else
		return bp_get_wire_item_id();
}
	function bp_get_wire_item_id() {
		global $bp_item_id;

		return apply_filters( 'bp_get_wire_item_id', $bp_item_id );
	}

function bp_wire_no_posts_message() {
	echo bp_get_wire_no_posts_message();
}
	function bp_get_wire_no_posts_message() {
		global $bp_wire_msg;
		return apply_filters( 'bp_get_wire_no_posts_message', $bp_wire_msg );
	}

function bp_wire_can_post() {
	global $bp_wire_can_post;
	return apply_filters( 'bp_wire_can_post', $bp_wire_can_post );
}

function bp_wire_show_email_notify() {
	global $bp_wire_show_email_notify;
	return apply_filters( 'bp_wire_show_email_notify', $bp_wire_show_email_notify );
}

function bp_wire_post_id( $deprecated = true ) {
	global $wire_posts_template;

	if ( !$deprecated )
		return bp_get_wire_post_id();
	else
		echo bp_get_wire_post_id();
}
	function bp_get_wire_post_id() {
		global $wire_posts_template;

		return apply_filters( 'bp_get_wire_post_id', $wire_posts_template->wire_post->id );
	}

function bp_wire_post_content() {
	echo bp_get_wire_post_content();
}
	function bp_get_wire_post_content() {
		global $wire_posts_template;

		return apply_filters( 'bp_get_wire_post_content', $wire_posts_template->wire_post->content );
	}

function bp_wire_needs_pagination() {
	global $wire_posts_template;

	if ( $wire_posts_template->total_wire_post_count > $wire_posts_template->pag_num )
		return true;

	return false;
}

function bp_wire_pagination() {
	echo bp_get_wire_pagination();
	wp_nonce_field( 'get_wire_posts' );
}
	function bp_get_wire_pagination() {
		global $wire_posts_template;
		return apply_filters( 'bp_get_wire_pagination', $wire_posts_template->pag_links );
	}

function bp_wire_pagination_count() {
	echo bp_get_wire_pagination_count();
}
	function bp_get_wire_pagination_count() {
		global $wire_posts_template;

		$from_num = intval( ( $wire_posts_template->pag_page - 1 ) * $wire_posts_template->pag_num ) + 1;
		$to_num = ( $from_num + ( $wire_posts_template->pag_num - 1) > $wire_posts_template->total_wire_post_count ) ? $wire_posts_template->total_wire_post_count : $from_num + ( $wire_posts_template->pag_num - 1);

		return apply_filters( 'bp_get_wire_pagination_count', sprintf( __( 'Viewing post %d to %d (%d total posts)', 'bp-wire' ), $from_num, $to_num, $wire_posts_template->total_wire_post_count ) );
	}

function bp_wire_ajax_loader_src() {
	echo bp_get_wire_ajax_loader_src();
}
	function bp_get_wire_ajax_loader_src() {
		global $bp;

		return apply_filters( 'bp_get_wire_ajax_loader_src', $bp->wire->image_base . '/ajax-loader.gif' );
	}

function bp_wire_post_date( $deprecated = null, $deprecated2 = true ) {
	global $wire_posts_template;

	if ( !$deprecated2 )
		return bp_get_wire_post_date();
	else
		echo bp_get_wire_post_date();
}
	function bp_get_wire_post_date() {
		global $wire_posts_template;

		return apply_filters( 'bp_get_wire_post_date', mysql2date( get_blog_option( BP_ROOT_BLOG, 'date_format'), $wire_posts_template->wire_post->date_posted ) );
	}

function bp_wire_post_author_name( $deprecated = true ) {
	global $wire_posts_template;

	if ( !$deprecated )
		return bp_get_wire_post_author_name();
	else
		echo bp_get_wire_post_author_name();
}
	function bp_get_wire_post_author_name() {
		global $wire_posts_template;

		return apply_filters( 'bp_get_wire_post_author_name', bp_core_get_userlink( $wire_posts_template->wire_post->user_id ) );
	}

function bp_wire_post_author_avatar() {
	echo bp_get_wire_post_author_avatar();
}
	function bp_get_wire_post_author_avatar() {
		global $wire_posts_template;

		return apply_filters( 'bp_get_wire_post_author_avatar', bp_core_fetch_avatar( array( 'item_id' => $wire_posts_template->wire_post->user_id, 'type' => 'thumb' ) ) );
	}

function bp_wire_get_post_form() {
	global $wire_posts_template;

	if ( is_user_logged_in() && $wire_posts_template->can_post )
		locate_template( array( '/wire/post-form.php' ), true );
}

function bp_wire_get_action() {
	echo bp_get_wire_get_action();
}
	function bp_get_wire_get_action() {
		global $bp;

		if ( empty( $bp->current_item ) )
			$uri = $bp->current_action;
		else
			$uri = $bp->current_item;

		//if ( $bp->current_component == $bp->wire->slug || $bp->current_component == $bp->profile->slug ) {
			return apply_filters( 'bp_get_wire_get_action', $bp->displayed_user->domain . $bp->wire->slug . '/post/' );
		//} else {
		//	return apply_filters( 'bp_get_wire_get_action', site_url() . '/' . $bp->{$bp->active_components[$bp->current_component]}->slug . '/' . $uri . '/' . $bp->wire->slug . '/post/' );
		//}
	}

function bp_wire_poster_avatar() {
	echo bp_get_wire_poster_avatar();
}
	function bp_get_wire_poster_avatar() {
		global $bp;

		return apply_filters( 'bp_get_wire_poster_avatar',  bp_core_fetch_avatar( array( 'item_id' => $bp->loggedin_user->id, 'type' => 'thumb' ) ) );
	}

function bp_wire_poster_name( $deprecated = true ) {
	if ( !$deprecated )
		return bp_get_wire_poster_name();
	else
		echo bp_get_wire_poster_name();
}
	function bp_get_wire_poster_name() {
		global $bp;

		return apply_filters( 'bp_get_wire_poster_name', '<a href="' . $bp->loggedin_user->domain . $bp->profile->slug . '">' . __('You', 'bp-wire') . '</a>' );
	}

function bp_wire_poster_date( $deprecated = null, $deprecated2 = true ) {
	if ( !$deprecated2 )
		return bp_get_wire_poster_date();
	else
		echo bp_get_wire_poster_date();
}
	function bp_get_wire_poster_date() {
		return apply_filters( 'bp_get_wire_poster_date', mysql2date( get_blog_option( BP_ROOT_BLOG, 'date_format' ), date("Y-m-d H:i:s") ) );
	}

function bp_wire_delete_link() {
	echo bp_get_wire_delete_link();
}
	function bp_get_wire_delete_link() {
		global $wire_posts_template, $bp;

		if ( empty( $bp->current_item ) )
			$uri = $bp->current_action;
		else
			$uri = $bp->current_item;

		if ( ( $wire_posts_template->wire_post->user_id == $bp->loggedin_user->id ) || $bp->is_item_admin || is_site_admin() ) {
			if ( $bp->wire->slug == $bp->current_component || $bp->profile->slug == $bp->current_component ) {
				return apply_filters( 'bp_get_wire_delete_link', '<a class="item-button delete-post confirm" href="' . wp_nonce_url( $bp->displayed_user->domain . $bp->wire->slug . '/delete/' . $wire_posts_template->wire_post->id, 'bp_wire_delete_link' ) . '">' . __('Delete', 'bp-wire') . '</a>' );
			} else {
				return apply_filters( 'bp_get_wire_delete_link', '<a class="item-button delete-post confirm" href="' . wp_nonce_url( site_url( $bp->{$bp->current_component}->slug . '/' . $uri . '/wire/delete/' . $wire_posts_template->wire_post->id ), 'bp_wire_delete_link' ) . '">' . __('Delete', 'bp-wire') . '</a>' );
			}
		}
	}

function bp_wire_see_all_link() {
	echo bp_get_wire_see_all_link();
}
	function bp_get_wire_see_all_link() {
		global $bp;

		if ( empty( $bp->current_item ) )
			$uri = $bp->current_action;
		else
			$uri = $bp->current_item;

		if ( $bp->current_component == $bp->wire->slug || $bp->current_component == $bp->profile->slug ) {
			return apply_filters( 'bp_get_wire_see_all_link', $bp->displayed_user->domain . $bp->wire->slug );
		} else {
			return apply_filters( 'bp_get_wire_see_all_link', $bp->root_domain . '/' . $bp->groups->slug . '/' . $uri . '/wire' );
		}
	}
function bp_profile_wire_can_post() {
		global $bp;

		if ( bp_is_my_profile() )
			return true;

		if ( function_exists('friends_install') ) {
			if ( friends_check_friendship( $bp->loggedin_user->id, $bp->displayed_user->id ) )
				return true;
			else
				return false;
		}

		return true;
	}
function bp_is_wire_component() {
		global $bp;

		if ( BP_WIRE_SLUG == $bp->current_action || in_array( BP_WIRE_SLUG, (array)$bp->action_variables ) )
			return true;

		return false;
	}
function bp_is_profile_wire() {
		global $bp;

		if ( BP_XPROFILE_SLUG == $bp->current_component && 'wire' == $bp->current_action )
			return true;

		return false;
	}
function bp_is_group_wire() {
		global $bp;

		if ( BP_GROUPS_SLUG == $bp->current_component && $bp->is_single_item && 'wire' == $bp->current_action )
			return true;

		return false;
	}
function bp_wire_get_whose_wire_name($title=null){
if(!empty($title))
    return $title;
global $bp;
  if(bp_is_member ())
          $title= bp_is_home()?__("your","bp-wire"):bp_core_get_user_displayname($bp->displayed_user->id)."'s";
  else if(bp_is_active("groups")&&bp_is_group())
          $title=  bp_get_group_name()."'s";
   return $title;
}

/**
* Fo The Wire Feed
*/

function bp_wire_get_last_updated($component,$component_id){
   global $bp;
   return BP_Wire_Post::get_last_updated($component,$component_id)    ;
    
}

function bp_wire_get_post_permalink(){
global $bp;
if($bp->groups->current_group)
        $link= bp_get_group_permalink ($bp->groups->current_group).BP_WIRE_SLUG;
else
   $link= bp_core_get_user_domain ($bp->displayed_user->id).BP_WIRE_SLUG;

return $link."#post-".bp_get_wire_post_id();
}
function bp_get_wire_feed_item_date(){

return bp_get_wire_post_date();
}

function bp_wire_feed_item_description(){
    global $wire_posts_template;
    return bp_create_excerpt(bp_get_wire_post_content(), 120);
}

function bp_wire_feed_item_title(){
    return sprintf(__("%s says:",'bp-wire'),bp_get_wire_post_author_name());// bp_get_wire_post_content(), 55);
}


function bp_wire_get_feed_link($component=null){
    global $bp;
    if(!$component)
        $component=$bp->current_component;
    if($component==$bp->wire->slug)
            $link=bp_core_get_user_domain ($bp->displayed_user->id);
    else if(bp_is_active("groups")&&$component==$bp->groups->slug)
            $link=bp_get_group_permalink ($bp->groups->cutrrent_group);

    return $link.BP_WIRE_SLUG."/feed";
   
}
/* Put wire filter ability back in filter list */
function bp_wire_activity_filter_option() { ?>
	<option value="new_wire_post"><?php _e( 'Show Updates', 'bp-wire' ) ?></option>

<?php }
add_action( 'bp_activity_filter_options', 'bp_wire_activity_filter_option' );

?>