<?php
/**
 *  Rule Criteria data.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}
?>
<div id="fgf_rule_data_criteria" class="fgf-rule-options-wrapper">
	<table class="form-table">
		<tbody>

			<?php do_action( 'fgf_before_rule_criteria_settings' , $rule_data ) ; ?>

			<tr>
				<th scope='row'>
					<label><?php esc_html_e( 'Criteria Type' , 'free-gifts-for-woocommerce' ) ; ?>
						<?php fgf_wc_help_tip( esc_html__( 'AND – The user will be eligible only when they satisfy both the criteria. OR – The user will be eligible if they satisfy any one of the criteria' , 'free-gifts-for-woocommerce' ) ) ; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped. ?>
					</label>
				</th>
				<td>
					<select name="fgf_rule[fgf_condition_type]">
						<option value="1" <?php selected( $rule_data[ 'fgf_condition_type' ] , '1' ) ; ?>><?php esc_html_e( 'AND' , 'free-gifts-for-woocommerce' ) ; ?></option>
						<option value="2" <?php selected( $rule_data[ 'fgf_condition_type' ] , '2' ) ; ?>><?php esc_html_e( 'OR' , 'free-gifts-for-woocommerce' ) ; ?></option>
					</select>
				</td>
			</tr>

			<tr>
				<th scope='row'>
					<label><?php esc_html_e( 'Criteria Calculated based on' , 'free-gifts-for-woocommerce' ) ; ?>
						<?php fgf_wc_help_tip( esc_html__( 'Cart Subtotal - Sum of all Product Prices and Taxes if applicable. Order Total - Sum of all Product Prices, Shipping and Taxes. Category Total - Sum of all Product Prices plus applicable Taxes that belong to a particular category.' , 'free-gifts-for-woocommerce' ) ) ; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped. ?>
					</label>
				</th>
				<td>
					<select name="fgf_rule[fgf_total_type]" class="fgf-rule-total-type">
						<option value="1" <?php selected( $rule_data[ 'fgf_total_type' ] , '1' ) ; ?>><?php esc_html_e( 'Cart Subtotal' , 'free-gifts-for-woocommerce' ) ; ?></option>
						<option value="2" <?php selected( $rule_data[ 'fgf_total_type' ] , '2' ) ; ?>><?php esc_html_e( 'Order Total' , 'free-gifts-for-woocommerce' ) ; ?></option>
						<option value="3" <?php selected( $rule_data[ 'fgf_total_type' ] , '3' ) ; ?>><?php esc_html_e( 'Category Total' , 'free-gifts-for-woocommerce' ) ; ?></option>
					</select>
				</td>
			</tr>

			<tr>
				<th scope='row'>
					<label><?php esc_html_e( 'Select a Category' , 'free-gifts-for-woocommerce' ) ; ?><span class="required">*</span></label>
				</th>
				<td>
					<select class="fgf_select2 fgf-rule-cart-categories" name="fgf_rule[fgf_cart_categories][]">
						<?php
						foreach ( $categories as $category_id => $category_name ) :
							$selected = ( in_array( $category_id , $rule_data[ 'fgf_cart_categories' ] ) ) ? ' selected="selected"' : '' ;
							?>
							<option value="<?php echo esc_attr( $category_id ) ; ?>"<?php echo esc_attr( $selected ) ; ?>><?php echo esc_html( $category_name ) ; ?></option>
						<?php endforeach ; ?>
					</select>
				</td>
			</tr>

			<tr>
				<th></th>
				<td>
					<?php esc_html_e( 'Min' , 'free-gifts-for-woocommerce' ) ; ?>
					<input type="text" name="fgf_rule[fgf_cart_subtotal_min_value]" min="0" value="<?php echo esc_attr( wc_format_localized_price( $rule_data[ 'fgf_cart_subtotal_min_value' ] ) ) ; ?>"/>
				</td>
				<td>
					<?php esc_html_e( 'Max' , 'free-gifts-for-woocommerce' ) ; ?>
					<input type="text" name="fgf_rule[fgf_cart_subtotal_max_value]" min="0" value="<?php echo esc_attr( wc_format_localized_price( $rule_data[ 'fgf_cart_subtotal_max_value' ] ) ) ; ?>"/>
				</td>
			</tr>

			<tr>
				<th scope='row'>
					<label><?php esc_html_e( 'Cart Quantity' , 'free-gifts-for-woocommerce' ) ; ?>
						<?php fgf_wc_help_tip( esc_html__( "The user's cart quantity(sum of all product quantities) should be within the specified range" , 'free-gifts-for-woocommerce' ) ) ; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped. ?>
					</label>
				</th>
				<td>
					<?php esc_html_e( 'Min' , 'free-gifts-for-woocommerce' ) ; ?>
					<input type="number" name="fgf_rule[fgf_quantity_min_value]" min="0" value="<?php echo esc_attr( $rule_data[ 'fgf_quantity_min_value' ] ) ; ?>"/>
				</td>
				<td>
					<?php esc_html_e( 'Max' , 'free-gifts-for-woocommerce' ) ; ?>
					<input type="number" name="fgf_rule[fgf_quantity_max_value]" min="0" value="<?php echo esc_attr( $rule_data[ 'fgf_quantity_max_value' ] ) ; ?>"/>
				</td>
			</tr>

			<tr>
				<th scope='row'>
					<label><?php esc_html_e( 'Number of Products in the Cart' , 'free-gifts-for-woocommerce' ) ; ?>
						<?php fgf_wc_help_tip( esc_html__( "Total number of products added in the user's cart should be within the specified range" , 'free-gifts-for-woocommerce' ) ) ; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped. ?>
					</label>
				</th>
				<td>
					<?php esc_html_e( 'Min' , 'free-gifts-for-woocommerce' ) ; ?>
					<input type="number" name="fgf_rule[fgf_product_count_min_value]" min="0" value="<?php echo esc_attr( $rule_data[ 'fgf_product_count_min_value' ] ) ; ?>"/>
				</td>
				<td>
					<?php esc_html_e( 'Max' , 'free-gifts-for-woocommerce' ) ; ?>
					<input type="number" name="fgf_rule[fgf_product_count_max_value]" min="0" value="<?php echo esc_attr( $rule_data[ 'fgf_product_count_max_value' ] ) ; ?>"/>
				</td>
			</tr>

			<?php do_action( 'fgf_after_rule_criteria_settings' , $rule_data ) ; ?>
		</tbody>
	</table>
</div>
<?php
