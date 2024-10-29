<?php
// ------------------------------------------------------------------------------------------------------
// Handle the plugin options in the Wordpress settings menu
// ------------------------------------------------------------------------------------------------------
//
add_action( 'admin_menu', 'advertentiePlanet_menu' );
add_action( 'template_redirect', 'advertentiePlanet_handleCallback' );

$plugin_slug = ADVERTENTIEPLANET_SLUG;

// Set a global var to track whether we are on our own options page
// This is used later to include CSS only on our own options page
function advertentiePlanet_menu() {
	global $gAdvertentiePlanet_OptionsPage;
	$gAdvertentiePlanet_OptionsPage = add_options_page(
		'Options for '.ADVERTENTIEPLANET_BRAND_HUMAN, ADVERTENTIEPLANET_BRAND_HUMAN, 'manage_options', ADVERTENTIEPLANET_BRAND_LOWERCASE, 'advertentiePlanet_options'
	);
}

// Load our css for admin pages
add_action( 'admin_enqueue_scripts', 'advertentiePlanet_enqueue_admin_css' );
function advertentiePlanet_enqueue_admin_css($hook) {
	// We only enqueue this css when we are on our own settings page
	// because we change css of Wordpress elements
	// We don't want to change the global look of the site
	global $gAdvertentiePlanet_OptionsPage;
	if ( $hook == $gAdvertentiePlanet_OptionsPage ) {
		wp_register_style( 'advertentiePlanet_admin_css', plugins_url('css/easyads.css', dirname(__FILE__)), false, '1.0.0' );
		wp_enqueue_style( 'advertentiePlanet_admin_css' );
	}
}

// Return the options page
function advertentiePlanet_options() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

	// Does the user want to connect, disconnect or get the status?
	$connect = $_GET['connect'] ? $_GET['connect'] : 'status';
	if ($connect == 'connect') {
		advertentiePlanet_handleConnect();
	}
	else if ($connect == 'wait_for_callback') {
		advertentiePlanet_handleWaitForCallback();
	}
	else if ($connect == 'waiting_for_callback') {
		// Do nothing on purpose
	}
	else if ($connect == 'disconnect') {
		advertentiePlanet_handleDisconnect();
	}
	else if ($connect == 'status') {
		// Do nothing on purpose
	}
	else {
		// Somebody is messing with the query.
		// Fail silently and return status
		// Do nothing on purpose
	}
?>
<div class="wrap">
	<div class="easyads-header-wrap">
		<div class="easyads-header">
			<img src="<?php echo plugins_url('img/logo.svg', dirname(__FILE__)) ?>" height="40" />
			<p class="easyads-intro">
				<?php echo ADVERTENTIEPLANET_HEADER_TEXT; ?>
			</p>
		</div>
	</div>
	<div class="easyads-body">
		<?php
			$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'status-tab';
			global $plugin_slug;
		?>
		<h2 class="nav-tab-wrapper">
				<a href="?page=<?php echo $plugin_slug; ?>&tab=status-tab" class="nav-tab <?php echo $active_tab == 'status-tab' ? 'nav-tab-active' : ''; ?>">Status</a>
		</h2>
		<form method="post" action="options.php">
			<?php
				if ($active_tab == 'status-tab') {
					include('tabs/easyads-tab-status.php');
				}
				else if ($active_tab == 'about-tab') {
					include('tabs/easyads-tab-about.php');
				} else {
					echo 'Error: unknown tab.';
				}
			?>
		</form>
	</div>
</div>
<?php
}

// advertentiePlanet_handleConnect
// 1. Enable the WooCommerce API
// 2. Create the WordPress user that will get access to the WooCommerce API
// 3. Create the WooCommerce API keys for the WordPress user
// 4. Redirect to the EasyAds website
function advertentiePlanet_handleConnect() {
	try {
		advertentiePlanet_enableWoocommerceAPI();
		$user_id = advertentiePlanet_createWordPressUser();
		$api_key = advertentiePlanet_addWoocommerceAPIkey($user_id);
		//update_option(ADVERTENTIEPLANET_STATUS_OPTION_NAME, 'waiting_for_callback');
		advertentiePlanet_redirectToEasyAds($api_key);
	}
	catch (\Exception $e) {
		echo 'Error in advertentiePlanet_handleConnect: ' . $e->getMessage();
		exit;
	}
}

