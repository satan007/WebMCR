/* WEB-APP : WebMCR (С) 2013 NC22 */

var user_profile_id = -1;
var err404 = 'incorrect address : error : '

function DeleteComment(id) {

	var req = getXmlHttp()

	req.onreadystatechange = function() {
				
	if (req.readyState != 4 || 
		(req.status != 200 && req.status != 0) || 
		(req.status == 0 && req.responseText.length == 0)) return false

               var response = getJSvalue(req.responseText)
               if ( response['code'] == 1 ) return false

			   $('#comment-byid-'+id).fadeOut(200, function (){
			
			    var commentTrash = document.getElementById('comment-byid-'+id)
				
				if ( commentTrash == null ) document.location.reload(true)
				else commentTrash.parentNode.removeChild(commentTrash)

			   });

	}

	req.open('POST', base_url + 'action.php', true)  
	req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded')
	req.send('method=del_com&item_id=' + encodeURIComponent(id))

	return false
}

function PostComment() {

    var addition_post = ''    
    var text          = getValById('comment-add-text')
	var item_id       = getValById('comment-item-id')

	if (text == null || item_id == null || item_id.value <= 0 ) return false

    if (text.length < 1) return false

	var req = getXmlHttp()
		
	var antibot = document.getElementById('antibot')
	if (antibot != null ) {
	
		if (antibot.value.length != 4) return false		
		addition_post = '&antibot=' + encodeURIComponent(antibot.value)	
	}

    toggleButton('comment-button')
	
	req.onreadystatechange = function() {
	
	var messageBoxText = document.getElementById('comment-error-text')
	
			if (req.readyState != 4 || 
			   (req.status != 200 && req.status != 0) || 
			   (req.status == 0 && req.responseText.length == 0)) return false

             var response = getJSvalue(req.responseText)
             var codeId = response['code']


             if ( response['code'] == 0 ) { document.location.reload(true); return false; }
             else {
				 var antibot = document.getElementById('antibot')
				 if (antibot != null ) {
				    antibot.value = ''
					document.getElementById('comment-captcha').src = base_url + 'instruments/captcha/captcha.php?refresh=' + rand(1337,31337) 			 
				 }
			 }
			 
		messageBoxText.innerHTML = response['message']
		BlockVisible('comment-error',true)
        toggleButton('comment-button')			
	}

	req.open('POST', base_url + 'action.php', true)  
	req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded')
	req.send('method=comment&comment=' + encodeURIComponent(text) + '&item_id=' + encodeURIComponent(item_id) + addition_post)
	
	return false
}

function Register() {	

	var login  = getValById('register-login')  
	var pass   = getValById('register-pass')
	var pass2  = getValById('register-repass')
	var email  = getValById('register-email')
	var female = getValById('register-female')
    
    if ( login == null || pass == null || pass2 == null || email == null || female == null) return false
	
	if (pass.length < 1 || pass2.length < 1 || login.length < 1 || email.length < 1 ) return false

	var req = getXmlHttp()
	
    req.onreadystatechange = function() {
	
			if (req.readyState != 4 || 
			   (req.status != 200 && req.status != 0) || 
			   (req.status == 0 && req.responseText.length == 0)) return false

              var response = getJSvalue(req.responseText)

			  document.getElementById('loginform-error-text').className = 'alert alert-error';

              if ( response['code'] == 0 ) { 

                document.getElementById('auth-login').value = login
			    document.getElementById('auth-pass').value  = pass
			    document.getElementById('loginform-error-text').className = 'alert alert-success'
              }
			
	document.getElementById('loginform-error-text').innerHTML = response['message'] 
	BlockVisible('loginform-error',true)		
	}
	
	req.open('POST', base_url + 'register.php', true)  
	req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded')
	req.send('login=' + encodeURIComponent(login) + '&pass=' + encodeURIComponent(pass) + '&repass=' + encodeURIComponent(pass2) + '&email=' + encodeURIComponent(email) + '&female=' + encodeURIComponent(female) )
	
	return false
}

