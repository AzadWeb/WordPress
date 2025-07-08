/**
 * Custom Comments & Reviews Frontend JavaScript
 */
(function($) {
    'use strict';

    $(document).ready(function() {

        // Like/Dislike functionality
        $('.ccr-comment-list').on('click', '.ccr-like-button, .ccr-dislike-button', function(e) {
            e.preventDefault();

            var $button = $(this);
            var $wrapper = $button.closest('.ccr-like-dislike-wrapper');
            var commentId = $wrapper.data('comment-id');
            var action = $button.data('action'); // 'like' or 'dislike'
            var $spinner = $wrapper.find('.ccr-vote-spinner');
            var $message = $wrapper.find('.ccr-vote-message');

            // Prevent multiple clicks while processing
            if ($button.hasClass('processing')) {
                return;
            }
            $button.addClass('processing');
            $spinner.show();
            $message.hide().removeClass('success error');

            $.ajax({
                url: ccr_ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'ccr_handle_like_dislike', // WordPress AJAX action hook
                    nonce: ccr_ajax_object.nonce,
                    comment_id: commentId,
                    vote_action: action
                },
                success: function(response) {
                    if (response.success) {
                        $wrapper.find('.ccr-like-count').text(response.data.likes);
                        $wrapper.find('.ccr-dislike-count').text(response.data.dislikes);

                        // Update button states
                        $wrapper.find('.ccr-like-button, .ccr-dislike-button').removeClass('voted');
                        if (response.data.new_status === 'liked') {
                            $wrapper.find('.ccr-like-button').addClass('voted');
                        } else if (response.data.new_status === 'disliked') {
                            $wrapper.find('.ccr-dislike-button').addClass('voted');
                        }

                        if(response.data.message) {
                            $message.text(response.data.message).addClass('success').show();
                             setTimeout(function() { $message.fadeOut(); }, 3000);
                        }

                    } else {
                        var errorMessage = response.data && response.data.message ? response.data.message : 'Error processing vote.';
                        $message.text(errorMessage).addClass('error').show();
                        console.error('Vote Error:', response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    $message.text('An unexpected error occurred.').addClass('error').show();
                },
                complete: function() {
                    $button.removeClass('processing');
                    $spinner.hide();
                }
            });
        });

    });

})(jQuery);
