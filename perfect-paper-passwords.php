<?php
/*
Plugin Name: Perfect Paper Passwords
Plugin URI: http://henrik.schack.dk/perfect-paper-passwords-for-wordpress
Description: Multi-Factor Authentication for WordPress using Perfect Paper Passwords
Author: Henrik Schack
Version: 0.52
Author URI: http://henrik.schack.dk/
Compatibility : WordPress 3.1.3
Text Domain: perfectpaperpasswords
Domain Path: /lang

----------------------------------------------------------------------------

	Read all about Perfect Paper Passwords at the 
	Gibson Research Corporation website https://www.grc.com/ppp.htm
	
	Thanks to:
	Aldo Latino for his ideas, and Italian translation.
	
----------------------------------------------------------------------------

    Copyright 2011  Henrik Schack  (email : henrik@schack.dk)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class PerfectPaperPasswords {
	private $characterset;
	private $sequencekey;
	private static $cols = array(0,0,11,8,7,5,5,4,3,3,3,2,2,2,2,2,2); // amount of coloums on each card

	function __construct( $secret, $characterset = '' ){
		$this->sequencekey=hash( 'sha256', $secret );
		// Store non-default characterset
		if ( $characterset != '' ) {
			$this->characterset=sortchars( $characterset );
		} else {
			// Default characterset uses 64 different characters
			$this->characterset=$this->sortchars( '!#%+23456789:=?@ABCDEFGHJKLMNPRSTUVWXYZabcdefghijkmnopqrstuvwxyz' );
		}
	}
	
	function getSequenceKey(){
		return $this->sequencekey;
	}
	
	// Convert string number into binary format 
	function packvalue( $num ) {
		$pack = '' ;
		while( $num ) {
			$pack .= chr(bcmod($num,256));
			$num = bcdiv($num,256);
		}
		return $pack ;
	}

	// Convert binary value into string representation of value.
	function unpackvalue( $pack ) {
		$pack = str_split(strrev($pack));
		$num = '0';
		foreach($pack as $char) {
			$num = bcmul($num,256);
			$num = bcadd($num,ord($char));
		}
		return $num ;
	}
	
	// sort character set
	function sortchars( $charset ) {
		$newchars = str_split($charset,1);
		sort($newchars);
		return implode('',$newchars);
	}	
	
	function getPerfectPaperPassword( $pwdnr, $pwdlen ) {
		$password = "";
		$charsetlen = strlen($this->characterset);
		$number=$this->unpackvalue( mcrypt_encrypt( MCRYPT_RIJNDAEL_128, pack( 'H*', $this->sequencekey ),$this->packvalue( $pwdnr ), MCRYPT_MODE_ECB, str_repeat( "\0", 16 ) ) );

		for ( $count =0;$count<$pwdlen;$count++ ) {
			$password .= substr( $this->characterset,bcmod( $number, $charsetlen ), 1 );
			$number = bcdiv( $number, $charsetlen );
		}
		return $password;
	}
	
	static function getPasswordCoordinates( $pwdnr, $passcodelength ){
		// Cardnumbers start at 1
		$cardnumber = intval( $pwdnr / ( self::$cols[$passcodelength] * 10 ) ) + 1;  
		$character  = chr( 65 + ( $pwdnr % self::$cols[$passcodelength] ) );
		// Rows start at 1
		$number		= 1 + intval( $pwdnr / self::$cols[$passcodelength] ) % 10; 
		return array( 'cardnumber'=>$cardnumber, 'character'=>$character, 'number'=>$number );
	}
}


/**
 * Add Perfect Paper Password field to login form.
 */
function perfectpaperpasswords_loginform() {
  echo "\t<p>\n";
  echo "\t\t<label><a href=\"http://www.grc.com/ppp\" target=\"_blank\" title=\"".__('If You don\'t have Perfect Paper Passwords enabled for Your Wordpress account, leave this field empty.','perfectpaperpasswords')."\">".__('Perfect Paper Password','perfectpaperpasswords')."</a><span id=\"pppinfo\"></span><br />\n";
  echo "\t\t<input type=\"password\" name=\"otp\" id=\"user_email\" class=\"input\" value=\"\" size=\"20\" tabindex=\"25\" /></label>\n";
  echo "\t</p>\n";
  echo "<script type=\"text/javascript\">\n";
  echo "var ajaxurl='".admin_url( 'admin-ajax.php' )."'\n";
  echo "var pppwait='".__( ' Please wait...', perfectpaperpasswords )."';\n";
  echo "var pppcode='".__( ' Code: ', perfectpaperpasswords )."';\n";
  echo "var pppcard='".__( ' Card: ', perfectpaperpasswords )."';\n";
  echo "var pppnonce='".wp_create_nonce('perfectpaperpasswordsaction')."';\n";
  echo <<<ENDOFJS
jQuery('#user_email').bind('select click focus', function() {
	jQuery('#pppinfo').html(pppwait);
	var data=new Object();
	data['action']	= 'perfectpaperpasswords_action';
	data['login']	= jQuery('#user_login').val();
	data['nonce']	= pppnonce;
	jQuery.post(ajaxurl, data, function(response) {
		var pppinfo='  '+pppcode+response['character']+response['number']+pppcard+response['cardnumber'];
  		jQuery('#pppinfo').html(pppinfo);
  	});  	
});  
</script>

ENDOFJS;
}


