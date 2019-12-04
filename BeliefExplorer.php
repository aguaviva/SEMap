<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport"           content="width=device-width, initial-scale=1"/>
<meta property="og:type"        content="article" /> 
<meta property="og:image"       content="https://semap.duckdns.org/BeliefExplorer_200x200.png" /> 
<meta property="og:description" content="Explore your belief here" />
<meta property="fb:app_id"      content="106897846035392"/>
<meta property="og:title"       content="<?php
//<meta property="og:url"         content="https://semap.duckdns.org/BeliefExplorer.php"/>

// Create connection
    $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
    if ($lang=="es")
        $conn = new SQLite3("SEMap_es.sqlite");
    else
        $conn = new SQLite3("SEMap_en.sqlite");

    $results = $conn->query("SELECT * FROM NODES where id='".$_GET['q']."';");
    $row = $results->fetchArray(1);
    if ($row["label"]=="No question")
    {
        $results = $conn->query("SELECT * FROM EDGES where target='".$row["id"]."';");
        $row = $results->fetchArray(1);
        //echo $row["source"];
        
        $results = $conn->query("SELECT label FROM NODES where id='".$row["source"]."';");
        $row = $results->fetchArray(1);
        
    }
    echo $row["label"];
    $conn->close();
?>"/>
<link rel="icon" href="/BeliefExplorer_200x200.png">
<title>Belief Explorer</title>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.2/jquery.min.js"></script>
<link rel="stylesheet" href="mini-default.min.css">
<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-3425939-8"></script>
<script>

function gtag(){dataLayer.push(arguments);}

var Database = {};
var locationNoVars = "";
var variables = {}

function ProcessWithPush(sectionName, itemChosen)
{
    history.pushState(sectionName, "", locationNoVars + "?q="+sectionName);
    Process(sectionName, itemChosen)
}

function ShowDialog(sectionName)
{
    //find answer that lead to this question
    for(var q in Database)
    {
        var entry = Database[q];
        var answers = entry.answers;
        for(var a in answers)
        {
            if (answers[a]==sectionName)
            {
                $("#cardTitle").html("<br/><b>OPPSS!! We don't have a good question for this claim, could you recommend a good one? Please let us know in the comment's section.</b><br/>");
                $("#question").html(" - " + entry.question);
                $("#answers").html(" - " + a);
                break;
            }
        }
    }
}

var firstTime = true;

function Process(sectionName, itemChosen)
{
    gtag('event', 'page', {'id':sectionName});    

    variables[sectionName] = itemChosen;
    var item = Database[sectionName];
 
    var comments = $("#comments");
    $("#comments").html("<div class='fb-comments' data-href='"+window.location.href+"' width='450' data-numposts='5'></div>");


    if (firstTime)
    {
        firstTime = false;
    }
    else
    {
        FB.XFBML.parse($("#comments")[0]);
    }
 
    if (item.question =="No question")
    {
        ShowDialog(sectionName)
        return;
    }
    
    $("cardTitle").html("");
    var question = item.question.replace(/\n/g, "<br/>");
    $("#question").html(question);
    var a="<ul>";
    for(var key in item.answers)
    {
        var res = "";
        var match = key.match(/\$(.*)\$/);
        if (match!=null && match.length==2)
            res = match[1]

        var answer = key.replace(/(\$)/g,"");
        var nextSectionName = item.answers[key];

        var onClick = 'ProcessWithPush("'+nextSectionName+'", "'+res+'");return false;';

        a+="<li>";
        if (Database[nextSectionName]!=undefined)
            a+="<a href=''onclick='"+onClick+"'>"+answer+"</a>";
        else
            a+="<div>"+answer+"</div>";
        a+="</li>";
    }
    a+="</ul>";
    $("#answers").html(a)
}

window.onpopstate = function(event) 
{
    if (event.state!==null)
        Process(event.state, "");
};

$(document).ready(function()
{
    window.dataLayer = window.dataLayer || [];
    gtag('js', new Date());
    gtag('config', 'UA-3425939-8');

    window.fbAsyncInit = function() {
        FB.init({
            appId      : '106897846035392',
            xfbml      : false,
            version    : 'v2.11'
        });
        FB.AppEvents.logPageView();
    };    
    
    (function(d, s, id) {
      var js, fjs = d.getElementsByTagName(s)[0];
      if (d.getElementById(id)) return;
      js = d.createElement(s); js.id = id;
      js.src = 'https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.11&appId=106897846035392';
      fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));

    $.ajax({
        type: "GET",
        url: "./SEMapDatabase.php",
        dataType: "json",
        success: function(data)
        {
            if (data.res!="OK") 
            {
                alert(data.res);
            }
            else
            {
                Database = {}
    
                var nodes = data["nodes"];
                for(var node in nodes)
                {
                    var n = nodes[node];
                    Database[n["id"]]={question:n["q"], answers:{}};
                }    
        
                var edges = data["edges"];
                for(var edge in edges)
                {
                    var e = edges[edge];
                    if (Database[e["s"]]!==undefined)
                        Database[e["s"]].answers[e["l"]] = e["t"];
                }    
                
                var startingPoint = "0244388211625568";
                
                splitUrl = window.location.href.split("?");
                locationNoVars = splitUrl[0]
                if (splitUrl.length==2)
                {
                    startingPoint =  splitUrl[1].split("=")[1]; 
                }
                
                history.replaceState(startingPoint, "", locationNoVars + "?q="+startingPoint);
                
                Process(startingPoint,  "");
            }
        }
    });    
})

</script>
</head>
<body>
    <div id="fb-root"></div>
    <script>
</script>    
    
    <header class="sticky">
        <img src="BeliefExplorer_200x200.png" style="max-height: 100%;max-width: 100%;position: relative;padding-top: 0;margin-top: 0;" alt="thinker logo" class="button">
        <a href="https://semap.duckdns.org/BeliefExplorer.php" class="logo">&nbsp;Belief Explorer</a>
        <div class="fb-like" data-href="https://semap.duckdns.org/BeliefExplorer.php" data-layout="button_count" data-action="like" data-size="large" data-show-faces="false" data-share="false" ></div>    </header>
    <div class="container">
        <div class="row">
            <div class="col-lg-6 col-lg-offset-3 col-md-6 col-md-offset-3 col-sm-12">
                <div style='text-align: center;' id="cardTitle"></div>
                <br>
                <form>
                    <fieldset>
                        <div id="question"></div>
                        <br>
                        <div class="input-group" id="answers"></div>
                    </fieldset>
                </form>
            </div>
        </div>
    </div>
    <br>
    <br>
    <div class="container">
        <div class="row">
            <div class="col-lg-6 col-lg-offset-3 col-md-6 col-md-offset-3 col-sm-12">
                <div style='text-align: center;' >Enjoy the comment section, please keep it constructive!</div>
            </div>
            <div style='text-align: center;' class="col-lg-12 col-md-12 col-sm-12" id="comments">
            </div>
        </div>
    </div>

    <footer>
        <p>MIT License | <a href="#">About</a> | <a href="https://github.com/aguaviva/SEMap">Github code</a> | <a href="PrivacyPolicy.html">Privacy</a></p>
    </footer>
</body>
</html>

