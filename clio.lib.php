<?php

/**
 * @file
 * Integration layer to communicate with the Clio API.
 * 
 * @see http://api-docs.clio.com/v2/index.html
 *
 * @author Theodis Butler.
 */


/**
 * The hole story
 *-----------------------------------------------------------------------------
 * +--------+                                           +---------------+
 * |        |--(A)------- Authorization Grant --------->|               |
 * |        |                                           |               |
 * |        |<-(B)----------- Access Token -------------|               |
 * |        |               & Refresh Token             |               |
 * |        |                                           |               |
 * |        |                            +----------+   |               |
 * |        |--(C)---- Access Token ---->|          |   |               |
 * |        |                            |          |   |               |
 * |        |<-(D)- Protected Resource --| Resource |   | Authorization |
 * | Client |                            |  Server  |   |     Server    |
 * |        |--(E)---- Access Token ---->|          |   |               |
 * |        |                            |          |   |               |
 * |        |<-(F)- Invalid Token Error -|          |   |               |
 * |        |                            +----------+   |               |
 * |        |                                           |               |
 * |        |--(G)----------- Refresh Token ----------->|               |
 * |        |                                           |               |
 * |        |<-(H)----------- Access Token -------------|               |
 * +--------+           & Optional Refresh Token        +---------------+
 * 
 *                 ---------------------------------
 * The flow illustrated in Figure includes the following steps:
 *
 * (A)  The client requests an access token by authenticating with the
 *       authorization server, and presenting an authorization grant.
 *  (B)  The authorization server authenticates the client and validates
 *       the authorization grant, and if valid issues an access token and
 *       a refresh token.
 *  (C)  The client makes a protected resource request to the resource
 *       server by presenting the access token.
 *  (D)  The resource server validates the access token, and if valid,
 *       serves the request.
 *  (E)  Steps (C) and (D) repeat until the access token expires.  If the
 *       client knows the access token expired, it skips to step (G),
 *       otherwise it makes another protected resource request.
 *  (F)  Since the access token is invalid, the resource server returns
 *       an invalid token error.
 *  (G)  The client requests a new access token by authenticating with
 *       the authorization server and presenting the refresh token.  The
 *       client authentication requirements are based on the client type
 *       and on the authorization server policies.
 *  (H)  The authorization server authenticates the client and validates
 *       the refresh token, and if valid issues a new access token (and
 *       optionally, a new refresh token).
 * ----------------------------------------------------------------------------
 * response_type:    code
 *  client_id:        application key from above
 * redirect_uri:     callback URL to redirect to after authorization
 * state (optional): Can be used by your application to maintain state between the request and the callback
 * -------------------------------------
 * 1) Request the authorization code
 *
 * https://app.goclio.com/oauth/authorize?response_type=code&client_id=fzaXZvrLWZX747wQQRNuASeVCBxaXpJaPMDi7F96&redirect_uri=http%3A%2F%2Fyourapp.com%2Fcallback&state=xyz
 *
 * --------------------------------------
 * 2) Approve the authorization access
 *
 * After the user approval, the URL will change to http://[REDIRECT_URI]?code=[CODE]
 * And now we have the [CODE] .
 * http://yourapp.com/callback?code=s9jGYmL8E00ZyuJP3AEO&state=xyz
 * 
 * Decline the authorization
 * http://yourapp.com/callback?error=access_denied&state=xyz
 * --------------------------------------
 * 3) Request the Bearer token
 * client_id=fzaXZvrLWZX747wQQRNuASeVCBxaXpJaPMDi7F96&client_secret=xVp5wAX05g1oDjV5astg2KZIZ85NX31FKTPV876v&grant_type=authorization_code&code=s9jGYmL8E00ZyuJP3AEO&redirect_uri=http%3A%2F%2Fyourapp.com%2Fcallback
 * 
 * <POSTRequest>
 * https://app.goclio.com/oauth/token
 *    grant_type=authorization_code&
 *    code=[CODE]&
 *    redirect_uri=[REDIRECT_URI]&
 *    client_id=[CLIENT_ID]&
 *    client_secret=[CLIENT_SECRET]
 * </POSTRequest>
 * 
 * The server will respond with an access token as JSON format:
 * 
 * <Respond>
 * {
 *	  "token_type": "bearer",
 *	  "access_token": "c0lQ2WLYW9qAZ9RH12cH1fJPzVWSscXP",
 * }
 * </Respond>
 *  
 * --------------------------------------
 * 4) Access the API using the Bearer token
 *
 * <GETRequest>
 * https://app.goclio.com/api/v2/users/who_am_i
 * "Authorization: Bearer c0lQ2WLYW9qAZ9RH12cH1fJPzVWSscXP"
 * </GETRequest>
 * 
 * The respond of this request is:
 * 
 * <Respond>
 * {
 *	  "account": {
 *		"image": "https://0.s3.envato.com/files/100000009/0006893824_192.jpg",
 *		"firstname": "Test",
 *		"surname": "User",
 *		"available_earnings": "0.00",
 *		"total_deposits": "0.00",
 *		"balance": "0.00",
 *		"country": "Australia"
 *	  }
 *	}
 * </Respond>
 * 
 * 
 */