/**
 * The Perfect Paper Passwords login form needs jQuery.
 */
function perfectpaperpasswords_login_enqueue_scripts() {
	wp_enqueue_script( 'jquery' );
}

/**
 * Login form handling.
 * Perforn Perfect Paper Passwords check if user has been setup to do so.
 * @param wordpressuser
 * @return user/loginstatus
 */
function perfectpaperpasswords_check_ppp( $user ) {
	// Does the user have Perfect Paper Passwords enabled ?
	if ( trim(get_user_option('perfectpaperpasswords_enabled',$user->ID)) == 'enabled' ) {

		// Get user specific settings
		$ppp_number = intval( trim( get_user_option( 'perfectpaperpasswords_number', $user->ID ) ) );
		$ppp_secret = trim( get_user_option( 'perfectpaperpasswords_secret', $user->ID ) );
		$ppp_len	= intval( trim( get_user_option( 'perfectpaperpasswords_length', $user->ID ) ) );
		// Get the Perfect Paper Password entered by the user trying to login
		$otp = trim( $_POST[ 'otp' ] );
		
		$ppp = new PerfectPaperPasswords( $ppp_secret );
		if ( $ppp->getPerfectPaperPassword($ppp_number, $ppp_len) != $otp ) {
			// Perfect Paper Password doesn't match, refuse login.
			return false;
		} else {
			// Increment highest Perfect Password Password number used on this system
			update_option( 'perfectpaperpasswords_high', intval( trim( get_option( 'perfectpaperpasswords_high' ) ) ) +1 );
			// Increment users used passwords
			update_user_option( $user->ID, 'perfectpaperpasswords_number', $ppp_number+1, true );
		}
	}	
	return $user;
}


/**
 * Extend personal profile page with Perfect Paper Passwords settings.
 */
function perfectpaperpasswords_profile_personal_options() {
	global $user_id, $is_profile_page;
	
	$ppp_secret			=trim( get_user_option( 'perfectpaperpasswords_secret', $user_id ) );
	$ppp_sequencekey	=trim( get_user_option( 'perfectpaperpasswords_sequencekey', $user_id ) );
	$ppp_enabled		=trim( get_user_option( 'perfectpaperpasswords_enabled', $user_id ) );
	$ppp_number			=intval( trim( get_user_option( 'perfectpaperpasswords_number', $user_id ) ) );
	$ppp_len			=intval( trim( get_user_option( 'perfectpaperpasswords_length', $user_id ) ) );
	
	// Set default Perfect Paper Password length
	if ( $ppp_len == 0 ) $ppp_len=4;

	echo "<h3>".__( 'Perfect Paper Passwords settings', 'perfectpaperpasswords' )."</h3>\n";

	echo "<table class=\"form-table\">\n";
	echo "<tbody>\n";
	echo "<tr>\n";
	echo "<th scope=\"row\">".__( 'Active', 'perfectpaperpasswords' )."</th>\n";
	echo "<td>\n";

	echo "<div><input name=\"ppp_enabled\" id=\"ppp_enabled\" class=\"tog\" type=\"checkbox\"";
	if ( $ppp_enabled == 'enabled' ) {
		echo ' checked="checked"';
	}
	echo "/>";
	echo "</div>\n";

	echo "</td>\n";
	echo "</tr>\n";

	if ( $is_profile_page || IS_PROFILE_PAGE ) {
		echo "<tr>\n";
		echo "<th><label for=\"ppp_len\">".__('Length','perfectpaperpasswords')."</label></th>\n";
		echo "<td>";
		echo "<select name=\"ppp_len\" id=\"ppp_len\">\n";
		for ( $plen = 2; $plen < 17; $plen++ ) {
			echo "<option value=\"".$plen."\"";
			if ( $plen == $ppp_len ) echo " selected=\"selected\"";
			echo"> ".$plen." </option>\n";
		}	
		echo "</select>\n";
		echo "<span class=\"description\">".__(' Perfect Paper Passwords length','perfectpaperpasswords')."</span></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<th><label for=\"ppp_secret\">".__('Secret','perfectpaperpasswords')."</label></th>\n";
		echo "<td><input name=\"ppp_secret\" id=\"ppp_secret\" value=\"".$ppp_secret."\" style=\"width: 500px\" type=\"text\"/><span class=\"description\">".__(' Your Perfect Paper Passwords secret','perfectpaperpasswords')."</span><br /></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<th><label for=\"ppp_sequencekey\">".__('Sequence Key','perfectpaperpasswords')."</label></th>\n";
		echo "<td><input name=\"ppp_sequencekey\" id=\"ppp_sequencekey\" value=\"".$ppp_sequencekey."\" style=\"width: 500px\" type=\"text\"  /><span class=\"description\"> ";
		echo __(' You can\'t edit this, but it\'s needed to print password cards at <a href="https://www.grc.com/ppp.htm">http://grc.com</a>','perfectpaperpasswords')."</span><br /></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<th><label for=\"ppp_number\">".__('Used passwords','perfectpaperpasswords')."</label></th>\n";
		echo "<td><input name=\"ppp_number\" id=\"ppp_number\" value=\"".$ppp_number."\" style=\"width: 500px\" type=\"text\" disabled=\"disabled\"  /><span class=\"description\"> ".__(' This value will get reset if you change your Secret.','perfectpaperpasswords')."</span><br /></td>\n";
		echo "</tr>\n";		
	}

	echo "</tbody></table>\n";
}

