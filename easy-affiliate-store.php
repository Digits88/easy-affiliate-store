<?php
/*
Plugin Name: Easy Affiliate Store
Plugin URI: http://www.danonwordpress.com/easy-affiliate-store-plugin-for-wordpress/
Description: Add an affiliate store section to any blog, allowing easy promotion of affiliate products.
Author: Dan Mossop
Version: 1.1.2
Author URI: http://www.danmossop.com
*/

/* === CONFIG === */
$doweas_product_detail_fields = array('sold_by', 'url', 'author', 'publisher', 'made_by', 'price', 'featured_product');

/* === DETAILS WIDGET === */

class doweas_productDetailsWidget extends WP_Widget {
	
	private $wid = 'doweas_productDetailsWidget'; // should be same as class name
	private $wname = "Product Details";
	private $wdescr = 'Displays details of the currently viewed product (if any)';
	
  function doweas_productDetailsWidget() {
    $this->WP_Widget($this->wid, $this->wname, array('classname'=>$this->wid, 'description'=>$this->wdescr));
  }
 
  function form($instance) {
    $instance = wp_parse_args((array) $instance, array('title'=>''));
	$id = $this->get_field_id('title');
	$name = $this->get_field_name('title');
	$val = attribute_escape($instance['title']);
	echo <<<END
	<p><label for="$id">Title: <input class="widefat" id="$id" name="$name" type="text" value="$val" /></label></p> 
END;
  }
 
  function update($new_instance, $old_instance) {
    $instance = $old_instance;
    $instance['title'] = $new_instance['title'];
    return $instance;
  }
 
  function widget($args, $instance) {
	global $post;
    extract($args, EXTR_SKIP);
    echo $before_widget;
    $title = empty($instance['title'])?' ':apply_filters('widget_title', $instance['title']);
    // WIDGET CODE GOES HERE
	
	$p = doweas_get_product_details($post->ID);
	if ($post->post_type=='doweas_product' and (!empty($p['sold_by']))) { 	
		if (!empty($title)) { echo $before_title.$title.$after_title; }
		echo '<ul>';
		if (!empty($p['author'])) { echo '<li>Author: '.$p['author']; }
		if (!empty($p['publisher'])) { echo '<li>Publisher: '.$p['publisher']; }
		if (!empty($p['made_by'])) { echo '<li>Made By: '.$p['made_by']; }
		if (!empty($p['sold_by'])) { echo '<li>Sold By: '.$p['sold_by']; }
		if (!empty($p['price'])) { echo '<li>Price: '.$p['price']; }
		echo '</ul>';
	}
	
	// END WIDGET CODE
    echo $after_widget;
  }
}
add_action( 'widgets_init', create_function('', 'return register_widget("doweas_productDetailsWidget");') );


/* === CSS === */

function doweas_css() { ?>
<style>
#doweas { 
	width:100%;
	text-align:center;
	padding:10px 0;
}
#doweas-btn {
	padding:9px 20px; 
	font: normal 11pt arial; 
	background-color:#ccc;
	color: white; 
    text-align: center;
    border: 1px solid #9c9c9c; /* fallback */
    border: 1px solid rgba(0, 0, 0, 0.1);
	border-radius: 4px;
    text-shadow: 0 1px 0 rgba(0,0,0,0.4);
    background-image: linear-gradient(to top,  rgba(0,0,0,0.15) 0%,rgba(82,82,82,0.15) 32%,rgba(255,255,255,0.24) 100%);
	cursor: pointer;
	outline: none;
}
#doweas-btn:hover, #doweas-btn:active, #doweas-btn:focus {
    background-image: linear-gradient(to top, rgba(0,0,0,0.05) 0%,rgba(82,82,82,0.05) 32%,rgba(255,255,255,0.4) 100%);
}
#doweas-btn img { width:16px;height:16px;vertical-align:top;margin:1px 8px 0px -10px; }
#doweas-btn { background-color: rgb(256,121,7); }

.doweas-product-post { float:left; margin-right:11px; margin-bottom:60px }
.doweas-product-post, .doweas-product-post h3, .doweas-product-post p { width:315px; } 
.doweas-product-post h3 { margin:6px 0; }
.doweas-product-post p { line-height:1.6em; }
</style>
<?php
}
add_action('wp_footer', 'doweas_css');


/* === REGISTER PRODUCT POST TYPE === */

