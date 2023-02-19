<?php
session_start();
$session_expired = 20*60; //Set session in seconds (default 20 minutes)
//create random login session
if (!isset($_SESSION["random"])) {
	$random = rand(10,100);
	$_SESSION["session_time"] = time();
	$_SESSION["random"] = $random;
}
//Redirect if session expired 
$session_left = $session_expired - (time() - $_SESSION["session_time"]);
if((time() - $_SESSION["session_time"]) > $session_expired)
{
	unset($_SESSION['random']);
	unset($_SESSION['session_time']);
	header("Location: login.php");
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		  
		<title>PAW Login</title>
		<link rel="stylesheet" href="css/style.css">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
		<script type="text/javascript" src="https://cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js"></script>	
		<style>
			#qrcode {
			  width:200px;
			  height:200px;
			  margin-top:30px;
			  margin-bottom:30px;
			}
		</style>
	</head>
	<body>
		<div class="wrapper active">
		<?php if (isset($_SESSION["login_account"]) && $_SESSION["login_check"] == TRUE){?>
			<header>
				<h1>Login Successful! <img src="favicon-32x32.png"></h1>
		  		<p>Your PAW address:</p>
			</header>
			
			<div class="form">
				<form action="logout.php" method="post">
					<input type="text" name="paw_address" spellcheck="false" minlength="64" maxlength="64" placeholder="PAW address" required="" value="<?php echo $_SESSION["login_account"];?>">
					<button>Logout</button>
				</form>
			</div>
		 <?php }
		 else {?>
			
		  <header>
			<h1>Login with PAW <img src="favicon-32x32.png"></h1>
		  </header>
		  
		  <?php 
		  	if ($_POST) {
		  ?>
		  <p>Send <?php echo $_SESSION["random"];?> 
			PAW to your own address:</p>
		  <div class="form">
		  	<form action="" method="post">
			<input style="font-size:14px;" type="text" name="paw_address" spellcheck="false" minlength="64" maxlength="64" readonly="" placeholder="PAW address" required="" value="<?php if ($_POST) { echo trim($_POST['paw_address']);}?>">
		  	</form>
		  </div>
			<div>Waiting for your payment. <br/>Session restart in <strong><span id="time"><?php echo gmdate('i:s', $session_left);?></span></strong> minutes!</div>	  	
		  	<div class="qrcode" id="qrcode"></div>
			<script>
				/*QR Code generator*/
				function makeCode () {		
					var qrcode = new QRCode(document.getElementById("qrcode"), {
						width : 200,
						height : 200,
						colorDark : "#000000",
						colorLight : "#ffffff",
						correctLevel : QRCode.CorrectLevel.H
					});
					var text = "<?php echo $_POST['paw_address'];?>";
					qrcode.makeCode(text);
				}
				makeCode();
				/*END QR code*/
			</script>
			
		 	<script>
				/*Count down timer*/
				function startTimer(duration, display) {
					var timer = duration, minutes, seconds;
					setInterval(function () {
						minutes = parseInt(timer / 60, 10)
						seconds = parseInt(timer % 60, 10);
				
						minutes = minutes < 10 ? "0" + minutes : minutes;
						seconds = seconds < 10 ? "0" + seconds : seconds;
				
						display.textContent = minutes + ":" + seconds;
				
						if (--timer < 0) {
							timer = duration;
							window.location.replace('login.php');
						}
					}, 1000);
				}
				
				window.onload = function () {
					var session_duration = <?php echo $session_left;?>,
						display = document.querySelector('#time');
					startTimer(session_duration, display);
				};
				/*END Countdown*/
			</script>
			

			<script>
			/*CHECK PAYMENT INTERMITTENTLY*/
			//read multiple fetch example: https://stackoverflow.com/questions/40981040/using-a-fetch-inside-another-fetch-in-javascript
			//read set timeout https://stackoverflow.com/questions/6685396/execute-the-setinterval-function-without-delay-the-first-time
			//https://www.freecodecamp.org/news/javascript-settimeout-how-to-set-a-timer-in-javascript-or-sleep-for-n-seconds/
			(function timeout() {			

						//get account history 
						fetch('https://rpc.paw.digital/', {
							method: 'post',
							body: JSON.stringify({'action': 'account_history', 'account': '<?php echo trim($_POST["paw_address"]);?>', 'count': '10'}),
							mode: 'cors',
							headers: new Headers({
								'Content-Type': 'application/json'
							})
						}).then(function(response){ 
							response.json().then( function(data){
							//alert(data.history[0].confirmed);
							
								console.log(data);
								if (data.account == data.history[0].account && data.history[0].type == "receive" && data.history[0].account == "<?php echo trim($_POST["paw_address"]);?>" && data.history[0].amount/10**27 == <?php echo $_SESSION["random"];?> && data.history[0].confirmed == "true") {
								
									//send ajax post to php script, set session login and come back to login.php
									//https://stackoverflow.com/questions/8567114/how-can-i-make-an-ajax-call-without-jquery
									//https://stackoverflow.com/questions/41707032/making-post-request-using-fetch
									//https://phpenthusiast.com/blog/javascript-fetch-api-tutorial
									if (data.account == data.history[1].account && data.history[1].type == "send" && data.history[1].account == "<?php echo trim($_POST["paw_address"]);?>" && data.history[1].amount/10**27 === <?php echo $_SESSION["random"];?> && data.history[1].confirmed == "true") {
										var json_data = {
											"account": data.history[0].account,
											"amount": data.history[0].amount,
											"random": <?php echo $_SESSION["random"];?>
										}
										fetch('ajax.php', {
											method : 'post',
											mode: 'cors', //cors, no-cors, same-origin
											headers: {
											  'Content-Type': 'application/json', //sent request
											  'Accept': 'application/json', //expected data sent back
											  'X-Requested-With': 'XMLHttpRequest',
											  //'credentials': 'same-origin'
											},
											body: JSON.stringify(json_data)
										})
										.then((res) => res.json())
										.then(function(result) {
											console.log(result);
											//Refresh window if login success from ajax call
											if(data.history[1].account === result.account && result.success == 1){
												window.location.replace('login.php');
											}
										})
										.catch((error) => console.log(error))
										clearTimeout(timeoutId);
										//console.log('Timeout ID ' + timeoutId + ' has been cleared');
									}
								}
								else{
									//alert('session ID changed');
								}
							});
						}).catch(function (failed) {
							console.log('Request failed', failed);
						});

				const timeoutId = setTimeout(timeout, 8000);
			})();
			</script>
		 <?php } else {?>
		 
		  <p>Enter your PAW address:</p>
		  <div class="form">
		  	<form action="" method="post">
			<input style="font-size:14px;" autocomplete="off" type="text" name="paw_address" spellcheck="false" minlength="64" maxlength="64" placeholder="PAW address" required="" value="<?php if ($_POST) { echo trim($_POST['paw_address']);}?>">
			<button>Login</button>
		  	</form>
		  </div>
		 <?php }?>
		 <?php }?>
		</div>
	
	</body>
</html>