function RestoreStart() {

BlockVisible('reg-box', false)
BlockVisible('login-box', false)
BlockVisible('restore-box', true)

var img_obj =  document.getElementById('antibot-visual') 
var img_src =  base_url + 'instruments/captcha/captcha.php?refresh=' + rand(1337,31337)
if (img_obj == null ) {

	var image = new Image()
	
	image.src = img_src
	
	image.onload = function(){		
		image.id = "antibot-visual"
		image.className = "img-polaroid"
		image.width = 70
		image.height = 30
		document.getElementById('restore-img-holder').appendChild(image)
	}
} else img_obj.src = img_src
}

function Restore() {
	
	var login = getValById('restore-login')
	var email = getValById('restore-email')
	var code  = getValById('antibot')

	if ( login == null || email == null || code == null ) return false

	if (login.length < 1 || email.length < 1 || code.length != 4) return false
	
	var req = getXmlHttp()
	req.onreadystatechange = function() {
	
	var messageBoxText = document.getElementById('loginform-error-text')
	
	if  (req.readyState != 4 || 
		(req.status != 200 && req.status != 0) || 
		(req.status == 0 && req.responseText.length == 0)) return false

          var response = getJSvalue(req.responseText)
        
          if (response['code'] == 0) messageBoxText.className = 'alert alert-success';
 
		  document.getElementById('antibot-visual').src = base_url + 'instruments/captcha/captcha.php?refresh=' + rand(1337,31337)
		
		  messageBoxText.innerHTML = response['message']
		  BlockVisible('loginform-error',true)			
	}

	req.open('POST', base_url + 'action.php', true)  
	req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded')
	req.send('method=restore&login=' + encodeURIComponent(login) + '&email=' + encodeURIComponent(email) + '&antibot=' + encodeURIComponent(code) )
	
	return false
}

function LoadProfile(form,pid) {
    
	if (user_profile_id == pid) {
	BlockVisible(form,true)
	return false
	}
	
	var Ava = new Image()
	    Ava.src = base_url + 'skin.php?user_id='+pid+'&refresh='+rand(1337,31337)
	    Ava.onload = function () {

        var ava_link = Ava.src
 
		document.getElementById(form + '-skin-front').style.backgroundImage = 'url('+ava_link+')'
        document.getElementById(form + '-skin-back').style.backgroundImage = 'url('+ava_link+')'

		var req = getXmlHttp()

			req.onreadystatechange = function() {
		
			if (req.readyState != 4 || 
			   (req.status != 200 && req.status != 0) || 
			   (req.status == 0 && req.responseText.length == 0)) return false
					
				var margin = Math.round(GetScrollTop() + (getClientH()/2) - (224/2))
				
				document.getElementById(form).style.top =  margin +'px' 
						
				var response = getJSvalue(req.responseText)				
				if (response['code'] != 0) return false 
					   
				if (response['female'] == 1) document.getElementById(form + '-female').style.display = 'block'	
                else document.getElementById(form + '-female').style.display = 'none'							 
                            
                document.getElementById(form + '-name').innerHTML = response['name']
                document.getElementById(form + '-group').innerHTML = response['group']
						 
				var date
						 
				if (response['play_last'] != 0) {
					date = new Date(response['play_last']*1000)								
					document.getElementById(form + '-play_last').innerHTML  = timeFrom(date)
				} else document.getElementById(form + '-play_last').innerHTML = 'Никогда'
					
				if (response['create_time'] != 0) {
					date = new Date(response['create_time']*1000)
					document.getElementById(form + '-create_time').innerHTML = date.getLocaleFormat('%H:%M:%S %d.%m.%y')
				} else document.getElementById(form + '-create_time').innerHTML = 'Неизвестно'
						 
				/* 
				 if (response['active_last'] != 0) {
				    date = new Date(response['active_last']*1000)
				    document.getElementById(form + '-active_last').innerHTML = timeFrom(date)
				 }
				*/
						 
                document.getElementById(form + '-comments_num').innerHTML  = response['comments_num']
                document.getElementById(form + '-play_times').innerHTML    = response['play_times']
                document.getElementById(form + '-undress_times').innerHTML = response['undress_times']			

                document.getElementById(form).style.display = 'block'	
                user_profile_id = pid						 					   
			}  
         
		req.open('POST', base_url + 'action.php', true)  
		req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded')
		req.send('method=load_info&id=' + encodeURIComponent(pid))				
	    }

    return false
}