/**
 * Exception handling class.
 */
class EnvatoException extends Exception {}

/**
 * Primary Clio API implementation class
 */
class Clio {

  /**
   *
   * @var
   * string
   */
  //public $client_id;
  
  /**
   *
   * @var
   * string
   */
  //public $client_secret;
    
  /**
   *
   * @var
   * JSON
   */
  public $token;
    
  
  
  /************************************************
   * Authentication
   ***********************************************/
  
  /**
   * Constructor for the Clio class
   * $client_id
   * $client_secret
   */
  public function __construct() {
  }
  
  /**
   *
   *
   */
  public function get_authorization_url($redirect_uri) {
    //https://app.goclio.com/authorization?response_type=code&client_id=[CLIENT ID]&redirect_uri=[REDIRECT URI]
    $client_id                   = variable_get('clio_client_id', '');
    //$client_secret               = variable_get('clio_client_secret', '');
    $url = variable_get('clio_api', CLIO_API) . '/oauth/authorization';
    $url .= "?response_type=code&client_id=".$client_id."&redirect_uri=".$redirect_uri;
    return $url;
  }

  /**
   *
   *
   */
  public function get_authentication_url() {
    //https://app.goclio.com/oauth/token
    $url = variable_get('clio_api', CLIO_API) . '/oauth/token';
    return $url;
  }
  
  
  //is_authorization
  /**
   * Just set boolean value to the $this->is_authorization.
   *
   * @param $boolean
   *   Boolean to assign to the $this->is_authorization.
   */
  public function set_is_authorization($boolean) {
    $this->is_authorization = $boolean;
  }
  /**
   * Authorization for the Clio APP.
   * @see http://api-docs.clio.com/
   *
   * @param string $redirect_uri
   *   String to append to the request.
   */
  public function get_authorization($redirect_uri) {
    $url = $this->get_authorization_url($redirect_uri);
    drupal_goto($url);
  }
  
  /**
   * Request an "access token" for the Envato API.
   * @see http://api-docs.clio.com/
   *
   * @param string $redirect_uri
   *   String to append to the request.
   * @return
   *   JSON object that has variable "access token" on it, or FALSE when there was an error.
   */
  public function get_authentication($code, $redirect_uri) {
    $url = $this->get_authentication_url();

    /** 
    * Adding parameters to request()
    *
    * grant_type=authorization_code&
    * code=[CODE]&
    * redirect_uri=[REDIRECT_URI]&
    * client_id=[CLIENT_ID]&
    * client_secret=[CLIENT_SECRET]
    */
    $parameters = array();
    
  	$parameters['grant_type']    = "authorization_code";
  	$parameters['code']          = $code;
  	$parameters['redirect_uri']  = $redirect_uri;
  	$parameters['client_id']     = variable_get('clio_client_id', '');
  	$parameters['client_secret'] = variable_get('clio_client_secret', '');
		$is_authorization            = FALSE;
    $method = 'POST';
	
    try {
      $response = $this->request($url, $parameters, $method,$is_authorization );
    }
    catch (ClioException $e) {
      watchdog('clio', '!message', array('!message' => $e->__toString()), WATCHDOG_ERROR);
      return FALSE;
    }
    $token = clio_json_decode($response);
    return $token;
  }
  
  
  /**
   * Performs a request.
   *
   * @throws EnvatoException
   */
  protected function request($url, $params = array(), $method = 'GET', $is_authorization = TRUE) {
        
    $data = '';
    if (count($params) > 0) {
      if ($method == 'GET') {
        $url .= '?'. http_build_query($params, '', '&');
      }
      else {
        $data = http_build_query($params, '', '&');
      }
    }

    $headers = array();
  	$headers['User-Agent'] = variable_get('clio_app_name', CLIO_APP_NAME);
  	$headers['Content-Type'] = 'application/x-www-form-urlencoded;charset=UTF-8';

  	if($is_authorization){
      $headers['Authorization'] = "bearer {$this->token['access_token']}";
  	}
    
    $response = $this->doRequest($url, $headers, $method, $data);
    ///dsm($response);
	
    if (!isset($response->error)) {

      return $response->data;
    }
    else {
      $error = $response->error;
      $check = clio_json_decode($response->data);
      
      if($check['error_description'] == "Token already expired"){
        $this->refresh_token();
        return array('refreshed'=>TRUE);
      }else{
        throw new EnvatoException($error);
      }
    }
	
  }

