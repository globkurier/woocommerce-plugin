<?php

namespace udigroup_globkurier;

class BulkSend
{
	
	public function init(): void
	{
		add_filter('bulk_actions-woocommerce_page_wc-orders', [$this, 'add_bulk_action']);
		add_filter('bulk_actions-edit-shop_order', [$this, 'add_bulk_action']);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
		
		add_action('wp_ajax_wpopieka_globkurier_bulk_send_open_modal', [$this, 'open_modal']);
		
		
		add_filter('manage_woocommerce_page_wc-orders_columns', [$this, 'add_shipping_method_column_hpos']);
		add_filter('manage_edit-shop_order_columns', [$this, 'add_shipping_method_column_legacy']);
		
		add_action('woocommerce_shop_order_list_table_custom_column',  [$this, 'populate_shipping_method_column_hpos'], 10, 2);
		add_action('manage_shop_order_posts_custom_column',  [$this, 'populate_shipping_method_column_legacy'], 10, 2);
		
	}
	
	public function add_bulk_action($actions)
	{
		$actions[ 'nadaj_z_globkurier' ] = 'Nadaj z GlobKurier';
		
		return $actions;
	}
	
	public function enqueue_scripts(): void
	{
		$screen = get_current_screen();
		if (! $screen || ! in_array($screen->id, ['edit-shop_order', 'woocommerce_page_wc-orders'])) {
			return;
		}
		
		$is_order_page = false;
		global $current_screen;
		
		// HPOS
		if ($current_screen && $current_screen->id === 'woocommerce_page_wc-orders' &&
			isset($_GET['action']) && $_GET['action'] === 'edit') {
			$is_order_page = true;
		}
		
		// Classic orders
		if ($current_screen && $current_screen->id === 'shop_order' &&
			$current_screen->base === 'post') {
			$is_order_page = true;
		}
		
		
		if($is_order_page){
			return;
		}
		
		wp_enqueue_script('wpopieka_globkurier_bulk_send_script', plugin_dir_url(__FILE__).'/assets/bulk-send.js', ['jquery'], '1.0');
		wp_localize_script('wpopieka_globkurier_bulk_send_script', 'wpopieka_globkurier_bulk_send_data', [
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce'    => wp_create_nonce('wpopieka_globkurier_bulk_send'),
			'action'   => 'wpopieka_globkurier_bulk_send_open_modal',
		]);
		
		wp_enqueue_style('wpopieka_globkurier_bulk_send_style', plugin_dir_url(__FILE__).'/assets/bulk-send.css');
	}
	
	public function open_modal()
	{
		$nonce = sanitize_text_field($_POST['nonce']);
		
		if ( ! wp_verify_nonce($nonce, 'wpopieka_globkurier_bulk_send')) {
			wp_send_json_error(['message' => 'Invalid nonce']);
		}
		$orderIds = array_filter(array_map('intval', $_POST['orders']));
		
		if(empty($orderIds)){
			wp_send_json_error(['message' => 'Brak wybranych zamówień']);
		}
		
		$orders = array_map(function ($order) {
			return wc_get_order($order);
		}, $orderIds);
		
		global $globKurier;
		
		ob_start();
		
		include __DIR__.'/templates/bulk_order_template.php';
		
		$html = ob_get_clean();
		
		wp_send_json_success(['message' => 'success', 'html' => $html, 'carrierData'=>$commonProducts??[]]);
	}
	
	
	
	
	public function add_shipping_method_column_hpos($columns) {
		$new_columns = array();
		foreach ($columns as $key => $column) {
			$new_columns[$key] = $column;
			if ($key === 'order_total') {
				$new_columns['shipping_method'] = __('Wysyłka', 'textdomain');
			}
		}
		return $new_columns;
	}
	
	public function add_shipping_method_column_legacy($columns) {
		$new_columns = array();
		foreach ($columns as $key => $column) {
			$new_columns[$key] = $column;
			if ($key === 'order_total') {
				$new_columns['shipping_method'] = __('Wysyłka', 'textdomain');
			}
		}
		return $new_columns;
	}

	public function populate_shipping_method_column_hpos($column, $order) {
		if ($column === 'shipping_method') {
			if (!is_object($order)) {
				$order = wc_get_order($order);
			}
			
			$shipping_methods = $order->get_shipping_methods();
			if (!empty($shipping_methods)) {
				$method = reset($shipping_methods);
				echo esc_html($method->get_name());
			}
		}
	}
	
	public function populate_shipping_method_column_legacy($column, $post_id) {
		if ($column === 'shipping_method') {
			$order = wc_get_order($post_id);
			
			$shipping_methods = $order->get_shipping_methods();
			if (!empty($shipping_methods)) {
				$method = reset($shipping_methods);
				echo esc_html($method->get_name());
			}
		}
	}
	
	
}

(new BulkSend())->init();