function UpdateProfile(admTrg) {
	
	var mainBody = document.getElementsByTagName('body')[0];
	
    toggleButton('profile-button')
	
    iframe = document.createElement('iframe')
    iframe.name = 'ajax-frame-' + Math.random(1000000)
    iframe.style.display = 'none'

    mainBody.appendChild(iframe)
	
    var form = document.getElementById('profile-update')
		form.target = iframe.name
	
	var event = function () {
   
		var iframeDoc = getIframeDocument(iframe)		
		if (iframeDoc.location.href == 'about:blank') return // Opera "double onload" bug-fix
		
        clearFileInputField('profile-skin-file')
		clearFileInputField('profile-cloak-file')

		var response = getJSvalue(decodeURIComponent(iframeDoc.body.innerHTML))
        document.getElementById('main-error-text').className = 'alert alert-error'		
		
		if (response != null) {	

            if (response['code'] == 0) document.getElementById('main-error-text').className = 'alert alert-success'
			if (response['code'] == 100) { 
			toggleButton('profile-button') 
			BlockVisible('main-error',false)
			return 
			}
			
			document.getElementById('main-error-text').innerHTML = nl2br(response['message'])
			BlockVisible('main-error',true)
		
		} else {

			document.getElementById('main-error-text').innerHTML = err404 + req.status
			BlockVisible('main-error',true)
		
		}
        
		var name = document.getElementById('profile-name')
		if (name != null) name.innerHTML = response['name']
		var group = document.getElementById('profile-group')
		if (group != null) group.innerHTML = response['group']

	    var Ava = new Image()
	        Ava.src = base_url + 'skin.php?user_id=' + response['id'] + '&refresh='+rand(1337,31337)
	        Ava.onload = function () {

                var ava_link = Ava.src
                var skin_front = document.getElementById('profile-skin-front')
				if (skin_front != null ) skin_front.style.backgroundImage = 'url('+ava_link+')'
                var skin_back = document.getElementById('profile-skin-back')
				if (skin_back != null ) skin_back.style.backgroundImage = 'url('+ava_link+')'  
      			
			    toggleButton('profile-button')
            }
		
		if (admTrg == null) {
		
			var Mini = new Image()
				Mini.src =  base_url + 'skin.php?mini=' + response['id'] + '&refresh=' + rand(1337,31337)  
				Mini.onload = function () {
				   document.getElementById('profile-mini').src = Mini.src 
				}	
		}
	
	    var mainBody = document.body
			mainBody.removeChild(iframe)
			
	}
	
	IframeOnLoadEvent(iframe,event)
	
    form.submit()

    return false
}

function Login() {
	
	var login = getValById('auth-login')
	var pass  = getValById('auth-pass')

	if ( login == null || pass == null ) return false
	
	if (login.length < 1 || pass.length < 1) return false
	
	var req = getXmlHttp()
	req.onreadystatechange = function() {
	
	var messageBoxText = document.getElementById('loginform-error-text')
	
	if (req.readyState != 4 || 
	   (req.status != 200 && req.status != 0) || 
	   (req.status == 0 && req.responseText.length == 0)) return false
	   
			var response = getJSvalue(req.responseText)

			if (response['code'] == 0) { document.location.reload(true); return false; }
			
			messageBoxText.innerHTML = response['message']
			BlockVisible('loginform-error',true)			
	}

	req.open('POST', base_url + 'login.php', true)  
	req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded')
	req.send('login=' + encodeURIComponent(login) + '&pass=' + encodeURIComponent(pass))
	
	return false
}