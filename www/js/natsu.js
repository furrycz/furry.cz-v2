var WindowTitle = document.title;

var openWindow;
		tinymce.init({
			setup: function(editor) {
				editor.addButton('furrafinityLinker', {
					text: false,
					tooltip: "Furraffinity linker",
					image: basepath+'\/images\/fur-affinity_icon_sm_zps6825a89d.png',
					onclick: function() {
						//editor.insertContent('Main button');
						openWindow = editor.windowManager.open({
							title: "Furrafinity linker",
							url: furralinkerurl,
							width: 600,
							height: 150,
							buttons: [{
										text: 'Vložit',
										onclick: function(){ 
												$.ajax({
													url: furrafinityget,
													data: {
														url: jQuery(jQuery("#"+openWindow._id+"-body").find('iframe').contents()).contents().find('#urlFurrafinity')[0].value
													},
													success: function( data ) {
														if(data.Error!=0)
															tinyMCE.activeEditor.windowManager.alert(data.Error);
														else{
															editor.insertContent('<a href="'+data.urlDefault+'" target="_blank"><img src="'+data.urlImage+'" style="max-width:400px;max-height:400px;" border=3></a>');	
															top.tinymce.activeEditor.windowManager.close();
														}	
													}
												});
										}
									}]
						});
					}
				});
			},
			selector: "textarea.tinimce",
			plugins: [
				"advlist autolink lists link image charmap print preview anchor spellchecker",
				"searchreplace visualblocks code fullscreen preview",
				"insertdatetime media table contextmenu paste textcolor mention link"
			],
			toolbar: "insertfile undo redo | styleselect | furrafinityLinker | removeformat | bold italic | forecolor backcolor | bullist numlist outdent indent | link image charmap | preview fullscreen ",
			menubar: false,
			toolbar_items_size: 'small',
			//statusbar: false,
			convert_urls: false,
			mentions: {
				delimiter: "#",
				source: function(query, process) {					
					$.getJSON(autocompleteur, function(data) {
						process(data);
					});
				},
				insert: function(item) {
					return '<a href="http://' + windowhosturlu + linktoprofileu + '/' + item.id + '">' + item.name.trim() + '</a>';
				}	
			}
		});

function NotifView(op){
	selectedDIV = "notificatons";
	if(op!=5){ $("#notificatons").toggle(); }
	$("#nottifSpin").css('display',"inline")
	if($("#notificatons").css('display')=="block"){
		if($("#buttonNotification").css('display')=="none"){ $("#buttonNotification").css('display',"inline");posbut = $("#buttonNotification").offset();$("#buttonNotification").css('display',"none");}
		else{ posbut = $("#buttonNotification").offset();}
		$("#notificatons").offset({ top: (posbut.top+24), left: (posbut.left-400+24)});
		if($("#notificationsText").html()==""){ $("#notificationsText").html("<div style='text-align:center;padding:7px;'>Načítám....</div>"); }
		$.ajax({
			url: notificationaj,
			data: { "jak":jak },
			success: function( data ) {	
				$("#nottifSpin").css('display',"none")
				$("#notificationsText").html("");
				for(i=0;i<data.length;i++){
					if(data[i].Url!=""){ divN="a";style=""; }else{ divN="div";style="cursor:default;"; }
					$("<"+divN+" style='"+style+"' class='notifList "+data[i].Class+"' href='"+data[i].Url+"'><img src='"+data[i].Image+"' class='img'><div class=body><div class='text'>"+data[i].Text+"</div><div class='info'>"+data[i].Info+"</div></div><div style='clear:both;'></div><div style='clear:both;'></div></"+divN+">").appendTo("#notificationsText");
				}		
				if(data.length==0){ $("<div style='text-align:center;padding:7px;'>Žádná notifikace k zobrazení!</div>").appendTo("#notificationsText"); }					
			}
		});
	}
}

/*Notification create*/
notifID = 0;
function NotificationCreate(text,desc,href,image){
	if(typeof image!="undefined" && image!="")
	{ $("<a class='notifAlert' id='notification_"+notifID+"' style='display:none;' href='"+href+"'><img src='"+image+"' style='float:left;width:50px;height:50px;padding-top: 4px;padding-bottom:4px;padding-right: 6px;'><div class=text>"+text+"</div><div class=info style='padding-left: 50px;'>"+desc+"</div><div style='clear:both;'></div></a>").appendTo("#notificatonsAlert"); }
	else
	{ $("<a class='notifAlert' id='notification_"+notifID+"' style='display:none;' href='"+href+"'><div class=text>"+text+"</div><div class=info>"+desc+"</div></a>").appendTo("#notificatonsAlert"); }
	$("#notification_"+notifID).fadeIn( "slow", function() {} );
	setTimeout(function(bi) { $("#notification_"+bi).fadeOut( "slow", function() {} ); }, 30000, notifID);
	notifID++;
}

