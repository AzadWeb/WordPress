<?php
/**
 * Custom Comment Template Functions
 *
 * @package Custom_Comments_Reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Main function to display comments.
 * This function will be used as a callback for wp_list_comments().
 *
 * @param object $comment Comment object.
 * @param array  $args    Arguments.
 * @param int    $depth   Depth of the current comment.
 */
function ccr_comment_display_callback( $comment, $args, $depth ) {
    $GLOBALS['comment'] = $comment; // WordPress uses this global.
    $tag = ( 'div' === $args['style'] ) ? 'div' : 'li';
    ?>
    <<?php echo $tag; ?> id="comment-<?php comment_ID(); ?>" <?php comment_class( empty( $args['has_children'] ) ? '' : 'parent', $comment ); ?>>
        <article id="div-comment-<?php comment_ID(); ?>" class="ccr-comment-body">
            <footer class="ccr-comment-meta">
                <div class="ccr-comment-author-avatar">
                    <?php if ( 0 != $args['avatar_size'] ) echo get_avatar( $comment, $args['avatar_size'] ); ?>
                </div>
                <div class="ccr-comment-author-info">
                    <span class="ccr-comment-author-name"><?php printf( esc_html__( '%s says:', 'custom-comments-reviews' ), get_comment_author_link( $comment ) ); ?></span>
                    <span class="ccr-comment-date">
                        <a href="<?php echo esc_url( get_comment_link( $comment, $args ) ); ?>">
                            <time datetime="<?php comment_time( 'c' ); ?>">
                                <?php
                                /* translators: 1: date, 2: time */
                                printf( esc_html__( '%1$s at %2$s', 'custom-comments-reviews' ), get_comment_date( '', $comment ), get_comment_time() );
                                ?>
                            </time>
                        </a>
                    </span>
                    <?php edit_comment_link( __( '(Edit)', 'custom-comments-reviews' ), '<span class="edit-link">', '</span>' ); ?>
                </div>
            </footer>

            <div class="ccr-comment-content">
                <?php if ( '0' == $comment->comment_approved ) : ?>
                <p class="ccr-comment-awaiting-moderation"><?php esc_html_e( 'Your comment is awaiting moderation.', 'custom-comments-reviews' ); ?></p>
                <?php endif; ?>
                <?php comment_text(); ?>
            </div>

            <div class="ccr-comment-actions">
                <?php
                // Display Like/Dislike buttons
                if ( function_exists( 'ccr_display_like_dislike_buttons' ) ) {
                    ccr_display_like_dislike_buttons( get_comment_ID() );
                }

                // WooCommerce review stars
                if ( function_exists( 'ccr_is_woocommerce_product_page' ) && ccr_is_woocommerce_product_page() && get_comment_type() == 'review' ) {
                    // WooCommerce uses a filter on 'comment_text' or its own templates to add stars.
                    // We can also directly call a function if available and appropriate.
                    // Let's add a specific hook here for stars or call a WooCommerce function if one exists for just displaying stars.
                    // For now, we'll retrieve the rating from comment meta and display it.
                    $rating = get_comment_meta( get_comment_ID(), 'rating', true );
                    if ( $rating && class_exists('WooCommerce') ) {
                        ?>
                        <div class="ccr-review-stars" title="<?php printf( esc_attr__( 'Rated %d out of 5', 'custom-comments-reviews' ), $rating ); ?>">
                            <?php echo wc_get_star_rating_html( $rating, get_comment_ID() ); ?>
                        </div>
                        <?php
                    }
                }
                // No closing ?> here as we are still in the main PHP block of the function
            </div>

            <div class="ccr-reply">
                <?php
                comment_reply_link(
                    array_merge(
                        $args,
                        array(
                            'add_below' => 'div-comment',
                            'depth'     => $depth,
                            'max_depth' => $args['max_depth'],
                            'before'    => '<div class="ccr-reply-button">',
                            'after'     => '</div>',
                        )
                    )
                );
                ?>
            </div>
        </article>
    <?php
    // Note: The closing </<?php echo $tag; ?>> is not here.
    // WordPress automatically adds it for 'ul' and 'ol' lists.
    // For 'div' style, it should be added if this function were to be self-contained.
    // However, wp_list_comments handles the closing tag.
}

