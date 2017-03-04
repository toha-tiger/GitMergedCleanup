$(document).ready(function() {

  var jiraUrl = 'https://jira.capsid.com/browse/';

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
//    e.stopPropagation();
//    e.preventDefault();
    var issue = $(this).parent().find('input').attr('name').match(/JOB-\d{4,5}/);
    if (issue) {
      $('.preview .info .status').text('loading ');
      $('.preview .info .issue').text(issue[0]);
      var src = jiraUrl + issue;
      if ($('.preview iframe').attr('src') != src) {
        $('.preview iframe').attr('src', src);
      }
      $('.preview iframe').show();
    } else {
      $('.preview .info .status').text('');
      $('.preview .info .issue').text('not an issue');
      $('.preview iframe').hide();
      var src = '';
      if ($('.preview iframe').attr('src') != src) {
        $('.preview iframe').attr('src', src);
      }
    }
  });

  $('.preview iframe').on('load', function() {
    $('.preview .info .status').text('');
  });

});