<?php
/*
Plugin Name: Yet Another Newsletter
Plugin URI: 
Description: A simple Wordpress Newsletter that works !
Version: 0.1
Author: Dorian
Author URI: 
License: WTFPL
*/

//
// ============= Add options in administration menu ====================
// 

add_action('admin_menu', 'yanewsletter_menu');

function yanewsletter_menu() {
	add_options_page('Yet Another Newsletter', 'Newsletter', 'manage_options', 'yanewletter-options-id', 'yanewsletter_options');
}

add_option('yanewsletter_from', get_bloginfo('admin_email'));

function yan_add_form() {
	echo '<form method="post">';
	  echo '<input type="text" name="yan_email" placeholder="Email" />';
	  echo '<input type="submit" class="button-primary" value="Subscribe" />';
	echo '</form>';
}

function yanewsletter_options($arg) {
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	
	if ($_POST['from']) {
	  update_option('yanewsletter_from', $_POST['from']);
	}
	
	echo '<div class="wrap">';
	echo '<h1>Options for Yet Another Newsletter Plugin</h1>';
	echo '<form method="post">';
	settings_fields( 'yanewsletter-options-group' );
	  echo 'From : <input type="text" name="yan_from" value="'.get_option('yanewsletter_from').'" /><br />';
	  echo '<input type="submit" class="button-primary" value="Save" />';
	echo '</form>';
	echo '<h1>Send post</h1>';
	echo '<form method="post">';
	  echo 'Post : <select name="yan_post">';
	  $posts = get_posts('numberposts=30');
	  foreach ($posts as $post) {
	    echo '<option value="'.$post->ID.'">'.$post->post_title.'</option>';
	  }
	  echo '</select><br />';
	  echo '<input type="submit" class="button-primary" value="Send to all emails" />';
	echo '</form>';
	echo '<h2>List of emails</h2>';
	echo '<ul>';
	  $emails = yan_get_emails();
	  foreach ($emails as $obj) {
	    echo '<li>'.$obj->email;
	      echo '<form method="post">';
	        echo '<input type="hidden" name="yan_delete" value="'.$obj->email.'" />';
	        echo '<input type="submit" value="X" />';
	      echo '</form>';
	    echo '</li>';
	  }
	echo '</ul>';
	echo 'Add email : ';
	yan_add_form();
	echo '</div>';
}

if ($_POST['yan_email']) {
  yan_add_email($_POST['yan_email']);
}

if ($_POST['yan_delete']) {
  yan_remove_email($_POST['yan_delete']);
}

if ($_POST['yan_post']) {
  yan_send_all($_POST['yan_post']);
}

//
// ================= Emails ========================
//

register_activation_hook(__FILE__,'yan_install');

function yan_install() {
  global $wpdb;
  $table_name = $wpdb->prefix . "yanewsletter_emails";

  $sql = "CREATE TABLE " . $table_name . " (
	  id mediumint(9) NOT NULL AUTO_INCREMENT,
	  email tinytext NOT NULL,
	  UNIQUE KEY id (id)
	);";

  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  dbDelta($sql);
}

function yan_add_email($email) {
  global $wpdb;
  $table_name = $wpdb->prefix . "yanewsletter_emails";

  $wpdb->insert( $table_name, array( 'email' => $email ) );
}

function yan_get_emails() {
  global $wpdb;
  $table_name = $wpdb->prefix . "yanewsletter_emails";

  return $wpdb->get_results('SELECT * FROM '.$table_name);
}

function yan_remove_email($email) {
	if (!current_user_can('manage_options'))  {
    global $wpdb;
    $table_name = $wpdb->prefix . "yanewsletter_emails";

    $wpdb->query('DELETE FROM '.$table_name.' WHERE email="'.$email.'"');
  }
}

function yan_send_all($post) {
  $emails = yan_get_emails();
  $post = get_post($post);
  foreach ($emails as $obj) {
    mail($obj->email, $post->post_title, $post->post_content, "From: ".get_option('yanewsletter_from')."\r\nContent-type: text/html; charset=iso-8859-1\r\n");
  }
  return '<h1>Sent !</h1>'; 
}

//
// ============= Widget ==================
//

function yan_widget_content() {
  echo "Subscribe to our newsletter :<br /><strong>Email :</strong>";
  if($_POST['yan_email']) {
    echo '<h1>Added</h1>';
  } else {
    yan_add_form();
  }
}
 
function widget_yanewsletter($args) {
  extract($args);
  echo $before_widget;
  echo $before_title;?>Newsletter<?php echo $after_title;
  yan_widget_content();
  echo $after_widget;
}
 
function yanewsletter_widget_init() {
  register_sidebar_widget('Newsletter', 'widget_yanewsletter');
}

add_action("plugins_loaded", "yanewsletter_widget_init");

