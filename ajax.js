  function createAjax()
  {
    var ajaxItem = false;
    if (window.XMLHttpRequest) 
    {
      ajaxItem = new XMLHttpRequest()
    }
    else if (window.ActiveXObject)
    {
      try 
      {
        ajaxItem = new ActiveXObject("Msxml2.XMLHTTP")
      }
      catch (e)
      {
        try
        {
          ajaxItem = new ActiveXObject("Microsoft.XMLHTTP")
        }
        catch (e)
        {
        }
      }
    }
    return ajaxItem;
  }
  
  
  function urlencode( str )
  {
    var histogram = {}, tmp_arr = [];
    var ret = str.toString();
    
    var replacer = function(search, replace, str) {
      var tmp_arr = [];
      tmp_arr = str.split(search);
      return tmp_arr.join(replace);
    };
    
    histogram["'"]   = '%27';
    histogram['(']   = '%28';
    histogram[')']   = '%29';
    histogram['*']   = '%2A';
    histogram['~']   = '%7E';
    histogram['!']   = '%21';
    histogram['%20'] = '+';
    
    ret = encodeURIComponent(ret);
    
    for (search in histogram) {
      replace = histogram[search];
      ret = replacer(search, replace, ret)
    }
    
    return ret.replace(/(\%([a-z0-9]{2}))/g, function(full, m1, m2) {
      return "%"+m2.toUpperCase();
    });
    
    return ret;
  }
  
  
  function urldecode( str )
  {
    var histogram = {}, tmp_arr = [];
    var ret = str.toString();
	
    var replacer = function(search, replace, str) {
      var tmp_arr = [];
      tmp_arr = str.split(search);
      return tmp_arr.join(replace);
    };
    
    histogram['%27']   = "'";
    histogram['%28']   = '(';
    histogram['%29']   = ')';
    histogram['%2A']   = '*';
    histogram['%7E']   = '~';
    histogram['%21']   = '!';
    histogram['+'] = '%20';
	
	for (search in histogram) {
      replace = histogram[search];
      ret = replacer(search, replace, ret)
    }
    
    ret = decodeURIComponent(ret);
	
    return ret;
  }
  
  
  function updateItem(itemId,itemValue,nameId,item)
  {
    var objetoAjax= createAjax();
    var valor=itemValue;
    
    if(itemId!=0 && itemValue)
    {
    	valor=1;
    }
    else if(itemId!=0)
    {
    	valor=0;
    }
    
    var updateUrl='./aj_mchar.php?act=upd&nameId='+nameId+'&itemId='+itemId+'&itemValue='+urlencode(valor)+"&IEfix="+new Date().getTime();
    item.disabled=true;
    document.getElementById("tempdiv").innerHTML=updateUrl;
    objetoAjax.onreadystatechange=function()
    {
       updateFinished(objetoAjax,item,nameId,itemId,valor);
    }
    objetoAjax.open('GET', updateUrl, true);
    objetoAjax.send(null); 
  } 
  
  function updateFinished(objetoAjax,objeto,nameId,itemId,valor)
  {
    if(objetoAjax.readyState == 4)
    {
	  var res=document.getElementById('resp');
      if(objetoAjax.responseText=='1')
      {
		var ftype;
		switch(itemId)
		{
		  case 0: ftype="comment"; break;
		  case 1: ftype="issec"; break;
		  case 2: ftype="explimit"; break;
		  case 3: ftype="nokill"; break;
		  case 4: ftype="killHP"; break;
		  case 5: ftype="blacklist"; break;
		  default: ftype="#ERROR#"; break;
		}
		res.innerHTML="<span class='response1'>OK: "+urldecode(nameId)+"'s "+ftype+"="+valor+".</span>";
        objeto.disabled=false;
      }
	  else if(objetoAjax.responseText=='2')
	  {
	    res.innerHTML="";
		objeto.disabled=false;
	  }
      else
      {
        res.innerHTML="<span class='response2'>ERROR: Couldn't update "+urldecode(nameId)+".</span>";
        objeto.disabled=false;
	 }
    }
  }

  
  function insertNew(item)
  {
	item.disabled=true;
    var objetoAjax= createAjax();
    
    var name=document.getElementById("addName").value;
    var explimit=(document.getElementById("addExpLimit").checked)?1:0;
    var secChar=(document.getElementById("addSecchar").checked)?1:0;
    var dontKill=(document.getElementById("addNokill").checked)?1:0;
    var killHP=(document.getElementById("addKillhp").checked)?1:0;
    var blackList=(document.getElementById("addBlacklist").checked)?1:0;
    var comment=document.getElementById("addComment").value;
    
    var insertUrl='./aj_mchar.php?act=ins&nameId='+urlencode(name)+
              '&explimit='+explimit+'&secChar='+secChar+'&dontKill='+dontKill+
              '&killHP='+killHP+'&blackList='+blackList+'&comment='+urlencode(comment)+
			  '&IEfix='+new Date().getTime();

    document.getElementById("tempdiv").innerHTML=insertUrl;
    objetoAjax.onreadystatechange=function()
    {
       insertFinished(objetoAjax,item,name);
    }
    objetoAjax.open('GET', insertUrl, true);
    objetoAjax.send(null); 
  } 
  
  function insertFinished(objetoAjax,item,nameId)
  {
    if(objetoAjax.readyState == 4)
    {
	  item.disabled=false;
	  var x=objetoAjax.responseText.match(/.$/g);
	  var res=document.getElementById('resp');
	  if(x=='1')
      {
		res.innerHTML="<span class='response1'>OK: "+urldecode(nameId)+" added to the wartool.</span>";
	  }
	  else if(x=='2')
	  {
	    res.innerHTML="<span class='response1'>OK: "+urldecode(nameId)+" updated.</span>";
	  }
	  else
	  {
		alert(objetoAjax.responseText);
		res.innerHTML="<span class='response2'>ERROR: Failed to add "+urldecode(nameId)+" to the wartool.</span>";
	  }
    }
  }

  
  function deleteItem(nameId, item)
  {
    if(!confirm("Are you sure you want to delete "+urldecode(nameId)+"?"))
    {
      return;
    }
    
    var objetoAjax= createAjax();
    
    var deleteUrl='./aj_mchar.php?act=del&nameId='+nameId+"&IEfix="+new Date().getTime();

    document.getElementById("tempdiv").innerHTML=deleteUrl;
    objetoAjax.onreadystatechange=function()
    {
       deleteFinished(objetoAjax,item,nameId);
    }
    
    objetoAjax.open('GET', deleteUrl, true);
    objetoAjax.send(null); 
  }
  
  function deleteFinished(objetoAjax,item,nameId)
  {
     
    if(objetoAjax.readyState == 4)
    {
      var res=document.getElementById('resp');  
      if(objetoAjax.responseText=='1') 
      {
        item.parentNode.parentNode.parentNode.removeChild(item.parentNode.parentNode);
		res.innerHTML="<span class='response1'>OK: Deleted "+urldecode(nameId)+" from the wartool.</span>";
      }
      else 
      {
      	res.innerHTML="<span class='response2'>ERROR: Failed to delete "+urldecode(nameId)+" from the wartool.</span>";
      }
    }
  }

  
  function resalta(linea)
  {
    linea.style.background='rgb(163,228,192)';
  }
  
  function apaga(linea)
  {
    linea.style.background='#DCDBDD';
  }
  
  
  function hideChar(nameId,elem)
  {
    var ajaxObj = createAjax();
    var target = './aj_mlist.php?act=hide&name='+nameId+"&IEfix="+new Date().getTime();
    
    ajaxObj.onreadystatechange=function()
    {
      hideCharFinished(ajaxObj,nameId,elem);
    }
    
    ajaxObj.open('GET',target,true);
    ajaxObj.send(null);
  }
  
  function hideCharFinished(ajaxObj,nameId,elem)
  {
	if(ajaxObj.readyState == 4)
	{
	  var res=document.getElementById('resp');
	  if(ajaxObj.responseText == '1')
	  {
		res.innerHTML = "<span class='response1'>OK: "+urldecode(nameId)+" has been hidden.</span>";
	    elem.parentNode.parentNode.parentNode.removeChild(elem.parentNode.parentNode);
	  }
	  else
	  {
	    res.innerHTML = "<span class='response2'>ERROR: Failed to hide "+urldecode(nameId)+".</span>";
	  }
	}
  }
  
  
  function unhideChar(nameId,elem)
  {
    var ajaxObj = createAjax();
    var target = './aj_mlist.php?act=unhide&name='+nameId+"&IEfix="+new Date().getTime();
    
    ajaxObj.onreadystatechange=function()
    {
      unhideCharFinished(ajaxObj,nameId,elem);
    }
    
    ajaxObj.open('GET',target,true);
    ajaxObj.send(null);
  }
  
  function unhideCharFinished(ajaxObj,nameId,elem)
  {
	if(ajaxObj.readyState == 4)
	{
	  var res=document.getElementById('resp');
	  if(ajaxObj.responseText == '1')
	  {
		res.innerHTML = "<span class='response1'>OK: "+urldecode(nameId)+" has been unhidden.</span>";
	    elem.parentNode.parentNode.parentNode.removeChild(elem.parentNode.parentNode);
	  }
	  else
	  {
	    res.innerHTML = "<span class='response2'>ERROR: Failed to unhide "+urldecode(nameId)+".</span>";
	  }
	}
  }
  
  
  function changeTag(nameId,typeId,elem,offs)
  {
    var ajaxObj = createAjax();
    var target = './aj_mlist.php?act=tag&name='+nameId+'&newTag='+typeId+"&IEfix="+new Date().getTime();
    
    ajaxObj.onreadystatechange=function()
    {
      changeTagFinished(ajaxObj,nameId,typeId,elem,offs);
    }
    
    ajaxObj.open('GET',target,true);
    ajaxObj.send(null);
  }
  
  function changeTagFinished(ajaxObj,nameId,typeId,elem,offs)
  {
	if(ajaxObj.readyState == 4)
	{
	  var res=document.getElementById('resp');
	  if(ajaxObj.responseText == '1')
	  {
	    var type;
		var img="<img src='./img/mag_";
		switch(typeId)
		{
			case 1: img=img+"np.gif' alt='harmless'>"; type="harmless"; break;
			case 2: img=img+"warn.gif' alt='potential threat'>"; type="threat"; break;
			case 3: img=img+"lock.gif' alt='exp limited'>"; type="exp limited"; break;
			case 4: img=img+"enem.gif' alt='enemy'>"; type="enemy"; break;
			case 5: img=img+"ally.gif' alt='ally'>"; type="ally"; break;
			default: img=img+"unk.gif' alt='unknown'>"; type="unknown"; break;
		}
		res.innerHTML = "<span class='response1'>OK: Changed "+urldecode(nameId)+"'s tag to "+type+".</span>";
		elem.parentNode.parentNode.childNodes[offs].innerHTML=img;
	  }
	  else
	  {
	    res.innerHTML = "<span class='response2'>ERROR: Failed to change "+urldecode(nameId)+"'s tag.</span>";
	  }
	}
  }
  
  
  function showPanel(elem)
  {
	e=elem.parentNode.childNodes[1];
	e.style.display=(e.style.display=='none')?'block':'none';
  }
  
  function showComment(elem)
  {
	e=elem.parentNode.childNodes[4];
	e.style.display=(e.style.display=='none')?'block':'none';
  }
  
  
  function addReq(typeId,elem)
  {
	elem.disabled=true;
	var n = elem.parentNode.childNodes[1].value;
	var c = elem.parentNode.childNodes[3].value;
	var ajaxObj = createAjax();
    var target = './aj_addc.php?act=add&name='+urlencode(n)+"&type="+typeId+"&comment="+urlencode(c)+"&IEfix="+new Date().getTime();
	
    ajaxObj.onreadystatechange=function()
    {
      addReqFinished(ajaxObj,typeId,elem);
    }
	ajaxObj.open('GET',target,true);
    ajaxObj.send(null);
  }
  
  function addReqFinished(ajaxObj,typeId,elem)
  {
    if(ajaxObj.readyState == 4)
	{
	  elem.disabled=false;
	  var res = document.getElementById('resp');
	  if(ajaxObj.responseText == '0')
	  {
	    res.innerHTML="<span class='response2'>ERROR: Access denied.</span>";
	  }
	  else if(ajaxObj.responseText == '1')
	  {
	    res.innerHTML="<span class='response2'>ERROR: Invalid name.</span>";
	  }
	  else if(ajaxObj.responseText == '2')
	  {
	    if(typeId==0)
	    res.innerHTML="<span class='response2'>ERROR: Char is on the wartool already.</span>";
	    else
		 res.innerHTML="<span class='response2'>ERROR: Char is not on the wartool.</span>";
	  }
	  else if(ajaxObj.responseText == '3')
	  {
	    res.innerHTML="<span class='response2'>ERROR: Char has pending requests already.</span>";
	  }
	  else if(ajaxObj.responseText == '4')
	  {
	    res.innerHTML="<span class='response2'>ERROR: Char does not exist (can be .com's fault).</span>";
	  }
	  else if(ajaxObj.responseText == '5')
	  {
	    res.innerHTML="<span class='response2'>ERROR: MySQL Error.</span>";
	  }
	  else if(ajaxObj.responseText == '6')
	  {
	    res.innerHTML="<span class='response2'>ERROR: Failed to add request.</span>";
	  }
	  else
	  {
	    res.innerHTML="<span class='response1'>OK: Request added.</span>";
	    var t1 = document.createElement("tr");
	    var t2 = document.createElement("td");
		
	    t1.align = 'left';
	    elem.parentNode.parentNode.parentNode.appendChild(t1).appendChild(t2).innerHTML = ajaxObj.responseText;
	    elem.parentNode.childNodes[1].value='';
	    elem.parentNode.childNodes[3].value='';
	  }
	}
  }
  
  
  function processReq(nameId,type,isSec,addType,elem)
  {
    var conf = "Add "+urldecode(nameId)+" as ";
	if(isSec==1) conf = conf+"second char,"; else conf = conf+"main char,";
    switch(addType) {
	case 0: conf=conf+'default'; break;
	case 1: conf=conf+'nokill'; break;
	case 2: conf=conf+'killhp'; break;
	case 3: conf=conf+'blacklist'; break;
	case 4: conf=conf+'explimit'; break;
	default: addType=0; conf=conf+'default'; break;
	}
	conf=conf+"?"
	if (confirm(conf))
	{
	  var ajaxObj = createAjax();
      var target = './aj_addc.php?act=process&name='+nameId+'&reqType='+type+'&issec='+isSec+"&addType="+addType+"&IEfix="+new Date().getTime();
	
      ajaxObj.onreadystatechange=function()
      {
        processReqFinished(ajaxObj,nameId,type,elem);
      }
    
      ajaxObj.open('GET',target,true);
      ajaxObj.send(null);
	}
  }
  
  function processReqFinished(ajaxObj,nameId,type,elem)
  {
    if(ajaxObj.readyState == 4)
	{
	  var res = document.getElementById('resp');
	  
	  if(ajaxObj.responseText == '0')
	  {
	    res.innerHTML="<span class='response2'>ERROR: Access denied.</span>";
	  }
	  else if(ajaxObj.responseText == '1')
	  {
	    res.innerHTML="<span class='response2'>ERROR: Char has no pending requests.</span>";
	  }
	  else if(ajaxObj.responseText == '2')
	  {
	    res.innerHTML="<span class='response2'>ERROR: MySQL Error retrieving comment.</span>";
	  }
	  else if(ajaxObj.responseText == '3')
	  {
	    res.innerHTML="<span class='response2'>ERROR: MySQL UPDATE Error.</span>";
	  }
	  else if(ajaxObj.responseText == '4')
	  {
	    res.innerHTML="<span class='response2'>ERROR: MySQL Error, affectedRows=0.</span>";
	  }
	  else if(ajaxObj.responseText == '5')
	  {
	    res.innerHTML="<span class='response2'>ERROR: Char doesn't exist.</span>";
	  }
	  else if(ajaxObj.responseText == '6')
	  {
	    res.innerHTML="<span class='response2'>ERROR: MySQL INSERT Error.</span>";
	  }
	  else if(ajaxObj.responseText == '7')
	  {
	    res.innerHTML="<span class='response2'>ERROR: MySQL Error, affectedRows=0.</span>";
	  }
	  else if(ajaxObj.responseText == 'OK')
	  {
	    res.innerHTML="<span class='response1'>OK: Request processed ("+urldecode(nameId)+").</span>";
	    elem.parentNode.parentNode.parentNode.parentNode.removeChild(elem.parentNode.parentNode.parentNode);
	  }
	  else
	  {
	    alert("ERROR: "+ajaxObj.responseText);
	  }
	}
  }
  
  
  function commentReq(nameId,typeId,elem)
  {
    elem.disabled=true;
    var c = elem.parentNode.childNodes[0].value;
	var ajaxObj = createAjax();
	var target = "./aj_addc.php?act=comment&name="+nameId+"&type="+typeId+"&comment="+urlencode(c)+"&IEfix="+new Date().getTime();
	
	ajaxObj.onreadystatechange=function()
    {
      commentReqFinished(ajaxObj,elem);
    }
	
	ajaxObj.open('GET',target,true);
    ajaxObj.send(null);
  }
  
  function commentReqFinished(ajaxObj,elem)
  {
    if(ajaxObj.readyState == 4)
	{
	  elem.disabled=false;
	  var res = document.getElementById('resp');
	  if(ajaxObj.responseText == '0')
	  {
	    res.innerHTML="<span class='response2'>ERROR: Access denied.</span>";
	  }
	  else if(ajaxObj.responseText == '1')
	  {
	    res.innerHTML="<span class='response2'>ERROR: Invalid request.</span>";
	  }
	  else if(ajaxObj.responseText == '2')
	  {
	    res.innerHTML="<span class='response2'>ERROR: Comment is empty.</span>";
	  }
	  else if(ajaxObj.responseText == '3')
	  {
	    res.innerHTML="<span class='response2'>ERROR: MySQL Error.</span>";
	  }
	  else if(ajaxObj.responseText == '4')
	  {
	    res.innerHTML="<span class='response2'>ERROR: Failed to add comment.</span>";
	  }
	  else
	  {
	    var tmp = elem.parentNode.parentNode.childNodes[2].innerHTML;
		tmp = tmp + ajaxObj.responseText;
		elem.parentNode.parentNode.childNodes[2].innerHTML=tmp;
	    res.innerHTML="<span class='response1'>OK: Comment posted.</span>";
		elem.parentNode.childNodes[0].value='';
		elem.parentNode.style.display='none';
		
	  }
	}
  }
  
  
  function declineReq(nameId,elem)
  {
    var text = "Decline and delete all requests for "+urldecode(nameId)+"?";
	if(confirm(text))
	{
      var target = "./aj_addc.php?act=decline&name="+nameId+"&IEfix="+new Date().getTime();
	  var ajaxObj = createAjax();
	  ajaxObj.onreadystatechange=function()
      {
        declineReqFinished(ajaxObj,elem);
      }
	
	  ajaxObj.open('GET',target,true);
      ajaxObj.send(null);
	}
  }
  
  function declineReqFinished(ajaxObj,elem)
  {
    if(ajaxObj.readyState == 4)
	{
	  var res = document.getElementById('resp');
	  if(ajaxObj.responseText == '0')
	  {
	    res.innerHTML="<span class='response2'>ERROR: Access denied.</span>";
	  }
	  else if(ajaxObj.responseText == '1')
	  {
	    res.innerHTML="<span class='response2'>ERROR: MySQL Error.</span>";
	  }
	  else
	  {
	    res.innerHTML="<span class='response1'>OK: Request declined and deleted.</span>";
		elem.parentNode.parentNode.parentNode.parentNode.removeChild(elem.parentNode.parentNode.parentNode);
	  }
	}
  }
  
  
  function forceUpdate(type)
  {
    if(gBusy==0)
	{
	  gBusy=1;
	  var res = document.getElementById('resp');
	  var res2 = document.getElementById('result');
	  res.innerHTML="";
	  res2.innerHTML="<img src='./img/loading.gif'>";
	  var target = "./aj_updt.php?type="+type+"&IEfix="+new Date().getTime();
	  var ajaxObj = createAjax();
	  ajaxObj.onreadystatechange=function()
      {
        forceUpdateFinished(ajaxObj,res,res2);
      }
	
	  ajaxObj.open('GET',target,true);
      ajaxObj.send(null);
	}
    else
	{
	  var res = document.getElementById('resp');
	  res.innerHTML = "<span class='response2'>ERROR: Wait for previous update to finish.</span>";
	}
	
  }
  
  function forceUpdateFinished(ajaxObj,res,res2)
  {
    if(ajaxObj.readyState == 4)
	{
	  gBusy=0;
	  res.innerHTML = "<span class='response1'>OK: Update request finished.</span>"
	  res2.innerHTML = (ajaxObj.responseText);
	}
  }
  
  function toggleRdy(par,e,name)
  {
    var ajaxObj = createAjax();
	var target = "./aj_myacc.php?act=rdy&name="+name+"&par="+par+"&IEfix="+new Date().getTime();
	ajaxObj.onreadystatechange=function()
    {
      toggleRdyFinished(ajaxObj,par,name,e);
    }
	ajaxObj.open('GET',target,true);
    ajaxObj.send(null);
  }
  
  function toggleRdyFinished(ajaxObj,par,name,e)
  {
    if(ajaxObj.readyState == 4)
	{
	  var res = document.getElementById('resp');
	  if(ajaxObj.responseText == "OK")
	  {
        if(par==1)
	    {
		  res.innerHTML = "<span class='response1'>OK: "+urldecode(name)+"'s status set to ready.</span>";
	      e.parentElement.innerHTML="<img src='./img/stat_norm.gif' onclick='toggleRdy(0,this,\""+name+"\");'>";
	    }
        else
	    {
		  res.innerHTML = "<span class='response1'>OK: "+urldecode(name)+"'s status set to NOT ready.</span>";
	      e.parentElement.innerHTML="<img src='./img/stat_na.gif' onclick='toggleRdy(1,this,\""+name+"\");'>";
	    }
      }
	  else res.innerHTML = "<span class='response2'>ERROR: Failed to change "+urldecode(name)+"'s ready state.</span>";
	}
  }
  
  function delOwn(name,e)
  {
    var text = "Are you sure you want to delete "+urldecode(name)+"?";
	if(confirm(text))
	{
      var ajaxObj = createAjax();
	  var target = "./aj_myacc.php?act=del&name="+name+"&IEfix="+new Date().getTime();
	  ajaxObj.onreadystatechange=function()
      {
        delOwnFinished(ajaxObj,name,e);
      }
	  ajaxObj.open('GET',target,true);
      ajaxObj.send(null);
	}
  }
  
  function delOwnFinished(ajaxObj,name,e)
  {
    if(ajaxObj.readyState == 4)
	{
	  var res = document.getElementById('resp');
	  if(ajaxObj.responseText == 'OK')
	  {
	    res.innerHTML = "<span class='response1'>OK: "+urldecode(name)+" is not your character anymore.</span>";
	    e.parentNode.parentNode.parentNode.removeChild(e.parentNode.parentNode);
	  }
	  else
	  {
	    res.innerHTML = "<span class='response2'>ERROR: Failed to delete "+urldecode(name)+".</span>";
	  }
	}
  }