function doweas_create_product_post_type() {
	register_post_type('doweas_product', array(
		'labels' => array(
			'name' => __( 'Products' ),
			'singular_name' => __( 'Product' ),
			'add_new_item' => __( 'Add New Product' ),
			'edit_item' => __( 'Edit Product' ),
			'new_item' => __( 'New Product' ),
			'view_item' => __( 'View Product' ),
			'search_items' => __( 'Search Products' ),
			'not_found' => __( 'No products found' ),
			'not_found_in_trash' => __( 'No products found in Trash' )
		),
		'taxonomies' => array('category', 'post_tag'),
		'public' => true,
		'has_archive' => true,
		'supports'=>array('title', 'editor', 'excerpt', 'thumbnail'),
		'register_meta_box_cb' => 'doweas_add_product_details_metabox',
		'menu_icon'=>'dashicons-cart',
		'exclude_from_search' => get_option('doweas_hide_in_search'),
		'rewrite' => array( 'slug' => 'product' ),
		)
	);
}
register_activation_hook(__FILE__, 'doweas_create_product_post_type');
add_action( 'init', 'doweas_create_product_post_type' );

function doweas_rewrite() { 
	global $wp_rewrite; 
	
	// rewrite options
	$query_var = 'doweas_dept';
	$url_base = 'store/';
	$page_slug = 'store-department';
	
	// register the rewrite
	add_rewrite_tag('%'.$query_var.'%','([^/]+)');
	add_rewrite_rule('^'.$url_base.'([^/]+)/?','index.php?page_id='.get_page_by_path($page_slug)->ID.'&'.$query_var.'=$matches[1]','top'); 
	$wp_rewrite->flush_rules();
} 
register_activation_hook(__FILE__, 'doweas_rewrite');

/* === PRODUCT DETAILS METABOX === */

function doweas_add_product_details_metabox() {
	add_meta_box('doweas_products_details_metabox', 'Product Details', 'doweas_products_details_metabox', 'doweas_product', 'normal', 'high');
}

function doweas_products_details_metabox() {
	global $post;
	
	// Nonce needed to verify where the data originated
	echo '<input type="hidden" name="doweas_product_details_nonce" id="doweas_product_details_nonce" value="' .
	wp_create_nonce(plugin_basename(__FILE__)).'" />';
	
	// Get existing event info, if set
	$p = doweas_get_product_details($post->ID);
	?>
<table>
<tr><td>Sold By:</td><td><input type="text" name="sold_by" value="<?php echo $p['sold_by']; ?>" class="widefat" style="width:40em"/></td></tr>
<tr><td>URL:</td><td><input type="text" name="url" value="<?php echo $p['url']; ?>" class="widefat" style="width:40em"/></td></tr>
<tr><td>Author:</td><td><input type="text" name="author" value="<?php echo $p['author']; ?>" class="widefat" style="width:40em"/></td></tr>
<tr><td>Publisher:</td><td><input type="text" name="publisher" value="<?php echo $p['publisher']; ?>" class="widefat" style="width:40em"/></td></tr>
<tr><td>Made By:</td><td><input type="text" name="made_by" value="<?php echo $p['made_by']; ?>" class="widefat" style="width:40em"/></td></tr>
<tr><td>Price:</td><td><input type="text" name="price" value="<?php echo $p['price']; ?>" class="widefat" style="width:40em"/></td></tr>
<tr><td>Featured Product:</td><td><input type="checkbox" name="featured_product" class="widefat" value="1" <?php checked($p['featured_product'],1); ?>/></td></tr>
</table>
	<?php	
}

// Save the Metabox Data
function doweas_save_product_details($post_id, $post) {
	global $doweas_product_detail_fields;

	// check the nonce, authorisation, autosave and post_type
	if (!wp_verify_nonce($_POST['doweas_product_details_nonce'], plugin_basename(__FILE__))) { return $post->ID; }
	if (!current_user_can('edit_post', $post->ID)) { return $post->ID; }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return $post_id; }
	if ($post->post_type!='doweas_product') { return; }
		
	// we're authorised, so save the data
	foreach ($doweas_product_detail_fields as $field) { 
		if (empty($_POST[$field])) { delete_post_meta($post->ID, "doweas_$field"); }
		else { 
			if(!update_post_meta($post->ID, "doweas_$field", $_POST[$field])) { 
				add_post_meta($post->ID, "doweas_$field", $_POST[$field]); 
			} 
		}
	}
}
add_action('save_post', 'doweas_save_product_details', 1, 2); // save the custom fields

// get the product details for a given post id
function doweas_get_product_details($id) {
	global $doweas_product_detail_fields;
	
	$info = array();
	foreach ($doweas_product_detail_fields as $field) { 
		$info[$field] = get_post_meta($id, "doweas_$field", true);
	}
	return $info; 
}

