/* WEB-APP : WebMCR (С) 2013 NC22 */

var servers_stack = new Array(); 

function LoadServersProc(){

    if (servers_stack.length == 0 ) return

	var item_id   = servers_stack.pop()
    var server_id = parseInt((/\d+/).exec(item_id)[0])
		
		var req = getXmlHttp();	req.onreadystatechange = function() {
		
			if (req.readyState != 4 || 
			   (req.status != 200 && req.status != 0) || 
			   (req.status == 0 && req.responseText.length == 0)) return false
			   
			var response = getJSvalue(req.responseText); delete req	
			
			
			
			if (response['code'] == 1) { 
                document.getElementById('load-sbyid-' + item_id).innerHTML  = 'Сервер не найден' 
				return false
			}
				
			var ServState = response['online']
			BlockVisible('load-sbyid-' + item_id,false)
				
			if (response['online'] == 1) {				

			if (response['numpl'] > response['slots']) response['numpl'] = response['slots'] 
			if (response['slots'] != -1) 
			document.getElementById('on-in-sbyid-' + item_id).innerHTML = response['numpl'] + ' / ' + response['slots']
				
			BlockVisible('on-sbyid-' + item_id,true)
			pbm.AddById('on-in-sbyid-' + item_id, true, 0, Math.round((response['numpl']/response['slots'])*100)) 

			} else BlockVisible('off-sbyid-' + item_id,true)
				
			if (response['pl_array'].length != 0 && response['numpl'] > 0 ) {
				
				var players_block = document.getElementById('users-sbyid-' + item_id)				
				if (players_block != null) {						
						
					/* Формирование списка игроков */
					
					var players_arr = response['pl_array'] .split(",")
					
					var formatStr = '<b>' + players_arr[0] + '</b>'
					for (i=1; i<=players_arr.length-1; ++i) formatStr += ', <b>' + players_arr[i] + '</b>'
				
					document.getElementById('users-sbyid-' + item_id).innerHTML  = formatStr
						
					BlockVisible('uholder-sbyid-' + item_id,true)
				}
			}		
		}

		req.open('POST', '/instruments/state.php', true)  
		req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded')
		req.send('id=' + encodeURIComponent(server_id))		
}

function LoadServers() {

   var tmp = getByClass('server-info','DIV')
   
   for (i=0; i<=tmp.length-1; ++i) {
   
     var id = tmp[i].id.split("-")[1] 
	 if (!id) continue

	 servers_stack.push(id)
     LoadServersProc()
   }
   
  return false
}

/* ProgressBarManager v1.1 */

function ProgressBarManager(className,static_text) {
var PBarMinWidth = 0

if (static_text == null) static_text = false; 

var PBars = []

if (className != null) {

var tmpBars = getDivsByClass(className)			    
for (i=0; i<=tmpBars.length-1; ++i) PBars[i] = { bar : tmpBars[i], main : GetParent(tmpBars[i], 'DIV'), static_text: static_text, dead : false }
delete tmpBars
}

	function parseIntZero(value) {
	value = parseInt(value)

	if (isNaN(value)) return 0
	else return value
	}
	
	function getDivsByClass(className) {
	var divList   = document.getElementsByTagName('DIV')
	var foundList = []

	for (i=0; i<=divList.length-1; ++i) 
		if (divList[i].className == className) foundList[foundList.length] = divList[i]
		
	return foundList
	}
	
	function AnimateBar(pid,from,to) {
	
	if (from == null) from = parseIntZero(PBars[pid].bar.style.width)
	if (from < PBarMinWidth) from = PBarMinWidth
	if (to == null || to < PBarMinWidth) to = PBarMinWidth
	
	PBars[pid].bar.style.width = from + '%'
	if (!PBars[pid].static_text) 
	PBars[pid].bar.innerHTML = PBars[pid].bar.style.width
	
	from = from + 2
	
	if (from < to) setTimeout(function(){AnimateBar(pid,from,to)},35)
	else PBars[pid].bar.style.width = to + '%'
	}
	
	function MoveImage(pid) {
	if (PBars[pid].dead || PBars[pid].main == null || PBars[pid].main.style.display == 'none') return
	var cur_x = parseIntZero(PBars[pid].bar.style.backgroundPosition)
	if (cur_x > 1000) cur_x = 1

	PBars[pid].bar.style.backgroundPosition = (cur_x + 1) + 'px'

	setTimeout(function(){MoveImage(pid)},35)
	}
	
return {
			Live: function(from,to) {
				for (i=0; i<=PBars.length-1; ++i) {
					AnimateBar(i,from,to)
					MoveImage(i)
				}
			},	
			SetMinWidth: function(newWidth) {
				PBarMinWidth = parseIntZero(newWidth)
			},	
			AddById: function(id, static_text, from, to) {
			
				//debug('[AddById] ID ' + id + ' From ' + from + ' To '+ to + '<br/>')
				
				var newBar = document.getElementById(id)
				if (newBar == null) return 
				var newBarKey = PBars.length
				
				PBars[newBarKey] = { bar : newBar, main : GetParent(newBar, 'DIV'), static_text: static_text, dead : false }
				
				AnimateBar(newBarKey, from, to)
				// MoveImage(newBarKey)				
			},	
			StopById: function(id) {
				for (i=0; i<=PBars.length-1; ++i) {
					if (PBars[i].bar.id == id) { PBars[i].dead = true; return } 
				}				
			},				
			SetStaticText: function(id,value) { 
				if (value == null) value = true
				for (i=0; i<=PBars.length-1; ++i) if (PBars[i].bar.id == id) PBars[i].static_text = value
			}
		}
}