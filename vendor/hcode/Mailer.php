<?php

	namespace Hcode;

	use Rain\Tpl;

	/**
	 * Classe para envio de email
	 */
	class Mailer
	{
		
		const USERNAME  = 'edlimaf.ef@gmail.com';
		const PASSWORD  = '*******';
		const NAME_FROM = 'Lojas Lima & Cia.';

		private $mail;

		/**
		* Construtor
		*/
		public function __construct($toAddress, $toName, $subject, $tplName, $data = array())
		{
	        
	      $config = array(
				"tpl_dir"       => $_SERVER['DOCUMENT_ROOT'] .'/views/email/',
				"cache_dir"     => $_SERVER['DOCUMENT_ROOT'] ."/views-cache/",
				"debug"         => false // set to false to improve the speed
			);

			Tpl::configure($config);

			$tpl = new Tpl;  

			foreach ($data as $key => $value) {
				$tpl->assign($key, $value);
			}

			$html = utf8_decode($tpl->draw($tplName, true));
	    	
			//Create a new PHPMailer instance
			$this->mail = new \PHPMailer;

			//Tell PHPMailer to use SMTP
			$this->mail->isSMTP();

			//Enable SMTP debugging
			// 0 = off (for production use) << Em produção >>
			// 1 = client messages << Em teste >>
			// 2 = client and server messages << Em desenvolvimento >>
			$this->mail->SMTPDebug = 0;

			//Set the hostname of the mail server
			$this->mail->Host = 'smtp.gmail.com'; // Pode conter (N)Servidores bastando colocar ponto e virgula (;).
			// use
			// $mail->Host = gethostbyname('smtp.gmail.com');
			// if your network does not support SMTP over IPv6

			//Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
			$this->mail->Port = 587;

			//Set the encryption system to use - ssl (deprecated) or tls
			$this->mail->SMTPSecure = 'tls';

			//Whether to use SMTP authentication
			$this->mail->SMTPAuth = true;

			//Username to use for SMTP authentication - use full email address for gmail
			$this->mail->Username = Mailer::USERNAME;    //"edlimaf.ef@gmail.com";

			//Password to use for SMTP authentication
			$this->mail->Password = Mailer::PASSWORD;

			//Set who the message is to be sent from
			$this->mail->setFrom(Mailer::USERNAME, Mailer::NAME_FROM);

			//Set an alternative reply-to address
			//$mail->addReplyTo('replyto@example.com', 'First Last'); // Não é obrigatorio

			//Set who the message is to be sent to
			$this->mail->addAddress($toAddress, $toName);

			//Set the subject line
			$this->mail->Subject = $subject;

			//Read an HTML message body from an external file, convert referenced images to embedded,
			//convert HTML into a basic plain-text alternative body
			$this->mail->msgHTML($html);

			//Replace the plain text body with one created manually
			$this->mail->AltBody = 'This is a plain-text message body';

			//Attach an image file
			//$mail->addAttachment('images/phpmailer_mini.png'); // não 

	   }


	   public function send()
	   {
	   	return $this->mail->send();
	   }
	}

?>