<?php
/**
 * Archive Events Template
 */
get_header(); ?>

<div class="em-archive-events">
    <header class="em-archive-header">
        <h1 class="em-archive-title"><?php _e('Events', 'events-manager'); ?></h1>
        
        <div class="em-filter-section">
            <form class="em-filter-form" method="GET">
                <div class="em-filter-row">
                    <input type="text" name="em_search" placeholder="<?php _e('Search events...', 'events-manager'); ?>" 
                           value="<?php echo isset($_GET['em_search']) ? esc_attr($_GET['em_search']) : ''; ?>">
                    
                    <select name="em_event_type">
                        <option value=""><?php _e('All Event Types', 'events-manager'); ?></option>
                        <?php 
                        $event_types = get_terms(array('taxonomy' => 'event_type', 'hide_empty' => true));
                        foreach ($event_types as $type) {
                            $selected = (isset($_GET['em_event_type']) && $_GET['em_event_type'] == $type->slug) ? 'selected' : '';
                            echo sprintf(
                                '<option value="%s" %s>%s</option>',
                                esc_attr($type->slug),
                                $selected,
                                esc_html($type->name)
                            );
                        }
                        ?>
                    </select>
                    
                    <input type="date" name="em_date_from" placeholder="<?php _e('From date', 'events-manager'); ?>" 
                           value="<?php echo isset($_GET['em_date_from']) ? esc_attr($_GET['em_date_from']) : ''; ?>">
                    
                    <input type="date" name="em_date_to" placeholder="<?php _e('To date', 'events-manager'); ?>" 
                           value="<?php echo isset($_GET['em_date_to']) ? esc_attr($_GET['em_date_to']) : ''; ?>">
                    
                    <button type="submit" class="em-filter-submit"><?php _e('Filter', 'events-manager'); ?></button>
                </div>
            </form>
        </div>
    </header>
    
    <?php if (have_posts()) : ?>
    <div class="em-events-grid">
        <?php while (have_posts()) : the_post(); 
            $event_date = get_post_meta(get_the_ID(), '_event_date', true);
            $event_location = get_post_meta(get_the_ID(), '_event_location', true);
        ?>
        
        <article id="event-<?php the_ID(); ?>" <?php post_class('em-event-card'); ?>>
            <?php if (has_post_thumbnail()) : ?>
                <div class="em-event-thumbnail">
                    <a href="<?php the_permalink(); ?>">
                        <?php the_post_thumbnail('medium'); ?>
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="em-event-content">
                <h2 class="em-event-title">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h2>
                
                <div class="em-event-excerpt">
                    <?php the_excerpt(); ?>
                </div>
                
                <div class="em-event-meta">
                    <?php if ($event_date) : ?>
                    <div class="em-meta-item">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php echo date_i18n(get_option('date_format'), strtotime($event_date)); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($event_location) : ?>
                    <div class="em-meta-item">
                        <span class="dashicons dashicons-location"></span>
                        <?php echo esc_html($event_location); ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <a href="<?php the_permalink(); ?>" class="em-read-more">
                    <?php _e('View Event Details →', 'events-manager'); ?>
                </a>
            </div>
        </article>
        
        <?php endwhile; ?>
    </div>
    
    <div class="em-pagination">
        <?php 
        the_posts_pagination(array(
            'mid_size' => 2,
            'prev_text' => __('Previous', 'events-manager'),
            'next_text' => __('Next', 'events-manager'),
        )); 
        ?>
    </div>
    
    <?php else : ?>
        <p class="em-no-events"><?php _e('No events found.', 'events-manager'); ?></p>
    <?php endif; ?>
</div>

<?php get_footer(); ?>