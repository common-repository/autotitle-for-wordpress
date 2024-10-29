<?php
/*
Plugin Name: Autotitle for Wordpress
Plugin URI: http://unalignedcode.wordpress.com/autotitle-for-wordpress/
Description: Generates a title for a post when upon publishing no title is given. Optionally works with editing posts too.
Author: unalignedcoder
Author URI: http://unalignedcode.wordpress.com
Version: 1.0.3
*/ 

//few definitions
define("AUTOTITLE_VERSION", 103);
if (basename(dirname(__FILE__)) != 'plugins')define ("AUTOTITLE_DIR", basename(dirname(__FILE__)) ."/" );
else define ("AUTOTITLE_DIR","");
define("AUTOTITLE_PATH", get_option("siteurl") . "/wp-content/plugins/" . AUTOTITLE_DIR);

//the main function
function auto_title() {

	global $_POST;
	
	if(!empty($_POST)) {
		
		$autosettings = get_option('auto_title_options');
		$publishonly = $autosettings['publish_only'];
		$limit = $autosettings['limit'];
		$maxlimit =  $autosettings['maxlimit'];
		settype($limit,"integer");
		$prefix = $autosettings['prefix'];
		$postfix =  $autosettings['postfix'];
	
		
		//generate the title
		$maketitle = autotitle_excerpt( $_POST['content'], $limit, $maxlimit );		
	
	
		if ($publishonly) {
			
			//make sure we are publishing and not editing
			if ($_POST['action'] == 'post' && $_POST['post_title'] == '' && $_POST['publish'] == 'Publish')
					$_POST['post_title'] = $prefix.$maketitle.$postfix;			
			
		} else {
			
			if ($_POST['post_title'] == '')
				$_POST['post_title'] = $prefix.$maketitle.$postfix;			
		}
	
	}
	
}

//original excerpt function thanks to http://www.talkincode.com/getting-an-excerpt-of-text-with-php-181.html
function autotitle_excerpt($paragraph, $limit, $maxlimit){
	$tok = strtok($paragraph, " ");
	
	$autosettings = get_option('auto_title_options');
	$stoppers = explode (" ",$autosettings['stoppers']); 
	$include_stopper = $autosettings['include_stopper'];
	$exclude_stopper = $autosettings['exclude_stopper'];
	
	$words = 0; 
 
	while($tok !== false && $words <= $maxlimit){
		$text .= " ".$tok;
		$words++;
		
		if($words >= $limit) {
		
			foreach ($stoppers as $stopper) {
			
				if (substr($tok, -1) == $stopper) break 2;
			
			}
		
		}
		$tok = strtok(" ");
	}
	
	
	if ($include_stopper) {
		$text = rtrim($text, $exclude_stopper);
	} else {		
		$text = rtrim($text, implode("", $stoppers));		
	}	
	
	$text = ltrim($text);
	
	return strip_tags($text);
}


//upon activation
function autotitle_activation() {
	
	$autosettings = get_option('auto_title_options');
	if ( false === $autosettings || !is_array($autosettings) || $autosettings==''  ) {
		
		$autosettings = array();
		$autosettings['publish_only'] = 'Y';
		$autosettings['limit'] = 3;
		$autosettings['maxlimit'] = 6;
		$autosettings['stoppers'] = '! ? . ,';
		$autosettings['include_stopper'] = 'Y';
		$autosettings['exclude_stopper'] = ',';
		$autosettings['prefix'] = '';
		$autosettings['postfix'] = '';
		$autosettings['version'] = AUTOTITLE_VERSION;
		$autosettings['uninstall'] = '';
		update_option('auto_title_options', $autosettings);
		
	}
	
}

//upon deactivation
function autotitle_deactivation() {

	//delete the options
	$autosettings = get_option('auto_title_options');
	if($autosettings['uninstall'] == 1) delete_option('auto_title_options');
	
}

