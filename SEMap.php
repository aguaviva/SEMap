<!DOCTYPE html> 
<html>
	<head>
		<title>SE Map</title>
		<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1, maximum-scale=1">
        <script src="https://code.jquery.com/jquery-3.2.1.min.js"  integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4="  crossorigin="anonymous"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/cytoscape/3.2.5/cytoscape.min.js"></script>
		<script src="https://cdn.rawgit.com/cytoscape/cytoscape.js-cola/1.6.0/cola.js"></script>
		<script src="https://cdn.rawgit.com/cytoscape/cytoscape.js-cola/1.6.0/cytoscape-cola.js"></script>
        <link rel="stylesheet" href="https://gitcdn.link/repo/Chalarangelo/mini.css/master/dist/mini-default.min.css">
		<style>
            body { margin: 0; }
            .responsive-label { align-items: center; }
            .wide  {  margin: auto; }
            #cy 
            {
                border: solid;
                border-width: 1;
                height: calc(100vh - 70px);
                background-color: #eeeeee;
            }
		</style>
		<script>
		    function databaseHTTP()
		    {
		        var credentials = {};
                this.setCredentials = function (_credentials, successFunction)		    
                {
                    credentials = _credentials;
                    $.ajax({
                        type: "POST",
                        url: "SEMapDatabase.php",
                        dataType: "json",
                        data: JSON.stringify({"credentials":credentials}),
                        success: successFunction
                    });
                }
		        
                this.POST = function (nodes, edges, successFunction)		    
                {
                   $.ajax({
                      type: "POST",
                      url: "./SEMapDatabase.php",
                      dataType: "text",
                      data: JSON.stringify({nodes:nodes, edges:edges, credentials:credentials }),
                      success: successFunction
                    });                    
                }
		
                this.DELETE = function (nodes, edges, successFunction)		    
                {
                    $.ajax({
                      type: "DELETE",
                      url: "./SEMapDatabase.php",
                      dataType: "json",
                      contentType: "application/json; charset=utf-8",
                      data: JSON.stringify({nodes:nodes, edges:edges, credentials:credentials }),
                      success : successFunction
                    });                    
                }
		
                this.LoadDataFromDatabase = function (callback)		    
                {
                    $.get("./SEMapDatabase.php", function(data)
                    {
                        callback(data);
                    });
                }
		    }
		    
            ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		    // Database loading, deleting, saving
		    //
		    function databaseIO(_backend)
		    {
		        var backend = _backend;
		        var credentials = { username: "", password : ""};
		        
		        this.setCredentials = function (user,pass, callback)
		        {
		            credentials.username = user;
		            credentials.password = pass;

                    backend.setCredentials(credentials, callback)
		        }
		        
                this.POST = function(eles, successFunction)
                {
                    var nodes = [];
                    var edges = [];                    
                    for(var i=0;i<eles.length;i++)
                    {
                        var e = eles[i];
                        if (e.isNode())
                        {
                            nodes.push({id:e.id(),l:e.data("label"), x:e.position().x, y:e.position().y});
                        }
                        else if (e.isEdge())
                        {
                            edges.push({id:e.id(), s:e.data("source"), t:e.data("target"), l:e.data("label") });
                        }
                    }
                    
                    backend.POST(nodes,edges, successFunction);
                }
                
                this.DELETE = function(eles, successFunction)
                {
                    var nodes = [];
                    var edges = [];                    
                    for(var i=0;i<eles.length;i++)
                    {
                        var e = eles[i];
                        if (e.isNode())
                        {
                            nodes.push(e.id());
                        }
                        else if (e.isEdge())
                        {
                            edges.push(e.id());
                        }
                    }
                    
                    backend.DELETE(nodes,edges, successFunction);
                }
    
                this.LoadDataFromDatabase = function ()
                {
                    // load elements from database and build diagram                
                    backend.LoadDataFromDatabase( function(data)
                    {
                        var data = JSON.parse(data);
                        
                        var elms = []
                        
                        var nodes = data["nodes"];
                        for(var node in nodes)
                        {
                            var n = nodes[node];
                            elms.push( { group: "nodes", data: { id: n["id"], label: n["q"] }, position:{x:n["x"], y:n["y"]} })                      
                        }
    
                        var edges = data["edges"];
                        for(var edge in edges)
                        {
                            var e = edges[edge];
                            elms.push( { group: "edges", data: { id: e["id"], source: e["s"], target: e["t"], label: e["l"] } })                      
                        }
                        cy.add(elms);
                        SetStyle();
                        cy.fit();
                        cy.autolock( true );
                    });
                }
		    }
		    
		    
		    base = new databaseIO(new databaseHTTP());
		    
            function SetStyle()
            {
                var nds = cy.nodes()
                for(var i=0;i<nds.length;i++)
                {
                    if (nds[i].incomers().length==0)
                    {
                        nds[i].style({'border-color': 'red', "border-width":10})
                    }
                    else
                    {
                       nds[i].style({'border-color': 'black', "border-width":5})
                    }
                }                
            }

			document.addEventListener('DOMContentLoaded', function()
            {
                ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                // Login stuff
                //
                function loggedIn()
                {
                    return $("#loginlogout").text()=="Logout";
                }
                
                function logOut()
                {
                    $("#loginlogout").text("LogIn");        
                    credentials["username"] = "";
                    credentials["password"] = "";
                    $(".admin").hide();
                }
    
                $( "#loginlogout" ).click(function() 
                {
                    if (loggedIn())
                    {
                        logOut();
                        cy.autolock( true );
                    }
                    else
                    {
                        $("#modal-login").prop("checked", true);
                        cy.autolock( false );
                    }
                });
                
                $( "#login" ).click(function() 
                {
                    base.setCredentials($("#username").val(), $("#password").val(), function(res)
                    {
                        if (res.res=="OK")
                        {
                            $("#modal-login").prop("checked", false);
                            $("#loginlogout").text("Logout");        
                            $(".admin").show();
                            
                            $("#modal-admin-help").prop("checked", true);
                        }
                        else
                        {
                            alert(res.res);
                        }
                    });
                });

                $(".admin").hide();


                ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                // Handle graph's UI
                //
				var cy = window.cy = cytoscape(
				{
					container: document.getElementById('cy'),
                    boxSelectionEnabled: false,
                    
                    minZoom: 0.1,
                    maxZoom: 100,
                    wheelSensitivity: 0.1,
                    
					style: 
					[
						{
							selector: 'node',
							css: 
							{
								'content': 'data(label)',
                                'text-valign': 'center',
                                'text-halign': 'center',
                                'shape': 'roundrectangle',
                                'text-wrap': 'wrap',   
                                'width': '300px',       
                                'text-max-width' : '300px',
                                'height': '100px',     
							}
						},
						{
							selector: 'edge',
							css: 
                            {
                                'curve-style': 'bezier',
                                'label': 'data(label)',
								'target-arrow-shape': 'triangle'
							}
						}
					]
				});

                // tapholding brings up a dialog to edit the node and the enges text
                cy.on('taphold', 'node', function(evt)
                {
                    if (!loggedIn()) return;
                    
                    $("#dialog_label").text("Question")
                    $("#dialog_input").attr("data", evt.target.id());
                    $("#dialog_input").val(evt.target.data().label)
                    $("#modal-edit").prop("checked", true);
                });

                // tapholding brings up a dialog to edit the node and the enges text
                cy.on('taphold', 'edge', function(evt)
                {
                    if (!loggedIn()) return;
                    
                    $("#dialog_label").text("Answer")
                    $("#dialog_input").attr("data", evt.target.id());
                    $("#dialog_input").val(evt.target.data().label);
                    $("#modal-edit").prop("checked", true);
                });

                // save handler for the text editing dialog
                $( "#save" ).click(function()
                {
                    if (!loggedIn()) return;
                    
                    var q = $("#dialog_input")[0];
                    var id = q.attributes["data"].nodeValue;
                    cy.$( "#"+id ).data("label", q.value);
                    base.POST(cy.$( "#"+id ));
                    $("#modal-edit").prop("checked", false);
                });                   

                // 
                function generateRandomID()
                {
                    var id = ""+ Math.random();                    
                    return id.split(".").join("");
                }

                //right clicking on a edge inserts a new node
                cy.on('cxttap', 'edge', function(evt)
                {
                    if (!loggedIn()) return;
                    
                    var source = evt.target.source();
                    var target = evt.target.target();
                    
                    var sp = source.position();
                    var st = target.position();
                    
                    var id = generateRandomID();
                    
                    var eles = cy.add([
                      { group: "nodes", data: { id: id, label: "New Node" }, position: { x: (sp.x + st.x)*.5, y: (sp.y + st.y)*.5 } },                      
                      { group: "edges", data: { id: generateRandomID(), source: source.id(), target: id } },
                      { group: "edges", data: { id: generateRandomID(), source: id, target: target.id(), label: evt.target.data().label } }
                    ]);
                    base.POST(eles, function(data)
                    {
                        var eles = cy.remove(cy.getElementById(evt.target.id()));
                        SetStyle();
                        base.DELETE( eles );
                    });
                });

                //right clicking on the background creates a node
                cy.on('cxttap', function(evt)
                {   
                    if (!loggedIn()) return;
                    
                    if (evt.target===cy)
                    {                    
                        var eles = cy.add([{ group: "nodes", data: { id: generateRandomID(), label: "No question" }, renderedPosition: evt.originalEvent }]);
                        base.POST(eles);
                    }
                });
                
                // shift selecting 2 nodes creates a link
                cy.on('select', 'node', function(evt)
                {
                    if (!loggedIn()) return;
                    
                    var node = evt.target;
                    
                    var selected = cy.elements(":selected");
                    if (selected.length==2)
                    {
                        var source = (node.id()==selected[1].id())?selected[0].id():selected[1].id();
                        var target = node.id();
                    
                        var eles = cy.add([{ group: "edges", data: { id: generateRandomID(), source: source, target: target, label: "No answer"  } }]); 
                        SetStyle();
                        base.POST(eles);
                    }                    
                });

                // the delete key erases the selected nodes (sometimes it doesnt work! :( )
                $('html').keydown(function(e)
                {
                    if(e.keyCode == 46) 
                    {
                        if($(event.target).is('textarea') || $(event.target).is('input')) 
                            return;

                        if (!loggedIn()) return;
                        
                        var eles = cy.remove(":selected");
                        base.DELETE(eles);
                    }
                });
                
                $( "#applyLayout" ).click(function() 
                {
                    if (!loggedIn()) return;
                    
                    cy.layout({
						name: 'cola',
                        edgeLength:400
					}).start(10)
                });

                $( "#saveDatabase" ).click(function() 
                {
                    if (!loggedIn()) return;
                    
                    base.POST(cy.nodes());
                });

                $( "#help" ).click(function() 
                {
                    if (loggedIn()) 
                        $('#modal-admin-help').prop('checked', true);
                    else
                        $('#modal-help').prop('checked', true);
                });

                // Load graph from database
                //
                base.LoadDataFromDatabase();
                $("#modal-help").prop("checked", true);
			});
        
		</script>
	</head>
	<body>
	    
	    <!-- header -->
        <header class="sticky">
          <a href="#" class="logo">SE Map</a>
          <button id="help" >Help</button>
          <button class="admin" id="applyLayout" >Apply Layout</button>
          <button class="admin" id="saveDatabase">Save</button>
          <button id="loginlogout" style="float: right">Login</button>
        </header>
        
        <!-- graph area-->
        <div class="container">
            <div class="content">
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12">
                        <div id="cy"></div>
                    </div>            
                </div>            
            </div>         
        </div>

        <!-- editing dialog -->
        <input id="modal-edit" type="checkbox"/>
        <div class="modal">
          <div class="wide" style="width:750px">
            <label for="modal-edit"></label>
            <form>
                <h3 class="section">Edit field</h3>
                <fieldset>
                    <div class="row responsive-label">
                        <div class="col-sm-12 col-md-3">
                            <label for="question" id="dialog_label">Question</label>
                        </div>
                        <div class="col-sm-12 col-md">
                            <textarea type="text" id="dialog_input" style="width:85%;" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="input-group fluid">
                      <button type="button" class="primary" id="save">Save</button>
                      <button type="button" class="cancel" onclick='$("#modal-edit").prop("checked", false);'>Cancel</button>
                    </div>
                </fieldset>
            </form>
          </div>
        </div>

        <!-- login dialog -->
        <input id="modal-login" type="checkbox"/>
        <div class="modal">
            <div class="wide">
            <!-- Adding some flex properties to center the form and some height to the page, these can be omitted -->
              <label for="modal-login"></label>
              <form >
                <fieldset>
                  <legend>Login form</legend>
                    <div class="input-group fluid">
                      <label for="username" style="width: 80px;">Username</label>
                      <input name="username" value="" id="username" placeholder="username" type="text" >
                    </div>
                    <div class="input-group fluid">
                      <label for="pwd" style="width: 80px;">Password</label>
                      <input name="password" value="" id="password" placeholder="password" type="password">
                    </div>
                    <div class="input-group fluid">
                      <button type="button" class="primary" id="login">Login</button>
                      <button type="button" class="cancel" onclick='$("#modal-login").prop("checked", false);'>Cancel</button>
                    </div>
                </fieldset>
              </form>
            </div>
        </div>

        <!-- help dialog -->
        <input id="modal-help" type="checkbox"/>
        <div class="modal">
            <div class="wide">
                <div class="card large">
                    <div class="section">
                        <h3>Quick start</h3>
                        <ul>
                            <li>Start in the <b>highlighted</b> box and go from there.</li>
                            <li><b>Controls</b>: same as Google Maps.</li>
                            <li><b>Help</b> brings back this dialog.</li>
                        </ul>
                        <div class="input-group fluid">
                            <button type="button" class="primary" onclick='$("#modal-help").prop("checked", false);' id="login">OK</button>
                        </div>
                    </div>
                </div>                
            </div>
        </div>

        <!-- help dialog -->
        <input id="modal-admin-help" type="checkbox"/>
        <div class="modal">
            <div class="wide">
                <div class="card large">
                    <div class="section">
                        <h3>Quick start</h3>
                        <ul>
                            <li>The boxes are for the questions, the arrows for the answers</li>
                            <li><b>Long click</b> for editing text</li>
                            <li><b>Right click</b> creates a new box</li>
                            <li><b>Shift + clicking</b> in two boxes links them with an arrow</li>
                            <li><b>Del</b> key to erase an arrow/box</li>
                            <li><b>Help</b> brings back this dialog.</li>
                        </ul>
                        <div class="input-group fluid">
                            <button type="button" class="primary" onclick='$("#modal-admin-help").prop("checked", false);' id="login">OK</button>
                        </div>
                    </div>
                </div>                
            </div>
        </div>
        
	</body>
</html>
