<html>
<head>
<title>Roller Derby Name Registrations</title>
<link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/ui-lightness/jquery-ui.css" type="text/css" media="all" />
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js" type="text/javascript"></script>
<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js" type="text/javascript"></script>
<style type="text/css">

<style type="text/css">
        html { background-color: #eee; }
        html, body { font-family: sans-serif; }
        body {
                background-color: #fff;
                margin: 20px auto;
                width: 1000px;
                box-shadow: 0px 0px 20px 5px rgba(0, 0, 0, 0.5);
                box-sizing: border-box;
                padding: 10px;
        }
        h2 { text-align: center; margin-bottom: 2px;}
        h3 { text-align: left; margin: 5px;}

        th,td { text-align: center; border: 1px solid black; width:80px; }
        u, a { text-decoration: none; border-bottom: 1px solid; }

        table { width: 100%; border-collapse: collapse;}
        .ui-datepicker { font-size: .7em; }
        .input-prompt { position: absolute; font-style: italic; color: #aaa; margin: 0.2em 0 0 0.5em; }
        .headeroption { border: 2px solid black; margin: 0; padding: 0}
        #initials {position: relative; left: 0px; right: 0px; top: 0px; width: 100%; }
        .clicks { width: 100%; border: 0; padding: 0; margin: 0 }
        .h { cursor:pointer }
        .h:hover { background-color: #ccddee; }
        .pagejump { padding-left: 1em; color: red; cursor: pointer}
        .pagejump:hover { background-color: #ccddee; } 
        #spage { text-decoration: underline }

</style>
</head>
<body>
<h2>Roller Derby Names</h2>
<p><input id='derbyname' type='text' title='Start typing a derby name...' size=30 /></p>
<p><div id='initials'>Loading...</div></p>
<p id='content'>Content goes here</p>

<script type='text/javascript'>
$(document).ready(function(){
   $('input[type=text][title],input[type=password][title],textarea[title]').each(function(i){
      $(this).addClass('input-prompt-' + i);
      var promptSpan = $('<span class="input-prompt"/>');
      $(promptSpan).attr('id', 'input-prompt-' + i);
      $(promptSpan).append($(this).attr('title'));
      $(promptSpan).click(function(){
        $(this).hide();
        $('.' + $(this).attr('id')).focus();
      });
      if($(this).val() != ''){ $(promptSpan).hide(); }
      $(this).before(promptSpan);
      $(this).focus(function(){ $('#input-prompt-' + i).hide(); });
      $(this).blur(function(){
        if($(this).val() == ''){ $('#input-prompt-' + i).show(); }
      });
    });
   $('#derbyname').keyup(function() { 
       clearTimeout($.data(this, 'timer'));
       var wait = setTimeout(function() { searchname($("#derbyname").val()) }, 500);
       $(this).data('timer', wait);
   });
   $.ajax({ url: "ajax.php?action=list",
       success: function(data) { 
          var newhtml = "<table><tr>"
          $("#initials").html("<table><tr>");
          $.each(data, function(x,y) {
              newhtml = newhtml + '<td class="h" data-value="'+encodeURIComponent(y)+'">'+y+'</td>';
          })
          $("#initials").html(newhtml+"</tr></td></table>");
          $(".h").click(function() {loadcontent($(this).data("value"), 1)});
       }
   });
});

function loadcontent(x, page) {
    $("#content").html("<span id='loading'>Loading...</span>");
    $.ajax({ url: "ajax.php?action=getchar&char="+encodeURIComponent(x)+"&page="+page,
        success: function(data) { 
            console.log("I'm here");
            var newhtml="<div id='pageselect'>"+data['size']+" Results. ";
            if (typeof data['pages'] != 'undefined') {
                $.each(data['pages'], function(x, y) {
                    if (data['thispage'] == y) {
                        newhtml = newhtml + '<span class=pagejump id=spage data-value="'+y+'">'+y+'&nbsp;</span>';
                    } else {
                        newhtml = newhtml + '<span class=pagejump data-value="'+y+'">'+y+'&nbsp;</span>';
                    }
                });
            }
            newhtml = newhtml + "</div><table>\n";
            $.each(data['data'], function(x, y) {
                newhtml = newhtml + "<tr><td>" + y['derbyname'] + "</td><td>" + y['number'] + "</td>";
                newhtml = newhtml + "<td>" + y['dateadded'] + "</td><td>" + y['league'] + "</td><td>";
                newhtml = newhtml + y['registrar'] + "</td></tr>\n";
            });
            newhtml = newhtml + "</table>";
            $("#content").html(newhtml);
            $(".pagejump").click(function() {loadcontent(x, $(this).data("value"))});
        }
    });
}

function searchname(x) {
    if (x.length < 4) {
        $("#content").html("<span id='warning'>Please enter more than 3 characters</span>");
        return;
    }
    $("#content").html("<span id='loading'>Loading...</span>");
    $.ajax({ url: "ajax.php?action=search&name="+encodeURIComponent(x),
        success: function(data) { 
            var newhtml="<table>\n";
            $.each(data, function(x, y) {
                newhtml = newhtml + "<tr><td>" + y['derbyname'] + "</td><td>" + y['number'] + "</td>";
                newhtml = newhtml + "<td>" + y['dateadded'] + "</td><td>" + y['league'] + "</td><td>";
                newhtml = newhtml + y['registrar'] + "</td></tr>\n";
            });
            newhtml = newhtml + "</table>";
            $("#content").html(newhtml);
        }
    });
}



</script>