/* === ADD TO END OF POST === */

// Add a buy button to the end of the post
function doweas_add_buy_button($content) {
	global $post;
	if (is_single() and get_post_type($post->ID)=='doweas_product') {
		
		$product = doweas_get_product_details($post->ID);
		
		// create the button (if needed)
		if ($product['url']) {
			
			$parse = parse_url($product['url']);
			$host = $parse['host'];
			
			// add amazon associate id if available
			$amazonid = get_option('amazonid');
			if ($host=='www.amazon.com' and !empty($amazonid)) { 
				$product['url'] = add_query_arg('tag', $amazonid, $product['url']); 
			} 
			
			$content.=<<<END
<div id="doweas">
	<a href="${product['url']}">
		<button id="doweas-btn">
			<img src="//$host/favicon.ico"/>
			View on ${product['sold_by']}
		</button>
	</a>
</div>
END;
		}
	}
	return $content;
}
add_filter('the_content', 'doweas_add_buy_button'); 

/* === PRODUCT SIDEBAR === */

register_sidebar(array(
    'name'         => __( 'Product Sidebar' ),
    'id'           => 'doweas_product_sidebar',
    'description'  => __('Widgets in this area will be shown on product pages.'),
    'before_title' => '<h4>',
    'after_title'  => '</h4>',
	'before_widget' => '<div class="doweas_product_sidebar_box sidebar-box clearfix">',
	'after_widget' => '</div>'
));

/* === SETTINGS PAGE === */

// add menu item  
function doweas_plugin_menu() {
	add_options_page('Easy Affiliate Store Options', 'Easy Affiliate Store', 'manage_options', 'doweas_settings', 'doweas_plugin_options' );
}
add_action('admin_menu', 'doweas_plugin_menu');

// register permitted fields
function doweas_register_settings() { // whitelist options
  register_setting('doweas-group', 'doweas_amazonid');
  register_setting('doweas-group', 'doweas_hide_on_front_page');
  register_setting('doweas-group', 'doweas_hide_in_categories');
  register_setting('doweas-group', 'doweas_hide_in_search');
}
add_action( 'admin_init', 'doweas_register_settings' );

// create the options page
function doweas_plugin_options() {
	if (!current_user_can('manage_options')) { wp_die(__('You do not have sufficient permissions to access this page.')); }
	?>
<div class="wrap">
<h2>Easy Affiliate Store Options</h2>
<p>This is the options page for the Easy Affiliate Store plugin.</p>
<p>Your store is located at <a href="<?php echo get_bloginfo('wpurl'); ?>/store/"><?php echo get_bloginfo('wpurl'); ?>/store/</a></p>
<form method="post" action="options.php">
<table>
<tr><td>Amazon Associates ID:</td><td><input type="text" name="doweas_amazonid" value="<?php echo get_option('doweas_amazonid'); ?>" /></td></tr>
<tr><td>Hide products from front page:</td><td><input type="checkbox" name="doweas_hide_on_front_page" value="1" <?php checked(get_option('doweas_hide_on_front_page'),1); ?>/></td></tr>
<tr><td>Hide products from categories:</td><td><input type="checkbox" name="doweas_hide_in_categories" value="1" <?php checked(get_option('doweas_hide_in_categories'),1); ?>/></td></tr>
<tr><td>Hide products from search:</td><td><input type="checkbox" name="doweas_hide_in_search" value="1" <?php checked(get_option('doweas_hide_in_search'),1); ?>/></td></tr>
</table>
<?php settings_fields('doweas-group'); ?>
<?php do_settings_sections('doweas-group'); ?>
<?php submit_button(); ?>
</form>
</div>
	<?php
}

// Configure custom post to show up on homepage, etc, like a normal post
function doweas_add_product_posts_to_listings($query) {
	if ((!get_option('doweas_hide_on_front_page') and $query->is_home()) or 
		(!get_option('doweas_hide_in_categories') and $query->is_category())) {
		
		// get existing post_types into an array
		$post_types = $query->get('post_type');
		if (empty($post_types)) { $post_types='post'; }
		$post_types = is_array($post_types)?$post_types:array($post_types);
		
		// add product post_type
		$post_types[] = 'doweas_product';
		$query->set('post_type',  $post_types);
	}
    return $query;
}
add_action( 'pre_get_posts', 'doweas_add_product_posts_to_listings' );

/* === CREATE STORE FRONT PAGE === */

