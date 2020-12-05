<?php
/**
 * Plugin Name: OPENED CUSTOM ITEMS
 * Plugin URI: https://github.com/
 * Description: allow iframes & much more - details in plugin comments
 * Version: 1.0
 * Author: Tom Woodward
 * Author URI: http://bionicteaching.com
 * License: GPL2
 */

 /*   2015 Tom Woodward   (email : bionicteaching@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );


/*-------------------------------------------FIX COMMENT NAMES TO REFLECT DISPLAY NAMES-------------------------------------------*/
//make sure comments reflect display name from https://wordpress.stackexchange.com/questions/31694/comments-do-not-respect-display-name-setting-how-to-make-plugin-to-overcome-thi
add_filter('get_comment_author', 'opened_comment_author_display_name');
function opened_comment_author_display_name($author) {
    global $comment;
    if (!empty($comment->user_id)){
        $user=get_userdata($comment->user_id);
        $author=$user->display_name;
    }
    return $author;
}

/*-------------------------------------------NEW FILE TYPES ALLOWED HERE-------------------------------------------*/
//allow some additional file types for upload
function opened_custom_mime_types( $mimes ) {

        // New allowed mime types.
        $mimes['svg'] = 'image/svg+xml';
        $mimes['svgz'] = 'image/svg+xml';

        // Optional. Remove a mime type.
        unset( $mimes['exe'] );

    return $mimes;
}
add_filter( 'upload_mimes', 'opened_custom_mime_types' );


/**
 *  Remove the h1 tag from the WordPress editor. This is for accessibility compliance help as 99% themes use the H1 and there should only be one.
 *
 *  @param   array  $settings  The array of editor settings
 *  @return  array             The modified edit settings
 */

function opened_format_TinyMCE( $in ) {
        $in['block_formats'] = "Paragraph=p; Heading 2=h2; Heading 3=h3; Heading 4=h4; Heading 5=h5; Heading 6=h6;Preformatted=pre";
    return $in;
}
add_filter( 'tiny_mce_before_init', 'opened_format_TinyMCE' );



/*------------------------------------ENABLE CSS FOR ADMINS-------------------------------------------------*/
//from https://wordpress.org/plugins/multisite-custom-css/ just didn't want another plugin

add_filter( 'map_meta_cap', 'opened_multisite_custom_css_map_meta_cap', 20, 2 );
function opened_multisite_custom_css_map_meta_cap( $caps, $cap ) {
    if ( 'edit_css' === $cap && is_multisite() ) {
        $caps = array( 'edit_theme_options' );
    }
    return $caps;
}



/*------------------------------------H5P  ---------------------------------------------------*/
// Make all H5P embeds mobile friendly 
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

if ( is_plugin_active(  'h5p/h5p.php' ) ) {
  //plugin is activated
     add_action('wp_enqueue_scripts', 'opened_h5pflex_widget_enqueue_script');
}

function opened_h5pflex_widget_enqueue_script() {
    $h5p_script = plugins_url( 'h5p/h5p-php-library/js/h5p-resizer.js', __DIR__);
    wp_enqueue_script( 'h5p_flex', $h5p_script, true );

    }


/*---------------------------------JSON MOD FOR ADDITIONAL SITE INFO----------------------------------*/

function opened_extra_json_data($response){
    $blog_id = get_current_blog_id();
    $blog_details = get_blog_details($blog_id);
    $data = $response->data;
    $data['created'] =$blog_details->registered;
    $data['last_updated'] =$blog_details->last_updated;
    $data['post_count'] =$blog_details->post_count;
    $response->set_data($data);
    return $response;
}

add_filter('rest_index', 'opened_extra_json_data');



/*------------------------ FILTERING GRAVITY FORMS CONFIRMATION MESSAGE TO ALLOW VARIABLE BUT REMOVE SCRIPTS ETC--------------------------------------*/

