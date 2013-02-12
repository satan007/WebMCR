/* WEB-APP : WebMCR (С) 2013 NC22 */

var iframe;

/* Prototypes */

Date.prototype.getLocaleFormat = function(format) {
	var f = {y : this.getYear() + 1900,m : this.getMonth() + 1,d : this.getDate(),H : this.getHours(),M : this.getMinutes(),S : this.getSeconds()}
	for(k in f)
		format = format.replace('%' + k, f[k] < 10 ? "0" + f[k] : f[k]);
	return format;
};

String.prototype.replaceAll = function(search, replace){
  return this.split(search).join(replace);
}

/* Math */

function rand(min, max) { return Math.floor(Math.random() * (max - min + 1)) + min; }

/* Ajax */

function getJSvalue(value) {
var result = false;
//alert(value)
if (typeof value != "string") { 
	
	alert('[getJSvalue] Value is not string : '+value)
	return result
}

	try {
	
	result = window.JSON && window.JSON.parse ? JSON.parse(value) : eval('('+value+')')
	
	} catch (E) {

	alert('[getJSvalue] Incorect server response : '+value)
	
	}
	
return result
}

function getXmlHttp(){
  var xmlhttp;  
  
  if (!xmlhttp && typeof XMLHttpRequest != 'undefined') 
    xmlhttp = new XMLHttpRequest();
  
  else {
  
  	  try {
		xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
	  } catch (e) {
	  
		try {
		  xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		} catch (E) {
		  xmlhttp = false;
		}
		
	  }
	  
  }
  return xmlhttp;
}

/* DOM Helpers */

function GetScrollTop() {
	return (document.documentElement && document.documentElement.scrollTop) || document.body.scrollTop
}

function GetParent(elem, type){ 
var parent = elem.parentNode

if (parent && parent.tagName != type) parent = GetParentForm(parent)

return parent;
}

function getByClass(className,tag) {
	var LinkList   = document.getElementsByTagName(tag)
	var foundList = []

	for (i=0; i<=LinkList.length-1; ++i) 
		if (LinkList[i].className == className) foundList[foundList.length] = LinkList[i]
		
	return foundList
}

function addSubmitEvent(buttonId,formId) {
	var tmp = document.getElementById(buttonId) 
	
	if (tmp != null) tmp.onclick = function(){
		document.getElementById(formId).submit()

        return false		
	}
	
}

function BlockVisible(itemID,state) {

	var item = document.getElementById(itemID)
	if (item == null) return false

	if (state == null) {
	
		if (item.style.display == 'block') item.style.display = 'none'
		else item.style.display = 'block'
		
		return true
	}
	
	var styleText = 'block'

	if (state == false)  styleText = 'none'
	
	item.style.display = styleText
	
	return true	
}

function nl2br (str, is_xhtml) {

  var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br ' + '/>' : '<br>'; 

  return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
}

function toggleButton(id) {

var el = document.getElementById(id)
if (el == null) return false

  el.disabled = !el.disabled
  return true
}

function getValById(id) {

var el = document.getElementById(id)
if (el == null || el.value == null) return null
else return el.value

}

function getIframeDocument(iframeNode) {

	  if (iframeNode.contentDocument) return iframeNode.contentDocument
	  if (iframeNode.contentWindow) return iframeNode.contentWindow.document
	  return iframeNode.document
}

function IframeOnLoadEvent(iframeNode,event) {

	if (iframeNode.attachEvent) iframeNode.attachEvent('onload', event)
	else if (iframe.addEventListener) iframeNode.addEventListener('load', event, false)
	else iframeNode.onload = event
}

function clearFileInputField(Id) { 

    var clear = document.getElementById(Id)
	
	if (clear != null ) 
        clear.innerHTML = document.getElementById(Id).innerHTML; 
}

function getClientW() {
  return document.compatMode=='CSS1Compat' && document.documentElement.clientWidth;
}

function getClientH() {
  return document.compatMode=='CSS1Compat' && document.documentElement.clientHeight;
}

/* Date Time */

function parseDate(input) {
  format = 'yyyy-mm-dd hh:MM:ss'; // default format
  var parts = input.match(/(\d+)/g), 
      i = 0, fmt = {};
	  
  // extract date-part indexes from the format
  format.replace(/(yyyy|dd|mm|hh|MM|ss)/g, function(part) { fmt[part] = i++; });

  return new Date(parts[fmt['yyyy']], parts[fmt['mm']]-1, parts[fmt['dd']], parts[fmt['hh']], parts[fmt['MM']], parts[fmt['ss']]);
}

function timeFrom(date) {

  var str = ''
  var now = new Date()
  var daysTo = (now-date) / 1000 / 60 / 60 / 24
  if (daysTo > 0) str = Math.floor(daysTo) + 'д.'
  
    hours = now.getHours() - date.getHours() 
	if (hours < 0) hours = hours *-1
	minutes = now.getMinutes() - date.getMinutes()   
	if (minutes < 0) minutes = minutes *-1  
	
  return str = str + " " + hours  + " ч. " + minutes + " мин. "	 
}

function debug(string){
document.getElementById('debug').innerHTML += string
}