//options page
function autotitle_options() {
	
	// Check Whether User Can Manage Options
	if(!current_user_can('manage_options'))die('Access Denied');
	$mode = trim($_GET['mode']);
	
	//handle the post event
	if(!empty($_POST['do'])) {
		switch($_POST['do']) {
			case 'Update' :
				$autosettings = array(
				'publish_only' => $_POST['publish_only'],
				'limit' => $_POST['limit'],
				'maxlimit' => $_POST['maxlimit'],
				'stoppers' => $_POST['stoppers'],
				'include_stopper' => $_POST['include_stopper'],
				'exclude_stopper' => $_POST['exclude_stopper'],
				'prefix' => $_POST['prefix'],
				'postfix' => $_POST['postfix'],
				'version' => AUTOTITLE_VERSION,
				'uninstall' => ''
				);
				$update_autotitle_options = update_option('auto_title_options', $autosettings);		
				if ($update_autotitle_options) { ?>
                <div id="message" class="updated fade"><p>
                <?php echo __('<strong>Options saved.</strong>', 'autotitle');
				?></p></div><?php }	
			break;
			
			case 'Deactivate' :
				$autosettings = get_option('auto_title_options');
				$autosettings = array(
				'publish_only' => $autosettings['publish_only'],
				'limit' => $autosettings['limit'],
				'maxlimit' => $autosettings['maxlimit'],
				'stoppers' => $autosettings['stoppers'],
				'include_stopper' => $autosettings['include_stopper'],
				'exclude_stopper' => $autosettings['exclude_stopper'],
				'prefix' => $autosettings['prefix'],
				'postfix' => $autosettings['postfix'],
				'version' => $autosettings['version'],
				'uninstall' => $_POST['remove'],
				);
				$update_autotitle_options = update_option('auto_title_options', $autosettings);	
				$mode = 'end-UNINSTALL';			
			break;				
		}
		
	}	
	
	switch($mode) {
		//  Deactivating
		case 'end-UNINSTALL':
		
			$deactivate_url = get_option("siteurl"). '/wp-admin/plugins.php?action=deactivate&amp;plugin='.AUTOTITLE_DIR.'autotitle-for-wordpress.php';
			if(function_exists('wp_nonce_url'))	$deactivate_url = urldecode(wp_nonce_url($deactivate_url, 'deactivate-plugin_'.AUTOTITLE_DIR.'autotitle-for-wordpress.php'));	       
			//feedback the deletion option
			$autosettings = get_option('auto_title_options');
			?><div class="wrap"><h2><?php echo _e('Deactivate Autotitle for Wordpress', 'autotitle') ?></h2>
			<p><strong><a href="<?php echo $deactivate_url ?>">
			<?php echo _e('Click Here</a> to deactivate Autotitle for Wordpress.', 'autotitle'); ?>
			</a></strong></p><?php			
			if( $autosettings['uninstall'] == 1 ) echo 'Warning:<br /><font color="#990000">'.__('The plugin options will be removed.', 'autotitle').'</font><br />';				
			?></div><?php
		break;
			
	// Main Page
	default:
	
	$autosettings = array();
	$autosettings = get_option('auto_title_options');	
	if ( $autosettings['publish_only'] ) $publishonly_selected = 'checked';
	if ( $autosettings['include_stopper'] ) $includestopper_selected = 'checked';
	?>		

	<?php /*options*/ ?>
	<div class="wrap"><br/><h2>Autotitle for Wordpress - <?php echo __('Settings','autotitle') ?></h2><br/>
    
	<form method="post" action="<?php $_SERVER['REQUEST_URI'] ?>">
	<table class="form-table">
    
    <tr valign="top"><th scope="row"><?php echo __('Enable only when publishing','autotitle'); ?></th><td>    
    <input type="checkbox" name="publish_only" value="Y" <?php echo $publishonly_selected ?> />
    &nbsp;<?php echo __('If unchecked, will generate a automatic title also when editing a post.','autotitle') ?>    
    </td></tr>    
    
    <tr valign="top"><th scope="row"><?php echo __('Word limit','autotitle'); ?></th><td><?php 
    $inputline1 = '<input style="border:thin solid #ccc" type="text" name="limit" value="'.$autosettings['limit'].'" size="2" />';
	$inputline2 = '<input style="border:thin solid #ccc" type="text" name="maxlimit" value="'.$autosettings['maxlimit'].'" size="2" />';
    echo str_replace (array("%x", "%y"),array($inputline1, $inputline2), __('A title will be generated using at least %x words from the beginning of the post (until the first stopper is encountered) but never more than %y','autotitle')); ?>    
    </td></tr>
    
    <tr valign="top"><th scope="row"><?php echo __('Stoppers','autotitle'); ?></th><td>
    <?php echo __('A title will be generated when first encountering one of these characters:','autotitle'); ?>&nbsp;
    <input type="text" name="stoppers" value="<?php echo $autosettings['stoppers'] ?>" size="8" />
    &nbsp;<?php echo __('(use spaces to separate them)','autotitle'); ?>    
    </td></tr>

    <tr valign="top"><th scope="row"><?php echo __('Include stopper in the title','autotitle'); ?></th><td>    
    <input type="checkbox" name="include_stopper" value="Y" <?php echo $includestopper_selected ?> />
    &nbsp;<?php echo __('If checked, the stopper will be included at the end of title, unless it is a','autotitle') ?> 
    <input type="text" name="exclude_stopper" value="<?php echo $autosettings['exclude_stopper'] ?>" size="1" maxlength="1" />
    </td></tr>    
    
    <tr valign="top"><th scope="row"><?php echo __('Prefix','autotitle'); ?></th><td>
    <input type="text" name="prefix" value="<?php echo $autosettings['prefix'] ?>" size="11" />
    &nbsp;<?php echo __('This will always be added before automatic titles','autotitle'); ?>    
    </td></tr>

    <tr valign="top"><th scope="row"><?php echo __('Suffix','autotitle'); ?></th><td>
    <input type="text" name="postfix" value="<?php echo $autosettings['postfix'] ?>" size="11" />
    &nbsp;<?php echo __('This will always be added after automatic titles','autotitle'); ?>    
    </td></tr>

    
	</table>
    <input type="hidden" name="do" value="Update" />
	<p class="submit"><input type="submit" value="<?php echo _e('Update Options &raquo;', 'autotitle') ?>" /></p>
    </form></div>
    
    <?php /*uninstall*/ ?>
    <div class="wrap"><br/><h2>Autotitle for Wordpress - <?php echo _e('Deactivation','lighter-menus') ?></h2><br/>
    <?php echo _e('Use this to deactivate Autotitle and optionally remove its options.','autotitle') ?><br/><br/>   
    
	<form method="post" action="<?php $_SERVER['REQUEST_URI'] ?>">
    <table class="form-table">
    <tr valign="top">
    <th scope="row"><?php echo __('When deactivating', 'autotitle'); ?></th>
    <td>
    <input type="checkbox" name="remove" value="1" <?php echo $remove_autotitle_selected ?> />
    <?php echo __('Remove the above options from the database.','autotitle') ?><br />
    </td>
    </tr>
	</table>
    <input type="hidden" name="do" value="Deactivate" />
	<p class="submit"><input type="submit" style="color:#990000" value="<?php echo __('Deactivate Autotitle &raquo;','autotitle') ?>" /></p>
    </form></div>  

	<?php } //end switch mode
	
	
}

//add pages to wordpress theme pages
function autotitle_pages() {
	add_options_page( __('Autotitle for Wordpress','autotitle'),'Autotitle', 9, basename(__FILE__), 'autotitle_options');
}

//excuse me, I'm hooking wordpresss
add_action('admin_menu', 'autotitle_pages');
add_action('init', 'auto_title');
register_deactivation_hook(__FILE__, 'autotitle_deactivation');
register_activation_hook(__FILE__, 'autotitle_activation');

?>