function doweas_insert_store_page() { 
	if (!get_page_by_path('store')) { 
		wp_insert_post(array(
			'post_content'   => '[easy_affiliate_store cat=featured] [easy_affiliate_store]',
			'post_name'      => 'store', 
			'post_title'     => 'My Store', 
			'post_status'    => 'publish', 
			'post_type'      => 'page', 
			'comment_status' => 'closed', 
		)); 
	} 
}
add_action('init', 'doweas_insert_store_page');

/* === CREATE DEPARTMENT PAGE === */

function doweas_insert_department_page() { 
	if (!get_page_by_path('store-department')) { 
		wp_insert_post(array(
			'post_content'   => '[easy_affiliate_store_department_description] [easy_affiliate_store_department count=10]', 
			'post_name'      => 'store-department', 
			'post_title'     => 'My Store: #DEPT#', 
			'post_status'    => 'publish', 
			'post_type'      => 'page', 
			'comment_status' => 'closed', 
		)); 
	}
}
add_action('init', 'doweas_insert_department_page');

/* === SHORTCODES === */
function doweas_store_shortcode($atts) {
	extract( shortcode_atts( array(
		'count' => '3',
		'cat' => '',
		'show_title' => true
	), $atts ) );
	return doweas_store_html($cat, $count);
}
add_shortcode('easy_affiliate_store', 'doweas_store_shortcode');

function doweas_department_shortcode($atts) {
	extract( shortcode_atts( array(
		'count' => '10'
	), $atts ) );
	
	$dept = get_query_var('doweas_dept');
	return (!empty($dept))?doweas_store_html($dept, $count, false):'';
}
add_shortcode('easy_affiliate_store_department', 'doweas_department_shortcode');

function doweas_store_html_department_description_shortcode() {
	return term_description(get_term_by('name', get_query_var('doweas_dept'), 'department'), 'department');
}
add_shortcode('easy_affiliate_store_department_description', 'doweas_store_html_department_description_shortcode');

/* === CUSTOM URL VAR === */
function doweas_var_filter($vars){

	// filter options
	$query_var = 'doweas_dept';

	// register the query var
	$vars[] = $query_var;
	return $vars;
}
add_filter('query_vars', 'doweas_var_filter');

/* === REWRITE DEPARTMENT TITLE === */
function modify_page_title ($title) {
	global $post;
	$query = get_query_var('doweas_dept');
	
	if (!empty($query)) {
		$orig = '#DEPT#'; 
		$new = get_term_by('name', get_query_var('doweas_dept'), 'department')->name;
		$slug = 'store-department';
		
		if($slug == $post->post_name and !empty($new)){ $title = str_replace($orig, $new, $title); }
	}
	return $title;
}
add_filter( 'the_title', 'modify_page_title');


/* === FUNCTIONS TO GET HTML === */
function doweas_store_html($dept='', $count=3, $show_title=true) {

	// if department not set, show all
	if (empty($dept)) { 
		$depts = array();
		$terms = get_terms('department');
		foreach($terms as $t) { $depts[]=$t->slug; }
	} else { 
		$depts = array($dept);
	}	
	
	$html = '<div class="doweas">';
	foreach($depts as $d) {
		$department_query = new WP_query();
		
		if ($d=='featured') { 
		    $department_args=array(
				'post_type'=>'doweas_product',
				'meta_key'=>'_featured_product',
				'meta_value'=>'1'
			);
		} else { 
			$department_args=array(
				'post_type'=>'doweas_product',
				'department'=>$d,
				'showposts'=>$count
			);
		}
		$department_query->query($department_args);
	
		if ($department_query->have_posts()) { 
			if ($show_title) { 
				$html.= '<h2><a href="'.site_url('/store/'.$d.'/').'">'.get_term_by('slug', $d, 'department')->name.'</a></h2>';
			}
			while($department_query->have_posts()) { 
				$department_query->the_post();
				
				$html.= '<div class="doweas-product-post">';
				
				if ( has_post_thumbnail() ) { 
					$feat_image = wp_get_attachment_url(get_post_thumbnail_id($post->ID));
					$img = doweas_image($feat_image, 315, 175);
					$html.='<a href="'.get_permalink().'" title="'.get_the_title().'">'.preg_replace('#http:\/\/[^\/]+\/#','/',$img).'</a>';
				}
				$html.='<h3><a href="'.get_permalink().'" title="'.get_the_title().'">'.get_the_title().'</a></h3>';
				
				// get the excerpt
				$excerpt = strip_tags(strip_shortcodes(apply_filters('the_excerpt', get_post_field('post_excerpt', $id))));
				if (empty($excerpt)) { $excerpt = get_the_excerpt(); } 
				if (strlen($excerpt)>125) { $excerpt = preg_replace('/\s+[^ ]+$/','',substr($excerpt,0,125)).'...'; }
				$html.= '<p>'.$excerpt.'</p>';
				
				$html.= '</div>';
			} 
			$html.='<div style="clear:both"></div>';
		} 
		
		wp_reset_postdata();
		//wp_reset_query();
	} 
	$html.= '</div>';
	return $html;
}

