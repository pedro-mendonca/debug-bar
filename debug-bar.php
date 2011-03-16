<?php
/*
 Plugin Name: Debug Bar
 Plugin URI: http://wordpress.org/extend/plugins/debug-bar/
 Description: Adds a debug menu to the admin bar that shows query, cache, and other helpful debugging information.
 Author: wordpressdotorg
 Version: 0.7-alpha
 Author URI: http://wordpress.org/
 */

/***
 * Debug Functions
 *
 * When logged in as a super admin, these functions will run to provide
 * debugging information when specific super admin menu items are selected.
 *
 * They are not used when a regular user is logged in.
 */

class Debug_Bar {
	var $panels = array();

	function Debug_Bar() {
		if ( defined('DOING_AJAX') && DOING_AJAX )
			add_action( 'admin_init', array( &$this, 'init_ajax' ) );
		add_action( 'admin_bar_init', array( &$this, 'init' ) );
	}

	function init() {
		if ( ! is_super_admin() || ! is_admin_bar_showing() || $this->is_wp_login() )
			return;

		add_action( 'admin_bar_menu',               array( &$this, 'admin_bar_menu' ), 1000 );
		add_action( 'wp_after_admin_bar_render',    array( &$this, 'render' ) );
		add_action( 'wp_head',                      array( &$this, 'ensure_ajaxurl' ), 1 );

		$this->requirements();
		$this->enqueue();
		$this->init_panels();
	}
	
	/* Are we on the wp-login.php page?
	 * We can get here while logged in and break the page as the admin bar isn't shown and otherthings the js relies on aren't available.
	 */
	function is_wp_login() {
		return 'wp-login.php' == basename( $_SERVER['SCRIPT_NAME']);
	}

	function init_ajax() {
		if ( ! is_super_admin() )
			return;

		$this->requirements();
		$this->init_panels();
	}

	function requirements() {
		$recs = array( 'panel', 'php', 'queries', 'request', 'wp-query', 'object-cache', 'deprecated' );
		foreach ( $recs as $rec )
			require_once "panels/class-debug-bar-$rec.php";
	}

	function enqueue() {
		$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';

		wp_enqueue_style( 'debug-bar', plugins_url( "css/debug-bar$suffix.css", __FILE__ ), array(), '20110114' );
		wp_enqueue_script( 'debug-bar-ui-dockable', plugins_url( "js/ui-dockable$suffix.js", __FILE__ ), array('jquery-ui-mouse'), '20110113' );
		wp_enqueue_script( 'debug-bar', plugins_url( "js/debug-bar$suffix.js", __FILE__ ), array('jquery', 'debug-bar-ui-dockable', 'utils'), '20110114' );

		do_action('debug_bar_enqueue_scripts');
	}

	function init_panels() {
		$classes = array(
			'Debug_Bar_PHP',
			'Debug_Bar_WP_Query',
			'Debug_Bar_Queries',
			'Debug_Bar_Deprecated',
			'Debug_Bar_Request',
			'Debug_Bar_Object_Cache',
		);

		foreach ( $classes as $class ) {
			$this->panels[] = new $class;
		}

		$this->panels = apply_filters( 'debug_bar_panels', $this->panels );

		foreach ( $this->panels as $panel_key => $panel ) {
			if ( ! $panel->is_visible() )
				unset( $this->panels[ $panel_key ] );
		}
	}

	function ensure_ajaxurl() {
		if ( is_admin() )
			return;
		$current_user = wp_get_current_user();
		?>
		<script type="text/javascript">
		//<![CDATA[
		var userSettings = {
				'url': '<?php echo SITECOOKIEPATH; ?>',
				'uid': '<?php echo $current_user->ID; ?>',
				'time':'<?php echo time() ?>'
			},
			ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
		//]]>
		</script>
		<?php
	}

	// memory_get_peak_usage is PHP >= 5.2.0 only
	function safe_memory_get_peak_usage() {
		if ( function_exists( 'memory_get_peak_usage' ) ) {
			$usage = memory_get_peak_usage();
		} else {
			$usage = memory_get_usage();
		}
		return $usage;
	}

	function admin_bar_menu() {
		global $wp_admin_bar;

		$classes = apply_filters( 'debug_bar_classes', array() );
		$classes = implode( " ", $classes );

		/* Add the main siteadmin menu item */
		$wp_admin_bar->add_menu( array(
			'id'    => 'debug-bar',
			'title' => __('Debug', 'debug-bar'),
			'meta'  => array( 'class' => $classes )
		) );
	}

	function render() {
		global $wpdb;

		if ( empty( $this->panels ) )
			return;

		foreach ( $this->panels as $panel_key => $panel ) {
			$panel->prerender();
			if ( ! $panel->is_visible() )
				unset( $this->panels[ $panel_key ] );
		}

		?>
	<div id='querylist'>

	<div id='debug-bar-handle'></div>
	<div id='debug-bar-menu'>
		<div id='debug-bar-menu-right'>
		<div id="debug-status">
			<?php //@todo: Add a links to information about WP_DEBUG, PHP version, MySQL version, and Peak Memory.
			$statuses = array();
			if ( ! WP_DEBUG )
				$statuses[] = array( 'warning', __('WP_DEBUG OFF', 'debug-bar'), '' );
			$statuses[] = array( 'site', sprintf( __('Site #%d on %s', 'debug-bar'), $GLOBALS['blog_id'], php_uname( 'n' ) ), '' );
			$statuses[] = array( 'php', __('PHP', 'debug-bar'), phpversion() );
			$statuses[] = array( 'db', __('DB', 'debug-bar'), $wpdb->db_version() );
			$statuses[] = array( 'memory', __('Mem.', 'debug-bar'), sprintf( __('%s bytes', 'debug-bar'), number_format( $this->safe_memory_get_peak_usage() ) ) );

			$statuses = apply_filters( 'debug_bar_statuses', $statuses );

			$status_html = array();
			foreach ( $statuses as $status ) {
				list( $slug, $title, $data ) = $status;

				$html = "<span id='debug-status-$slug' class='debug-status'>";
				$html .= "<span class='debug-status-title'>$title</span>";
				if ( ! empty( $data ) )
					$html .= " <span class='debug-status-data'>$data</span>";
				$html .= '</span>';
				$status_html[] = $html;
			}

			echo implode( ' | ', $status_html );
			?>
		</div>
		<div id="debug-bar-actions">
			<span class="plus">+</span>
			<span class="minus" style="display: none">&ndash;</span>
		</div>
		</div>
		<ul id="debug-menu-links">

	<?php
		$current = ' current';
		foreach ( $this->panels as $panel ) :
			$class = get_class( $panel );
			?>
			<li><a
				id="debug-menu-link-<?php echo esc_attr( $class ); ?>"
				class="debug-menu-link<?php echo $current; ?>"
				href="#debug-menu-target-<?php echo esc_attr( $class ); ?>">
				<?php
				// Not escaping html here, so panels can use html in the title.
				echo $panel->title();
				?>
			</a></li>
			<?php
			$current = '';
		endforeach; ?>

		</ul>
	</div>

	<div id="debug-menu-targets"><?php
	$current = ' style="display: block"';
	foreach ( $this->panels as $panel ) :
		$class = get_class( $panel ); ?>

		<div id="debug-menu-target-<?php echo $class; ?>" class="debug-menu-target" <?php echo $current; ?>>
			<?php $panel->render(); ?>
		</div>

		<?php
		$current = '';
	endforeach;
	?>
	</div>

	<?php do_action( 'debug_bar' ); ?>
	</div>
	<?php
	}
}

$GLOBALS['debug_bar'] = new Debug_Bar();

?>