add_filter( 'gform_sanitize_confirmation_message', '__return_true' );



/*------------------------ ADD REMOVE SELF FROM MY SITES LIST --------------------------------------*/

function hidden_blogs($user_id){
    //THIS SHOULD GO TO THE USER PROFILE MAYBE AND GET THE LIST -- DOES FILTER MY SITES PAGE AND DROP DOWN
    $hidden_blogs = get_user_meta($user_id, 'my_hidden_sites', true); 
    return $hidden_blogs;
}

function remove_selected_blogs_from_get_blogs($blogs) {
    global $pagenow; //mostly works to allow full list on profile page but filter elsewhere
    $newblogs = array();
    $user_id = wp_get_current_user()->ID;
    $hidden_blogs = explode(",",hidden_blogs($user_id));
    if ( $pagenow != 'profile.php') { // remove !is_super_admin() &&
        foreach ($blogs as $key => $value) {
            if (!in_array($value->userblog_id, $hidden_blogs) )
                $newblogs[$key] = $value;
        }
        return $newblogs;
    } else {
        return $blogs;
    }
}
add_filter( 'get_blogs_of_user', 'remove_selected_blogs_from_get_blogs' );


//add sites interface to user profile field
add_action( 'show_user_profile', 'hidden_site_user_profile_fields' );
add_action( 'edit_user_profile', 'hidden_site_user_profile_fields' );

function hidden_site_user_profile_fields( $user ) { ?>
    <span class='dashicons dashicons-hidden big-eye'></span>
    <h3><?php _e("Hide a Site?", "blank"); ?></h3>
    <?php opened_get_user_sites($user->ID);?>
    <table class="form-table">
    <tr>
        <th><label for="my_hidden_sites"><?php _e(""); ?></label></th>
        <td>
            <input type="hidden" name="my_hidden_sites" id="my_hidden_sites" value="<?php echo esc_attr( get_the_author_meta( 'my_hidden_sites', $user->ID ) ); ?>" class="regular-text" /><br />
            <span class="description"><?php _e(""); ?></span>
        </td>
    </tr>
    </table>
<?php }

add_action( 'personal_options_update', 'save_hidden_site_user_profile_fields' );
add_action( 'edit_user_profile_update', 'save_hidden_site_user_profile_fields' );

function save_hidden_site_user_profile_fields( $user_id ) {
    if ( !current_user_can( 'edit_user', $user_id ) ) { 
        return false; 
    }
    update_user_meta( $user_id, 'my_hidden_sites', $_POST['my_hidden_sites'] );
}


function opened_get_user_sites($user_id){
    $user_blogs = get_blogs_of_user( $user_id );
    //var_dump($user_blogs);
    echo 'Check the sites you would like to hide. Do not forget to update your profile at the bottom of this page.<ul>';
    foreach ($user_blogs AS $user_blog) {
        echo '<li class="hidden-list"><input type="checkbox" name="blog-' . $user_blog->userblog_id .'" id="blog-' . $user_blog->userblog_id .'" value="' . $user_blog->userblog_id . '"/> <label for="blog-' . $user_blog->userblog_id .'">'.$user_blog->blogname.'</label></li>';
    }
    echo '</ul>';
}



function hidden_sites_js_enqueue($hook) {
    if ( 'profile.php' != $hook ) {
        return;
    }
    wp_enqueue_style( 'hidden_sites_css', plugins_url('assets/hidden-sites.css', __FILE__), null, null, false);
    wp_enqueue_script( 'hidden_sites_js', plugins_url('assets/hidden-sites.js', __FILE__), null, null, false);
}
add_action( 'admin_enqueue_scripts', 'hidden_sites_js_enqueue' );


/*------------------------ TWITTER TIMELINE WIDGET --------------------------------------*/