// advertentiePlanet_handleConnect
// 1. Enable the WooCommerce API
// 2. Create the WordPress user that will get access to the WooCommerce API
// 3. Create the WooCommerce API keys for the WordPress user
// 4. Redirect to the EasyAds website
function advertentiePlanet_handleWaitForCallback() {
	try {
		update_option(ADVERTENTIEPLANET_STATUS_OPTION_NAME, 'waiting_for_callback');
	}
	catch (\Exception $e) {
		echo 'Error in advertentiePlanet_handleWaitForCallback: ' . $e->getMessage();
		exit;
	}
}

// advertentiePlanet_handleCallback
// The EasyAds website calls us back with a GUID
// Once we received this call we are connected
// 1. Store the GUID
// 2. Update our status to connected
function advertentiePlanet_handleCallback() {
	if (!isset($_REQUEST['advertentiePlanet_callback'])) return;

	$guid = $_REQUEST['guid'];
	if ($guid && $guid != '') {
		update_option(ADVERTENTIEPLANET_GUID_OPTION_NAME, $guid);
		update_option(ADVERTENTIEPLANET_STATUS_OPTION_NAME, 'connected');
		$result = array('result' => 'ok', 'error' => '');
		echo json_encode($result);
		exit;
	}
	else {
		$result = array('result' => 'error', 'error' => 'guid not found');
		echo json_encode($result);
		exit;
	}
}

// advertentiePlanet_handleDisconnect
// 1. Remove the stored GUID
// 2. Revoke the WooCommerce API keys
function advertentiePlanet_handleDisconnect() {
	$guid = get_option(ADVERTENTIEPLANET_GUID_OPTION_NAME);
	delete_option(ADVERTENTIEPLANET_GUID_OPTION_NAME);
	advertentiePlanet_deleteWoocommerceAPIkey();
	update_option(ADVERTENTIEPLANET_STATUS_OPTION_NAME, 'disconnected');
}

// advertentiePlanet_getConnectionStatus
// Returns the current connection status
function advertentiePlanet_getConnectionStatus() {
	$validStatuses = array('connected', 'waiting_for_callback', 'disconnected');
	$status = get_option(ADVERTENTIEPLANET_STATUS_OPTION_NAME);
	if (in_array($status, $validStatuses, true)) {
		return $status;
	}
	else {
		// Somebody messing with the status record in the database
		// Set it to 'disconnected'
		update_option(ADVERTENTIEPLANET_STATUS_OPTION_NAME, 'disconnected');
		return 'disconnected';
	}
	// We should never get here
	throw new \Exception('advertentiePlanet_getConnectionStatus: An impossible error just occured.');
}

// advertentiePlanet_createWordPressUser
// 1. Create the WordPress user
// 2. Set the role of the WordPress user to "Shop Manager"
function advertentiePlanet_createWordPressUser() {
	// Create the user if needed
	$user_name = ADVERTENTIEPLANET_WP_USERNAME;
	$user_id = username_exists($user_name);
	if (!$user_id) {
		$random_password = wp_generate_password($length=24, $include_standard_special_chars=false);
		$user_id = wp_create_user($user_name, $random_password);
	}
	if (!$user_id) throw new \Exception("advertentiePlanet_createWordPressUser: could not create user $user_name");

	// Ensure the user has role shop_manager
	$user = new WP_User( $user_id );
	$user->set_role('shop_manager');

	return $user_id;
}

// advertentiePlanet_enableWoocommerceAPI
// Enable the WooCommerce API
function advertentiePlanet_enableWoocommerceAPI() {
	// Note, I did not find a formal WooCommerce function to call
	// So we're doing this directly
	update_option('woocommerce_api_enabled', 'yes');
}

