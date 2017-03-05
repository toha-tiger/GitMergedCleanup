$(document).ready(function() {

  $('.select-all').click(function() {
    $('form .branch input').prop('checked', true);
  });

  $('.select-local').click(function() {
    $('form .branch input').each(function(index, element) {
      if (!$(element).attr('name').match(/^origin\//)) {
        $(element).prop('checked', true);
      } else {
        $(element).prop('checked', false);
      }
    });
  });

  $('.select-remote').click(function() {
    $('form .branch input').each(function(index, element) {
      if ($(element).attr('name').match(/^origin\//)) {
        $(element).prop('checked', true);
      } else {
        $(element).prop('checked', false);
      }
    });
  });

  $('form .branch span.title').click(function(e) {
    var src, status, text;
    var issue = $(this).parent().find('input').attr('data-issue');
    if (issue.length) {
      src = jiraUrl + issue;
      status = 'loading ';
      text = issue;
      $('.preview iframe').show();
    } else {
      src = '';
      status = '';
      text = 'not an Jira issue';
      $('.preview iframe').hide();
    }
    $('.preview .info .issue').text(text);
    if ($('.preview iframe').attr('src') != src) {
      $('.preview .info .status').text(status);
      $('.preview iframe').attr('src', src);
    }
  });

  $('.preview iframe').on('load', function() {
    $('.preview .info .status').text('');
  });

});