function NotificationControl(){
	$.ajax({
			url: notifcountajax,
			data: {},
			success: function( data ) {					
				$("#buttonNotification").html(data.Count);
				$("#buttonNotification").css("display","inline");
				if(data.Count>0 || data.Notif>0){ $("#buttonNotification").css("background-color","red");document.title = "("+(data.Count+data.Notif)+") "+WindowTitle; }else{ $("#buttonNotification").css("background-color","");document.title = WindowTitle; }
				if(data.Notif>0 && data.Count>0){
					$("#buttonNotificationOther").html(data.Notif);
					$("#buttonNotificationOther").css("display","inline");
					$("#buttonNotificationOther").css("margin-right",-5);
					$("#buttonNotificationOther").css("border-top-right-radius",0);
					$("#buttonNotification").css("border-top-left-radius",0);
					$("#buttonNotificationOther").css("border-bottom-right-radius",0);
					$("#buttonNotification").css("border-bottom-left-radius",0);
				}else if(data.Notif>0){
					$("#buttonNotification").css("display","none");
					$("#buttonNotificationOther").html(data.Notif);
					$("#buttonNotificationOther").css("display","inline");
					$("#buttonNotificationOther").css("margin-right",0);
					$("#buttonNotificationOther").css("border-top-right-radius",5);
					$("#buttonNotification").css("border-top-left-radius",5);
					$("#buttonNotificationOther").css("border-bottom-right-radius",5);
					$("#buttonNotification").css("border-bottom-left-radius",5);
				}else if(data.Count>0){						
					$("#buttonNotificationOther").html(data.Notif);
					$("#buttonNotificationOther").css("display","none");
					$("#buttonNotification").css("border-top-left-radius",5);
					$("#buttonNotification").css("border-bottom-left-radius",5);
				}else{ $("#buttonNotificationOther").css("display","none"); }
			}
		});					
	$.ajax({				
			url: notificationco+"?time="+(timeNot-1000),
			data: {},
			success: function( data ) {		
				for(i=1;i<data.length;i++){
					NotificationCreate(data[i].Text,data[i].Info,data[i].Href,data[i].Image);
				}
				timeNot = data[0].time;
			}
		});	
}

