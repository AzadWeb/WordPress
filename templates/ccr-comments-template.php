<?php
/**
 * The template for displaying comments and the comment form.
 *
 * This is the template that displays the area of the page that contains both the current comments
 * and the comment form.
 *
 * @package Custom_Comments_Reviews
 */

/*
 * If the current post is protected by a password and
 * the visitor has not yet entered the password we will
 * return early without loading the comments.
 */
if ( post_password_required() ) {
	return;
}

// Ensure our comment functions are loaded
if ( ! function_exists( 'ccr_list_comments' ) ) {
    require_once CCR_PLUGIN_DIR . 'includes/comment-template-functions.php';
}
?>

<div id="comments" class="ccr-comments-wrapper">

    <?php
    // You can start editing here -- including this comment!

    // This function will list comments and display the comment form.
    // It includes wp_list_comments with our custom callback and comment_form with our custom arguments.
    ccr_list_comments();
    ?>

</div><!-- #comments -->
