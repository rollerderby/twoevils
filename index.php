<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<title>Roller Derby Name Registrations</title>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js" type="text/javascript"></script>
<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js" type="text/javascript"></script>
<script src="jquery.address-1.4.min.js" type="text/javascript"></script>
<style type="text/css">

<style type="text/css">
        html { background-color: #eee; }
        html, body { font-family: sans-serif; }
        html { overflow-y: scroll; }
        body {
                background-color: #fff;
                margin: 20px auto;
                width: 1000px;
                box-shadow: 0px 0px 20px 5px rgba(0, 0, 0, 0.5);
                box-sizing: border-box;
                padding: 10px;
        }
        h2 { text-align: center; margin-bottom: 2px;}

        th,td { text-align: center; border: 1px solid black; width:80px; }
        u, a { text-decoration: none; border-bottom: 1px solid; }

        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .input-prompt { position: absolute; font-style: italic; color: #aaa; margin: 0.2em 0 0 0.5em; }
        #initials {position: relative; left: 0px; right: 0px; top: 0px; width: 100%; }
        .h { cursor:pointer; width: 18px;  }
        .h:hover { background-color: #ccddee; }
        .pagejump { padding-left: 1em; color: red; cursor: pointer}
        .pagejump:hover { background-color: #ccddee; } 
        #spage { text-decoration: underline }
        table.main {width: 980px; }
        th { font-variant: small-caps; }
        th.dn { width: 50px; }
        th.dnum { width: 30px; }
        th.dj { width: 40px; }
        th.source { width: 40px; }
        td.dn { text-align: left; padding-left: .5em;}
        td.dnum { text-align: left; padding-left: .5em;}
        #xpageselect {float: right;}
        #content-1, #content-2 {display: none; position: absolute;}
        #displaybox { width: inherit; }

</style>
</head>
<body>
<h2>Roller Derby Names</h2>
<div id='left'><input id='derbyname' type='text' title='Search for a derby name...' size=30 /> &nbsp; <span id='status'></span></div>
<div id='right'><input id='soundslike' type='text' title='Search for a similar sounding name' size=40> (Doesn't work yet) </div>
<p><div id='initials'>Loading...</div></p>
<span id=displaybox>
<p id='content-1'>Content goes here</p>
<p id='content-2'>Content goes here</p>
</span>

<script type='text/javascript'>
$(document).ready(function(){
    $.address.change(function(event){ 
        /* Regexp is winrar */
        var reg = event.value.match(/letter=(.)&page=(.+)/);
        if (reg != null) { loadcontent(reg[1], reg[2]); }
    });
    window.currentcontent = 'content-1';
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
        if ($("#derbyname").val().length == 0) {
            $("#status").html("");
        } else if ($('#derbyname').val().length < 4) {
            $("#status").html("Please enter more than 3 characters.");
        } else {
            $("#status").html("Waiting for you to finish typing...");
        }
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
          $(".h").click(function() {
              loadcontent($(this).data("value"), 1);
          });
<?php
  if (!isset($_REQUEST['page'])) { $page = 1; } else { $page = $_REQUEST['page']; }
  if (isset($_REQUEST['letter'])) { print "          loadcontent('".$_REQUEST['letter']."', $page);\n"; } 
?>
       }
   });
    
});

function loadcontent(x, page) {
    var stateObj;
    $.address.value("letter="+x+"&page="+page);
    $(".h").each(function() {
        if ($(this).data("value") == x) {
            $(this).animate({ backgroundColor: "#f6f6f6" }, 'fast');
        } else {
            $(this).animate({ backgroundColor: "#ccddee" }, 'fast');
        }
    });
    $.ajax({ url: "ajax.php?action=getchar&char="+encodeURIComponent(x)+"&page="+page,
        success: function(data) { 
            var newhtml="<span id='pageselect'>"+data['size']+" Results. ";
            if (typeof data['pages'] != 'undefined') {
                newhtml = newhtml + '(' +data['pagecount'] + ' pages) ';
                $.each(data['pages'], function(x, y) {
                    if (data['thispage'] == y) {
                        newhtml = newhtml + '<span class=pagejump id=spage data-value="'+y+'">'+y+'&nbsp;</span>';
                    } else {
                        newhtml = newhtml + '<span class=pagejump data-value="'+y+'">'+y+'&nbsp;</span>';
                    }
                });
            }
            newhtml = newhtml + "</span>" + header();
            $.each(data['data'], function(x, y) {
                newhtml = newhtml + "<tr><td class='dn'>" + y['derbyname'] + "</td><td class='dnum'>" + y['number'] + "</td>";
                newhtml = newhtml + "<td class='dj'>" + y['dateadded'] + "</td><td class='club'>" + y['league'] + "</td><td class='source'>";
                newhtml = newhtml + y['registrar'] + "</td></tr>\n";
            });
            newhtml = newhtml + "</table>";
            disp(newhtml);
            $(".pagejump").click(function() {loadcontent(x, $(this).data("value"))});
        }
    });
}

function searchname(x) {
    $(".h").each(function() {
            $(this).animate({ backgroundColor: "#f6f6f6" }, 'fast');
    });
    if (x.length < 4) {
        $("#status").html("Please enter more than 3 characters.");
        return;
    }
    $("#status").html("Searching...");
    $.ajax({ url: "ajax.php?action=search&name="+encodeURIComponent(x),
        success: function(data) { 
            var newhtml=header();
            $.each(data, function(x, y) {
                newhtml = newhtml + "<tr><td>" + y['derbyname'] + "</td><td>" + y['number'] + "</td>";
                newhtml = newhtml + "<td>" + y['dateadded'] + "</td><td>" + y['league'] + "</td><td>";
                newhtml = newhtml + y['registrar'] + "</td></tr>\n";
            });
            newhtml = newhtml + "</table>";
            disp(newhtml);
            $("#status").html("");
        }
    });
}

function header() {
    var html = '<table class="main"><tr><th class="dn">Derby Name</th><th class="dnum">Number</th>';
    html = html + '<th class="dj">Date Joined</th><th class="club">Club</th><th class="source">Source</th>\n';
    return html
}

function disp(x) {
    if (window.currentcontent == 'content-1') {
        window.currentcontent = 'content-2';
        $("#content-2").html(x);
        $("#content-1").fadeOut(400);
        $("#content-2").fadeIn(400);
        var nh = $("#content-2").height()+200;
        if (nh < $(window).height() - 30) {
            nh = $(window).height() - 30;
        }
        $("body").css({ "height" : nh });
    } else {
        window.currentcontent = 'content-1';
        $("#content-1").html(x);
        $("#content-2").fadeOut(400);
        $("#content-1").fadeIn(400);
        var nh = $("#content-1").height()+200;
        if (nh < $(window).height() - 30) {
            nh = $(window).height() - 30;
        }
        $("body").css({ "height" : nh });
    }
}

</script>