function ContextMenuClickable(){
	$(".ContextMenu").each(function(i)
	{
	if($(this).attr("jsed")!=1){
		selectbox_html = $(this).html();
		$(this).attr("jsed",1);
		$(this).append(" &#x25bc;");
		$(this).click(function(){
			var divi = $(this).attr("dropdown");
			var ope_ = $(this).attr("dropdown-open");
			var abs_ = $(this).attr("dropdown-absolute");	
			var sel_ = $(this).attr("selectType");
			$("#"+divi).css("width",$(window).width()-$("#"+divi).offset().left-20);
			if($("#"+divi).css('display')=="none"){			
				if(abs_=="true"){ top_=$(this).offset().top;left_=$(this).offset().left; }else{ top_=0;left_=0; }
				if(ope_=="right"){
					//$("#"+divi).show();
					$("#"+divi).appendTo("body");
					$("#"+divi).show();
					$("#"+divi).css("top",$(this).offset().top+this.offsetHeight-1);						
					$("#"+divi).css("left",$(this).offset().left-($($("#"+divi).children(".listBox")[0]).outerWidth()-this.offsetWidth));												
					/*
					$("#"+divi).css("top",top_+this.offsetHeight-1);
					$("#"+divi).css("right",left_);//$("#"+divi).children(".listBox")[0].offsetWidth
					*/
				}
				if(ope_=="left"){
					$("#"+divi).appendTo("body");
					$("#"+divi).css("top",$(this).offset().top+this.offsetHeight-1);
					$("#"+divi).css("left",$(this).offset().left);
					$("#"+divi).show();
					//$("#"+divi).css("right",$("#"+divi).children(".listBox")[0].offsetWidth);
				}	
				if(ope_=="top"){
					$("#"+divi).appendTo("body");
					$("#"+divi).show();
					$("#"+divi).css("top",$(this).offset().top-($($("#"+divi).children(".listBox")[0]).outerHeight()));
					$("#"+divi).css("left",$(this).offset().left);						
					//$("#"+divi).css("right",$("#"+divi).children(".listBox")[0].offsetWidth);
				}						
				$("#"+divi).css("width",$(window).width()-$("#"+divi).offset().left-20);					
				$(this).addClass("selected");
				selectedDIV = divi;
				selectedBUT = $(this);
				return false;
			}
		});
		
		var selectData = new Array();
		$(this).each(function(i)
		{
			var sel_ = $(this).attr("selectType");			
			var divi = $(this).attr("dropdown");
			selectData[divi] = new Array();
			selectData[divi][0] = 0;
			selectData[divi][1] = $(this);	
			if(sel_=="2"){
				$(this).html("");
				var wid_ = $(this).attr("width");
				if(typeof wid_ == "undefined"){wid_=100;}
				//$(this).html("&#x25bc;");
				$(this).width(wid_);
				
				inputer = document.createElement('input');
				inputer.setAttribute("parent",i);
				inputer.setAttribute("divi",divi);
				inputer.setAttribute("style","width:"+(wid_-25)+"px");
				inputer.setAttribute("placeholder",selectbox_html);
				
				$(inputer).keyup(function( event ) {	
					mam=0;mat="";map=0;maq=0;
					valu_trigged_input  = $(this).val();
					divi = $(this).attr("divi");
					$("#"+divi).find("li").each(function(i){
						value_trigged_list = $(this).find("a")[0].innerHTML;
						if( value_trigged_list.toLowerCase().indexOf( valu_trigged_input.toLowerCase() ) ==-1 ){
							$(this).hide();
						}
						else{ 
							mam++;
							mat = value_trigged_list;
							map = i;
							$(this).show();
							if( value_trigged_list.toLowerCase() ==  valu_trigged_input.toLowerCase()){								
								maq++;
								divi = $(this).attr("divi");
								$(this).addClass("selx");
								selectData[divi][0] = $(this).attr("pos");
								if(event.keyCode>=48 && event.keyCode<=122){ selectData[divi][2].val(value_trigged_list); }
								selectData[divi][1].attr("value_",$(this).attr("value_")); 
								eval("var value_='"+$(this).attr("value_")+"';"+selectData[divi][1].attr("onChange"));
								
								$("#"+divi).find("li").each(function(i){
									if(selectData[divi][0]!=i){
										$(this).removeClass("selx");
									}						
								 });		
								
							}else{
								$(this).removeClass("selx");
							}
						}													
					});
					if(mam==1 && event.keyCode!=8 && event.keyCode>=48 && event.keyCode<=122){  						
						$("#"+divi).find("li").each(function(i){								
							divi = $(this).attr("divi");
							if(map==i){ 
								$(this).addClass("selx"); 
								selectData[divi][0] = $(this).attr("pos");
								selectData[divi][2].val($(this).find("a")[0].innerHTML);	
								selectData[divi][1].attr("value_",$(this).attr("value_")); 
								maq=1;
								eval("var value_='"+$(this).attr("value_")+"';"+selectData[divi][1].attr("onChange"));
							}
							else{ $(this).removeClass("selx"); }								
						});						
					}
					if(mam==0){selectData[divi][2].animate({backgroundColor:'red'},200);}else{selectData[divi][2].animate({backgroundColor:'transparent'},200);}
					if(maq<1){if(selectData[divi][0]!=0){ selectData[divi][0] = 0;selectData[divi][1].attr("value_","");eval("var value_='';"+selectData[divi][1].attr("onChange"));} }
				});
				
				selectData[divi][2] = $(inputer);
				$(this).append(inputer);
				
				span = document.createElement('span');
				$(span).html("&#x25bc;");
				$(this).append(span);
				
				//$(this).html("&#x25bc;");
				$(this).attr("value_","");			
				$("#"+divi).find("li").each(function(i){
					if($(this).attr("selx")==1){ 
						$(this).addClass("selx");
						selectData[divi][2].val($(this).find("a")[0].innerHTML);	
						selectData[divi][1].attr("value_",$(this).attr("value_")); 
					}
					$(this).attr("divi",divi);
					$(this).attr("pos",i);
					$(this).click(function(){
						divi = $(this).attr("divi");
						$(this).addClass("selx");
						selectData[divi][0] = $(this).attr("pos");
						selectData[divi][2].val($(this).find("a")[0].innerHTML);					
						selectData[divi][1].attr("value_",$(this).attr("value_"));
						eval("var value_='"+$(this).attr("value_")+"';"+selectData[divi][1].attr("onChange"));
						$("#"+divi).find("li").each(function(i){
							if(selectData[divi][0]!=i){
								$(this).removeClass("selx");
								$(this).removeClass("nos");
								//$(this).addClass("nos");
								//Close!								
							}									
						 });
						 $("#"+selectedDIV).hide();
						 selectedDIV="";
						 selectedBUT.removeClass("selected");
						 selectData[divi][2].keyup();
					});
				});
			}else if(sel_=="1"){
				$(this).html(" &#x25bc;");
				$(this).attr("value_","");			
				$("#"+divi).find("li").each(function(i){
					if($(this).attr("sel")==1){ $(this).addClass("sel");selectData[divi][1].html($(this).find("a")[0].innerHTML+" &#x25bc;");selectData[divi][1].attr("value_",$(this).attr("value_")); }else{ $(this).addClass("nos"); }
					$(this).attr("divi",divi);
					$(this).attr("pos",i);
					$(this).click(function(){
						divi = $(this).attr("divi");
						$(this).addClass("sel");
						selectData[divi][0] = $(this).attr("pos");
						selectData[divi][1].html($(this).find("a")[0].innerHTML+" &#x25bc;");					
						selectData[divi][1].attr("value_",$(this).attr("value_"));
						eval("var value_='"+$(this).attr("value_")+"';"+selectData[divi][1].attr("onChange"));
						$("#"+divi).find("li").each(function(i){
							if(selectData[divi][0]!=i){
								$(this).removeClass("sel");$(this).removeClass("nos");
								$(this).addClass("nos");								
								//Close!								
							}						
						 });						 
						 $("#"+selectedDIV).hide();
						 selectedDIV="";
						 selectedBUT.removeClass("selected");
					});
				});
			}else{
				$("#"+divi).find("li").each(function(i){
					$(this).click(function(){
						//Close!							
						$("#"+selectedDIV).hide();
						selectedDIV="";
						selectedBUT.removeClass("selected");
						if($($(this).find("a")[0]).attr("href")=="#no"){ return false; }
					});
				});
			}
		});	
		$(this).on('selectstart', false);
		}
	});
}