/* === IMAGE RESIZING === */
function doweas_image($src, $width, $height ) {
	global $wpdb;
 
	// Sanitize
	$height = absint( $height );
	$width = absint( $width );
	$src = esc_url( strtolower( $src ) );
	$needs_resize = true;
 
	$upload_dir = wp_upload_dir();
	$base_url = strtolower( $upload_dir['baseurl'] );
	
	// Let's see if the image belongs to our uploads directory.
	if ( substr( $src, 0, strlen( $base_url ) ) != $base_url ) {
		return "Error: external images are not supported.";
	}
	
	// Look the file up in the database.
	$file = str_replace( trailingslashit( $base_url ), '', $src );
	$attachment_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_attachment_metadata' AND meta_value LIKE %s LIMIT 1;", '%"' . like_escape( $file ) . '"%' ) );
 
	// If an attachment record was not found.
	if ( ! $attachment_id ) {
		return "Error: attachment not found.";
	}
	
	// Look through the attachment meta data for an image that fits our size.
	$meta = wp_get_attachment_metadata( $attachment_id );
	foreach( $meta['sizes'] as $key => $size ) {
		if ( $size['width'] == $width && $size['height'] == $height ) {
			$src = str_replace( basename( $src ), $size['file'], $src );
			$needs_resize = false;
			break;
		}
	}
	
	// If an image of such size was not found, we can create one.
	if ( $needs_resize ) {
		$attached_file = get_attached_file( $attachment_id );
		$resized = image_make_intermediate_size( $attached_file, $width, $height, true );
		if ( ! is_wp_error( $resized ) ) {
			
			// Let metadata know about our new size.
			$key = sprintf( 'resized-%dx%d', $width, $height );
			$meta['sizes'][$key] = $resized;
			$src = str_replace( basename( $src ), $resized['file'], $src );
			wp_update_attachment_metadata( $attachment_id, $meta );
			
			// Record in backup sizes so everything's cleaned up when attachment is deleted.
			$backup_sizes = get_post_meta( $attachment_id, '_wp_attachment_backup_sizes', true );
			if ( ! is_array( $backup_sizes ) ) $backup_sizes = array();
			$backup_sizes[$key] = $resized;
			update_post_meta( $attachment_id, '_wp_attachment_backup_sizes', $backup_sizes );
		}
	}
	
	// Generate the markup and return.
	$width = ( $width ) ? 'width="' . absint( $width ) . '"' : '';
	$height = ( $height ) ? 'height="' . absint( $height ) . '"' : '';
 	return sprintf( '<img src="%s" %s %s />', esc_url( $src ), $width, $height );
}

/* === CUSTOM TAXONOMY === */
function doweas_department_init() {

	// create a new taxonomy
	register_taxonomy(
		'department',
		'doweas_product',
		array(
			'label' => __( 'Departments' ),
			'rewrite' => array( 'slug' => 'department' ),
			'hierarchical'=>true
		)
	);
}
add_action( 'init', 'doweas_department_init' );

// Hide category parent option for this taxonomy
function doweas_admin_css() { 
	$taxonomy = 'department';
	echo <<<END
<style>
#new${taxonomy}_parent, 
.taxonomy-${taxonomy} #parent, 
.taxonomy-${taxonomy} label[for=parent] 
{ display:none; }
</style>
END;
}
add_action('admin_head', 'doweas_admin_css');

/* === Add link to settings page in plugin listing ==== */
add_filter('plugin_action_links', 'doweas_plugin_action_links', 10, 2);

function doweas_plugin_action_links($links, $file) {
    static $this_plugin;

    if (!$this_plugin) { $this_plugin = plugin_basename(__FILE__); }

    if ($file == $this_plugin) {
        $settings_link = '<a href="'.get_bloginfo('wpurl').'/wp-admin/admin.php?page=doweas_settings">Settings</a>';
        array_unshift($links, $settings_link);
    }

    return $links;
}
?>