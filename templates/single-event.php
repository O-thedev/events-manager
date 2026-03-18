<?php
/**
 * Single Event Template
 */
get_header(); ?>

<div class="em-single-event">
    <?php while (have_posts()) : the_post(); 
        $event_date = get_post_meta(get_the_ID(), '_event_date', true);
        $event_location = get_post_meta(get_the_ID(), '_event_location', true);
        $event_capacity = get_post_meta(get_the_ID(), '_event_capacity', true);
        $enable_rsvp = get_post_meta(get_the_ID(), '_enable_rsvp', true);
    ?>
    
    <article id="event-<?php the_ID(); ?>" <?php post_class('em-event-card'); ?>>
        <header class="em-event-header">
            <h1 class="em-event-title"><?php the_title(); ?></h1>
            
            <?php if (has_post_thumbnail()) : ?>
                <div class="em-event-thumbnail">
                    <?php the_post_thumbnail('large'); ?>
                </div>
            <?php endif; ?>
        </header>
        
        <div class="em-event-details">
            <div class="em-event-meta">
                <?php if ($event_date) : ?>
                <div class="em-meta-item em-event-date">
                    <span class="em-meta-label"><?php _e('Date:', 'events-manager'); ?></span>
                    <span class="em-meta-value"><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($event_date)); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($event_location) : ?>
                <div class="em-meta-item em-event-location">
                    <span class="em-meta-label"><?php _e('Location:', 'events-manager'); ?></span>
                    <span class="em-meta-value"><?php echo esc_html($event_location); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($event_capacity) : ?>
                <div class="em-meta-item em-event-capacity">
                    <span class="em-meta-label"><?php _e('Capacity:', 'events-manager'); ?></span>
                    <span class="em-meta-value"><?php echo intval($event_capacity); ?></span>
                </div>
                <?php endif; ?>
                
                <?php 
                $event_types = get_the_terms(get_the_ID(), 'event_type');
                if ($event_types && !is_wp_error($event_types)) : 
                ?>
                <div class="em-meta-item em-event-types">
                    <span class="em-meta-label"><?php _e('Event Type:', 'events-manager'); ?></span>
                    <span class="em-meta-value">
                        <?php 
                        $type_names = array();
                        foreach ($event_types as $type) {
                            $type_names[] = sprintf(
                                '<a href="%s">%s</a>',
                                get_term_link($type),
                                esc_html($type->name)
                            );
                        }
                        echo implode(', ', $type_names);
                        ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="em-event-content">
                <?php the_content(); ?>
            </div>
            
            <?php if ($enable_rsvp === '1' && is_user_logged_in()) : ?>
            <div class="em-rsvp-section">
                <button class="em-rsvp-button" data-event-id="<?php the_ID(); ?>">
                    <?php _e('RSVP for this Event', 'events-manager'); ?>
                </button>
                <div class="em-rsvp-message"></div>
            </div>
            <?php elseif ($enable_rsvp === '1') : ?>
            <div class="em-rsvp-login">
                <p><?php _e('Please <a href="' . wp_login_url(get_permalink()) . '">log in</a> to RSVP for this event.', 'events-manager'); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </article>
    
    <?php endwhile; ?>
</div>

<?php get_footer(); ?>