function loading_show(){ $("#dialog-loading").show("scale",400); }
function loading_hide(){ $("#dialog-loading").hide("fade",200);  }

function UserShowCategory(ids){
	html="";
	if(ids==-1){
		for(i=0;i<allUserCategory.sekcion.length;i++){
			if(typeof allUserCategory.users[i]!="undefined"){
				for(a=0;a<allUserCategory.users[i].length;a++){
					html+="<div class='ratingBoxis'><img src='{!$baseUrl}/images/avatars/"+allUserCategory.users[i][a]["Avatar"]+"' class='ratingAvatar'><div style='float:left;'><b><a style='bacground:white;' href='{!$presenter->link('Intercom:default')}/"+allUserCategory.users[i][a]["Id"]+"/'>"+allUserCategory.users[i][a]["Name"]+"</a></b><br>"+allUserCategory.sekcion[i]+"</div><div style='clear:both;'></div></div>";
				}
			}
		}
	}else{
		if(typeof allUserCategory.users[ids] == "undefined"){
			html = "<div style='font-weight:bold;text-align:center;padding:20px;'>Nebyly nalezeny žádné výsledky.</div>";
		}else{
			for(var i=0;i<allUserCategory.users[ids].length;i++){
				html+="<div class='ratingBoxis'><img src='{!$baseUrl}/images/avatars/"+allUserCategory.users[ids][i]["Avatar"]+"' class='ratingAvatar'><div style='float:left;'><b>"+allUserCategory.users[ids][i]["Name"]+"</b><br><a href='{!$presenter->link('Intercom:default')}/"+allUserCategory.users[ids][i]["Id"]+"/'>Poslat zprávu</a></div><div style='clear:both;'></div></div>";
			}
		}
	}	
	$("#UserListCategory").html(html);
}

var swpier_toggle_body = new Array();
var swpier_toggle_id = new Array(); 
var resizer_klt = new Array();
var progres_bar = new Array();

