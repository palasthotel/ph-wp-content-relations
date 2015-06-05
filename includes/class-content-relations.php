<?php

/**
 * The core plugin class.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 */
class Content_Relations {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the Dashboard and
	 * the public-facing side of the site.
	 *
	 */
	public function __construct() {

		$this->plugin_name = 'content-realations';
		$this->version = '1.0';

		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-content-relations-loader.php';

		/**
		 * The class that handles required relations for post types
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/class-content-relations-required.php';

		/**
		 * The class that handles all data
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/class-content-relations-store.php';

		/**
		 * The class responsible for defining all actions that occur in the Dashboard.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-content-relations-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-content-relations-public.php';

		$this->loader = new Content_Relations_Loader();

	}

	/**
	 * Register all of the hooks related to the dashboard functionality
	 * of the plugin.
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Content_Relations_Admin( $this->get_plugin_name(), $this->get_version() );

		/**
		 * settings page
		 */
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'menu_page' );

		/**
		 * adds content relations metabox to post
		 */
		$this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'add_post_meta_relations' );

		/**
		 * registers save_post action
		 */
		$this->loader->add_action( 'save_post', $plugin_admin, 'save_post_meta_relations' );

		/**
		 * registers delete_post action that is triggert before post is deleted
		 */
		$this->loader->add_action( 'delete_post', $plugin_admin, 'delete_post_meta_relations' );

		/**
		 * Ajax endpoint for title search
		 */
		$this->loader->add_action( 'wp_ajax_ph_content_relations_title', $plugin_admin, 'get_contents_by_title' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 */
	private function define_public_hooks() {

		$plugin_public = new Content_Relations_Public( $this->get_plugin_name(), $this->get_version() );

		// on post render
		$this->loader->add_action( 'the_post', $plugin_public, 'add_relations_to_post' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 */
	public function get_version() {
		return $this->version;
	}

}