// [twitter name=""]
function opened_twitter_func( $atts ) {
    extract(shortcode_atts( array(
         'name' => '', //name of account
    ), $atts));

    return '<a class="twitter-timeline" href="https://twitter.com/' . $name . '?ref_src=twsrc%5Etfw">Tweets by' . $name . '</a> <script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>';
}
add_shortcode( 'twitter', 'opened_twitter_func' );




/*------------------------ KSES ALLOW IFRAME MODIFICATION --------------------------------------*/


add_filter( 'wp_kses_allowed_html', 'opened_author_cap_filter',1,1 );

function opened_author_cap_filter( $allowedposttags ) {

    //Here put your conditions, depending your context

    if ( !current_user_can( 'publish_posts' ) )
    return $allowedposttags;

    // Here add tags and attributes you want to allow

    $allowedposttags['iframe']=array(
        'align' => true,
        'width' => true,
        'height' => true,
        'frameborder' => true,
        'name' => true,
        'src' => true,
        'id' => true,
        'class' => true,
        'style' => true,
        'scrolling' => true,
        'marginwidth' => true,
        'marginheight' => true,
        'allowfullscreen' => true, 
        'mozallowfullscreen' => true, 
        'webkitallowfullscreen' => true,
        'allowusermedia' => true,
        'allowfullscreen' => true,
        'allow' => true,
    );

    $allowedposttags["object"] = array(
     "height" => array(),
     "width" => array()
    );
     
    $allowedposttags["param"] = array(
     "name" => array(),
     "value" => array()
    );

    $allowedposttags["embed"] = array(
     "type" => array(),
     "src" => array(),
     "flashvars" => array()
    );


    return $allowedposttags;

}


//semi super admin adjuster 

add_filter('map_meta_cap', 'less_super_admins', 10, 4);
function less_super_admins($caps, $cap, $user_id, $args){

    $super = array(
        'update_core',
        'update_plugins',
        'update_themes',
        'upgrade_network',
        'install_plugins',
        'install_themes',
        'delete_themes',
        'delete_plugins',        
        'edit_plugins',
        'edit_themes',
        'delete_sites',
        'setup_network',
        'manage_network_plugins',     //these are big removals of all the access to these items   
        'manage_network_themes',
        'manage_network_options',
    );
    $still_super = [1,2,3,4,5,233,595];//user IDs for super admins to retain FULL RIGHTS - all other super admins are limited as defined above
    if($user_id != in_array($user_id,$still_super) && in_array($cap, $super)) {
        $caps[] = 'do_not_allow';
    }
    return $caps;
}


// Hook to columns on network sites listing
add_filter( 'wpmu_blogs_columns', 'mfs_blogs_columns' );
 
/**
* To add a columns to the sites columns
*
* @param array
*
* @return array
*/
function mfs_blogs_columns($sites_columns)
{
    //array_slice ( array $array , int $offset [, int|null $length = NULL [, bool $preserve_keys = FALSE ]] ) : array

    $columns_1 = array_slice( $sites_columns, 1, 2 );
    $columns_2 = array_slice( $sites_columns, 2 );
     
    $sites_columns = $columns_1 + array( 'content' => 'Posts/Pages' ) + $columns_2;
     
    return $sites_columns;
}

function sort_mfs_sites_custom_column(){
    $columns['content'] = 'Posts/Pages';
    return $columns;
}


// Hook to manage column data on network sites listing
add_action( 'manage_sites_custom_column', 'mfs_sites_custom_column', 10, 2 );

/**
* Show page post count
*
* @param string
* @param integer
*
* @return void
*/
function mfs_sites_custom_column($column_name, $blog_id)
{
    if ( $column_name == 'content' ) {
         switch_to_blog($blog_id);
            $pages = wp_count_posts('page','publish')->publish;
            $posts = wp_count_posts('post', 'publish')->publish;
    restore_current_blog();
   
    if ($posts < 1){
        $posts = 0;
    }
    if ($pages < 1){
        $pages = 0;
    }
        echo  $posts . '/' . $pages ;
    }
}