  /**
   * Actually performs a request.
   *
   * This method can be easily overriden through inheritance.
   *
   * @param string $url
   *   The url of the endpoint.
   * @param array $headers
   *   Array of headers.
   * @param string $method
   *   The HTTP method to use (normally POST or GET).
   * @param array $data
   *   An array of parameters
   * @return
   *   stdClass response object.
   */
  protected function doRequest($url, $headers, $method, $data) {
    $params = array('headers' => $headers, 'method' => $method, 'data' => $data);
    return drupal_http_request($url, $params);
  }
  
  /**
   * Creates an API endpoint URL.
   *
   * @param string $path
   *   The path of the endpoint.
   * @param string $format
   *   The format of the endpoint to be appended at the end of the path.
   * @return
   *   The complete path to the endpoint.
   */
  protected function create_url($path, $format = '.json') {
    $url =  variable_get('clio_api', CLIO_API) .'/v2/'. $path . $format;
    return $url;
  }
  
  
  protected function call_parameters($url, $params, $method, $default_value = NULL, $refresh = FALSE){
    $variables = &drupal_static(__FUNCTION__,$default_value,$refresh);
    if (!isset($variables)) {
      // generate contents of static variable
      $variables['url']     = $url;
      $variables['params']  = $params;
      $variables['method']  = $method;
    }
    return $variables;
  }
  

  /**
   *    REFRESH TOKEN
   */
  protected function refresh_token(){
    ///global $user;
    // . '/token?grant_type=refresh_token&refresh_token='.$this->token['refresh_token'];
    $url = variable_get('clio_api', CLIO_API).'/token'; 

    $parameters = array();
    $parameters['grant_type'] = "refresh_token";

    ///$parameters['refresh_token']  = $user->refresh_token;
    $parameters['refresh_token']  = $this->token['refresh_token'];
    $parameters['redirect_uri']   = variable_get('clio_app_redirect_uri', CLIO_APP_REDIRECT_URI);//$redirect_uri;
    
    $parameters['client_id']      = variable_get('clio_client_id', '');
    $parameters['client_secret']  = variable_get('clio_client_secret', '');
    
    $method = 'POST';
    $is_authorization = FALSE;
    try {
      $response = $this->request($url, $parameters, $method,$is_authorization);
    }
    catch (EnvatoException $e) {
      watchdog('envato', '!message', array('!message' => $e->__toString()), WATCHDOG_ERROR);
      return FALSE;
    }
    $new_token = clio_json_decode($response);
    
    $this->token['access_token'] = $new_token['access_token'];
        
    module_invoke_all('clio_refresh_token', $new_token);
    
  }

  /**
   * Calls a Clio API endpoint.
   * 
   * @return
   *   JSON data as respond.
   */
  public function call($path, $params = array(), $method = 'GET') {
    
    $url = $this->create_url($path);
    $call_params  = $this->call_parameters($url, $params, $method);   

    $response = '';
    try {
      $response = $this->request($call_params['url'], $call_params['params'], $call_params['method']);
      if(isset($response['refreshed'])){
        $response = $this->request($call_params['url'], $call_params['params'], $call_params['method']);
      }
    }
    catch (ClioException $e) {
      watchdog('clio', '!message', array('!message' => $e->__toString()), WATCHDOG_ERROR);
      return FALSE;
    }
    
    if (!$response) {
      return FALSE;
    }

    return clio_json_decode($response);
  }





