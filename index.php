<html>
<head>
<title>Roller Derby Name Registrations</title>
<link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/ui-lightness/jquery-ui.css" type="text/css" media="all" />
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js" type="text/javascript"></script>
<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js" type="text/javascript"></script>
<style type="text/css">

<style type="text/css">
        html, body { font-family: sans-serif; }
        html { background-color: #eee; }

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
        td.cardno { width: 100px; }
        td.ttime { width: 150px; }
        td.tid { width: 90px; }
        td.product { text-align: left; width:350px; padding-left: .2em;}

        /* tr.odd { background-color: lightcyan; } */

        u, a { text-decoration: none; border-bottom: 1px solid; }

        table { width: 100%; border-collapse: collapse;}
        .ui-datepicker { font-size: .7em; }
        .input-prompt { position: absolute; font-style: italic; color: #aaa; margin: 0.2em 0 0 0.5em; }
        .headeroption { border: 2px solid black; margin: 0; padding: 0}
        #initials {position: relative; left: 0px; right: 0px; top: 0px; width: 100%; }
        .clicks { width: 100%; border: 0; padding: 0; margin: 0 }
        .h { cursor:pointer }
        .h:hover { background-color: #ccddee; }

</style>
</head>
<body>
<h2>Roller Derby Names</h2>
<p><input id='derbyname' type='text' title='Start typing a derby name...' size=50 /></p>
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
    $('#derbyname').autocomplete({ source: "http://rollerder.by/twoevils/ajax.php" });
    $.ajax({ url: "ajax.php?action=list",
        success: function(data) { 
            var newhtml = "<table><tr>"
            $("#initials").html("<table><tr>");
            $.each(data, function(x,y) {
                newhtml = newhtml + "<td class='h' data-value='"+y+"'>"+y+"</td>";
            })
            $("#initials").html(newhtml+"</tr></td></table>");
            $(".h").click(function() {loadcontent($(this).data("value"))});
        }
    });
});

function loadcontent(x) {
    alert(x);
}
</script>
