<?php
// Include phpCAS source
include_once('CAS-1.3.3/CAS-1.3.3/CAS.php');
include_once('authorize.php');

// CAS URL parameters
$cas_host = 'auth.vt.edu';
$cas_context = '';
$cas_port = 443;
$cas_url = 'https://'.$cas_host.$cas_context;

// The "real" hosts that send SAML logout messages
$cas_real_hosts = array(
  'cas-1.middleware.vt.edu',
  'cas-2.middleware.vt.edu',
  'cas-3.middleware.vt.edu'
);

// Uncomment to enable debugging
phpCAS::setDebug('logs/phpcas.log');

// Initialize phpCAS
phpCAS::client(SAML_VERSION_1_1, $cas_host, $cas_port, $cas_context);

// Set the CA certificate chain for CAS server cert
// The VT Global CA chain is available at
// https://filebox.vt.edu/users/serac/pub/vtgsca_chain.pem
phpCAS::setCasServerCACert('certificates/vt-global-ca-chain.pem');

// Handle SAML logout requests that emanate from the CAS host exclusively.
// Failure to restrict SAML logout requests to authorized hosts could
// allow denial of service attacks where at the least the server is
// tied up parsing bogus XML messages.
phpCAS::handleLogoutRequests(true, $cas_real_hosts);

// Force CAS authentication on any page that includes this file
phpCAS::forceAuthentication(true);

// Attributes related to user login. Not useful for this application but might help later.
//$attributes = phpCAS::getAttributes();

// Authorize User
$user = phpCAS::getUser();
$result = authorizeUser($user);

if (is_null($result)) 
{
	header('X-PHP-Response-Code: 403', true, 403);
	echo 'Unfortunately you are not an authorized HokiePRIDE exec board member.'."\n";
	return;
}


?>

<h1>Student Area</h1>

<strong>Welcome 

	<?php 
	$resArr = $result->fetch_assoc();
	echo $resArr['firstname'].' '.$resArr['lastname'];
	?>

!</strong><br>

