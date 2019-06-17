<?php $display_fields_labels = $wcmca_option_model->display_fields_labels(); ?>

<h3 class="wcmca_additional_addresses_list_title wcmca_shipping_addresses_title"><?php _e( 'Gekoppelde leveradressen','oft' ); ?></h3>

<?php			
	$col_counter = 1;
	foreach( (array) $addresses as $address_id => $address ) {
		$draw_separator = false;
		if ( $col_counter == 1 ) : ?>
		<div class="col2-set addresses">
		<?php endif; ?>	
			<div class="col-<?php echo $col_counter; ?> address">
				<header class="wcmcam_address_block_title">
					<!-- Address name -->
					<<?php echo $default_addresses_style['my_account_page_addresses_title_tag']; ?> class="wcmca_address_title" id="wcmca_field_name_<?php echo $address_id; ?>">
						<?php echo __( 'Levernummer', 'oft' ).' '.$address['shipping_number_oft']; ?>
					</<?php echo $default_addresses_style['my_account_page_addresses_title_tag']; ?>>
					
					<?php if ( ! isset( $type_to_show_in_order_edit_page ) ) : ?> 
						<?php if ( is_admin() || ! $field_managment_options['disable_user_'.$address['type'].'_addresses_editing_capabilities'] ) : ?>
							<a class="wcmca_duplicate_address_button" data-id="<?php echo $address_id;?>" data-type="<?php echo $address['type']; ?>" <?php if(isset($user_id)) echo 'data-user-id="'.$user_id.'"';?> data-id="<?php echo $address_id;?>"  href="#"><?php _e('Duplicate','woocommerce-multiple-customer-addresses'); ?></a> 
							<span class="class_action_sparator">|</span>
							<a class="wcmca_edit_address_button" data-id="<?php echo $address_id;?>" data-type="<?php echo $address['type']; ?>" href="#wcmca_address_form_container_<?php echo $address['type']; ?>"><?php _e('Edit','woocommerce-multiple-customer-addresses'); ?></a> 
							<span class="class_action_sparator">|</span>
							<a class="wcmca_delete_address_button" <?php if(isset($user_id)) echo 'data-user-id="'.$user_id.'"';?> data-id="<?php echo $address_id;?>" data-type="<?php echo $address['type']; ?>" href="#"><?php _e('Delete','woocommerce-multiple-customer-addresses'); ?></a>
							<?php $draw_separator = true; ?>
						<?php endif; ?>
							
						<!-- Default address badge -->
						<?php if ( isset( $address[$address['type']."_is_default_address"] ) ) : ?>
							<?php if ( $draw_separator ) : ?><span class="class_action_sparator">|</span><?php endif; ?>
							<div class="wcmca_default_address_badge" style="<?php echo "background: ".$default_addresses_style['default_badge_backgroud_color']."; color: ".$default_addresses_style['default_badge_text_color'].";"; ?>">
								<?php _e( 'Default leveradres', 'oft' ); ?>
							</div>
						<?php endif; ?>
					<?php else : ?>
						<a class="button button-primary wcmca_primary wcmca_load_address_button" <?php if ( isset( $user_id ) ) echo 'data-user-id="'.$user_id.'"';?> data-id="<?php echo $address_id; ?>" data-type="<?php echo $address['type']; ?>" href="#"><?php _e('Load','woocommerce-multiple-customer-addresses'); ?></a>
					<?php endif; ?>
				</header>
				
				<!-- Addresses list -->
				<address class="wcmca_address_container" id="wcmca_address_details_<?php echo $address_id; ?>">
					<span style="display: none;" class="wcmca_clear_right" id="wcmca_address_internal_name_<?php echo $address_id; ?>" data-name="<?php echo $address['type']; ?>_address_internal_name"><?php echo $address['address_internal_name']; ?></span>
					<span style="display: none;" class="wcmca_clear_right" data-name="<?php echo $address['type']."_is_default_address"; ?>"><?php if ( isset( $address[$address['type']."_is_default_address"] ) ) echo 'yes'; else echo ''; ?></span>
					<?php
						// According to the country, addresses fields are reloaded
						$address_fields = isset($address[$type.'_country']) ? $wcmca_address_model->get_woocommerce_address_fields_by_type($type, $address[$type.'_country']) : array();
						
						// GEWIJZIGD: Default WooCommerce-formaat volgen?
						// echo WC()->countries->get_formatted_address( $address_fields );
						
						foreach ( $address_fields as $field_name => $woocommerce_address_field ) { 
							$woocommerce_address_field['type'] = ! isset( $woocommerce_address_field['type'] ) ? "text" : $woocommerce_address_field['type'];
							$select_field_data = $field_value_to_show = "";
							
							if ( isset( $address[$field_name] ) && in_array( $woocommerce_address_field['type'], $this->allowed_field_type ) ) {
								//Value to show check
								$data_code = is_array($address[$field_name]) ? implode("-||-",$address[$field_name]) : $address[$field_name];
								$field_metadata = $woocommerce_address_field['type'] == 'select' ||  
												  $woocommerce_address_field['type'] == 'multiselect' || 
												  $woocommerce_address_field['type'] == 'checkbox' || 
												  $woocommerce_address_field['type'] == 'radio'  ? 'data-code="'.$data_code.'"' : "";
								
								//Support for Checkout Field Editor Pro
								$field_value_to_show = $woocommerce_address_field['type'] == 'select' && isset($woocommerce_address_field['options'][$address[$field_name]]) ? $woocommerce_address_field['options'][$address[$field_name]] : $address[$field_name];
								$values_to_check = is_array($address[$field_name]) ? $address[$field_name] : array($address[$field_name]);
								
								//Support for Checkout Field Editor Pro Advanced
								if ( isset( $woocommerce_address_field['options_object'] ) ) {
									$field_value_to_show_temp = array();
									foreach ( $woocommerce_address_field['options_object'] as $option_object ) {
										foreach ( $values_to_check as $value_to_check ) {
											if ( $option_object["key"] == $value_to_check ) {
												$field_value_to_show_temp[] = $option_object["text"];
											}
										}
									}			
									$field_value_to_show = count($field_value_to_show_temp) > 0 ? $field_value_to_show_temp : $field_value_to_show;
								}
								
								//Country field
								if ( $field_name == 'billing_country' || $field_name == 'shipping_country' ) {
									$field_metadata = 'data-code="'.$address[$field_name].'"';
									$field_value_to_show = $wcmca_address_model->country_code_to_name($address[$field_name]);
								} elseif ( $field_name == 'billing_state' || $field_name == 'shipping_state' ) {
									$field_metadata = 'data-code="'.$address[$field_name].'"';
									$field_value_to_show = $wcmca_address_model->state_code_to_name($address[$type.'_country'], $address[$field_name]);
									$field_value_to_show  = $field_value_to_show ? $field_value_to_show : $address[$field_name];
								}

								//Checkbox
								if ( $woocommerce_address_field['type'] == 'checkbox' ) {
									$field_value_to_show = $field_value_to_show == 1 ? __('Yes','woocommerce-multiple-customer-addresses') : __('No','woocommerce-multiple-customer-addresses');
								}
								
								if ( isset( $woocommerce_address_field['label'] ) && $display_fields_labels ) {
									echo '<h5 class="wcmca_personal_data_title">' . $woocommerce_address_field['label'] . '</h5>';
								}

								$content_class = $display_fields_labels ? 'wcmca_clear_right' : 'wcmca_clear_both';
								if(!$display_fields_labels && ($field_name == 'billing_first_name' || $field_name == 'shipping_first_name' || $field_name == 'billing_last_name' || $field_name == 'shipping_last_name')) {
									$content_class = !$display_fields_labels ? $field_name : '';
								}
								
								?>
									<span class="<?php echo $content_class;?>" id="wcmca_<?php echo $field_name; ?>_<?php echo $address_id;?>" data-default="<?php if(isset($woocommerce_address_field['default']))  echo $woocommerce_address_field['default']; else echo 0; ?>" data-name="<?php echo $field_name; ?>" <?php echo $field_metadata; ?>>
										<?php echo is_array($field_value_to_show) ? implode(", ",$field_value_to_show) : $field_value_to_show; ?>
									</span>
								<?php 
							}
						}
					?>
				</address>
			</div> <!-- col-X address -->
		<?php if($col_counter == 2):?>		
		</div> <!--col2-set addresses-->
		<?php endif; 
		$col_counter = $col_counter + 1 > 2 ? 1 : 2;
	} //end foreach($addresses)
	if($col_counter == 2): //if the foreach finishes and there was an unclosed <div>?>		
		</div> <!--col2-set addresses (forced) -->
	<?php endif; ?>