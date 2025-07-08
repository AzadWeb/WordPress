<?php
/**
 * Like/Dislike Functionality
 *
 * @package Custom_Comments_Reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Get like count for a comment.
 *
 * @param int $comment_id Comment ID.
 * @return int Like count.
 */
function ccr_get_like_count( $comment_id ) {
    $count = get_comment_meta( $comment_id, '_ccr_like_count', true );
    return empty( $count ) ? 0 : intval( $count );
}

/**
 * Get dislike count for a comment.
 *
 * @param int $comment_id Comment ID.
 * @return int Dislike count.
 */
function ccr_get_dislike_count( $comment_id ) {
    $count = get_comment_meta( $comment_id, '_ccr_dislike_count', true );
    return empty( $count ) ? 0 : intval( $count );
}

/**
 * Display like/dislike buttons for a comment.
 *
 * @param int $comment_id Comment ID.
 */
function ccr_display_like_dislike_buttons( $comment_id ) {
    if ( ! $comment_id ) {
        $comment_id = get_comment_ID();
    }

    $like_count = ccr_get_like_count( $comment_id );
    $dislike_count = ccr_get_dislike_count( $comment_id );

    // Check if user has already voted (simple cookie-based check for non-logged-in, user meta for logged-in)
    // A more robust solution might involve IP checking for non-logged-in users or more advanced tracking.
    $user_vote = null;
    if ( is_user_logged_in() ) {
        $user_vote = get_user_meta( get_current_user_id(), '_ccr_comment_vote_' . $comment_id, true );
    } else {
        if ( isset( $_COOKIE['ccr_comment_vote_' . $comment_id] ) ) {
            $user_vote = sanitize_text_field( $_COOKIE['ccr_comment_vote_' . $comment_id] );
        }
    }

    $like_button_class = 'ccr-like-button';
    $dislike_button_class = 'ccr-dislike-button';

    if ( $user_vote === 'liked' ) {
        $like_button_class .= ' voted';
    } elseif ( $user_vote === 'disliked' ) {
        $dislike_button_class .= ' voted';
    }

    ob_start();
    ?>
    <div class="ccr-like-dislike-wrapper" data-comment-id="<?php echo esc_attr( $comment_id ); ?>">
        <button class="<?php echo esc_attr( $like_button_class ); ?>" data-action="like" title="<?php esc_attr_e('Like this comment', 'custom-comments-reviews'); ?>">
            <span class="ccr-icon ccr-icon-like">&#x1F44D;</span> <!-- Thumbs up emoji unicode -->
            <span class="ccr-like-count"><?php echo esc_html( $like_count ); ?></span>
        </button>
        <button class="<?php echo esc_attr( $dislike_button_class ); ?>" data-action="dislike" title="<?php esc_attr_e('Dislike this comment', 'custom-comments-reviews'); ?>">
            <span class="ccr-icon ccr-icon-dislike">&#x1F44E;</span> <!-- Thumbs down emoji unicode -->
            <span class="ccr-dislike-count"><?php echo esc_html( $dislike_count ); ?></span>
        </button>
        <span class="ccr-vote-spinner" style="display:none;"></span>
        <span class="ccr-vote-message" style="display:none;"></span>
    </div>
    <?php
    echo ob_get_clean();
}

/**
 * AJAX handler for liking/disliking a comment.
 */
function ccr_handle_like_dislike_ajax() {
    check_ajax_referer( 'ccr_like_dislike_nonce', 'nonce' );

    $comment_id = isset( $_POST['comment_id'] ) ? intval( $_POST['comment_id'] ) : 0;
    $action = isset( $_POST['vote_action'] ) ? sanitize_text_field( $_POST['vote_action'] ) : ''; // 'like' or 'dislike'

    if ( ! $comment_id || ! in_array( $action, array( 'like', 'dislike' ) ) ) {
        wp_send_json_error( array( 'message' => __( 'Invalid data.', 'custom-comments-reviews' ) ) );
    }

    $comment = get_comment( $comment_id );
    if ( ! $comment ) {
        wp_send_json_error( array( 'message' => __( 'Comment not found.', 'custom-comments-reviews' ) ) );
    }

    $user_id = get_current_user_id();
    $can_vote = false;
    $previous_vote = null;

    if ( $user_id ) { // Logged-in user
        $previous_vote = get_user_meta( $user_id, '_ccr_comment_vote_' . $comment_id, true );
        $can_vote = true;
    } else { // Guest user (cookie-based)
        if ( isset( $_COOKIE['ccr_comment_vote_' . $comment_id] ) ) {
            $previous_vote = sanitize_text_field( $_COOKIE['ccr_comment_vote_' . $comment_id] );
        }
        $can_vote = true; // Allow guests to vote, but it's less secure
    }

    if ( ! $can_vote ) { // Should not happen with current logic but as a safeguard
        wp_send_json_error( array( 'message' => __( 'You cannot vote at this time.', 'custom-comments-reviews' ) ) );
    }

    $like_count = ccr_get_like_count( $comment_id );
    $dislike_count = ccr_get_dislike_count( $comment_id );

    $new_vote_status = $action . 'd'; // 'liked' or 'disliked'

    // If user is clicking the same button again (e.g., clicking 'like' when already liked) - unvote
    if ( $previous_vote === $new_vote_status ) {
        if ( $action === 'like' ) {
            $like_count = max(0, $like_count - 1);
            update_comment_meta( $comment_id, '_ccr_like_count', $like_count );
        } else { // dislike
            $dislike_count = max(0, $dislike_count - 1);
            update_comment_meta( $comment_id, '_ccr_dislike_count', $dislike_count );
        }
        // Clear the vote
        if ( $user_id ) {
            delete_user_meta( $user_id, '_ccr_comment_vote_' . $comment_id );
        } else {
            setcookie( 'ccr_comment_vote_' . $comment_id, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN );
        }
        wp_send_json_success( array(
            'likes' => $like_count,
            'dislikes' => $dislike_count,
            'message' => __( 'Vote removed.', 'custom-comments-reviews' ),
            'new_status' => 'none'
        ) );
        return;
    }

    // If switching vote (e.g., was 'liked', now clicking 'dislike')
    if ( $previous_vote ) {
        if ( $previous_vote === 'liked' ) {
            $like_count = max(0, $like_count - 1);
        } elseif ( $previous_vote === 'disliked' ) {
            $dislike_count = max(0, $dislike_count - 1);
        }
    }

    // Apply the new vote
    if ( $action === 'like' ) {
        $like_count++;
    } else { // dislike
        $dislike_count++;
    }

    update_comment_meta( $comment_id, '_ccr_like_count', $like_count );
    update_comment_meta( $comment_id, '_ccr_dislike_count', $dislike_count );

    if ( $user_id ) {
        update_user_meta( $user_id, '_ccr_comment_vote_' . $comment_id, $new_vote_status );
    } else {
        // Set cookie for 1 year for guest users
        setcookie( 'ccr_comment_vote_' . $comment_id, $new_vote_status, time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
    }

    wp_send_json_success( array(
        'likes' => $like_count,
        'dislikes' => $dislike_count,
        'message' => __( 'Vote counted!', 'custom-comments-reviews' ),
        'new_status' => $new_vote_status
    ) );
}
add_action( 'wp_ajax_ccr_handle_like_dislike', 'ccr_handle_like_dislike_ajax' );
add_action( 'wp_ajax_nopriv_ccr_handle_like_dislike', 'ccr_handle_like_dislike_ajax' ); // For non-logged-in users

?>