$("input[type=\"progressbar\"]").each(function(i)
	{
		var state = $(this).attr("value");
		var width = $(this).outerWidth(true);
		var max = $(this).attr("data-max");
		var proc = Math.round((state/max)*100);
		var wigra = (width/100)*state;
				
		$(this).css("display","none");
		this.setAttribute("parent",i);
		body = document.createElement('div');
		body.setAttribute("parent",i);
		$(body).css("width",width+"px");
		$(body).addClass("progress_bar");
		$(body).html("<span class=pro>"+proc+"%</span>");
		
		gra = document.createElement('div');
		$(gra).css("width",wigra+"px");
		$(gra).addClass("gra");
		$(body).append(gra);
		
		text = document.createElement('div');
		$(text).css("width",width+"px");
		$(text).addClass("text");
		$(text).html(proc+"%");
		$(gra).append(text);		
		
		progres_bar[i] = new Array($(this),body);
		
		$(this).bind("change paste keyup", function(event){
			var state = $(this).val();
			var max = $(this).attr("data-max");
			var proc = Math.round((state/max)*100);
			var wigra = (state/width)*100;
			var parent = $(this).attr("parent");
			var div = progres_bar[parent][1];
			
			$($(div).find(".pro")[0]).html(proc+"%");
			//$($(div).find(".gra")[0]).css("width",wigra+"px");
			$($(div).find(".gra")[0]).animate({ width: wigra+"px",}, 200 );
			$($(div).find(".text")[0]).html(proc+"%");
		});
		
		$(this).after(body);
	}
);	
$("img[type=\"image_resize\"]").load(function(a)
	{
		i=a.timeStamp;
		var image = $(this).attr("src");
		var name = $(this).attr("name");
		var resize = $(this).attr("data-resize").split("x");
		
		$(this).css("display","none");
		
		imre_body = document.createElement('div');
		$(imre_body).css("width",resize[0]);
		$(imre_body).css("height",resize[1]);
		imre_body.setAttribute("parent",i);
		$(imre_body).css("background","url("+image+")");	
		$(imre_body).addClass("image_resize_b");
		
		$(this).after(imre_body);
		/*
		image_resize_body = document.createElement('div');
		image_resize_body.setAttribute("id","image_resize_div_"+i);
		$(image_resize_body).css("width",size[0]);
		$(image_resize_body).css("height",size[1]);
		$(image_resize_body).css("background","url("+image+") center center no-repeat black");		
		si1="auto";	if($(this).outerWidth(true)>$(image_resize_body).outerWidth(true)){si1="100%";}
		si2="auto";	if($(this).outerHeight(true)>$(image_resize_body).outerHeight(true)){si2="100%";}
		$(image_resize_body).css("background-size",si1+" "+si2);
		$(image_resize_body).addClass("image_resize");
		
		image_resize_resizer_border_image_black = document.createElement('div');	
		$(image_resize_resizer_border_image_black).addClass("image_resize_border_image_black");
		$(image_resize_body).append(image_resize_resizer_border_image_black);
		
		image_resize_resizer_border = document.createElement('div');
		image_resize_resizer_border.setAttribute("parent",i);
		$(image_resize_resizer_border).css("width",resize[0]);
		$(image_resize_resizer_border).css("height",resize[1]);
		$(image_resize_resizer_border).addClass("image_resize_border");
		
		psl = ($(image_resize_body).outerWidth(true)/2)-($(image_resize_resizer_border).outerWidth(true)/2);
		pst = ($(image_resize_body).outerHeight(true)/2)-($(image_resize_resizer_border).outerHeight(true)/2);
		
		$(image_resize_resizer_border).css("left",psl);
		$(image_resize_resizer_border).css("top",pst);		
		$(image_resize_body).append(image_resize_resizer_border);
		
		ws=$(image_resize_body).outerWidth(true);
		hs=$(image_resize_body).outerHeight(true);		
		kl=0;
		kt=0;
		if($(this).outerWidth(true)<=$(image_resize_body).outerWidth(true)){ws = $(this).outerWidth(true);kl=(($(image_resize_body).outerWidth(true)-ws)/2);}
		if($(this).outerHeight(true)<=$(image_resize_body).outerHeight(true)){hs = $(this).outerHeight(true);kt=(($(image_resize_body).outerHeight(true)-hs)/2);}
		
		resizer_klt[i] = new Array(kl,kt);

		image_resize_resizer_border_image_crop = document.createElement('div');	
		$(image_resize_resizer_border_image_crop).css("z-index",50);
		$(image_resize_resizer_border_image_crop).css("width",$(image_resize_resizer_border).outerWidth(true)-2);
		$(image_resize_resizer_border_image_crop).css("height",$(image_resize_resizer_border).outerWidth(true)-2);
		$(image_resize_resizer_border_image_crop).addClass("image_resize_border_image");
		$(image_resize_resizer_border_image_crop).css("background-image","url("+image+")");
		$(image_resize_resizer_border_image_crop).css("background-size",ws+"px "+hs+"px");	
		$(image_resize_resizer_border_image_crop).css("background-position","-"+(psl-kl+1)+" -"+(pst-kt+1));
		$(image_resize_resizer_border).append(image_resize_resizer_border_image_crop);
		
		$(image_resize_resizer_border).draggable({ containment: "parent" });
		$(image_resize_resizer_border).bind('drag',function( event ){
			var iom = $(this).find(".image_resize_border_image")[0];
			var par = $(this).attr("parent");
			var bod = $("image_resize_div_"+$(this).attr("parent"));
			kl = resizer_klt[par][0];
			kt = resizer_klt[par][1];
			psl = $(this).offset().left;
			pst = $(this).offset().top;
			console.log(psl-kl);
			$(iom).css("background-position",((psl-kl+1)*-1)+" "+((pst-kt+1)*-1));
		});
		$(this).after(image_resize_body);
		*/
	}
);	