// advertentiePlanet_addWoocommerceAPIkey
// Adds WooCommerce API keys for the user ($user_id)
// Note: there is no formal WooCommerce API call that can do this
// for us. Therefore we do it ourselves.
//
// See woocommerce/includes/Class-wc-ajax for an example of how
// WooCommerce generates these keys
function advertentiePlanet_addWoocommerceAPIkey($user_id) {
	if (!$user_id) throw new \Exception('advertentiePlanet_addWoocommerceAPIkeys: User invalid');

	// Always generate a new key.
	// Keys are stored encrypted, so sending the existing key again is useless as we can't decypher it, nor use it.

	// Create the keys
	$description = ADVERTENTIEPLANET_WOO_API_DESCRIPTION;
	$permissions = 'read_write';
	$consumer_key    = 'ck_' . wc_rand_hash();
	$consumer_secret = 'cs_' . wc_rand_hash();

	$data = array(
		'user_id'         => $user_id,
		'description'     => $description,
		'permissions'     => $permissions,
		'consumer_key'    => wc_api_hash( $consumer_key ),
		'consumer_secret' => $consumer_secret,
		'truncated_key'   => substr( $consumer_key, -7 )
	);

	global $wpdb;
	$wpdb->insert(
		$wpdb->prefix . 'woocommerce_api_keys',
		$data,
		array(
			'%d',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s'
		)
	);
	return array('consumer_key' => $consumer_key, 'consumer_secret' => $consumer_secret);
}


// advertentiePlanet_deleteWoocommerceAPIkey
// Delete the WooCommerce API keys
function advertentiePlanet_deleteWoocommerceAPIkey() {
	global $wpdb;
	$user_name = ADVERTENTIEPLANET_WP_USERNAME;
	$user_id = username_exists($user_name);
	if ($user_id) {
		$wpdb->query("DELETE FROM " . $wpdb->prefix . "woocommerce_api_keys WHERE user_id = $user_id");
	}
}

// advertentiePlanet_redirectToEasyAds
// Redirect the user to the following page:
// https://www.easyadswebsite.nl/link/woocommerce?ck=<woocommerce consumer key>&cs=<woocommerce consumer secret>&cb=<urlencoded
// callback url>
function advertentiePlanet_redirectToEasyAds($api_key) {
	wp_redirect(advertentiePlanet_getEasyAdsLinkUrl($api_key));
	exit;
}

// advertentiePlanet_getEasyAdsLinkUrl
// Returns the link url for the Easyads website
function advertentiePlanet_getEasyAdsLinkUrl($apiKeys = false) {
	if (!$apiKeys)
		return;

	global $wp_version;

	//$user_name = ADVERTENTIEPLANET_WP_USERNAME;
	//$user_id = username_exists($user_name);
	//if (!$user_id) throw new \Exception("advertentiePlanet_getCallbackUrl: user " . ADVERTENTIEPLANET_WP_USERNAME . " does not exist");
	//global $wpdb;
	//$api_key = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "woocommerce_api_keys WHERE user_id = $user_id");
	//if (!$api_key) throw new \Exception("advertentiePlanet_getCallbackUrl: api Key does not exist");

	// Parameters for the link url
	// ck = consumer key
	// cs = consumer secret
	// cb = callback url
	// pv = plugin version
	// wpv = wordpress version
	// wcv = woocommerce version

	$userData = wp_get_current_user();

	$ck = urlencode($apiKeys['consumer_key']);
	$cs = urlencode($apiKeys['consumer_secret']);
	$cb = urlencode(advertentiePlanet_getCallbackUrl());
	$pv = urlencode(ADVERTENTIEPLANET_PLUGIN_VERSION);
	$wpv = urlencode($wp_version);
	$wcv = urlencode(get_option( 'woocommerce_version', 'unknown'));
	$e = urlencode($userData->data->user_email);

	$easyads_url = ADVERTENTIEPLANET_LINK_URL . "?ck=$ck&cs=$cs&cb=$cb&pv=$pv&wpv=$wpv&wcv=$wcv&e=$e";
	return $easyads_url;
}

// advertentiePlanet_getEasyAdsUnlinkUrl
// Returns the unlink url for EasyAds website
function advertentiePlanet_getEasyAdsUnlinkUrl() {
	$guid = get_option(ADVERTENTIEPLANET_GUID_OPTION_NAME);
	$url = ADVERTENTIEPLANET_UNLINK_URL . "?guid=$guid";
	return $url;
}

function advertentiePlanet_getCallbackUrl() {
	$callback_url = get_site_url() . "?advertentiePlanet_callback=1";
	return $callback_url;
}