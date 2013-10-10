<?php
/**
 * WC_Shipping_USPS class.
 *
 * @extends WC_Shipping_Method
 */
class WC_Shipping_USPS extends WC_Shipping_Method {

	private $endpoint = 'http://production.shippingapis.com/shippingapi.dll';

	private $default_user_id = '150WOOTH2143';

	private $domestic = array( "US", "PR", "VI" );

	private $found_rates;

	private $services = array(
		// Domestic
		"d0"  => "First-Class Mail&#0174; Parcel",
		"d1"  => "Priority Mail&#0174;",
		"d2"  => "Express Mail&#0174; Hold for Pickup",
		"d3"  => "Express Mail&#0174; PO to Address",
		"d4"  => "Standard Post&#8482;",
		"d5"  => "Bound Printed Matter",
		"d6"  => "Media Mail&#0174;",
		"d7"  => "Library Mail",
		"d12" => "First-Class&#8482; Postcard Stamped",
		"d15" => "First-Class&#8482; Large Postcards",
		"d18" => "Priority Mail&#0174; Keys and IDs",
		"d19" => "First-Class&#8482; Keys and IDs",
		"d23" => "Express Mail&#0174; Sunday/Holiday",

		// International
		"i1"  => "Express Mail International&#0174;",
		"i2"  => "Priority Mail International&#0174;",
		"i4"  => "Global Express Guaranteed&#0174;",
		"i5"  => "Global Express Guaranteed&#0174; Document used",
		"i6"  => "Global Express Guaranteed&#0174; Non-Document Rectangular",
		"i7"  => "Global Express Guaranteed&#0174; Non-Document Non-Rectangular",
		"i12" => "Global Express Guaranteed&#0174; Envelope",
		"i13" => "First Class Package Service&#8482; International Letters",
		"i14" => "First Class Package Service&#8482; International Flats",
		"i15" => "First Class Package Service&#8482; International Parcel",
		"i21" => "International Postcards"
	);

