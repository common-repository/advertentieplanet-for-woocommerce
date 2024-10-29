<?php

// ------------------------------------------------------------------------------------------------------
// easyads-tab-status
// ------------------------------------------------------------------------------------------------------
//
?>

<div class="easyads-tab easyads-tab-status">
	<table class="easyads-tab easyads-tab-status">
		<tr>
			<td class="easyads-tab-status-td1">
				<strong>Versie</strong><br>
			</td>
			<td class="easyads-tab-status-td2">
				<?php echo ADVERTENTIEPLANET_PLUGIN_VERSION; ?>
			</td>
		</tr>
		<tr>
			<td class="easyads-tab-status-td1">
				<strong>Verbinding</strong><br>
			</td>
			<td class="easyads-tab-status-td2">
				<?php if (advertentiePlanet_getConnectionStatus() == 'disconnected') { ?>
					<div class="easyads-status-off"></div>
				<?php } ?>
				<?php if (advertentiePlanet_getConnectionStatus() == 'waiting_for_callback') { ?>
					<div class="easyads-status-waiting"></div>
				<?php } ?>
				<?php if (advertentiePlanet_getConnectionStatus() == 'connected') { ?>
					<div class="easyads-status-on"></div>
				<?php } ?>
			</td>
			<td >
				<?php if (advertentiePlanet_getConnectionStatus() == 'disconnected') { ?>
					<p>
						<?php echo __('Not connected','advertentieplanet'); ?><br>
						<a id="easyads_connect" href="#">
							<?php echo __('Click here to connect','advertentieplanet'); ?>
						</a>
					</p>
				<?php } ?>
				<?php if (advertentiePlanet_getConnectionStatus() == 'waiting_for_callback') { ?>
					<p>
						<meta http-equiv="refresh" content="10; url=?page=<?php echo ADVERTENTIEPLANET_SLUG; ?>" />
						<?php printf( esc_html__( 'Awaiting confirmation of successful connection to your %1$s account.', 'advertentieplanet' ), ADVERTENTIEPLANET_BRAND_HUMAN ); ?>
					</p>
					<p>
						<?php echo __('Do you believe the connection has failed?','advertentieplanet'); ?> <a href="?page=<?php echo ADVERTENTIEPLANET_SLUG; ?>&connect=connect" target="_blank">Try connecting again.</a>
					</p>
				<?php } ?>
				<?php if (advertentiePlanet_getConnectionStatus() == 'connected') { ?>
					<p>
						<?php echo __('Connected', 'advertentieplanet'); ?>
						<br>
						<a id="easyads_disconnect" href="#">
							<?php echo __('Click here to disconnect from '.ADVERTENTIEPLANET_BRAND_HUMAN, 'advertentieplanet'); ?>
						</a>
					</p>
				<?php } ?>
			</td>
		</tr>
	</table>
</div><!-- .easyads-tab-status -->

<script type="text/javascript">
	window.onload = function() {
		var a = document.getElementById("easyads_disconnect");
		if (a) {
			a.onclick = function() {
				easyads_disconnect();
				return false;
			}
		}
		var ca = document.getElementById("easyads_connect");
		if (ca) {
			ca.onclick = function() {
				easyads_connect();
				return false;
			}
		}
	}

	function easyads_connect() {
		// Open a new tab with the connect link
		var connectUrl = "?page=<?php echo ADVERTENTIEPLANET_SLUG; ?>&connect=connect";

		// Call ourselves to wait for callback
		window.location.href = "?page=<?php echo ADVERTENTIEPLANET_SLUG; ?>&connect=wait_for_callback";

		// Call Easyads website to connect
		window.open(connectUrl);
	}
	function easyads_disconnect() {
		// Open a new tab with the disconnect link
		var disconnectUrl = "<?php echo advertentiePlanet_getEasyAdsUnlinkUrl(); ?>";

		// Call ourselves to disconnect
		window.location.href = "?page=<?php echo ADVERTENTIEPLANET_SLUG; ?>&connect=disconnect";

		// Call Easyads website to disconnect
		window.open(disconnectUrl);
	}
</script>