/**
 * Form handling of Perfect Paper Passwords options added to personal profile page (user editing his own profile)
 */
function perfectpaperpasswords_personal_options_update() {
	global $user_id;

	$ppp_enabled	= trim( $_POST['ppp_enabled'] );
	$ppp_secret		= stripslashes( trim( $_POST['ppp_secret'] ) );
	$ppp_len		= intval( trim( $_POST['ppp_len'] ) );
	
	
	// Existing Perfect Paper Passwords secret
	$old_ppp_secret =trim( get_user_option( 'perfectpaperpasswords_secret', $user_id ) );

	// Don't enable Perfect Paper Passwords unless a secret has been entered
	if ( $ppp_enabled != '' && strlen( $ppp_secret ) ) {
		update_user_option( $user_id, 'perfectpaperpasswords_enabled', 'enabled', true );
	} else {
		update_user_option( $user_id, 'perfectpaperpasswords_enabled', 'disabled', true );
	}
	
	// If the secret is changed we reset the password counter
	if ( $ppp_secret != $old_ppp_secret ) {	
		update_user_option( $user_id, 'perfectpaperpasswords_number', '0', true );
	}
	
	// Update secret, sequencekey and password length
	update_user_option( $user_id, 'perfectpaperpasswords_secret', $ppp_secret, true );
	$ppp=new PerfectPaperPasswords( ( $ppp_secret ) );
	update_user_option( $user_id, 'perfectpaperpasswords_sequencekey', $ppp->getSequenceKey(), true );
	update_user_option( $user_id, 'perfectpaperpasswords_length', $ppp_len, true );

}

/**
 * Extend profile page with ability to enable/disable Perfect Paper Passwords authentication requirement.
 * Used by an administrator when editing other users.
 */
function perfectpaperpasswords_edit_user_profile() {
	global $user_id;
	
	$ppp_enabled = trim( get_user_option( 'perfectpaperpasswords_enabled', $user_id ) );
	echo "<h3>".__('Perfect Paper Passwords settings','perfectpaperpasswords')."</h3>\n";
	echo "<table class=\"form-table\">\n";
	echo "<tbody>\n";
	echo "<tr>\n";
	echo "<th scope=\"row\">".__('Active','perfectpaperpasswords')."</th>\n";
	echo "<td>\n";
	echo "<div><input name=\"ppp_enabled\" id=\"ppp_enabled\"  class=\"tog\" type=\"checkbox\"";
	if ( $ppp_enabled == 'enabled' ) {
		echo ' checked="checked"';
	}
	echo "/>\n";
	echo "</td>\n";
	echo "</tr>\n";
	echo "</tbody>\n";
	echo "</table>\n";
}

/**
 * Form handling of Perfect Paper Passwords options on edit profile page (admin user editing other user)
 */
function perfectpaperpasswords_edit_user_profile_update() {
	global $user_id;
	
	$ppp_enabled	= trim( $_POST['ppp_enabled'] );
	
	if ( $ppp_enabled != '' ) {
		update_user_option( $user_id, 'perfectpaperpasswords_enabled', 'enabled', true );
	} else {
		update_user_option( $user_id, 'perfectpaperpasswords_enabled', 'disabled', true );	
	}
}

