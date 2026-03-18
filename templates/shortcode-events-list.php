<?php
/**
 * Events List Shortcode Template
 */
?>
<div class="em-shortcode-events">
    <?php while ($events_query->have_posts()) : $events_query->the_post(); 
        $event_date = get_post_meta(get_the_ID(), '_event_date', true);
        $event_location = get_post_meta(get_the_ID(), '_event_location', true);
    ?>
    
    <div class="em-shortcode-event-item">
        <div class="em-event-mini-card">
            <?php if (has_post_thumbnail()) : ?>
                <div class="em-event-mini-thumb">
                    <a href="<?php the_permalink(); ?>">
                        <?php the_post_thumbnail('thumbnail'); ?>
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="em-event-mini-details">
                <h3 class="em-event-mini-title">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h3>
                
                <?php if ($event_date) : ?>
                <div class="em-event-mini-date">
                    <strong><?php _e('Date:', 'events-manager'); ?></strong> 
                    <?php echo date_i18n(get_option('date_format'), strtotime($event_date)); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($event_location) : ?>
                <div class="em-event-mini-location">
                    <strong><?php _e('Location:', 'events-manager'); ?></strong> 
                    <?php echo esc_html($event_location); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php endwhile; ?>
</div>