$("input[type=\"toggle_swipe\"]").each(function(i)
	{
		swpier_toggle_id[i] = $(this);
		swpier_toggle_id[i].css("display","none");
		var state = $(this).attr("value"); if(state == undefined){state=0;}
		var stav = $(this).attr("data-state"); if(typeof $(this).attr("data-state") == "undefined" || $(this).attr("data-state")==""){stav=new Array("ON","OFF");}else{stav = stav.split("|");}
		var disab = $(this).attr("disabled");
		swpier_toggle = document.createElement('a');
		swpier_toggle.setAttribute("id","toggle_swipe_"+i);
		swpier_toggle.setAttribute("parent",i);
		swpier_toggle.setAttribute("href","#toggle_swipe_"+i);
		$(swpier_toggle).addClass("toggle_swipe");
		this.setAttribute("value",state);
		$(swpier_toggle).click(function(){
			var parent = $(this).attr("parent");			
			var state = swpier_toggle_id[parent].attr("value");
			var disab = swpier_toggle_id[parent].attr("disabled");
			if(disab=="disabled"){
				if(state==1){
					$($(swpier_toggle_body[i]).find(".mover")[0]).animate({ 
						left: "-=15px",
					}, 200 );
					$($(swpier_toggle_body[i]).find(".mover")[0]).animate({ 
						left: "+=15px",
					}, 200 );
				}else{
					$($(swpier_toggle_body[i]).find(".mover")[0]).animate({ 
						left: "+=15px",
					}, 200 );
					$($(swpier_toggle_body[i]).find(".mover")[0]).animate({ 
						left: "-=15px",
					}, 200 );
				}
			}else{			
				if(state==1){
					$($(swpier_toggle_body[i]).find(".mover")[0]).animate({ 
						left: "-=34px",
					}, 200 );
				}else{
					$($(swpier_toggle_body[i]).find(".mover")[0]).animate({ 
						left: "+=34px",
					}, 200 );
				}
				swpier_toggle_id[parent].attr("value",(state==0?1:0));
			}
			return false;
		});
		
		swpier_toggle_body[i] = document.createElement('span'); // tělo switche
		$(swpier_toggle_body[i]).addClass("body");
		
		swpier_toggle_body_zap = document.createElement('div'); //pozadí zap
		$(swpier_toggle_body_zap).addClass("zap");
		$(swpier_toggle_body_zap).html(stav[0]);
		$(swpier_toggle_body[i]).append(swpier_toggle_body_zap);
		
		swpier_toggle_body_vyp = document.createElement('div'); //pozadí vyp
		$(swpier_toggle_body_vyp).addClass("vyp");
		$(swpier_toggle_body_vyp).html(stav[1]);
		$(swpier_toggle_body[i]).append(swpier_toggle_body_vyp);
		
		swpier_toggle_body_switch = document.createElement('div'); //přepínač
		$(swpier_toggle_body_switch).addClass("mover");
		if(disab=="disabled"){ $(swpier_toggle_body_switch).addClass("disabled"); }
		if(state==1){
			$(swpier_toggle_body_switch).css("left",75-1-39);	
			$(swpier_toggle_body_switch).css("top",1);	
		}else{
			$(swpier_toggle_body_switch).css("left",1);	
			$(swpier_toggle_body_switch).css("top",1);	
		}
		$(swpier_toggle_body[i]).append(swpier_toggle_body_switch);
		
		$(swpier_toggle).append(swpier_toggle_body[i]);
		
		$(this).after(swpier_toggle);
	}
);	