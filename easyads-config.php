<?php
	require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'easyads-shared-config.php';

	// ADVERTENTIEPLANET_LINK_URL
	// Endpoint for the call to link to AdvertentiePlanet
	define('ADVERTENTIEPLANET_LINK_URL', 'https://app.advertentieplanet.nl/link/woocommerce');
	
	// ADVERTENTIEPLANET_UNLINK_URL
	// Endpoint for the call to unlink from AdvertentiePlanet
	define('ADVERTENTIEPLANET_UNLINK_URL', 'https://app.advertentieplanet.nl/unlink/woocommerce');



	// ADVERTENTIEPLANET_HEADER_TEXT
	// Text to be displayed in the header of the plugin
	define('ADVERTENTIEPLANET_HEADER_TEXT', __('AdvertentiePlanet is the ideal tool for automatic real-time product publishing on a broad variety of marketplaces and shopping sites for all WooCommerce shop owners. <br/>
	Connect your shop, import your products and publish them automatically to the most relevant marketplaces and shopping sites.<br/><br/>
	For documentation and support see <a href="https://www.advertentieplanet.nl" target="_blank">www.advertentieplanet.nl</a>.', 'advertentieplanet'));