  /* ----------------------------------------------------------------------------------------------- */
  /* --------------- http://api-docs.clio.com/ -------------------------------------------------- */
  /* ----------------------------------------------------------------------------------------------- */

  /****************************************************
  * Activities                     *
  *****************************************************/


  /****************************************************
  * User Details                                      *
  *****************************************************/
  
  /**
   * User account details.
   * 
   * @return
   *   JSON data as respond.
   */
  public function get_user($user_name){
    return $this->call("market/user:".$user_name);
  }
  
  /**
   * Get a user's username.
   * 
   * @return
   *   JSON data as respond.
   */
  public function get_user_name(){
    return $this->call("market/private/user/username");
  }
  
  /**
   * Get a user's email.
   * 
   * @return
   *   JSON data as respond.
   */
  public function get_user_email(){
	 return $this->call("market/private/user/email");
  }
  
  /**
   * List a user's badges.
   * 
   * @return
   *   JSON data as respond.
   */
  public function get_user_badges($user_name){
	 return $this->call("market/user-badges:".$user_name);
  }
  
  /**
   * A user's items by site.
   * 
   * Show the number of items an author has for sale on each site. Requires a username, e.g. collis
   *
   * @return
   *   JSON data as respond.
   */
  public function get_user_items_by_site($user_name){
	 return $this->call("market/user-items-by-site:".$user_name);
  }
  
  /**
   * New items by user.
   * 
   * Shows the newest 25 files a user has uploaded to a particular site. 
   * Requires username and site parameters, e.g. new-files-from-user:collis,themeforest
   *
   * @return
   *   JSON data as respond.
   */
  public function get_new_files_from_user($user_name,$site){
	 return $this->call("market/new-files-from-user:".$user_name.",".$site);
  }
  
  /****************************************************
  * Private User Details                              *
  *****************************************************/ 
  
  /**
   * User account details.
   * 
   * Returns the first name, surname, earnings available to withdraw,
   * total deposits, balance (deposits + earnings) and country.
   *
   * @return
   *   JSON data as respond.
   */
  public function get_private_user_account(){
   return $this->call("market/private/user/account");
  }

  /**
   * Sales by month.
   * 
   * Returns the monthly sales data, as displayed on the user's earnings page.
   *
   * @return
   *   JSON data as respond.
   */
  public function get_private_user_earnings_and_sales_by_month(){
   return $this->call("market/private/user/earnings-and-sales-by-month");
  }

  /**
   * Statement data.
   * 
   * Returns the last 100 events as seen on the user's statement page. Only shows data from the last 28 days.
   *
   * @return
   *   JSON data as respond.
   */
  public function get_private_user_statement(){
   return $this->call("market/private/user/statement");
  }

  /**
   * Most recent sales.
   * 
   * Shows the 50 most recent sales of the user's items.
   *
   * @return
   *   JSON data as respond.
   */
  public function get_private_user_recent_sales(){
   return $this->call("market/private/user/recent-sales");
  }

  /**
   * Download a purchase.
   * 
   * URL to download an item you have purchased. Requires a purchase code,
   * e.g. download-purchase:550e8400-e29b-41d4-a716-446655440000.
   *
   * @return
   *   JSON data as respond.
   */
  public function get_private_user_download_purchase($purchase_code){
   return $this->call("market/private/user/download-purchase:".$purchase_code);
  }

  /**
   * Verify purchase code.
   * 
   * Details of an item you have sold. Requires a purchase code,
   * e.g. verify-purchase:550e8400-e29b-41d4-a716-446655440000..
   *
   * @return
   *   JSON data as respond.
   */
  public function get_private_user_verify_purchase($purchase_code){
   return $this->call("market/private/user/verify-purchase:".$purchase_code);
  }

  /****************************************************
  * Get All Activities                              *
  *****************************************************/
public function get_all_activities(){
   return $this->call("api/v2/activities:".$purchase_code);
  }

  /****************************************************
  * Get An Activity                             *
  *****************************************************/

  /****************************************************
  * Create An Activity                             *
  *****************************************************/
  
   /****************************************************
  * Update An Activity                             *
  *****************************************************/
 /****************************************************
  * Delete An Activity                             *
  *****************************************************/
}
