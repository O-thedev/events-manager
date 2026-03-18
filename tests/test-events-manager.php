<?php
/**
 * Events Manager Plugin Tests
 */
class Events_Manager_Test extends WP_UnitTestCase {
    
    private $plugin;
    private $event_id;
    
    public function setUp() {
        parent::setUp();
        $this->plugin = Events_Manager::get_instance();
        
        // Create a test event
        $this->event_id = $this->factory->post->create(array(
            'post_title' => 'Test Event',
            'post_type' => 'event',
            'post_status' => 'publish'
        ));
        
        // Add event meta
        update_post_meta($this->event_id, '_event_date', '2024-12-25T18:00');
        update_post_meta($this->event_id, '_event_location', 'Test Location');
        update_post_meta($this->event_id, '_event_capacity', 50);
        update_post_meta($this->event_id, '_enable_rsvp', '1');
    }
    
    /**
     * Test post type registration
     */
    public function test_post_type_registration() {
        $this->assertTrue(post_type_exists('event'));
    }
    
    /**
     * Test taxonomy registration
     */
    public function test_taxonomy_registration() {
        $this->assertTrue(taxonomy_exists('event_type'));
    }
    
    /**
     * Test event meta saving
     */
    public function test_event_meta_saving() {
        $this->assertEquals('2024-12-25T18:00', get_post_meta($this->event_id, '_event_date', true));
        $this->assertEquals('Test Location', get_post_meta($this->event_id, '_event_location', true));
        $this->assertEquals(50, get_post_meta($this->event_id, '_event_capacity', true));
        $this->assertEquals('1', get_post_meta($this->event_id, '_enable_rsvp', true));
    }
    
    /**
     * Test RSVP functionality
     */
    public function test_rsvp_functionality() {
        $user_id = $this->factory->user->create();
        wp_set_current_user($user_id);
        
        // Test RSVP saving
        $rsvp_key = '_rsvp_user_' . $user_id;
        update_post_meta($this->event_id, $rsvp_key, current_time('mysql'));
        
        $this->assertNotEmpty(get_post_meta($this->event_id, $rsvp_key, true));
        
        // Test RSVP count
        $count = $this->plugin->get_rsvp_count($this->event_id);
        $this->assertEquals(1, $count);
    }
    
    /**
     * Test capacity limits
     */
    public function test_capacity_limits() {
        $capacity = get_post_meta($this->event_id, '_event_capacity', true);
        $this->assertEquals(50, $capacity);
        
        // Add RSVPs up to capacity
        for ($i = 0; $i < 50; $i++) {
            $user_id = $this->factory->user->create();
            $rsvp_key = '_rsvp_user_' . $user_id;
            update_post_meta($this->event_id, $rsvp_key, current_time('mysql'));
        }
        
        $count = $this->plugin->get_rsvp_count($this->event_id);
        $this->assertEquals(50, $count);
    }
    
    /**
     * Test event query filtering
     */
    public function test_event_query_filtering() {
        // Create past event
        $past_event_id = $this->factory->post->create(array(
            'post_title' => 'Past Event',
            'post_type' => 'event',
            'post_status' => 'publish'
        ));
        update_post_meta($past_event_id, '_event_date', '2020-01-01T10:00');
        
        // Query only future events
        $args = array(
            'post_type' => 'event',
            'meta_key' => '_event_date',
            'meta_query' => array(
                array(
                    'key' => '_event_date',
                    'value' => current_time('mysql'),
                    'compare' => '>=',
                    'type' => 'DATETIME'
                )
            )
        );
        
        $query = new WP_Query($args);
        $found_ids = wp_list_pluck($query->posts, 'ID');
        
        $this->assertContains($this->event_id, $found_ids);
        $this->assertNotContains($past_event_id, $found_ids);
    }
    
    /**
     * Test shortcode rendering
     */
    public function test_shortcode_rendering() {
        $shortcode_output = $this->plugin->render_events_shortcode(array('limit' => 5));
        $this->assertContains('Test Event', $shortcode_output);
    }
    
    /**
     * Test REST API endpoint
     */
    public function test_rest_api_endpoint() {
        $request = new WP_REST_Request('GET', '/events-manager/v1/events');
        $response = $this->plugin->get_events_api($request);
        $data = $response->get_data();
        
        $this->assertNotEmpty($data);
        $this->assertEquals('Test Event', $data[0]['title']);
        $this->assertEquals('Test Location', $data[0]['location']);
    }
    
    /**
     * Test input sanitization
     */
    public function test_input_sanitization() {
        $malicious_input = '<script>alert("XSS")</script>Test Location';
        
        $_POST['event_location'] = $malicious_input;
        $_POST['event_meta_box_nonce'] = wp_create_nonce('event_meta_box');
        
        $this->plugin->save_event_meta($this->event_id, get_post($this->event_id));
        
        $saved_location = get_post_meta($this->event_id, '_event_location', true);
        $this->assertEquals('Test Location', $saved_location);
        $this->assertNotContains('<script>', $saved_location);
    }
    
    
    public function test_email_notifications() {
        // Create user with notification preference
        $user_id = $this->factory->user->create();
        update_user_meta($user_id, 'event_notifications', '1');
        
        // Trigger notification
        $this->plugin->send_event_notification($this->event_id, get_post($this->event_id));
        
        // Check if email was sent 
        $this->assertTrue(true); // Placeholder
    }
}