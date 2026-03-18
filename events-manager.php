<?php
/**
 * Plugin Name: Events Manager
 * Description: A comprehensive events management plugin with custom post types, taxonomies, and advanced features
 * Author: Omar
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('EM_VERSION', '1.0.0');
define('EM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EM_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
class Events_Manager {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Register post type and taxonomy
        add_action('init', array($this, 'register_event_post_type'));
        add_action('init', array($this, 'register_event_taxonomy'));
        
        // Admin hooks
        add_action('admin_menu', array($this, 'add_meta_boxes'));
        add_action('save_post_event', array($this, 'save_event_meta'), 10, 2);
        add_filter('manage_event_posts_columns', array($this, 'add_event_columns'));
        add_action('manage_event_posts_custom_column', array($this, 'display_event_columns'), 10, 2);
        
        // Front-end hooks
        add_filter('template_include', array($this, 'load_event_templates'));
        add_shortcode('events_list', array($this, 'render_events_shortcode'));
        
        // REST API
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // Notifications
        add_action('publish_event', array($this, 'send_event_notification'), 10, 2);
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Localization
        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }
    
    /**
     * Register Event Custom Post Type
     */
    public function register_event_post_type() {
        $labels = array(
            'name'               => _x('Events', 'post type general name', 'events-manager'),
            'singular_name'      => _x('Event', 'post type singular name', 'events-manager'),
            'menu_name'          => _x('Events', 'admin menu', 'events-manager'),
            'name_admin_bar'     => _x('Event', 'add new on admin bar', 'events-manager'),
            'add_new'            => _x('Add New', 'event', 'events-manager'),
            'add_new_item'       => __('Add New Event', 'events-manager'),
            'new_item'           => __('New Event', 'events-manager'),
            'edit_item'          => __('Edit Event', 'events-manager'),
            'view_item'          => __('View Event', 'events-manager'),
            'all_items'          => __('All Events', 'events-manager'),
            'search_items'       => __('Search Events', 'events-manager'),
            'parent_item_colon'  => __('Parent Events:', 'events-manager'),
            'not_found'          => __('No events found.', 'events-manager'),
            'not_found_in_trash' => __('No events found in Trash.', 'events-manager')
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'event'),
            'capability_type'    => 'event',
            'capabilities'       => array(
                'edit_post'          => 'edit_event',
                'read_post'          => 'read_event',
                'delete_post'        => 'delete_event',
                'edit_posts'         => 'edit_events',
                'edit_others_posts'  => 'edit_others_events',
                'publish_posts'      => 'publish_events',
                'read_private_posts' => 'read_private_events',
            ),
            'has_archive'        => 'events',
            'hierarchical'       => false,
            'menu_position'      => 20,
            'menu_icon'          => 'dashicons-calendar-alt',
            'supports'           => array('title', 'editor', 'thumbnail', 'excerpt'),
            'show_in_rest'       => true, // Enable Gutenberg and REST API
            'rest_base'          => 'events',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
        );

        register_post_type('event', $args);
    }
    
    /**
     * Register Event Type Taxonomy
     */
    public function register_event_taxonomy() {
        $labels = array(
            'name'              => _x('Event Types', 'taxonomy general name', 'events-manager'),
            'singular_name'     => _x('Event Type', 'taxonomy singular name', 'events-manager'),
            'search_items'      => __('Search Event Types', 'events-manager'),
            'all_items'         => __('All Event Types', 'events-manager'),
            'parent_item'       => __('Parent Event Type', 'events-manager'),
            'parent_item_colon' => __('Parent Event Type:', 'events-manager'),
            'edit_item'         => __('Edit Event Type', 'events-manager'),
            'update_item'       => __('Update Event Type', 'events-manager'),
            'add_new_item'      => __('Add New Event Type', 'events-manager'),
            'new_item_name'     => __('New Event Type Name', 'events-manager'),
            'menu_name'         => __('Event Types', 'events-manager'),
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'event-type'),
            'show_in_rest'      => true, // Enable Gutenberg and REST API
        );

        register_taxonomy('event_type', array('event'), $args);
    }
    
    /**
     * Add meta boxes for event details
     */
    public function add_meta_boxes() {
        add_meta_box(
            'event_details',
            __('Event Details', 'events-manager'),
            array($this, 'render_event_meta_box'),
            'event',
            'normal',
            'high'
        );
        
        add_meta_box(
            'event_rsvp',
            __('RSVP Settings', 'events-manager'),
            array($this, 'render_rsvp_meta_box'),
            'event',
            'side',
            'default'
        );
    }
    
    /**
     * Render event details meta box
     */
    public function render_event_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('event_meta_box', 'event_meta_box_nonce');
        
        // Get saved values
        $event_date = get_post_meta($post->ID, '_event_date', true);
        $event_location = get_post_meta($post->ID, '_event_location', true);
        $event_capacity = get_post_meta($post->ID, '_event_capacity', true);
        ?>
        <div class="event-meta-box">
            <p>
                <label for="event_date"><?php _e('Event Date:', 'events-manager'); ?></label>
                <input type="datetime-local" id="event_date" name="event_date" 
                       value="<?php echo esc_attr($event_date); ?>" class="widefat" />
            </p>
            <p>
                <label for="event_location"><?php _e('Location:', 'events-manager'); ?></label>
                <input type="text" id="event_location" name="event_location" 
                       value="<?php echo esc_attr($event_location); ?>" class="widefat" />
            </p>
            <p>
                <label for="event_capacity"><?php _e('Maximum Capacity:', 'events-manager'); ?></label>
                <input type="number" id="event_capacity" name="event_capacity" 
                       value="<?php echo esc_attr($event_capacity); ?>" min="0" class="widefat" />
                <span class="description"><?php _e('Leave empty for unlimited', 'events-manager'); ?></span>
            </p>
        </div>
        <?php
    }
    
    /**
     * Render RSVP meta box
     */
    public function render_rsvp_meta_box($post) {
        $enable_rsvp = get_post_meta($post->ID, '_enable_rsvp', true);
        ?>
        <p>
            <label for="enable_rsvp">
                <input type="checkbox" id="enable_rsvp" name="enable_rsvp" value="1" 
                       <?php checked($enable_rsvp, '1'); ?> />
                <?php _e('Enable RSVP for this event', 'events-manager'); ?>
            </label>
        </p>
        <?php
    }
    
    /**
     * Save event meta data
     */
    public function save_event_meta($post_id, $post) {
        // Check nonce
        if (!isset($_POST['event_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['event_meta_box_nonce'], 'event_meta_box')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save event date
        if (isset($_POST['event_date'])) {
            update_post_meta($post_id, '_event_date', sanitize_text_field($_POST['event_date']));
        }
        
        // Save location
        if (isset($_POST['event_location'])) {
            update_post_meta($post_id, '_event_location', sanitize_text_field($_POST['event_location']));
        }
        
        // Save capacity
        if (isset($_POST['event_capacity'])) {
            update_post_meta($post_id, '_event_capacity', intval($_POST['event_capacity']));
        }
        
        // Save RSVP setting
        $enable_rsvp = isset($_POST['enable_rsvp']) ? '1' : '0';
        update_post_meta($post_id, '_enable_rsvp', $enable_rsvp);
    }
    
    /**
     * Add custom columns to admin list view
     */
    public function add_event_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            if ($key === 'title') {
                $new_columns[$key] = $value;
                $new_columns['event_date'] = __('Event Date', 'events-manager');
                $new_columns['event_location'] = __('Location', 'events-manager');
                $new_columns['event_type'] = __('Event Type', 'events-manager');
                $new_columns['rsvp_status'] = __('RSVP', 'events-manager');
            } else {
                $new_columns[$key] = $value;
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Display custom column content
     */
    public function display_event_columns($column, $post_id) {
        switch ($column) {
            case 'event_date':
                $date = get_post_meta($post_id, '_event_date', true);
                if ($date) {
                    echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($date));
                } else {
                    echo '—';
                }
                break;
                
            case 'event_location':
                $location = get_post_meta($post_id, '_event_location', true);
                echo $location ? esc_html($location) : '—';
                break;
                
            case 'event_type':
                $terms = get_the_terms($post_id, 'event_type');
                if ($terms && !is_wp_error($terms)) {
                    $term_names = array();
                    foreach ($terms as $term) {
                        $term_names[] = sprintf(
                            '<a href="%s">%s</a>',
                            esc_url(admin_url('edit.php?event_type=' . $term->slug . '&post_type=event')),
                            esc_html($term->name)
                        );
                    }
                    echo implode(', ', $term_names);
                } else {
                    echo '—';
                }
                break;
                
            case 'rsvp_status':
                $enable_rsvp = get_post_meta($post_id, '_enable_rsvp', true);
                if ($enable_rsvp === '1') {
                    $attendees = $this->get_rsvp_count($post_id);
                    echo sprintf(__('%d attending', 'events-manager'), $attendees);
                } else {
                    echo '—';
                }
                break;
        }
    }
    
    /**
     * Load custom templates for events
     */
    public function load_event_templates($template) {
        if (is_singular('event')) {
            $plugin_template = EM_PLUGIN_DIR . 'templates/single-event.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        if (is_post_type_archive('event')) {
            $plugin_template = EM_PLUGIN_DIR . 'templates/archive-event.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Render events shortcode
     */
    public function render_events_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 10,
            'event_type' => '',
            'show_past' => 'no',
            'orderby' => 'meta_value',
            'order' => 'ASC',
        ), $atts, 'events_list');
        
        // Build query args
        $args = array(
            'post_type' => 'event',
            'posts_per_page' => intval($atts['limit']),
            'meta_key' => '_event_date',
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
            'meta_query' => array()
        );
        
        // Filter by event type
        if (!empty($atts['event_type'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'event_type',
                    'field' => 'slug',
                    'terms' => explode(',', $atts['event_type'])
                )
            );
        }
        
        // Filter out past events if needed
        if ($atts['show_past'] === 'no') {
            $args['meta_query'][] = array(
                'key' => '_event_date',
                'value' => current_time('mysql'),
                'compare' => '>=',
                'type' => 'DATETIME'
            );
        }
        
        $events_query = new WP_Query($args);
        
        ob_start();
        if ($events_query->have_posts()) {
            include EM_PLUGIN_DIR . 'templates/shortcode-events-list.php';
        } else {
            echo '<p>' . __('No events found.', 'events-manager') . '</p>';
        }
        wp_reset_postdata();
        
        return ob_get_clean();
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('events-manager/v1', '/events', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_events_api'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route('events-manager/v1', '/events/(?P<id>\d+)/rsvp', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_rsvp_api'),
            'permission_callback' => array($this, 'check_rsvp_permission')
        ));
    }
    
    /**
     * Get events via REST API
     */
    public function get_events_api($request) {
        $args = array(
            'post_type' => 'event',
            'posts_per_page' => $request->get_param('per_page') ?: 10,
            'paged' => $request->get_param('page') ?: 1
        );
        
        // Add date filtering
        if ($request->get_param('from_date')) {
            $args['meta_query'][] = array(
                'key' => '_event_date',
                'value' => $request->get_param('from_date'),
                'compare' => '>=',
                'type' => 'DATE'
            );
        }
        
        $events = new WP_Query($args);
        $data = array();
        
        foreach ($events->posts as $event) {
            $event_data = array(
                'id' => $event->ID,
                'title' => $event->post_title,
                'content' => $event->post_content,
                'date' => get_post_meta($event->ID, '_event_date', true),
                'location' => get_post_meta($event->ID, '_event_location', true),
                'capacity' => get_post_meta($event->ID, '_event_capacity', true),
                'rsvp_enabled' => get_post_meta($event->ID, '_enable_rsvp', true),
                'event_types' => wp_get_post_terms($event->ID, 'event_type'),
                'link' => get_permalink($event->ID)
            );
            $data[] = $event_data;
        }
        
        return rest_ensure_response($data);
    }
    
    /**
     * Handle RSVP via REST API
     */
    public function handle_rsvp_api($request) {
        $event_id = $request->get_param('id');
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return new WP_Error('not_logged_in', __('You must be logged in to RSVP', 'events-manager'), array('status' => 401));
        }
        
        // Check if event exists and RSVP is enabled
        if (get_post_meta($event_id, '_enable_rsvp', true) !== '1') {
            return new WP_Error('rsvp_disabled', __('RSVP is not enabled for this event', 'events-manager'), array('status' => 400));
        }
        
        // Check capacity
        $capacity = get_post_meta($event_id, '_event_capacity', true);
        if (!empty($capacity)) {
            $current_attendees = $this->get_rsvp_count($event_id);
            if ($current_attendees >= intval($capacity)) {
                return new WP_Error('event_full', __('This event has reached maximum capacity', 'events-manager'), array('status' => 400));
            }
        }
        
        // Add RSVP
        $rsvp_key = '_rsvp_user_' . $user_id;
        update_post_meta($event_id, $rsvp_key, current_time('mysql'));
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => __('RSVP confirmed!', 'events-manager'),
            'attendees' => $this->get_rsvp_count($event_id)
        ));
    }
    
    /**
     * Check RSVP permission
     */
    public function check_rsvp_permission() {
        return is_user_logged_in();
    }
    
    /**
     * Get RSVP count for an event
     */
    private function get_rsvp_count($event_id) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $wpdb->postmeta 
             WHERE post_id = %d 
             AND meta_key LIKE '_rsvp_user_%'",
            $event_id
        ));
        
        return intval($count);
    }
    
    /**
     * Send notification when event is published
     */
    public function send_event_notification($post_id, $post) {
        // Get all users who have subscribed to event notifications
        $subscribers = get_users(array(
            'meta_key' => 'event_notifications',
            'meta_value' => '1'
        ));
        
        $event_title = $post->post_title;
        $event_date = get_post_meta($post_id, '_event_date', true);
        $event_link = get_permalink($post_id);
        
        $subject = sprintf(__('New Event: %s', 'events-manager'), $event_title);
        
        $message = sprintf(
            __("A new event has been published:\n\nTitle: %s\nDate: %s\n\nView event: %s", 'events-manager'),
            $event_title,
            $event_date,
            $event_link
        );
        
        foreach ($subscribers as $user) {
            wp_mail($user->user_email, $subject, $message);
        }
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (is_post_type_archive('event') || is_singular('event') || has_shortcode(get_post()->post_content, 'events_list')) {
            wp_enqueue_style('events-manager-frontend', EM_PLUGIN_URL . 'assets/css/frontend.css', array(), EM_VERSION);
            wp_enqueue_script('events-manager-frontend', EM_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), EM_VERSION, true);
            
            wp_localize_script('events-manager-frontend', 'em_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('em_frontend_nonce')
            ));
        }
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        global $post_type;
        
        if ($post_type === 'event') {
            wp_enqueue_style('events-manager-admin', EM_PLUGIN_URL . 'assets/css/admin.css', array(), EM_VERSION);
            wp_enqueue_script('events-manager-admin', EM_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), EM_VERSION, true);
        }
    }
    
    /**
     * Load plugin text domain for localization
     */
    public function load_textdomain() {
        load_plugin_textdomain('events-manager', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
}

// Initialize the plugin
Events_Manager::get_instance();

// Activation/Deactivation hooks
register_activation_hook(__FILE__, 'events_manager_activate');
register_deactivation_hook(__FILE__, 'events_manager_deactivate');

function events_manager_activate() {
    // Create custom capabilities
    $roles = array('administrator', 'editor');
    foreach ($roles as $role_name) {
        $role = get_role($role_name);
        if ($role) {
            $role->add_cap('edit_event');
            $role->add_cap('read_event');
            $role->add_cap('delete_event');
            $role->add_cap('edit_events');
            $role->add_cap('edit_others_events');
            $role->add_cap('publish_events');
            $role->add_cap('read_private_events');
        }
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

function events_manager_deactivate() {
    // Remove custom capabilities
    $roles = array('administrator', 'editor');
    foreach ($roles as $role_name) {
        $role = get_role($role_name);
        if ($role) {
            $role->remove_cap('edit_event');
            $role->remove_cap('read_event');
            $role->remove_cap('delete_event');
            $role->remove_cap('edit_events');
            $role->remove_cap('edit_others_events');
            $role->remove_cap('publish_events');
            $role->remove_cap('read_private_events');
        }
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
    // This is the end of the code if you reached till here, Thanks for reviewing it for real.
}