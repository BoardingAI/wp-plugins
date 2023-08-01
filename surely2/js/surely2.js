jQuery(document).ready(function ($) {
    const logMessages = [];

    function logToDebugLog(message, data = null) {
        console.log(message, data);
        logMessages.push(message + (data ? ': ' + JSON.stringify(data) : ''));

        // Send the log message to the server
        $.ajax({
            url: surely_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'log_js_message',
                message: message + (data ? ': ' + JSON.stringify(data) : ''),
                security: $('#surely_nonce').val()
            }
        });
    }

    function getPromptStack(aspect) {
        const promptStacks = {
            'headline': ['headline_score', 'headline_generate', 'headline_best'],
            'keywords': ['keywords', 'keywords_refine'],
            'meta_description': ['meta_description'],
            'introduction': ['introduction', 'introduction_refine'],
            'conclusion': ['conclusion', 'conclusion_refine'],
            'image_alt_text': ['image_alt_text'],
            'image_caption': ['image_caption'],
            'call_to_action': ['call_to_action'],
            'internal_links': ['internal_links'],
            'external_links': ['external_links']
            // ...
        };

        return promptStacks[aspect] || [];
    }

    function updateTextarea(content) {
        $('#surely-response').val(content);
    }

    function showError(message) {
        $('#surely-loading').hide();
        updateTextarea('Error: ' + message);
    }

    function getPostTitle() {
        if (typeof wp !== 'undefined' && typeof wp.data !== 'undefined') {
            return wp.data.select('core/editor').getEditedPostAttribute('title');
        } else {
            return jQuery('#titlewrap #title').val();
        }
    }

    function getPostContent() {
        if (typeof wp !== 'undefined' && typeof wp.data !== 'undefined') {
            return wp.data.select('core/editor').getEditedPostContent();
        } else {
            let content;
            if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor && !tinyMCE.activeEditor.isHidden()) {
                content = tinyMCE.activeEditor.getContent();
            } else {
                content = jQuery('#content').val();
            }
            return content;
        }
    }

    function sendApiRequest(aspect, systemMessage, userMessage, previousResponses) {
        return new Promise((resolve, reject) => {
            const postId = $('#post_ID').val();
            let post_title, post_content;

            post_title = getPostTitle();
            post_content = getPostContent();

            logToDebugLog('Fetched post title: ' + post_title);
            logToDebugLog('Fetched post content: ' + post_content);

            console.log('aspect:', aspect);
            console.log('postId:', postId);
            console.log('post_title:', post_title);
            console.log('post_content:', post_content);

            $('#surely-loading').show();

            logToDebugLog('Sending AJAX request with aspect: ' + aspect + ', post_id: ' + postId + ', post_title: ' + post_title + ', post_content: ' + post_content);
            logToDebugLog('System prompt stack: ' + JSON.stringify(getPromptStack(aspect)));

            // Log the API request details
            logToDebugLog('API request details: aspect: ' + aspect + ', systemMessage: ' + systemMessage + ', userMessage: ' + userMessage + ', previousResponses: ' + JSON.stringify(previousResponses));

            // Include the system prompt in the 'messages' array of the API request
            const messages = [
                {
                    role: 'system',
                    content: systemMessage
                },
                {
                    role: 'user',
                    content: userMessage
                }
            ];

            if (previousResponses) {
                messages.push(...previousResponses.map(response => ({ role: 'user', content: response })));
            }

            // Log the API request data
            const requestData = {
                action: 'surely_optimize',
                aspect: aspect,
                post_id: postId,
                post_data: {
                    title: post_title,
                    content: post_content
                },
                log_messages: logMessages,
                security: $('#surely_nonce').val(),
                messages: messages
            };
            logToDebugLog('API request data for call of ' + systemMessages.length, requestData);

            $.ajax({
                url: surely_ajax.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: requestData
            })
                .done(function (response, textStatus) {
                    logToDebugLog('AJAX request completed with status: ' + textStatus);
                    console.log('Raw response:', response);
                    logToDebugLog('API response data for call of ' + systemMessages.length, response);
                    resolve(response);
                })
                .fail(function (jqXHR, textStatus, errorThrown) {
                    logToDebugLog('AJAX request failed with status: ' + textStatus + ' - ' + errorThrown);
                    showError('An error occurred during the AJAX request: ' + textStatus + ' - ' + errorThrown);
                    reject(errorThrown);
                });
        });
    }

    $('#surely-analyze').on('click', function () {
        const aspect = $('#surely-aspect').val();
        const systemMessages = getPromptStack(aspect);

        if (systemMessages.length > 0) {
            // Define the index variable
            const index = 0;

            // Perform the first API call
            sendApiRequest(aspect, systemMessages[index], "Headline: " + getPostTitle() + " Blog Post: " + getPostContent(), systemMessages)
                .then((response) => {
                    processApiResponse(response, aspect, index, systemMessages);
                })
                .catch((error) => {
                    // Handle errors
                    showError('Error during API call 1: ' + error);
                });
        } else {
            // Invalid aspect
            showError('Invalid aspect: ' + aspect);
            console.log('Invalid aspect:', aspect);
            logToDebugLog('Invalid aspect: ' + aspect);
        }
    });

    function processApiResponse(response, aspect, index, systemMessages) {
        // Process the response
        updateTextarea(response.choices[0].message.content);
        logToDebugLog('API call ' + (index + 1) + ' of ' + systemMessages.length + ' completed');

        if (index < systemMessages.length - 1) {
            // Store the previous responses in an array
            const previousResponses = index === 0 ? [] : systemMessages.slice(0, index).map((_, i) => response.choices[i].message.content);

            // For the keywords aspect, pass the entire response content instead of just the content
            const nextUserMessage = response.choices[0].message.content;

            // Log the response data for debugging
            console.log('Response data for API call ' + (index + 1) + ':', response);

            // Update the user message for the keywords_refine aspect
            let modifiedUserMessage = aspect === 'keywords' && index === 0 ? "Refine keywords: " + nextUserMessage.split(', ').join(', ') : nextUserMessage;

            // Perform the next API call
            sendApiRequest(aspect, systemMessages[index + 1], modifiedUserMessage, previousResponses)
                .then((nextResponse) => {
                    // Log the request data for debugging
                    console.log('Request data for API call ' + (index + 2) + ':', {
                        aspect: aspect,
                        systemMessage: systemMessages[index + 1],
                        userMessage: modifiedUserMessage,
                        previousResponses: previousResponses
                    });

                    processApiResponse(nextResponse, aspect, index + 1, systemMessages);
                })
                .catch((error) => {
                    // Handle errors
                    showError('Error during API call ' + (index + 2) + ': ' + error);
                });
        } else {
            // All API calls are completed
            $('#surely-loading').hide();
            console.log('All API calls completed');
            logToDebugLog('All API calls completed');
        }
    }

    $('#surely-insert').on('click', function () {
        const optimizedContent = $('#surely-response').val();
        const aspect = $('#surely-aspect').val();
        let currentContent;
        let contentElement;

        if (aspect === 'headline') {
            if (typeof wp !== 'undefined' && typeof wp.blocks !== 'undefined') {
                wp.data.dispatch('core/editor').editPost({ title: optimizedContent });
            } else {
                $('#title').val(optimizedContent);
            }
        } else {
            if (typeof wp !== 'undefined' && typeof wp.blocks !== 'undefined') {
                wp.data.dispatch('core/editor').insertBlock(wp.blocks.createBlock('core/paragraph', { content: optimizedContent }), 0);
            } else {
                if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor && !tinyMCE.activeEditor.isHidden()) {
                    tinyMCE.activeEditor.execCommand('mceInsertContent', false, optimizedContent);
                } else {
                    contentElement = $('#content');
                    currentContent = contentElement.val();
                    contentElement.val(currentContent + '\n\n' + optimizedContent);
                }
            }
        }
    });
});