	private $flat_rate_boxes = array(
		// Express Mail
		"d13"     => array(
			"name"   => "Express Mail Flat Rate Envelope",
			"length" => "12.5",
			"width"  => "9.5",
			"height" => "0.25",
			"weight" => "70",
			"cost"   => "19.95", // Jan 27 2013
			"type"   => "express"
		),
		"d30"     => array(
			"name"   => "Express Mail Legal Flat Rate Envelope",
			"length" => "9.5",
			"width"  => "15",
			"height" => "0.25",
			"weight" => "70",
			"cost"   => "19.95", // Jan 27 2013
			"type"   => "express"
		),
		"d55"     => array(
			"name"   => "Express Mail Flat Rate Box",
			"length" => "11",
			"width"  => "8.5",
			"height" => "5.5",
			"weight" => "70",
			"cost"   => "39.95", // Jan 27 2013
			"type"   => "express"
		),
		"d63"     => array(
			"name"   => "Express Mail Padded Flat Rate Envelope",
			"length" => "12.5",
			"width"  => "9.5",
			"height" => "1",
			"weight" => "70",
			"cost"   => "19.95", // Jan 27 2013
			"type"   => "express"
		),

		// Priority Mail
		"d16"     => array(
			"name"   => "Priority Mail Flat Rate Envelope",
			"length" => "12.5",
			"width"  => "9.5",
			"height" => "0.25",
			"weight" => "70",
			"cost"   => "5.60", // Jan 27 2013
			"type"   => "priority"
		),
		"d17"     => array(
			"name"   => "Priority Mail Flat Rate Medium Box",
			"length" => "11.875",
			"width"  => "13.625",
			"height" => "3.375",
			"weight" => "70",
			"cost"   => "12.35", // Jan 27 2013
			"type"   => "priority"
		),
		"d17b"     => array(
			"name"   => "Priority Mail Flat Rate Medium Box",
			"length" => "11",
			"width"  => "8.5",
			"height" => "5.5",
			"weight" => "70",
			"cost"   => "12.35", // Jan 27 2013
			"type"   => "priority"
		),
		"d22"     => array(
			"name"   => "Priority Mail Flat Rate Large Box",
			"length" => "12",
			"width"  => "12",
			"height" => "5.5",
			"weight" => "70",
			"cost"   => "16.85", // Jan 27 2013
			"type"   => "priority"
		),
		"d28"     => array(
			"name"   => "Priority Mail Flat Rate Small Box",
			"length" => "5.375",
			"width"  => "8.625",
			"height" => "1.625",
			"weight" => "70",
			"cost"   => "5.80", // Jan 27 2013
			"type"   => "priority"
		),
		"d29"     => array(
			"name"   => "Priority Mail Padded Flat Rate Envelope",
			"length" => "12.5",
			"width"  => "9.5",
			"height" => "1",
			"weight" => "70",
			"cost"   => "5.95", // Jan 27 2013
			"type"   => "priority"
		),
		"d38"     => array(
			"name"   => "Priority Mail Gift Card Flat Rate Envelope",
			"length" => "10",
			"width"  => "7",
			"height" => "0.25",
			"weight" => "70",
			"cost"   => "5.60", // Jan 27 2013
			"type"   => "priority"
		),
		"d40"     => array(
			"name"   => "Priority Mail Window Flat Rate Envelope",
			"length" => "5",
			"width"  => "10",
			"height" => "0.25",
			"weight" => "70",
			"cost"   => "5.60", // Jan 27 2013
			"type"   => "priority"
		),
		"d42"     => array(
			"name"   => "Priority Mail Small Flat Rate Envelope",
			"length" => "6",
			"width"  => "10",
			"height" => "0.25",
			"weight" => "70",
			"cost"   => "5.60", // Jan 27 2013
			"type"   => "priority"
		),
		"d44"     => array(
			"name"   => "Priority Mail Legal Flat Rate Envelope",
			"length" => "9.5",
			"width"  => "15",
			"height" => "0.5",
			"weight" => "70",
			"cost"   => "5.75", // Jan 27 2013
			"type"   => "priority"
		),

		// International Priority Mail
		"i8"      => array(
			"name"   => "Priority Mail Flat Rate Envelope (International)",
			"length" => "12.5",
			"width"  => "9.5",
			"height" => "0.25",
			"weight" => "4",
			"cost"   => "23.95", // Jan 27 2013
			"cost_CA"  => "19.95", // Jan 27 2013
			"type"   => "priority"
		),
		"i16"     => array(
			"name"   => "Priority Mail Flat Rate Small Box (International)",
			"length" => "5.375",
			"width"  => "8.625",
			"height" => "1.625",
			"weight" => "4",
			"cost"   => "23.95", // Jan 27 2013
			"cost_CA"  => "19.95", // Jan 27 2013
			"type"   => "priority"
		),
		"i9"      => array(
			"name"   => "Priority Mail Flat Rate Medium Box (International)",
			"length" => "11.875",
			"width"  => "13.625",
			"height" => "3.375",
			"weight" => "20",
			"cost"   => "59.95", // Jan 27 2013
			"cost_CA"  => "40.95", // Jan 27 2013
			"type"   => "priority"
		),
		"i9b"      => array(
			"name"   => "Priority Mail Flat Rate Medium Box (International)",
			"length" => "11",
			"width"  => "8.5",
			"height" => "5.5",
			"weight" => "70",
			"cost"   => "59.95", // Jan 27 2013
			"cost_CA"  => "40.95", // Jan 27 2013
			"type"   => "priority"
		),
		"i11"     => array(
			"name"   => "Priority Mail Flat Rate Large Box (International)",
			"length" => "12",
			"width"  => "12",
			"height" => "5.5",
			"weight" => "20",
			"cost"   => "77.95", // Jan 27 2013
			"cost_CA"  => "53.95", // Jan 27 2013
			"type"   => "priority"
		)/*,
		"i24"     => array(
			"name"   => "Priority Mail DVD Flat Rate Box (International)",
			"length" => "7.5625",
			"width"  => "5.4375",
			"height" => "0.625",
			"weight" => "20",
			"cost"   => "16.95",
			"cost_CA"  => "12.95",
			"type"   => "priority"
		),
		"i25"     => array(
			"name"   => "Priority Mail Large Video Flat Rate Box (International)",
			"length" => "9.25",
			"width"  => "6.25",
			"height" => "2",
			"weight" => "20",
			"cost"   => "16.95",
			"cost_CA"  => "12.95",
			"type"   => "priority"
		)*/
	);

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		$this->id                 = 'usps';
		$this->method_title       = __( 'USPS', 'wc_usps' );
		$this->method_description = __( 'The <strong>USPS</strong> extension obtains rates dynamically from the USPS API during cart/checkout.', 'wc_usps' );
		$this->init();
	}

    /**
     * init function.
     *
     * @access public
     * @return void
     */
    private function init() {
		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->enabled		   = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : $this->enabled;
		$this->title           = isset( $this->settings['title'] ) ? $this->settings['title'] : $this->method_title;
		$this->availability    = isset( $this->settings['availability'] ) ? $this->settings['availability'] : 'all';
		$this->countries       = isset( $this->settings['countries'] ) ? $this->settings['countries'] : array();
		$this->origin          = isset( $this->settings['origin'] ) ? $this->settings['origin'] : '';
		$this->user_id         = ! empty( $this->settings['user_id'] ) ? $this->settings['user_id'] : $this->default_user_id;
		$this->packing_method  = isset( $this->settings['packing_method'] ) ? $this->settings['packing_method'] : 'per_item';
		$this->boxes           = isset( $this->settings['boxes'] ) ? $this->settings['boxes'] : array();
		$this->custom_services = isset( $this->settings['services'] ) ? $this->settings['services'] : array();
		$this->offer_rates     = isset( $this->settings['offer_rates'] ) ? $this->settings['offer_rates'] : 'all';
		$this->fallback		   = ! empty( $this->settings['fallback'] ) ? $this->settings['fallback'] : '';

		$this->enable_standard_services = isset( $this->settings['enable_standard_services'] ) && $this->settings['enable_standard_services'] == 'yes' ? true : false;

		$this->enable_flat_rate_boxes = isset( $this->settings['enable_flat_rate_boxes'] ) && $this->settings['enable_flat_rate_boxes'] == 'yes' ? true : false;
		$this->debug           = isset( $this->settings['debug_mode'] ) && $this->settings['debug_mode'] == 'yes' ? true : false;

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'clear_transients' ) );
	}

	/**
	 * environment_check function.
	 *
	 * @access public
	 * @return void
	 */
	private function environment_check() {
		global $woocommerce;

		if ( get_woocommerce_currency() != "USD" ) {
			echo '<div class="error">
				<p>' . sprintf( __( 'USPS requires that the <a href="%s">currency</a> is set to US Dollars.', 'wc_usps' ), admin_url( 'admin.php?page=woocommerce_settings&tab=catalog' ) ) . '</p>
			</div>';
		}

		elseif ( ! in_array( $woocommerce->countries->get_base_country(), $this->domestic ) ) {
			echo '<div class="error">
				<p>' . sprintf( __( 'USPS requires that the <a href="%s">base country/region</a> is the United States.', 'wc_usps' ), admin_url( 'admin.php?page=woocommerce_settings&tab=general' ) ) . '</p>
			</div>';
		}

		elseif ( ! $this->origin && $this->enabled == 'yes' ) {
			echo '<div class="error">
				<p>' . __( 'USPS is enabled, but the origin postcode has not been set.', 'wc_usps' ) . '</p>
			</div>';
		}
	}

	/**
	 * admin_options function.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_options() {
		// Check users environment supports this method
		$this->environment_check();

		// Show settings
		parent::admin_options();
	}

	/**
	 * generate_services_html function.
	 *
	 * @access public
	 * @return void
	 */
	function generate_services_html() {
		ob_start();
		?>
		<tr valign="top" id="service_options">
			<th scope="row" class="titledesc"><?php _e( 'Services', 'wc_usps' ); ?></th>
			<td class="forminp">
				<table class="usps_services widefat">
					<thead>
						<th class="sort">&nbsp;</th>
						<th><?php _e( 'Service Code', 'wc_usps' ); ?></th>
						<th><?php _e( 'Name', 'wc_usps' ); ?></th>
						<th><?php _e( 'Enabled', 'wc_usps' ); ?></th>
						<th><?php echo sprintf( __( 'Price Adjustment (%s)', 'wc_usps' ), get_woocommerce_currency_symbol() ); ?></th>
						<th><?php _e( 'Price Adjustment (%)', 'wc_usps' ); ?></th>
					</thead>
					<tbody>
						<?php
							$sort = 0;
							$this->ordered_services = array();

							foreach ( $this->services as $code => $name ) {

								if ( isset( $this->custom_services[ $code ]['order'] ) ) {
									$sort = $this->custom_services[ $code ]['order'];
								}

								while ( isset( $this->ordered_services[ $sort ] ) )
									$sort++;

								$this->ordered_services[ $sort ] = array( $code, $name );

								$sort++;
							}

							ksort( $this->ordered_services );

							foreach ( $this->ordered_services as $value ) {
								$code = $value[0];
								$name = $value[1];
								?>
								<tr>
									<td class="sort"><input type="hidden" class="order" name="usps_service[<?php echo $code; ?>][order]" value="<?php echo isset( $this->custom_services[ $code ]['order'] ) ? $this->custom_services[ $code ]['order'] : ''; ?>" /></td>
									<td><strong><?php echo $code; ?></strong></td>
									<td><input type="text" name="usps_service[<?php echo $code; ?>][name]" placeholder="<?php echo $name; ?> (<?php echo $this->title; ?>)" value="<?php echo isset( $this->custom_services[ $code ]['name'] ) ? $this->custom_services[ $code ]['name'] : ''; ?>" size="50" /></td>
									<td><input type="checkbox" name="usps_service[<?php echo $code; ?>][enabled]" <?php checked( ( ! isset( $this->custom_services[ $code ]['enabled'] ) || ! empty( $this->custom_services[ $code ]['enabled'] ) ), true ); ?> /></td>
									<td><input type="text" name="usps_service[<?php echo $code; ?>][adjustment]" placeholder="N/A" value="<?php echo isset( $this->custom_services[ $code ]['adjustment'] ) ? $this->custom_services[ $code ]['adjustment'] : ''; ?>" size="4" /></td>
									<td><input type="text" name="usps_service[<?php echo $code; ?>][adjustment_percent]" placeholder="N/A" value="<?php echo isset( $this->custom_services[ $code ]['adjustment_percent'] ) ? $this->custom_services[ $code ]['adjustment_percent'] : ''; ?>" size="4" /></td>
								</tr>
								<?php
							}
						?>
					</tbody>
				</table>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * generate_box_packing_html function.
	 *
	 * @access public
	 * @return void
	 */
	public function generate_box_packing_html() {
		ob_start();
		?>
		<tr valign="top" id="packing_options">
			<th scope="row" class="titledesc"><?php _e( 'Box Sizes', 'wc_usps' ); ?></th>
			<td class="forminp">
				<style type="text/css">
					.usps_boxes td, .usps_services td {
						vertical-align: middle;
						padding: 4px 7px;
					}
					.usps_boxes td input {
						margin-right: 4px;
					}
					.usps_boxes .check-column {
						vertical-align: middle;
						text-align: left;
						padding: 0 7px;
					}
					.usps_services th.sort {
						width: 16px;
					}
					.usps_services td.sort {
						cursor: move;
						width: 16px;
						padding: 0;
						cursor: move;
						background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAgAAAAICAYAAADED76LAAAAHUlEQVQYV2O8f//+fwY8gJGgAny6QXKETRgEVgAAXxAVsa5Xr3QAAAAASUVORK5CYII=) no-repeat center;					}
				</style>
				<table class="usps_boxes widefat">
					<thead>
						<tr>
							<th class="check-column"><input type="checkbox" /></th>
							<th><?php _e( 'Outer Length', 'wc_usps' ); ?></th>
							<th><?php _e( 'Outer Width', 'wc_usps' ); ?></th>
							<th><?php _e( 'Outer Height', 'wc_usps' ); ?></th>
							<th><?php _e( 'Inner Length', 'wc_usps' ); ?></th>
							<th><?php _e( 'Inner Width', 'wc_usps' ); ?></th>
							<th><?php _e( 'Inner Height', 'wc_usps' ); ?></th>
							<th><?php _e( 'Box Weight', 'wc_usps' ); ?></th>
							<th><?php _e( 'Max Weight', 'wc_usps' ); ?></th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th colspan="3">
								<a href="#" class="button plus insert"><?php _e( 'Add Box', 'wc_usps' ); ?></a>
								<a href="#" class="button minus remove"><?php _e( 'Remove selected box(es)', 'wc_usps' ); ?></a>
							</th>
							<th colspan="6">
								<small class="description"><?php _e( 'Items will be packed into these boxes based on item dimensions and volume. Outer dimensions will be passed to USPS, whereas inner dimensions will be used for packing. Items not fitting into boxes will be packed individually.', 'wc_usps' ); ?></small>
							</th>
						</tr>
					</tfoot>
					<tbody id="rates">
						<?php
							if ( $this->boxes ) {
								foreach ( $this->boxes as $key => $box ) {
									?>
									<tr>
										<td class="check-column"><input type="checkbox" /></td>
										<td><input type="text" size="5" name="boxes_outer_length[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['outer_length'] ); ?>" />in</td>
										<td><input type="text" size="5" name="boxes_outer_width[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['outer_width'] ); ?>" />in</td>
										<td><input type="text" size="5" name="boxes_outer_height[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['outer_height'] ); ?>" />in</td>
										<td><input type="text" size="5" name="boxes_inner_length[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['inner_length'] ); ?>" />in</td>
										<td><input type="text" size="5" name="boxes_inner_width[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['inner_width'] ); ?>" />in</td>
										<td><input type="text" size="5" name="boxes_inner_height[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['inner_height'] ); ?>" />in</td>
										<td><input type="text" size="5" name="boxes_box_weight[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['box_weight'] ); ?>" />lbs</td>
										<td><input type="text" size="5" name="boxes_max_weight[<?php echo $key; ?>]" value="<?php echo esc_attr( $box['max_weight'] ); ?>" />lbs</td>
									</tr>
									<?php
								}
							}
						?>
					</tbody>
				</table>
				<script type="text/javascript">

					jQuery(window).load(function(){

						jQuery('#woocommerce_usps_enable_standard_services').change(function(){
							if ( jQuery(this).is(':checked') ) {
								jQuery('#service_options, #packing_options').show();
								jQuery('#woocommerce_usps_packing_method, #woocommerce_usps_offer_rates').closest('tr').show();
								jQuery('#woocommerce_usps_packing_method').change();
							} else {
								jQuery('#service_options, #packing_options').hide();
								jQuery('#woocommerce_usps_packing_method, #woocommerce_usps_offer_rates').closest('tr').hide();
							}
						}).change();

						jQuery('#woocommerce_usps_packing_method').change(function(){

							if ( jQuery('#woocommerce_usps_enable_standard_services').is(':checked') ) {

								if ( jQuery(this).val() == 'box_packing' )
									jQuery('#packing_options').show();
								else
									jQuery('#packing_options').hide();

								if ( jQuery(this).val() == 'weight' )
									jQuery('#woocommerce_usps_max_weight').closest('tr').show();
								else
									jQuery('#woocommerce_usps_max_weight').closest('tr').hide();

							}

						}).change();

						jQuery('.usps_boxes .insert').click( function() {
							var $tbody = jQuery('.usps_boxes').find('tbody');
							var size = $tbody.find('tr').size();
							var code = '<tr class="new">\
									<td class="check-column"><input type="checkbox" /></td>\
									<td><input type="text" size="5" name="boxes_outer_length[' + size + ']" />in</td>\
									<td><input type="text" size="5" name="boxes_outer_width[' + size + ']" />in</td>\
									<td><input type="text" size="5" name="boxes_outer_height[' + size + ']" />in</td>\
									<td><input type="text" size="5" name="boxes_inner_length[' + size + ']" />in</td>\
									<td><input type="text" size="5" name="boxes_inner_width[' + size + ']" />in</td>\
									<td><input type="text" size="5" name="boxes_inner_height[' + size + ']" />in</td>\
									<td><input type="text" size="5" name="boxes_box_weight[' + size + ']" />lbs</td>\
									<td><input type="text" size="5" name="boxes_max_weight[' + size + ']" />lbs</td>\
								</tr>';

							$tbody.append( code );

							return false;
						} );

						jQuery('.usps_boxes .remove').click(function() {
							var $tbody = jQuery('.usps_boxes').find('tbody');

							$tbody.find('.check-column input:checked').each(function() {
								jQuery(this).closest('tr').hide().find('input').val('');
							});

							return false;
						});

						// Ordering
						jQuery('.usps_services tbody').sortable({
							items:'tr',
							cursor:'move',
							axis:'y',
							handle: '.sort',
							scrollSensitivity:40,
							forcePlaceholderSize: true,
							helper: 'clone',
							opacity: 0.65,
							placeholder: 'wc-metabox-sortable-placeholder',
							start:function(event,ui){
								ui.item.css('baclbsround-color','#f6f6f6');
							},
							stop:function(event,ui){
								ui.item.removeAttr('style');
								usps_services_row_indexes();
							}
						});

						function usps_services_row_indexes() {
							jQuery('.usps_services tbody tr').each(function(index, el){
								jQuery('input.order', el).val( parseInt( jQuery(el).index('.usps_services tr') ) );
							});
						};

					});

				</script>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * validate_box_packing_field function.
	 *
	 * @access public
	 * @param mixed $key
	 * @return void
	 */
	public function validate_box_packing_field( $key ) {

		$boxes = array();

		if ( isset( $_POST['boxes_outer_length'] ) ) {
			$boxes_outer_length = $_POST['boxes_outer_length'];
			$boxes_outer_width  = $_POST['boxes_outer_width'];
			$boxes_outer_height = $_POST['boxes_outer_height'];
			$boxes_inner_length = $_POST['boxes_inner_length'];
			$boxes_inner_width  = $_POST['boxes_inner_width'];
			$boxes_inner_height = $_POST['boxes_inner_height'];
			$boxes_box_weight   = $_POST['boxes_box_weight'];
			$boxes_max_weight   = $_POST['boxes_max_weight'];


			for ( $i = 0; $i < sizeof( $boxes_outer_length ); $i ++ ) {

				if ( $boxes_outer_length[ $i ] && $boxes_outer_width[ $i ] && $boxes_outer_height[ $i ] && $boxes_inner_length[ $i ] && $boxes_inner_width[ $i ] && $boxes_inner_height[ $i ] ) {

					$boxes[] = array(
						'outer_length' => floatval( $boxes_outer_length[ $i ] ),
						'outer_width'  => floatval( $boxes_outer_width[ $i ] ),
						'outer_height' => floatval( $boxes_outer_height[ $i ] ),
						'inner_length' => floatval( $boxes_inner_length[ $i ] ),
						'inner_width'  => floatval( $boxes_inner_width[ $i ] ),
						'inner_height' => floatval( $boxes_inner_height[ $i ] ),
						'box_weight'   => floatval( $boxes_box_weight[ $i ] ),
						'max_weight'   => floatval( $boxes_max_weight[ $i ] ),
					);

				}

			}
		}

		return $boxes;
	}

	/**
	 * validate_services_field function.
	 *
	 * @access public
	 * @param mixed $key
	 * @return void
	 */
	public function validate_services_field( $key ) {
		$services         = array();
		$posted_services  = $_POST['usps_service'];

		foreach ( $posted_services as $code => $settings ) {

			$services[ $code ] = array(
				'name'               => woocommerce_clean( $settings['name'] ),
				'order'              => woocommerce_clean( $settings['order'] ),
				'enabled'            => isset( $settings['enabled'] ) ? true : false,
				'adjustment'         => woocommerce_clean( $settings['adjustment'] ),
				'adjustment_percent' => str_replace( '%', '', woocommerce_clean( $settings['adjustment_percent'] ) )
			);

		}

		return $services;
	}

	/**
	 * clear_transients function.
	 *
	 * @access public
	 * @return void
	 */
	public function clear_transients() {
		delete_transient( 'wc_usps_quotes' );
	}

    /**
     * init_form_fields function.
     *
     * @access public
     * @return void
     */
    public function init_form_fields() {
	    global $woocommerce;

    	$this->form_fields  = array(
			'enabled'          => array(
				'title'           => __( 'Enable/Disable', 'wc_usps' ),
				'type'            => 'checkbox',
				'label'           => __( 'Enable this shipping method', 'wc_usps' ),
				'default'         => 'no'
			),
			'title'            => array(
				'title'           => __( 'Method Title', 'wc_usps' ),
				'type'            => 'text',
				'description'     => __( 'This controls the title which the user sees during checkout.', 'wc_usps' ),
				'default'         => __( 'USPS', 'wc_usps' )
			),
			'origin'           => array(
				'title'           => __( 'Origin Postcode', 'wc_usps' ),
				'type'            => 'text',
				'description'     => __( 'Enter the postcode for the <strong>sender</strong>.', 'wc_usps' ),
				'default'         => ''
		    ),
		    'availability'  => array(
				'title'           => __( 'Method Availability', 'wc_usps' ),
				'type'            => 'select',
				'default'         => 'all',
				'class'           => 'availability',
				'options'         => array(
					'all'            => __( 'All Countries', 'wc_usps' ),
					'specific'       => __( 'Specific Countries', 'wc_usps' ),
				),
			),
			'countries'        => array(
				'title'           => __( 'Specific Countries', 'wc_usps' ),
				'type'            => 'multiselect',
				'class'           => 'chosen_select',
				'css'             => 'width: 450px;',
				'default'         => '',
				'options'         => $woocommerce->countries->get_allowed_countries(),
			),
		    'api'           => array(
				'title'           => __( 'API Settings', 'wc_usps' ),
				'type'            => 'title',
				'description'     => __( 'You can obtaining a USPS user ID by signing up via their website, or just use ours. This is optional.', 'wc_usps' ),
		    ),
		    'user_id'           => array(
				'title'           => __( 'USPS User ID', 'wc_usps' ),
				'type'            => 'text',
				'description'     => __( 'Obtained from USPS after getting an account.', 'wc_usps' ),
				'default'         => '',
				'placeholder'     => $this->default_user_id
		    ),
		    'debug_mode'  => array(
				'title'           => __( 'Debug Mode', 'wc_usps' ),
				'label'           => __( 'Enable debug mode', 'wc_usps' ),
				'type'            => 'checkbox',
				'default'         => 'yes',
				'description'     => __( 'Enable debug mode to show debugging information on your cart/checkout.', 'wc_usps' )
			),
		    'rates'           => array(
				'title'           => __( 'Rates and Services', 'wc_usps' ),
				'type'            => 'title',
				'description'     => __( 'The following settings determine the rates you offer your customers.', 'wc_usps' ),
		    ),
		    'enable_flat_rate_boxes'  => array(
				'title'           => __( 'Flat Rate Boxes', 'wc_usps' ),
				'label'           => __( 'Enable Flat Rate Boxes', 'wc_usps' ),
				'type'            => 'checkbox',
				'default'         => 'yes',
				'description'     => __( 'Enable this option to offer shipping using USPS Flat Rate boxes. Items will be packed into the boxes and the customer will be offered a single rate from these.', 'wc_usps' )
			),
			'enable_standard_services'  => array(
				'title'           => __( 'Standard Services', 'wc_usps' ),
				'label'           => __( 'Enable Standard Services', 'wc_usps' ),
				'type'            => 'checkbox',
				'default'         => 'no',
				'description'     => __( 'Enable non-flat rate services.', 'wc_usps' )
			),
			'packing_method'  => array(
				'title'           => __( 'Parcel Packing Method', 'wc_usps' ),
				'type'            => 'select',
				'default'         => '',
				'class'           => 'packing_method',
				'options'         => array(
					'per_item'       => __( 'Default: Pack items individually', 'wc_usps' ),
					'box_packing'    => __( 'Recommended: Pack into boxes with weights and dimensions', 'wc_usps' ),
				),
			),
			'boxes'  => array(
				'type'            => 'box_packing'
			),
			'offer_rates'   => array(
				'title'           => __( 'Offer Rates', 'wc_usps' ),
				'type'            => 'select',
				'description'     => '',
				'default'         => 'all',
				'options'         => array(
				    'all'         => __( 'Offer the customer all returned rates', 'wc_usps' ),
				    'cheapest'    => __( 'Offer the customer the cheapest rate only', 'wc_usps' ),
				),
		    ),
		    'fallback' => array(
				'title'       => __( 'Fallback', 'wc_usps' ),
				'type'        => 'text',
				'description' => __( 'If USPS returns no matching rates, offer this amount for shipping so that the user can still checkout. Leave blank to disable.', 'wc_usps' ),
				'default'     => ''
			),
			'services'  => array(
				'type'            => 'services'
			),
		);
    }

    /**
     * calculate_shipping function.
     *
     * @access public
     * @param mixed $package
     * @return void
     */
    public function calculate_shipping( $package ) {
    	global $woocommerce;

    	$this->rates      = array();
    	$domestic         = in_array( $package['destination']['country'], $this->domestic ) ? true : false;

    	if ( $this->enable_standard_services ) {

	    	$results          = get_transient( 'wc_usps_quotes' );
	    	$package_requests = $this->get_package_requests( $package );
	    	$api              = $domestic ? 'RateV4' : 'IntlRateV2';

	    	libxml_use_internal_errors( true );

	    	if ( $package_requests ) {

	    		$request  = '<' . $api . 'Request USERID="' . $this->user_id . '">' . "\n";
	    		$request .= '<Revision>2</Revision>' . "\n";

	    		foreach ( $package_requests as $key => $package_request ) {
	    			$request .= $package_request;
	    		}

	    		$request .= '</' . $api . 'Request>' . "\n";

	    		$request = 'API=' . $api . '&XML=' . str_replace( array( "\n", "\r" ), '', $request );

	    		if ( isset( $results[ md5( $request ) ] ) ) {
	    			$response = $results[ md5( $request ) ];
	    		} else {
	    			$response = wp_remote_post( $this->endpoint,
			    		array(
							'timeout'   => 70,
							'sslverify' => 0,
							'body'      => $request
					    )
					);

					$results[ md5( $request ) ] = $response;
	    		}

	    		if ( $this->debug ) {
					$woocommerce->add_message( 'USPS REQUEST: <pre>' . print_r( htmlspecialchars( $request ), true ) . '</pre>' );
					$woocommerce->add_message( 'USPS RESPONSE: <pre>' . print_r( htmlspecialchars( $response['body'] ), true ) . '</pre>' );
				}

				$xml = simplexml_load_string( '<root>' . preg_replace('/<\?xml.*\?>/','', $response['body'] ) . '</root>' );

				if ( $this->debug ) {
					if ( ! $xml ) {
						$woocommerce->add_error( 'Failed loading XML' );
					}
				}

				if ( ! empty( $xml->{ $api . 'Response' } ) ) {

					$usps_packages = $xml->{ $api . 'Response' }->children();

					if ( $usps_packages ) {

						$index = 0;

						foreach ( $usps_packages as $usps_package ) {

							$cart_item_qty = end( explode( ':', $usps_package->attributes()->ID ) );

							$services = $usps_package->children();

							foreach ( $services as $service ) {

								if ( $domestic ) {
									$rate_code    = 'd' . strval( $service->attributes()->CLASSID );
									$service_name = strip_tags( htmlspecialchars_decode( str_replace( '*', '', (string) $service->{'MailService'} ) ) );
									$rate_cost    = (float) $service->{'Rate'} * $cart_item_qty;
								} else {
									$rate_code    = 'i' . strval( $service->attributes()->ID );
									$service_name = strip_tags( htmlspecialchars_decode( str_replace( '*', '', (string) $service->{'SvcDescription'} ) ) );
									$rate_cost    = (float) $service->{'Postage'} * $cart_item_qty;
								}

								// Handle first class - PARCEL is what we want
								if ( $rate_code == 'd0' && ! strstr( strtoupper( $service_name ), 'PARCEL' ) )
									continue;

								if ( ! array_key_exists( $rate_code, $this->services ) )
									continue;

								$rate_id   = $this->id . ':' . $rate_code;
								$rate_name = $service_name . ' (' . $this->title . ')';

								$this->prepare_rate( $rate_code, $rate_id, $rate_name, $rate_cost );
							}

							$index++;
						}
					}

				} else {
					// No rates
					if ( $this->debug ) {
						$woocommerce->add_error( 'Invalid request; no rates returned' );
					}
				}
			}

			// Set transient
			set_transient( 'wc_usps_quotes', $results );

			// Ensure rates were found for all packages
			if ( $this->found_rates ) {
				foreach ( $this->found_rates as $key => $value ) {
					if ( $value['packages'] < sizeof( $package_requests ) )
						unset( $this->found_rates[ $key ] );
				}
			}
		}

		// Flat Rate boxes quote
		if ( $this->enable_flat_rate_boxes ) {
			// Priority
			$flat_rate = $this->calculate_flat_rate_box_rate( $package, 'priority' );
			if ( $flat_rate )
				$this->found_rates[ $flat_rate['id'] ] = $flat_rate;

			// Express
			$flat_rate = $this->calculate_flat_rate_box_rate( $package, 'express' );
			if ( $flat_rate )
				$this->found_rates[ $flat_rate['id'] ] = $flat_rate;
		}

		// Add rates
		if ( $this->found_rates ) {

			if ( $this->offer_rates == 'all' ) {

				uasort( $this->found_rates, array( $this, 'sort_rates' ) );

				foreach ( $this->found_rates as $key => $rate ) {
					$this->add_rate( $rate );
				}

			} else {

				$cheapest_rate = '';

				foreach ( $this->found_rates as $key => $rate ) {
					if ( ! $cheapest_rate || $cheapest_rate['cost'] > $rate['cost'] )
						$cheapest_rate = $rate;
				}

				$cheapest_rate['label'] = $this->title;

				$this->add_rate( $cheapest_rate );

			}

		// Fallback
		} elseif ( $this->fallback ) {
			$this->add_rate( array(
				'id' 	=> $this->id . '_fallback',
				'label' => $this->title,
				'cost' 	=> $this->fallback,
				'sort'  => 0
			) );
		}

    }

    /**
     * prepare_rate function.
     *
     * @access private
     * @param mixed $rate_code
     * @param mixed $rate_id
     * @param mixed $rate_name
     * @param mixed $rate_cost
     * @return void
     */
    private function prepare_rate( $rate_code, $rate_id, $rate_name, $rate_cost ) {

	    // Name adjustment
		if ( ! empty( $this->custom_services[ $rate_code ]['name'] ) )
			$rate_name = $this->custom_services[ $rate_code ]['name'];

		// Cost adjustment %
		if ( ! empty( $this->custom_services[ $rate_code ]['adjustment_percent'] ) )
			$rate_cost = $rate_cost + ( $rate_cost * ( floatval( $this->custom_services[ $rate_code ]['adjustment_percent'] ) / 100 ) );
		// Cost adjustment
		if ( ! empty( $this->custom_services[ $rate_code ]['adjustment'] ) )
			$rate_cost = $rate_cost + floatval( $this->custom_services[ $rate_code ]['adjustment'] );

		// Enabled check
		if ( isset( $this->custom_services[ $rate_code ] ) && empty( $this->custom_services[ $rate_code ]['enabled'] ) )
			return;

		// Merging
		if ( isset( $this->found_rates[ $rate_id ] ) ) {
			$rate_cost = $rate_cost + $this->found_rates[ $rate_id ]['cost'];
			$packages  = 1 + $this->found_rates[ $rate_id ]['packages'];
		} else {
			$packages = 1;
		}

		// Sort
		if ( isset( $this->custom_services[ $rate_code ]['order'] ) ) {
			$sort = $this->custom_services[ $rate_code ]['order'];
		} else {
			$sort = 999;
		}

		$this->found_rates[ $rate_id ] = array(
			'id'       => $rate_id,
			'label'    => $rate_name,
			'cost'     => $rate_cost,
			'sort'     => $sort,
			'packages' => $packages
		);
    }

    /**
     * sort_rates function.
     *
     * @access public
     * @param mixed $a
     * @param mixed $b
     * @return void
     */
    public function sort_rates( $a, $b ) {
		if ( $a['sort'] == $b['sort'] ) return 0;
		return ( $a['sort'] < $b['sort'] ) ? -1 : 1;
    }

    /**
     * get_request function.
     *
     * @access private
     * @return void
     */
    private function get_package_requests( $package ) {

	    // Choose selected packing
    	switch ( $this->packing_method ) {
	    	case 'box_packing' :
	    		$requests = $this->box_shipping( $package );
	    	break;
	    	case 'per_item' :
	    	default :
	    		$requests = $this->per_item_shipping( $package );
	    	break;
    	}

    	return $requests;
    }

    /**
     * per_item_shipping function.
     *
     * @access private
     * @param mixed $package
     * @return void
     */
    private function per_item_shipping( $package ) {
	    global $woocommerce;

	    $requests = array();
	    $domestic = in_array( $package['destination']['country'], $this->domestic ) ? true : false;

    	// Get weight of order
    	foreach ( $package['contents'] as $item_id => $values ) {

    		if ( ! $values['data']->needs_shipping() ) {
    			if ( $this->debug )
    				$woocommerce->add_message( sprintf( __( 'Product # is virtual. Skipping.', 'wc_usps' ), $item_id ) );
    			continue;
    		}

    		if ( ! $values['data']->get_weight() ) {
	    		if ( $this->debug )
	    			$woocommerce->add_error( sprintf( __( 'Product # is missing weight. Aborting.', 'wc_usps' ), $item_id ) );
	    		return;
    		}

    		$weight = woocommerce_get_weight( $values['data']->get_weight(), 'lbs' );
    		$size   = 'REGULAR';

    		if ( $values['data']->length && $values['data']->height && $values['data']->width ) {

				$dimensions = array( woocommerce_get_dimension( $values['data']->length, 'in' ), woocommerce_get_dimension( $values['data']->height, 'in' ), woocommerce_get_dimension( $values['data']->width, 'in' ) );

				sort( $dimensions );

				if ( max( $dimensions ) > 12 ) {
					$size   = 'LARGE';
				}

				$girth = $dimensions[0] + $dimensions[0] + $dimensions[1] + $dimensions[1];
			}

			if ( $domestic ) {

				$request  = '<Package ID="' . $item_id . ':' . $values['quantity'] . '">' . "\n";
				$request .= '	<Service>ALL</Service>' . "\n";
				$request .= '	<ZipOrigination>' . str_replace( ' ', '', strtoupper( $this->origin ) ) . '</ZipOrigination>' . "\n";
				$request .= '	<ZipDestination>' . strtoupper( $package['destination']['postcode'] ) . '</ZipDestination>' . "\n";
				$request .= '	<Pounds>' . floor( $weight ) . '</Pounds>' . "\n";
				$request .= '	<Ounces>' . number_format( ( $weight - floor( $weight ) ) * 16, 2 ) . '</Ounces>' . "\n";
				$request .= '	<Container />' . "\n";
				$request .= '	<Size>' . $size . '</Size>' . "\n";
				$request .= '	<Width>' . $dimensions[1] . '</Width>' . "\n";
				$request .= '	<Length>' . $dimensions[2] . '</Length>' . "\n";
				$request .= '	<Height>' . $dimensions[0] . '</Height>' . "\n";
				$request .= '	<Girth>' . round( $girth ) . '</Girth>' . "\n";
				$request .= '	<Machinable>true</Machinable> ' . "\n";
				$request .= '	<ShipDate>' . date( "d-M-Y", current_time('timestamp') ) . '</ShipDate>' . "\n";
				$request .= '</Package>' . "\n";

			} else {

				$request  = '<Package ID="' . $item_id . ':' . $values['quantity'] . '">' . "\n";
				$request .= '	<Pounds>' . floor( $weight ) . '</Pounds>' . "\n";
				$request .= '	<Ounces>' . number_format( ( $weight - floor( $weight ) ) * 16, 2 ) . '</Ounces>' . "\n";
				$request .= '	<Machinable>true</Machinable> ' . "\n";
				$request .= '	<MailType>Package</MailType>' . "\n";
				$request .= '	<ValueOfContents>' . $values['data']->get_price() . '</ValueOfContents>' . "\n";
				$request .= '	<Country>' . strtoupper( $woocommerce->countries->countries[ $package['destination']['country'] ] ) . '</Country>' . "\n";
				$request .= '	<Container>RECTANGULAR</Container>' . "\n";
				$request .= '	<Size>' . $size . '</Size>' . "\n";
				$request .= '	<Width>' . $dimensions[1] . '</Width>' . "\n";
				$request .= '	<Length>' . $dimensions[2] . '</Length>' . "\n";
				$request .= '	<Height>' . $dimensions[0] . '</Height>' . "\n";
				$request .= '	<Girth>' . round( $girth ) . '</Girth>' . "\n";
				$request .= '	<OriginZip>' . str_replace( ' ', '', strtoupper( $this->origin ) ) . '</OriginZip>' . "\n";
				$request .= '	<CommercialFlag>N</CommercialFlag>' . "\n";
				$request .= '</Package>' . "\n";

			}

			$requests[] = $request;
    	}

		return $requests;
    }

    /**
     * box_shipping function.
     *
     * @access private
     * @param mixed $package
     * @return void
     */
    private function box_shipping( $package ) {
	    global $woocommerce;

	    $requests = array();
	    $domestic = in_array( $package['destination']['country'], $this->domestic ) ? true : false;

	  	if ( ! class_exists( 'WC_Boxpack' ) )
	  		include_once 'box-packer/class-wc-boxpack.php';

	    $boxpack = new WC_Boxpack();

	    // Define boxes
		foreach ( $this->boxes as $box ) {

			$newbox = $boxpack->add_box( $box['outer_length'], $box['outer_width'], $box['outer_height'], $box['box_weight'] );

			$newbox->set_inner_dimensions( $box['inner_length'], $box['inner_width'], $box['inner_height'] );

			if ( $box['max_weight'] )
				$newbox->set_max_weight( $box['max_weight'] );

		}

		// Add items
		foreach ( $package['contents'] as $item_id => $values ) {

			if ( ! $values['data']->needs_shipping() )
				continue;

			if ( $values['data']->length && $values['data']->height && $values['data']->width && $values['data']->weight ) {

				$dimensions = array( $values['data']->length, $values['data']->height, $values['data']->width );

				for ( $i = 0; $i < $values['quantity']; $i ++ ) {
					$boxpack->add_item(
						woocommerce_get_dimension( $dimensions[2], 'in' ),
						woocommerce_get_dimension( $dimensions[1], 'in' ),
						woocommerce_get_dimension( $dimensions[0], 'in' ),
						woocommerce_get_weight( $values['data']->get_weight(), 'lbs' ),
						$values['data']->get_price()
					);
				}

			} else {
				$woocommerce->add_error( sprintf( __( 'Product # is missing dimensions. Aborting.', 'wc_usps' ), $item_id ) );
				return;
			}
		}

		// Pack it
		$boxpack->pack();

		// Get packages
		$box_packages = $boxpack->get_packages();

		foreach ( $box_packages as $key => $box_package ) {

			$weight     = $box_package->weight;
    		$size       = 'REGULAR';
    		$dimensions = array( $box_package->length, $box_package->width, $box_package->height );

			sort( $dimensions );

			if ( max( $dimensions ) > 12 ) {
				$size   = 'LARGE';
			}

			$girth = $dimensions[0] + $dimensions[0] + $dimensions[1] + $dimensions[1];

			if ( $domestic ) {

				$request  = '<Package ID="' . $key . ':1">' . "\n";
				$request .= '	<Service>ALL</Service>' . "\n";
				$request .= '	<ZipOrigination>' . str_replace( ' ', '', strtoupper( $this->origin ) ) . '</ZipOrigination>' . "\n";
				$request .= '	<ZipDestination>' . strtoupper( $package['destination']['postcode'] ) . '</ZipDestination>' . "\n";
				$request .= '	<Pounds>' . floor( $weight ) . '</Pounds>' . "\n";
				$request .= '	<Ounces>' . number_format( ( $weight - floor( $weight ) ) * 16, 2 ) . '</Ounces>' . "\n";
				$request .= '	<Container />' . "\n";
				$request .= '	<Size>' . $size . '</Size>' . "\n";
				$request .= '	<Width>' . $dimensions[1] . '</Width>' . "\n";
				$request .= '	<Length>' . $dimensions[2] . '</Length>' . "\n";
				$request .= '	<Height>' . $dimensions[0] . '</Height>' . "\n";
				$request .= '	<Girth>' . round( $girth ) . '</Girth>' . "\n";
				$request .= '	<Machinable>true</Machinable> ' . "\n";
				$request .= '	<ShipDate>' . date( "d-M-Y", current_time('timestamp') ) . '</ShipDate>' . "\n";
				$request .= '</Package>' . "\n";

			} else {

				$request  = '<Package ID="' . $key . ':1">' . "\n";
				$request .= '	<Pounds>' . floor( $weight ) . '</Pounds>' . "\n";
				$request .= '	<Ounces>' . number_format( ( $weight - floor( $weight ) ) * 16, 2 ) . '</Ounces>' . "\n";
				$request .= '	<Machinable>true</Machinable> ' . "\n";
				$request .= '	<MailType>Package</MailType>' . "\n";
				$request .= '	<ValueOfContents>' . $values['data']->get_price() . '</ValueOfContents>' . "\n";
				$request .= '	<Country>' . $this->get_country_name( $package['destination']['country'] ) . '</Country>' . "\n";
				$request .= '	<Container>RECTANGULAR</Container>' . "\n";
				$request .= '	<Size>' . $size . '</Size>' . "\n";
				$request .= '	<Width>' . $dimensions[1] . '</Width>' . "\n";
				$request .= '	<Length>' . $dimensions[2] . '</Length>' . "\n";
				$request .= '	<Height>' . $dimensions[0] . '</Height>' . "\n";
				$request .= '	<Girth>' . round( $girth ) . '</Girth>' . "\n";
				$request .= '	<OriginZip>' . str_replace( ' ', '', strtoupper( $this->origin ) ) . '</OriginZip>' . "\n";
				$request .= '	<CommercialFlag>N</CommercialFlag>' . "\n";
				$request .= '</Package>' . "\n";

			}

    		$requests[] = $request;
		}

		return $requests;
    }

    /**
     * get_country_name function.
     *
     * @access private
     * @return void
     */
    private function get_country_name( $code ) {
	    global $woocommerce;

	    if ( isset( $woocommerce->countries->countries[ $code ] ) ) {
		    $name = strtoupper( $woocommerce->countries->countries[ $code ] );
	    } else {
		    return false;
	    }

	    if ( $name == 'REPUBLIC OF IRELAND' )
	    	$name = 'IRELAND';

	    return $name;
    }

    /**
     * calculate_flat_rate_box_rate function.
     *
     * @access private
     * @param mixed $package
     * @return void
     */
    private function calculate_flat_rate_box_rate( $package, $box_type = 'priority' ) {
	    global $woocommerce;

	    if ( $this->debug ) {
			$woocommerce->add_message( 'Calculating USPS Flat Rate Boxes' );
		}

	    $cost = 0;

	  	if ( ! class_exists( 'WC_Boxpack' ) )
	  		include_once 'box-packer/class-wc-boxpack.php';

	    $boxpack  = new WC_Boxpack();
	    $domestic = in_array( $package['destination']['country'], $this->domestic ) ? true : false;

	    // Define boxes
		foreach ( $this->flat_rate_boxes as $service_code => $box ) {

			if ( $box['type'] != $box_type )
				continue;

			$domestic_service = substr( $service_code, 0, 1 ) == 'd' ? true : false;

			if ( $domestic && $domestic_service || ! $domestic && ! $domestic_service ) {
				$newbox = $boxpack->add_box( $box['length'], $box['width'], $box['height'] );
				$newbox->set_max_weight( $box['weight'] );
				$newbox->set_id( $service_code );

				if ( $this->debug ) {
					$woocommerce->add_message( 'Adding box: ' . $service_code . ' ' . $box['name'] . ' - ' . $box['length'] . 'x' . $box['width'] . 'x' . $box['height'] );
				}
			}
		}

		// Add items
		foreach ( $package['contents'] as $item_id => $values ) {

			if ( ! $values['data']->needs_shipping() )
				continue;

			if ( $values['data']->length && $values['data']->height && $values['data']->width && $values['data']->weight ) {

				$dimensions = array( $values['data']->length, $values['data']->height, $values['data']->width );

				for ( $i = 0; $i < $values['quantity']; $i ++ ) {
					$boxpack->add_item(
						woocommerce_get_dimension( $dimensions[2], 'in' ),
						woocommerce_get_dimension( $dimensions[1], 'in' ),
						woocommerce_get_dimension( $dimensions[0], 'in' ),
						woocommerce_get_weight( $values['data']->get_weight(), 'lbs' ),
						$values['data']->get_price()
					);
				}

			} else {
				$woocommerce->add_error( sprintf( __( 'Product # is missing dimensions. Aborting.', 'wc_usps' ), $item_id ) );
				return;
			}
		}

		// Pack it
		$boxpack->pack();

		// Get packages
		$flat_packages = $boxpack->get_packages();

		if ( $flat_packages ) {
			foreach ( $flat_packages as $flat_package ) {

				if ( isset( $this->flat_rate_boxes[ $flat_package->id ] ) ) {

					if ( $this->debug ) {
						$woocommerce->add_message( 'Packed ' . $flat_package->id );
					}

					if ( isset( $this->flat_rate_boxes[ $flat_package->id ][ 'cost_' . $package['destination']['country'] ] ) ) {
						$cost += $this->flat_rate_boxes[ $flat_package->id ][ 'cost_' . $package['destination']['country'] ];
					} else {
						$cost += $this->flat_rate_boxes[ $flat_package->id ]['cost'];
					}

				} else {
					return; // no match
				}

			}

			return array(
				'id' 	=> $this->id . ':flat_rate_box_' . $box_type,
				'label' => ( $box_type == 'express' ? 'Express Mail Flat Rate&#0174;' : 'Priority Mail Flat Rate&#0174;' ) . ' (' . $this->title . ')',
				'cost' 	=> $cost,
				'sort'  => ( $box_type == 'express' ? -1 : -2 )
			);
		}
    }
}