/**
 * Customizes the comment form.
 *
 * @param array $fields Default comment form fields.
 * @return array Modified comment form fields.
 */
function ccr_custom_comment_form_fields( $fields ) {
    // Customize fields here if needed, e.g., add/remove fields, change classes
    // For now, we'll keep it simple and focus on the display

    $commenter = wp_get_current_commenter();
    $req = get_option( 'require_name_email' );
    $aria_req = ( $req ? " aria-required='true'" : '' );

    $fields['author'] = '<p class="comment-form-author">' .
                        '<label for="author">' . __( 'Name', 'custom-comments-reviews' ) . ( $req ? ' <span class="required">*</span>' : '' ) . '</label> ' .
                        '<input id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) . '" size="30"' . $aria_req . ' /></p>';

    $fields['email'] = '<p class="comment-form-email"><label for="email">' . __( 'Email', 'custom-comments-reviews' ) . ( $req ? ' <span class="required">*</span>' : '' ) . '</label> ' .
                       '<input id="email" name="email" type="email" value="' . esc_attr(  $commenter['comment_author_email'] ) . '" size="30" aria-describedby="email-notes"' . $aria_req . ' /></p>';

    $fields['url'] = '<p class="comment-form-url"><label for="url">' . __( 'Website', 'custom-comments-reviews' ) . '</label>' .
                     '<input id="url" name="url" type="url" value="' . esc_attr( $commenter['comment_author_url'] ) . '" size="30" /></p>';

    return $fields;
}
// add_filter( 'comment_form_default_fields', 'ccr_custom_comment_form_fields' ); // Enable when ready to customize form fields

/**
 * Customizes the comment form defaults (textarea, submit button, etc.).
 *
 * @param array $defaults Default comment form arguments.
 * @return array Modified comment form arguments.
 */
function ccr_custom_comment_form_defaults( $defaults ) {
    $defaults['comment_field'] = '<p class="comment-form-comment"><label for="comment">' . _x( 'Comment', 'noun', 'custom-comments-reviews' ) . '</label><textarea id="comment" name="comment" cols="45" rows="8" maxlength="65525" required="required"></textarea></p>';

    $defaults['class_submit'] = 'ccr-submit-button'; // Add a custom class to the submit button
    $defaults['title_reply'] = esc_html__( 'Leave a Reply', 'custom-comments-reviews' );
    $defaults['title_reply_to'] = esc_html__( 'Leave a Reply to %s', 'custom-comments-reviews' );
    $defaults['cancel_reply_link'] = esc_html__( 'Cancel Reply', 'custom-comments-reviews' );
    $defaults['label_submit'] = esc_html__( 'Post Comment', 'custom-comments-reviews' );

    // Example: Add a wrapper around the form for styling
    $defaults['comment_notes_before'] = '<div class="ccr-comment-notes-before">' . $defaults['comment_notes_before'] . '</div>';
    $defaults['comment_notes_after'] = '<div class="ccr-comment-notes-after">' . $defaults['comment_notes_after'] . '</div>';
    $defaults['fields'] = apply_filters( 'comment_form_default_fields', $defaults['fields'] ); // Ensure our fields filter is applied

    return $defaults;
}
// add_filter( 'comment_form_defaults', 'ccr_custom_comment_form_defaults' ); // Enable when ready to customize the overall form

/**
 * Function to actually display comments.
 * This should be called in your theme's comments.php or via a filter.
 */
