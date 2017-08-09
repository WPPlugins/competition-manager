// setTimeout holder for the loading dots (...)
var compLoading;

function comp_Vote(that, idForm)
{
  var error = '';

  if(document.getElementById('emailAddr_'+idForm).value == '' || !checkEmail(document.getElementById('emailAddr_'+idForm).value))
  {
    error += '<li>Email address incorrect</li>';
  }
  if(document.getElementById('verif_'+idForm).value != '4')
  {
      error += '<li>Verification not passed</li>';
  }
     
	inpts = that.getElementsByTagName('input');
	user_added = false;
	ans = -1;
	theSubmit = false;
	for (i = 0; i < inpts.length; i++)
	{
		cur = inpts[i];
		if (cur.type == 'radio' && cur.checked)
		{
			ans = cur.value;
    }
		if (cur.name == 'competition_id')
			poll_id = cur.value;
			
    if (cur.name == 'comp_cookie_days')
      cdays = cur.value;

		if (cur.type == 'submit')
			theSubmit = cur;

	}	
	
	// they haven't checked a box 
	if (ans == -1 || ans == ''){
	   error += '<li>You need to choose an answer</li>';
  }
  
  if(error != '')
  {
    document.getElementById('errorComp_'+idForm).innerHTML = '<ul>'+error+'</ul>';
    document.getElementById('errorComp_'+idForm).style.display = 'block';
    return false;
  }
  else
  {
    email = document.getElementById('emailAddr_'+idForm).value;
  }
  
  if(document.getElementById('allowContact_'+idForm)!= null)
  {
    if(document.getElementById('allowContact_'+idForm).checked == true)
    {
      allowContact = 1;
    }
    else
    {
      allowContact = 0;
    }
  }
  else
  {
    allowContact = 1;
  }
	compLoading = setTimeout(comp_loadingDots.bind(theSubmit), 50);
  
	path = that.action;
		
	if(user_added)
	{
	   path += "?comp_action=add_answer";
	   path += "&comp_new_answer="+encodeURIComponent(ans);
	   
	   
	}else{
	   path += "?comp_action=vote";
	   path += "&comp_poll_"+poll_id+"="+ans;
  } 
    
	path += "&comp_id="+poll_id;
	path += "&comp_ajax=true";
	path += "&emailAddr="+email;
	path += "&allowContact="+allowContact;
		 
	comp_ajax.open("GET", path, true);
	comp_ajax.onreadystatechange = comp_displayVotes.bind(that);
	comp_ajax.send(null);

	return false;
}

function comp_addUncheck()
{
	oUL = this.parentNode.parentNode;
	lis = oUL.getElementsByTagName('li');
	
	els = lis[lis.length-1].childNodes;
	
	for (i = els.length-1; i >= 0; i--)
		if (els[i].nodeName.toLowerCase() == 'a')
			els[i].style.display = '';
		else
			els[i].parentNode.removeChild(els[i]);
		
			

	Inp = oUL.getElementsByTagName('input');
    for (i = 0; i < Inp.length; i++)
    {
        Inp[i].onclick = function () { return true };
    }

    return true;
}


// very simple ajaxy loading visual
// adds 3 dots to link, then erase and start over
function comp_loadingDots() {
	
	isInput = this.nodeName.toLowerCase() == 'input';
	
	str = (isInput) ? this.value : this.innerHTML;

	if (str.substring(str.length-3) == '...')
		if (isInput)
			this.value     = str.substring(0, str.length-3);
		else
			this.innerHTML = str.substring(0, str.length-3);
	else
		if (isInput)
			this.value     += '.';
		else
			this.innerHTML += '.';
	
	compLoading = setTimeout(comp_loadingDots.bind(this), 200);
}

function comp_clearDots() {
	clearTimeout(compLoading);
}


function comp_getVotes(path, that)
{
	
	that.blur();
	compLoading = setTimeout(comp_loadingDots.bind(that), 50);

	comp_ajax.open("GET", path, true);
	comp_ajax.onreadystatechange = comp_displayVotes.bind(that.parentNode);
	comp_ajax.send(null);

  return false;
}

function comp_displayVotes ()
{
	
	if (comp_ajax.readyState != 4)
		return false;

	if (comp_ajax.status != 200)
	{
		alert('Error '+comp_ajax.status);
		return false;
	}
	//alert(comp_ajax.responseText);
	clearTimeout(compLoading);
	this.innerHTML = comp_ajax.responseText;
}

function comp_getHTTPObject() {
  var xmlhttp;
  /*@cc_on
  @if (@_jscript_version >= 5)
    try {
      xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
    } catch (e) {
      try {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
      } catch (E) {
        xmlhttp = false;
      }
    }
  @else
  xmlhttp = false;
  @end @*/
  if (!xmlhttp && typeof XMLHttpRequest != 'undefined') {
    try {
      xmlhttp = new XMLHttpRequest();
    } catch (e) {
      xmlhttp = false;
    }
  }
  return xmlhttp;
}


comp_ajax = new comp_getHTTPObject();


/*  from prototype.js */
Function.prototype.bind = function() {
  var __method = this, args = $A(arguments), object = args.shift();
  return function() {
    return __method.apply(object, args.concat($A(arguments)));
  }
}

var $A = Array.from = function(iterable) {
  if (!iterable) return [];
  if (iterable.toArray) {
    return iterable.toArray();
  } else {
    var results = [];
    for (var i = 0; i < iterable.length; i++)
      results.push(iterable[i]);
    return results;
  }
}

function checkEmail(email)
{
  if (/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test(email))
  {
    return true;
  }
  else
  {
    return false;
  }
}