/**
* AJAX callback function used by the login form to lookup cardnumber and code for user
* trying to login.
*/
function perfectpaperpasswords_callback() {
	// Some AJAX security
	check_ajax_referer('perfectpaperpasswordsaction', 'nonce');
	
	$login = trim( $_POST['login'] );
	$user = get_user_by( 'login', $login );
	
	// Is this a valid Perfect Paper Passwords enabled user ?
	if ( $user && ( get_user_option( 'perfectpaperpasswords_enabled', $user->ID ) ==  'enabled') && get_user_option( 'perfectpaperpasswords_length', $user->ID ) ) {
		// Get next password number and length of password
		$ppp_number = intval( trim( get_user_option( 'perfectpaperpasswords_number', $user->ID ) ) );
		$ppp_len	= intval( trim( get_user_option( 'perfectpaperpasswords_length', $user->ID ) ) );
	} else {
		// This is not a valid or enabled user, we just show some fake values (ToDo: figure out something smarter)
		$ppp_secret	= get_option( 'perfectpaperpasswords_secret' );		
		$ppp_number = intval( trim(get_option( 'perfectpaperpasswords_high', 1 ) ) );
		if ( $ppp_number < 100 ) $ppp_number+=100;
		$hash		= hash_hmac ('sha256' ,$ppp_secret, $login,false);
		$ppp_number	= intval( 1.5 * $ppp_number * ( hexdec( substr( $hash, 0 , 2 ) ) / 255 ) ); 
		$ppp_len	= hexdec( substr( $hash, -1 ) ) | 4;		
	}
	
	// Get password data ie Cardnumber, column and row
	$result = PerfectPaperPasswords::getPasswordCoordinates( $ppp_number, $ppp_len );
	header( "Content-Type: application/json" );
	echo json_encode( $result );
	// die() is required to return a proper result
	die(); 
}

/**
* Does the PHP installation have what it takes to use this plugin ? 
*/
function perfectpaperpasswords_check_requirements() {

	// is SHA-256 hashing available ?
	$perfectpaperpasswordalgos = hash_algos();
	if ( ! in_array( "sha256", $perfectpaperpasswordalgos ) ) {
		return false;
	}
	
	// Is bcmath available ?
	if ( ! function_exists( 'bcadd' ) ) {
		return false;
	}
	
	// Is mcrypt/rijndael-128 (AES-128) available ?
	if ( function_exists( 'mcrypt_list_algorithms' ) ) {
		$perfectpaperpassword_mcrypt_algos = mcrypt_list_algorithms();
		if ( ! in_array( "rijndael-128", $perfectpaperpassword_mcrypt_algos ) ) {
			return false;
		}
	} else {
		return false;
	}	
	return true;
}

/**
* Prevent activation of the plugin if the PHP installation doesn't meet the requirements.
* Create random value for use when displaying code/cardnumber to unknown usernames.
*/
function perfectpaperpasswords_activate() {
	global $user_id;

	if ( ! perfectpaperpasswords_check_requirements()) {
		die( __('Perfect Paper Passwords: Something is missing, this plugin requires the SHA256 hashing algorithm, BCMath library and Mcrypt to be present in your PHP installation.', 'perfectpaperpasswords') );
	}
	if ( ! get_option( 'perfectpaperpasswords_secret' ) ) {
		// we use the users password hash as input for our generation of a secret.
		update_option( 'perfectpaperpasswords_secret', hash( 'sha256', get_user_option( 'user_pass', $user_id ) ) );
	}
}

// Initialization and Hooks
add_action('personal_options_update','perfectpaperpasswords_personal_options_update');
add_action('profile_personal_options','perfectpaperpasswords_profile_personal_options');
add_action('edit_user_profile','perfectpaperpasswords_edit_user_profile');
add_action('edit_user_profile_update','perfectpaperpasswords_edit_user_profile_update');
add_action('wp_ajax_nopriv_perfectpaperpasswords_action', 'perfectpaperpasswords_callback');
add_action('login_form', 'perfectpaperpasswords_loginform');
add_action('login_enqueue_scripts', 'perfectpaperpasswords_login_enqueue_scripts');	
add_filter('wp_authenticate_user','perfectpaperpasswords_check_ppp');	

register_activation_hook( __FILE__, 'perfectpaperpasswords_activate' );

load_plugin_textdomain('perfectpaperpasswords', false , dirname( plugin_basename(__FILE__)).'/lang' );
?>