function ccr_list_comments() {
    // Check if comments are open or if there are comments.
    if ( comments_open() || get_comments_number() ) :
        ?>
        <div id="ccr-comments-area" class="ccr-comments-area">
            <h2 class="ccr-comments-title">
                <?php
                $comments_number = get_comments_number();
                if ( '1' === $comments_number ) {
                    /* translators: %s: post title */
                    printf( _x( 'One thought on &ldquo;%s&rdquo;', 'comments title', 'custom-comments-reviews' ), get_the_title() );
                } else {
                    printf(
                        /* translators: 1: number of comments, 2: post title */
                        _nx(
                            '%1$s thought on &ldquo;%2$s&rdquo;',
                            '%1$s thoughts on &ldquo;%2$s&rdquo;',
                            $comments_number,
                            'comments title',
                            'custom-comments-reviews'
                        ),
                        number_format_i18n( $comments_number ),
                        get_the_title()
                    );
                }
                ?>
            </h2>

            <?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : // Are there comments to navigate through? ?>
            <nav id="ccr-comment-nav-above" class="ccr-navigation ccr-comment-navigation" role="navigation">
                <h3 class="ccr-screen-reader-text"><?php esc_html_e( 'Comment navigation', 'custom-comments-reviews' ); ?></h3>
                <div class="ccr-nav-links">
                    <div class="ccr-nav-previous"><?php previous_comments_link( esc_html__( 'Older Comments', 'custom-comments-reviews' ) ); ?></div>
                    <div class="ccr-nav-next"><?php next_comments_link( esc_html__( 'Newer Comments', 'custom-comments-reviews' ) ); ?></div>
                </div><!-- .nav-links -->
            </nav><!-- #comment-nav-above -->
            <?php endif; // Check for comment navigation. ?>

            <ol class="ccr-comment-list">
                <?php
                wp_list_comments( array(
                    'style'       => 'ol', // ol, ul, div
                    'short_ping'  => true,
                    'avatar_size' => 50,
                    'callback'    => 'ccr_comment_display_callback', // Our custom callback
                    'max_depth'   => '', // Handled by WordPress
                ) );
                ?>
            </ol><!-- .comment-list -->

            <?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : // Are there comments to navigate through? ?>
            <nav id="ccr-comment-nav-below" class="ccr-navigation ccr-comment-navigation" role="navigation">
                <h3 class="ccr-screen-reader-text"><?php esc_html_e( 'Comment navigation', 'custom-comments-reviews' ); ?></h3>
                <div class="ccr-nav-links">
                    <div class="ccr-nav-previous"><?php previous_comments_link( esc_html__( 'Older Comments', 'custom-comments-reviews' ) ); ?></div>
                    <div class="ccr-nav-next"><?php next_comments_link( esc_html__( 'Newer Comments', 'custom-comments-reviews' ) ); ?></div>
                </div><!-- .nav-links -->
            </nav><!-- #comment-nav-below -->
            <?php endif; // Check for comment navigation. ?>

        </div><!-- #comments -->
        <?php
    endif;

    // If comments are closed and there are comments, let's leave a little note, shall we?
    if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) :
    ?>
        <p class="ccr-no-comments"><?php esc_html_e( 'Comments are closed.', 'custom-comments-reviews' ); ?></p>
    <?php
    endif;

    // Display the comment form.
    // We will use our custom defaults if the filter is enabled.
    add_filter( 'comment_form_default_fields', 'ccr_custom_comment_form_fields' );
    add_filter( 'comment_form_defaults', 'ccr_custom_comment_form_defaults' );
    comment_form();
    remove_filter( 'comment_form_default_fields', 'ccr_custom_comment_form_fields' );
    remove_filter( 'comment_form_defaults', 'ccr_custom_comment_form_defaults' );

}

/**
 * Filter to use our custom comments template.
 * This is a more robust way to replace the comments template.
 *
 * @param string $template The path to the template file.
 * @return string The path to our custom template file.
 */
function ccr_override_comments_template( $template ) {
    // Check if it's a single post or page, or a WooCommerce product page
    if ( is_singular() ) {
        // Check if comments are open or if there are any comments.
        if ( comments_open() || get_comments_number() ) {
            // Path to our plugin's custom comments template
            $plugin_template = CCR_PLUGIN_DIR . 'templates/ccr-comments-template.php';
            if ( file_exists( $plugin_template ) ) {
                return $plugin_template;
            }
        }
    }
    return $template;
}
add_filter( 'comments_template', 'ccr_override_comments_template', 99 ); // High priority to override others


// Helper function to check if we are on a WooCommerce product page
function ccr_is_woocommerce_product_page() {
    return function_exists( 'is_product' ) && is_product();
}
?>
