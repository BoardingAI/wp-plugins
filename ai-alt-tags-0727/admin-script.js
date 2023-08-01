// Wait for the document to be ready
jQuery(document).ready(function($) {
  // Declare a variable to check if the process is running
  var processing = false;

  // Add a click event listener to the button with id 'batch-ai-alt'
  $('#batch-ai-alt').on('click', function () {
    // Set processing to true and disable the button
    processing = true;
    $(this).attr('disabled', true);

    // Reset the progress bar and text
    $('#batch-ai-progress span').css('width', '0');
    $('#batch-ai-progress-wrapper span.progress-text').text(0);

    // Show the progress bar
    $('#batch-ai-progress-wrapper').show();

    // Start the AJAX process
    ajaxParseIds(1);
  });

  // Add a confirmation message when the user tries to leave the page while the process is running
  window.onbeforeunload = confirmAiAltProcessExit;
  function confirmAiAltProcessExit() {
    if (jQuery('#batch-ai-alt').attr('disabled')) {
      return "You have attempted to leave this page. Are you sure?";
    }
  }

  // Add a click event listener to the elements with class 'settings-expander' to toggle the visibility of the next div
  $('.settings-expander').on('click', function(e) {
    e.preventDefault();
    $(this).toggleClass('expanded');
    $(this).next('div').slideToggle();
  });

  // Add a click event listener to the elements with class 'notice-dismiss' to hide the parent element
  $('.notice-dismiss').on('click', function(e) {
    e.preventDefault();
    $(this).parent().hide();
  });

  // Define the AJAX process
  async function ajaxParseIds(page) {
    $.ajax({
      // Set the URL and method for the AJAX request
      url: ai_alt_tags.rest_url + '&page=' + page,
      method: 'GET',
      success: function (rsp, status, xhr) {
        // Get the total number of pages from the response headers
        var maxPages = parseInt(xhr.getResponseHeader('X-WP-TotalPages'), 10);

        // If there are no pages or an error occurred, show an error message and stop the process
        if (isNaN(maxPages) || maxPages === 0) {
          console.error('Error: No posts found or an error occurred. Response:', rsp, 'Status:', status, 'XHR:', xhr);
          alert('No posts found or an error occurred. Please check the console for more details.');
          $('#batch-ai-progress-wrapper').hide();
          $('#batch-ai-alt').removeAttr('disabled');
          processing = false;
          return;
        }

        // Initialize counters for the progress bar
        let completedImages = 0;
        let totalImages = rsp.length;

        // Create an array of promises for each image to be processed
        const promises = rsp.map(function (p, i) {
          return new Promise(function (resolve, reject) {
            setTimeout(function () {
              // Send a POST request for each image
              $.ajax({
                url: ai_alt_tags.ajax_url,
                method: 'POST',
                data: { 'action': 'ai_alt_tag', 'pid': p },
                success: function (rsp) {
                  // Increment the counter and update the progress bar and text
                  completedImages++;
                  $('#batch-ai-progress span').css('width', (completedImages / totalImages * 100) + '%');
                  $('#batch-ai-progress-wrapper span.progress-text').text(((completedImages / totalImages) * 100).toFixed(2));
                  resolve();
                },
                error: function (error) {
                  // Log any errors and continue the process
                  console.error('Error processing post ID:', p, error);
                  resolve();
                },
              });
            }, i * 100); // Delay each API call by 100ms
          });
        });

        // When all promises are resolved, check if there are more pages to process
        Promise.all(promises)
          .then(function () {
            if (page < maxPages) {
              // If there are more pages, call the function again with the next page
              setTimeout(function () {
                ajaxParseIds(page + 1);
              }, 250);
            } else {
              // If there are no more pages, finish the process
              $('#batch-ai-progress span').css('width', '100%');
              $('#batch-ai-alt').removeAttr('disabled');
              alert('Finished!');
              $('#batch-ai-progress-wrapper').hide();
              processing = false;
            }
          });
      },
      error: function (error) {
        // Log any errors and stop the process
        console.error('Error fetching posts:', error);
        alert('An error occurred while fetching posts. Please check the console for more details.');
        $('#batch-ai-progress-wrapper').hide();
        $('#batch-ai-alt').removeAttr('disabled');
        processing = false;
      },
    });
  }

  // Add a change event listener to the select element with name 'language' to update the options of the select element with name 'api_version'
  $('select[name="language"]').on('change', function() {
    var selectedLanguage = $(this).val();
    var apiVersionSelect = $('select[name="api_version"]');
    var apiVersionHidden = $('input[name="api_version_hidden"]');

    if (selectedLanguage === 'en') {
      // If the selected language is English, enable the select element and set the options to 'v3.2' and 'v4.0'
      apiVersionSelect.empty();
      apiVersionSelect.append($('<option>', { value: 'v3.2', text: 'v3.2' }));
      apiVersionSelect.append($('<option>', { value: 'v4.0', text: 'v4.0' }));
      apiVersionSelect.removeAttr('disabled');

      apiVersionSelect.val(ai_alt_tags.api_version);
    } else {
      // If the selected language is not English, disable the select element and set the option to 'v3.2'
      apiVersionSelect.val('v3.2');
      apiVersionSelect.attr('disabled', 'disabled');
    }

    // Update the hidden input element with the selected API version
    apiVersionHidden.val(apiVersionSelect.val());
  });

  // Trigger the change event to set the initial state of the select element with name 'api_version'
  $('select[name="language"]').trigger('change');

  // Add a change event listener to the input element with name 'batch_processing' to enable or disable the select element with name 'specific_version'
  $('input[name="batch_processing"]').on('change', function() {
    if ($('#batch_overwrite_specific').is(':checked')) {
      $('select[name="specific_version"]').removeAttr('disabled');
    } else {
      $('select[name="specific_version"]').attr('disabled', 'disabled');
    }
  });
});